<?php

namespace App\Libraries\EscrowWallet;

use App\Repositories\EscrowAddressRepository;
use App\Repositories\EscrowWalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tokenly\RecordLock\Facade\RecordLock;
use Tokenly\TokenpassClient\TokenpassAPI;
use User;

/**
 * EscrowWalletGenerator
 */
class EscrowWalletManager
{

    function __construct(EscrowWalletRepository $wallet_repository, EscrowAddressRepository $address_repository, TokenpassAPI $tokenpass_client)
    {
        $this->wallet_repository = $wallet_repository;
        $this->address_repository = $address_repository;
        $this->tokenpass_client = $tokenpass_client;
    }

    public function generateEscrowAddressForUser(User $user, string $recovery_address, string $chain)
    {
        // ensure a wallet
        $wallet = $this->ensureSubstationWalletForUser($user, $chain);

        // allocate the addresses
        DB::transaction(function () use ($wallet, $user, $recovery_address) {
            // use privileged substation client
            $substation_client = app('substationclient.escrow');

            // allocate an automatic address from substation
            $address_info = $substation_client->allocateAddress($wallet['uuid']);
            $address_attributes = [
                'uuid' => $address_info['uuid'],
                'address' => $address_info['address'],
                'recovery_address' => $recovery_address,
                'offset' => $address_info['index'],
            ];
            $address = $this->address_repository->createAddress($wallet, $user, $address_attributes);

            // make sure the address is whitelisted with tokenpass
            $this->ensureWhitelistedSourceAddress($address_attributes['address']);
        });

        // send a websocket notification
        // event(new EscrowWalletsUpdated($user));
    }

    public function ensureSubstationWalletForUser(User $user, string $chain)
    {

        return RecordLock::acquireAndExecute('substationwallet.' . $user['id'], function () use ($user, $chain) {
            $wallet = $this->getWalletForUser($user, $chain);

            if (is_null($wallet)) {
                // create a new wallet
                $name = "Merchant Wallet for " . $user['username'];
                $notification_queue_name = env('SIGNAL_NOTIFICATION_QUEUE', 'tokenmarkets');

                $substation_client = $this->getSubstationClient();
                $wallet_info = $substation_client->createServerManagedWallet($chain, $name, $_unlock_phrase = null, $notification_queue_name);
                $substation_wallet_uuid = $wallet_info['uuid'];

                // save the wallet
                $wallet = $this->createWalletForUser($user, $chain, $substation_wallet_uuid);
            }

            return $wallet;
        });
    }

    public function getWalletForUser(User $user, string $chain)
    {
        $wallet = $this->wallet_repository->findByUser($user, $chain);
        if (!$wallet) {
            return null;
        }

        return $wallet;
    }

    public function createWalletForUser(User $user, string $chain, string $wallet_uuid)
    {
        $wallet = $this->wallet_repository->create([
            'uuid' => $wallet_uuid,
            'chain' => $chain,
            'user_id' => $user['id'],
        ]);

        return $wallet;
    }

    public function ensureWhitelistedSourceAddress($source_address)
    {
        $all_registered_source_addresses_map = $this->getMapOfAllRegisteredSourceAddresses();

        if (isset($all_registered_source_addresses_map[$source_address])) {
            Log::debug("Address $source_address was already registered.");
            return;
        }

        // register a new address
        $success = $this->tokenpass_client->registerProvisionalSource($source_address, null);
        if (!$success) {
            throw new Exception("Failed to register source address", 1);
        }
        Log::debug("Registered address $source_address.");
    }

    protected function getSubstationClient()
    {
        return app('substationclient.escrow');
    }

    protected function getMapOfAllRegisteredSourceAddresses()
    {
        $all_registered_source_addresses_objects = $this->tokenpass_client->getProvisionalSourceList();
        $all_registered_source_addresses_map = [];
        foreach ($all_registered_source_addresses_objects as $source_addresses_object) {
            $all_registered_source_addresses_map[$source_addresses_object['address']] = true;
        }
        return $all_registered_source_addresses_map;
    }


}