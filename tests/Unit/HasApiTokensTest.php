<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use SMartins\PassportMultiauth\Tests\TestCase;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Admin;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;
use SMartins\PassportMultiauth\Exceptions\MissingConfigException;

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
            $company->createToken('Company Token '.($i + 1));
        }

        $user = factory(User::class)->create();
        for ($i = 0; $i < 1; $i++) {
            $user->createToken('User Token '.($i + 1));
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

    public function testCreateTokenToModelWithoutProviderConfigs()
    {
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('Any provider found to '.Admin::class.'. Please, check your config/auth.php file.');

        $admin = factory(Admin::class)->create();

        $admin->createToken('Admin token');
    }

    public function testGetTokensToModelWithoutProviderConfigs()
    {
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('Any provider found to '.Admin::class.'. Please, check your config/auth.php file.');

        $admin = factory(Admin::class)->create();

        $admin->tokens();
    }

    /**
     * Setup auth configs.
     *
     * @return void
     */
    protected function setAuthConfigs()
    {
        // Set up default entity
        config(['auth.guards.api.driver' => 'passport']);
        config(['auth.guards.api.provider' => 'users']);
        config(['auth.providers.users.model' => User::class]);

        // Set up company entity
        config(['auth.guards.company.driver' => 'passport']);
        config(['auth.guards.company.provider' => 'companies']);
        config(['auth.providers.companies.driver' => 'eloquent']);
        config(['auth.providers.companies.model' => Company::class]);

        // Set up Admin entity, without providers
        config(['auth.guards.admin.driver' => 'passport']);
        /*
        config(['auth.guards.admin.provider' => 'admins']);
        config(['auth.providers.admins.driver' => 'eloquent']);
        config(['auth.providers.admins.model' => Company::class]);
        */
    }
}
