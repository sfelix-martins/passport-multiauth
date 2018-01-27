# Laravel Passport Multi-Auth

Add support to multi-authentication to [Laravel Passport](https://laravel.com/docs/5.5/passport)

**OBS:** Based on responses from [renanwilian](https://github.com/renanwilliam) to [Passport Multi-Auth issue](https://github.com/laravel/passport/issues/161)

## Compatibility

| Laravel Passport |
|------------------|
| ^2.0             |
| ^3.0             |

## Installing and configuring

- Install using composer:

```console
$ composer require smartins/passport-multiauth
```

- If you are using less than Laravel 5.5 do you need add the providers to `config/app.php`:

```php
    'providers' => [
        ...
        SMartins\PassportMultiauth\Providers\MultiauthServiceProvider::class,
    ],
```

- Migrate database to create `oauth_access_token_providers` table:

```console
$ php artisan migrate
```

- Add new provider in `config/auth.php` using a model that extends `Authenticable` class and use `HasRoles`, `HasApiTokens` traits.

Ex.:

```php
<?php

return [
    ...

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\User::class,
        ],

        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Administrator::class,
        ],
    ],
];

```

- Add a new `guard` in `config/auth.php` guards array using driver `passport` and the provider added above:

```php
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
    ]
```

- Register the middlewares `AddCustomProvider` and `ConfigAccessTokenCustomProvider` on `$middlewareGroups` attribute on `app/Http/Kernel`.

```php

class Kernel extends HttpKernel
{
    ...

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            'throttle:60,1',
            'bindings',
            \Barryvdh\Cors\HandleCors::class,
            'custom-provider',
        ],

        'custom-provider' => [
            \SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class,
            \SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider::class,
        ]
    ];

    ...
}
```

- Encapsulate the passport routes for access token with the registered middleware in `AuthServiceProvider`:

```php

use Route;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    ...
    
    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Passport::routes();

        // Middleware `api` that contains the `custom-provider` middleware group defined on $middlewareGroups above
        Route::group(['middleware' => 'api'], function () {
            Passport::routes(function ($router) {
                return $router->forAccessTokens();
            });
        });
    }
    ...
}
```

**Optional:** Publish migrations:

```console
$ php artisan vendor:publish
```

And choose the provider `SMartins\PassportMultiauth\Providers\MultiauthServiceProvider`

## Usage

- Add the 'provider' parameter in your request at `/oauth/token`:

```
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

- You can pass your guards on `auth` middleware as you with. Example:

```php
Route::group(['middleware' => ['api', 'auth:admin']], function () {
    Route::get('/admin', function ($request) {
        // Get the logged admin instance
        return $request->user(); // You can use too `$request->user('admin')` passing the guard.
    });
});

```

The  `api` guard use is equals the example with `admin`

- You can pass many guards to `auth` middleware. 

**OBS:** To pass many you need pass the `api` guard on end of guards and the guard `api` as parameter on `$request->user()` method. Ex:

```php
// `api` guard on end of guards separated by comma
Route::group(['middleware' => ['api', 'auth:admin,api']], function () {
    Route::get('/admin', function ($request) {
        // Passing `api` guard to `$request->user()` method
        // The instance of user authenticated (Admin or User in this case) will be returned
        return $request->user('api');
    });
});
```

- On your routes encapsulated with `custom-provider` middleware you needs now pass the 'api' guard to user() method:

- `app\routes\api.php`:

```php
use Illuminate\Http\Request;

Route::get('/admin', function (Request $request) {
    return $request->user('api');
});

```

### Refreshing tokens

- Add the 'provider' param in your request at `/oauth/token`:

```
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

- Instead of using the [`scope` and `scopes`](https://laravel.com/docs/5.5/passport#checking-scopes
) middleware from `Laravel\Passport` use from `SMartins\PassportMultiauth` package:

```php
protected $routeMiddleware = [
    'scope' => \SMartins\PassportMultiauth\Http\Middleware\MultiAuthCheckForAnyScope::class,
    'scopes' => \SMartins\PassportMultiauth\Http\Middleware\MultiAuthCheckScopes::class,
];
```

On middlewares the has the guard `api` on request object
