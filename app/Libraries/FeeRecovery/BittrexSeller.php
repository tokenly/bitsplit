<?php

namespace App\Libraries\FeeRecovery;

use App\Repositories\FeeRecoveryLedgerEntryRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;

class BittrexSeller
{

    const BITTREX_FEE = 0.0025;

    public function __construct(FeeRecoveryLedgerEntryRepository $fee_recovery_ledger)
    {
        $this->fee_recovery_ledger = $fee_recovery_ledger;
    }

    public function purchaseBTC(CryptoQuantity $purchase_amount)
    {
        $raw_response = $this->bittrexCall('/api/v1.1/public/getticker', [
            'market' => 'BTC-FLDC',
            '_' => time(),
        ], $_signed = false);
        if (!$raw_response['success']) {
            EventLog::error('bittrex.getTickerFailed', [
                'message' => $raw_response['message'] ?? 'unknown message',
                'code' => $raw_response['http_status'],
            ]);
            return;
        }
        // Log::debug("\$raw_response=" . json_encode($raw_response, 192));
        $market_summary = $raw_response['result'];

        $bid = CryptoQuantity::fromFloat($market_summary['Bid']);
        $bid_float = $bid->getFloatValue();

        // fill it now
        $quantity_to_sell_float = ($purchase_amount->getFloatValue() * (1 + self::BITTREX_FEE)) / $bid_float;

        $purchase_vars = [
            'MarketName' => 'BTC-FLDC',
            'OrderType' => 'LIMIT',
            'Quantity' => number_format($quantity_to_sell_float, 8, '.', ''),
            'Rate' => number_format($bid->getFloatValue(), 8, '.', ''),
            'TimeInEffect' => 'IMMEDIATE_OR_CANCEL',
            'ConditionType' => 'NONE',
            'Target' => '0',
        ];

        EventLog::debug('bittrex.sellAttempt', [
            'quantity' => $purchase_vars['Quantity'],
            'rate' => $purchase_vars['Rate'],
            'desiredBtc' => $purchase_amount->getSatoshisString(),
        ]);
        $raw_response = $this->bittrexCall('/api/v2.0/key/market/TradeSell', $purchase_vars, $_signed = true);
        // Log::debug("\$raw_response=" . json_encode($raw_response, 192));

        if (!$raw_response['success']) {
            EventLog::error('bittrex.sellFailed', [
                'message' => $raw_response['message'] ?? 'unknown message',
                'code' => $raw_response['http_status'],

                'quantity' => $purchase_vars['Quantity'],
                'rate' => $purchase_vars['Rate'],
                'desiredBtc' => $purchase_amount->getSatoshisString(),
            ]);
            return;
        }
        $sell_result = $raw_response['result'];
        $order_uuid = $sell_result['OrderId'];

        EventLog::debug('bittrex.sellExecuted', [
            'quantity' => $purchase_vars['Quantity'],
            'rate' => $purchase_vars['Rate'],
            'desiredBtc' => $purchase_amount->getSatoshisString(),
            'orderUuid' => $order_uuid,
        ]);

        // get order result (try 5 times)
        $attempts = 0;
        $max_attempts = 5;
        while ($attempts <= $max_attempts) {
            ++$attempts;
            $raw_response = $this->bittrexCall('/api/v1.1/account/getorder', ['uuid' => $order_uuid], $_signed = true);
            // Log::debug("\$raw_response=" . json_encode($raw_response, 192));
            $order_result = $raw_response['result'];
            if (!$raw_response['success']) {
                EventLog::error('bittrex.getOrderFailed', [
                    'message' => $raw_response['message'] ?? 'unknown message',
                    'code' => $raw_response['http_status'],
                    'attempt' => $attempts,
                ]);
                sleep(2 * $attempts);
                continue;
            }

            $fee = CryptoQuantity::fromFloat($order_result['CommissionPaid']);
            $btc_gained = CryptoQuantity::fromFloat($order_result['Price'])->subtract($fee);
            $fldc_sold = CryptoQuantity::fromFloat($order_result['Quantity']);
            EventLog::debug('bittrex.sellComplete', [
                'quantity' => $purchase_vars['Quantity'],
                'rate' => $purchase_vars['Rate'],
                'desiredBtc' => $purchase_amount->getSatoshisString(),
                'orderUuid' => $order_uuid,
                'fldcSold' => $fldc_sold->getSatoshisString(),
                'btcGained' => $btc_gained->getSatoshisString(),
                'btcFee' => $fee->getSatoshisString(),
            ]);
            break;
        }

        return [
            'fldc_sold' => $fldc_sold->getSatoshisString(),
            'btc_gained' => $btc_gained->getSatoshisString(),
            'btc_fee' => $fee->getSatoshisString(),
        ];
    }

    // ------------------------------------------------------------------------

    protected function bittrexCall($url, $parameters, $signed = true)
    {

        if ($signed) {
            $api_key = env('BITTREX_API_KEY', null);
            $api_secret = env('BITTREX_API_SECRET', null);
            if (!$api_key) {
                throw new Exception("Missing Bittrex API key", 1);
            }
            if (!$api_secret) {
                throw new Exception("Missing Bittrex API secret", 1);
            }

            $parameters = array_merge([
                'apikey' => $api_key,
                'nonce' => time(),
            ],
                $parameters
            );
        }

        $uri = 'https://bittrex.com' . $url . '?' . http_build_query($parameters);
        $ch = curl_init($uri);

        if ($signed) {
            // $sign=hash_hmac('sha512',$uri,$apisecret);
            $signature = hash_hmac('sha512', $uri, $api_secret);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('apisign:' . $signature));
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);

        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $is_bad_status_code = ($status_code >= 400 and $status_code < 600);
        if ($is_bad_status_code) {
            // attempt to decode response
            try {
                $decoded_json = json_decode($res, true);
                if ($decoded_json) {
                    $decoded_json['http_status'] = $status_code;
                    $decoded_json['success'] = false;
                    return $decoded_json;
                }
            } catch (Exception $e) {
                $decoded_json = null;
            }

            throw new Exception("Call to {$uri} returned bad status code: $status_code", 1);
        }

        $decoded_json = json_decode($res, true);
        $decoded_json['http_status'] = $status_code;
        $decoded_json['success'] = $decoded_json['success'] ?? true;

        return $decoded_json;
    }

}

/*
https://github.com/thebotguys/golang-bittrex-api/wiki/Bittrex-API-Reference-(Unofficial)
 */

// [2018-08-27 13:09:30] local.DEBUG: $raw_response={
//     "success": true,
//     "message": "",
//     "result": {
//         "OrderId": "99dd1fb3-7193-41ee-a7d3-3d78dde7e88f",
//         "MarketName": "BTC-FLDC",
//         "MarketCurrency": "FLDC",
//         "BuyOrSell": "Sell",
//         "OrderType": "LIMIT",
//         "Quantity": 1165.69767442,
//         "Rate": 4.3e-7
//     },
//     "http_status": 200
// }

// $raw_response={
//    "success": true,
//    "message": "",
//    "result": {
//        "AccountId": null,
//        "OrderUuid": "99dd1fb3-7193-41ee-a7d3-3d78dde7e88f",
//        "Exchange": "BTC-FLDC",
//        "Type": "LIMIT_SELL",
//        "Quantity": 1165.69767442,
//        "QuantityRemaining": 0,
//        "Limit": 4.3e-7,
//        "Reserved": 1165.69767442,
//        "ReserveRemaining": 1165.69767442,
//        "CommissionReserved": 0,
//        "CommissionReserveRemaining": 0,
//        "CommissionPaid": 1.25e-6,
//        "Price": 0.00050125,
//        "PricePerUnit": 4.2e-7,
//        "Opened": "2018-08-27T13:09:29.927",
//        "Closed": "2018-08-27T13:09:30.02",
//        "IsOpen": false,
//        "Sentinel": "e742bbee-7e40-4c25-9d2b-6fe009a3bf06",
//        "CancelInitiated": false,
//        "ImmediateOrCancel": true,
//        "IsConditional": false,
//        "Condition": "NONE",
//        "ConditionTarget": null
//    },
//    "http_status": 200
// }
