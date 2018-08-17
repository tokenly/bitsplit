<?php

use App\Libraries\EscrowWallet\EscrowWalletManager;
use App\Repositories\EscrowAddressRepository;
use PHPUnit\Framework\Assert as PHPUnit;

class EscrowWalletAddressHelper
{

    public function generateNewEscrowWalletAddress(User $user, string $recovery_address = null, $chain = null)
    {
        $recovery_address = $recovery_address ?? 'mwoYfKbpGY264KpvSzgxDF1tPT66K8snqu';
        $chain = $chain ?? 'counterpartyTestnet';

        // generate the address now
        app(EscrowWalletManager::class)->generateEscrowAddressForUser($user, $recovery_address, $chain);

        // get the most recent address
        $addresses = app(EscrowAddressRepository::class)->findAllByUser($user, $chain)->slice(-1);
        PHPUnit::assertNotEmpty($addresses);
        return $addresses[0];
    }

}
