<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddCustomProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $params = $request->all();
        if (array_key_exists('provider', $params)) {
            if (! is_null($params['provider'])) {
                config(['auth.guards.api.provider' => $params['provider']]);
            }
        }

        return $next($request);
    }
}
