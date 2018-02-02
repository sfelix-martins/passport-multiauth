<?php

namespace SMartins\PassportMultiauth\Tests;

use \Mockery;
use SMartins\PassportMultiauth\Guards\GuardChecker;

class GuardCheckerTest extends TestCase
{
    public function test_try_get_auth_guards_without_guards()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['api']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, []);
    }

    public function test_try_get_auth_guards_with_one_guard()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['auth:api']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, ['api']);
    }

    public function test_try_get_auth_guards_with_more_than_one_guard()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['auth:api,companies']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, ['api', 'companies']);
    }

    public function test_try_get_auth_guards_with_more_than_one_guard_in_many_middlewares()
    {
        $request = Mockery::mock('Illuminate\Http\Request');
        $request->shouldReceive('route')->andReturn($route = Mockery::mock());

        $route->shouldReceive('middleware')->andReturn(['auth:api', 'auth:companies']);

        $response = GuardChecker::getAuthGuards($request);

        $this->assertEquals($response, ['api', 'companies']);
    }

    public function test_get_default_guard_provider()
    {
        $guards = ['users', 'companies'];

        foreach ($guards as $guard) {
            $this->app['config']->set('auth.guards.'.$guard.'.provider', $guard);

            $provider = GuardChecker::defaultGuardProvider($guard);

            $this->assertEquals($provider, $guard);
        }
    }
}
