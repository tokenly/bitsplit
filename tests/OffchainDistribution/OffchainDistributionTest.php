<?php

namespace Tests\unit;

use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use Distribute\Processor;
use PHPUnit\Framework\Assert as PHPUnit;
use SampleId;
use SubstationHelper;
use TestCase;
use Tokenly\CryptoQuantity\CryptoQuantity;

class OffchainDistributionTest extends TestCase
{

    protected $use_database = true;

    public function testOffchainDistribution()
    {
        // mocks
        SubstationHelper::mockAll();
        $tokenpass_mock = app('TokenpassHelper')->mockProvisionalSourceMethods(app('TokenpassHelper')->ensureMockedTokenpassAPI());

        // validate mocks
        $promise_counter = 101;
        $tokenpass_mock->shouldReceive('promiseTransaction')->andReturnUsing(function () use (&$promise_counter) {
            ++$promise_counter;
            return [
                'promise_id' => $promise_counter - 1,
            ];
        });

        // prerequisites
        $user = app('UserHelper')->newRandomUser();
        $user->makeAdmin();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user, $_recovery_address = null, $chain = 'counterparty');

        // seed ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $ledger->credit($escrow_address, CryptoQuantity::fromSatoshis(1500000000), 'FLDC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(500), 'recv:FLDC:' . SampleId::txid(500));

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

        // make sure promises were called
        PHPUnit::assertEquals(106, $promise_counter);
    }

    public function testRequiresTokensForOffchainDistribution()
    {
        // mocks
        SubstationHelper::mockAll();
        $tokenpass_mock = app('TokenpassHelper')->mockProvisionalSourceMethods(app('TokenpassHelper')->ensureMockedTokenpassAPI());

        // prerequisites
        $user = app('UserHelper')->newRandomUser();
        $user->makeAdmin();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user, $_recovery_address = null, $chain = 'counterparty');

        // seed ledger with inadequate funds
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $ledger->credit($escrow_address, CryptoQuantity::fromSatoshis(900000000), 'FLDC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(500), 'recv:FLDC:' . SampleId::txid(500));

        // create the distribution
        $distribution = app('DistributionHelper')->newOffchainDistribution($user, ['asset' => 'FLDC'], $_and_initialize = true);
        PHPUnit::assertNotEmpty($distribution->getEscrowAddress());
        PHPUnit::assertTrue($distribution->isOffchainDistribution());
        PHPUnit::assertNotEmpty($distribution);
        PHPUnit::assertEquals('EnsureTokens', ($distribution->refresh())->stageName());

        // EnsureTokens fails due to inadequate funds
        $processor = app(Processor::class);
        $success = $processor->processStage($distribution);
        PHPUnit::assertFalse($success);
        PHPUnit::assertEquals('EnsureTokens', ($distribution->refresh())->stageName());
    }

    public function testAllocateLedgerEntriesForOffchainDistribution()
    {
        // mocks
        SubstationHelper::mockAll();
        $tokenpass_mock = app('TokenpassHelper')->mockProvisionalSourceMethods(app('TokenpassHelper')->ensureMockedTokenpassAPI());

        // prerequisites
        $user = app('UserHelper')->newRandomUser();
        $user->makeAdmin();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($user, $_recovery_address = null, $chain = 'counterparty');

        // seed ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $ledger->credit($escrow_address, CryptoQuantity::fromSatoshis(1500000000), 'FLDC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(500), 'recv:FLDC:' . SampleId::txid(500));

        // create the distribution
        $distribution = app('DistributionHelper')->newOffchainDistribution($user, ['asset' => 'FLDC'], $_and_initialize = true);
        PHPUnit::assertTrue($distribution->isOffchainDistribution());
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

        // check the ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $ledger_entries = $ledger->findAllByAddress($escrow_address);
        PHPUnit::assertCount(6, $ledger_entries);

    }
}
