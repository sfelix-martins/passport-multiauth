<?php

namespace SMartins\PassportMultiauth\Config;

use Illuminate\Contracts\Auth\Authenticatable;
use SMartins\PassportMultiauth\Exceptions\MissingConfigException;

class AuthConfigHelper
{
    /**
     * Get the user provider on configs.
     *
     * @param  Authenticatable $user
     * @return string|null
     * @throws MissingConfigException
     */
    public static function getUserProvider(Authenticatable $user)
    {
        foreach (config('auth.providers') as $provider => $config) {
            if ($user instanceof $config['model']) {
                return $provider;
            }
        }

        throw MissingConfigException::provider($user);
    }

    /**
     * Get the guard of specific provider to `passport` driver.
     *
     * @param  string $provider
     * @param Authenticatable $user
     * @return string
     * @throws MissingConfigException
     */
    public static function getProviderGuard($provider, Authenticatable $user)
    {
        foreach (config('auth.guards') as $guard => $content) {
            if ($content['driver'] == 'passport' && $content['provider'] == $provider) {
                return $guard;
            }
        }

        throw MissingConfigException::guard($user);
    }

    /**
     * Get the user guard on provider with `passport` driver.
     *
     * @param  Authenticatable $user
     * @return string|null
     * @throws MissingConfigException
     */
    public static function getUserGuard(Authenticatable $user)
    {
        $provider = self::getUserProvider($user);

        return self::getProviderGuard($provider, $user);
    }
}
