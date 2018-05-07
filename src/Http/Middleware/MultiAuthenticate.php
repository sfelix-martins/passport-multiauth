<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Factory as Auth;
use SMartins\PassportMultiauth\ProviderRepository;
use SMartins\PassportMultiauth\Guards\GuardChecker;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class MultiAuthenticate extends Authenticate
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * @var \League\OAuth2\Server\ResourceServer
     */
    private $server;

    /**
     * @var \SMartins\PassportMultiauth\ProviderRepository
     */
    private $providers;

    public function __construct(ResourceServer $server, ProviderRepository $providers, Auth $auth)
    {
        $this->server = $server;
        $this->providers = $providers;
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request. Authenticates the guard from access token
     * used on request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param string[]                 ...$guards
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
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

            if (! $tokenId) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            $accessToken = $this->providers->findForToken($tokenId);

            if (! $accessToken) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            $providers = collect($guards)->mapWithKeys(function ($guard) {
                return [GuardChecker::defaultGuardProvider($guard) => $guard];
            });

            // use only guard associated to access token provider
            if ($providers->has($accessToken->provider)) {
                $this->authenticate([$providers->get($accessToken->provider)]);
            } else {
                $this->authenticate([]);
            }

            return $next($request);
        } catch (OAuthServerException $e) {
            // @todo Check if it's the best way to handle with OAuthServerException
            throw new AuthenticationException('Unauthenticated', $guards);
        }
    }
}
