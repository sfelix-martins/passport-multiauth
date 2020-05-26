<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as Auth;
use SMartins\PassportMultiauth\PassportMultiauth;
use SMartins\PassportMultiauth\Provider as Token;
use SMartins\PassportMultiauth\ProviderRepository;
use SMartins\PassportMultiauth\Guards\GuardChecker;
use SMartins\PassportMultiauth\Facades\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;

class MultiAuthenticate extends Authenticate
{
    /**
     * @var \League\OAuth2\Server\ResourceServer
     */
    protected $server;

    /**
     * @var \SMartins\PassportMultiauth\ProviderRepository
     */
    protected $providers;

    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

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
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // If don't has any guard follow the flow
        if (empty($guards)) {
            return $next($request);
        }

        $psrRequest = ServerRequest::createRequest($request);

        try {
            $psrRequest = $this->server->validateAuthenticatedRequest($psrRequest);

            if (! $tokenId = $psrRequest->getAttribute('oauth_access_token_id')) {
                $this->unauthenticated($request, $guards);
            }

            if (! $accessToken = $this->providers->findForToken($tokenId)) {
                $this->unauthenticated($request, $guards);
            }

            $this->authenticateTokenGuard($accessToken, $guards);
        } catch (OAuthServerException $e) {
            // If has an OAuthServerException check if has unit tests and fake
            // user authenticated.
            if ($user = PassportMultiauth::userActing()) {
                if ($this->canBeAuthenticated($user, $guards)) {
                    return $next($request);
                }
            }

            // @todo Check if it's the best way to handle with OAuthServerException
            $this->unauthenticated($request, $guards);
        }

        return $next($request);
    }

    /**
     * Check if user acting has the required guards and scopes on request.
     *
     * @param  \Illuminate\Foundation\Auth\User $user
     * @param  array $guards
     * @return bool
     */
    public function canBeAuthenticated(Authenticatable $user, $guards)
    {
        $userGuard = PassportMultiauth::getUserGuard($user);

        return in_array($userGuard, $guards);
    }

    /**
     * Authenticate correct guard based on token.
     *
     * @param \SMartins\PassportMultiauth\Provider $token
     * @param  array $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function authenticateTokenGuard(Token $token, $guards)
    {
        $providers = GuardChecker::getGuardsProviders($guards);

        // use only guard associated to access token provider
        $authGuards = $providers->has($token->provider) ? [$providers->get($token->provider)] : [];

        return $this->authenticate($authGuards);
    }
}
