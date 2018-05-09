<?php

namespace SMartins\PassportMultiauth\Testing;

use Laravel\Passport\Client;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait MultiauthActions
{
    /**
     * The route to generate the access token. The default value is the standard
     * route from Laravel\Passport.
     *
     * @var string
     */
    protected $oauthTokenRoute = 'oauth/token';

    /**
     * @codeCoverageIgnore
     */
    public function setUp()
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * Set the the Authorization header with an access token created using
     * Laravel Passport. The method `ẁithHeader` of trait `MakesHttpRequests` is
     * avaiable after version 5.5 of Laravel Framework.
     *
     * @todo Change way to issue token from $this->json() to creating accessing
     *       AccessTokenController@issueToken directly.
     * @todo Pass this method to PassportMultiauth::actingAs().
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string $scope
     * @return $this
     */
    public function multiauthActingAs(Authenticatable $user, $scope = '')
    {
        if ((float) App::version() < 5.5) {
            throw new \RuntimeException('The method is only available to Laravel >= 5.5. To older versions try use $this->multiauthAccessToken() to get access token.');
        }

        $this->withHeader('Authorization', $this->multiauthAccessToken($user, $scope));

        return $this;
    }

    /**
     * Get multiauth header to be used on request to get access token.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  string $scope
     * @return string
     */
    public function multiauthAccessToken(Authenticatable $user, $scope = '')
    {
        // @todo Change to specific repository
        $client = Client::where('personal_access_client', false)
                        ->where('revoked', false)
                        ->first();

        if (! $client) {
            throw new ModelNotFoundException('Laravel\Passport password grant not found. Please run `passport:install` to generate client.');
        }

        $provider = $this->getUserProvider($user);

        $params = [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $user->email,
            'password' => 'secret',
            'scope' => $scope,
        ];

        // If model to be authenticated don't is the default provider
        if (! $this->isDefaultProvider($provider)) {
            $params = array_merge($params, ['provider' => $provider]);
        }

        $response = $this->json('POST', $this->oauthTokenRoute, $params);

        $accessToken = json_decode($response->getContent())->access_token;

        return 'Bearer '.$accessToken;
    }

    /**
     * Get the user provider on configs.
     *
     * @todo Move to class specialized in check auth configs.
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return string
     */
    protected function getUserProvider(Authenticatable $user)
    {
        $provider = '';
        foreach (config('auth.providers') as $p => $config) {
            if ($user instanceof $config['model']) {
                $provider = $p;
            }
        }

        return $provider;
    }

    /**
     * Check if provider is the default provider used by Laravel\Passport.
     *
     * @todo Move to class specialized in check auth configs.
     * @param string $provider
     * @return bool
     */
    protected function isDefaultProvider(string $provider)
    {
        return config('auth.guards.api.provider') === $provider;
    }
}
