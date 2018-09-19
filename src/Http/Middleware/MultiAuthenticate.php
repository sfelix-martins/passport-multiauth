<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Psr\Http\Message\ServerRequestInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as Auth;
use SMartins\PassportMultiauth\PassportMultiauth;
use SMartins\PassportMultiauth\Provider as Token;
use SMartins\PassportMultiauth\ProviderRepository;
use SMartins\PassportMultiauth\Guards\GuardChecker;
use SMartins\PassportMultiauth\Facades\ServerRequest;
use SMartins\PassportMultiauth\Config\AuthConfigHelper;
use League\OAuth2\Server\Exception\OAuthServerException;

class MultiAuthenticate extends Authenticate
{
    /**
     * @var ResourceServer
     */
    protected $server;

    /**
     * @var ProviderRepository
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
     * @param \Closure $next
     * @param string[] ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     * @throws \SMartins\PassportMultiauth\Exceptions\MissingConfigException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // If don't has any guard follow the flow
        if (empty($guards)) {
            $this->authenticate($request, $guards);

            // Stop laravel from checking for a token if session is not set
            return $next($request);
        }

        $psrRequest = ServerRequest::createRequest($request);

        try {
            $psrRequest = $this->server->validateAuthenticatedRequest($psrRequest);

            if (! ($accessToken = $this->getAccessTokenFromRequest($psrRequest))) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            $guard = $this->getTokenGuard($accessToken, $guards);

            $this->authenticate($request, $guard);
        } catch (OAuthServerException $e) {
            // If has an OAuthServerException check if has unit tests and fake
            // user authenticated.
            if (($user = PassportMultiauth::userActing()) &&
                $this->canBeAuthenticated($user, $guards)
            ) {
                return $next($request);
            }

            // @todo Check if it's the best way to handle with OAuthServerException
            throw new AuthenticationException('Unauthenticated', $guards);
        }

        return $next($request);
    }

    /**
     * @param ServerRequestInterface $request
     * @return null|Token
     */
    public function getAccessTokenFromRequest(ServerRequestInterface $request)
    {
        if (! ($tokenId = $request->getAttribute('oauth_access_token_id'))) {
            return;
        }

        return $this->providers->findForToken($tokenId);
    }

    /**
     * Check if user acting has the required guards and scopes on request.
     *
     * @param Authenticatable $user
     * @param  array $guards
     * @return bool
     * @throws \SMartins\PassportMultiauth\Exceptions\MissingConfigException
     */
    public function canBeAuthenticated(Authenticatable $user, $guards)
    {
        $userGuard = AuthConfigHelper::getUserGuard($user);

        return in_array($userGuard, $guards);
    }

    /**
     * Get guard related with token.
     *
     * @param Token $token
     * @param $guards
     * @return array
     */
    public function getTokenGuard(Token $token, $guards)
    {
        $providers = GuardChecker::getGuardsProviders($guards);

        // use only guard associated to access token provider
        return $providers->has($token->provider) ? [$providers->get($token->provider)] : [];
    }
}
