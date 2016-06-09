<?php
namespace App\Http\Middleware;
use Closure, Response, APIKey, User;
 
class AuthenticateAPIKey {
 
    /**
    * Handle an incoming request.
    *
    * @param \Illuminate\Http\Request $request
    * @param \Closure $next
    * @return mixed
    */
    public function handle($request, Closure $next) {

        if($request->input('key')){
            $key = $request->input('key');
            $getKey = APIKey::where('client_key', $key)->where('active', 1)->first();
            if($getKey){
                \App\Http\Controllers\APIController::$api_user = User::find($getKey->user_id);
                return $next($request);
            }
        }
        return Response::json(array('error' => 'Valid API Client Key required'), 403);
    }

}
