<?php

namespace SMartins\PassportMultiauth\Tests;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\PassportServiceProvider;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use SMartins\PassportMultiauth\Tests\Fixtures\Http\Kernel;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;
use SMartins\PassportMultiauth\Providers\MultiauthServiceProvider;

abstract class TestCase extends BaseTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(realpath(__DIR__.'/Fixtures/database/migrations'));
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

    protected function setUpLaravelPassport()
    {
        $this->installPassport();

        $this->loadPassportRoutes();
    }

    /**
     * Install laravel passport to generate configs.
     */
    protected function installPassport()
    {
        $this->artisan('passport:install');
    }

    /**
     * Load laravel passport routes.
     *
     */
    protected function loadPassportRoutes()
    {
        Passport::routes();

        // Middleware `api` that contains the `custom-provider` middleware group defined on $middlewareGroups above
        Route::group(['middleware' => 'oauth.providers'], function () {
            Passport::routes(function ($router) {
                return $router->forAccessTokens();
            });
        });
    }

    /**
     * Setup auth configs.
     */
    protected function setAuthConfigs()
    {
        // Set up default entity
        config(['auth.guards.api.driver' => 'passport']);
        config(['auth.guards.api.provider' => 'users']);
        config(['auth.providers.users.model' => User::class]);

        // Set up company entity
        config(['auth.guards.company.driver' => 'passport']);
        config(['auth.guards.company.provider' => 'companies']);
        config(['auth.providers.companies.driver' => 'eloquent']);
        config(['auth.providers.companies.model' => Company::class]);
    }
}
