<?php 
namespace App\Http\Controllers;
use User, Auth, Config;
class HomeController extends Controller {
    
	public function __construct()
	{
        parent::__construct();
		$this->middleware('auth');
	}


	public function index()
	{
		$user = Auth::user();

		return view('home', array('user' => $user));
	}
}
