<?php
namespace Distribute\Stages;
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserWalletManager;
use Exception, Config, Log;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;

class CompleteCleanup extends Stage
{
	public function init()
	{
		$distro = $this->distro;
		$sweep_destination = env('HOUSE_INCOME_ADDRESS');
		$default_miner = Config::get('settings.miner_fee');

		$substation = Substation::instance();
		$user = User::find($distro->user_id);
		$wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);

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
		
		// $unconfirmed_btc_balance = $balances['BTC']['unconfirmed']->subtract($balances['BTC']['confirmed']);

		if(!isset($balances['BTC']) or $balances['BTC']['confirmed']->lt($default_miner)){
			EventLog::info('distribution.noSweep', [
			    'distributionId' => $distro->id,
				'btcBalance' => $balances['BTC']['confirmed']->getSatoshisString(),
			]);

			$this->completeDistribution($distro);
			return true;
		}

		// sweep the distribution asset
		if (isset($balances[$distro->asset]) AND $balances[$distro->asset]['confirmed']->gt(0)) {
			if($balances['BTC']['confirmed']->gte($default_miner * 2)) {
				$asset_quantity = $balances[$distro->asset]['confirmed'];
				$send_parameters = [
					'requestId' => $distro->id.':sweep:'.$distro->asset, // the requestId prevents duplicate transactions
					'feeRate' => 'medLow',
				];
	            $send_result = $substation->sendImmediatelyToSingleDestination($wallet_uuid, $distro->address_uuid, $distro->asset, $asset_quantity, $sweep_destination, $send_parameters);
	            $sent_txid = $send_result['txid'];

	            EventLog::debug('distribution.sweep', [
	                'distributionId' => $distro->id,
	            	'quantity' => $asset_quantity->getSatoshisString(),
	            	'asset' => $distro->asset,
	            	'destination' => $sweep_destination,
	            	'txid' => $sent_txid,
	            ]);
			} else {
	            EventLog::warning('distribution.assetSweepSkipped', [
	                'distributionId' => $distro->id,
	            	'asset' => $distro->asset,
	            	'btcBalance' => $balances['BTC']['confirmed']->getSatoshisString(),
	            ]);
			}
		}

		// sweep BTC

		// get all txos
		[$utxos, $btc_quantity] = $this->buildUtxosArray($distro, $wallet_uuid);
		$btc_quantity_minus_fee = $btc_quantity->subtract(CryptoQuantity::fromSatoshis($default_miner));

		// if too low, then don't try and send
		if ($btc_quantity_minus_fee->lt($default_miner)) {
			EventLog::info('distribution.noBTCSweep', [
			    'distributionId' => $distro->id,
				'btcBalance' => $btc_quantity->getSatoshisString(),
			]);

			$this->completeDistribution($distro);
			return true;
		}

		// send the specific txos (sweep)
		$send_parameters = [
			'requestId' => $distro->id.':sweep:BTC', // the requestId prevents duplicate transactions
			'txos' => $utxos,
		];
        $send_result = $substation->sendImmediatelyToSingleDestination($wallet_uuid, $distro->address_uuid, 'BTC', $btc_quantity_minus_fee, $sweep_destination, $send_parameters);
        $sent_txid = $send_result['txid'];

        EventLog::debug('distribution.sweepBtc', [
            'distributionId' => $distro->id,
        	'quantity' => $btc_quantity_minus_fee->getSatoshisString(),
        	'destination' => $sweep_destination,
        	'txid' => $sent_txid,
        ]);

		$this->completeDistribution($distro);
		return true;
	}
	
	protected function buildUtxosArray($distro, $wallet_uuid)
	{
		$quantity = CryptoQuantity::fromSatoshis(0);

		$substation = Substation::instance();
		try {
	    	$txos_info = $substation->getTXOsById($wallet_uuid, $distro->address_uuid);
	    	$txos = $txos_info['items'];
	    } catch(Exception $e) {
	    	EventLog::logError('getTxos.error', $e, [
	    	    'distributionId' => $distro->id,
	    	    'stage' => 'ConsolidateTxs',
	    	]);
	    	throw $e;
	    }
	    
	    $utxos = array();
	    foreach($txos as $utxo){
	    	if(!$utxo['spent']){
	    		$utxos[] = "{$utxo['txid']}:{$utxo['n']}";
	    		$quantity = $quantity->add(CryptoQuantity::fromSatoshis($utxo['amount']));
	    	}
	    }
	    return [$utxos, $quantity];
	}

	protected function completeDistribution($distribution)
	{
		EventLog::info('distribution.stageComplete', [
		    'distributionId' => $distribution->id,
		    'stage' => 'CompleteCleanup',
		]);

		$distribution->markComplete();
		return true;
	}
	
}
