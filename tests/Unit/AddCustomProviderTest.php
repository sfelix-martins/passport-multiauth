<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use Mockery;
use Illuminate\Http\Request;
use SMartins\PassportMultiauth\Tests\TestCase;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;

class AddCustomProviderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Config default provider
        config(['auth.guards.api.provider', 'users']);
    }

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

        $this->assertEquals(config('auth.guards.api.provider'), 'companies');

        // Check if was correctly reset to default provider on `terminate()`
        $middleware->terminate();
        $this->assertEquals(config('auth.guards.api.provider'), 'users');
    }
}
