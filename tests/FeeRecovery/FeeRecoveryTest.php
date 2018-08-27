<?php

namespace Tests\unit;

use App\Libraries\FeeRecovery\BittrexSeller;
use App\Libraries\FeeRecovery\FeeRecoveryManager;
use App\Models\FeeRecoveryLedgerEntry;
use App\Repositories\FeeRecoveryLedgerEntryRepository;
use Mockery;
use PHPUnit\Framework\Assert as PHPUnit;
use TestCase;
use Tokenly\CryptoQuantity\CryptoQuantity;

class FeeRecoveryTest extends TestCase
{

    protected $use_database = true;

    public function testFeeRecoveryDebitsAndCredits()
    {
        // ledger
        $ledger = app(FeeRecoveryLedgerEntryRepository::class);

        // credit
        $ledger->credit(CryptoQuantity::fromFloat(1.25), 'BTC', FeeRecoveryLedgerEntry::TYPE_DEPOSIT);
        $ledger->credit(CryptoQuantity::fromFloat(0.25), 'BTC', FeeRecoveryLedgerEntry::TYPE_DEPOSIT);

        // debit
        $ledger->debit(CryptoQuantity::fromFloat(1.0), 'BTC', FeeRecoveryLedgerEntry::TYPE_WITHDRAWAL);

        // check totals
        $balance = $ledger->balance('BTC');
        PHPUnit::assertEquals(0.5, $balance->getFloatValue());
        $balances = $ledger->balancesByAsset();
        PHPUnit::assertEquals(['BTC'], array_keys($balances));
        PHPUnit::assertEquals(0.5, $balances['BTC']->getFloatValue());
    }

    public function testFeeReserves()
    {
        $mock_seller = Mockery::mock(BittrexSeller::class);
        $mock_seller->shouldReceive('purchaseBTC')->andReturn([
            'fldc_sold' => CryptoQuantity::fromFloat(1000),
            'btc_gained' => CryptoQuantity::fromFloat(0.0051),
            'btc_fee' => CryptoQuantity::fromFloat(0.00000025),
        ]);
        app()->instance(BittrexSeller::class, $mock_seller);

        $fee_recovery_manager = app(FeeRecoveryManager::class);

        // ledger
        $ledger = app(FeeRecoveryLedgerEntryRepository::class);

        // credit
        $ledger->credit(CryptoQuantity::fromFloat(0.0009), 'BTC', FeeRecoveryLedgerEntry::TYPE_DEPOSIT);

        // low
        PHPUnit::assertFalse($fee_recovery_manager->feeReservesAreAdequate());

        // to purchase
        $to_purchase = $fee_recovery_manager->feeReservesToPurchase();
        PHPUnit::assertEquals(0.0051, $to_purchase->getFloatValue());

        // execute mock purchase
        $fee_recovery_manager->purchaseFeeReserves();

        // ok
        PHPUnit::assertTrue($fee_recovery_manager->feeReservesAreAdequate());

        // check amount to purchase (again)
        $to_purchase = $fee_recovery_manager->feeReservesToPurchase();
        PHPUnit::assertEquals(0, $to_purchase->getFloatValue());

    }

}
