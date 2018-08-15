<?php

namespace App\Libraries\EscrowWallet;

use Illuminate\Support\ServiceProvider;
use Tokenly\SubstationClient\SubstationClient;

class EscrowWalletServiceProvider extends ServiceProvider
{

    /**
     * @return void
     */
    public function boot()
    {
    }

    /**
     * @return void
     */
    public function register()
    {

        // bind the token escrow substation client
        $this->app->bind('substationclient.escrow', function ($app) {
            return new SubstationClient(
                env('ESCROW_SUBSTATION_CONNECTION_URL', env('SUBSTATION_CONNECTION_URL')),
                env('ESCROW_SUBSTATION_API_TOKEN', null),
                env('ESCROW_SUBSTATION_API_KEY', null)
            );
        });
    }

}
