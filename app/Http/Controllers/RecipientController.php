<?php

namespace App\Http\Controllers;

use App\Jobs\ExecuteWithdrawal;
use App\Libraries\Withdrawal\RecipientWithdrawalManager;
use App\Libraries\Withdrawal\WithdrawalFeeManager;
use Auth;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tokenly\LaravelEventLog\Facade\EventLog;
use User;

class RecipientController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index(RecipientWithdrawalManager $recipient_withdrawal_manager)
    {
        $user = Auth::user();
        $addresses = $recipient_withdrawal_manager->getAddressesForUserWithBalances($user);

        // filter addresses without FLDC
        $addresses_with_balances = collect($addresses)->filter(function($address) {
            if ($address['balances']['FLDC'] ?? false) {
                return true;
            }
            if ($address['balances']['TESTFLDC'] ?? false) {
                return true;
            }
            return false;
        });

        return view('recipient.dashboard', [
            'user' => $user,
            'addresses' => $addresses_with_balances,
        ]);
    }

    public function withdraw(Request $request, RecipientWithdrawalManager $recipient_withdrawal_manager, WithdrawalFeeManager $withdrawal_fee_manager)
    {
        $user = Auth::user();
        $addresses = $recipient_withdrawal_manager->getAddressesForUserWithBalances($user);

        $default_blockchain_address = $request->get('address');

        return view('recipient.withdraw', [
            'user' => $user,
            'addresses' => $addresses,
            'default_blockchain_address' => $default_blockchain_address,
            'fee_quote' => $withdrawal_fee_manager->getLatestFeeQuote(),
        ]);
    }

    public function processWithdraw(Request $request, RecipientWithdrawalManager $recipient_withdrawal_manager)
    {
        $user = Auth::user();

        // validate token address
        $address = $request->input('blockchain_address');
        if (!$address) {
            return $this->return_error('recipient.withdraw', "Please select an address to withdraw your tokens.");
        }

        // validate confirm checkbox
        $confirm = $request->input('confirm');
        if (!$confirm) {
            return $this->return_error('recipient.withdraw', "You must select the confirmation checkbox to withdraw your tokens.");
        }

        // validate tokens exist
        $balance = $recipient_withdrawal_manager->getPromisedBalanceForUser($user, $address, FLDCAssetName());
        if ($balance === null or $balance == 0) {
            return $this->return_error('recipient.withdraw', "This address does not have any ".FLDCAssetName()." available.");
        }

        // schedule the job
        ExecuteWithdrawal::dispatch($address);

        EventLog::debug('withdrawal.scheduled', [
            'user' => $user['id'],
            'address' => $address,
        ]);

        return view('recipient.withdraw-confirmation', [
        	'user' => $user,
            'address' => $address,
        ]);
    }
}
