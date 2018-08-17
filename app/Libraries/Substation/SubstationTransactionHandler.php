<?php

namespace App\Libraries\Substation;

use App\Libraries\Substation\Substation;
use App\Models\EscrowAddressLedgerEntry;
use App\Models\UserMeta;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use App\Repositories\EscrowAddressRepository;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Models\Distribution;
use Models\DistributionTx;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;

class SubstationTransactionHandler
{

    const CONFIRMATIONS_REQUIRED = 2;

    public function __construct(EscrowAddressRepository $escrow_address_repository, EscrowAddressLedgerEntryRepository $escrow_address_ledger_entry_repository)
    {
        $this->escrow_address_repository = $escrow_address_repository;
        $this->ledger = $escrow_address_ledger_entry_repository;
    }

    public function handleSubstationTransactionPayload($payload)
    {
        DB::transaction(function () use ($payload) {
            $this->processDebits($payload['debits'], $payload);
            $this->processCredits($payload['credits'], $payload);
        });

    }
    // ------------------------------------------------------------------------

    protected function processCredits($credits, $transaction)
    {
        foreach ($credits as $credit) {
            // check escrow address credit
            $this->processEscrowAddressEntry('credit', $credit, $transaction);

            // check fuel address credit
            $this->processFuelAddressCredit($credit, $transaction);

            // check distribution address credits
            $this->processDistributionAddressCredit($credit, $transaction);
        }
    }

    protected function processDebits($debits, $transaction)
    {
        foreach ($debits as $debit) {
            // check escrow address debit
            $this->processEscrowAddressEntry('debit', $debit, $transaction);

            $this->processDistributionAddressDebit($debit, $transaction);

            $this->processFuelAddressDebit($debit, $transaction);
        }
    }

    protected function processFuelAddressCredit($entry, $transaction)
    {
        $address = $entry['address'];
        $asset = $entry['asset'];
        $txid = $transaction['txid'];
        $confirmations = $transaction['confirmations'];
        $amount = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);
        $amount_sat = $amount->getSatoshisString();
        $time = timestamp();

        // if this is a change address, ignore it
        if ($this->creditIsChangeOutput($entry, $transaction)) {
            return;
        }

        // find a fuel address
        // DW note: This needs to be optimized in the future.  UserMeta values are not indexed, so this query will slow down with a lot of fuel address entries
        $fuel_address = UserMeta::where('metaKey', 'fuel_address')->where('value', $address)->first();
        if (!$fuel_address) {
            // ignore this transaction - it isn't related to the fuel address
            return;
        }

        $user_id = $fuel_address->userId;
        $user = User::find($user_id);
        $fuel_address_uuid = UserMeta::getMeta($user_id, 'fuel_address_uuid');

        $fuel_deposit = DB::table('fuel_deposits')->where('txid', $txid)->first();
        $valid_assets = Config::get('settings.valid_fuel_tokens');
        $min_conf = Config::get('settings.min_fuel_confirms');

        if (!$fuel_deposit and isset($valid_assets[$asset])) {
            $tx_data = [
                'user_id' => $user_id,
                'asset' => $asset,
                'created_at' => $time,
                'updated_at' => $time,
                'quantity' => $amount_sat,
                'fuel_quantity' => $amount_sat,
                'txid' => $txid,
                'confirmed' => 0,
            ];
            if ($confirmations >= $min_conf) {
                $tx_data['confirmed'] = 1;
            }
            DB::table('fuel_deposits')->insert($tx_data);
        }
        if ($fuel_deposit and $fuel_deposit->confirmed == 1) {
            //already confirmed
            EventLog::debug('fuel.alreadyConfirmed', [
                'txid' => $txid,
                'address' => $address,
                'confirmations' => $confirmations,
            ]);
            return;
        }

        if ($asset == 'BTC') {
            //credit direct BTC fuel
            $amount = intval($amount_sat);

            // update the user's fuel balance
            try {
                $this->updateFuelBalance($user);
            } catch (Exception $e) {
                // exceptions are ok here
            }

            if ($confirmations >= $min_conf) {
                if ($fuel_deposit) {
                    DB::table('fuel_deposits')->where('txid', $txid)->update([
                        'confirmed' => 1,
                        'updated_at' => $time,
                    ]);
                }
                EventLog::info('fuel.deposited', [
                    'userId' => $user_id,
                    'txid' => $txid,
                    'address' => $address,
                    'amount' => $amount_sat,
                    'asset' => $asset,
                ]);
            }
        } elseif (isset($valid_assets[$asset])) {
            // other assets (TOKENLY) are not implemented at this time
            EventLog::logError('fuelDeposit.notImplemented', [
                'userId' => $user_id,
                'txid' => $txid,
                'address' => $address,
                'amount' => $amount_sat,
                'asset' => $asset,
            ]);
            return;

            // if ($confirmations >= $min_conf and
            //     (!$fuel_deposit or ($fuel_deposit and $fuel_deposit->confirmed == 0))) {
            //     //swap asset for fuel
            //     $quote = Fuel::getFuelQuote($asset, $amount_sat);
            //     if ($quote) {
            //         if ($fuel_deposit) {
            //             $save = DB::table('fuel_deposits')->where('txid', $txid)->update(array('confirmed' => 1, 'fuel_quantity' => $quote));
            //             if (!$save) {
            //                 Log::error('Error saving fuel deposit ' . $txid);
            //                 die();
            //             }
            //         }
            //         Log::info('Fuel swap quote: ' . $asset . ' - ' . $quote);
            //         Fuel::masterFuelSwap($user_id, $asset, 'BTC', $amount_sat, $quote);
            //     }
            // }
        }

    }

    protected function processDistributionAddressCredit($entry, $transaction)
    {
        $address = $entry['address'];
        $asset = $entry['asset'];
        $txid = $transaction['txid'];
        $confirmations = $transaction['confirmations'];
        $amount = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);
        $amount_sat = $amount->getSatoshisString();
        $time = timestamp();

        // find a matching distribution
        $distribution = Distribution::where('deposit_address', $address)->first();
        if (!$distribution) {
            return;
        }

        // if this distribution is at stage 3 or greater, check for a confirmed prime transaction
        if ($distribution->stage >= 3) {
            $this->processDistributionPrimingTransactionCredit($entry, $transaction, $distribution);
            return;
        }

        // log the distribution deposit
        EventLog::debug('distribution.depositReceived', [
            'distributionId' => $distribution->id,
            'txid' => $txid,
            'asset' => $asset,
            'amount' => $amount_sat,
            'confirmations' => $confirmations,
        ]);

        // if completed then warn and ignore
        if ($distribution->complete) {
            EventLog::warning('distributionDeposit.alreadyComplete', [
                'distributionId' => $distribution->id,
                'txid' => $txid,
                'asset' => $asset,
                'amount' => $amount_sat,
                'confirmations' => $confirmations,
            ]);
            return;
        }

        $user_id = $distribution->user_id;

        $min_conf = Config::get('settings.min_distribution_confirms');
        if ($asset == 'BTC' or $asset == $distribution->asset) {
            $tx_record = DB::table('distribution_deposits')->where('txid', $txid)->first();
            if (!$tx_record) {
                // create a new distribution deposit tx record
                $tx_data = [
                    'distribution_id' => $distribution->id,
                    'asset' => $asset,
                    'created_at' => $time,
                    'updated_at' => $time,
                    'quantity' => $amount_sat,
                    'txid' => $txid,
                    'confirmed' => $confirmations >= $min_conf ? 1 : 0,
                ];
                DB::table('distribution_deposits')->insert($tx_data);

                $distribution->setMessage('receiving');
                $distribution->sendWebhookUpdateNotification();
            } else {
                if ($tx_record->confirmed == 1) {
                    // already confirmed
                    EventLog::debug('distributionCredit.alreadyConfirmed', [
                        'distributionId' => $distribution->id,
                        'txid' => $txid,
                    ]);
                } else {
                    if ($confirmations >= $min_conf) {
                        DB::table('distribution_deposits')->where('id', $tx_record->id)->update([
                            'confirmed' => 1,
                            'updated_at' => $time,
                        ]);
                    }
                }
            }

            // update the confirmed total that was received for this distribution
            $all_deposits = DB::table('distribution_deposits')->where('distribution_id', $distribution->id)->get();
            if ($all_deposits and count($all_deposits) > 0) {
                $token_total = 0;
                $fuel_total = 0;
                foreach ($all_deposits as $row) {
                    if ($row->confirmed == 1) {
                        if ($row->asset == 'BTC') {
                            $fuel_total += $row->quantity;
                        } elseif ($row->asset == $distribution->asset) {
                            $token_total += $row->quantity;
                        }
                    }
                }
                $distribution->asset_received = $token_total;
                $distribution->fee_received = $fuel_total;
                $distribution->save();
                $distribution->sendWebhookUpdateNotification();

                EventLog::debug('distribution.totalReceived', [
                    'distributionId' => $distribution->id,
                    'token' => CryptoQuantity::satoshisToValue($distribution->asset_received),
                    'fee' => CryptoQuantity::satoshisToValue($distribution->fee_received),
                    'asset' => $distribution->asset,
                ]);
            }
        }
    }

    protected function processDistributionPrimingTransactionCredit($entry, $transaction, $distribution)
    {
        $address = $entry['address'];
        $asset = $entry['asset'];
        $txid = $transaction['txid'];
        $confirmations = $transaction['confirmations'];
        $amount = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);
        $amount_sat = $amount->getSatoshisString();
        $time = timestamp();

        if ($distribution->complete == 0) {
            $user_id = $distribution->user_id;
            $min_conf = Config::get('settings.min_distribution_confirms');
            if ($asset == 'BTC') {
                //see if this is a priming transaction
                $tx_record = DB::table('distribution_primes')->where('txid', $txid)->first();
                if ($tx_record) {
                    if ($confirmations >= $min_conf) {
                        if ($tx_record->confirmed == 1) {
                            EventLog::debug('prime.alreadyConfirmed', [
                                'distributionId' => $distribution->id,
                                'txid' => $txid,
                                'address' => $address,
                                'confirmations' => $confirmations,
                            ]);
                        } else {
                            DB::table('distribution_primes')->where('id', $tx_record->id)->update([
                                'confirmed' => 1,
                                'updated_at' => $time,
                            ]);
                            EventLog::debug('prime.confirmed', [
                                'distributionId' => $distribution->id,
                                'txid' => $txid,
                                'address' => $address,
                                'confirmations' => $confirmations,
                            ]);
                        }
                    } else {
                        EventLog::debug('prime.unconfirmed', [
                            'distributionId' => $distribution->id,
                            'txid' => $txid,
                            'address' => $address,
                            'confirmations' => $confirmations,
                        ]);
                    }
                } else {
                    EventLog::warning('prime.noTxRecordFound', [
                        'distributionId' => $distribution->id,
                        'txid' => $txid,
                        'address' => $address,
                        'confirmations' => $confirmations,
                    ]);
                }
                return;
            }
        }

        EventLog::warning('distribution.unexpectedAsset', [
            'distributionId' => $distribution->id,
            'txid' => $txid,
            'asset' => $asset,
            'amount' => $amount_sat,
            'confirmations' => $confirmations,
        ]);
    }

    protected function processDistributionAddressDebit($entry, $transaction)
    {
        $destination = $transaction['credits'][0]['address'];
        $address = $entry['address'];
        $asset = $entry['asset'];
        $txid = $transaction['txid'];
        $confirmations = $transaction['confirmations'];
        $amount = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);
        $amount_sat = $amount->getSatoshisString();
        $time = timestamp();

        // find a matching distribution
        $distribution = Distribution::where('deposit_address', $address)->first();
        if (!$distribution) {
            return;
        }

        // ignore completed distributions
        if ($distribution->complete == 1) {
            EventLog::debug('distributionDebit.ignored', [
                'distributionId' => $distribution->id,
                'txid' => $txid,
                'asset' => $asset,
                'amount' => $amount_sat,
                'confirmations' => $confirmations,
            ]);
            return;
        }

        $user_id = $distribution->user_id;
        $min_conf = Config::get('settings.min_distribution_confirms');
        if ($asset == 'BTC') {
            // this is probably a priming transaction
            //   ignore it
        } else if ($asset == $distribution->asset) {
            //see if this matches up to an outgoing token distribution tx
            $tx_record = DistributionTx::where('distribution_id', $distribution->id)->where('destination', $destination)->first();
            if ($tx_record) {
                if ($tx_record->confirmed == 1) {
                    EventLog::debug('distribution.alreadyConfirmed', [
                        'txid' => $txid,
                        'address' => $address,
                        'confirmations' => $confirmations,
                    ]);
                } else {
                    //also make sure quantity matches up
                    if ($amount_sat == $tx_record->quantity) {
                        //now check for confirms
                        if ($confirmations >= $min_conf) {
                            $tx_record->txid = $txid;
                            $tx_record->confirmed = 1;
                            $tx_record->save();
                            EventLog::debug('distribution.confirmed', [
                                'txid' => $txid,
                                'address' => $address,
                                'destination' => $destination,
                                'confirmations' => $confirmations,
                            ]);
                        } else {
                            EventLog::debug('distribution.unconfirmed', [
                                'txid' => $txid,
                                'address' => $address,
                                'destination' => $destination,
                                'confirmations' => $confirmations,
                            ]);
                        }
                    } else {
                        EventLog::warning('distribution.quantityMismatch', [
                            'txid' => $txid,
                            'address' => $address,
                            'destination' => $destination,
                            'confirmations' => $confirmations,
                            'actualQuantity' => $amount_sat,
                            'expectedQuantity' => $tx_record->quantity,
                        ]);

                    }
                }
            } else {
                EventLog::warning('distribution.destinationNotFound', [
                    'txid' => $txid,
                    'address' => $address,
                    'destination' => $destination,
                    'confirmations' => $confirmations,
                ]);
            }
        } else {
            EventLog::warning('distribution.unexpectedDebit', [
                'distributionId' => $distribution->id,
                'txid' => $txid,
                'asset' => $asset,
                'amount' => $amount_sat,
                'confirmations' => $confirmations,
            ]);
        }
    }

    protected function processFuelAddressDebit($entry, $transaction)
    {
        $address = $entry['address'];
        $asset = $entry['asset'];
        $txid = $transaction['txid'];
        $confirmations = $transaction['confirmations'];
        $amount = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);
        $amount_sat = $amount->getSatoshisString();
        $time = timestamp();

        $valid_assets = Config::get('settings.valid_fuel_tokens');

        // find a fuel address
        // DW note: This needs to be optimized in the future.  UserMeta values are not indexed, so this query will slow down with a lot of fuel address entries
        $fuel_address = UserMeta::where('metaKey', 'fuel_address')->where('value', $address)->first();
        if (!$fuel_address) {
            // ignore this transaction - it isn't related to the fuel address
            return;
        }

        $user_id = $fuel_address->userId;
        $uuid = UserMeta::getMeta($user_id, 'fuel_address_uuid');
        $tx_record = DB::table('fuel_debits')->where('txid', $txid)->first();
        $min_conf = 1;
        if (!$tx_record) {
            if ($asset != 'BTC' and !isset($valid_assets[$asset])) {
                EventLog::logError('fuelDebit.invalidAsset', [
                    'userId' => $user_id,
                    'txid' => $txid,
                    'address' => $address,
                    'amount' => $amount_sat,
                    'asset' => $asset,
                ]);

                return;
            }

            $net_fuel_debited = $this->calculateNetDebit($entry, $transaction);
            $tx_data = [
                'user_id' => $user_id,
                'asset' => $asset,
                'created_at' => $time,
                'updated_at' => $time,
                'quantity' => $net_fuel_debited->getSatoshisString(),
                'txid' => $txid,
                'confirmed' => 0,
            ];
            if ($confirmations >= $min_conf) {
                $tx_data['confirmed'] = 1;
            }

            DB::table('fuel_debits')->insert($tx_data);
        } else {
            // tx record already exists
            if ($tx_record->confirmed == 1) {
                //already confirmed
                EventLog::logError('fuelDebit.alreadyConfirmed', [
                    'userId' => $user_id,
                    'txid' => $txid,
                    'address' => $address,
                    'confirmations' => $confirmations,
                ]);
            } else {
                // confirm
                DB::table('fuel_debits')->where('id', $tx_record->id)->update([
                    'confirmed' => 1,
                    'updated_at' => $time,
                ]);

                EventLog::logError('fuelDebit.confirmed', [
                    'userId' => $user_id,
                    'txid' => $txid,
                    'address' => $address,
                    'confirmations' => $confirmations,
                ]);
            }
        }

        // update the fuel balance
        if ($asset == 'BTC') {
            $user = User::find($user_id);
            try {
                $this->updateFuelBalance($user);
            } catch (Exception $e) {
                // exceptions are ok here
            }
        }
    }

    protected function updateFuelBalance(User $user)
    {
        $user_id = $user['id'];
        // Log::debug("updateFuelBalance \$user_id=".json_encode($user_id, 192));

        try {
            $substation = Substation::instance();
            $wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);

            // get substation balances.  The array looks like this:
            // [
            //   'BTC' => [
            //       'asset' => 'BTC',
            //       'confirmed' => Tokenly\CryptoQuantity\CryptoQuantity::fromSatoshis('1000000'),
            //       'unconfirmed' => Tokenly\CryptoQuantity\CryptoQuantity::fromSatoshis('1000000'),
            //   ],
            // ]
            $fuel_address_uuid = UserMeta::getMeta($user_id, 'fuel_address_uuid');
            $substation_balances = $substation->getCombinedAddressBalanceById($wallet_uuid, $fuel_address_uuid);
            Log::debug("updateFuelBalance \$substation_balances=" . json_encode($substation_balances, 192));

            $confirmed_balance = 0;
            $unconfirmed_balance = 0;
            if (isset($substation_balances['BTC']['confirmed'])) {
                $confirmed_balance = $substation_balances['BTC']['confirmed']->getSatoshisString();
            }
            if (isset($substation_balances['BTC']['unconfirmed'])) {
                // only use the unconfirmed balance if it is different than the confirmed balance
                if ($substation_balances['BTC']['unconfirmed']->getSatoshisString() != $confirmed_balance) {
                    $unconfirmed_balance = $substation_balances['BTC']['unconfirmed']->getSatoshisString();
                }
            }

            UserMeta::setMeta($user_id, 'fuel_balance', $confirmed_balance);
            UserMeta::setMeta($user_id, 'fuel_pending', $unconfirmed_balance);

            EventLog::info('fuel.balancesUpdated', [
                'userId' => $user_id,
                'confirmed' => $confirmed_balance,
                'unconfirmed' => $unconfirmed_balance,
            ]);
        } catch (Exception $e) {
            EventLog::logError('fuelBalance.error', $e, [
                'userId' => $user_id,
            ]);
            throw $e;
        }
    }

    protected function calculateNetDebit($entry, $transaction)
    {
        $net_debit = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);

        $address = $entry['address'];
        foreach ($transaction['credits'] as $credit) {
            if ($credit['address'] == $address) {
                $net_debit = $net_debit->subtract(Substation::buildCryptoQuantity($transaction['chain'], $credit['quantity']));
            }
        }

        return $net_debit;
    }

    protected function creditIsChangeOutput($entry, $transaction)
    {
        $address = $entry['address'];
        foreach ($transaction['debits'] as $debit) {
            if ($debit['address'] == $address) {
                return true;
            }
        }
        return false;
    }

    // ------------------------------------------------------------------------
    // escrow address

    protected function processEscrowAddressEntry($entry_type, $entry, $transaction)
    {
        $destination = $transaction['credits'][0]['address'];
        $address = $entry['address'];
        $asset = $entry['asset'];
        $txid = $transaction['txid'];
        $chain = $transaction['chain'];
        $confirmations = $transaction['confirmations'];
        $amount = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);
        $time = timestamp();

        // Log::debug("processEscrowAddressEntry ".json_encode($entry, 192));
        // find a merchant wallet matching the address
        $escrow_address = $this->escrow_address_repository->findByAddress($address);
        // Log::debug("processEscrowAddressEntry {$entry_type} \$address=$address \$escrow_address=".json_encode($escrow_address, 192));
        if ($escrow_address) {
            $prefix = $entry_type == 'credit' ? 'recv' : 'send';
            $tx_identifier = $prefix . ':' . $asset . ':' . $txid;
            $confirmed = ($confirmations >= self::CONFIRMATIONS_REQUIRED);

            // find or update the ledger entry by txid
            try {
                if ($entry_type == 'credit') {
                    $this->ledger->credit($escrow_address, $amount, $asset, EscrowAddressLedgerEntry::TYPE_DEPOSIT, $txid, $tx_identifier, $confirmed);
                } else if ($entry_type == 'debit') {
                    $this->ledger->debit($escrow_address, $amount, $asset, EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, $txid, $tx_identifier, $confirmed);
                }

                EventLog::debug('tx.escrowAddress', [
                    'walletId' => $escrow_address['id'],
                    'transactionType' => $entry_type,
                    'txid' => $txid,
                    'confirmations' => $confirmations,
                    'address' => $escrow_address['address'],
                    'chain' => $chain,
                    'asset' => $asset,
                    'amount' => $amount->getSatoshisString(),
                ]);
            } catch (Exception $e) {
                EventLog::logError('tx.escrowAddress.failed', $e, [
                    'walletId' => $escrow_address['id'],
                    'transactionType' => $entry_type,
                    'txid' => $txid,
                    'confirmations' => $confirmations,
                    'address' => $escrow_address['address'],
                    'chain' => $chain,
                    'asset' => $asset,
                    'amount' => $amount->getSatoshisString(),
                ]);
            }
        }

    }

}
