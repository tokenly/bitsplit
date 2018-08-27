<?php

namespace Tests\unit;

use App\Models\EscrowAddress;
use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use PHPUnit\Framework\Assert as PHPUnit;
use SampleId;
use SubstationHelper;
use TestCase;
use Tokenly\CryptoQuantity\CryptoQuantity;

class EscrowAddressLedgerTest extends TestCase
{

    protected $use_database = true;

    public function testEscrowAddressDebitsAndCredits()
    {
        // mocks
        SubstationHelper::mockAll();
        app('TokenpassHelper')->mockPromiseMethods();

        // ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);

        // create a new user and address
        // build the objects
        $user = app('UserHelper')->newRandomUser();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user);

        // credit
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(1.25), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1000), 'recv:' . SampleId::txid(1000));
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(0.25), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1001), 'recv:' . SampleId::txid(1001), $_confirmed = false);

        // debit
        $ledger->debit($escrow_address, CryptoQuantity::fromFloat(1.0), 'BTC', EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, SampleId::txid(1002), 'send:' . SampleId::txid(1002));

        // check totals
        $balance = $ledger->addressBalance($escrow_address, 'BTC');
        PHPUnit::assertEquals(0.5, $balance->getFloatValue());
        $balances = $ledger->addressBalancesByAsset($escrow_address);
        PHPUnit::assertEquals(['BTC'], array_keys($balances));
        PHPUnit::assertEquals(0.5, $balances['BTC']->getFloatValue());

        // confirmed only
        PHPUnit::assertEquals(0.25, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = true)->getFloatValue());

        // other user
        $other_user = app('UserHelper')->newRandomUser();
        $other_user_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($other_user);
        $ledger->credit($other_user_address, CryptoQuantity::fromFloat(2.5), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1003), 'recv:' . SampleId::txid(1003));

        // check original totals are unchanged
        $balance = $ledger->addressBalance($escrow_address, 'BTC');
        PHPUnit::assertEquals(0.5, $balance->getFloatValue());
    }

    public function testForeignEntityDebitsAndCredits()
    {
        // mocks
        SubstationHelper::mockAll();
        app('TokenpassHelper')->mockPromiseMethods();

        // ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);

        // create a new user and address
        // build the objects
        $user = app('UserHelper')->newRandomUser();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user);

        // credit
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(1000), 'FLDC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(2000), 'recv:' . SampleId::txid(2000));
        $ledger->credit($escrow_address, CryptoQuantity::fromFloat(1000), 'OTHERCOIN', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(2001), 'recv:' . SampleId::txid(2001));

        // debit to foreign entity
        $foreign_entity_one = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
        $ledger->debit($escrow_address, CryptoQuantity::fromFloat(150), 'FLDC', EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, SampleId::txid(1002), 'send:' . SampleId::txid(1002), $_confirmed = true, $_promise_id = 101, $foreign_entity_one);
        $ledger->debit($escrow_address, CryptoQuantity::fromFloat(50), 'FLDC', EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, SampleId::txid(1003), 'send:' . SampleId::txid(1003), $_confirmed = true, $_promise_id = 102, $foreign_entity_one);
        $ledger->debit($escrow_address, CryptoQuantity::fromFloat(25), 'OTHERCOIN', EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, SampleId::txid(1004), 'send:' . SampleId::txid(1004), $_confirmed = true, $_promise_id = 103, $foreign_entity_one);

        $foreign_entity_two = '1AAAA2222xxxxxxxxxxxxxxxxxxy4pQ3tU';
        $ledger->debit($escrow_address, CryptoQuantity::fromFloat(30), 'FLDC', EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, SampleId::txid(1005), 'send:' . SampleId::txid(1005), $_confirmed = true, $_promise_id = 101, $foreign_entity_two);

        // check source totals
        $balance = $ledger->addressBalance($escrow_address, 'FLDC');
        PHPUnit::assertEquals(770, $balance->getFloatValue());

        // check foreign entity totals
        $balance = $ledger->foreignEntityBalance($foreign_entity_one, 'FLDC');
        PHPUnit::assertEquals(200, $balance->getFloatValue());
        $balances = $ledger->foreignEntityBalancesByAsset($foreign_entity_one);
        PHPUnit::assertEquals(200, $balances['FLDC']->getFloatValue());
    }

    public function testEscrowAddressBlockchainCreditAndDebit()
    {
        // mocks
        SubstationHelper::mockAll();
        app('TokenpassHelper')->mockPromiseMethods();

        // ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);

        // create a new user and address
        // build the objects
        $user = app('UserHelper')->newRandomUser();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user);

        // ---------------------
        // credit

        // empty quantity
        PHPUnit::assertEquals(0, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = true)->getFloatValue());

        // receive with 0 confs
        $this->runTransactionJobCredit($escrow_address, SampleId::txid(2001), 0, 0.25);
        PHPUnit::assertEquals(0, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = true)->getFloatValue());
        PHPUnit::assertEquals(0.25, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = false)->getFloatValue());

        // soft confirm the transaction (1 confirmation)
        $this->runTransactionJobCredit($escrow_address, SampleId::txid(2001), 1, 0.25);
        PHPUnit::assertEquals(0, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = true)->getFloatValue());
        PHPUnit::assertEquals(0.25, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = false)->getFloatValue());

        // fully confirm the transaction (3 confirmations)
        // test confirmed and unconfirmed
        $this->runTransactionJobCredit($escrow_address, SampleId::txid(2001), 3, 0.25);
        PHPUnit::assertEquals(0.25, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = true)->getFloatValue());
        PHPUnit::assertEquals(0.25, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = false)->getFloatValue());

        // ---------------------
        // debit

        // 0 confs
        $this->runTransactionJobDebit($escrow_address, SampleId::txid(2002), 0, 0.15);
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
        PHPUnit::assertEquals(0.25, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = true)->getFloatValue());
        PHPUnit::assertEquals(0.1, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = false)->getFloatValue());

        // 3 confs
        $this->runTransactionJobDebit($escrow_address, SampleId::txid(2002), 3, 0.15);
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
        PHPUnit::assertEquals(0.1, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = true)->getFloatValue());
        PHPUnit::assertEquals(0.1, $ledger->addressBalance($escrow_address, 'BTC', $_confirmed_only = false)->getFloatValue());
    }

    // ------------------------------------------------------------------------

    protected function runTransactionJobCredit(EscrowAddress $escrow_address, $txid, $confirmations, $float_value)
    {
        $job_helper = app('SignalNotificationJobHelper');

        $parsed_transaction_data = array_merge($job_helper->buildSampleParsedTransaction($escrow_address['address']), [
            'txid' => $txid,
            'confirmations' => $confirmations,
            'confirmed' => $confirmations > 0,
        ]);
        // modify the received amount
        $parsed_transaction_data['credits'][1]['quantity'] = CryptoQuantity::floatToSatoshis($float_value);
        $job_helper->processJob($parsed_transaction_data);
    }

    protected function runTransactionJobDebit(EscrowAddress $escrow_address, $txid, $confirmations, $float_value)
    {
        $job_helper = app('SignalNotificationJobHelper');

        $escrow_address_hash = $escrow_address['address'];
        $parsed_transaction_data = array_merge($job_helper->buildSampleParsedTransaction('randomrecipient', $escrow_address_hash), [
            'txid' => $txid,
            'confirmations' => $confirmations,
            'confirmed' => $confirmations > 0,

            'debits' => [
                0 => [
                    'address' => $escrow_address_hash,
                    'asset' => 'BTC',
                    'quantity' => CryptoQuantity::floatToSatoshis(1),
                ],
            ],
            'credits' => [
                0 => [
                    'address' => $escrow_address_hash,
                    'asset' => 'BTC',
                    'quantity' => CryptoQuantity::fromFloat(1)->subtract(CryptoQuantity::fromFloat($float_value))->getSatoshisString(),
                ],
                1 => [
                    'address' => 'randomrecipient',
                    'asset' => 'BTC',
                    'quantity' => CryptoQuantity::floatToSatoshis(0.05),
                ],
            ],
        ]);
        // echo "\$parsed_transaction_data: ".json_encode($parsed_transaction_data, 192)."\n";

        $job_helper->processJob($parsed_transaction_data);
    }

}
