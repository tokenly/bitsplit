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

class EscrowAddressTest extends TestCase
{

    protected $use_database = true;

    public function testEscrowAddressDebitsAndCredits()
    {
        // mocks
        SubstationHelper::mockAll();
        app('TokenpassHelper')->mockPromiseMethods();

        // ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);

        // create a new merchant and address
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

        // other merchant
        $other_merchant = app('UserHelper')->newRandomUser();
        return;
        $other_merchant_addresses = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($other_merchant);
        $ledger->credit($other_merchant_addresses[0], CryptoQuantity::fromFloat(2.5), 'BTC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(1003), 'recv:' . SampleId::txid(1003));

        // check original totals are unchanged
        $balance = $ledger->addressBalance($escrow_address, 'BTC');
        PHPUnit::assertEquals(0.5, $balance->getFloatValue());
    }

    public function testEscrowAddressBlockchainCreditAndDebit()
    {
        // IN PROGRESS...
        return $this->markTestIncomplete();

        // mocks
        SubstationHelper::mockAll();
        app('TokenpassHelper')->mockPromiseMethods();

        // ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);

        // create a new merchant and address
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
        return;

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

    // public function testTokenDeliveryFulfillmentUpdatesLedger()
    // {
    //     // mocks
    //     SubstationHelper::mockAll();
    //     $tokenpass_mock = TokenpassHelper::mockCheckoutMethods();
    //     $tokenpass_mock = TokenpassHelper::mockPromiseMethods($tokenpass_mock);

    //     // ledger
    //     $ledger = app(EscrowAddressLedgerEntryRepository::class);

    //     // merchant
    //     $user = app('UserHelper')->newRandomUserWithPaymentAddresses();

    //     // get merchant address
    //     $escrow_addresses = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user);
    //     $escrow_address = $escrow_addresses[0];

    //     // seed the merchant address with 10 MYCOIN
    //     $ledger->credit($escrow_address, CryptoQuantity::fromFloat(10), 'MYCOIN', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(3000), 'recv:' . SampleId::txid(3000), $_confirmed = true);

    //     // build the delivery and attach it to an order
    //     $token_delivery = app('TokenDeliveryHelper')->newTokenDeliveryWithFulfillment($user, $escrow_address);

    //     // check the balance is now 9
    //     PHPUnit::assertEquals(9, $ledger->addressBalance($escrow_address, 'MYCOIN')->getFloatValue());
    //     PHPUnit::assertEquals(101, $ledger->findAllByAddress($escrow_address)->last()['promise_id']);
    //     // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";

    // }

    // // test that completing a token delivery will remove debit the promised tx amount from the ledger
    // public function testBlockchainDeliveryUpdatesEscrowAddressLedger()
    // {
    //     $tokenpass_mock = TokenpassHelper::mockCheckoutMethods();
    //     $tokenpass_mock = TokenpassHelper::mockPromiseMethods($tokenpass_mock);
    //     $tokenpass_mock = app('TokenBlockchainDeliveryHelper')->mockTokenpassPromises(null, $tokenpass_mock);
    //     $substation_mock = app('TokenBlockchainDeliveryHelper')->mockSubstationClient();
    //     app('TokenBlockchainDeliveryHelper')->mockCryptoAuth();

    //     $user = app('UserHelper')->newRandomUserWithPaymentAddresses();

    //     // add a keypair
    //     app('MerchantKeypairHelper')->newMerchantKeypair($user);

    //     $ledger = app(EscrowAddressLedgerEntryRepository::class);
    //     $token_blockchain_delivery_manager = app(TokenBlockchainDeliveryManager::class);

    //     // get merchant address
    //     $escrow_addresses = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user);
    //     $escrow_address = $escrow_addresses[0];

    //     // seed the merchant address with 10 SOUP
    //     $ledger->credit($escrow_address, CryptoQuantity::fromFloat(10), 'SOUP', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(3000), 'recv:' . SampleId::txid(3000), $_confirmed = true);

    //     // build the delivery and attach it to an order
    //     $token_delivery = app('TokenDeliveryHelper')->newTokenDeliveryWithFulfillment($user, $escrow_address, null, ['asset' => 'SOUP']);

    //     // new blockchain delivery with token
    //     $delivery = app('TokenBlockchainDeliveryHelper')->newTokenBlockchainDeliveryWithPromisesAndMockedSignature([], $escrow_address);

    //     // move to authorized and approved
    //     //   this will send the token
    //     $delivery->getStateMachine()->transition('init');
    //     $delivery->getStateMachine()->transition('authorize');
    //     app('TokenBlockchainDeliveryHelper')->approveTokenBlockchainDelivery($delivery);

    //     // now send a test job to mark as confirmed (2 confs)
    //     $this->sendSignalJob([
    //         'txid' => $delivery['txid'],
    //         'confirmations' => 2,
    //         'confirmed' => false,
    //     ]);

    //     // confirm that it moved into the confirmed state
    //     $delivery->refresh();
    //     PHPUnit::assertEquals(TokenBlockchainDelivery::STATUS_CONFIRMED, $delivery['status']);

    //     // check the ledger
    //     // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
    //     $last_ledger_entry = $ledger->findAllByAddress($escrow_address)->last();
    //     PHPUnit::assertEquals('SOUP', $last_ledger_entry['asset']);
    //     PHPUnit::assertEquals(CryptoQuantity::floatToSatoshis(1), $last_ledger_entry['amount']->getSatoshisString());
    //     PHPUnit::assertEquals('fill:promise:101', $last_ledger_entry['tx_identifier']);
    // }

    // // ------------------------------------------------------------------------

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

    // protected function runTransactionJobDebit(EscrowAddress $escrow_address, $txid, $confirmations, $float_value)
    // {
    //     $job_helper = app('SignalNotificationJobHelper');

    //     $escrow_address_hash = $escrow_address['address'];
    //     $parsed_transaction_data = array_merge($job_helper->buildSampleParsedTransaction('randomrecipient', $escrow_address_hash), [
    //         'txid' => $txid,
    //         'confirmations' => $confirmations,
    //         'confirmed' => $confirmations > 0,

    //         'debits' => [
    //             0 => [
    //                 'address' => $escrow_address_hash,
    //                 'asset' => 'BTC',
    //                 'quantity' => CryptoQuantity::floatToSatoshis(1),
    //             ],
    //         ],
    //         'credits' => [
    //             0 => [
    //                 'address' => $escrow_address_hash,
    //                 'asset' => 'BTC',
    //                 'quantity' => CryptoQuantity::fromFloat(1)->subtract(CryptoQuantity::fromFloat($float_value))->getSatoshisString(),
    //             ],
    //             1 => [
    //                 'address' => 'randomrecipient',
    //                 'asset' => 'BTC',
    //                 'quantity' => CryptoQuantity::floatToSatoshis(0.05),
    //             ],
    //         ],
    //     ]);
    //     // echo "\$parsed_transaction_data: ".json_encode($parsed_transaction_data, 192)."\n";

    //     $job_helper->processJob($parsed_transaction_data);
    // }

    // // ------------------------------------------------------------------------

    // protected function sendSignalJob($override_vars = [])
    // {
    //     $job_helper = app('SignalNotificationJobHelper');

    //     $sending_address = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j';
    //     $delivery_address = '1AAAA2222xxxxxxxxxxxxxxxxxxy4pQ3tU';
    //     $asset = 'SOUP';

    //     $parsed_transaction_data = $job_helper->buildSampleParsedTransaction($delivery_address, $sending_address, $asset);
    //     $override_vars = array_merge([
    //         'debits' => [
    //             [
    //                 'address' => $sending_address,
    //                 'asset' => $asset,
    //                 'quantity' => '100000000',
    //             ],
    //             [
    //                 'address' => $sending_address,
    //                 'asset' => 'BTC',
    //                 'quantity' => '20000',
    //             ],
    //         ],
    //         'credits' => [
    //             [
    //                 'address' => $delivery_address,
    //                 'asset' => $asset,
    //                 'quantity' => '100000000',
    //             ],
    //         ],
    //     ], $override_vars);
    //     $parsed_transaction_data = array_merge($parsed_transaction_data, $override_vars);
    //     // echo "\$parsed_transaction_data: ".json_encode($parsed_transaction_data, 192)."\n";

    //     return $job_helper->processJob($parsed_transaction_data);
    // }
}
