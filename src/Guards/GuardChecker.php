<?php

namespace SMartins\PassportMultiauth\Guards;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GuardChecker
{
    /**
     * Get guards passed as parameters to `auth` middleware.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public static function getAuthGuards(Request $request)
    {
        $middlewares = $request->route()->middleware();

        $guards = [];
        foreach ($middlewares as $middleware) {
            if (Str::startsWith($middleware, 'auth')) {
                $explodedGuards = explode(',', Str::after($middleware, ':'));
                $guards = array_unique(array_merge($guards, $explodedGuards));
            }
        }

        return $guards;
    }

    /**
     * Get default provider from guard.
     *
     * @param  string $guard
     * @return string|null
     */
    public static function defaultGuardProvider($guard)
    {
        return config('auth.guards.'.$guard.'.provider');
    }
}
