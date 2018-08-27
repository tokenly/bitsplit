<?php
namespace App\Libraries\Folders;


use GuzzleHttp\Client;

class StatsAPI
{

    const SERVER_URL = 'https://teststatsdownloadapi.azurewebsites.net/';

    public function __construct()
    {

    }

    function getDistro($start_date, $end_date, $amount) {
        $client = new Client(['base_uri' => self::SERVER_URL,]);
        $res = $client->request('GET', '/v1/GetDistro', [
            'query' => ['startDate' => $start_date, 'endDate' => $end_date, 'amount' => $amount]
        ]);
        $response = json_decode($res->getBody(), true);
        if(empty($response['distro'])) {
            throw new \Exception('We couldn\'t get the data from the Stats API');
        }
        return $response;
    }
}