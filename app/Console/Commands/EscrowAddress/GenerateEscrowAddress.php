<?php

namespace App\Console\Commands\EscrowAddress;

use App\Libraries\EscrowWallet\EscrowWalletManager;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelEventLog\Facade\EventLog;

class GenerateEscrowAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'escrow:generate
        { username : Username of the user who owns the escrow address }
        { recovery-address : The recovery address used to make withdrawals from the escrow address }
        { --chain=counterparty : Chain of the escrow address (counterparty or counterpartyTestnet) }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates an escrow address for the given user';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(UserRepository $user_repository, EscrowWalletManager $escrow_wallet_manager)
    {
        $username = $this->argument('username');
        $recovery_address = $this->argument('recovery-address');
        $chain = $this->option('chain');

        $user = $user_repository->findByUsername($username);
        if (!$user) {
            $this->error("User not found");
            return 1;
        }

        $existing_address = $escrow_wallet_manager->getEscrowAddressForUser($user, $chain);
        if ($existing_address) {
            $this->info("Escrow address already exists for user {$user['username']}: ".$existing_address['address']);
            Log::info("Escrow address already exists for user {$user['username']}: ".$existing_address['address']);
            return;
        }
        
        $this->comment("Creating new escrow address for user {$user['username']} on chain {$chain} with recovery address {$recovery_address}");
        Log::info("Creating new escrow address for user {$user['username']} on chain {$chain} with recovery address {$recovery_address}");
        $new_address = $escrow_wallet_manager->ensureEscrowAddressForUser($user, $recovery_address, $chain);

        $this->info("New escrow address generated for user {$user['username']}: ".$new_address['address']);
        Log::info("New escrow address generated for user {$user['username']}: ".$new_address['address']);
    }
}
