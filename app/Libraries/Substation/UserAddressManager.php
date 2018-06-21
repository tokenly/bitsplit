<?php

namespace App\Libraries\Substation;

use App\Libraries\Substation\UserWalletManager;
use Tokenly\SubstationClient\SubstationClient;
use User;

class UserAddressManager
{

    public function __construct(UserWalletManager $user_wallet_manager, SubstationClient $substation_client)
    {
        $this->user_wallet_manager = $user_wallet_manager;
        $this->substation_client = $substation_client;
    }

    /**
     * Allocates an address for this user's wallet
     * returns:
     * {
     *   'address': '1AAAA9999xxxxxxxxxxxxxxxxxxxtA4f45',
     *   'uuid': '99dba108-bd65-45b6-adac-36fb74bc653d'
     * }
     * @param  User        $user owning user
     * @param  string|null $chain blockchain name
     * @return array new address details
     */
    public function newPaymentAddressForUser(User $user, string $chain = null)
    {
        // get the wallet
        $wallet_uuid = $this->user_wallet_manager->ensureSubstationWalletForUser($user, $chain);

        // allocate an address
        $new_address_details = $this->substation_client->allocateAddress($wallet_uuid);
        return $new_address_details;
    }

}
