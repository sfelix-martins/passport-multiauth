<?php

namespace SMartins\PassportMultiauth\Http\Middleware;

use DB;
use Closure;
use Illuminate\Http\Request;
use League\OAuth2\Server\ResourceServer;
use SMartins\PassportMultiauth\ProviderRepository;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class ConfigAccessTokenCustomProvider
{
    private $server;

    private $providers;

    public function __construct(ResourceServer $server, ProviderRepository $providers)
    {
        $this->server = $server;
        $this->providers = $providers;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $psr = (new DiactorosFactory)->createRequest($request);

        try {
            $psr = $this->server->validateAuthenticatedRequest($psr);
            $tokenId = $psr->getAttribute('oauth_access_token_id');
            if ($tokenId) {
                $accessToken = $this->providers->findForToken($tokenId);

                if ($accessToken) {
                    config(['auth.guards.api.provider' => $accessToken->provider]);
                }
            }
        } catch (\Exception $e) {
            //
        }

        return $next($request);
    }
}
