<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyFolder extends Model
{
    public static function countUniqueFolders()
    {
        return self::groupBy('bitcoin_address')->count();
    }
}
