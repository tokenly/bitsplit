<?php
namespace App\Libraries\Folders;

use Illuminate\Support\Collection;
use GuzzleHttp\Client;
use Models\Distribution;

class Folders
{

    private $start_date;
    private $end_date;
    private $amount;

    private $folders;
    private $amounts;

    public function __construct($start_date, $end_date, $amount)
    {
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->amount = $amount;
        $this->getFoldersData();
    }

    private function getFoldersData() {
        $stats = app(StatsAPI::class);
        $response = $stats->getDistro($this->start_date, $this->end_date, $this->amount);
        $folders = $response['distro'];
        //Unify all folders with the same address
        $pre_folders = [];
        foreach ($folders as $folder) {
            if(isset($pre_folders[$folder['bitcoinAddress']])) {
                $pre_folders[$folder['bitcoinAddress']]['pointsGained'] += $folder['pointsGained'];
                $pre_folders[$folder['bitcoinAddress']]['amount'] = bcadd($pre_folders[$folder['bitcoinAddress']]['amount'], $folder['amount'], 8);
            } else {
                $pre_folders[$folder['bitcoinAddress']] = $folder;
            }
        }
        //
        foreach ($pre_folders as $folder) {
            $this->folders[] = new Folder($folder['pointsGained'], $folder['bitcoinAddress']);
            if(isset($this->amounts[$folder['bitcoinAddress']])) {
                $this->amounts[$folder['bitcoinAddress']] += $folder['amount'];
            } else {
                $this->amounts[$folder['bitcoinAddress']] = $folder['amount'];
            }
        }
    }

    private function getFolders(): array {
        $folders = array();
        foreach ($this->folders as $k => $v) {
            $folders[$k] = clone $v;
        }
        return $folders;
    }

    private function processFolders(string $calculation_type, array $folders, $compare_amount = false): array {
        $total_points = 0.00;
        foreach ($folders as $folder) {
            $total_points += $folder->new_points;
        }
        foreach ($folders as $folder) {
            $folder->calculateAmount($total_points, $this->amount, $calculation_type === Distribution::PROPORTIONAL_CALCULATION_TYPE);
            if($compare_amount && (bccomp($folder->getAmount(), $this->amounts[$folder->address]) !== 0)) {
                throw new \Exception('Amount wasn\'t calculated correctly for address: ' . $folder->address . '. ' . $folder->getAmount() . ' doesn\'t match ' . $this->amounts[$folder->address]);
            }
        }
        return $folders;
    }

    function getAllFolders(string $calculation_type) {
        return $this->processFolders($calculation_type, $this->folders, $calculation_type == Distribution::PROPORTIONAL_CALCULATION_TYPE);
    }

    function getTopFolders(string $calculation_type, int $amount) {
        $folders = $this->getFolders();
        usort($folders, function($folder_a, $folder_b) {
            return $folder_b->new_points - $folder_a->new_points;
        });
        $top_folders = array_slice($folders,0, $amount);
        return $this->processFolders($calculation_type, $top_folders);
    }

    function getFoldersWithMin(string $calculation_type, $minimum_fah_points) {
        $folders = $this->getFolders();
        $min_folders = array_filter($folders, function (Folder $folder) use ($minimum_fah_points) {
            return $folder->new_points >= $minimum_fah_points;
        });
        return $this->processFolders($calculation_type, $min_folders);
    }

    function getRandomFolders(string $calculation_type, int $amount, bool $weight_cache_by_fah) {
        $folders = $this->getFolders();
        $folding_address_list = array();
        if($weight_cache_by_fah) {
            //randomize selection but use new_credit to determine probabilities
            $total_credit = 0;
            $total_count = 0;
            foreach($folders as $k => $daily_folder){
                $total_credit += $daily_folder->new_points;
                $total_count++;
            }
            $winners = 0;
            while($winners < $amount && $winners < $total_count){
                foreach($folders as $k => $daily_folder){
                    if(isset($folding_address_list[$k])){
                        //already won
                        continue;
                    }
                    $probability = ($daily_folder->new_points / $total_credit) * 1000;
                    $lucky = mt_rand(0, 1000);
                    if($lucky <= $probability){
                        //winner
                        $folding_address_list[$k] = $daily_folder;
                        $winners++;
                    }
                }
            }
        } else {
            //get completely (pseudo)random addresses
            $keys = array();
            foreach($folders as $k => $daily_folder){
                $keys[] = $k;
            }
            $select_keys = array_rand($keys, $amount);
            foreach($select_keys as $k){
                $folding_address_list[$k] = $folders[$k];
            }
        }
        $folding_address_list = array_values($folding_address_list);
        return $this->processFolders($calculation_type, $folding_address_list);
    }
}