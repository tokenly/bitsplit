<?php

use Tokenly\TokenmapClient\Mock\MockeryBuilder;

/**
 *  TokenmapHelper
 */
class TokenmapHelper
{

    public static function mockTokenmap()
    {
        $builder = MockeryBuilder::bindTokenmapClientMock();

        // add FLDC quote
        $entries = $builder->getDefaultMockRateEntries();
        $entries['BTC:FLDC:counterparty'] =
            [
            'source' => 'bittrex',
            'pair' => 'BTC:FLDC',
            'inSatoshis' => true,
            'bid' => 42,
            'last' => 42,
            'ask' => 43,
            'bidLow' => 41,
            'bidHigh' => 43,
            'bidAvg' => 42,
            'lastLow' => 41,
            'lastHigh' => 44,
            'lastAvg' => 43,
            'askLow' => 42,
            'askHigh' => 44,
            'askAvg' => 44,
            'start' => '2018-08-23T15:32:25+00:00',
            'end' => '2018-08-24T15:32:25+00:00',
            'time' => '2018-08-24T15:32:21+00:00',
        ];
        $entries['USD:FLDC:counterparty'] =
            [
            'source' => 'coinMarketCap',
            'pair' => 'USD:FLDC',
            'inSatoshis' => false,
            'bid' => 0.00306114,
            'last' => 0.00306114,
            'ask' => 0.00306114,
            'askLow' => 0.00301821,
            'askHigh' => 0.00319815,
            'askAvg' => 0.00309212,
            'bidLow' => 0.00301821,
            'bidHigh' => 0.00319815,
            'bidAvg' => 0.00309212,
            'lastLow' => 0.00301821,
            'lastHigh' => 0.00319815,
            'lastAvg' => 0.00309212,
            'start' => '2018-08-28T13:51:45+00:00',
            'end' => '2018-08-29T13:51:45+00:00',
            'time' => '2018-08-29T13:51:02+00:00',
        ];

        $builder->setMockRateEntries($entries);

        return $builder;
    }

}
