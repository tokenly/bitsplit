<?php

namespace App\Libraries\Withdrawal;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tokenly\TokenpassClient\TokenpassAPI;
use User;

class RecipientWithdrawalManager
{

    function __construct(TokenpassAPI $tokenpass)
    {
        $this->tokenpass = $tokenpass;
    }


    public function getAddressesForUser(User $user)
    {
        $cache_key = 'addresses.user-'.$user['id'];
        $addresses = Cache::remember($cache_key, $_minutes=1, function() use ($user) {
            Log::debug("building addresses LIVE");
            return $this->buildAddressesForUser($user);
        });
        return $addresses;
    }

    protected function buildAddressesForUser(User $user)
    {
        $addresses = $this->tokenpass->getAddressesForAuthenticatedUser($user['oauth_token'], $_refresh=false);
        return $addresses;
    }

}
