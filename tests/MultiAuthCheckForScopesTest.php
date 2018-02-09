<?php

namespace SMartins\PassportMultiauth\Tests;

use Mockery;
use SMartins\PassportMultiauth\Http\Middleware\MultiAuthCheckScopes as CheckScopes;

class MultiAuthCheckForScopesTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testRequestIsPassedAlongIfScopesArePresentOnToken()
    {
        $middleware = new CheckScopes;
        $request = Mockery::mock();
        $request->shouldReceive('user')->andReturn($user = Mockery::mock());
        $user->shouldReceive('token')->andReturn($token = Mockery::mock());
        $user->shouldReceive('tokenCan')->with('foo')->andReturn(true);
        $user->shouldReceive('tokenCan')->with('bar')->andReturn(true);
        $response = $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
        $this->assertEquals('response', $response);
    }

    /**
     * @expectedException Laravel\Passport\Exceptions\MissingScopeException
     */
    public function testExceptionIsThrownIfTokenDoesntHaveScope()
    {
        $middleware = new CheckScopes;
        $request = Mockery::mock();
        $request->shouldReceive('user')->andReturn($user = Mockery::mock());
        $user->shouldReceive('token')->andReturn($token = Mockery::mock());
        $user->shouldReceive('tokenCan')->with('foo')->andReturn(false);
        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    /**
     * @expectedException Illuminate\Auth\AuthenticationException
     */
    public function testExceptionIsThrownIfNoAuthenticatedUser()
    {
        $middleware = new CheckScopes;
        $request = Mockery::mock();
        $request->shouldReceive('user')->once()->andReturn(null);
        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }

    /**
     * @expectedException Illuminate\Auth\AuthenticationException
     */
    public function testExceptionIsThrownIfNoToken()
    {
        $middleware = new CheckScopes;
        $request = Mockery::mock();
        $request->shouldReceive('user')->andReturn($user = Mockery::mock());
        $user->shouldReceive('token')->andReturn(null);
        $middleware->handle($request, function () {
            return 'response';
        }, 'foo', 'bar');
    }
}
