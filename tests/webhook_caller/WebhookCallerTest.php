<?php

use PHPUnit_Framework_Assert as PHPUnit;


class WebhookCallerTest extends TestCase
{

    protected $use_database = true;

    public function testWebhookCaller() {
        $getXCallerNotifications_fn = app('XCallerHelper')->mockXCallerClient(1);
        $user = app('UserHelper')->newRandomUser();

        // send a fake webhook
        $webhook_caller = app('App\Distribute\WebhookCaller');
        $webhook_caller->sendWebhook('fooevent', $user, ['foo' => 'bar',]);

        // check the sent notifications
        $xcaller_notifications = $getXCallerNotifications_fn();
        PHPUnit::assertCount(1, $xcaller_notifications);
        $xcaller_notification = $xcaller_notifications[0];
        PHPUnit::assertEquals('http://user.app/webhook', $xcaller_notification['meta']['endpoint']);
        PHPUnit::assertEquals($user->firstActiveAPIKey()['client_key'], $xcaller_notification['meta']['apiToken']);
        $payload = json_decode($xcaller_notification['payload'], true);
        PHPUnit::assertEquals('bar', $payload['foo']);

    }

}
