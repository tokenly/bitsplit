<?php

namespace App\Repositories;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use TableDumper;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;

/*
 * FeeRecoveryLedgerEntryRepository
 */
class FeeRecoveryLedgerEntryRepository extends APIRepository
{

    protected $model_type = 'App\Models\FeeRecoveryLedgerEntry';

    public function credit(CryptoQuantity $quantity, $asset, $tx_type)
    {
        if ($quantity->lte(0)) {
            throw new Exception("Credits must be a positive number", 1);
        }

        return $this->createCreditOrDebit($_is_credit = true, $quantity, $asset, $tx_type);
    }

    public function debit(CryptoQuantity $quantity, $asset, $tx_type)
    {
        if ($quantity->lte(0)) {
            throw new Exception("Debits must be a positive number", 1);
        }

        return $this->createCreditOrDebit($_is_credit = false, $quantity, $asset, $tx_type);
    }

    // returns CryptoQuantity::fromFloat(0.5)
    public function balance($asset)
    {
        $query = $this->prototype_model
            ->where('asset', $asset);

        $sat_sum = $query->sum('amount');
        if ($sat_sum === null) {$sat_sum = 0;}

        return CryptoQuantity::fromSatoshis($sat_sum);
    }

    // returns
    // [
    //    BTC => CryptoQuantity::fromFloat(0.12),
    //    SOUP =>  CryptoQuantity::fromFloat(10),
    // ]
    public function balancesByAsset()
    {
        $query = $this->prototype_model
            ->select('asset', DB::raw('SUM(amount) AS total_amount'))
            ->groupBy('asset');

        $results = $query->get();

        $sums = $this->assembleBalancesByAsset($results);
        return $sums;
    }

    public function debugDumpLedger($entries)
    {
        $headers = ['amount', 'asset', 'tx_type', 'last update'];
        $rows = [];
        foreach ($entries as $entry) {
            $row = [
                (string) $entry['amount'],
                $entry['asset'],
                $entry->getTypeDescription(),
                Carbon::parse($entry['last_update'])
                    ->setTimezone('America/Chicago')->format("Y-m-d h:i:s A T"),
            ];
            $rows[] = $row;
        }

        return TableDumper::dumpToTable($headers, $rows);
    }

    ////////////////////////////////////////////////////////////////////////

    protected function assembleBalancesByAsset($results)
    {
        $balance_by_asset = [];

        foreach ($results as $result) {
            $total_amount = $result['total_amount'];

            $quantity = CryptoQuantity::fromSatoshis($total_amount);
            $balance_by_asset[$result['asset']] = $quantity;
        }

        return $balance_by_asset;
    }

    protected function createCreditOrDebit($is_credit, CryptoQuantity $quantity, $asset, $tx_type)
    {
        $create_vars = [
            'amount' => ($is_credit ? '' : '-') . $quantity->getSatoshisString(),
            'asset' => $asset,
            'tx_type' => $tx_type,
        ];

        return $this->create($create_vars);
    }

}
