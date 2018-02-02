<?php

namespace SMartins\PassportMultiauth\Tests;

use \Mockery;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;
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

    public function test_try_config_without_access_token_id()
    {
        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($provide = \Mockery::mock());

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(null);

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function test_try_config_with_not_existent_access_token()
    {
        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn(null);

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);

        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }


    public function test_try_config_with_not_more_than_one_entity_with_same_id_on_providers()
    {
        $this->createUser();

        $resourceServer = \Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = \Mockery::mock());

        $repository = \Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($provider = \Mockery::mock());

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer token');

        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);
        $psr->shouldReceive('getAttribute')->with('oauth_user_id')->andReturn(1);

        $userProvider = Mockery::mock('Illuminate\Contracts\Auth\UserProvider');
        $userProvider->shouldReceive('retrieveById')->with(1)->andReturn(new TokenGuardTestUser);

        $this->app['config']->set('auth.providers.users.model', TokenGuardTestUser::class);

        $middleware = new ConfigAccessTokenCustomProvider($resourceServer, $repository, new App);
        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function test_try_config_without_guards_on_auth_middleware()
    {
        //
    }

    public function testTryConfigWithMoreThanOneGuardsOnAuthMiddleware()
    {
        //
    }

    public function testTryConfigWithProviderNotEqualsDefaultGuardProvider()
    {
        //
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
}

class TokenGuardTestUser extends Model
{
    protected $table = 'users';

    use \Laravel\Passport\HasApiTokens;

    public function getAuthIdentifierName()
    {
        return 'id';
    }
}
