<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AddCustomProvider
{
    /**
     * The default provder of api guard.
     *
     * @var string
     */
    protected $defaultApiProvider;

    /**
     * Handle an incoming request. Set the `provider` from `api` guard using a
     * parameter `provider` coming from request. The provider on `apì` guard
     * is used by Laravel Passport to get the correct model on access token
     * creation.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->defaultApiProvider = config('auth.guards.api.provider');

        $params = $request->all();
        if (array_key_exists('provider', $params)) {
            if (! is_null($params['provider'])) {
                config(['auth.guards.api.provider' => $params['provider']]);
            }
        }

        return $next($request);
    }

    public function terminate($request, $response)
    {
        // Reset config provider to default after complete request.
        config(['auth.guards.api.provider' => $this->defaultApiProvider]);
    }
}
