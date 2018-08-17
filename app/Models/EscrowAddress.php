<?php

namespace App\Models;

use App\Models\Concerns\UseWalletsDB;
use Exception;
use Tokenly\LaravelApiProvider\Model\APIModel;

class EscrowAddress extends APIModel {

    use UseWalletsDB;

    protected $api_attributes = ['id',];

}
