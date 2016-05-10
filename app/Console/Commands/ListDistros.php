<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Models\Distribution as Distro;

class ListDistros extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:listDistros';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists each distribution in system';

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
        $get = Distro::orderBy('id', 'asc')->get();
        if(!$get OR count($get) == 0){
			$this->error('No distributions found');
			return false;
		}
		foreach($get as $row){
			$line = '#'.$row->id.' '.$row->deposit_address.' '.$row->asset;
			if(trim($row->label) != ''){
				$line .= ' "'.$row->label.'"';
			}
			if($row->complete == 1){
				$line .= ' COMPLETE';
			}
			elseif($row->hold == 1){
				$line .= ' HOLD';
			}
			else{
				$line .= ' Stage: '.$row->stage;
			}
			$this->info($line);
		}
    }
}
