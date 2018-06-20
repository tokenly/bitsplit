<?php
namespace Distribute\Stages;
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserWalletManager;
use Config, UserMeta, DB, Exception, Log;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;

class ConsolidateTxs extends Stage
{
	public function init()
	{
		$distro = $this->distro; 
		$max_inputs = Config::get('settings.max_tx_outputs');
		$per_byte = Config::get('settings.miner_satoshi_per_byte');
        if($distro->fee_rate != null){
            $per_byte = $distro->fee_rate;
        }
		$reasonable_count = 10; //number of inputs this address is allowed before cleanup required

		$substation = Substation::instance();
		$user = User::find($distro->user_id);
		$wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);
		
		$balances = false;
		try{
			$balances = $substation->getCombinedAddressBalanceById($wallet_uuid, $distro->address_uuid);
		}
		catch(Exception $e){
			EventLog::logError('getBalances.error', $e, [
			    'distributionId' => $distro->id,
			    'stage' => 'ConsolidateTxs',
			]);
			return false;
		}
		
		$unconfirmed_btc_balance = $balances['BTC']['unconfirmed']->subtract($balances['BTC']['confirmed']);
		if($unconfirmed_btc_balance->gt(0)){
			Log::info('Waiting for all transactions to confirm for distro #'.$distro->id.' before consolidating');
			EventLog::debug('txs.waiting', [
				'distributionId' => $distro->id,
				'unconfirmedBalance' => $unconfirmed_btc_balance->getSatoshisString(),
			]);
			return false;
		}
		
		try {
			$txos_info = $substation->getTXOsById($wallet_uuid, $distro->address_uuid);
			$txos = $txos_info['items'];
		} catch(Exception $e) {
			EventLog::logError('getTxos.error', $e, [
			    'distributionId' => $distro->id,
			    'stage' => 'ConsolidateTxs',
			]);
			return false;
		}
		
		$valid_utxos = array();
		foreach($txos as $utxo){
			if($utxo['confirmations'] > 0 and !$utxo['spent']){
				$valid_utxos[] = $utxo;
			}
		}
		$utxo_count = count($valid_utxos);

		// cleanup is no longer necessary
		//   just report and continue
		EventLog::debug('txs.counted', [
			'distributionId' => $distro->id,
			'utxoCount' => $utxo_count,
		]);

		$this->goToNextStage($distro);
		return true;
	}

	protected function goToNextStage($distribution)
	{
		EventLog::info('distribution.stageComplete', [
		    'distributionId' => $distribution->id,
		    'stage' => 'ConsolidateTxs',
		]);

		$distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();
		return true;
	}
}
