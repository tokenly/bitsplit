<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DailyFolder extends Model
{
    public static function countUniqueFolders()
    {
        $result = DB::select("SELECT COUNT(*) FROM (SELECT 1 FROM daily_folders GROUP BY bitcoin_address) t;")[0];
        return get_object_vars($result)['COUNT(*)'] ?? 0;
    }
}
