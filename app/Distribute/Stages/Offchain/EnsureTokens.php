<?php

namespace App\Distribute\Stages\Offchain;

use App\Distribute\Stages\Stage;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use Models\Distribution;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;

class EnsureTokens extends Stage
{
    public function init()
    {
        parent::init();
        $distribution = $this->distro;

        // make sure there is enough FLDC available in the escrow address
        $escrow_address = $distribution->getEscrowAddress();
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $asset = $distribution->asset;
        $balance = $ledger->addressBalance($escrow_address, $asset);

        // make sure the balance is greater than what is needed
        if ($balance->lt(CryptoQuantity::fromSatoshis($distribution->asset_total))) {
            EventLog::error('distribution.offchain.balanceInsufficient', [
                'distributionId' => $distribution->id,
                'stage' => 'EnsureTokens',
                'asset' => $asset,
                'balance' => $balance->getSatoshisString(),
                'required' => $distribution->asset_total,
            ]);
            return false;
        }

        return $this->goToNextStage($distribution);
    }

    protected function goToNextStage(Distribution $distribution)
    {
        EventLog::info('distribution.offchain.stageComplete', [
            'distributionId' => $distribution->id,
            'stage' => 'EnsureTokens',
        ]);

        $distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();

        return true;
    }

}
