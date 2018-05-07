<?php

namespace SMartins\PassportMultiauth\Testing;

use Laravel\Passport\Client;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;

trait MultiauthActions
{
    use MakesHttpRequests, InteractsWithConsole;

    /**
     * The route to generate the access token. The default value is the standard
     * route from Laravel\Passport.
     *
     * @var string
     */
    protected $oauthTokenRoute = 'oauth/token';

    public function setUp()
    {
        parent::setUp();

        $this->artisan('passport:install');
    }

    /**
     * Set the the Authorization header with an access token created using
     * Laravel Passport.
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
        $client = Client::where('personal_access_client', false)
                        ->where('revoked', false)
                        ->first();

        $provider = '';
        foreach (config('auth.providers') as $p => $config) {
            if ($user instanceof $config['model']) {
                $provider = $p;
            }
        }

        $params = [
            'grant_type' => 'password',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'username' => $user->email,
            'password' => 'secret',
            'scope' => $scope,
        ];

        // If model to be authenticated don't has the default provider
        if (config('auth.guards.api.provider') !== $provider) {
            $params = array_merge($params, ['provider' => $provider]);
        }

        $response = $this->json('POST', $this->oauthTokenRoute, $params);
        $accessToken = json_decode($response->original)->access_token;

        $this->withHeader('Authorization', 'Bearer '.$accessToken);

        return $this;
    }
}
