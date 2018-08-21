<?php

namespace App\Distribute\Stages\Onchain;

use App\Distribute\Stages\Stage;
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserWalletManager;
use Config, UserMeta, DB, Exception, Log, Models\Fuel, Models\Distribution as Distro;
use Models\DistributionTx;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;

class PrepareTxs extends Stage
{
	
	public function init()
	{
		$distro = $this->distro;
		$tx_count = $distro->addressCount();
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
        if($distro->fee_rate != null){
            $per_byte = $distro->fee_rate;
        }
		$average_size = Config::get('settings.average_tx_bytes');
		$dust_size = $distro->getBTCDustSatoshis();
		$default_miner = Config::get('settings.miner_fee');
		$xcp_tx_bytes = Config::get('settings.xcp_tx_bytes');
        $extra_bytes = Config::get('settings.tx_extra_bytes');
        $input_bytes = Config::get('settings.tx_input_bytes'); 
        $txo_bytes = Config::get('settings.average_txo_bytes');
        
        $base_txo_cost = ($xcp_tx_bytes * $per_byte) + $dust_size;
		// $float_cost = round($base_txo_cost/100000000,8);
		$fee_float = round(($xcp_tx_bytes*$per_byte)/100000000,8);
		$dust_size_float = round($dust_size/100000000,8);
		

		[$distribution_txs_to_assign, $used_txos] = $this->collectDistributionTransactionInfo($distro);
		if ($used_txos === null) {
			return false;
		}

		if(count($distribution_txs_to_assign) == 0){
			//all utxos assigned
			//  we're done
			$this->goToNextStage($distro);
			return true;
		}
		
		$pending_totals = $distro->pendingDepositTotals();
		if($pending_totals['fuel'] > 0){
			Log::info('Waiting for more fuel to confirm for distro #'.$distro->id.' [utxo prep]');
			return true;
		}		

		$user = User::find($distro->user_id);
		$wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);
		$substation = Substation::instance();
		$substation_distribution = Substation::distributionClientInstance();
				
		try{
			$utxo_info = $substation_distribution->loadTXOInfoFromSubstation($wallet_uuid, $distro->address_uuid, CryptoQuantity::fromSatoshis($base_txo_cost));
			$primed_utxos = $utxo_info['primes'];
		}
		catch(Exception $e){
			Log::error('Error getting utxos for distro #'.$distro->id.': '.$e->getMessage());
			return false;
		}		
		// Log::debug("\$primed_utxos=".json_encode($primed_utxos, 192));
				
		foreach($distribution_txs_to_assign as $row){
			$row_utxos = array();
			$coin_left_to_assign = CryptoQuantity::fromSatoshis($base_txo_cost);
			foreach($primed_utxos as $utxo){
				$txo_id = $utxo['txid'].':'.$utxo['n'];
				if(!in_array($txo_id, $used_txos) AND $coin_left_to_assign->gt(0) AND $utxo['amount'] == $base_txo_cost){
					$row_utxos[] = $txo_id;
					$used_txos[] = $txo_id;
					$coin_left_to_assign = $coin_left_to_assign->subtract(CryptoQuantity::fromSatoshis($utxo['amount']));
					continue;
				}
			}
			
			if($coin_left_to_assign->gt(0)){
				Log::error('Not enough inputs available for Distro #'.$distro->id.' transaction to '.$row->address);
				if($distro->use_fuel == 1 AND Config::get('settings.auto_pump_stuck_distros')){
					//pump a bit of fuel to give this a kick
					try{
                        $miner_fee = (($input_bytes + $extra_bytes + ($txo_bytes*2)) * $per_byte);
						$pump = Fuel::pump($distro->user_id, $distro->deposit_address, $base_txo_cost, 'BTC', $miner_fee);
						$spent = intval(UserMeta::getMeta($distro->user_id, 'fuel_spent'));
						$spent = $spent + $base_txo_cost + $miner_fee;
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

		// see if all distributions are now assigned
		[$distribution_txs_to_assign, $used_txos] = $this->collectDistributionTransactionInfo($distro);
		if($distribution_txs_to_assign !== null and count($distribution_txs_to_assign) == 0){
			$this->goToNextStage($distro);
		}

		return true;	
	}

	protected function collectDistributionTransactionInfo($distribution)
	{
        $address_list = DistributionTx::where('distribution_id', $distribution->id)->get();
        if (!$address_list or count($address_list) == 0) {
            Log::error('Error preparing txs: no addresses found distro ' . $distribution->id);
            return [null, null];
        }
        $distribution_txs_to_assign = array();
        $used_txos = array();
        foreach ($address_list as $row) {
            $exp_utxo = explode(',', $row->utxo);
            foreach ($exp_utxo as $exp) {
                $utxo = trim($exp);
                if ($utxo == '') {
                    $distribution_txs_to_assign[] = $row;
                } else {
                    $used_txos[] = $utxo;
                }
            }
        }

        return [$distribution_txs_to_assign, $used_txos];
    }

	protected function goToNextStage($distribution)
	{
		EventLog::info('distribution.stageComplete', [
		    'distributionId' => $distribution->id,
		    'stage' => 'PrepareTxs',
		]);

		$distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();
		return true;
	}
}
