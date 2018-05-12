<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Authenticatable;
use SMartins\PassportMultiauth\Provider as Token;
use SMartins\PassportMultiauth\PassportMultiauth;
use SMartins\PassportMultiauth\ProviderRepository;
use SMartins\PassportMultiauth\Guards\GuardChecker;
use League\OAuth2\Server\Exception\OAuthServerException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

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
     *
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // If don't has any guard follow the flow
        if (empty($guards)) {
            return $next($request);
        }

        $psrRequest = (new DiactorosFactory())->createRequest($request);

        try {
            $psrRequest = $this->server->validateAuthenticatedRequest($psrRequest);

            if (! $tokenId = $psrRequest->getAttribute('oauth_access_token_id')) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            if (! $accessToken = $this->providers->findForToken($tokenId)) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }

            $this->authenticateTokenGuard($accessToken, $guards);

            return $next($request);
        } catch (OAuthServerException $e) {
            // @todo It's the best place to this code???
            if ($user = PassportMultiauth::userActing()) {
                if (! $this->canBeAuthenticated($user, $request)) {
                    throw new AuthenticationException('Unauthenticated', $guards);
                }

                return $next($request);
            }

            // @todo Check if it's the best way to handle with OAuthServerException
            throw new AuthenticationException('Unauthenticated', $guards);
        }
    }

    /**
     * Check if user acting has the required guards and scopes on request.
     *
     * @param  \Illuminate\Foundation\Auth\User $user
     * @param  \Illuminate\Http\Request $request
     * @return bool
     */
    public function canBeAuthenticated(Authenticatable $user, Request $request)
    {
        $guards = GuardChecker::getAuthGuards($request);

        $userGuard = PassportMultiauth::getUserGuard($user);

        return in_array($userGuard, $guards);
    }

    /**
     * Authenticate correct guard based on token.
     *
     * @param \SMartins\PassportMultiauth\Provider $token
     * @param  array $guards
     * @return void
     */
    public function authenticateTokenGuard(Token $token, $guards)
    {
        $providers = collect($guards)->mapWithKeys(function ($guard) {
            return [GuardChecker::defaultGuardProvider($guard) => $guard];
        });

        // use only guard associated to access token provider
        $authGuards = $providers->has($token->provider) ? [$providers->get($token->provider)] : [];

        $this->authenticate($authGuards);
    }
}
