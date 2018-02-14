<?php

namespace SMartins\PassportMultiauth\Guards;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class GuardChecker
{
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

    public static function defaultGuardProvider(string $guard)
    {
        return config('auth.guards.'.$guard.'.provider');
    }
}
