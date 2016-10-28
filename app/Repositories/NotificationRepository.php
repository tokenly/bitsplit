<?php

namespace App\Repositories;

use App\Models\Notification;
use Exception;
use Tokenly\LaravelApiProvider\Repositories\APIRepository;

/*
* NotificationRepository
*
* To send a webhook:
*   
* // make the payload
* $payload = [
*     'event'          => 'complete',
*     'notificationId' => null, // <-- this will be filled automatically
*     'otherData'      => 'foo',
* ];
* 
* // create a notification
* $user = Auth::user();
* $payload = app('App\Repositories\NotificationRepository')->completePayloadAndStoreNotification($payload, $user['id']);
* 
* // send the webhook
* $user = Auth::user();
* app('Tokenly\XcallerClient\Client')->sendWebhookWithReturn($payload, $user['webhook_url'], $payload['notificationId'], $user['apitoken'], $user['apisecretkey'], NotificationReturnJob::class);
* 
*/
class NotificationRepository extends APIRepository
{

    protected $model_type = 'App\Models\Notification';

    public function completePayloadAndStoreNotification($payload, $user_id=null) {
        $notification_model = $this->create([
            'user_id'      => $user_id,
            'status'       => Notification::STATUS_NEW,
            'notification' => $payload,
        ]);

        $notification_uuid = $notification_model['uuid'];

        $payload['notificationId'] = $notification_uuid;

        return $payload;
    }

}



