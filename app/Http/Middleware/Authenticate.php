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

        // $user = Auth::guard($guard)->user();
        // if($user) {

        //     $user_account_data = $user->getCurrentUserAccountData();
        //     if(!$user_account_data)
        //     {
        //         // redirect to complete form if the user doesn't have data
        //         $route_name = $request->route()->getName();
        //         $is_on_complete_form = in_array($route_name, ['account.get_complete', 'account.complete',]);

        //         if (!$is_on_complete_form) {
        //             return redirect()->route('account.complete');
        //         }
        //     }

        //     if ($user_account_data) {
        //         // force users with account data to accept terms and conditions
        //         $user_tac_accept = $user->checkTACAccept();
        //         if(!$user_tac_accept) {
        //             Session::put('return_route_after_tac', url()->full());
        //             return redirect()->route('terms-and-conditions');
        //         }
        //     }
        // }

        return $next($request);
    }
}
