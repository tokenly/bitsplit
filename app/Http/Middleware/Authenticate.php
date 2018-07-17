<?php

namespace App\Http\Middleware;

use Closure, Session;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        if (Auth::guard($guard)->guest()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response('Unauthorized.', 401);
            } else {
                return redirect()->guest(route('account.auth'));
            }
        }

        if(Auth::guard($guard)->user()) {
            $user = Auth::guard($guard)->user();
            $user_tac_accept = $user->checkTACAccept();
            if(!$user_tac_accept) {
                Session::put('return_route_after_tac', url()->full());
                return redirect()->route('terms-and-conditions');
            }
        }

        return $next($request);
    }
}
