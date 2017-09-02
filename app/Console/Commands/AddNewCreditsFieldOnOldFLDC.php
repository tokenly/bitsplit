<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class AddNewCreditsFieldOnOldFLDC extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:addNewCreditsFieldOnOldFLDC';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a new field to our backup copy of the old FLDC database';

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
        $db = DB::connection('fldc');
        
        $tables = $db->select('SHOW TABLES');
        
        if(!$tables){
            $this->error('Error loading table list');
            return false;
        }
        
        foreach($tables as $tbl){
            $key = 'Tables_in_'.env('FLDC_DB_DATABASE');
            $name = $tbl->$key;
            try{
                Schema::table(env('FLDC_DB_DATABASE').'.'.$name, function (Blueprint $table) {
                    $table->bigInteger('new_credit')->default(0);
                });
                $this->info('updated '.$name);
            }
            catch(\Exception $e){
                $this->error('Error updating '.$name.': '.$e->getMessage());
                continue;
            }
        }
    }
}
