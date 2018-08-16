<?php

namespace Tests\unit;

use App\Libraries\EscrowWallet\EscrowAddressSynchronizer;
use App\Models\EscrowAddress;
use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Assert as PHPUnit;
use SampleId;
use SubstationHelper;
use TestCase;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\SubstationClient\SubstationClient;

class EscrowAddressLedgerReconciliationTest extends TestCase
{

    protected $use_database = true;

    public function testSimpleLedgerSynchronization()
    {
        [$escrow_address, $ledger, $user] = $this->doSetup();

        // credit ledger
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(1.25), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1000), 'recv:BTC:' . SampleId::txid(1000));

        // ------------------------------------------------------------------------
        // no differences

        // re-mock substation client
        $credits = [
            [
                'asset' => 'BTC',
                'quantity' => CryptoQuantity::fromFloat(1.25),
            ],
        ];
        $debits = [];
        $transactions_list = [$this->buildTransaction($escrow_address, $credits, $debits, SampleId::txid(1000), $_confirmations = 1)];
        $this->mockDeliverySubstationClient($transactions_list);

        // check no differences
        $differences = app(EscrowAddressSynchronizer::class)->buildTransactionDifferences($escrow_address);
        PHPUnit::assertFalse($differences['any']);

        // ------------------------------------------------------------------------
        // 1 transaction sync with differences

        // check with differences
        $credits = [
            [
                'asset' => 'BTC',
                'quantity' => CryptoQuantity::fromFloat(1.35),
            ],
        ];
        $debits = [];
        $transactions_list = [$this->buildTransaction($escrow_address, $credits, $debits, SampleId::txid(1000), $_confirmations = 1)];
        $this->mockDeliverySubstationClient($transactions_list);

        $differences = app(EscrowAddressSynchronizer::class)->buildTransactionDifferences($escrow_address);
        PHPUnit::assertTrue($differences['any']);

        // synchronize
        app(EscrowAddressSynchronizer::class)->synchronizeLedgerWithSubstation($escrow_address);

        // check ledger
        $ledger_entries = $ledger->findAllByAddress($escrow_address);
        PHPUnit::assertcount(1, $ledger_entries);
        $ledger_entry = $ledger_entries->last();
        PHPUnit::assertEquals(CryptoQuantity::floatToSatoshis(1.35), $ledger_entry['amount']->getSatoshisString());
    }

    public function testMissingDbTx()
    {
        [$escrow_address, $ledger, $user] = $this->doSetup();

        // credit ledger
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(1.25), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1000), 'recv:BTC:' . SampleId::txid(1000));

        // ------------------------------------------------------------------------

        // re-mock substation client
        $debits = [];
        $transactions_list = [];
        $credits = [
            [
                'asset' => 'BTC',
                'quantity' => CryptoQuantity::fromFloat(1.25),
            ],
        ];
        $transactions_list[] = $this->buildTransaction($escrow_address, $credits, $debits, SampleId::txid(1000), $_confirmations = 1);
        $credits = [
            [
                'asset' => 'SOUP',
                'quantity' => CryptoQuantity::fromFloat(5),
            ],
        ];
        $transactions_list[] = $this->buildTransaction($escrow_address, $credits, $debits, SampleId::txid(1001), $_confirmations = 1);
        $this->mockDeliverySubstationClient($transactions_list);

        // synchronize
        app(EscrowAddressSynchronizer::class)->synchronizeLedgerWithSubstation($escrow_address);

        // check ledger
        $ledger_entries = $ledger->findAllByAddress($escrow_address);
        PHPUnit::assertcount(2, $ledger_entries);
        PHPUnit::assertEquals(CryptoQuantity::floatToSatoshis(1.25), $ledger_entries[0]['amount']->getSatoshisString());
        PHPUnit::assertEquals(CryptoQuantity::floatToSatoshis(5), $ledger_entries[1]['amount']->getSatoshisString());
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
    }

    public function testMissingSubstationTx()
    {
        [$escrow_address, $ledger, $user] = $this->doSetup();

        // credit ledger
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(1.25), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1000), 'recv:BTC:' . SampleId::txid(1000));
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(6), 'SOUP', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1001), 'recv:SOUP:' . SampleId::txid(1001));

        // ------------------------------------------------------------------------

        // re-mock substation client
        $debits = [];
        $transactions_list = [];
        $credits = [
            [
                'asset' => 'BTC',
                'quantity' => CryptoQuantity::fromFloat(1.25),
            ],
        ];
        $transactions_list[] = $this->buildTransaction($escrow_address, $credits, $debits, SampleId::txid(1000), $_confirmations = 1);
        $this->mockDeliverySubstationClient($transactions_list);

        // synchronize
        app(EscrowAddressSynchronizer::class)->synchronizeLedgerWithSubstation($escrow_address);

        // check ledger
        $ledger_entries = $ledger->findAllByAddress($escrow_address);
        PHPUnit::assertcount(1, $ledger_entries);
        PHPUnit::assertEquals(CryptoQuantity::floatToSatoshis(1.25), $ledger_entries[0]['amount']->getSatoshisString());
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
    }

    public function testDebits()
    {
        [$escrow_address, $ledger, $user] = $this->doSetup();

        // credit ledger
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(1.25), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1000), 'recv:BTC:' . SampleId::txid(1000));
        $ledger->debit($escrow_address, CryptoQuantity::fromFloat(0.25), 'BTC', EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, SampleId::txid(1001), 'send:BTC:' . SampleId::txid(1001));

        // ------------------------------------------------------------------------

        // re-mock substation client
        $transactions_list = [];
        $credits = [
            [
                'asset' => 'BTC',
                'quantity' => CryptoQuantity::fromFloat(1.25),
            ],
        ];
        $debits = [];
        $transactions_list[] = $this->buildTransaction($escrow_address, $credits, $debits, SampleId::txid(1000), $_confirmations = 1);
        $credits = [];
        $debits = [
            [
                'asset' => 'BTC',
                'quantity' => CryptoQuantity::fromFloat(0.25),
            ],
        ];
        $transactions_list[] = $this->buildTransaction($escrow_address, $credits, $debits, SampleId::txid(1001), $_confirmations = 1);
        $this->mockDeliverySubstationClient($transactions_list);

        // check no differences
        $differences = app(EscrowAddressSynchronizer::class)->buildTransactionDifferences($escrow_address);
        Log::debug("\$differences=" . json_encode($differences, 192));
        PHPUnit::assertFalse($differences['any']);

        // synchronize
        app(EscrowAddressSynchronizer::class)->synchronizeLedgerWithSubstation($escrow_address);

        // check ledger
        $ledger_entries = $ledger->findAllByAddress($escrow_address);
        PHPUnit::assertcount(2, $ledger_entries);
        PHPUnit::assertEquals(CryptoQuantity::floatToSatoshis(1.25), $ledger_entries[0]['amount']->getSatoshisString());
        PHPUnit::assertEquals(CryptoQuantity::floatToSatoshis(-0.25), $ledger_entries[1]['amount']->getSatoshisString());
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
    }

    // ------------------------------------------------------------------------

    protected function doSetup()
    {
        // mocks
        SubstationHelper::mockAll();
        app('TokenpassHelper')->mockPromiseMethods();

        // create a new merchant and address
        // build the objects
        $user = app('UserHelper')->newRandomUser();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user);

        // ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);

        return [$escrow_address, $ledger, $user];
    }

    protected function mockDeliverySubstationClient($transactions_list)
    {
        $mock_substation = Mockery::mock(SubstationClient::class);
        $mock_substation->shouldReceive('getTransactionsById')->andReturn([
            'items' => $transactions_list,
        ]);
        app()->instance('substationclient.escrow', $mock_substation);
    }

    protected function buildTransaction(EscrowAddress $escrow_address, $credits, $debits, $txid = null, $confirmations = null, $chain = 'bitcoinTestnet', $asset = 'BTC')
    {
        $txid = $txid ?? SampleId::txid(1001);
        $confirmations = $confirmations ?? 6;

        $tx_credits = [];
        foreach ($credits as $credit) {
            $tx_credit = [
                'address' => $escrow_address['address'],
                'asset' => $credit['asset'],
                'quantity' => $credit['quantity']->jsonSerialize(),
            ];
            $tx_credits[] = $tx_credit;
        }
        $tx_debits = [];
        foreach ($debits as $debit) {
            $tx_debit = [
                'address' => $escrow_address['address'],
                'asset' => $debit['asset'],
                'quantity' => $debit['quantity']->jsonSerialize(),
            ];
            $tx_debits[] = $tx_debit;
        }

        $precision = 8;
        $transaction = [
            "chain" => $chain,
            "debits" => $tx_debits,
            "credits" => $tx_credits,
            "fees" => [
                [
                    "asset" => $asset,
                    "quantity" => [
                        "value" => 100000000,
                        "precision" => $precision,
                    ],
                ],
            ],
            "blockhash" => "0000000000000000012b9d37eb4cb9729684735e6f937f6b4187bbf0fcd021a8",
            "txid" => $txid,
            "confirmations" => 2,
            "confirmationFinality" => 6,
            "confirmed" => $confirmations > 0,
            "final" => $confirmations >= 6,
            "confirmationTime" => "2016-09-03T14:30:00+0000",
        ];

        return $transaction;
    }

}
