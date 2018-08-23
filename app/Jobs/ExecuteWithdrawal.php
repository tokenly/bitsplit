<?php

namespace App\Jobs;

use App\Libraries\EscrowWallet\EscrowWalletManager;
use App\Libraries\Substation\Substation;
use App\Libraries\Withdrawal\RecipientWithdrawalManager;
use App\Models\EscrowAddressLedgerEntry;
use App\Models\RecipientWithdrawal;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use App\Repositories\RecipientWithdrawalRepository;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenpassClient\TokenpassAPI;

class ExecuteWithdrawal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $withdrawal;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(RecipientWithdrawal $withdrawal)
    {
        $this->withdrawal = $withdrawal;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RecipientWithdrawalManager $recipient_withdrawal_manager, EscrowAddressLedgerEntryRepository $ledger, TokenpassAPI $tokenpass, RecipientWithdrawalRepository $recipient_withdrawal_repository, UserRepository $user_repository, EscrowWalletManager $escrow_wallet_manager)
    {
        $user = $this->withdrawal->user;
        $destination_address = $this->withdrawal->address;
        $asset = $this->withdrawal->asset;

        $owner = $user_repository->findEscrowWalletOwner();
        $escrow_address = $escrow_wallet_manager->getEscrowAddressForUser($owner, Substation::chain());

        try {
            // verify the address is owned by the user
            $user_addresses = collect($recipient_withdrawal_manager->getAddressesForUser($user))->keyBy('address');
            if (!isset($user_addresses[$destination_address])) {
                EventLog::warning('withdrawal.unknownAddress', [
                    'userId' => $user['id'],
                    'address' => $destination_address,
                    'asset' => $asset,
                ]);
                return null;
            }

            // call Tokenpass to cancel all the promise(s)
            [$promised_total_quantity, $tmp_delivery_ledger_entry] = DB::transaction(function() use ($user, $escrow_address, $ledger, $tokenpass, $destination_address, $asset) {
                $promised_total_quantity = CryptoQuantity::zero();

                $entries = $ledger->entriesWithPromiseIDsByForeignEntityAndAsset($destination_address, $asset);
                foreach($entries as $ledger_entry) {
                    // cancel the tokenpass promise
                    $tokenpass->clearErrors();
                    $tokenpass_response = $tokenpass->deletePromisedTransaction($ledger_entry['promise_id']);
                    if (!$tokenpass_response) {
                        $error_string = $tokenpass->getErrorsAsString();
                        $tokenpass->clearErrors();

                        if ($error_string == 'Provisional tx not found') {
                            // allow not found provisional transactions
                            EventLog::warn('provisionalTx.notFound', [
                                'promiseId' => $ledger_entry['promise_id'],
                                'userId' => $user['id'],
                                'address' => $destination_address,
                                'asset' => $asset,
                            ]);
                        } else {
                            throw new Exception("Tokenpass deletePromisedTransaction call failed: {$error_string}", 1);
                        }
                    }

                    // remove the promise id from the ledger
                    $ledger->update($ledger_entry, [
                        'promise_id' => null,
                    ]);


                    // sum the total quantity
                    //   this is subtracted since it is a foreign entity transaction - we are looking at the other side of the transaction
                    $promised_total_quantity = $promised_total_quantity->subtract($ledger_entry['amount']);
                }

                // if the quantity was zero, don't create the ledger entries
                if ($promised_total_quantity->lte(0)) {
                    return [$promised_total_quantity, null];
                }

                // add a credit to reverse the promises total
                $txid = $this->withdrawal['uuid'];
                $tx_identifier = 'fulfill:' . $asset . ':' . $this->withdrawal['uuid'];
                $ledger->credit($escrow_address, $promised_total_quantity, $asset, EscrowAddressLedgerEntry::TYPE_PROMISE_FULFILLED, $txid, $tx_identifier, $_confirmed = true, $_promise_id = null, $destination_address);

                // create a temporary delivery debit that will be deleted after the Substation send is completed
                $txid = $this->withdrawal['uuid'];
                $tx_identifier = 'tmp-deliver:' . $asset . ':' . $this->withdrawal['uuid'];
                $tmp_delivery_ledger_entry = $ledger->debit($escrow_address, $promised_total_quantity, $asset, EscrowAddressLedgerEntry::TYPE_BLOCKCHAIN_DELIVERY, $txid, $tx_identifier, $_confirmed = false, $_promise_id = null);

                return [$promised_total_quantity, $tmp_delivery_ledger_entry];
            });


            // verify that the quantity was not zero
            if ($promised_total_quantity->lte(0)) {
                $error = "Balance was zero";
                EventLog::warning('withdrawal.zeroBalance', [
                    'userId' => $user['id'],
                    'address' => $destination_address,
                    'asset' => $asset,
                ]);
                $recipient_withdrawal_repository->update($this->withdrawal, [
                    'error' => $error,
                    'completed_at' => time(),
                ]);

                return;
            }


            // call Substation
            $substation_client = $this->getSubstationClient();
            $send_parameters = [
                'requestId' => $this->withdrawal->uuid,
            ];
            $wallet = $escrow_address->escrowWallet;
            $send_result = $substation_client->sendImmediatelyToSingleDestination($wallet['uuid'], $escrow_address['uuid'], $asset, $promised_total_quantity, $destination_address, $send_parameters);
            Log::debug("\$send_result=".json_encode($send_result, 192));

            EventLog::info('withdrawal.sent', [
                'userId' => $user['id'],
                'address' => $destination_address,
                'asset' => $asset,
                'quantity' => $promised_total_quantity->getSatoshisString(),
                'txid' => $send_result['txid'],
            ]);


            // now that the substation transaction was successfully sent
            //   delete the temporary delivery ledger entry
            $ledger->delete($tmp_delivery_ledger_entry);

        } catch (Exception $e) {
            EventLog::logError('withdrawal.error', $e, [
                'userId' => $user['id'],
                'address' => $destination_address,
                'asset' => $asset,
            ]);

            throw $e;
        }

    }

    protected function getSubstationClient()
    {
        return app('substationclient.escrow');
    }

}
