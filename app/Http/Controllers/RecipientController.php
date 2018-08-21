<?php

namespace App\Http\Controllers;

use App\Libraries\Withdrawal\RecipientWithdrawalManager;
use Auth;
use User;

class RecipientController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $user = Auth::user();

        return view('recipient.dashboard', array('user' => $user));
    }

    public function withdraw(RecipientWithdrawalManager $recipient_withdrawal_manager)
    {
        $user = Auth::user();
        $addresses = $recipient_withdrawal_manager->getAddressesForUser($user);

        return view('recipient.withdraw', [
        	'user' => $user,
        	'addresses' => $addresses,
        ]);
    }
}
