<?php

namespace App\Libraries\Substation;

use Tokenly\CryptoQuantity\CryptoQuantity;
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

    /**
     * Builds the approrpiate cryptoquantity object for the given quantity
     * @param  string|BlockchainInterface $chain Chain object or type string
     * @param  string|array|CryptoQuantity $quantity The quantity string, serialized array or object
     * @return mixed The CryptoQuantity for the chain
     */
    public static function buildCryptoQuantity($chain, $quantity)
    {
        // don't build a quantity for null
        if ($quantity === null) {
            return null;
        }

        // get the class
        $quantity_class = CryptoQuantity::class;

        // convert to the correct class
        if ($quantity instanceof CryptoQuantity) {
            return call_user_func([$quantity_class, 'fromCryptoQuantity'], $quantity);
        }

        // from serialized
        if (is_array($quantity)) {
            return call_user_func([$quantity_class, 'unserialize'], $quantity);
        }

        // from satoshis
        return call_user_func([$quantity_class, 'fromSatoshis'], $quantity);
    }

}
