<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class DailyFolder extends Model
{
    public static function countUniqueFolders($cached = false)
    {
        if($cached){
            try{
                $get = Cache::get('total-unique-folders');
            }
            catch(Exception $e){
                $get = false;
            }
            if(!$get){
                return 0;
            }
            return $get;
        }
        else{
            $result = DB::select("SELECT COUNT(DISTINCT(username)) as total FROM daily_folders")[0];
            $total = get_object_vars($result)['total'] ?? 0;
            Cache::forever('total-unique-folders', $total);
            return $total;
        }
    }
    
    public static function countUniqueFoldersInDateRange($start, $end)
    {
        $result = DB::select("SELECT COUNT(DISTINCT(username)) as total FROM daily_folders WHERE `date` >= '".$start."' AND `date` <= '".$end."' AND new_credit > 0")[0];
        $total = get_object_vars($result)['total'] ?? 0;
        return $total;
    }
    

}
