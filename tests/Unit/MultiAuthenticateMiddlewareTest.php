<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use Mockery;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Auth\AuthenticationException;
use SMartins\PassportMultiauth\Tests\TestCase;
use SMartins\PassportMultiauth\Tests\Models\User;
use SMartins\PassportMultiauth\Testing\MultiauthActions;
use League\OAuth2\Server\Exception\OAuthServerException;
use SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate;

class MultiAuthenticateMiddlewareTest extends TestCase
{
    protected $auth;

    use MultiauthActions;

    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/factories');

        $this->setAuthConfigs();

        $this->setUpLaravelPassport();

        $this->auth = $this->app['auth'];
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testTryAuthWithoutGuards()
    {
        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');

        $repository = Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');

        $request = $this->createRequest();

        $middleware = new MultiAuthenticate($resourceServer, $repository, $this->auth);
        $response = $middleware->handle($request, function () {
            return 'response';
        });

        $this->assertEquals('response', $response);
    }

    public function testTryAuthWithoutAccessTokenId()
    {
        $this->expectException(AuthenticationException::class);

        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(null);

        $repository = Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');

        $request = $this->createRequest();

        $middleware = new MultiAuthenticate($resourceServer, $repository, $this->auth);
        $middleware->handle($request, function () {
            return 'response';
        }, 'api', 'company');
    }

    public function testTryAuthWithNotExistentAccessToken()
    {
        $this->expectException(AuthenticationException::class);

        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);

        $repository = Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn(null);

        $request = $this->createRequest();

        $middleware = new MultiAuthenticate($resourceServer, $repository, $this->auth);
        $middleware->handle($request, function () {
            return 'response';
        }, 'api', 'company');
    }

    public function testTryAuthWithoutAuthorizationHeader()
    {
        $this->expectException(AuthenticationException::class);

        $resourceServer = $this->createMock('League\OAuth2\Server\ResourceServer');
        $resourceServer->method('validateAuthenticatedRequest')
            ->will($this->throwException(OAuthServerException::accessDenied('Missing "Authorization" header')));

        $repository = Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn(null);

        $request = Request::create('/');

        $middleware = new MultiAuthenticate($resourceServer, $repository, $this->auth);
        $middleware->handle($request, function () {
            return 'response';
        }, 'api', 'company');
    }

    protected function createRequest($token = null)
    {
        $token = $token ?? 'Bearer token';

        $request = Request::create('/');
        $request->headers->set('Authorization', $token);
        $request = $request->setRouteResolver(function () use ($request) {
            $route = new Route('GET', '/foo/bar', ['as' => 'foo.bar']);
            $route->bind($request);

            return $route;
        });

        return $request;
    }
}
