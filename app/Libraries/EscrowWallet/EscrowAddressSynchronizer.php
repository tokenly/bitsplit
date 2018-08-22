<?php

namespace App\Libraries\EscrowWallet;

use App\Libraries\EscrowWallet\EscrowWalletManager;
use App\Models\EscrowAddress;
use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tokenly\APIClient\Exception\APIException;
use Tokenly\CryptoQuantity\CryptoQuantity;

/**
 * Manages escrow-related token activities
 */
class EscrowAddressSynchronizer
{

    public function __construct(EscrowAddressLedgerEntryRepository $ledger, EscrowWalletManager $escrow_wallet_manager)
    {
        $this->ledger = $ledger;
        $this->escrow_wallet_manager = $escrow_wallet_manager;
    }

    public function synchronizeLedgerWithSubstation(EscrowAddress $escrow_address)
    {
        $differences = $this->buildTransactionDifferences($escrow_address);
        $this->reconcileDifferences($differences, $escrow_address);
    }

    public function buildTransactionDifferences(EscrowAddress $escrow_address)
    {
        // get database transactions
        $db_tx_map = [];
        $db_txs = $this->ledger->findAllByAddress($escrow_address);
        foreach ($db_txs as $db_tx) {
            // ignore promises
            switch ($db_tx['tx_type']) {
                case EscrowAddressLedgerEntry::TYPE_DEPOSIT:
                case EscrowAddressLedgerEntry::TYPE_WITHDRAWAL:
                    $db_tx = $db_tx->toArray();

                    // make withdrawals positive
                    if ($db_tx['tx_type'] == EscrowAddressLedgerEntry::TYPE_WITHDRAWAL) {
                        $db_tx['amount'] = CryptoQuantity::zero()->subtract($db_tx['amount']);
                    }

                    $db_tx_map[$db_tx['tx_identifier']] = $db_tx;
                    break;
            }

        }

        // get substation transactions
        $substation_client = $this->getSubstationClient();
        $wallet = $escrow_address->escrowWallet;
        if (!$wallet) {
            throw new Exception("Wallet not found for escrow address", 1);
        }
        $wallet_uuid = $wallet['uuid'];
        $substation_tx_map = [];
        $address_uuid = $escrow_address['uuid'];
        try {
            $substation_transactions = $substation_client->getTransactionsById($wallet_uuid, $address_uuid)['items'];
        } catch (APIException $e) {
            if ($e->getCode() == 404) {
                Log::debug("Substation returned 404 for $wallet_uuid $address_uuid");
                $substation_transactions = [];
            } else {
                throw $e;
            }
        }
        Log::debug("\$substation_transactions for address {$address_uuid} {$escrow_address['address']} is\n".json_encode($substation_transactions, 192));

        // process all txos paying to this address
        $address_hash = $escrow_address['address'];
        foreach ($substation_transactions as $substation_transaction) {
            // build the credits and debits that match...
            foreach (['credits', 'debits'] as $entry_type) {
                foreach ($substation_transaction[$entry_type] as $entry) {
                    if ($entry['address'] == $address_hash) {
                        $is_credit = $entry_type == 'credits' ? true : false;
                        $txid = $substation_transaction['txid'];
                        $asset = $entry['asset'];
                        $prefix = $is_credit ? 'recv' : 'send';
                        $tx_identifier = $prefix . ':' . $asset . ':' . $txid;
                        $amount = CryptoQuantity::unserialize($entry['quantity']);
                        $substation_tx = [
                            'amount' => $amount,
                            'asset' => $asset,
                            'tx_type' => $is_credit ? EscrowAddressLedgerEntry::TYPE_DEPOSIT : EscrowAddressLedgerEntry::TYPE_WITHDRAWAL,
                            'txid' => $txid,
                            'tx_identifier' => $tx_identifier,
                            'confirmed' => $substation_transaction['confirmed'],
                            'is_credit' => $is_credit,
                            'confirmation_time' => $substation_transaction['confirmed'] ? Carbon::parse($substation_transaction['confirmationTime']) : null,
                        ];

                        $substation_tx_map[$tx_identifier] = $substation_tx;
                    }
                }
            }
        }

        return $this->diffMaps($db_tx_map, $substation_tx_map);
    }

    public function reconcileDifferences($differences, EscrowAddress $escrow_address)
    {
        if ($differences['any']) {
            Log::debug("Transaction differences found for {$escrow_address['address']} ({$escrow_address['uuid']}) " . json_encode($differences, 192));

            foreach ($differences['differences'] as $difference) {
                DB::transaction(function () use ($difference, $escrow_address) {
                    // delete the db txo if it is different in any way
                    if (isset($difference['db']) and $difference['db']) {
                        $transaction = $difference['db'];
                        Log::debug('Removing DB transaction: ' . $transaction['tx_identifier']);

                        // delete
                        $this->ledger->deleteByTransactionIdentifierAndAddress($transaction['tx_identifier'], $escrow_address);
                    }

                    if (isset($difference['substation'])) {
                        $transaction = $difference['substation'];
                        Log::debug('Adding substation transaction: ' . $transaction['tx_identifier']);

                        // add (or update) the transaction
                        $credit_or_debit = ($transaction['is_credit']) ? 'credit' : 'debit';
                        $this->ledger->{$credit_or_debit}($escrow_address, $transaction['amount'], $transaction['asset'], $transaction['tx_type'], $transaction['txid'], $transaction['tx_identifier'], $transaction['confirmed'], $_promise_id = null, $transaction['confirmation_time']);
                    }
                });
            }

        } else {
            Log::debug("No differences found for {$escrow_address['address']} ({$escrow_address['uuid']})");
        }

    }

    protected function diffMaps($db_tx_map, $substation_tx_map)
    {
        $differences = [];
        $any_differences = false;
        $explanations = [];

        foreach (array_keys($db_tx_map) as $db_key) {
            if (!isset($substation_tx_map[$db_key])) {
                $differences[$db_key] = ['substation' => null, 'db' => $db_tx_map[$db_key]];
                $explanations[] = "substation transaction for {$db_key} did not exist";
                $any_differences = true;
            }
        }

        foreach (array_keys($substation_tx_map) as $substation_key) {
            if (!isset($db_tx_map[$substation_key])) {
                $differences[$substation_key] = ['substation' => $substation_tx_map[$substation_key], 'db' => null];
                $explanations[] = "db entry for {$substation_key} did not exist";
                $any_differences = true;
            } else {
                if ($this->entriesAreDifferent($db_tx_map[$substation_key], $substation_tx_map[$substation_key])) {
                    $explanations[] = $this->explainDifference($db_tx_map[$substation_key], $substation_tx_map[$substation_key]);
                    $differences[$substation_key] = ['substation' => $substation_tx_map[$substation_key], 'db' => $db_tx_map[$substation_key]];
                    $any_differences = true;
                }
            }
        }

        $differences = ['any' => $any_differences, 'differences' => $differences, 'explanations' => $explanations];
        return $differences;
    }

    protected function entriesAreDifferent($entry1, $entry2)
    {
        return (
            !$entry1['amount']->equals($entry2['amount'])
            or $entry1['asset'] != $entry2['asset']
            or $entry1['tx_type'] != $entry2['tx_type']
            or $entry1['txid'] != $entry2['txid']
            or $entry1['tx_identifier'] != $entry2['tx_identifier']
            or $entry1['confirmed'] != $entry2['confirmed']
        );
    }

    protected function explainDifference($entry1, $entry2)
    {
        $difference_text = '';
        if (!$entry1['amount']->equals($entry2['amount'])) {$difference_text .= " amounts were different ({$entry1['amount']} != {$entry2['amount']})";}
        if ($entry1['asset'] != $entry2['asset']) {$difference_text .= " assets were different ({$entry1['asset']} != {$entry2['asset']})";}
        if ($entry1['tx_identifier'] != $entry2['tx_identifier']) {$difference_text .= " tx_identifiers were different ({$entry1['tx_identifier']} != {$entry2['tx_identifier']})";}
        if ($entry1['txid'] != $entry2['txid']) {$difference_text .= " txids were different ({$entry1['txid']} != {$entry2['txid']})";}
        if ($entry1['tx_type'] != $entry2['tx_type']) {$difference_text .= " tx_types were different ({$entry1['tx_type']} != {$entry2['tx_type']})";}
        if ($entry1['confirmed'] != $entry2['confirmed']) {$difference_text .= " confirmed was different ({$entry1['confirmed']} != {$entry2['confirmed']})";}
        if (!strlen($difference_text)) {
            return "No differences found";
        }
        return trim($difference_text);
    }

    protected function getSubstationClient()
    {
        return app('substationclient.escrow');
    }

}
