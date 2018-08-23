<?php

namespace App\Libraries\Withdrawal;

use App\Repositories\EscrowAddressLedgerEntryRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tokenly\TokenpassClient\TokenpassAPI;
use User;

class RecipientWithdrawalManager
{

    public function __construct(TokenpassAPI $tokenpass, EscrowAddressLedgerEntryRepository $ledger)
    {
        $this->tokenpass = $tokenpass;
        $this->ledger = $ledger;
    }

    public function getAddressesForUser(User $user)
    {
        $cache_key = 'addresses.user:' . $user['id'];
        $addresses = Cache::remember($cache_key, $_minutes = 1, function () use ($user) {
            return $this->buildAddressesForUser($user);
        });
        return $addresses;
    }

    public function getAddressesForUserWithBalances(User $user)
    {
        $addresses = $this->getAddressesForUser($user);
        return $this->addLedgerBalancesToAddresses($addresses);
    }

    // public function getPromisedBalanceForAddress($address, $asset)
    // {
    //     $transaction_list = $this->tokenpass->getPromisedTransactionList($address);
    // }

    public function getPromisedBalanceForUser(User $user, $address, $asset)
    {
        $addresses = collect($this->getAddressesForUserWithBalances($user))->keyBy('address');
        if (!isset($addresses[$address])) {
            // unknown address
            return null;
        }

        return $addresses[$address]['balances'][$asset] ?? 0;
    }

    // public function getTotalQuantityAndTokenpassPromiseIDs()
    // {
        
    // }

    protected function buildAddressesForUser(User $user)
    {
        $addresses = $this->tokenpass->getAddressesForAuthenticatedUser($user['oauth_token'], $_refresh = false);
        return $addresses;
    }

    protected function addLedgerBalancesToAddresses($addresses)
    {
        $output = [];
        foreach($addresses as $address) {
            // $raw_balances = $address['balances'];
            $address['balances'] = $this->ledger->foreignEntityBalancesByAsset($address, $_confirmed_only = false);
            $output[] = $address;
        }

        return $output;
    }
}
