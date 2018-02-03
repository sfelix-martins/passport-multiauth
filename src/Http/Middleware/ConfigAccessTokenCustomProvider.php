<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\CreatesUserProviders;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use SMartins\PassportMultiauth\ProviderRepository;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class ConfigAccessTokenCustomProvider
{
    use CreatesUserProviders;

    private $app;

    private $server;

    private $providers;

    public function __construct(ResourceServer $server, ProviderRepository $providers, App $app)
    {
        $this->server = $server;
        $this->providers = $providers;
        $this->app = App::getFacadeRoot();
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $psr = (new DiactorosFactory())->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);

            $tokenId = $psr->getAttribute('oauth_access_token_id');

            if (!$tokenId) {
                return $next($request);
            }

            $accessToken = $this->providers->findForToken($tokenId);

            if (!$accessToken) {
                return $next($request);
            }

            // Check if has on provider users with same id of access_token owner.
            $entities = $this->entitiesWithSameIdOnProviders(
                $psr->getAttribute('oauth_user_id')
            );

            // If just one entity a register with this id follow the flow
            if (!$entities->count() > 1) {
                return $next($request);
            }

            // Get the auth guard if has to check the default guard
            $guards = $this->getAuthGuards($request);

            // If don't has any guard follow the flo
            if (count($guards) == 0) {
                return $next($request);
            }

            // If has one or more than guards set the api provider used to
            // choose provider on \Laravel\Passport\Bridge\UserRepository::getUserEntityByUserCredentials.
            // When has one or more guards the api provider will receive the
            // provider of token stored on oauth_access_token_providers.
            config(['auth.guards.api.provider' => $accessToken->provider]);

            if (count($guards) > 1) {
                return $next($request);
            }

            // If has users with same id and the accessToken provider is different
            // of the default provider return unauthenticated.
            if ($accessToken->provider != $this->defaultGuardProvider($guards[0])) {
                throw new AuthenticationException('Unauthenticated', $guards);
            }
        } catch (OAuthServerException $e) {
            //
        }

        return $next($request);
    }

    public function getAuthGuards(Request $request)
    {
        $middlewares = $request->route()->middleware();

        $guards = [];
        foreach ($middlewares as $middleware) {
            if (Str::startsWith($middleware, 'auth')) {
                $explodedGuards = explode(',', Str::after($middleware, ':'));
                $guards = array_unique(array_merge($guards, $explodedGuards));
            }
        }

        return $guards;
    }

    public function entitiesWithSameIdOnProviders($id): Collection
    {
        $providers = array_keys(config('auth.providers'));
        $entities = collect([]);
        foreach ($providers as $provider) {
            $entities->push($this->findEntityOnProvider($provider, $id));
        }

        return $entities;
    }

    public function defaultGuardProvider(string $guard)
    {
        return config('auth.guards.'.$guard.'.provider');
    }

    public function findEntityOnProvider($provider, $userId)
    {
        $userProvider = $this->createUserProvider($provider);

        return $userProvider->retrieveById($userId);
    }
}
