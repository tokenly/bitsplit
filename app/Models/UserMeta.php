<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Exception;

class UserMeta extends Model
{
	protected $table = 'user_meta';
	
	public static function allUser($id)
	{
		$getAll = UserMeta::where('userId', '=', $id)->all();
		$output = array();
		foreach($getAll as $row){
			$output[$row->metaKey] = $row->value;
		}
		return $output;
	}
	
	public static function getMeta($id, $key)
	{
		$get = UserMeta::where('userId', '=', $id)->where('metaKey', '=', $key)->first();
		if(!$get){
			return false;
		}
		return $get->value;
	}
	
	public static function setMeta($id, $key, $value)
	{
		$get = UserMeta::where('userId', '=', $id)->where('metaKey', '=', $key)->first();
		if(!$get){
			$get = new UserMeta;
			$get->userId = $id;
			$get->metaKey = $key;
		}
		$get->value = $value;
		$get->save();
		return true;
	}
	
	public static function cleanMeta($id, $key)
	{
		$get = UserMeta::where('userId', '=', $id)->where('metaKey', '=', $key)->first();
		if($get){
			$get->delete();
		}
		return true;
	}

}
