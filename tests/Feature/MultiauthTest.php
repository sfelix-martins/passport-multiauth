<?php

namespace SMartins\PassportMultiauth\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\AuthenticationException;
use SMartins\PassportMultiauth\Tests\TestCase;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\Testing\MultiauthActions;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;

class MultiauthTest extends TestCase
{
    use MultiauthActions;

    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/../Fixtures/factories');

        $this->setAuthConfigs();

        $this->setUpLaravelPassport();

        $this->setUpRoutes();
    }

    /**
     * Create routes to tests authentication with guards and auth middleware.
     *
     * @return void
     */
    public function setUpRoutes()
    {
        Route::middleware('auth:api')->get('/user', function (Request $request) {
            return $request->user();
        });

        Route::middleware('auth:company')->get('/company', function (Request $request) {
            return $request->user();
        });

        Route::middleware('auth:api,company')->get('/users', function (Request $request) {
            return get_class($request->user());
        });
    }

    public function testGetLoggedUserAsUser()
    {
        $user = factory(User::class)->create();

        $response = $this->sendRequest('GET', 'user', $user);

        $this->assertInstanceOf(User::class, $response->original);
    }

    public function testGetLoggedUserAsCompany()
    {
        $company = factory(Company::class)->create();

        $response = $this->sendRequest('GET', 'user', $company);

        $this->assertInstanceOf(AuthenticationException::class, $response->exception);
    }

    public function testGetLoggedCompanyAsCompany()
    {
        $company = factory(Company::class)->create();

        $response = $this->sendRequest('GET', 'company', $company);

        $this->assertInstanceOf(Company::class, $response->original);
    }

    public function testGetLoggedCompanyAsUser()
    {
        $user = factory(User::class)->create();

        $response = $this->sendRequest('GET', 'company', $user);

        $this->assertInstanceOf(AuthenticationException::class, $response->exception);
    }

    public function testGetLoggedUserTypeAsCompany()
    {
        $company = factory(Company::class)->create();

        $response = $this->sendRequest('GET', 'users', $company);

        $this->assertEquals(Company::class, $response->original);
    }

    public function testGetLoggedUserTypeAsUser()
    {
        $user = factory(User::class)->create();

        $response = $this->sendRequest('GET', 'users', $user);

        $this->assertEquals(User::class, $response->original);
    }

    /**
     * Send request to route with user to be authenticated.
     *
     * @param  string $method
     * @param  string $uri
     * @param  \Illuminate\Foundation\Auth\User $user
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    public function sendRequest($method, $uri, $user)
    {
        return (float) App::version() < 5.5
            ? $this->json($method, $uri, [], ['Authorization' => $this->multiauthAccessToken($user)])
            : $this->multiauthActingAs($user)->json($method, $uri);
    }
}
