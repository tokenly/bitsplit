<?php

use Distribute\Initialize;
use Models\Distribution;
use Models\DistributionTx;
use Models\Fuel;
use Ramsey\Uuid\Uuid;
use Tokenly\SubstationClient\Mock\MockSubstationClient;

/**
 *  DistributionHelper
 */
class DistributionHelper
{

    public function newDistribution(User $user = null, $is_offchain = false)
    {
        if ($user === null) {
            $user = app('UserHelper')->newRandomUser();
        }

        $deposit_address = MockSubstationClient::sampleAddress('bitcoin', 0);

        $address_list = [
            ['address' => '1AAAA1111xxxxxxxxxxxxxxxxxxy43CZ9j', 'amount' => 100000000],
            ['address' => '1AAAA2222xxxxxxxxxxxxxxxxxxy4pQ3tU', 'amount' => 200000000],
            ['address' => '1AAAA3333xxxxxxxxxxxxxxxxxxxsTtS6v', 'amount' => 300000000],
            ['address' => '1AAAA4444xxxxxxxxxxxxxxxxxxxxjbqeD', 'amount' => 400000000],
            ['address' => '1AAAA5555xxxxxxxxxxxxxxxxxxxwEhYkL', 'amount' => 500000000],
        ];

        $distro = new Distribution();
        $distro->user_id = $user->id;
        $distro->stage = 0;
        $distro->deposit_address = $deposit_address;
        $distro->address_uuid = Uuid::uuid4()->toString();
        $distro->network = 'btc';
        $distro->asset = 'MYCOIN';
        $distro->asset_total = 1500000000; // 15.0
        $distro->label = 'Test Distribution One';
        $distro->use_fuel = 0;
        $distro->uuid = Uuid::uuid4()->toString();
        $distro->fee_rate = 40;

        if ($is_offchain) {
            $distro->offchain = true;
        }

        //estimate fees
        if (!$is_offchain) {
            $fee_total = Fuel::estimateFuelCost(count($address_list), $distro);
            $distro->fee_total = $fee_total;
        } else {
            $distro->fee_total = 0;
        }

        // save
        $save = $distro->save();

        //save individual distro addresses
        foreach ($address_list as $row) {
            $tx = new DistributionTx();
            $tx->distribution_id = $distro->id;
            $tx->destination = $row['address'];
            $tx->quantity = $row['amount'];
            $tx->save();
        }

        return $distro;
    }

    public function newOffchainDistribution(User $user = null, $update_vars = null, $and_initialize = true)
    {
        $distro = $this->newDistribution($user, $_is_offchain = true);

        if ($update_vars) {
            foreach($update_vars as $k => $v) {
                $distro->{$k} = $v;
            }
            $distro->save();
        }

        if ($and_initialize) {
            app(Initialize::class)->init($distro);
        }

        return $distro;
    }

}
