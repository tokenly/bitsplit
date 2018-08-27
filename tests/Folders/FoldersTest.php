<?php

use App\Distribute\Stages\Onchain\PrimeUtxos;
use PHPUnit\Framework\Assert as PHPUnit;

class FoldersTest extends TestCase
{

    protected $use_database = true;

    public function testSimpleUtxoPriming() {
        //Get test data
        $distribution = json_decode(file_get_contents(storage_path() . "/app/distribution.json"), true);
        $test_folders = array();
        foreach ($distribution['distro'] as $test_folder) {
            if(isset($test_folders[$test_folder['bitcoinAddress']])) {
                $test_folders[$test_folder['bitcoinAddress']]['pointsGained'] += $test_folder['pointsGained'];
                $test_folders[$test_folder['bitcoinAddress']]['amount'] = bcadd($test_folders[$test_folder['bitcoinAddress']]['amount'], $test_folder['amount']);
            } else {
                $test_folders[$test_folder['bitcoinAddress']] = $test_folder;
            }
        }
        //Mock
        $stats_api = Mockery::mock(\App\Libraries\Folders\StatsAPI::class);
        $stats_api->shouldReceive('getDistro')->andReturn($distribution);
        app()->instance(\App\Libraries\Folders\StatsAPI::class, $stats_api);
        //Create Folders and compare
        $folders_API = new \App\Libraries\Folders\Folders('07-30-2018', '07-30-2018', 100000000);
        $folders = $folders_API->getAllFolders(\Models\Distribution::PROPORTIONAL_CALCULATION_TYPE);
        foreach ($folders as $folder) {
            $test_folder = $test_folders[$folder->address];
            PHPUnit::assertTrue(bccomp($test_folder['amount'], $folder->getAmount()) === 0,  'Calculations not working for Address ' . $folder->address. '. ' . $test_folder['amount'] . ' ' . $folder->getAmount());
        }
    }

}
