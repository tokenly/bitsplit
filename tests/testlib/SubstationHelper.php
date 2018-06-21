<?php

use Ramsey\Uuid\Uuid;
use Tokenly\CryptoQuantity\CryptoQuantity;

/**
 *  SubstationHelper
 */
class SubstationHelper
{

    static $MOCKED_TXOS;

    public static function mockAll($mock = null)
    {
        $mock = self::ensureMockedSubstationClient($mock);

        self::mock_getTxosById($mock);
        self::mock_createServerManagedWallet($mock);
        self::mock_sendImmediatelyToDestinations($mock);
    }

    public static function mock_createServerManagedWallet($mock = null)
    {
        $mock = self::ensureMockedSubstationClient($mock);

        $mock->shouldReceive('createServerManagedWallet')->andReturn([
            'uuid' => Uuid::uuid4()->toString(),
        ]);

        return $mock;
    }

    public static function mock_getTXOsById($mock = null)
    {
        $mock = self::ensureMockedSubstationClient($mock);

        $mock->shouldReceive('getTXOsById')->andReturnUsing(function () {
            $txos = self::getTxosForResponse();
            return [
                'items' => $txos,
                'count' => count($txos),
            ];
        });

        return $mock;
    }

    public static function mock_sendImmediatelyToDestinations($mock = null, $expectation_callback_fn = null, $called_callback_fn = null)
    {
        $mock = self::ensureMockedSubstationClient($mock);

        $call_offset = 0;
        self::handleExpectationCallback(
            $mock->shouldReceive('sendImmediatelyToDestinations')->andReturnUsing(function () use ($called_callback_fn, &$call_offset) {
                if ($called_callback_fn) {
                    $args = func_get_args();
                    $called_callback_fn($args, $call_offset);
                    ++$call_offset;
                }

                return [
                    'feePaid' => CryptoQuantity::fromSatoshis(1000), // 0.0001 BTC
                    'txid' => '0000000000000000000000000000000000000000000000000000000000001000',
                ];
            }),
            $expectation_callback_fn
        );

        return $mock;
    }

    // ------------------------------------------------------------------------

    public static function setTxos($txos)
    {
        self::$MOCKED_TXOS = $txos;
    }

    public static function getTxosForResponse()
    {
        if (isset(self::$MOCKED_TXOS)) {
            return self::$MOCKED_TXOS;
        }
        return self::defaultTxosResponse();
    }

    public static function defaultTxosResponse()
    {
        return [
            [
                'txid' => '0001',
                'n' => '0',
                'amount' => '10000000', // 0.1 BTC
                'spent' => false,
            ],
        ];
    }

    // ------------------------------------------------------------------------

    public static function ensureMockedSubstationClient($mock = null)
    {
        if ($mock === null) {
            $mock = Mockery::mock('Tokenly\SubstationClient\SubstationClient');
            app()->instance('Tokenly\SubstationClient\SubstationClient', $mock);
        }

        return $mock;
    }

    // ------------------------------------------------------------------------

    protected static function handleExpectationCallback($expectation, $expectation_callback_fn)
    {
        if ($expectation_callback_fn) {
            $expectation_callback_fn($expectation);
        }
    }

}
