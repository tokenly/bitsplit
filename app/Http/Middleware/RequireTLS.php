<?php

namespace App\Http\Middleware;

use Closure;

class RequireTLS
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (env('USE_SSL', false) && env('APP_ENV') != 'testing' && !$request->secure()) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request); 
    }
}
