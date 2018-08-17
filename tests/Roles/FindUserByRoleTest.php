<?php

namespace Tests\unit;

use App\Repositories\UserRepository;
use PHPUnit\Framework\Assert as PHPUnit;
use TestCase;

class FindUserByRoleTest extends TestCase
{

    protected $use_database = true;

    public function testFindUserByRole()
    {
        $users = [];
        $users[] = app('UserHelper')->newRandomUser();
        $users[] = app('UserHelper')->newRandomUser();

        $users[1]->makeAdmin();

        $user_repository = app(UserRepository::class);
        $owner = $user_repository->findEscrowWalletOwner();
        PHPUnit::assertEquals($users[1]['id'], $owner['id']);
    }

}
