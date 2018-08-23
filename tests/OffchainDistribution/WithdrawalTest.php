<?php

namespace Tests\unit;

use App\Jobs\ExecuteWithdrawal;
use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use Distribute\Processor;
use PHPUnit\Framework\Assert as PHPUnit;
use SampleId;
use SubstationHelper;
use TestCase;
use Tokenly\CryptoQuantity\CryptoQuantity;
use User;

class WithdrawalTest extends TestCase
{

    protected $use_database = true;

    public function testExecuteWithdrawal()
    {
        // mocks
        SubstationHelper::mockAll();
        $tokenpass_mock = app('TokenpassHelper')->mockPromiseMethods();
        $tokenpass_mock = app('TokenpassHelper')->mockTokenpassErrors($tokenpass_mock);
        $tokenpass_mock->shouldReceive('getAddressesForAuthenticatedUser')->andReturn([
            [
                'address' => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j',
                'balances' => [],
            ],
        ]);

        // prerequisites
        $admin = app('UserHelper')->newRandomUser();
        $admin->makeAdmin();
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($admin, $_recovery_address = null, $chain = 'counterparty');

        // seed ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $ledger->credit($escrow_address, CryptoQuantity::fromSatoshis(1500000000), 'FLDC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(500), 'recv:FLDC:' . SampleId::txid(500));

        // run the distribution
        $this->runDistribution($admin);

        // create a withdrawal
        $recipient = app('UserHelper')->newRandomUser();
        $recipient_withdrawal = app('RecipientWithdrawalHelper')->newWithdrawal($recipient);

        // now run the withdrawal
        ExecuteWithdrawal::dispatch($recipient_withdrawal);

        // check the ledger entries
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";
        $entries = $ledger->findAllByAddress($escrow_address);

        // promise id is removed
        $entry = $entries
            ->filter(function($e) { return $e['tx_identifier'] == 'disttx:FLDC:1'; })
            ->first();
        PHPUnit::assertNull($entry['promise_id']);

        // balance is now zero
        PHPUnit::assertEquals(0, $ledger->foreignEntityBalance('1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j', 'FLDC')->getFloatValue());

        // promise id is removed
        $entry = $entries
            ->filter(function($e) { return $e['foreign_entity'] == '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j' and $e['tx_type'] == EscrowAddressLedgerEntry::TYPE_PROMISE_FULFILLED; })
            ->first();
        PHPUnit::assertNotNull($entry);
        PHPUnit::assertEquals(1, $entry['amount']->getFloatValue());



    }

    // ------------------------------------------------------------------------

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
