<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Config;

trait UseWalletsDB
{

    public function getConnectionName()
    {
        if (is_null($this->connection)) {
            $this->connection = Config::get('database.wallets');
        }

        return $this->connection;
    }

}
