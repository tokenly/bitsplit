<?php

namespace App\Distribute\Stages\Offchain;

use App\Distribute\Stages\Stage;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use Illuminate\Support\Facades\Log;
use Models\Distribution;
use Models\DistributionTx;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenpassClient\TokenpassAPI;

class DistributePromises extends Stage
{
    public function init()
    {
        parent::init();

        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $tokenpass = app(TokenpassAPI::class);

        $distribution = $this->distro;
        $escrow_address = $distribution->getEscrowAddress();

        // distribute the Tokenpass promises
        $distribution_txs = DistributionTx::where('distribution_id', $distribution->id)->get();
        if (!$distribution_txs or count($distribution_txs) == 0) {
            Log::error('No distribution addresses found for distro ' . $distribution->id);
            return null;
        }

        // creates each promise in tokenpass
        $promise_count = 0;
        $asset = $distribution->asset;
        $expiration = null;
        foreach ($distribution_txs as $distribution_tx) {
            if ($distribution_tx->promise_id !== null) {
                continue;
            }

            $quantity = CryptoQuantity::fromSatoshis($distribution_tx->quantity);
            $ref = 'disttx:' . $distribution_tx['id'];

            // TODO: try to get a provisional transaction by ref first here
            //   so we never create 2 provisional transactions for the same distribution_tx

            // call tokenpass
            $promise_response = $tokenpass->promiseTransaction($escrow_address['address'], $distribution_tx->destination, $asset, $quantity->getSatoshisString(), $expiration, $_txid = null, $_fingerprint = null, $ref);
            if (!$promise_response) {
                $error_string = $tokenpass->getErrorsAsString();
                $tokenpass->clearErrors();
                throw new Exception("Tokenpass promiseTransaction call failed: {$error_string}", 1);
            }
            $promise_id = $promise_response['promise_id'];

            // update the distribution_tx with the promise
            $distribution_tx->update([
                'promise_id' => $promise_id,
            ]);

            // also update the ledger entry with the promise id
            $dist_tx_id = $distribution_tx->id;
            $tx_identifier = 'disttx:' . $asset . ':' . $dist_tx_id;
            $ledger_entry = $ledger->findByTransactionIdentifierAndAddress($tx_identifier, $escrow_address);
            if ($ledger_entry) {
                if ($ledger_entry['promise_id'] === null) {
                    $ledger->update($ledger_entry, [
                        'promise_id' => $promise_id,
                    ]);
                } else {
                    EventLog::warning('ledger.promiseIdNotNull', [
                        'distributionId' => $distribution->id,
                        'txIdentifier' => $tx_identifier,
                        'existingPromiseId' => $ledger_entry['promise_id'],
                        'newPromiseId' => $promise_id,
                        'stage' => 'DistributePromises',
                    ]);
                }
            } else {
                EventLog::warning('ledger.notFound', [
                    'distributionId' => $distribution->id,
                    'txIdentifier' => $tx_identifier,
                    'stage' => 'DistributePromises',
                ]);
            }


            ++$promise_count;
        }

        EventLog::info('distribution.offchain.allocatedPromises', [
            'distributionId' => $distribution->id,
            'promiseCount' => $promise_count,
        ]);


        return $this->goToNextStage($distribution);
    }

    protected function goToNextStage(Distribution $distribution)
    {
        EventLog::info('distribution.offchain.stageComplete', [
            'distributionId' => $distribution->id,
            'stage' => 'DistributePromises',
        ]);

        $distribution->incrementStage();
        $distribution->sendWebhookUpdateNotification();

        return true;
    }

}
