<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Laravel\Passport\Exceptions\MissingScopeException;

class MultiAuthCheckForAnyScope
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$scopes
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\AuthenticationException|\Laravel\Passport\Exceptions\MissingScopeException
     */
    public function handle($request, $next, ...$scopes)
    {
        if (! $request->user('api') || ! $request->user('api')->token()) {
            throw new AuthenticationException;
        }

        foreach ($scopes as $scope) {
            if ($request->user('api')->tokenCan($scope)) {
                return $next($request);
            }
        }

        throw new MissingScopeException($scopes);
    }
}
