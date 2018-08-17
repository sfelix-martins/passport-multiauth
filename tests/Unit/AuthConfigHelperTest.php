<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use SMartins\PassportMultiauth\Config\AuthConfigHelper;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Customer;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\TestCase;

class AuthConfigHelperTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->setAuthConfigs();
    }

    public function testGetProviderGuard()
    {
        $guard = AuthConfigHelper::getProviderGuard('companies');

        $this->assertEquals('company', $guard);
    }

    public function testGetProviderGuardWithNotPassportDriver()
    {
        config(['auth.guards.customer.driver' => 'token']);
        config(['auth.guards.customer.provider' => 'customers']);

        config(['auth.providers.customers.driver' => 'eloquent']);
        config(['auth.providers.customers.model' => Customer::class]);

        $guard = AuthConfigHelper::getProviderGuard('customers');

        $this->assertNull($guard);
    }

    public function testGetUserGuard()
    {
        $guard = AuthConfigHelper::getUserGuard(new User);

        $this->assertEquals('api', $guard);
    }

    public function testGetUserGuardToCompanyModel()
    {
        $guard = AuthConfigHelper::getUserGuard(new Company);

        $this->assertEquals('company', $guard);
    }

    public function testGetUserProviderWithModelNotExistentOnProviders()
    {
        $provider = AuthConfigHelper::getUserProvider(new Customer);

        $this->assertNull($provider);
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
    }
}
