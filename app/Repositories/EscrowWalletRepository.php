<?php

namespace App\Repositories;

use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use User;

/*
 * EscrowWalletRepository
 */
class EscrowWalletRepository extends APIRepository
{

    protected $model_type = 'App\Models\EscrowWallet';

    public function findByUser(User $user, string $chain)
    {
        return $this->prototype_model
            ->where('user_id', '=', $user['id'])
            ->where('chain', '=', $chain)
            ->first();
    }

}
