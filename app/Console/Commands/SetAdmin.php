<?php

namespace App\Console\Commands;

use Illuminate\Console\Command, User, Log;

class SetAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:setAdmin {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Toggles a users admin abilities';

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
        $id = $this->argument('id');
        $user = User::where('id', $id)->orWhere('username', $id)->first();
        if(!$user){
            $this->error('User not found');
            return false;
        }
        if($user->admin == 1){
            $user->admin = 0;
            $message = $user->username.' is no longer an admin';
        }
        else{
            $user->admin = 1;
            $message = $user->username.' now has admin privileges';
        }
        $save = $user->save();
        if(!$save){
            $this->error('Error setting admin privileges for '.$user->username);
            return false;
        }
        Log::info($message);
        $this->info($message);
        return true;
    }
}
