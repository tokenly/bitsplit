<?php

namespace App\Libraries\Withdrawal;

use App\Libraries\EscrowWallet\EscrowWalletManager;
use App\Libraries\Substation\Substation;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\TokenmapClient\TokenmapClient;

class WithdrawalFeeManager
{

    public function __construct(UserRepository $user_repository, EscrowWalletManager $escrow_wallet_manager, TokenmapClient $tokenmap_client)
    {
        $this->user_repository = $user_repository;
        $this->escrow_wallet_manager = $escrow_wallet_manager;
        $this->tokenmap_client = $tokenmap_client;
    }

    public function rebuildLatestFeeQuoteCaches()
    {
        $cache_key = 'withdrawal.fee.'.Substation::chain();
        Cache::put($cache_key, $this->buildBTCWithdrawalFee(), $_minutes = 30);
        $cache_key = 'fldc.btc.quote';
        Cache::put($cache_key, $this->buildFLDCQuote(), $_minutes = 30);
    }

    public function getLiveFeeQuote($round_up = true)
    {
        $this->rebuildLatestFeeQuoteCaches();
        return $this->getLatestFeeQuote($round_up);
    }

    public function getLatestFeeQuote($round_up = true)
    {
        $cache_key = 'withdrawal.fee.'.Substation::chain();
        $btc_fee = Cache::get($cache_key, null);
        if ($btc_fee === null) {
            // conservative default
            $btc_fee = CryptoQuantity::fromFloat(0.001);
        }

        $cache_key = 'fldc.btc.quote';
        $btc_quantity_for_fldc = Cache::get($cache_key, null);
        if ($btc_quantity_for_fldc === null) {
            // super conservative default
            $btc_quantity_for_fldc = CryptoQuantity::fromSatoshis(10);
        }

        $fldc_cost = $btc_fee->multiply(pow(10, $btc_fee->getPrecision()))->divideAndRound($btc_quantity_for_fldc, $round_up = true);

        // round up
        if ($round_up) {
            $fldc_cost = CryptoQuantity::fromFloat(ceil($fldc_cost->getFloatValue()));
        }

        return $fldc_cost;
    }

    public function buildBTCWithdrawalFee()
    {
        $owner = $this->user_repository->findEscrowWalletOwner();
        $escrow_address = $this->escrow_wallet_manager->getEscrowAddressForUser($owner, Substation::chain());

        $wallet = $escrow_address->escrowWallet;

        // sample quantity and address
        $asset = FLDCAssetName();
        $sample_quantity = CryptoQuantity::fromFloat(100);
        if (Substation::chain() == 'counterpartyTestnet') {
            $destination_address = 'mszKvXQgvN3Dv8ifidzb5tpa6oRpUZd2Mt';
        } else {
            $destination_address = '1AAAA9999xxxxxxxxxxxxxxxxxxxtA4f45';
        }

        $substation_client = $this->getSubstationClient();
        $send_parameters = [];
        // Log::debug("BEGIN buildBTCWithdrawalFee");
        $fee = $substation_client->estimateFeeForSendToSingleDestination($wallet['uuid'], $escrow_address['uuid'], $asset, $sample_quantity, $destination_address, $send_parameters);
        // Log::debug("END buildBTCWithdrawalFee \$fee=" . json_encode($fee, 192));
        return $fee;
    }

    public function buildFLDCQuote()
    {
        $tokenmap_client = app(TokenmapClient::class);
        $asset = 'FLDC';
        $btc_quantity = $this->tokenmap_client->getSimpleQuote('BTC', $asset, 'counterparty');
        return $btc_quantity;
    }

    protected function getSubstationClient()
    {
        return app('substationclient.escrow');
    }

}
