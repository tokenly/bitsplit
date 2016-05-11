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
		$base_txo_cost = ($average_size * $per_byte) + $dust_size;
		$float_cost = round($base_txo_cost/100000000,8);
		$fee_float = round(($average_size*$per_byte)/100000000,8);
		$dust_size_float = round($dust_size/100000000,8);
		
		$address_list = DistroTx::where('distribution_id', $distro->id)->get();
		if(!$address_list OR count($address_list) == 0){
			Log::error('Error preparing txs: no addresses found distro '.$distro->id);
			return false;
		}	
		$assign_list = array();
		$used_txos = array();
		foreach($address_list as $row){
			$exp_utxo = explode(',', $row->utxo);
			foreach($exp_utxo as $exp){
				$utxo = trim($exp);
				if($utxo == ''){
					$assign_list[] = $row;
				}
				else{
					$used_txos[] = $utxo;
				}
			}
		}

		if(count($assign_list) == 0){
			//all utxos assigned
			Log::info('All Transactions prepared for distro '.$distro->id);
			$distro->incrementStage();
			return true;
		}
		
		$pending_totals = $distro->pendingDepositTotals();
		if($pending_totals['fuel'] > 0){
			Log::info('Waiting for more fuel to confirm for distro #'.$distro->id.' [utxo prep]');
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
			$row_utxos = array();
			$coin_left = $float_cost;
			foreach($utxo_list as $utxo){
				$txo_id = $utxo['txid'].':'.$utxo['n'];
				if(!in_array($txo_id, $used_txos) AND $coin_left > 0){
					$row_utxos[] = $txo_id;
					$used_txos[] = $txo_id;
					$coin_left -= $utxo['amount'];
					continue;
				}
			}
			
			if($coin_left > 0){
				Log::error('Not enough inputs available for Distro #'.$distro->id.' transaction to '.$row->address);
				if($distro->use_fuel == 1 AND Config::get('settings.auto_pump_stuck_distros')){
					//pump a bit of fuel to give this a kick
					try{
						$pump = Fuel::pump($distro->user_id, $distro->deposit_address, $default_miner, 'BTC', $default_miner);
						$spent = intval(UserMeta::getMeta($distro->user_id, 'fuel_spent'));
						$spent = $spent + ($default_miner*2);
						UserMeta::setMeta($distro->user_id, 'fuel_spent', $spent);
						Log::info('Extra fuel pumped for distro '.$distro->id.' utxo prepping '.$pump['txid']);
					}
					catch(Exception $e){
						Log::error('Error pumping extra fuel for distro '.$distro->id.' utxo prepping: '.$e->getMessage());
					}
					
				}
				break; //nothing else can be prepped 
			}

			if(count($row_utxos) > 0){
				$new_utxo = join(',', $row_utxos);
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
		return true;	
	}
}
