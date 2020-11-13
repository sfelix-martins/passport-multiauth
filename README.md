# [ALERT] Deprecated !!
> The package is deprecated because Laravel Passport has a native implementaion since [version 9.0](https://github.com/laravel/passport/releases/tag/v9.0.0)

# Laravel Passport Multi-Auth

[![Latest Stable Version](https://poser.pugx.org/smartins/passport-multiauth/v/stable)](https://packagist.org/packages/smartins/passport-multiauth)
[![Build Status](https://travis-ci.org/sfelix-martins/passport-multiauth.svg?branch=6.x)](https://travis-ci.org/sfelix-martins/passport-multiauth)
[![Code Coverage](https://scrutinizer-ci.com/g/sfelix-martins/passport-multiauth/badges/coverage.png?b=7.x)](https://scrutinizer-ci.com/g/sfelix-martins/passport-multiauth/?branch=7.x)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sfelix-martins/passport-multiauth/badges/quality-score.png?b=7.x)](https://scrutinizer-ci.com/g/sfelix-martins/passport-multiauth/?branch=7.x)
[![StyleCI](https://styleci.io/repos/106218586/shield?branch=6.x)](https://styleci.io/repos/106218586)
[![License](https://poser.pugx.org/smartins/passport-multiauth/license)](https://packagist.org/packages/smartins/passport-multiauth)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=AEXFGV23MM386&source=url)

Add multi-authentication support to [Laravel Passport](https://laravel.com/docs/5.5/passport)

## Upgrading from 4.0 to 5.0

- To upgrade from version 4.0 to 5.0 folow [this guide](https://github.com/sfelix-martins/passport-multiauth/blob/5.0/UPGRADE.md)

## Upgrading from 2.0 to 3.0

- To upgrade from version 2.0 to 3.0 folow [this guide](https://github.com/sfelix-martins/passport-multiauth/blob/3.0/UPGRADE.md)

## Upgrading from 1.0 to 2.0

- To upgrade from version 1.0 to 2.0 follow [this guide](https://github.com/sfelix-martins/passport-multiauth/blob/2.0/UPGRADE.md)

## Compatibility

| Laravel Passport |
|------------------|
| ^5.0             |
| ^6.0             |
| ^7.0             |
| ^8.0             |

| Laravel Framework  | Passport MultiAuth |
|--------------------|--------------------|
| <= 5.6             | <= 3.0             |
| 5.7.x              | 4.0.x              |
| >= 5.7.x  <= 5.8.x | 5.0.x              |
| 6.x                | 6.x                |
| 7.x                | 7.x                |
          

## Installing and configuring

Install using composer:

```sh
$ composer require smartins/passport-multiauth
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

Maybe you will need clear the bootstrap cache files to re-register the providers:

```sh
php artisan optimize:clear
```

Migrate database to create `oauth_access_token_providers` table:

```sh
$ php artisan migrate
```

**NOTE** If you don't ron the command to install passport run:

```sh
$ php artisan passport:install
``` 

Instead of using the `Laravel\Passport\HasApiTokens` trait from [Laravel Passport](https://laravel.com/docs/5.6/passport#installation) core, use the trait `SMartins\PassportMultiauth\HasMultiAuthApiTokens`. 

Internally, this `HasMultiAuthApiTokens` uses the `HasApiTokens`, overriding the methods `tokens()` and `createToken($name, $scopes = [])`. 
The behavior of the method `tokens()` was changed to join with the table `oauth_access_token_providers` getting just the tokens created
to specific model. 
The method `createToken($name, $scopes = [])` was changed to create the token using the `provider` defined to model on `config/auth.php`. 
Now when you create the token, this token will be related with the model that is calling.

Add new provider in `config/auth.php` using a model that extends of `Authenticatable` class and use `HasMultiAuthApiTokens` trait.

Example:

Configure your model:

```php

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;

class Admin extends Authenticatable
{
    use Notifiable, HasMultiAuthApiTokens;
}

```

And your `config/auth.php` providers:

```php
    // ...

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],

        // ** New provider**
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Admin::class,
        ],
    ],

    // ...
```

Add a new `guard` in `config/auth.php` guards array using driver `passport` and the provider added above:

```php
    // ...

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'passport',
            'provider' => 'users',
        ],

        // ** New guard **
        'admin' => [
            'driver' => 'passport',
            'provider' => 'admins',
        ],
    ],

    // ...
```

Register the middleware `AddCustomProvider` to `$routeMiddleware` attributes on `app/Http/Kernel.php` file.

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
        // ...
        'oauth.providers' => \SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class,
    ];

    // ...
}
```

**OBS:** The param `provider` is required to routes wrapped by `AddCustomProvider` middleware. 
You must to pass a valid provider configured on `config/auth.php`.
 
Add new middleware `Authenticate` on `app/Http/Kernel` `$routeMiddleware` attribute.

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
        'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
        // ** New middleware **
        'multiauth' => \SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'oauth.providers' => \SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];

    // ...
}
```

Encapsulate the passport routes for access token with the registered middleware in `AuthServiceProvider`. 
This middleware will add the capability to `Passport` route `oauth/token` use the value of `provider` param on request:

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

**Optional:** Publish migrations:

Just run the `vendor:publish` artisan command with package provider as parameter:

```sh
$ php artisan vendor:publish --provider="SMartins\PassportMultiauth\Providers\MultiauthServiceProvider"
```

If you are not going to use PassportMultiauth's default migrations, you should call the `SMartins\PassportMultiauth\PassportMultiauth::ignoreMigrations` method in the register method of your AppServiceProvider.

## Usage

Add the `provider` parameter in your request at `/oauth/token`:

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
    "provider" : "admins"
}
```

You can pass your guards on `multiauth` middleware as you wish. Example:

```php
Route::group(['middleware' => ['api', 'multiauth:admin']], function () {
    Route::get('/admin', function ($request) {
        // Get the logged admin instance
        return $request->user(); // You can use too `$request->user('admin')` passing the guard.
    });
});

```

The  `api` guard use is equals the example with `admin`.

You can pass many guards to `multiauth` middleware.

```php
Route::group(['middleware' => ['api', 'multiauth:admin,api']], function () {
    Route::get('/admin', function ($request) {
        // The instance of user authenticated (Admin or User in this case) will be returned
        return $request->user();
    });
});
```

You can use too the `Auth` facade:

```php
Auth::check();
Auth::user();
```

### Refreshing tokens

Add the `provider` parameter in your request at `/oauth/token`:

```http
POST /oauth/token HTTP/1.1
Host: localhost
Accept: application/json, text/plain, */*
Content-Type: application/json;charset=UTF-8
Cache-Control: no-cache

{
    "grant_type" : "refresh_token",
    "client_id": "client-id",
    "client_secret" : "client-secret",
    "refresh_token" : "refresh-token",
    "provider" : "admins"
}
```

### Using scopes

Just use the [`scope` and `scopes`](https://laravel.com/docs/5.5/passport#checking-scopes) middlewares from `Laravel\Passport`.

```php
protected $routeMiddleware = [
    'scopes' => \Laravel\Passport\Http\Middleware\CheckScopes::class,
    'scope' => \Laravel\Passport\Http\Middleware\CheckForAnyScope::class,
];
```

### Personal Access Tokens

In your model that uses the trait `SMartins\PassportMultiauth\HasMultiAuthApiTokens` you can uses the methods `createToken($name, $scopes = [])` and `tokens()` to manage your personal access tokens. E.g.:

```php

$user = User::find(1);
$admin = Admin::find(1);

// Create token from Model instance.
$user->createToken('My Token');
$admin->createToken('My Admin Token');

// Get the tokens created to this user. 
$user->tokens()->each(function ($token) {
    echo $token->name; // My Token
});

$admin->tokens()->each(function ($token) {
    echo $token->name; // My Admin Token
});
```

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
The difference is that the `actingAs` from this package get the guard based on `Authenticatable` instance passed on first parameter and authenticate this user using your guard. On authenticated request (Using `auth` middleware from package -  `SMartins\PassportMultiauth\Http\Middleware\MultiAuthenticate)` the guard is checked on `Request` to return the user or throws a `Unauthenticated` exception. E.g.:

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

    public function withScopesTest()
    {
        $user = factory(User::class)->create();

        PassportMultiauth::actingAs($user, ['see-balance']);

        $this->json('GET', 'api/balance');
    }
}
```

### Sample Project

You can see a complete `Passport-Multiauth` implementation using `Password Grant Tokens` and `Personal Access Token` on [passport-multiauth-demo](https://github.com/sfelix-martins/passport-multiauth-demo) project

### Contributors

Based on [renanwilian](https://github.com/renanwilliam) responses to [Passport Multi-Auth issue](https://github.com/laravel/passport/issues/161).
