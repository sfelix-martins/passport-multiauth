<?php

namespace SMartins\PassportMultiauth;

use Mockery;
use Laravel\Passport\Token;
use Illuminate\Contracts\Auth\Authenticatable;

class PassportMultiauth
{
    /**
     * Set the current user for the application with the given scopes.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $scopes
     * @param  string  $guard
     * @return void
     */
    public static function actingAs($user, $scopes = [])
    {
        $token = Mockery::mock(Token::class)->shouldIgnoreMissing(false);

        foreach ($scopes as $scope) {
            $token->shouldReceive('can')->with($scope)->andReturn(true);
        }

        $guard = self::getUserGuard($user);

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

        return null;
    }

    /**
     * Get the guard of specific provider to `passport` driver.
     *
     * @todo Move to class specialized in check auth configs.
     * @param  string $provider
     * @return string|null
     */
    public static function getProviderGuard($provider)
    {
        foreach (config('auth.guards') as $guard => $content) {
            if ($content['driver'] == 'passport' && $content['provider'] == $provider) {
                return $guard;
            }
        }

        return null;
    }

    /**
     * Get the user guard on provider with `passport` driver;
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
}
