<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Distribute\Processor;


class Distribute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'distribute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the distributor processor';

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
        $processor = new Processor;
        try{
			$do = $processor->processDistributions();
		}
		catch(\Exception $e){
			$this->error($e->getMessage());
			return false;
		}
		if($do){
			$this->info('...done');
		}
        return true;
    }
}
