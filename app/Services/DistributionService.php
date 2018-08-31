<?php
namespace App\Services;

use App\Http\Requests\SubmitDistribution;
use App\Libraries\Folders\ClearinghouseFolders;
use App\Libraries\Folders\Folders;
use App\Libraries\Stats\Stats;
use App\Libraries\Substation\Substation;
use App\Libraries\Substation\UserAddressManager;
use Distribute\Initialize as DistroInit;
use Exception;
use Illuminate\Support\Facades\Log;
use Models\Distribution;
use Models\DistributionTx;
use Models\Fuel;
use Ramsey\Uuid\Uuid;
use Tokenly\CryptoQuantity\CryptoQuantity;
use Tokenly\LaravelEventLog\Facade\EventLog;
use Tokenly\TokenmapClient\TokenmapClient;

class DistributionService
{

    private $request;
    private $folding_start_date;
    private $folding_end_date;
    private $deposit_address;
    private $asset;
    private $calculation_type;

    private $distroCount;
    private $addresses = array();
    private $credit_lists = array();

    public function __construct(SubmitDistribution $request)
    {
        $user = $request->user();

        $this->request = $request;
        $this->folding_end_date = date("Y-m-d", strtotime($request->input('folding_end_date'))).' 23:59:59';
        $this->folding_start_date = date("Y-m-d", strtotime($request->input('folding_start_date')));
        $this->asset = trim($request->input('asset', ''));
        $this->calculation_type = $this->request->input('calculation_type');

        $this->distro_uuid = Uuid::uuid4()->toString();

        // calculate onchain vs offchain distribution
        $is_official_distribution = ($this->asset == FLDCAssetName() and $user->isAdmin);
        if ($is_official_distribution and $request->input('offchain')) {
            $offchain = true;
        } else {
            $offchain = false;
        }
        $this->offchain = $offchain;
        $this->onchain = !$offchain;

        if ($this->calculation_type == 'clearinghouse' and $user->isAdmin) {
            $this->initClearinghouseDistribution($request);
            $this->is_clearinghouse = true;
        } else {
            $this->initRegularDistribution($request);
            $this->is_clearinghouse = false;
        }
        $this->distroCount = count($this->addresses);

        Log::debug("\$this->onchain=".json_encode($this->onchain, 192));
        if ($this->onchain) {
            try{
                $this->deposit_address = app(UserAddressManager::class)->newPaymentAddressForUser($request->user());
                Log::debug("\$this->deposit_address=".json_encode($this->deposit_address, 192));
            } catch(\Exception $e){
                EventLog::logError('depositAddress.error', $e, ['userId' => $request->user()->id,]);
                throw $e;
            }
        } else {
            $this->deposit_address = null;
        }
    }

    function create() {
        $request = $this->request;
        $distro = new Distribution();
        $distro->user_id = $request->user()->id;
        $distro->stage = 0;
        $distro->network = 'btc';
        $distro->asset = $this->asset;
        $distro->label = $request->filled('label') ? htmlentities(trim($request->input('label'))): '';
        $distro->use_fuel = $request->filled('use_fuel') && intval($request->input('use_fuel')) == 1 ? 1 : 0;
        $distro->uuid = $this->distro_uuid;
        $distro->folding_start_date = $this->folding_start_date;
        $distro->folding_end_date = $this->folding_end_date;
        $distro->label = $this->asset. ' - ' . $request->input('asset_total', $this->is_clearinghouse ? 'CLEARINGHOUSE' : 'ALL') . ' - '. date('Y/m/d');
        $distro->distribution_class = $request->input('distribution_class', '');
        $distro->calculation_type = ucfirst($request->input('calculation_type'));
        $distro->total_folders = $this->distroCount;
        $distro->fiat_token_quote = app(TokenmapClient::class)->getSimpleQuote('USD', $this->asset, Substation::chain())->getFloatValue();
        if ($this->is_clearinghouse) {
            // this will be summed after address balances are calculated
            $distro->asset_total = $this->sumAddressBalances($this->addresses)->getSatoshisString();
        } else {
            if($this->request->input('calculation_type')  === 'even') {
                $distro->asset_total = intval(bcmul(trim($this->request->input('asset_total')), '100000000', '0'));
            } else {
                $distro->asset_total = intval(bcmul(trim($this->request->input('asset_total')), '100000000', '0')) * count($this->addresses);
            }
        }

        if ($this->onchain) {
            $distro->offchain = false;
            $distro->fee_total = (string) Fuel::estimateFuelCost(count($this->addresses), $distro);
            $distro->fee_rate = $request->filled('btc_fee_rate') ? intval($request->input('btc_fee_rate')) : null;
            $distro->deposit_address = $this->deposit_address['address'];
            $distro->address_uuid = $this->deposit_address['uuid'];

        } else {
            // offchain
            $distro->offchain = true;
            $distro->fee_total = '0';
            $distro->fee_rate = null;
            // since the UI uses the address as the identifier, generate a random uuid for the address here
            $distro->deposit_address = Uuid::uuid4()->toString();
            $distro->address_uuid = '';

            // hold offchain distributions by default
            $distro->hold = 1;
        }

        $save = $distro->save();

        $this->saveDistroAddresses($distro);
        $initializer = new DistroInit;
        $initializer->init($distro);
        return $distro->deposit_address;
    }

    // ------------------------------------------------------------------------

    protected function initClearinghouseDistribution($request)
    {
        $user = $request->user();

        // check clearinghouse distribution is allowed only by admins
        if ($this->calculation_type == 'clearinghouse' and !$user->isAdmin) {
            throw new Exception("You must be an admin to use this function", 1);
        }
        if ($this->calculation_type == 'clearinghouse' and $this->asset != FLDCAssetName()) {
            throw new Exception("Only ".FLDCAssetName()." can be used in a clearinghouse distribution.", 1);
        }

        // assign all points
        $folders = new ClearinghouseFolders($this->distro_uuid);
        $this->addresses = $folders->getAllFolders();

        // default start and end date (not used for calculations)
        $this->folding_end_date = date("Y-m-d");
        $this->folding_start_date = date("Y-m-d");
    }

    protected function initRegularDistribution($request)
    {
        $folders = new Folders($this->folding_start_date, $this->folding_end_date, $this->request->input('asset_total'));
        switch ($this->request->input('distribution_class')) {
            case 'Minimum FAH points':
                $this->addresses = $folders->getFoldersWithMin($this->calculation_type, $this->request->input('minimum_fah_points'));
                break;
            case 'Top Folders':
                $this->addresses = $folders->getTopFolders($this->calculation_type, $this->request->input('amount_top_folders'));
                break;
            case 'Random':
                $this->addresses = $folders->getRandomFolders($this->calculation_type, $this->request->input('amount_random_folders'), $this->request->input('weight_cache_by_fah'));
                break;
            default:
                $this->addresses = $folders->getAllFolders($this->calculation_type);
                break;
        }

    }


    private function saveDistroAddresses(Distribution $distro) {
        foreach($this->addresses as $address){
            $tx = new DistributionTx();
            $tx->distribution_id = $distro->id;
            $tx->destination = $address->address;
            $tx->quantity = $address->getAmountAsSatoshis();
            $tx->folding_credit = $address->new_points;
            $tx->fldc_usernames = null;
            $tx->save();
        }
    }

    protected function sumAddressBalances($addresses)
    {
        $sum = CryptoQuantity::zero();
        foreach($addresses as $address) {
            $sum = $sum->add($address->getAmountQuantity());
        }

        Log::debug("sumAddressBalances \$sum=".json_encode($sum, 192));
        return $sum;
    }
}