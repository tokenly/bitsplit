<?php

namespace Tests\unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Assert as PHPUnit;
use TestCase;

class WalletsDatabaseTest extends TestCase
{

    protected $use_database = true;

    public function testCreateWalletsDatabase()
    {
        // verify that the wallets table exists
        PHPUnit::assertTrue(Schema::connection(Config::get('database.wallets'))->hasTable('escrow_wallets'));

        // verify that the wallets table does not exist on the main database
        PHPUnit::assertFalse(Schema::hasTable('escrow_wallets'));
    }

}
