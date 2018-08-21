<?php

use App\Distribute\Stages\Onchain\PrimeUtxos;
use PHPUnit\Framework\Assert as PHPUnit;


class PrimeUtxosTest extends TestCase
{

    protected $use_database = true;

    public function testSimpleUtxoPriming() {
        $mock = null;
        $mock = SubstationHelper::mock_getTxosById($mock);
        $mock = SubstationHelper::mock_createServerManagedWallet($mock);
        $mock = SubstationHelper::mock_sendImmediatelyToDestinations($mock, 
            function($expectation) {
                $expectation->times(1);
            },
            function($called_args) {
                $destinations = $called_args[3];
                PHPUnit::assertCount(5, $destinations);
                PHPUnit::assertEquals(8560, $destinations[0]['quantity']->getSatoshisString());
            }
        );

        // create the distribution
        $distibution = app('DistributionHelper')->newDistribution();

        // prime
        $prime_stage = new PrimeUtxos($distibution);
        $success = $prime_stage->init();
        PHPUnit::assertTrue($success);
    }


    public function testTwoStageUtxoPriming_stage1() {
        // set a config to ensure 2 setup primes
        Config::set('settings.max_tx_outputs', 3);

        $mock = null;
        $mock = SubstationHelper::ensureMockedSubstationClient($mock);
        $mock = SubstationHelper::mock_getTXOsById($mock);
        $mock = SubstationHelper::mock_createServerManagedWallet($mock);
        $mock = SubstationHelper::mock_sendImmediatelyToDestinations($mock, 
            function($expectation) {
                $expectation->times(1);
            },
            function($called_args, $call_offset) {
                $destinations = $called_args[3];

                // 2 setup primes
                PHPUnit::assertCount(2, $destinations);
                PHPUnit::assertEquals(36120, $destinations[0]['quantity']->getSatoshisString());
                PHPUnit::assertEquals(26200, $destinations[1]['quantity']->getSatoshisString());
            }
        );

        // create the distribution
        $distibution = app('DistributionHelper')->newDistribution();

        // prime
        $prime_stage = new PrimeUtxos($distibution);
        $success = $prime_stage->init();
        PHPUnit::assertTrue($success);

        // check the distribution transactions
        $records = DB::table('distribution_primes')->get();
        PHPUnit::assertCount(1, $records);
        PHPUnit::assertEquals('1', $records[0]->stage);
    }

    public function testTwoStageUtxoPriming_stage2() {
        // set a config to ensure 2 setup primes
        Config::set('settings.max_tx_outputs', 3);

        // setup the txos response
        SubstationHelper::setTxos([
            [
                'txid' => '0000000000000000000000000000000000000000000000000000000000001000',
                'n' => '0',
                'amount' => '36120',
                'spent' => false,
            ],
            [
                'txid' => '0000000000000000000000000000000000000000000000000000000000001000',
                'n' => '1',
                'amount' => '26200',
                'spent' => false,
            ],
            [
                // change
                'txid' => '0000000000000000000000000000000000000000000000000000000000001000',
                'n' => '2',
                'amount' => '500000',
                'spent' => false,
            ],
        ]);

        $mock = null;
        $mock = SubstationHelper::ensureMockedSubstationClient($mock);
        $mock = SubstationHelper::mock_getTXOsById($mock);
        $mock = SubstationHelper::mock_createServerManagedWallet($mock);
        $mock = SubstationHelper::mock_sendImmediatelyToDestinations($mock, 
            function($expectation) {
                $expectation->times(2);
            },
            function($called_args, $call_offset) {
                $destinations = $called_args[3];

                switch ($call_offset) {
                    case 0:
                        // first call has 3 txo primes
                        PHPUnit::assertCount(3, $destinations);
                        PHPUnit::assertEquals(8560, $destinations[0]['quantity']->getSatoshisString());
                        PHPUnit::assertEquals(8560, $destinations[1]['quantity']->getSatoshisString());
                        PHPUnit::assertEquals(8560, $destinations[2]['quantity']->getSatoshisString());
                        break;
                    
                    case 1:
                        // second call has 2 txo primes
                        PHPUnit::assertCount(2, $destinations);
                        PHPUnit::assertEquals(8560, $destinations[0]['quantity']->getSatoshisString());
                        PHPUnit::assertEquals(8560, $destinations[1]['quantity']->getSatoshisString());
                        break;
                    
                    default:
                        throw new Exception("Unexpected call offset: $call_offset", 1);
                }
            }
        );

        // create the distribution
        $distibution = app('DistributionHelper')->newDistribution();

        // prime (should run stage 2)
        $prime_stage = new PrimeUtxos($distibution);
        $success = $prime_stage->init();
        PHPUnit::assertTrue($success);

        // check the distribution transactions
        $records = DB::table('distribution_primes')->get();
        PHPUnit::assertCount(2, $records);
        PHPUnit::assertEquals('2', $records[0]->stage);
        PHPUnit::assertEquals('2', $records[1]->stage);
    }

    // ------------------------------------------------------------------------

    protected function setupTwoStageTxosExpectations()
    {
        $substation_client_mock->shouldReceive('getTXOsById')->andReturnUsing(function () {
            $txos = SubstationHelper::getTxosResponse();
            return [
                'items' => $txos,
                'count' => count($txos),
            ];
        });
    }
}
