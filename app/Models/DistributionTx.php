<?php
namespace Models;

use Illuminate\Database\Eloquent\Model;

class DistributionTx extends Model
{
	
	protected $table = 'distribution_tx';
    
    public static $api_fields = array('destination', 'quantity', 'utxo', 'txid', 'confirmed', 'updated_at');

    protected static $unguarded = true;
	
	public function distribution()
	{
		return $this->hasOne('Distribution', 'id', 'distribution_id');
	}
	
}
