<?php
namespace Distribute;
use Models\Distribution as Distro;
use Log, Exception;
use Tokenly\TokenpassClient\TokenpassAPI;

class Initialize
{
	public function init($distro)
	{
		$this->startMonitor($distro);
        $this->registerToTokenpassProvisionalWhitelist($distro);
	}
	
	public function startMonitor($distro, $first_stage = true, $force = false)
	{
        if(!$force and $distro->stage > 0){
            return false;
        }

        // substation automatically monitors all allocated addresses
        //   so we don't need to explictly create a monitor

        // mark the distribution as entering hte first stage
		if($first_stage){
			$distro->stage = 1;
    		$distro->save();
        }

		Log::info('Started distro for #'.$distro->id);
		return true;

	}
	
	public function stopMonitor($distro)
	{
        // substation does not have a method to close down monitoring of allocated addresses
		Log::info('Stopped distro receive monitor for #'.$distro->id);

		return true;
	}
    
    public function registerToTokenpassProvisionalWhitelist($distro)
    {
        $tokenpass = app(TokenpassAPI::class);
        try{
            $register = $tokenpass->registerProvisionalSource($distro->deposit_address, null, $distro->asset);
        }
        catch(Exception $e){
            Log::error('Error registering distro #'.$distro->id.' to Tokenpass provisional source whitelist: '.$e->getMessage());
            return false;
        }
        if(!$register){
            Log::error('Failed registering distro #'.$distro->id.' to Tokenpass provisional source whitelist');
            return false;
        }
        Log::info('Registered distro #'.$distro->id.' to Tokenpass provisional whitelist');
        return true;
    }
    
    public function deleteFromTokenpassProvisionalWhitelist($distro)
    {
        $tokenpass = app(TokenpassAPI::class);
        try{
            $delete = $tokenpass->deleteProvisionalSource($distro->deposit_address);
        }
        catch(Exception $e){
            Log::error('Error deleting distro #'.$distro->id.' from Tokenpass provisional source whitelist: '.$e->getMessage());
            return false;
        }
        if(!$delete){
            Log::error('Failed deleting distro #'.$distro->id.' from Tokenpass provisional source whitelist');
            return false;
        }
        Log::info('Deleted distro #'.$distro->id.' from Tokenpass provisional whitelist');
        return true; 
    }
    
}
