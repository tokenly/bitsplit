<?php
namespace App\Services;

use App\Http\Requests\Request;
use App\Http\Requests\SubmitDistribution;
use App\Libraries\Stats\Stats;
use App\Libraries\Substation\UserAddressManager;
use App\Models\DailyFolder;
use Illuminate\Support\Facades\Log;
use Models\Distribution;
use Models\DistributionTx;
use Models\Fuel;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Ramsey\Uuid\Uuid;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenmapClient\TokenmapClient;
use Distribute\Initialize as DistroInit;

class CreateDistribution
{

    private $request;
    private $folding_start_date;
    private $folding_end_date;
    private $folding_address_list;
    private $deposit_address;
    private $asset;
    private $calculation_type;
    private $distroCount;
    private $distroTotalPoints;

    public function __construct(SubmitDistribution $request)
    {
        $this->request = $request;
        $this->folding_end_date = date("Y-m-d", strtotime($request->input('folding_end_date'))).' 23:59:59';
        $this->folding_start_date = date("Y-m-d", strtotime($request->input('folding_start_date')));
        $this->asset = trim($request->input('asset', ''));
        $this->calculation_type = $this->request->input('calculation_type');
        $this->getFoldingAddressList();
        try{
            $this->deposit_address = app(UserAddressManager::class)->newPaymentAddressForUser($request->user());
        } catch(\Exception $e){
            EventLog::logError('depositAddress.error', $e, ['userId' => $request->user()->id,]);
        }
    }

    function create() {
        $request = $this->request;
        $distro = new Distribution();
        $distro->user_id = $request->user()->id;
        $distro->stage = 0;
        $distro->deposit_address = $this->deposit_address['address'];
        $distro->address_uuid = $this->deposit_address['uuid'];
        $distro->network = 'btc';
        $distro->asset = $this->asset;
        $distro->asset_total = (string) $this->calculateAssetTotal();
        $distro->label = $request->filled('label') ? htmlentities(trim($request->input('label'))): '';
        $distro->use_fuel = $request->filled('use_fuel') && intval($request->input('use_fuel')) == 1 ? 1 : 0;
        $distro->uuid = Uuid::uuid4()->toString();
        $distro->fee_rate = $request->filled('btc_fee_rate') ? intval($request->input('btc_fee_rate')) : null;
        $distro->folding_start_date = $this->folding_start_date;
        $distro->folding_end_date = $this->folding_end_date;
        $distro->label = $this->asset. ' - ' . $request->input('asset_total') . ' - '. date('Y/m/d');
        $distro->distribution_class = $request->input('distribution_class');
        $distro->calculation_type = ucfirst($request->input('calculation_type'));
        $distro->total_folders = $this->distroCount;
        $distro->fee_total = $this->estimateFeeTotal($this->getAddressList(), $distro);
        $distro->fiat_token_quote = $this->getUsdQuote($this->asset);
        $save = $distro->save();
        if(!$save){
            Log::error('Error saving distribution '.$this->deposit_address['address'].' for user '.$request->user()->id);
            return false;
        }
        $this->saveDistroAddresses($distro);
        $initializer = new DistroInit;
        $initializer->init($distro);
        return $this->deposit_address['address'];
    }

    private function saveDistroAddresses(Distribution $distro) {
        $address_list = $this->getAddressList();
        $list_new_credits = $this->getPointsGainedByEachAddress();
        foreach($address_list as $row){
            $tx = new DistributionTx();
            $tx->distribution_id = $distro->id;
            $tx->destination = $row['address'];
            $tx->quantity = (string)$row['amount'];
            $tx->folding_credit = (string) $list_new_credits[$row['address']];
            $tx->fldc_usernames = null;
            $tx->save();
        }
    }

    private function getUsdQuote(string $asset) {
        try{
            $tokenmap_client = app(TokenmapClient::class);
            return $tokenmap_client->getSimpleQuote('USD', $asset, 'counterparty')->getFloatValue();
        } catch(\Exception $e){

        }
    }

    private function estimateFeeTotal($address_list, Distribution $distro): string {
        return (string) Fuel::estimateFuelCost(count($address_list), $distro);
    }

    private function getPointsGainedByEachAddress(): array {
        //Array to store new credits for each address
        $list_new_credits = array();
        foreach ($this->folding_address_list as $daily_folder) {
            if(isset($list_new_credits[$daily_folder['bitcoinAddress']])) {
                $list_new_credits[$daily_folder['bitcoinAddress']] += $daily_folder['pointsGained'];
            } else {
                $list_new_credits[$daily_folder['bitcoinAddress']] = $daily_folder['pointsGained'];
            }
        }
        return $list_new_credits;
    }

    private function getFoldingAddressList() {
        $stats = new Stats();
        $response = $stats->getDistributionData($this->folding_start_date, $this->folding_end_date, $this->request->input('asset_total'));
        if(empty($response['distro'])) {
            throw new \Exception('We couldn\'t get the data from the Stats API');
        }
        $this->folding_address_list = $response['distro'];
        $this->distroCount = $response['distroCount'];
        $this->distroTotalPoints = $response['totalPoints'];
    }

    private function getAddressList(): array {
        $folding_list = array();
        foreach($this->folding_address_list as $folder){
            if($folder['amount'] <= 0){ continue; }
            $folding_list[$folder['bitcoinAddress']] = $folder['amount'];
        }
        return Distribution::processAddressList($folding_list);
    }

    private function calculateAssetTotal() {
        $use_total = false;
        if ($this->request->filled('asset_total')) {
            $use_total = intval(bcmul(trim($this->request->input('asset_total')), '100000000', '0'));
        }
        if(!$use_total OR $use_total <= 0){
            return false;
        }
        //figure out total to send
        return intval(bcmul(trim($this->request->input('asset_total')), '100000000', '0'));
    }

}