<?php

namespace App\Repositories;

use Tokenly\LaravelApiProvider\Repositories\APIRepository;
use Exception;

/*
* RecipientWithdrawalRepository
*/
class RecipientWithdrawalRepository extends APIRepository
{

    protected $model_type = 'App\Models\RecipientWithdrawal';

}
