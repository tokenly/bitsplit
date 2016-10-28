<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro;

class ResendWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:resend-webhook {address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends out a new webhook notification for a distribution';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $address = $this->argument('address');
        $get = Distro::where('deposit_address', $address)->first();
        if(!$get){
            $get = Distro::where('id', intval($address))->orWhere('uuid', $address)->first();
        }
        if(!$get){
            $this->error('Distribution not found');
            return false;
        }
        $get->sendWebhookUpdateNotification();
        $this->info('Notification sent');
    }
}
