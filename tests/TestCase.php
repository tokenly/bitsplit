<?php

abstract class TestCase extends Illuminate\Foundation\Testing\TestCase
{

    protected $use_database = false;

    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';


    public function setUp(): void
    {
        parent::setUp();

        // bind the test case so we can typehint TestCase
        //   and have access to this instance
        app()->instance(TestCase::class, $this);

        if ($this->use_database) { $this->setUpDb(); }
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function setUpDb()
    {
         // migrate the wallets DB
        $result = $this->app['Illuminate\Contracts\Console\Kernel']->call('migrate', [
            '--path' => 'database/walletdb_migrations',
            '--database' => $this->app['config']['database.wallets'],
        ]);

        // migrate the regular DB
        $result = $this->app['Illuminate\Contracts\Console\Kernel']->call('migrate', [
            '--database' => $this->app['config']['database.main'],
        ]);

    }

}
