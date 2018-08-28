<?php
namespace App\Libraries\Folders;

use Exception;
use GuzzleHttp\Client;

class StatsAPI
{

    public function __construct()
    {

    }

    public function getDistro($start_date, $end_date, $amount)
    {
        $STATS_PROVIDER_URL = env('STATS_PROVIDER_URL');
        if (!$STATS_PROVIDER_URL) {
            throw new Exception("STATS_PROVIDER_URL environment variable not defined", 1);
        }
        $client = new Client(['base_uri' => $STATS_PROVIDER_URL]);
        $res = $client->request('GET', '/v1/GetDistro', [
            'query' => ['startDate' => $start_date, 'endDate' => $end_date, 'amount' => $amount],
        ]);
        $response = json_decode($res->getBody(), true);
        if (empty($response['distro'])) {
            throw new \Exception('We couldn\'t get the data from the Stats API');
        }
        return $response;
    }
}
