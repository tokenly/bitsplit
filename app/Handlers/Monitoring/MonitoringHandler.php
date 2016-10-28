<?php

namespace App\Handlers\Monitoring;

use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tokenly\ConsulHealthDaemon\ServicesChecker;

/**
 * This is invoked regularly to monitor connections
 */
class MonitoringHandler {

    public function __construct(ServicesChecker $services_checker) {
        $this->services_checker = $services_checker;
    }

    public function handleConsoleHealthCheck() {
        // check MySQL
        $this->services_checker->checkMySQLConnection();

        // check that pending queue sizes aren't too big
        $this->services_checker->checkQueueSizes([
            'notifications_out'    => 15,
            'notifications_return' => 30,
        ]);

        // check queue velocities
        // $this->services_checker->checkTotalQueueJobsVelocity([
        //     'notifications_out'    => [1,  '2 hours'],
        //     'notifications_return' => [1,  '2 hours'],
        // ]);

    }

    ////////////////////////////////////////////////////////////////////////
    ////////////////////////////////////////////////////////////////////////
    // Checks
    
}
