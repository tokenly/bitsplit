<?php
namespace App\Http\Middleware;
use Closure, Response, APIKey, User;
 
class AuthenticateAPIKeySigned {
 
    /**
    * Handle an incoming request.
    *
    * @param \Illuminate\Http\Request $request
    * @param \Closure $next
    * @return mixed
    */
    public function handle($request, Closure $next) {

        if($request->input('key') AND $request->input('request_hash')){
            $key = $request->input('key');
            $getKey = APIKey::where('client_key', $key)->where('active', 1)->first();
            if($getKey){
                $input = $request->input();
                $request_hash = $input['request_hash'];
                unset($input['request_hash']);
                $query_string = http_build_query($input);
                $real_hash = hash('sha256', $query_string.$getKey->client_secret);
                if($real_hash === $request_hash){
                    \App\Http\Controllers\APIController::$api_user = User::find($getKey->user_id);
                    return $next($request);
                }
            }
        }
        return Response::json(array('error' => 'Valid API Client Key & Request Hash required'), 403);
    }

}
