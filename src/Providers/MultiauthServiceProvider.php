<?php

namespace SMartins\PassportMultiauth\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Events\AccessTokenCreated;
use SMartins\PassportMultiauth\ProviderRepository;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class MultiauthServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(ProviderRepository $providers)
    {
        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
        }

        $this->createAccessTokenProvider($providers);

        // Register the facade ServerRequest returning an instance of DiactorosFactory.
        $this->app->singleton('ServerRequest', function ($app) {
            return new DiactorosFactory;
        });

        // Register the middleware as signleton to use the same middleware
        // instance when the handle and terminate methods are called.
        $this->app->singleton(\SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class);
    }

    /**
     * Register migrations to work on `php artisan migrate` comamnd.
     *
     * @return void
     */
    protected function registerMigrations()
    {
        $migrationsPath = __DIR__.'/../../database/migrations';

        $this->loadMigrationsFrom($migrationsPath);

        $this->publishes(
            [$migrationsPath => database_path('migrations')],
            'migrations'
        );
    }

    /**
     * Create access token provider when access token is created.
     *
     * @return void
     */
    protected function createAccessTokenProvider(ProviderRepository $providers)
    {
        Event::listen(AccessTokenCreated::class, function ($event) use ($providers) {
            $provider = config('auth.guards.api.provider');

            $providers->create($event->tokenId, $provider);
        });
    }
}
