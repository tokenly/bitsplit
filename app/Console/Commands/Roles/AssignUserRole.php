<?php

namespace App\Console\Commands\Roles;

use App\Libraries\EscrowWallet\EscrowWalletManager;
use App\Repositories\UserRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AssignUserRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'role:assign
        { username : Username of the user who owns the escrow address }
        { role : The role name to assign }
        { --remove : Remove the role instead of assigning it }
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assigns a role to the given user';

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
        $role = $this->argument('role');
        $remove = $this->option('remove');

        $user = $user_repository->findByUsername($username);
        if (!$user) {
            $this->error("User not found");
            return 1;
        }

        if ($remove) {
            $this->comment("Removing role {$role} from user {$user['username']}");
            Log::info("Removing role {$role} from user {$user['username']}");
            $user->removeRole($role);

        } else {
            $this->comment("Assigning user {$user['username']} role {$role}");
            Log::info("Assigning user {$user['username']} role {$role}");
            $user->createAndAssignRole($role);
        }

        $this->info("done");
    }
}
