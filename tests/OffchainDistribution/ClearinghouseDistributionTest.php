<?php

namespace Tests\unit;

use App\Models\EscrowAddressLedgerEntry;
use App\Models\FeeRecoveryLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use App\Repositories\FeeRecoveryLedgerEntryRepository;
use Distribute\Processor;
use PHPUnit\Framework\Assert as PHPUnit;
use SampleId;
use SubstationHelper;
use TestCase;
use Tokenly\CryptoQuantity\CryptoQuantity;
use User;

class ClearinghouseDistributionTest extends TestCase
{

    protected $use_database = true;

    public function testSubmitClearinghouseDistribution()
    {
        [$admin, $escrow_address] = $this->generateDistribution();

        // now submit a request to create a clearinghouse distribution
        $post_vars = [
            'asset' => 'FLDC',
            'calculation_type' => 'clearinghouse',
            'use_fuel' => '1',
            'offchain' => '0',
        ];
        $response = $this->actingAs($admin)->post('/distribute', $post_vars);
        // echo "\$response: ".($response->getContent())."\n";
        // echo "session('message'): ".(session('message'))."\n";

        // test that the promises were fulfilled (cleared)
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
        $balance = $ledger->foreignEntityBalance('1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j', 'FLDC');
        PHPUnit::assertEquals(0, $balance->getFloatValue());
        $balance = $ledger->foreignEntityBalance('1AAAA2222xxxxxxxxxxxxxxxxxxy4pQ3tU', 'FLDC');
        PHPUnit::assertEquals(0, $balance->getFloatValue());

    }

    // ------------------------------------------------------------------------

    protected function generateDistribution()
    {
        $SATOSHI = 100000000;

        // mocks
        app('TokenmapHelper')->mockTokenmap();
        SubstationHelper::mockAll();
        $tokenpass_mock = app('TokenpassHelper')->mockPromiseMethods();
        $tokenpass_mock = app('TokenpassHelper')->mockTokenpassErrors($tokenpass_mock);
        $tokenpass_mock->shouldReceive('getAddressesForAuthenticatedUser')->andReturn([
            [
                'address' => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
                'balances' => [],
            ],
        ]);

        // objects
        $recovery_ledger = app(FeeRecoveryLedgerEntryRepository::class);
        $recovery_ledger->credit(CryptoQuantity::fromFloat(0.006), 'BTC', FeeRecoveryLedgerEntry::TYPE_DEPOSIT);

        // prerequisites
        $admin = app('UserHelper')->newRandomUser();
        $admin->makeAdmin();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($admin, $_recovery_address = null, $_chain = 'counterparty');

        // save user data
        app('UserHelper')->approveUser($admin);

        // seed ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $ledger->credit($escrow_address, CryptoQuantity::fromSatoshis(9000 * $SATOSHI), 'FLDC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(500), 'recv:FLDC:' . SampleId::txid(500));

        // run the distribution
        $this->runDistribution($admin);

        return [$admin, $escrow_address];
    }

    protected function runDistribution(User $user)
    {
        // create the distribution
        $distribution = app('DistributionHelper')->newOffchainDistribution($user, ['asset' => 'FLDC'], $_and_initialize = true);
        PHPUnit::assertTrue($distribution->isOffchainDistribution());
        PHPUnit::assertNotEmpty($distribution->getEscrowAddress());
        PHPUnit::assertNotEmpty($distribution);
        PHPUnit::assertEquals('EnsureTokens', ($distribution->refresh())->stageName());

        // do EnsureTokens
        $processor = app(Processor::class);
        $success = $processor->processStage($distribution);
        PHPUnit::assertTrue($success);
        PHPUnit::assertEquals('AllocatePromises', ($distribution->refresh())->stageName());

        // do AllocatePromises
        $processor = app(Processor::class);
        $success = $processor->processStage($distribution);
        PHPUnit::assertTrue($success);
        PHPUnit::assertEquals('DistributePromises', ($distribution->refresh())->stageName());

        // do DistributePromises
        $processor = app(Processor::class);
        $processor->processStage($distribution);
        PHPUnit::assertTrue($success);
        PHPUnit::assertEquals('Complete', ($distribution->refresh())->stageName());

        return $distribution;
    }

}
