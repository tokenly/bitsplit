<?php

namespace App\Console\Commands\EscrowAddress;

use App\Libraries\EscrowWallet\EscrowAddressSynchronizer;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use App\Repositories\EscrowAddressRepository;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;

class SyncEscrowAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'escrow:sync-address
        {--username= : Sync all addresses for this user }
        {--address= : Sync address by hash }
        {--no-sync : Do not sync.  Show balances only. }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronizes an address with Substation and Tokenpass';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $username = $this->option('username');
        $address_hash = $this->option('address');
        $do_sync = !$this->option('no-sync');

        $escrow_address_repository = app(EscrowAddressRepository::class);
        if ($address_hash) {
            $address = $escrow_address_repository->findByAddress($address_hash);
            if (!$address) {
                $this->error("Address not found");
                return 1;
            }
            $addresses = [$address];
        } else if ($username) {
            $user = app(UserRepository::class)->findByUsername($username);
            if (!$user) {
                $this->error("User not found");
                return 1;
            }
            $addresses = $escrow_address_repository->findAllByUser($user);
            if (count($addresses) == 0) {
                $this->comment("No addresses found for this user", 1);
                return 1;
            }
        } else {
            $this->error("Please supply a username or address");
            return 1;
        }

        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $table_headers = ['address', 'asset', 'value', 'satoshis'];
        $table_rows = [];
        foreach ($addresses as $address) {
            if ($do_sync) {
                // synchronize
                app(EscrowAddressSynchronizer::class)->synchronizeLedgerWithSubstation($address);
            }

            $balances = $ledger->addressBalancesByAsset($address);
            foreach ($balances as $asset => $balance) {
                $table_rows[] = [
                    $address['address'],
                    $asset,
                    $balance->getFloatValue(),
                    $balance->getSatoshisString(),
                ];
            }

            if (!$balances) {
                $table_rows[] = [
                    $address['address'],
                    '[none]',
                    '0',
                    '0',
                ];
            }
        }

        $this->info("Updated balances");
        $this->table($table_headers, $table_rows);

        $this->comment("done.");
    }
}
