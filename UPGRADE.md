# Upgrade Guide

## Upgrading to 2.0 from 1.0

### Updating Dependencies

Update the `smartins/passport-multiauth` dependency to `^2.0` in your `composer.json` file.

**OBS:** If you have problems using `laravel/passport` `2.0.*` please update to >= `3.0`.

### Update configs

On `app/Http/Kernel.php` remove the `SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider` from your middlewares.
Now you need just of `SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class` to use in your `forAccessTokens` `Laravel/Passport` routes. Replace the default `auth` middleware from `Illuminate\Auth\Middleware\Authenticate::class` to `SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate::class`:

```php
class Kernel extends HttpKernel
{
    // ...

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // ** Replace auth middleware **
        // 'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        'auth' => \SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        // Keep the `AddCustomProvider`
        'oauth.providers' => \SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];

    // ...
}
```

* Change the middleware on `Laravel\Passport` routes on `AuthServiceProvider`:

```php

namespace App\Providers;

use Route;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    // ...

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();

        // Middleware `oauth.providers` middleware defined on $routeMiddleware above
        Route::group(['middleware' => 'oauth.providers'], function () {
            Passport::routes(function ($router) {
                return $router->forAccessTokens();
            });
        });
    }
    // ...
}
```

### Request and Auth

On `v2.0` you don't need to pass the guard `api` to routes that has multi-auth guard. E.g.:

* On `v1.0` to use more than one guard on `auth` middleware you should do something like:

```php
Route::middleware('auth:admin,api')->get('users', function (Request $request) {
    return $request->user('api'); // Return an instance of `Admin` or `User`
});

// Or to just one guard with not default middleware (api):
Route::middleware('auth:admin')->get('admin', function (Request $request) {
    return $request->user('admin');
});
```

* On `v2.0` in all cases you can use `$request->user()`, without the guard. E.g.:

```php
Route::middleware('auth:admin,api')->get('users', function (Request $request) {
    return $request->user(); // Returns an instance of `Admin` or `User`
});

// Or to just one guard with not default middleware (api):
Route::middleware('auth:admin')->get('admin', function (Request $request) {
    return $request->user(); // Returns an instance of `Admin` 
});

Route::middleware('auth:admin,api,customer')->get('users', function (Request $request) {
    // You can specify the guard
    return $request->user('customer'); // Returns an instance of `Customer` or `null`
});
```

* You can use too the `Auth` facade:

```php
Auth::check();
Auth::user();
```

### Scopes

On older major release (v1.0), to use scopes you should replace the `Laravel\Passport` scopes middlewares to package middlewares:

```php
// On version 1.0.
    'multiauth.scope' => \SMartins\PassportMultiauth\Http\Middleware\MultiAuthCheckForAnyScope::class,
    'multiauth.scopes' => \SMartins\PassportMultiauth\Http\Middleware\MultiAuthCheckScopes::class,
```

On version 2.0 you must do the opposite way. Just uses the default middlewares from `Larave\Passport`:

```php
// On version 2.0.
    'scopes' => \Laravel\Passport\Http\Middleware\CheckScopes::class,
    'scope' => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
```

Read more about laravel passport scopes on [official docs](https://laravel.com/docs/5.5/passport#checking-scopes)

### Unit tests

Instead to use the `Laravel\Passport\Passport::actingAs()` method, use `SMartins\PassportMultiauth\PassportMultiauth::actingAs()`.
The difference is that the `actingAs` from this package get the guard based on `Authenticatable` instance passed on first parameter and authenticate this user using your guard. E.g.:

```php
use App\User;
use Tests\TestCase;
use SMartins\PassportMultiauth\PassportMultiauth;

class AuthTest extends TestCase
{
    public function fooTest()
    {
        $user = factory(User::class)->create();

        PassportMultiauth::actingAs($user);

        $this->json('GET', 'api/user');
    }
}
```
