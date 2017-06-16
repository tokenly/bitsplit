<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyFolder extends Model
{
    public static function countUniqueFolders()
    {
        return count(self::select('bitcoin_address')->groupBy('bitcoin_address')->get());
    }
}
