<?php

namespace App\Distribute\Stages;

class Stage
{
	public $distro = false;
	
	function __construct($distro)
	{
		$this->distro = $distro;
		
	}
	
	public function init()
	{
		
		
	}
    
	
}
