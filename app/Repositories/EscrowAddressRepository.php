<?php

namespace App\Repositories;

use App\Models\EscrowWallet;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use User;

/*
 * EscrowAddressRepository
 */
class EscrowAddressRepository extends APIRepository
{

    protected $model_type = 'App\Models\EscrowAddress';

    public function createAddress(EscrowWallet $wallet, User $user, array $attributes)
    {
        $attributes['wallet_id'] = $wallet['id'];
        $attributes['chain'] = $wallet['chain'];
        $attributes['user_id'] = $user['id'];

        return parent::create($attributes);
    }

    public function findAllByUser(User $user, string $chain = null, int $limit = null)
    {
        $query = $this->prototype_model->where('user_id', '=', $user['id']);

        if ($chain !== null) {
            $query->where('chain', '=', $chain);
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $query->orderBy('offset');

        return $query->get();
    }

    public function findByAddress(string $address_hash)
    {
        return $this->prototype_model
            ->where('address', $address_hash)
            ->first();
    }

}
