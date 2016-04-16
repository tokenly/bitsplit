<?php
namespace Models;

use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
	
	public static function getStageMap()
	{
		static $map_cache = false;
		if($map_cache){
			return $map_cache;
		}
		$path = storage_path().'/app/distribution-stage-map.json';
		$get = json_decode(@file_get_contents($path), true);
		if(!is_array($get)){
			throw new \Exception('Cannot open distribution stage map');
		}
		$map_cache = $get;
		return $get;
	}
	
	public static function getStageName($stage)
	{
		$stage = (string)$stage;
		$map = Distribution::getStageMap();
		if(isset($map[$stage])){
			return $map[$stage];
		}
		return false;
	}
	
	public function stageName()
	{
		return Distribution::getStageName($this->stage);
	}
	
	public function incrementStage()
	{
		$this->stage = $this->stage+1;
		return $this->save();
	}
	
	public function backtrackStage($stage = false)
	{
		if(!$stage){
			if($this->stage > 0){
				$this->stage = $this->stage - 1;
			}
		}
		else{
			$this->stage = $stage;
		}
		return $this->save();
	}
	
	public function getExtra()
	{
		$decode = json_decode($this->extra, true);
		if(!is_array($decode)){
			return false;
		}
		return $decode;
	}
	
	public function addressCount()
	{
		return DistributionTx::where('distribution_id', $this->id)->count();
	}
	
	public function countComplete()
	{
		return DistributionTx::where('distribution_id', $this->id)->where('confirmed', 1)->count();
	}
	
}
