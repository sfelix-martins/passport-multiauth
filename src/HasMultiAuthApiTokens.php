<?php

namespace SMartins\PassportMultiauth;

use Laravel\Passport\Token;
use Illuminate\Container\Container;
use Laravel\Passport\PersonalAccessTokenResult;
use Laravel\Passport\PersonalAccessTokenFactory;
use Laravel\Passport\HasApiTokens as BaseHasApiTokens;
use SMartins\PassportMultiauth\Config\AuthConfigHelper;
use SMartins\PassportMultiauth\Exceptions\MissingConfigException;

trait HasMultiAuthApiTokens
{
    use BaseHasApiTokens;

    /**
     * Get all of the access tokens for the user relating with Provider.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function tokens()
    {
        return $this->hasMany(Token::class, 'user_id')
            ->join('oauth_access_token_providers', function ($join) {
                $join->on(
                    'oauth_access_tokens.id', '=', 'oauth_access_token_id'
                )->where('oauth_access_token_providers.provider', '=', AuthConfigHelper::getUserProvider($this));
            })->orderBy('created_at', 'desc')
            ->select('oauth_access_tokens.*')
            ->get();
    }

    /**
     * Create a new personal access token for the user and create .
     *
     * @param  string $name
     * @param  array $scopes
     * @return PersonalAccessTokenResult
     * @throws MissingConfigException
     */
    public function createToken($name, array $scopes = [])
    {
        // Backup default provider
        $defaultProvider = config('auth.guards.api.provider');

        $userProvider = AuthConfigHelper::getUserProvider($this);

        // Change config to when the token is created set the provider from model creating the token.
        config(['auth.guards.api.provider' => $userProvider]);

        $token = Container::getInstance()->make(PersonalAccessTokenFactory::class)->make(
            $this->getKey(), $name, $scopes
        );

        // Reset config to defaults
        config(['auth.guards.api.provider' => $defaultProvider]);

        return $token;
    }
}
