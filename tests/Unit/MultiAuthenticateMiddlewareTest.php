<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use Mockery;
use Illuminate\Http\Request;
use SMartins\PassportMultiauth\Provider;
use Illuminate\Auth\AuthenticationException;
use SMartins\PassportMultiauth\Tests\TestCase;
use League\OAuth2\Server\Exception\OAuthServerException;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;
use SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate;

class MultiAuthenticateMiddlewareTest extends TestCase
{
    protected $auth;

    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/../Fixtures/factories');

        $this->setAuthConfigs();

        $this->auth = $this->app['auth'];
    }

    public function tearDown()
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testTryAuthWithoutGuards()
    {
        $this->expectException(AuthenticationException::class);

        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');

        $repository = Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');

        $request = $this->createRequest();

        $middleware = new MultiAuthenticate($resourceServer, $repository, $this->auth);
        $middleware->handle($request, function () {
            return 'response';
        });
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

    public function testTryAuthWithExistentAccessTokenAndExistentOnProviders()
    {
        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);

        $tokenProvider = new Provider;
        $tokenProvider->provider = 'companies';

        $repository = Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($tokenProvider);

        $request = $this->createRequest();

        $guard = 'company';
        app('auth')->guard($guard)->setUser(factory(Company::class)->create());

        app('auth')->shouldUse($guard);

        $middleware = new MultiAuthenticate($resourceServer, $repository, $this->auth);
        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'api', 'company');

        $this->assertEquals('response', $response);
    }

    public function testTryAuthWithExistentAccessTokenAndNotExistentOnProviders()
    {
        $this->expectException(AuthenticationException::class);

        $resourceServer = Mockery::mock('League\OAuth2\Server\ResourceServer');
        $resourceServer->shouldReceive('validateAuthenticatedRequest')->andReturn($psr = Mockery::mock());
        $psr->shouldReceive('getAttribute')->with('oauth_access_token_id')->andReturn(1);

        $tokenProvider = new Provider;
        $tokenProvider->provider = 'companies';

        $repository = Mockery::mock('SMartins\PassportMultiauth\ProviderRepository');
        $repository->shouldReceive('findForToken')->andReturn($tokenProvider);

        $request = $this->createRequest();

        $middleware = new MultiAuthenticate($resourceServer, $repository, $this->auth);
        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'api');
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

    /**
     * Create request instance to be used on MultiAuthenticate::handle() param.
     *
     * @param string $token|null
     * @return \Illuminate\Http\Request
     */
    protected function createRequest(string $token = null)
    {
        $token = $token ? $token : 'Bearer token';

        $request = Request::create('/');
        $request->headers->set('Authorization', $token);

        return $request;
    }
}
