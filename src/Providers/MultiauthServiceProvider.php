<?php

namespace SMartins\PassportMultiauth\Providers;

use Illuminate\Auth\AuthServiceProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Events\AccessTokenCreated;
use SMartins\PassportMultiauth\Auth\AuthManager;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;
use SMartins\PassportMultiauth\PassportMultiauth;
use SMartins\PassportMultiauth\ProviderRepository;

class MultiauthServiceProvider extends AuthServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @param ProviderRepository $providers
     * @return void
     */
    public function boot(ProviderRepository $providers)
    {
        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
        }

        $this->createAccessTokenProvider($providers);

        // Register the middleware as singleton to use the same middleware
        // instance when the handle and terminate methods are called.
        $this->app->singleton(AddCustomProvider::class);
    }

    /**
     * Register migrations to work on `php artisan migrate` command.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $migrationsPath = __DIR__.'/../../database/migrations';

        if (PassportMultiauth::$runsMigrations) {
            $this->loadMigrationsFrom($migrationsPath);
        }

        $this->publishes(
            [$migrationsPath => database_path('migrations')],
            'migrations'
        );
    }

    /**
     * Register the authenticator services.
     *
     * @return void
     */
    protected function registerAuthenticator()
    {
        $this->app->singleton('auth', function ($app) {
            // Once the authentication service has actually been requested by the developer
            // we will set a variable in the application indicating such. This helps us
            // know that we need to set any queued cookies in the after event later.
            $app['auth.loaded'] = true;

            return new AuthManager($app);
        });

        $this->app->singleton('auth.driver', function ($app) {
            return $app['auth']->guard();
        });
    }

    /**
     * Create access token provider when access token is created.
     *
     * @param ProviderRepository $repository
     * @return void
     */
    protected function createAccessTokenProvider(ProviderRepository $repository)
    {
        Event::listen(AccessTokenCreated::class, function ($event) use ($repository) {
            $provider = config('auth.guards.api.provider');

            $repository->create($event->tokenId, $provider);
        });
    }
}
