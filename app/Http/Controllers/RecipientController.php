<?php

namespace App\Http\Controllers;

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
}
