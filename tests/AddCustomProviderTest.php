<?php

namespace SMartins\PassportMultiauth\Tests;

use Mockery;
use Illuminate\Http\Request;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;

class AddCustomProviderTest extends TestCase
{
    public function tearDown()
    {
        Mockery::close();
    }

    public function testIfApiProviderOnAuthWasSetCorrectly()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('all')->andReturn([
            'provider' => 'companies',
        ]);

        $middleware = new AddCustomProvider();
        $middleware->handle($request, function () {
            return 'response';
        });

        $provider = $this->app['config']->get('auth.guards.api.provider');

        $this->assertEquals($provider, 'companies');
    }
}
