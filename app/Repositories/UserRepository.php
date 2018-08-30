<?php

namespace App\Repositories;

use App\Libraries\Substation\Substation;
use App\Repositories\EscrowWalletRepository;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract;
use Tokenly\LaravelApiProvider\Repositories\UserRepository as APIUserRepository;

/*
 * UserRepository
 */
class UserRepository extends APIUserRepository implements APIUserRepositoryContract
{

    protected $model_type = '\User';

    public function findByUuid($uuid)
    {
        return null;
    }
    public function findByAPIToken($api_token)
    {
        return null;
    }

    public function findByUsername(string $username)
    {
        return $this->prototype_model->where('username', $username)->first();
    }

    public function findEscrowWalletOwner()
    {
        $owner_id = Cache::remember('escrowWallet.ownerId', $_minutes = 60, function () {
            $wallet_repository = app(EscrowWalletRepository::class);

            // check all admin users and return the first wallet
            $chain = Substation::chain();
            $admin_role = Role::findByName('admin');
            foreach ($admin_role->users as $user) {
                $wallet = $wallet_repository->findByUser($user, $chain);
                if ($wallet) {
                    return $user['id'];
                }
            }

            return null;
        });

        if ($owner_id === null) {
            return null;
        }

        return $this->findById($owner_id);
    }
}
