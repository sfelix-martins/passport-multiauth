<?php

namespace SMartins\PassportMultiauth\Tests\Feature;

use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;
use SMartins\PassportMultiauth\Tests\TestCase;
use SMartins\PassportMultiauth\PassportMultiauth;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;

class MultiauthTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->artisan('key:generate');

        $this->artisan('passport:install');

        $this->withFactories(__DIR__.'/../Fixtures/factories');

        $this->setAuthConfigs();

        $this->setUpRoutes();
    }

    /**
     * Create routes to tests authentication with guards and auth middleware.
     *
     * @return void
     */
    public function setUpRoutes()
    {
        Route::group(['middleware' => AddCustomProvider::class], function () {
            Passport::routes(function ($router) {
                return $router->forAccessTokens();
            });
        });

        Route::middleware('auth:api')->get('/user', function (Request $request) {
            return $request->user();
        });

        Route::middleware('auth:company')->get('/company', function (Request $request) {
            return $request->user();
        });

        Route::middleware('auth:api,company')->get('/users', function (Request $request) {
            return [
                $request->user('api'),
                $request->user('company'),
                $request->user(),
                Auth::user(),
                Auth::guard('api')->user(),
                Auth::guard('company')->user(),
                Auth::check(),
                Auth::id(),
            ];
        });

        Route::middleware('auth:api')->get('/just_user', function (Request $request) {
            return [
                $request->user('api'),
                $request->user('company'),
                $request->user(),
                Auth::user(),
                Auth::guard('api')->user(),
                Auth::guard('company')->user(),
                Auth::check(),
                Auth::id(),
            ];
        });

        Route::middleware('auth:company')->get('/just_company', function (Request $request) {
            return [
                $request->user('api'),
                $request->user('company'),
                $request->user(),
                Auth::user(),
                Auth::guard('api')->user(),
                Auth::guard('company')->user(),
                Auth::check(),
                Auth::id(),
            ];
        });

        Route::middleware('auth')->get('/no_guards', function (Request $request) {
            return $request->user();
        });
    }

    /**
     * @test
     */
    public function it_will_return_401_when_try_access_route_with_company_guard_as_user()
    {
        $configs = [
            function () {
                config(['auth.defaults.guard' => 'company']);
            },
            function () {
                config(['auth.defaults.guard' => 'api']);
            },
            function () {
                config(['auth.defaults.guard' => 'web']);
            }
        ];

        foreach ($configs as $config) {
            $config();

            // Two different models with same id.
            $user    = factory(User::class)->create();
            $company = factory(Company::class)->create();

            $this->assertEquals($user->getKey(), $company->getKey());

            $client = Client::query()
                ->where(['password_client' => 1, 'revoked' => 0])
                ->first();

            $params = [
                'grant_type' => 'password',
                'username' => $user->email,
                'password' => 'secret',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'provider' => 'users',
            ];

            $response = $this->json('POST', '/oauth/token', $params);

            $accessToken = json_decode($response->getContent(), true)['access_token'];

            $this->json('GET', '/just_company', [], ['Authorization' => 'Bearer '.$accessToken])
                ->assertStatus(401);
        }
    }

    /**
     * @test
     */
    public function it_will_return_401_when_try_access_route_with_user_guard_as_company()
    {
        $configs = [
            function () {
                config(['auth.defaults.guard' => 'company']);
            },
            function () {
                config(['auth.defaults.guard' => 'api']);
            },
            function () {
                config(['auth.defaults.guard' => 'web']);
            }
        ];

        foreach ($configs as $config) {
            $config();

            // Two different models with same id.
            $user    = factory(User::class)->create();
            $company = factory(Company::class)->create();

            $this->assertEquals($user->getKey(), $company->getKey());

            $client = Client::query()
                ->where(['password_client' => 1, 'revoked' => 0])
                ->first();

            $params = [
                'grant_type' => 'password',
                'username' => $company->email,
                'password' => 'secret',
                'client_id' => $client->id,
                'client_secret' => $client->secret,
                'provider' => 'companies',
            ];

            $response = $this->json('POST', '/oauth/token', $params);

            $accessToken = json_decode($response->getContent(), true)['access_token'];

            $this->json('GET', '/just_user', [], ['Authorization' => 'Bearer '.$accessToken])
                ->assertStatus(401);
        }
    }

    /**
     * @test
     */
    public function it_will_return_user_instance_just_with_correct_guard()
    {
        // Two different models with same id.
        $user = factory(User::class)->create();
        factory(Company::class)->create();

        $client = (new Client())
            ->where(['password_client' => 1, 'revoked' => 0])
            ->first();

        $params = [
            'grant_type' => 'password',
            'username' => $user->email,
            'password' => 'secret',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'provider' => 'users',
        ];

        $response = $this->json('POST', '/oauth/token', $params);

        $accessToken = json_decode($response->getContent(), true)['access_token'];

        $response = $this->json('GET', '/just_user', [], ['Authorization' => 'Bearer '.$accessToken]);

        $original = $response->getOriginalContent();

        $this->assertInstanceOf(User::class, $original[0]);
        $this->assertNull($original[1]);
        $this->assertInstanceOf(User::class, $original[2]);
        $this->assertInstanceOf(User::class, $original[3]);
        $this->assertInstanceOf(User::class, $original[4]);
        $this->assertNull($original[5]);
        $this->assertTrue($original[6]);
        $this->assertEquals($user->id, $original[7]);
    }

    /**
     * @test
     */
    public function it_will_return_company_instance_just_with_correct_guard()
    {
        // Two different models with same id.
        factory(User::class)->create();
        $company = factory(Company::class)->create();

        $client = (new Client())
            ->where(['password_client' => 1, 'revoked' => 0])
            ->first();

        $params = [
            'grant_type' => 'password',
            'username' => $company->email,
            'password' => 'secret',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'provider' => 'companies',
        ];

        $response = $this->json('POST', '/oauth/token', $params);

        $accessToken = json_decode($response->getContent(), true)['access_token'];

        $response = $this->json('GET', '/just_company', [], ['Authorization' => 'Bearer '.$accessToken]);

        $original = $response->getOriginalContent();

        $this->assertNull($original[0]);
        $this->assertInstanceOf(Company::class, $original[1]);
        $this->assertInstanceOf(Company::class, $original[2]);
        $this->assertInstanceOf(Company::class, $original[3]);
        $this->assertNull($original[4]);
        $this->assertInstanceOf(Company::class, $original[5]);
        $this->assertTrue($original[6]);
        $this->assertEquals($company->id, $original[7]);
    }

    /**
     * @test
     */
    public function it_will_return_ways_to_get_user_logged_as_user_on_multi_guards_route()
    {
        // Two different models with same id.
        $user = factory(User::class)->create();
        factory(Company::class)->create();

        $client = (new Client())
            ->where(['password_client' => 1, 'revoked' => 0])
            ->first();

        $params = [
            'grant_type' => 'password',
            'username' => $user->email,
            'password' => 'secret',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'provider' => 'users',
        ];

        $response = $this->json('POST', '/oauth/token', $params);

        $accessToken = json_decode($response->getContent(), true)['access_token'];

        $response = $this->json('GET', '/users', [], ['Authorization' => 'Bearer '.$accessToken]);

        $original = $response->getOriginalContent();

        $this->assertInstanceOf(User::class, $original[0]);
        $this->assertNull($original[1]);
        $this->assertInstanceOf(User::class, $original[2]);
        $this->assertInstanceOf(User::class, $original[3]);
        $this->assertInstanceOf(User::class, $original[4]);
        $this->assertNull($original[5]);
        $this->assertTrue($original[6]);
        $this->assertEquals($user->id, $original[7]);
    }

    /**
     * @test
     */
    public function it_will_return_ways_to_get_user_logged_as_company_on_multi_guards_route()
    {
        // Two different models with same id.
        factory(User::class)->create();
        $company = factory(Company::class)->create();

        $client = (new Client())
            ->where(['password_client' => 1, 'revoked' => 0])
            ->first();

        $params = [
            'grant_type' => 'password',
            'username' => $company->email,
            'password' => 'secret',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'provider' => 'companies',
        ];

        $response = $this->json('POST', '/oauth/token', $params);

        $accessToken = json_decode($response->getContent(), true)['access_token'];

        $response = $this->json('GET', '/users', [], ['Authorization' => 'Bearer '.$accessToken]);

        $original = $response->getOriginalContent();

        $this->assertNull($original[0]);
        $this->assertInstanceOf(Company::class, $original[1]);
        $this->assertInstanceOf(Company::class, $original[2]);
        $this->assertInstanceOf(Company::class, $original[3]);
        $this->assertNull($original[4]);
        $this->assertInstanceOf(Company::class, $original[5]);
        $this->assertTrue($original[6]);
        $this->assertEquals($company->id, $original[7]);
    }

    public function testAuthenticateOnRouteWithoutGuardsWithInvalidToken()
    {
        $response = $this->json('GET', 'no_guards', [], ['Authorization' => 'Bearer token']);

        $this->assertInstanceOf(AuthenticationException::class, $response->exception);
    }

    public function testGetLoggedUserAsCompany()
    {
        $company = factory(Company::class)->create();

        $response = $this->sendRequest('GET', 'user', $company);

        $this->assertInstanceOf(AuthenticationException::class, $response->exception);
    }

    public function testGetLoggedCompanyAsUser()
    {
        $user = factory(User::class)->create();

        $response = $this->sendRequest('GET', 'company', $user);

        $this->assertInstanceOf(AuthenticationException::class, $response->exception);
    }

    /**
     * Send request to route with user to be authenticated.
     *
     * @param  string $method
     * @param  string $uri
     * @param  \Illuminate\Foundation\Auth\User $user
     * @return \Illuminate\Foundation\Testing\TestResponse
     * @throws \Exception
     */
    public function sendRequest($method, $uri, $user, $scopes = [])
    {
        PassportMultiauth::actingAs($user, $scopes);

        return $this->json($method, $uri);
    }
}
