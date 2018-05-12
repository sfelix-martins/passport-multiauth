<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use SMartins\PassportMultiauth\Tests\TestCase;
use SMartins\PassportMultiauth\PassportMultiauth;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Customer;

class PassportMultiauthTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/../Fixtures/factories');

        $this->setAuthConfigs();
    }

    public function testActingAsWithScopes()
    {
        $user = factory(User::class)->create();

        $scopes = ['check-scopes', 'test-packages'];
        PassportMultiauth::actingAs($user, $scopes);

        foreach ($scopes as $scope) {
            $this->assertTrue($user->tokenCan($scope));
        }
    }

    public function testGetUserProviderWithModelNotExistentOnProviders()
    {
        $model = new Customer;

        $provider = PassportMultiauth::getUserProvider($model);

        $this->assertNull($provider);
    }

    public function testGetProviderGuardWithNotPassportDriver()
    {
        config(['auth.guards.customer.driver' => 'token']);
        config(['auth.guards.customer.provider' => 'customers']);

        config(['auth.providers.customers.driver' => 'eloquent']);
        config(['auth.providers.customers.model' => Customer::class]);

        $guard = PassportMultiauth::getProviderGuard('customers');

        $this->assertNull($guard);
    }

    protected function modelNotFoundOnProviders()
    {
        return new class() extends Authenticatable {
        };
    }
}
