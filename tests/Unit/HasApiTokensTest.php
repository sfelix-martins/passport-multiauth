<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\TestCase;

class HasApiTokensTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->artisan('passport:install');

        $this->artisan('key:generate');

        $this->withFactories(__DIR__.'/../Fixtures/factories');

        $this->setAuthConfigs();
    }

    public function testCreatePersonalAccessToken()
    {
        $user = factory(User::class)->create();

        $token = $user->createToken('Token test')->token;

        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'id' => $token->id,
        ]);

        $this->assertDatabaseHas('oauth_access_token_providers', [
            'provider' => 'users',
            'oauth_access_token_id' => $token->id,
        ]);
    }

    public function testCreatePersonalAccessTokenToAnotherModel()
    {
        $user = factory(Company::class)->create();

        $token = $user->createToken('Token test')->token;

        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'id' => $token->id,
        ]);

        $this->assertDatabaseHas('oauth_access_token_providers', [
            'provider' => 'companies',
            'oauth_access_token_id' => $token->id,
        ]);
    }

    public function testGetUserCreatedTokens()
    {
        $company = factory(Company::class)->create();
        for ($i = 0; $i < 3; $i++) {
            $company->createToken('Company Token ' . ($i + 1));
        }

        $user = factory(User::class)->create();
        for ($i = 0; $i < 1; $i++) {
            $user->createToken('User Token ' . ($i + 1));
        }

        $this->assertCount(3, $company->tokens());
        $this->assertCount(1, $user->tokens());

        $i = 0;
        foreach ($company->tokens() as $token) {
            $this->assertEquals('Company Token '.($i + 1), $token->name);
            $i++;
        }

        $i = 0;
        foreach ($user->tokens() as $token) {
            $this->assertEquals('User Token '.($i + 1), $token->name);
            $i++;
        }
    }
}
