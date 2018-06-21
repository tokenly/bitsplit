<?php

namespace App\Jobs;

use App\Libraries\Substation\SubstationTransactionHandler;
use Exception;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\SignalClient\Jobs\SignalConsumerJob;

class SignalNotificationJob extends SignalConsumerJob
{

    // give up after 80 attempts
    protected $max_attempts = 80;

    // backoff
    protected $use_backoff = true;

    /**
     * Called when Signal sends a transaction notification to this application
     * @param  array $data The notification data
     * @return mixed null or reply data
     */
    public function handle($data)
    {
        // look for transaction events
        if ($data['eventType'] == 'transaction') {
            $payload = $data['payload'];
            if ($payload['event'] == 'send') {
                try {
                    // process the transaction
                    app(SubstationTransactionHandler::class)->handleSubstationTransactionPayload($payload);
                } catch (Exception $e) {
                    EventLog::logError('signal.processSendFailed', $e, ['chain' => $payload['chain'], 'txid' => $payload['txid'], 'confirmations' => $payload['confirmations']]);
                    throw $e;
                }
            }
        }

        // no reply
        return;
    }

}
