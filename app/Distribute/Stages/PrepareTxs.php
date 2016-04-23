<?php
namespace Distribute\Stages;
use Config, UserMeta, DB, Exception, Log, Models\Fuel, Models\Distribution as Distro, Models\DistributionTx as DistroTx;

class PrepareTxs extends Stage
{
	
	public function init()
	{
		$distro = $this->distro;
		$xchain = xchain();
		$tx_count = $distro->addressCount();
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
		$average_size = Config::get('settings.average_tx_bytes');
		$dust_size = Config::get('settings.default_dust');
		$default_miner = Config::get('settings.miner_fee');
		$base_txo_cost = ($average_size * $per_byte) + ($dust_size * 2);
		$float_cost = round($base_txo_cost/100000000,8);
		
		$address_list = DistroTx::where('distribution_id', $distro->id)->get();
		if(!$address_list OR count($address_list) == 0){
			Log::error('Error preparing txs: no addresses found distro '.$distro->id);
			return false;
		}	
		$assign_list = array();
		$used_txos = array();
		foreach($address_list as $row){
			$utxo = trim($row->utxo);
			if($utxo == '' OR trim($row->raw_tx) == ''){
				$assign_list[] = $row;
			}
			if($utxo != ''){
				$used_txos[] = $utxo;
			}
		}
		
		if(count($assign_list) == 0){
			//all utxos assigned
			Log::info('All Transactions prepared for distro '.$distro->id);
			$distro->incrementStage();
			return true;
		}
				
		$utxo_list = false;
		try{
			$utxo_list = $xchain->checkPrimedUTXOs($distro->address_uuid, $float_cost);
		}
		catch(Exception $e){
			Log::error('Error getting utxos for distro #'.$distro->id.': '.$e->getMessage());
			return false;
		}		
		
		if($utxo_list AND isset($utxo_list['utxos'])){
			$utxo_list = $utxo_list['utxos'];
		}
		foreach($assign_list as $row){
			if(trim($row->utxo) == ''){
				//assign utxo first
				$new_utxo = false;
				foreach($utxo_list as $utxo){
					if($utxo['amount'] >= $float_cost){
						$txo_id = $utxo['txid'].':'.$utxo['n'];
						if(!in_array($txo_id, $used_txos)){
							$new_utxo = $txo_id;
							$used_txos[] = $txo_id;
							break;
						}
					}
				}
				if($new_utxo){
					$row->utxo = $new_utxo;
					$save = $row->save();
					if(!$save){
						Log::error('Error assigning utxo to Distro #'.$distro->id.' -> '.$row->destination.' '.$new_utxo);
						continue;
					}
					else{
						Log::info('Distro #'.$distro->id.' UTXO assigned to '.$row->destination.' '.$new_utxo);
						continue;
					}
				}
			}
			else{
				//construct raw transaction
			}
		}	
	}
}
