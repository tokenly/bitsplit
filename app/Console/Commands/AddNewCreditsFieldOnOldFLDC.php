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
            $table_name = env('FLDC_DB_DATABASE').'.'.$name;
            try{
                if(!Schema::hasColumn($table_name, 'new_credit')){
                    //add new column
                    Schema::table($table_name, function (Blueprint $table) {
                        $table->bigInteger('new_credit')->default(0);
                    });
                }
                else{
                    //reset values to 0
                    DB::table($table_name)->update(array('new_credit' => 0));
                }
                $this->info('updated '.$name);
            }
            catch(\Exception $e){
                try{
                    $update = DB::table($table_name)->update(array('new_credit' => 0));
                    $this->info('reset '.$name);
                }
                catch(\Exception $e){
                    $this->error('Error updating '.$name.': '.$e->getMessage());
                }
                continue;
            }
        }
    }
}
