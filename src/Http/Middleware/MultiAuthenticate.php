<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use League\OAuth2\Server\ResourceServer;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Factory as Auth;
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
        if (count($guards) === 0) {
            return $next($request);
        }

        $psrRequest = (new DiactorosFactory())->createRequest($request);

        try {
            $psrRequest = $this->server->validateAuthenticatedRequest($psrRequest);

            $tokenId = $psrRequest->getAttribute('oauth_access_token_id');

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
            // @todo It's the best place to this code???
            //
            // If running unit test and try authenticate an user with
            // `PassportMultiauth::actingAs($user) check the guards on request
            // to authenticate or not the user.
            $user = app('auth')->user();

            if (App::runningUnitTests() && $user) {
                // @todo Move to method
                $guards = GuardChecker::getAuthGuards($request);

                $userGuard = PassportMultiauth::getUserGuard($user);

                if (! in_array($userGuard, $guards)) {
                    throw new AuthenticationException('Unauthenticated', $guards);
                }

                return $next($request);
            }

            // @todo Check if it's the best way to handle with OAuthServerException
            throw new AuthenticationException('Unauthenticated', $guards);
        }
    }
}
