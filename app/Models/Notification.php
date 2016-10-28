<?php

namespace App\Models;

use Tokenly\LaravelApiProvider\Model\APIModel;
use Exception;

class Notification extends APIModel {

    const STATUS_NEW     = 1;
    const STATUS_SUCCESS = 2;
    const STATUS_FAILURE = 3;

    protected $api_attributes = ['id',];

    protected $casts = [
        'notification' => 'json',
    ];


}
