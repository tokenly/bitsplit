<?php

namespace App\Distribute\Stages\Offchain;

use App\Distribute\Stages\Stage;
use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Models\Distribution;
use Models\DistributionTx;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;

class AllocatePromises extends Stage
{
    public function init()
    {
        parent::init();

        $distribution = $this->distro;

        // allocate all promises in the ledger
        DB::transaction(function () use ($distribution) {
            $ledger = app(EscrowAddressLedgerEntryRepository::class);

            $escrow_address = $distribution->getEscrowAddress();

            // allocate all promises in one database transaction
            $distribution_txs = DistributionTx::where('distribution_id', $distribution->id)->get();
            if (!$distribution_txs or count($distribution_txs) == 0) {
                Log::error('No distribution addresses found for distro ' . $distribution->id);
                return null;
            }

            // allocate each promise debit in the ledger
            $asset = $distribution->asset;
            $promise_count = 0;
            foreach ($distribution_txs as $distribution_tx) {
                $quantity = CryptoQuantity::fromSatoshis($distribution_tx->quantity);
                $dist_tx_id = $distribution_tx->id;
                $tx_identifier = 'disttx:' . $asset . ':' . $dist_tx_id;
                $txid = 'disttx:' . $dist_tx_id;
                $destination = $distribution_tx->destination;
                $ledger->debit($escrow_address, $quantity, $asset, EscrowAddressLedgerEntry::TYPE_PROMISE_CREATED, $txid, $tx_identifier, $_confirmed = true, $_promise_id = null, $destination);
                ++$promise_count;
            }

            // after debiting everything, make sure the FLDC available is not below zero in the escrow address
            $balance = $ledger->addressBalance($escrow_address, $asset);

            // make sure the balance is greater than what is needed
            if ($balance->lt(0)) {
                EventLog::error('distribution.offchain.balanceBelowZero', [
                    'distributionId' => $distribution->id,
                    'asset' => $asset,
                    'balance' => $balance->getSatoshisString(),
                    'required' => $distribution->asset_total,
                ]);
                throw new Exception("Failed to allocate promises to ledger", 1);
            }

            EventLog::info('distribution.offchain.allocatedPromises', [
                'distributionId' => $distribution->id,
                'promiseCount' => $promise_count,
            ]);

        });


        return $this->goToNextStage($distribution);
    }


    protected function goToNextStage(Distribution $distribution)
    {
        EventLog::info('distribution.offchain.stageComplete', [
            'distributionId' => $distribution->id,
            'stage' => 'AllocatePromises',
        ]);

        $distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();

        return true;
    }

}
