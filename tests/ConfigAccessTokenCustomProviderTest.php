<?php

namespace SMartins\PassportMultiauth\Tests;

use \Mockery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider;
use SMartins\PassportMultiauth\ProviderRepository;
use SMartins\PassportMultiauth\Provider;

class ConfigAccessTokenCustomProviderTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);
    }

    public function tearDown()
    {
        \Mockery::close();
    }

    public function testTryConfigWithoutAccessTokenID()
    {
        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($provide = \Mockery::mock());

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });
        $route = $request->route()->middleware('auth:api');
        $request = $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(null);

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function testTryConfigWithNotExistentAccessToken()
    {
        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn(null);

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });
        $route = $request->route()->middleware('auth:api');
        $request = $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }


    public function testTryConfigWithNotMoreThanOneEntityWithSameIDOnProviders()
    {
        $this->createUser();

        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($provider = \Mockery::mock());

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });
        $route = $request->route()->middleware('auth:api');
        $request = $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);

        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new User);

        $this->app['config']->set('auth.providers.users.model', User::class);

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);
        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function testTryConfigWithoutGuardsOnAuthMiddleware()
    {
        $this->createUser();

        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($provider = \Mockery::mock());

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });

        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);

        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new User);

        $this->app['config']->set('auth.providers.users.model', User::class);

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function testTryConfigWithMoreThanOneGuardsOnAuthMiddleware()
    {
        $this->createUser();
        $this->createCompany();

        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());

        $accessToken = new Provider;
        $accessToken->provider = 'companies';
        $accessToken->oauth_access_token_id = 'token';

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($accessToken);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });
        $route = $request->route()->middleware('auth:company,api');
        $request = $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);

        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new User);

        $this->app['config']->set('auth.providers.users.driver', 'eloquent');
        $this->app['config']->set('auth.providers.users.model', User::class);
        $this->app['config']->set('auth.providers.companies.driver', 'eloquent');
        $this->app['config']->set('auth.providers.companies.model', Company::class);

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);
        $response = $middleware->handle($request, function () {
            return 'response';
        });

        // Check if config was set
        $this->assertEquals(config('auth.guards.api.provider'), $accessToken->provider);

        $this->assertEquals('response', $response);
    }

    public function testTryConfigWithProviderNotEqualsDefaultGuardProvider()
    {
        $this->withoutExceptionHandling();

        $this->createUser();
        $this->createCompany();

        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());

        $accessToken = new Provider;
        $accessToken->provider = 'users';
        $accessToken->oauth_access_token_id = 'token';

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($accessToken);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });
        $route = $request->route()->middleware('auth:company');
        $request = $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);

        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new User);

        $this->app['config']->set('auth.providers.users.driver', 'eloquent');
        $this->app['config']->set('auth.providers.users.model', User::class);
        $this->app['config']->set('auth.guards.api.provider', 'users');
        $this->app['config']->set('auth.providers.companies.driver', 'eloquent');
        $this->app['config']->set('auth.providers.companies.model', Company::class);
        $this->app['config']->set('auth.guards.company.provider', 'companies');

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);

        try {
            $response = $middleware->handle($request, function () {
                return 'response';
            });
        } catch (\Exception $e) {
            $this->assertInstanceOf(AuthenticationException::class, $e);
        }

        // Check if config was set
        $this->assertEquals(config('auth.guards.api.provider'), $accessToken->provider);
    }

    public function testTryConfigWithCorrectProvider()
    {
        $this->createUser();
        $this->createCompany();

        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());

        $accessToken = new Provider;
        $accessToken->provider = 'companies';
        $accessToken->oauth_access_token_id = 'token';

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($accessToken);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });
        $route = $request->route()->middleware('auth:company');
        $request = $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn('token');
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);

        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new User);

        $this->app['config']->set('auth.providers.users.driver', 'eloquent');
        $this->app['config']->set('auth.providers.users.model', User::class);
        $this->app['config']->set('auth.providers.companies.driver', 'eloquent');
        $this->app['config']->set('auth.providers.companies.model', Company::class);
        $this->app['config']->set('auth.guards.company.provider', 'companies');

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        // Check if config was set
        $this->assertEquals(config('auth.guards.api.provider'), $accessToken->provider);
        $this->assertEquals($response, 'response');
    }

    public function createUser()
    {
        $now = Carbon::now();
        \DB::table('users')->insert([
            'name' => 'Samuel',
            'email' => 'sam.martins.dev@gmail.com',
            'password' => \Hash::make('456'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function createCompany()
    {
        $now = Carbon::now();
        \DB::table('companies')->insert([
            'name' => 'Samuel',
            'email' => 'sam.martins.dev@gmail.com',
            'password' => \Hash::make('456'),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}

class User extends Model
{
    protected $table = 'users';

    use \Laravel\Passport\HasApiTokens;

    public function getAuthIdentifierName()
    {
        return 'id';
    }
}

class Company extends Model
{
    protected $table = 'companies';

    use \Laravel\Passport\HasApiTokens;

    public function getAuthIdentifierName()
    {
        return 'id';
    }
}
