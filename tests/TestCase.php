<?php

namespace SMartins\PassportMultiauth\Tests;

use Laravel\Passport\PassportServiceProvider;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use SMartins\PassportMultiauth\Tests\Fixtures\Http\Kernel;
use SMartins\PassportMultiauth\Providers\MultiauthServiceProvider;

abstract class TestCase extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(realpath(__DIR__.'/database/migrations'));
    }

    protected function getPackageProviders($app)
    {
        return [
            PassportServiceProvider::class,
            MultiauthServiceProvider::class,
            ConsoleServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'passport');
        $app['config']->set('database.connections.passport', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);
    }

    /**
     * Resolve application HTTP Kernel implementation.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function resolveApplicationHttpKernel($app)
    {
        $app->singleton('Illuminate\Contracts\Http\Kernel', Kernel::class);
    }
}
