<?php

namespace App\Libraries\Substation;

use App\Libraries\Substation\Substation;
use Exception;
use Tokenly\RecordLock\Facade\RecordLock;
use Tokenly\SubstationClient\SubstationClient;
use User;
use \App\Models\UserMeta;

class UserWalletManager
{

    public function __construct(SubstationClient $substation_client)
    {
        $this->substation_client = $substation_client;
    }

    public function ensureSubstationWalletForUser(User $user, string $chain = null)
    {
        if ($chain === null) {
            $chain = Substation::chain();
        }
        return RecordLock::acquireAndExecute('substationwallet.' . $user['id'], function () use ($user, $chain) {
            $wallet_uuid = $this->getSubstationWalletIdForUser($user, $chain);

            if (!$wallet_uuid) {
                // create a new wallet
                $name = ucfirst(env('APP_CODE', 'bitsplit')) . " User " . $user['username'];
                $notification_queue_name = env('RABBITMQ_SIGNAL_QUEUE', 'bitsplit');

                $wallet_info = $this->substation_client->createServerManagedWallet($chain, $name, $_unlock_phrase = null, $notification_queue_name);

                $wallet_uuid = $wallet_info['uuid'];

                // save the wallet
                $this->setSubstationWalletIdForUser($user, $chain, $wallet_uuid);
            }

            return $wallet_uuid;
        });
    }

    public function getSubstationWalletIdForUser(User $user, string $chain)
    {
        // ensure the blockchain name is valid - will throw an error if not
        $this->ensureChainIsValid($chain);

        return UserMeta::getMeta($user['id'], 'walletUuid.' . $chain);
    }

    public function setSubstationWalletIdForUser(User $user, string $chain, $wallet_uuid)
    {
        // ensure the blockchain name is valid - will throw an error if not
        $this->ensureChainIsValid($chain);

        return UserMeta::setMeta($user['id'], 'walletUuid.' . $chain, $wallet_uuid);
    }

    public function ensureChainIsValid(string $chain)
    {
        switch ($chain) {
            case 'counterparty':
            case 'counterpartyTestnet':
            case 'bitcoin':
            case 'bitcoinTestnet':
                return true;
        }

        throw new Exception("Invalid chain name: {$chain}", 1);
    }


}
