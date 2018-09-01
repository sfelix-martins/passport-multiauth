<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use League\OAuth2\Server\Exception\OAuthServerException;

class AddCustomProvider
{
    /**
     * The default provider of api guard.
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
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     *
     * @return mixed
     *
     * @throws OAuthServerException
     */
    public function handle(Request $request, Closure $next)
    {
        $this->defaultApiProvider = config('auth.guards.api.provider');

        $provider = $request->get('provider');

        if ($this->invalidProvider($provider)) {
            throw OAuthServerException::invalidRequest('provider');
        }

        config(['auth.guards.api.provider' => $provider]);

        return $next($request);
    }

    /**
     * Reset config provider to default after complete request. If necessary
     * can receive $request and $response params. To be used the attribute
     * $this->defaultApiProvider the middleware was registered on ServiceProvider
     * as a singleton.
     * Read more in https://laravel.com/docs/5.6/middleware#terminable-middleware.
     *
     * @return void
     */
    public function terminate()
    {
        config(['auth.guards.api.provider' => $this->defaultApiProvider]);
    }

    /**
     * Check if the given provider is not registered in the auth configuration file.
     *
     * @param $provider
     *
     * @return bool
     */
    protected function invalidProvider($provider)
    {
        if (is_null($provider)) {
            return true;
        }

        foreach (config('auth.guards') as $guardsConfiguration) {
            if ($guardsConfiguration['provider'] === $provider) {
                return false;
            }
        }

        return true;
    }
}
