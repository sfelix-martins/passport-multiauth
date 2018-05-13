<?php

namespace SMartins\PassportMultiauth;

use Mockery;
use Exception;
use Laravel\Passport\Token;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\App;
use Illuminate\Contracts\Auth\Authenticatable;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Customer;

class PassportMultiauth
{
    /**
     * Set the current user for the application with the given scopes.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $scopes
     * @return void
     */
    public static function actingAs($user, $scopes = [])
    {
        $token = Mockery::mock(Token::class)->shouldIgnoreMissing(false);

        foreach ($scopes as $scope) {
            $token->shouldReceive('can')->with($scope)->andReturn(true);
        }

        $guard = self::getUserGuard($user);

        if (! in_array(HasApiTokens::class, class_uses($user))) {
            throw new Exception('The model ['.get_class($user).'] must uses the trait '.HasApiTokens::class);
        }

        $user->withAccessToken($token);

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);
    }

    /**
     * Get the user provider on configs.
     *
     * @todo Move to class specialized in check auth configs.
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return string|null
     */
    public static function getUserProvider(Authenticatable $user)
    {
        foreach (config('auth.providers') as $provider => $config) {
            if ($user instanceof $config['model']) {
                return $provider;
            }
        }
    }

    /**
     * Get the guard of specific provider to `passport` driver.
     *
     * @todo Move to class specialized in check auth configs.
     * @param  string $provider
     * @return string
     */
    public static function getProviderGuard($provider)
    {
        foreach (config('auth.guards') as $guard => $content) {
            if ($content['driver'] == 'passport' && $content['provider'] == $provider) {
                return $guard;
            }
        }
    }

    /**
     * Get the user guard on provider with `passport` driver.
     *
     * @todo Move to class specialized in check auth configs.
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @return string|null
     */
    public static function getUserGuard(Authenticatable $user)
    {
        $provider = self::getUserProvider($user);

        return self::getProviderGuard($provider);
    }

    /**
     * If running unit test and try authenticate an user with actingAs($user)
     * check the guards on request to authenticate or not the user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public static function userActing()
    {
        if (App::runningUnitTests() && $user = app('auth')->user()) {
            return $user;
        }
    }
}
