<?php

namespace App\Libraries\FeeRecovery;

use App\Libraries\FeeRecovery\BittrexSeller;
use App\Models\FeeRecoveryLedgerEntry;
use App\Repositories\FeeRecoveryLedgerEntryRepository;
use Illuminate\Support\Facades\DB;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\RecordLock\Facade\RecordLock;

class FeeRecoveryManager
{

    const LOW_FEE_RESERVES_SAT = 100000;     // 0.00100000 BTC
    const DESIRED_FEE_RESERVES_SAT = 600000; // 0.00600000 BTC

    public function __construct(FeeRecoveryLedgerEntryRepository $recovery_ledger, BittrexSeller $bittrex_seller)
    {
        $this->recovery_ledger = $recovery_ledger;
        $this->bittrex_seller = $bittrex_seller;
    }

    public function feeReservesAreAdequate()
    {
        $btc_fee_balance = $this->recovery_ledger->balance('BTC');
        return $btc_fee_balance->gte(self::LOW_FEE_RESERVES_SAT);
    }

    public function feeReservesToPurchase()
    {
        $btc_fee_balance = $this->recovery_ledger->balance('BTC');
        $amount_to_purchase = CryptoQuantity::fromSatoshis(self::DESIRED_FEE_RESERVES_SAT)->subtract($btc_fee_balance);
        return $amount_to_purchase;
    }

    public function purchaseFeeReserves()
    {
        RecordLock::acquireAndExecute('fee.purchase', function() {
            if ($this->feeReservesAreAdequate()) {
                EventLog::debug('feeReservePurchase.alreadyAdequate');
                return;
            }

            $purchase_quantity = $this->feeReservesToPurchase();
            EventLog::debug('feeReservePurchase.beginPurchase', [
                'quantity' => $purchase_quantity->getSatoshisString(),
            ]);

            // purchase
            $purchase_result = $this->bittrex_seller->purchaseBTC($purchase_quantity);

            // update ledger
            DB::transaction(function() use ($purchase_result) {
                $this->recovery_ledger->credit($purchase_result['btc_gained'], 'BTC', FeeRecoveryLedgerEntry::TYPE_DEPOSIT);
                $this->recovery_ledger->debit($purchase_result['btc_gained'], 'FLDC', FeeRecoveryLedgerEntry::TYPE_WITHDRAWAL);
            });

            EventLog::debug('feeReservePurchase.complete', [
                'quantity' => $purchase_quantity->getSatoshisString(),
            ]);

        }, $_timeout=180);
    }

}
