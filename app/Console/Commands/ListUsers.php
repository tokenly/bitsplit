<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use User, Log;

class ListUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:listUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Shows quick list of all users in system';

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
        $users = User::all();
        if(!$users){
            $this->error('No users found..');
            return false;
        }
        foreach($users as $user){
            $this->info('#'.$user->id.' - '.$user->username.' - '.$user->email);
        }
        $this->info('...done');
    }
}
