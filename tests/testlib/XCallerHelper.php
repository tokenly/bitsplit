<?php

use Illuminate\Support\Facades\Log;
use Tokenly\XcallerClient\Client as XCallerClient;

/*
* XCallerHelper
*/
class XCallerHelper
{

    protected $xcaller_notifications = [];

    public function __construct() {
    }


    /**
     * mocks xcaller client
     * @param  integer $queue_count expected calls count
     * @return function  a function which returns an array of ougoing xcaller notifications
     */
    public function mockXCallerClient($queue_count=1) {
        // use an ArrayObject because objects always pass by reference
        $xcaller_notifications = new ArrayObject();

        // mock the xcaller client
        $mock_xcaller_client = Mockery::mock(XCallerClient::class, [Config::get('xcaller-client.queue_connection'), Config::get('xcaller-client.queue_name'), app('Illuminate\Queue\QueueManager')]);
        $mock_xcaller_client->makePartial();
        $mock_xcaller_client->shouldReceive('_loadNotificationIntoQueue')->times($queue_count)->andReturnUsing(function($notification_entry) use ($xcaller_notifications) {
            $xcaller_notifications[] = $notification_entry;
        });
        app()->instance(XCallerClient::class, $mock_xcaller_client);

        // returns a function to get the xcaller calls
        return function() use ($xcaller_notifications) {
            return $xcaller_notifications->getArrayCopy();
        };
    }

}
