<?php
namespace App\Libraries\Stats;

use GuzzleHttp\Client;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;

class Stats
{
    const SERVER_URL = 'https://teststatsdownloadapi.azurewebsites.net/';
    private $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => self::SERVER_URL,
        ]);
    }

    function getDistributionData($start_date, $end_date, $amount) {
        $res = $this->client->request('GET', '/v1/GetDistro', [
            'query' => ['startDate' => $start_date, 'endDate' => $end_date, 'amount' => $amount]
        ]);
        return json_decode($res->getBody(), true);
    }

}