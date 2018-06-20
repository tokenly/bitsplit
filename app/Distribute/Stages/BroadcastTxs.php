<?php
namespace Distribute\Stages;
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserWalletManager;
use Config, UserMeta, DB, Exception, Log, Models\Fuel, Models\Distribution as Distro;
use Models\DistributionTx;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;

class BroadcastTxs extends Stage
{
	
	public function init()
	{
		$distro = $this->distro;
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
        if($distro->fee_rate != null){
            $per_byte = $distro->fee_rate;
        }
		$xcp_tx_bytes = Config::get('settings.xcp_tx_bytes');        
		$dust_size = $distro->getBTCDustSatoshis();
		$dust_size_float = round($dust_size/100000000,8);
        $tx_fee = $xcp_tx_bytes * $per_byte;
        $fee_float = round($tx_fee/100000000,8);

        $send_list = $this->buildSendList($distro);
        if ($send_list === null) {
        	return false;
        }
		if(count($send_list) == 0){
			// all transactions are broadcasted
            $this->goToNextStage($distro);
			return true;
		}
		
		$substation = Substation::instance();
		$user = User::find($distro->user_id);
		$wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);

		foreach($send_list as $row){
			$sent_txid = null;
			try{
				$exp_utxos = explode(',', $row->utxo);
				$utxos = array();
				foreach($exp_utxos as $utxo){
					$exp_utxo = explode(':', $utxo);
					if(!isset($exp_utxo[1])){
						Log::error('Malformed utxo entry for distro '.$distro->id.' -> '.$row->destination);
						continue 2;
					}
					// $utxos[] = array('txid' => $exp_utxo[0], 'n' => $exp_utxo[1]);
					$utxos[] = "{$exp_utxo[0]}:{$exp_utxo[1]}";
				}
				
				$send_parameters = [
					'requestId' => $distro->id.':'.$row->id, // the requestId prevents duplicate transactions
					'txos' => $utxos,
				];
				$destination_quantity = CryptoQuantity::fromSatoshis($row->quantity);
                $send_result = $substation->sendImmediatelyToSingleDestination($wallet_uuid, $distro->address_uuid, $distro->asset, $destination_quantity, $row->destination, $send_parameters);
                $sent_txid = $send_result['txid'];
			}
			catch(Exception $e){
				EventLog::logError('distribution.txSendError', $e, [
				    'distributionId' => $distro->id,
					'quantity' => $row->quantity,
					'asset' => $distro->asset,
					'destination' => $row->destination,
				]);

				continue;
			}

			$row->txid = $sent_txid;
			$save = $row->save();
			EventLog::debug('distribution.txSent', [
			    'distributionId' => $distro->id,
				'quantity' => $row->quantity,
				'asset' => $distro->asset,
				'destination' => $row->destination,
				'txid' => $sent_txid,
			]);
		}

		// see if it is complete now
        $send_list = $this->buildSendList($distro);
		if($send_list !== null and count($send_list) == 0){
            $this->goToNextStage($distro);
			return true;
		}

		return true;
	}

	protected function buildSendList($distribution)
	{
	    $address_list = DistributionTx::where('distribution_id', $distribution->id)->get();
	    if(!$address_list OR count($address_list) == 0){
	    	Log::error('No distribution addresses found for distro '.$distribution->id);
	    	return null;
	    }
	    $send_list = [];
	    foreach($address_list as $row){
	    	if(trim($row->txid) == '' AND trim($row->utxo) != ''){
	    		$send_list[] = $row;
	    	}
	    }

	    return $send_list;
	}
	
	protected function goToNextStage($distribution)
	{
		EventLog::info('distribution.stageComplete', [
		    'distributionId' => $distribution->id,
		    'stage' => 'BroadcastTxs',
		]);

		$distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();

		return true;
	}

}
