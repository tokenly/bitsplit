<?php

namespace App\Console\Commands\EscrowAddress;

use App\Libraries\EscrowWallet\EscrowAddressSynchronizer;
use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use App\Repositories\EscrowAddressRepository;
use App\Repositories\UserRepository;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\TokenpassClient\TokenpassAPI;

class CreateEscrowAddressPromise extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'escrow:create-promise
        {destination : Destination address hash }
        {asset : asset }
        {amount : amount as a float }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a promise from the admin escrow address';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        DB::transaction(function () {
            $destination = $this->argument('destination');
            $asset = $this->argument('asset');
            $amount = $this->argument('amount');

            $msg = "Creating a promise of $amount $asset to $destination";
            $this->info($msg);

            $ledger = app(EscrowAddressLedgerEntryRepository::class);
            $user_repository = app(UserRepository::class);
            $owner = $user_repository->findEscrowWalletOwner();
            $escrow_address = $owner->getEscrowAddress();

            $timestamp = time();
            $quantity = CryptoQuantity::fromFloat($amount);

            // call tokenpass
            $tokenpass = app(TokenpassAPI::class);
            $ref = 'manual:' . $timestamp;
            $promise_response = $tokenpass->promiseTransaction($escrow_address['address'], $destination, $asset, $quantity->getSatoshisString(), $_expiration = null, $_txid = null, $_fingerprint = null, $ref);
            if (!$promise_response) {
                $error_string = $tokenpass->getErrorsAsString();
                $tokenpass->clearErrors();
                throw new Exception("Tokenpass promiseTransaction call failed: {$error_string}", 1);
            }
            $promise_id = $promise_response['promise_id'];
            $this->comment("Tokenpass promise succeeded with promise id {$promise_id}");

            // add to ledger
            $tx_identifier = 'manual:' . $asset . ':' . $timestamp;
            $txid = 'manual:' . $timestamp;
            $ledger->debit($escrow_address, $quantity, $asset, EscrowAddressLedgerEntry::TYPE_WITHDRAWAL, $txid, $tx_identifier, $_confirmed = true, $promise_id, $destination);

        });

        $this->comment("done.");
    }
}
