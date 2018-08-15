<?php

use App\Jobs\SignalNotificationJob;
use Ramsey\Uuid\Uuid;

/**
 *
 */
class SignalNotificationJobHelper
{

    public function __construct()
    {
    }

    public function fireJob($parsed_transaction_data_or_callback = null, $chain = null, $attempts = 1)
    {
        return $this->processJob($parsed_transaction_data_or_callback, $chain, $_fire_job = true, $attempts);
    }

    public function processJob($parsed_transaction_data_or_callback = null, $chain = null, $fire_job = false, $attempts = 1)
    {
        if ($chain === null) {
            $chain = 'bitcoin';
        }

        if ($chain == 'bitcoin') {
            $parsed_transaction_data = $this->buildSampleParsedTransaction();
        } else if ($chain == 'counterparty') {
            $parsed_transaction_data = $this->buildSampleParsedCounterpartyTransaction();
        }

        if (is_callable($parsed_transaction_data_or_callback)) {
            $parsed_transaction_data = $parsed_transaction_data_or_callback($parsed_transaction_data);
        } else if ($parsed_transaction_data_or_callback) {
            $parsed_transaction_data = $parsed_transaction_data_or_callback;
        }

        // set confirmed flag
        $parsed_transaction_data['confirmed'] = ($parsed_transaction_data['confirmations'] > 0);

        $job = new SignalNotificationJob();
        $data = $this->mockJobData($parsed_transaction_data);

        if ($fire_job) {
            // fire the job
            $queue_job = new MockQueueJob();
            $queue_job->setMockAttempts($attempts);
            $job->fire($queue_job, $data);
        } else {
            // just process
            return $job->handle($data);
        }
    }

    public function mockJobData($parsed_transaction_data)
    {
        return [
            'uuid' => Uuid::uuid4()->toString(),
            'eventType' => 'transaction',
            'payload' => $parsed_transaction_data,
        ];
    }

    public function buildSampleParsedTransaction(string $recipient_address = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j', string $sending_address = '1AAAA9999xxxxxxxxxxxxxxxxxxxtA4f45', string $asset = 'BTC')
    {

        return [
            'event' => 'send',
            'chain' => 'bitcoin',
            'debits' => [
                0 => [
                    'address' => $sending_address,
                    'asset' => $asset,
                    'quantity' => '120000',
                ],
            ],
            'credits' => [
                0 => [
                    'address' => $sending_address,
                    'asset' => $asset,
                    'quantity' => '900000',
                ],
                1 => [
                    'address' => $recipient_address,
                    'asset' => $asset,
                    'quantity' => '100000',
                ],
            ],
            'fees' => [
                0 => [
                    'asset' => 'BTC',
                    'quantity' => '20000',
                ],
            ],
            'txid' => '0000000000000000000000000000000000000000000000000000000000001001',
            'confirmations' => 4,
            'confirmationFinality' => 6,
            'confirmed' => true,
            'final' => false,
            'confirmationTime' => '2018-01-06T14:52:50+00:00',
            'seenTime' => '2018-01-10T17:37:41+00:00',
        ];
    }

    public function buildSampleParsedCounterpartyTransaction(string $recipient_address = '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j', string $sending_address = '1AAAA9999xxxxxxxxxxxxxxxxxxxtA4f45', string $asset = 'XCP')
    {

        return [
            'event' => 'send',
            'chain' => 'counterparty',
            'debits' => [
                0 => [
                    'address' => $sending_address,
                    'asset' => $asset,
                    'quantity' => '500000000',
                ],
            ],
            'credits' => [
                0 => [
                    'address' => $recipient_address,
                    'asset' => $asset,
                    'quantity' => '500000000',
                ],
            ],
            'fees' => [
                0 => [
                    'asset' => 'BTC',
                    'quantity' => '20000',
                ],
            ],
            'txid' => '0000000000000000000000000000000000000000000000000000000000001001',
            'confirmations' => 4,
            'confirmationFinality' => 6,
            'confirmed' => true,
            'final' => false,
            'confirmationTime' => '2018-01-06T14:52:50+00:00',
            'seenTime' => '2018-01-10T17:37:41+00:00',
        ];
    }

}
