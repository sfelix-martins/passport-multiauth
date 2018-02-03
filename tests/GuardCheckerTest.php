<?php

namespace SMartins\PassportMultiauth\Tests;

use Mockery;
use SMartins\PassportMultiauth\Guards\GuardChecker;

class GuardCheckerTest extends TestCase
{
    public function testTryGetAuthGuardsWithoutGuards()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['api']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, []);
    }

    public function testTryGetAuthGuardsWithOneGuard()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['auth:api']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, ['api']);
    }

    public function testTryGetAuthGuardsWithMoreThanOneGuard()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['auth:api,companies']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, ['api', 'companies']);
    }

    public function testTryGetAuthGuardsWithMoreThanOneGuardInManyMiddlewares()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['auth:api', 'auth:companies']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, ['api', 'companies']);
    }

    public function testGetDefaultGuardProvider()
    {
        $guards = ['users', 'companies'];

        foreach ($guards as $guard) {
            $this->app['config']->set('auth.guards.'.$guard.'.provider', $guard);

            $provider = GuardChecker::defaultGuardProvider($guard);

            $this->assertEquals($provider, $guard);
        }
    }
}
