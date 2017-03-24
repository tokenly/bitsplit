<?php
namespace Distribute\Stages;
use Config, UserMeta, DB, Exception, Log, Models\Fuel, Models\Distribution as Distro, Models\DistributionTx as DistroTx;

class BroadcastTxs extends Stage
{
	
	public function init()
	{
		$distro = $this->distro;
		$xchain = xchain();
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
		$xcp_tx_bytes = Config::get('settings.xcp_tx_bytes');        
		$dust_size = $distro->getBTCDustSatoshis();
		$dust_size_float = round($dust_size/100000000,8);
        $tx_fee = $xcp_tx_bytes * $per_byte;
        $fee_float = round($tx_fee/100000000,8);
				
		$address_list = DistroTx::where('distribution_id', $distro->id)->get();
		if(!$address_list OR count($address_list) == 0){
			Log::error('No distribution addresses found for distro '.$distro->id);
			return false;
		}
		$send_list = array();
		foreach($address_list as $row){
			if(trim($row->txid) == '' AND trim($row->utxo) != ''){
				$send_list[] = $row;
			}
		}
		if(count($send_list) == 0){
			//all transactions signed, proceed
			$distro->incrementStage();
            $distro->sendWebhookUpdateNotification();
			return true;
		}
		
		foreach($send_list as $row){
			$send = false;
			try{
				$exp_utxos = explode(',', $row->utxo);
				$utxos = array();
				foreach($exp_utxos as $utxo){
					$exp_utxo = explode(':', $utxo);
					if(!isset($exp_utxo[1])){
						Log::error('Malformed utxo entry for distro '.$distro->id.' -> '.$row->destination);
						continue 2;
					}
					$utxos[] = array('txid' => $exp_utxo[0], 'n' => $exp_utxo[1]);
				}
				
				$quantity_float = round($row->quantity/100000000, 8, PHP_ROUND_HALF_DOWN);
                $send = $xchain->send($distro->address_uuid, $row->destination, $quantity_float, $distro->asset, $fee_float, $dust_size_float, null, $utxos);
                //$send = $xchain->sendFromAccount($distro->address_uuid, $row->destination, $quantity_float, $distro->asset, 'default', false, null, $dust_size_float, null, $utxos, $per_byte);
			}
			catch(Exception $e){
				Log::error('Error sending tx for distro '.$distro->id.' to address '.$row->destination.': '.$e->getMessage());
				continue;
			}
			if(!$send){
				Log::error('Unknown error sending tx for distro '.$distro->id.' to address '.$row->destination);
				continue;
			}
			if(!isset($send['txid']) OR trim($send['txid']) == '' OR trim($send['txid']) == 'NULL'){
				Log::error('Failed broadcasting tx for distro '.$distro->id.' to address '.$row->destination);
				continue;
			}
			$row->txid = $send['txid'];
			$save = $row->save();
			if(!$save){
				Log::error('Failed saving tx '.$send['txid'].' for distro '.$distro->id.' to address '.$row->destination);
			}
			else{
				Log::info('Distro '.$distro->id.' tx sent to '.$row->destination.' -> '.$send['txid']);
			}
		}
		return true;
	}
	
}
