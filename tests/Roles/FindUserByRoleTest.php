<?php

namespace Tests\unit;

use App\Repositories\UserRepository;
use PHPUnit\Framework\Assert as PHPUnit;
use Spatie\Permission\Models\Role;
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

        $admin_role = Role::findByName('admin');
        $owner = $admin_role->users->first();
        PHPUnit::assertCount(1, $admin_role->users);
        PHPUnit::assertEquals($users[1]['id'], $owner['id']);
    }

}
