<?php

/**
*  TokenpassHelper
*/
class TokenpassHelper
{

    public static function mockAll($mock = null) {
        $mock = self::mockCheckoutMethods($mock);
        $mock = self::mockPromiseMethods($mock);
        return $mock;
    }

    public static function mockCheckoutMethods($mock = null) {
        $mock = self::mockTokenpassCreditAccount($mock);
        $mock = self::mockTokenpassSpendCredits($mock);
        $mock = self::mockTokenpassErrors($mock);
        $mock = self::mockCheckTokenAccessByEmail($mock);
        return $mock;
    }

    public static function mockPromiseMethods($mock = null) {
        $mock = self::mockTokenpassPromiseTransaction($mock);
        $mock = self::mockProvisionalSourceMethods($mock);
        // $mock = self::mockTokenpassPromisedTransactionListByEmailAddress($mock);
        return $mock;
    }

    public static function mockProvisionalSourceMethods($mock = null) {
        $mock = self::mockTokenpassRegisterProvisionalSource($mock);
        $mock = self::mockTokenpassGetProvisionalSourceList($mock);
        return $mock;
    }

    // ------------------------------------------------------------------------
    
    public static function mockCheckTokenAccessByEmail($mock = null) {
        $mock = self::ensureMockedTokenpassAPI($mock);
        $mock->shouldReceive('checkTokenAccessByEmail')->andReturn(false);
        return $mock;
    }


    public static function mockTokenpassErrors($mock = null) {
        $mock = self::ensureMockedTokenpassAPI($mock);
        $mock->shouldReceive('getErrors')->andReturn([]);
        $mock->shouldReceive('clearErrors');
        return $mock;
    }

    public static function mockTokenpassCreditAccount($mock = null) {
        $mock = self::ensureMockedTokenpassAPI($mock);

        $mock->shouldReceive('getAppCreditAccount')->andReturn([
            'balance' => 1000000000000000000,
            'uuid' => '5000',
        ]);

        return $mock;
    }

    public static function mockTokenpassSpendCredits($mock = null) {
        $mock = self::ensureMockedTokenpassAPI($mock);

        $mock->shouldReceive('takeAppCredit')->andReturn([
            'uuid' => '007341aa-0000-0000-0000-0000007341aa',
        ]);

        return $mock;
    }

    public static function mockTokenpassPromiseTransaction($mock = null)
    {
        $mock = self::ensureMockedTokenpassAPI($mock);

        $mock->shouldReceive('promiseTransaction')->andReturn([
            'promise_id' => 101,
        ]);

        return $mock;
    }

    public static function mockTokenpassRegisterProvisionalSource($mock = null)
    {
        $mock = self::ensureMockedTokenpassAPI($mock);

        $mock->shouldReceive('registerProvisionalSource')->andReturn(
            true
        );

        return $mock;
    }

    public static function mockTokenpassGetProvisionalSourceList($mock = null)
    {
        $mock = self::ensureMockedTokenpassAPI($mock);

        $mock->shouldReceive('getProvisionalSourceList')->andReturn(
            []
        );

        return $mock;
    }

    public static function mockTokenpassPromisedTransactionListByEmailAddress($mock = null)
    {
        $mock = self::ensureMockedTokenpassAPI($mock);

        $mock->shouldReceive('getPromisedTransactionListByEmailAddress')->andReturn(
            []
        );

        return $mock;
    }

    // ------------------------------------------------------------------------
    
    public static function ensureMockedTokenpassAPI($mock = null) {
        if ($mock === null) {
            $mock = Mockery::mock('Tokenly\TokenpassClient\TokenpassAPI');
            app()->instance('Tokenly\TokenpassClient\TokenpassAPI', $mock);
        }

        return $mock;
    }
}