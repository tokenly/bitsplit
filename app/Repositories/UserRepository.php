<?php

namespace App\Repositories;

use Exception;
use Spatie\Permission\Models\Role;
use Tokenly\LaravelApiProvider\Contracts\APIUserRepositoryContract;
use Tokenly\LaravelApiProvider\Repositories\UserRepository as APIUserRepository;

/*
* UserRepository
*/
class UserRepository extends APIUserRepository implements APIUserRepositoryContract
{

    protected $model_type = '\User';

    public function findByUuid($uuid) {
        return null;
    }
    public function findByAPIToken($api_token) {
        return null;
    }

    public function findByUsername(string $username)
    {
        return $this->prototype_model->where('username', $username)->first();
    }

    public function findEscrowWalletOwner()
    {
        $admin_role = Role::findByName('admin');
        return $admin_role->users->first();
    }
}
