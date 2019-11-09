# Upgrade Guide

## Upgrading to 5.0 from 4.0

Change version on `composer.json`:

```json
"smartins/passport-multiauth": "^5.0",
```

Update using composer:

```sh
$ composer update smartins/passport-multiauth
```

To all works fine, we need to ensure that the `SMartins\PassportMultiauth\Providers\MultiauthServiceProvider::class` service provider
be registered before `Laravel\Passport\PassportServiceProvider::class`.

Firstly, you will remove the `laravel/passport` package from Laravel [Package Discovery](https://laravel.com/docs/5.8/packages#package-discovery).

In your `composer.json` file, add the `laravel/passport` to `extra.laravel.dont-discover` array:

```json
    "extra": {
        "laravel": {
            "dont-discover": [
                "laravel/passport"
            ]
        }
    },
```

And register the providers manually on `config/app.php`:

```php
    'providers' => [
        // ...
        SMartins\PassportMultiauth\Providers\MultiauthServiceProvider::class,
        Laravel\Passport\PassportServiceProvider::class,
    ],
```

**WARNING:** The provider `SMartins\PassportMultiauth\Providers\MultiauthServiceProvider::class` MUST be added before `Laravel\Passport\PassportServiceProvider::class` to it works fine.

Clear the bootstrap cache files to re-register the providers:

```sh
php artisan optimize:clear
```

## Upgrading to 3.0 from 2.0

You just should asserts that your requests to routes wrapped by middleware `\SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class` has the `provider` param passing the desired provider.

E.g.:

In version `2.0` if your request to `oauth/token` don't has the param provider, the default provider defined on `config('auth.guards.api.provider')` will be used.

```http
POST /oauth/token HTTP/1.1
Host: localhost
Accept: application/json, text/plain, */*
Content-Type: application/json;charset=UTF-8
Cache-Control: no-cache

{
    "username":"user@domain.com",
    "password":"password",
    "grant_type" : "password",
    "client_id": "client-id",
    "client_secret" : "client-secret"
}
```

The response to request above should be a `200 OK` with access token on body.

In version `3.0` the request above will return a `400 Bad Request`

```json
{
    "message": "The request is missing a required parameter, includes an invalid parameter value, includes a parameter more than once, or is otherwise malformed.",
    "exception": "League\\OAuth2\\Server\\Exception\\OAuthServerException",
    "file": "/home/vagrant/code/vendor/league/oauth2-server/src/Exception/OAuthServerException.php",
    "line": 114,
    "trace": []
}
```

To fix it you should pass the provider param on request even for the provider defined in the `api` guard. E.g.:

```http
POST /oauth/token HTTP/1.1
Host: localhost
Accept: application/json, text/plain, */*
Content-Type: application/json;charset=UTF-8
Cache-Control: no-cache

{
    "username":"user@domain.com",
    "password":"password",
    "grant_type" : "password",
    "client_id": "client-id",
    "client_secret" : "client-secret",
    "provider": "users" 
}
```

In short, the param `provider` is required and must exists on `config/auth.php` providers config.

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

### Known Issues

To all works correctly you must use the default guard `web` on `config/auth.php`. E.g.:

```php
    'defaults' => [
        'guard' => 'web',
    ],
```

Exists an opened [issues](https://github.com/sfelix-martins/passport-multiauth/issues/63) that will be analysed ASAP.

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
