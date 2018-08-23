<?php

namespace App\Models;

use Tokenly\LaravelApiProvider\Model\APIModel;
use Exception;
use User;

class RecipientWithdrawal extends APIModel {

    protected $api_attributes = ['id',];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
