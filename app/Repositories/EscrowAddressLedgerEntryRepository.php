<?php

namespace App\Repositories;

use App\Models\EscrowAddress;
use App\Models\EscrowAddressLedgerEntry;
use Exception;
use Illuminate\Support\Facades\DB;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;

/*
* EscrowAddressLedgerEntryRepository
*/
class EscrowAddressLedgerEntryRepository extends APIRepository
{

    protected $model_type = 'App\Models\EscrowAddressLedgerEntry';

    public function credit(EscrowAddress $address, CryptoQuantity $quantity, $asset, $tx_type, $txid, $tx_identifier, $confirmed = true, $promise_id = null, $created_at = null)
    {
        if ($quantity->lte(0)) {
            throw new Exception("Credits must be a positive number", 1);
        }

        return $this->updateOrCreate($address, $_is_credit = true, $quantity, $asset, $tx_type, $txid, $tx_identifier, $confirmed, $promise_id, $created_at);
    }

    public function debit(EscrowAddress $address, CryptoQuantity $quantity, $asset, $tx_type, $txid, $tx_identifier, $confirmed = true, $promise_id = null, $created_at = null)
    {
        if ($quantity->lte(0)) {
            throw new Exception("Debits must be a positive number", 1);
        }

        return $this->updateOrCreate($address, $_is_credit = false, $quantity, $asset, $tx_type, $txid, $tx_identifier, $confirmed, $promise_id, $created_at);
    }

    public function findAllByAddress(EscrowAddress $address)
    {
        $address_id = $address['id'];
        $query = $this->prototype_model
            ->where('address_id', $address_id)
            ->orderBy('id');

        return $query->get();
    }

    public function findByTransactionIdentifierAndAddress($tx_identifier, EscrowAddress $address)
    {
        $address_id = $address['id'];

        $query = $this->prototype_model
            ->where('tx_identifier', $tx_identifier)
            ->where('address_id', $address_id);

        return $query->first();
    }

    public function findByPromiseId($promise_id, $tx_type = null)
    {
        $query = $this->prototype_model
            ->where('promise_id', $promise_id);

        if ($tx_type !== null) {
            $query->where('tx_type', $tx_type);
        }

        return $query->get();
    }

    public function deleteByAddress(EscrowAddress $address)
    {
        return $this->deleteByAddressId($address['id']);
    }

    public function deleteByAddressId($address_id)
    {
        return $this->prototype_model
            ->where('address_id', $address_id)
            ->delete();
    }

    public function deleteByTransactionIdentifierAndAddress($tx_identifier, EscrowAddress $address)
    {
        return $this->prototype_model
            ->where('tx_identifier', $tx_identifier)
            ->where('address_id', $address['id'])
            ->delete();
    }

    // returns CryptoQuantity::fromFloat(0.5)
    public function addressBalance(EscrowAddress $address, $asset, $confirmed_only = false)
    {
        $address_id = $address['id'];

        $query = $this->prototype_model
            ->where('address_id', $address_id)
            ->where('asset', $asset);

        if ($confirmed_only) {
            $query->where('confirmed', '1');
        }

        $sat_sum = $query->sum('amount');
        if ($sat_sum === null) {$sat_sum = 0;}

        return EscrowAddressLedgerEntry::cryptoQuantityForAddress($sat_sum, $address);
    }

    // returns
    // [
    //    BTC => CryptoQuantity::fromFloat(0.12),
    //    SOUP =>  CryptoQuantity::fromFloat(10),
    // ]
    public function addressBalancesByAsset(EscrowAddress $address, $confirmed_only = false)
    {
        $address_id = $address['id'];

        $query = $this->prototype_model
            ->select('asset', DB::raw('SUM(amount) AS total_amount'))
            ->where('address_id', $address_id)
            ->groupBy('asset');

        if ($confirmed_only) {
            $query->where('confirmed', '1');
        }

        $results = $query->get();

        $sums = $this->assembleBalancesByAsset($results, $address);
        return $sums;
    }

    public function debugDumpLedger($entries)
    {
        $bool = function ($val) {return $val ? '<info>true</info>' : '<comment>false</comment>';};

        $headers = ['amount', 'asset', 'type', 'confirmed', 'identifier', 'promise_id', 'last update'];
        $rows = [];
        foreach ($entries as $entry) {
            $row = [
                (string) $entry['amount'],
                $entry['asset'],
                $entry->getTypeDescription(),
                $bool($entry['confirmed']),
                $entry['tx_identifier'],
                $entry['promise_id'],
                Carbon::parse($entry['last_update'])
                    ->setTimezone('America/Chicago')->format("Y-m-d h:i:s A T"),
            ];
            $rows[] = $row;
        }

        return TableDumper::dumpToTable($headers, $rows);
    }

    ////////////////////////////////////////////////////////////////////////

    protected function assembleBalancesByAsset($results, EscrowAddress $address)
    {
        $balance_by_asset = [];

        foreach ($results as $result) {
            $balance_by_asset[$result['asset']] = EscrowAddressLedgerEntry::cryptoQuantityForAddress($result['total_amount'], $address);
        }

        return $balance_by_asset;
    }

    protected function updateOrCreate(EscrowAddress $address, $is_credit, CryptoQuantity $quantity, $asset, $tx_type, $txid, $tx_identifier, $confirmed, $promise_id, $created_at)
    {

        $update_or_create_vars = [
            'address_id' => $address['id'],
            'amount' => ($is_credit ? '' : '-') . $quantity->getSatoshisString(),
            'asset' => $asset,
            'tx_type' => $tx_type,
            'txid' => $txid,
            'tx_identifier' => $tx_identifier,
            'promise_id' => $promise_id,
            'confirmed' => $confirmed,
        ];

        $update_vars = $update_or_create_vars;
        $updated_model = $this->updateIfExists($update_vars, $address);
        if ($updated_model) {
            return $updated_model;
        }

        $create_vars = $update_or_create_vars;
        if ($created_at !== null) {
            $create_vars['created_at'] = $created_at;
        }
        return $this->create($create_vars);
    }

    protected function updateIfExists($attributes, EscrowAddress $address)
    {
        return DB::transaction(function () use ($attributes, $address) {
            $existing_model = $this->findByTransactionIdentifierAndAddress($attributes['tx_identifier'], $address);
            if ($existing_model) {
                $this->update($existing_model, $attributes);
                return $existing_model;
            }
            return null;
        });

    }

}
