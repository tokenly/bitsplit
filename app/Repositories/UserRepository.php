<?php

namespace App\Repositories;

use Exception;
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

}
