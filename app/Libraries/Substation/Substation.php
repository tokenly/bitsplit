<?php

namespace App\Libraries\Substation;

use Tokenly\SubstationClient\SubstationClient;

class Substation
{

    public static function instance(): SubstationClient
    {
        return app(SubstationClient::class);
    }

    public static function chain(): string
    {
        $livenet = env('SUBSTATION_USE_LIVENET', true);
        if ($livenet) {
            return 'counterparty';
        } else {
            return 'counterpartyTestnet';
        }
    }

}
