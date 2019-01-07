<?php

namespace App\Console\Commands;

use Distribute\Processor;
use Illuminate\Console\Command;
use Models\Distribution;

class Distribute extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitsplit:distribute {id?}';

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
        $id = $this->argument('id');
        $distro = null;
        if ($id) {
            $distro = Distribution::find($id);
            if (!$distro) {
                $this->error('Distribution not found');
                return false;
            }
        }

        $processor = new Processor;
        try {
            if ($distro) {
                $do = $processor->processStage($distro);
            } else {
                $do = $processor->processDistributions();
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return false;
        }
        if ($do) {
            $this->info('...done');
        }
        return true;
    }
}
