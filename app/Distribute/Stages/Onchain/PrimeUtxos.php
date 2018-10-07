<?php

namespace App\Distribute\Stages\Onchain;

use App\Distribute\Stages\Stage;
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserWalletManager;
use Config;
use DB;
use Exception;
use Illuminate\Support\Facades\Log;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;

class PrimeUtxos extends Stage
{

    public function init()
    {
        $distro = $this->distro;

        // initialize variables
        $addresses_count = $distro->addressCount();
        $max_txos_per_tx = Config::get('settings.max_tx_outputs');
        $fee_per_byte = $distro->fee_rate ?? Config::get('settings.miner_satoshi_per_byte');
        $xcp_tx_bytes = Config::get('settings.xcp_tx_bytes');
        $fee_per_delivery = ($xcp_tx_bytes * $fee_per_byte);

        // check if any primes are waiting on confirmations
        $unconf_primes = DB::table('distribution_primes')->where('distribution_id', $distro->id)->where('confirmed', 0)->get();
        if ($unconf_primes and count($unconf_primes) > 0) {
            Log::info('Waiting for distro #' . $distro->id . ' primes to confirm');
            return true;
        }

        // wait for fuel
        $pending_totals = $distro->pendingDepositTotals();
        if ($pending_totals['fuel'] > 0) {
            Log::info('Waiting for more fuel to confirm for distro #' . $distro->id . ' [priming]');
            return true;
        }

        // setup substation
        [$substation, $wallet_uuid] = $this->getSubstationAndWalletUuid();

        $delivery_primes_count = null;
        try {
            $substation_distribution = Substation::distributionClientInstance();
            $txo_info = $substation_distribution->loadTXOInfoFromSubstation($wallet_uuid, $distro->address_uuid, CryptoQuantity::fromSatoshis($fee_per_delivery));
            $delivery_primes_count = count($txo_info['primes']);
        } catch (Exception $e) {
            Log::error('Error checking primes for distro #' . $distro->id . ': ' . $e->getMessage());
            return false;
        }

        if ($delivery_primes_count >= $addresses_count) {
            $this->goToNextStage($distro);
            return true;
        }

        $txos_needed_count = $addresses_count;
        $missing_txos_count = $txos_needed_count - $delivery_primes_count;

        if ($missing_txos_count > $max_txos_per_tx) {
            // we need to use setup primes
            try {
                [$prime_setups, $any_sent] = $this->ensureSetupPrimes($distro, $missing_txos_count, $max_txos_per_tx, $fee_per_delivery);
                if ($any_sent) {
                    // we set a stage 1 priming transaction
                    //  we will wait until the next pass to proces stage 2
                    return true;
                }
            } catch (Exception $e) {
                EventLog::logError('primeUtxos.error', $e, [
                    'distributionId' => $distro->id,
                    'stage' => '1',
                ]);
                return false;
            }
        } else {
            // no setup primes needed
            $prime_setups = [
                [
                    'use_txos' => false,
                    'output_count' => $missing_txos_count,
                ],
            ];
        }

        // send stage 2 transactions
        foreach ($prime_setups as $prime_setup) {
            try {
                $this->sendStageTwoPrimeTransaction($distro, $prime_setup, $fee_per_delivery);
            } catch (Exception $e) {
                EventLog::logError('primeUtxos.error', $e, [
                    'distributionId' => $distro->id,
                    'stage' => '2',
                ]);
                return false;
            }
        }

        return true;
    }

    protected function ensureSetupPrimes($distro, $missing_txos_count, $max_txos_per_tx, $fee_per_delivery)
    {
        $fee_per_byte = $distro->fee_rate ?? Config::get('settings.miner_satoshi_per_byte');
        $output_size = Config::get('settings.average_txo_bytes');
        $base_tx_size = (Config::get('settings.tx_input_bytes') + Config::get('settings.tx_extra_bytes'));

        $prime_setup_count = intval(ceil($missing_txos_count / $max_txos_per_tx));
        $txos_per_setup_prime_count = intval(ceil($missing_txos_count / $prime_setup_count));

        $unassigned_txos_count = $missing_txos_count;
        $prime_setups = [];
        $txo_identifier_offset_by_prime_size = [];
        for ($setup_offset = 0; $setup_offset < $prime_setup_count; $setup_offset++) {
            $txos_count = $txos_per_setup_prime_count;
            if ($txos_count > $unassigned_txos_count) {
                $txos_count = $unassigned_txos_count;
            }
            if ($txos_count <= 0) {
                continue;
            }

            // how much BTC will be sent to the stage 2 primes
            $stage2_payload_size = $txos_count * $fee_per_delivery;
            $stage2_fees = (($txos_count * $output_size) + $base_tx_size) * $fee_per_byte;
            $setup_prime_size = $stage2_payload_size + $stage2_fees;

            // if no prime exists, create it
            $txo_identifier_offset = $txo_identifier_offset_by_prime_size[$setup_prime_size] ?? 0;
            $txo_identifier = $this->findSetupTXOBySize($distro, $setup_prime_size, $txo_identifier_offset);
            if ($txo_identifier) {
                $txo_identifier_offset_by_prime_size[$setup_prime_size] = $txo_identifier_offset + 1;
            }

            $looked_up_primes[] = [
                'use_txos' => true,
                'txos' => $txo_identifier ? [$txo_identifier] : null,
                'output_count' => $txos_count,
                'output_quantity' => $setup_prime_size,
            ];

            $unassigned_txos_count -= $txos_count;
        }

        // send the missing primes if there are any
        $missing_prime_quantities = [];
        foreach ($looked_up_primes as $prime_setup) {
            if ($prime_setup['txos'] === null) {
                $missing_prime_quantities[] = $prime_setup['output_quantity'];
            }
        }
        $send_result = null;
        if ($missing_prime_quantities) {
            $send_result = $this->sendStageOnePrimeTransaction($distro, $missing_prime_quantities);
        }
        $any_sent = $send_result ? true : false;

        // build the final prime setups
        $prime_setups = [];
        $new_prime_setup_txo_offset = 0;
        foreach ($looked_up_primes as $looked_up_prime) {
            $prime_setup = $looked_up_prime;
            unset($prime_setup['output_quantity']);
            if ($prime_setup['txos'] === null) {
                $txo_identifier = "{$send_result['txid']}:{$new_prime_setup_txo_offset}";
                $prime_setup['txos'] = [$txo_identifier];
                ++$new_prime_setup_txo_offset;
            }

            $prime_setups[] = $prime_setup;
        }

        return [$prime_setups, $any_sent];
    }

    protected function sendStageOnePrimeTransaction($distro, $quantities_sat)
    {
        $fee_per_byte = $distro->fee_rate ?? Config::get('settings.miner_satoshi_per_byte');

        $time = timestamp();
        $prime_send_result = null;
        $total_quantity_sat = 0;
        try {
            // build the destination
            $destinations = [];
            foreach ($quantities_sat as $quantity_sat) {
                $destinations[] = [
                    'address' => $distro->deposit_address,
                    'quantity' => CryptoQuantity::fromSatoshis($quantity_sat),
                ];

                $total_quantity_sat += $quantity_sat;
            }

            // send the stage 1 prime transaction
            [$substation, $wallet_uuid] = $this->getSubstationAndWalletUuid();
            $prime_send_result = $substation->sendImmediatelyToDestinations($wallet_uuid, $distro->address_uuid, 'BTC', $destinations, [
                // use the exact rate of satoshis/byte
                'feeRate' => (string)$fee_per_byte,
            ]);
            $fee_paid = intval($prime_send_result['feePaid']->getSatoshisString());
        } catch (Exception $e) {
            Log::error('Priming error distro ' . $distro->id . ': ' . $e->getMessage());
            throw $e;
        }

        $tx_data = [
            'created_at' => $time,
            'updated_at' => $time,
            'distribution_id' => $distro->id,
            'quantity' => $fee_paid + $total_quantity_sat,
            'txid' => $prime_send_result['txid'],
            'stage' => 1,
            'confirmed' => 0,
        ];
        DB::table('distribution_primes')->insert($tx_data);

        EventLog::debug('prime.stageOne', [
            'distributionId' => $distro->id,
            'count' => count($destinations),
            'txid' => $prime_send_result['txid'],
        ]);

        return $prime_send_result;
    }

    protected function sendStageTwoPrimeTransaction($distro, $prime_setup, $fee_per_delivery)
    {
        $time = timestamp();
        $prime_send_result = null;

        // build the destination
        $destinations = [];
        for ($dest_offset = 0; $dest_offset < $prime_setup['output_count']; $dest_offset++) {
            $destinations[] = [
                'address' => $distro->deposit_address,
                'quantity' => CryptoQuantity::fromSatoshis($fee_per_delivery),
            ];
        }

        if ($prime_setup['use_txos']) {
            // use specific txos from a prime setup
            $send_parameters = [
                'txos' => $prime_setup['txos'],
            ];
        } else {
            // use a fee rate
            $fee_per_byte = $distro->fee_rate ?? Config::get('settings.miner_satoshi_per_byte');
            $send_parameters = [
                'feeRate' => (string)$fee_per_byte,
            ];
        }
        [$substation, $wallet_uuid] = $this->getSubstationAndWalletUuid();
        $prime_send_result = $substation->sendImmediatelyToDestinations($wallet_uuid, $distro->address_uuid, 'BTC', $destinations, $send_parameters);
        $fee_paid = intval($prime_send_result['feePaid']->getSatoshisString());

        $tx_data = [
            'created_at' => $time,
            'updated_at' => $time,
            'distribution_id' => $distro->id,
            'quantity' => $fee_paid + ($fee_per_delivery * $prime_setup['output_count']),
            'txid' => $prime_send_result['txid'],
            'stage' => 2,
            'confirmed' => 0,
        ];
        DB::table('distribution_primes')->insert($tx_data);

        EventLog::debug('prime.stageTwo', [
            'distributionId' => $distro->id,
            'txid' => $prime_send_result['txid'],
            'count' => count($destinations),
        ]);

        return $prime_send_result;
    }

    protected function findSetupTXOBySize($distro, $setup_prime_size, $offset)
    {
        [$substation, $wallet_uuid] = $this->getSubstationAndWalletUuid();
        $substation_distribution = Substation::distributionClientInstance();
        $txo_info = $substation_distribution->loadTXOInfoFromSubstation($wallet_uuid, $distro->address_uuid, CryptoQuantity::fromSatoshis($setup_prime_size));
        $txos = $txo_info['primes'];
        if ($txos and isset($txos[$offset])) {
            $txo = $txos[$offset];
            return "{$txo['txid']}:{$txo['n']}";
        }
        return null;
    }

    protected function goToNextStage($distribution)
    {
        EventLog::info('distribution.stageComplete', [
            'distributionId' => $distribution->id,
            'stage' => 'PrimeUtxos',
        ]);

        $distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();
        return true;

    }

    protected function getSubstationAndWalletUuid()
    {
        if (!isset($this->_substation_vars)) {
            $user = User::find($this->distro->user_id);
            $wallet_uuid = app(UserWalletManager::class)->ensureSubstationWalletForUser($user);
            $this->_substation_vars = [Substation::instance(), $wallet_uuid];
        }
        return $this->_substation_vars;
    }
}
