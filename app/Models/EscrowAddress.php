<?php

namespace App\Models;

use App\Models\Concerns\UseWalletsDB;
use App\Models\EscrowWallet;
use Exception;
use Tokenly\LaravelApiProvider\Model\APIModel;

class EscrowAddress extends APIModel {

    use UseWalletsDB;

    protected $api_attributes = ['id',];

    public function escrowWallet()
    {
        return $this->belongsTo(EscrowWallet::class, 'wallet_id');
    }

}
