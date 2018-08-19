<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use SMartins\PassportMultiauth\Config\AuthConfigHelper;
use SMartins\PassportMultiauth\Exceptions\MissingConfigException;
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
        $guard = AuthConfigHelper::getProviderGuard('companies', new Company);

        $this->assertEquals('company', $guard);
    }

    public function testGetProviderGuardWithNotPassportDriver()
    {
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('Any guard with driver "passport" found to '.Customer::class. '. Please, check your config/auth.php file.');

        config(['auth.guards.customer.driver' => 'token']);
        config(['auth.guards.customer.provider' => 'customers']);

        config(['auth.providers.customers.driver' => 'eloquent']);
        config(['auth.providers.customers.model' => Customer::class]);

        AuthConfigHelper::getProviderGuard('customers', new Customer);
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
        $this->expectException(MissingConfigException::class);
        $this->expectExceptionMessage('Any provider found to '.Customer::class.'. Please, check your config/auth.php file.');

        AuthConfigHelper::getUserProvider(new Customer);
    }
}
