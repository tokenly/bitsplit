<?php
namespace Models;

use Illuminate\Database\Eloquent\Model;

class DistributionTx extends Model
{
	
	public function distribution()
	{
		return $this->hasOne('Distribution', 'id', 'distribution_id');
	}
	
}
