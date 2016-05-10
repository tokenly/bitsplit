<?php

namespace App\Console\Commands;

use Illuminate\Console\Command, Exception;

class NewAddress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:newAddress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a new address from xchain';

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
        $xchain = xchain();
        try{
			$address = $xchain->newPaymentAddress();
			dd($address);
		}
		catch(Exception $e){
			$this->error($e->getMessage());
			return false;
		}
    }
}
