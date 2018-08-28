<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

class DeclareQueues extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $config = app()['config'];

        // declare exchanges and queues for each connection
        $declarations = explode(',', env('RABBITMQ_QUEUE_DECLARATIONS', ''));
        foreach ($declarations as $raw_declaration) {
            $exp = explode(':', $raw_declaration);
            if(!isset($exp[1])){
                continue;
            }
            list($connection_name, $raw_queues) = $exp;

            // turn declarations on
            $config['queue.connections.' . $connection_name . '.options.exchange.declare'] = true;
            $config['queue.connections.' . $connection_name . '.options.queue.declare'] = true;
            $config['queue.connections.' . $connection_name . '.options.queue.bind'] = true;

            // declare each queue
            $queues = explode('|', $raw_queues);
            foreach ($queues as $queue) {
                Log::debug("declaring $connection_name $queue");
                $size = Queue::connection($connection_name)->size($queue);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
