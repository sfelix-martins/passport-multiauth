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

**Optional:** Publish migrations:

```console
$ php artisan vendor:publish
```

And choose the provider `SMartins\PassportMultiauth\Providers\MultiauthServiceProvider`
**End optional**

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

- Add the middlewares `PassportCustomProvider` and `PassportCustomProviderAccessToken` to api array on `$middlewareGroups` attribute on `app/Http/Kernel`.

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
            \SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider::class,
            \SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider::class,
        ],
    ];

    ...
}
```

- Encapsulate the passport routes with this middleware in `AuthServiceProvider`:

```php

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

        Route::group(['middleware' => 'api'], function () {
            Passport::routes();
        });
    }
    ...
}
```

- Add the 'provider' param in your request at `/oauth/token`:

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

## Use sample

- Create a middleware to check if user authenticated is admin

```php
<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use App\Admin;

class CheckIfIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $model = Auth::guard('api')->user();

        if ($model instanceof Admin) {
            return $next($request);
        } else {
            abort(403, "You aren't admin.");
        }
    }
}

```

- Register the middleware

```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    ...
    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        ...
        'admin' => \App\Http\Middleware\CheckIfIsAdmin::class,
    ];
    ...
}
```

- Add middleware to route

```php
<?php

Route::group(['middleware' => 'api', 'prefix' => 'admins'], function () {
    Route::get('/me', 'AdminController@me')->middleware('admin');
});

```

- Create a new admin, login with `admins` provider parameter on `oauth/token` and call route with access token:

```
GET /admins/me HTTP/1.1
Host: localhost
Accept: application/json
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbG ...
```

Response if is admin:

```json
{
    "id": 1,
    "name": "Admin",
    "email": "admin@domain.com",
    "created_at": "2017-10-08 00:00:00",
    "updated_at": "2017-10-08 00:00:00"
}
```

Response if isn't admin:

```json
{
    "errors": [
        {
            "status": 403,
            "code": 121,
            "title": "Action not allowed.",
            "detail": "You aren't admin."
        }
    ]
}
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
