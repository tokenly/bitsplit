<?php

namespace Tests\unit;

use App\Jobs\ExecuteWithdrawal;
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
use Tokenly\TokenmapClient\Mock\MockeryBuilder;
use User;

class WithdrawalTest extends TestCase
{

    protected $use_database = true;

    public function testExecuteWithdrawal()
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
        $escrow_address = app('EscrowWalletAddressHelper')->generateNewEscrowWalletAddress($admin, $_recovery_address = null, $chain = 'counterparty');

        // seed ledger
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $ledger->credit($escrow_address, CryptoQuantity::fromSatoshis(9000 * $SATOSHI), 'FLDC', EscrowAddressLedgerEntry::TYPE_DEPOSIT, SampleId::txid(500), 'recv:FLDC:' . SampleId::txid(500));

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
        PHPUnit::assertEquals(1000, $entry['amount']->getFloatValue());

        // test that the fee has been allocated to the fee address
        $fee_entries = $entries
            ->filter(function($e) {
                return $e['foreign_entity'] == env('FOLDINGCOIN_FEE_RECOVERY_ADDRESS') and $e['tx_type'] == EscrowAddressLedgerEntry::TYPE_BLOCKCHAIN_DELIVERY_FEE;
            });
        PHPUnit::assertCount(1, $fee_entries);
        $entry = $fee_entries->first();
        PHPUnit::assertEquals(-239.0, $entry['amount']->getFloatValue());
        // echo "\n".$ledger->debugDumpLedger($ledger->findAllByAddress($escrow_address))."\n";

        // test that fee recover ledger was updated
        $balance = $recovery_ledger->balance('FLDC');
        PHPUnit::assertEquals(239, $balance->getFloatValue());
        $balance = $recovery_ledger->balance('BTC');
        PHPUnit::assertEquals(0.0059, $balance->getFloatValue());
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
