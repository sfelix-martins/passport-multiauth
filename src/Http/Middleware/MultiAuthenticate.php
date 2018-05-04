<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use SMartins\PassportMultiauth\Guards\GuardChecker;
use SMartins\PassportMultiauth\ProviderRepository;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class MultiAuthenticate
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    private $app;

    /**
     * @var \League\OAuth2\Server\ResourceServer
     */
    private $server;

    /**
     * @var \SMartins\PassportMultiauth\ProviderRepository
     */
    private $providers;

    public function __construct(ResourceServer $server, ProviderRepository $providers, App $app, Auth $auth)
    {
        $this->server = $server;
        $this->providers = $providers;
        $this->app = App::getFacadeRoot();
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string[]                 ...$guards
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        // Get the auth guard if has to check the default guard
        $guards = GuardChecker::getAuthGuards($request);

        // If don't has any guard follow the flow
        if (0 === count($guards)) {
            return $next($request);
        }

        $psr = (new DiactorosFactory())->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);

            $tokenId = $psr->getAttribute('oauth_access_token_id');

            if (!$tokenId) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            $accessToken = $this->providers->findForToken($tokenId);

            if (!$accessToken) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            // use only guard associated to access token
            if (in_array($accessToken->provider, $guards, true)) {
                $this->authenticate([$accessToken->provider]);
            } else {
                $this->authenticate([]);
            }

            return $next($request);
        } catch (OAuthServerException $e) {
        }

        return $next($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param array $guards
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate(array $guards)
    {
        if (empty($guards)) {
            return $this->auth->authenticate();
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        throw new AuthenticationException('Unauthenticated.', $guards);
    }
}
