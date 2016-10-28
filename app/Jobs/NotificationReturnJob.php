<?php

namespace App\Jobs;

use App\Jobs\RetryingJob;
use App\Models\Notification;
use App\Repositories\NotificationRepository;
use Illuminate\Support\Facades\Event;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Exception;

/*
* NotificationReturnJob
*/
class NotificationReturnJob extends RetryingJob
{
    public function __construct(NotificationRepository $notification_repository)
    {
        $this->notification_repository = $notification_repository;
    }

    public function fireJob($job, $data) {
        $notification = $this->notification_repository->updateByUuid($data['meta']['id'], [
            'returned' => new \DateTime(),
            'status'   => ($data['return']['success'] ? Notification::STATUS_SUCCESS : Notification::STATUS_FAILURE),
            'error'    => $data['return']['error'],
            'attempts' => $data['return']['totalAttempts'],
        ]);

        // fire an event
        // Event::fire('notification.returned', [$notification]);

        return true;
    }


}
