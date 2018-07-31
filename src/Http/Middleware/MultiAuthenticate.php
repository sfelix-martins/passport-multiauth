<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\AuthenticationException;
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
     * Create a new middleware instance.
     *
     * @param ResourceServer $server
     * @param ProviderRepository $providers
     * @param Auth $auth
     */
    public function __construct(
        ResourceServer $server,
        ProviderRepository $providers,
        Auth $auth
    ) {
        parent::__construct($auth);

        $this->server = $server;
        $this->providers = $providers;
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
            $this->authenticate($guards);
            
            return $next($request);
        }

        $psrRequest = ServerRequest::createRequest($request);

        try {
            $psrRequest = $this->server->validateAuthenticatedRequest($psrRequest);

            if (! $tokenId = $psrRequest->getAttribute('oauth_access_token_id')) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            if (! $accessToken = $this->providers->findForToken($tokenId)) {
                throw new AuthenticationException('Unauthenticated', $guards);
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
            throw new AuthenticationException('Unauthenticated', $guards);
        }

        return $next($request);
    }

    /**
     * Check if user acting has the required guards and scopes on request.
     *
     * @param Authenticatable $user
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
     * @return mixed
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
