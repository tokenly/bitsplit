<?php
use Tokenly\TokenGenerator\TokenGenerator;
class APIKey extends Eloquent
{
	protected $table = 'api_keys';
    
    public static function generate(User $user)
    {
        $token_gen = new TokenGenerator;
        $key = new APIKey;
        $key->user_id = $user->id;
        $key->client_key = $token_gen->generateToken(16, 'T');
        $key->client_secret = $token_gen->generateToken(40, 'K');
        $key->active = 1;
        
        return $key->save();
    }
    
}
