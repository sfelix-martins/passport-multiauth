<?php

namespace SMartins\PassportMultiauth;

use Mockery;
use Exception;
use Laravel\Passport\Token;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\App;
use SMartins\PassportMultiauth\Config\AuthConfigHelper;

class PassportMultiauth
{
    /**
     * Set the current user for the application with the given scopes.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable $user
     * @param  array $scopes
     * @return void
     * @throws Exception
     */
    public static function actingAs($user, $scopes = [])
    {
        $token = Mockery::mock(Token::class)->shouldIgnoreMissing(false);

        foreach ($scopes as $scope) {
            $token->shouldReceive('can')->with($scope)->andReturn(true);
        }

        $uses = array_flip(class_uses_recursive($user));

        if (! isset($uses[HasApiTokens::class])) {
            throw new Exception('The model ['.get_class($user).'] must uses the trait '.HasApiTokens::class);
        }

        $user->withAccessToken($token);

        $guard = AuthConfigHelper::getUserGuard($user);

        app('auth')->guard($guard)->setUser($user);

        app('auth')->shouldUse($guard);
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
