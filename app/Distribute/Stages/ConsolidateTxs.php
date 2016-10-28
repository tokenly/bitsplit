<?php
namespace Distribute\Stages;
use Config, UserMeta, DB, Exception, Log, Models\Distribution as Distro, Models\DistributionTx as DistroTx;

class ConsolidateTxs extends Stage
{
	public function init()
	{
		$distro = $this->distro; 
		$xchain = xchain();
		$max_inputs = Config::get('settings.max_tx_outputs');
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
		$reasonable_count = 10; //number of inputs this address is allowed before cleanup required
		$get_utxos = false;
		
		$balances = false;
		try{
			$balances = $xchain->getAccountBalances($distro->address_uuid, 'default');
		}
		catch(Exception $e){
			Log::error('Error checking balances for distro #'.$distro->id.' consolidation: '.$e->getMessage());
			return false;
		}
		
		if(!$balances OR !isset($balances['unconfirmed'])){
			Log::error('Failed getting current balances for distro #'.$distro->id.' consolidation');
			return false;
		}		
		
		if($balances['unconfirmed']['BTC'] > 0 OR $balances['sending']['BTC'] > 0){
			Log::info('Waiting for all transactions to confirm for distro #'.$distro->id.' before consolidating');
			return false;
		}
		
		try{
			//not actually checking for primes, just getting utxo list
			$get_utxos = $xchain->checkPrimedUTXOs($distro->address_uuid, 0.0001); 
		}
		catch(Exception $e){
			Log::error('Error checking utxo list for distro #'.$distro->id.' consolidation: '.$e->getMessage());
			return false;
		}
		
		if(!$get_utxos OR !isset($get_utxos['utxos'])){
			Log::error('Failed getting utxo list for distro #'.$distro->id.' consolidation');
			return false;
		}
		
		$get_utxos = $get_utxos['utxos'];
		$valid_utxos = array();
		foreach($get_utxos as $utxo){
			if($utxo['type'] == 'confirmed' AND $utxo['green']){
				$valid_utxos[] = $utxo;
			}
		}
		$utxo_count = count($valid_utxos);
		
		if($utxo_count <= $reasonable_count){
			Log::info('No more tx consolidation needed for distro #'.$distro->id);
			$distro->incrementStage();
            $distro->sendWebhookUpdateNotification();
			return true;
		}
		
		$num_cleanups = ceil($utxo_count / $max_inputs);
		$per_cleanup = floor($utxo_count / $num_cleanups);
		if($per_cleanup > $max_inputs){
			$per_cleanup = $max_inputs;
		}
		
		for($i = 0; $i < $num_cleanups; $i++){
			$cleanup = false;
			try{
				$cleanup = $xchain->cleanupUTXOs($distro->address_uuid, $per_cleanup, $per_byte);	
			}
			catch(Exception $e){
				Log::error('Error consolidating utxos for distro #'.$distro->id.': '.$e->getMessage());
				continue;
			}
			
			if(!$cleanup OR !isset($cleanup['txid'])){
				Log::error('Unkown error consolidating utxos for distro #'.$distro->id);
				continue;
			}
			if($cleanup['txid'] == null OR trim($cleanup['txid']) == ''){
				Log::error('Failed consolidating utxos for distro #'.$distro->id);
				continue;
			}
			Log::info($utxo_count.' UTXOs consolidated for distro #'.$distro->id.' txid: '.$cleanup['txid']);
		}
		return true;
	}
}
