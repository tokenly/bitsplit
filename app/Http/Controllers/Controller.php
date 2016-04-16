<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    
    protected function return_with_message($route, $message, $class)
    {
		\Session::flash('message', $message);
		\Session::flash('message-class', $class);
		return \Redirect::route($route);
	}
    
    protected function return_error($route, $message)
    {
		return $this->return_with_message($route, $message, 'alert-danger');
	}
	
	protected function return_success($route, $message)
	{
		return $this->return_with_message($route, $message, 'alert-success');
	}
	
	protected function return_warning($route, $message)
	{
		return $this->return_with_message($route, $message, 'alert-warning');
	}
	
	protected function return_info($route, $message)
	{
		return $this->return_with_message($route, $message, 'alert-info');
	}	
    
}
