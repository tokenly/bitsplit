<?php

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

    public function newDistribution(User $user=null)
    {
        if ($user === null) {
            $user = app('UserHelper')->newRandomUser();
        }

        $deposit_address = MockSubstationClient::sampleAddress('bitcoin', 0);

        $address_list = [
            ['address' => 'recip_addr_001', 'amount' => 100000000,],
            ['address' => 'recip_addr_002', 'amount' => 200000000,],
            ['address' => 'recip_addr_003', 'amount' => 300000000,],
            ['address' => 'recip_addr_004', 'amount' => 400000000,],
            ['address' => 'recip_addr_005', 'amount' => 500000000,],
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

        //estimate fees
        $fee_total = Fuel::estimateFuelCost(count($address_list), $distro);
        $distro->fee_total = $fee_total;

        // save
        $save = $distro->save();

        //save individual distro addresses
        foreach($address_list as $row){
            $tx = new DistributionTx();
            $tx->distribution_id = $distro->id;
            $tx->destination = $row['address'];
            $tx->quantity = $row['amount'];
            $tx->save();
        }

        return $distro;
    }

}
