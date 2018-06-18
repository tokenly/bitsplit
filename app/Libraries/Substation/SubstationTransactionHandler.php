<?php

namespace App\Libraries\Substation;

use App\Libraries\Substation\Substation;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;
use UserMeta;

class SubstationTransactionHandler
{

    public function handleSubstationTransactionPayload($payload)
    {
        DB::transaction(function () use ($payload) {
            $this->processDebits($payload['debits'], $payload);
            $this->processCredits($payload['credits'], $payload);
        });

    }
    // ------------------------------------------------------------------------

    protected function processDebits($debits, $transaction)
    {
    }

    protected function processCredits($credits, $transaction)
    {
        foreach ($credits as $credit) {
            // check fuel deposits
            $this->processFuelAddressCredit($credit, $transaction);
        }
    }

    protected function processFuelAddressCredit($entry, $transaction)
    {
        $address = $entry['address'];
        $asset = $entry['asset'];
        $txid = $transaction['txid'];
        // $chain = $transaction['chain'];
        $confirmations = $transaction['confirmations'];
        $amount = Substation::buildCryptoQuantity($transaction['chain'], $entry['quantity']);
        $amount_sat = $amount->getSatoshisString();

        // find a fuel address
        // DW note: This needs to be optimized in the future.  UserMeta values are not indexed, so this query will slow down with a lot of fuel address entries
        $fuel_address = UserMeta::where('metaKey', 'fuel_address')->where('value', $address)->first();
        if ($fuel_address) {
            $user_id = $fuel_address->userId;
            $user = User::find($user_id);
            $fuel_address_uuid = UserMeta::getMeta($user_id, 'fuel_address_uuid');
            $time = timestamp();

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
                $save = DB::table('fuel_deposits')->insert($tx_data);
                if (!$save) {
                    throw new Exception('Error saving fuel deposit ' . $txid, 1);
                }
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
                    $substation_balances = $substation->getCombinedAddressBalanceById($wallet_uuid, $fuel_address_uuid);
                    $confirmed_balance = 0;
                    $unconfirmed_balance = 0;
                    if (isset($substation_balances['BTC']['confirmed'])) {
                        $confirmed_balance = $substation_balances['BTC']['confirmed']->getSatoshisString();
                        UserMeta::setMeta($user_id, 'fuel_balance', $confirmed_balance);
                    }
                    if (isset($substation_balances['BTC']['unconfirmed'])) {
                        $total_unconfirmed_balance = $substation_balances['BTC']['unconfirmed']->getSatoshisString();
                        $unconfirmed_balance = $total_unconfirmed_balance - $confirmed_balance;
                        UserMeta::setMeta($user_id, 'fuel_pending', $unconfirmed_balance);
                    }
                    EventLog::info('fuel.balancesUpdated', [
                        'userId' => $user_id,
                        'confirmed' => $confirmed_balance,
                        'unconfirmed' => $unconfirmed_balance,
                    ]);
                } catch (Exception $e) {
                    EventLog::logError('fuelDeposit.error', $e, [
                        'userId' => $user_id,
                        'txid' => $txid,
                        'address' => $address,
                    ]);
                    throw $e;
                }

                if ($confirmations >= $min_conf) {
                    if ($fuel_deposit) {
                        $save = DB::table('fuel_deposits')->where('txid', $txid)->update(array('confirmed' => 1, 'updated_at' => $time));
                        if (!$save) {
                            throw new Exception('Error saving fuel deposit ' . $txid, 1);
                        }
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
                // other assets (TOKENLY) not implemented at this time
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
            // }
        }

    }

}
