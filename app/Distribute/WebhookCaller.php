<?php

namespace App\Distribute;

use App\Jobs\NotificationReturnJob;
use App\Repositories\NotificationRepository;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\XcallerClient\Client as XCallerClient;
use Exception;
use User;
use UserMeta;

class WebhookCaller {
    
    function __construct(NotificationRepository $notification_repository, XCallerClient $xcaller_client) {
        $this->notification_repository = $notification_repository;
        $this->xcaller_client          = $xcaller_client;
    }

    public function sendWebhook($event_type, User $user, $webhook_url, $payload_vars=[]) {
        EventLog::warning('webhook.disabled', [
            'event' => $event_type,
            'userId' => $user['id'],
        ]);
        return;

        try {
            // get api keys
            $api_key_model = $user->firstActiveAPIKey();
            if (!$api_key_model) {
                EventLog::warning('webhook.noActiveApiKey', ['event' => $event_type, 'userId' => $user['id']]);
                return;
            }
            $apitoken     = $api_key_model['client_key'];
            $apisecretkey = $api_key_model['client_secret'];

            // create the payload and notification model
            $payload = array_merge([
                'event'          => $event_type,
                'notificationId' => null, // to be filled in
            ], $payload_vars);
            $payload = $this->notification_repository->completePayloadAndStoreNotification($payload, $user['id']);
            
            // send the webhook
            $this->xcaller_client->sendWebhookWithReturn($payload, $webhook_url, $payload['notificationId'], $apitoken, $apisecretkey, NotificationReturnJob::class);
        } catch (Exception $e) {
            EventLog::error('webhook.error', $e, ['event' => $event_type, 'userId' => $user['id']]);
        }

    }    

}
