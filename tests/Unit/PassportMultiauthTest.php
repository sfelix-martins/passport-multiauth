<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use Exception;
use SMartins\PassportMultiauth\Tests\TestCase;
use SMartins\PassportMultiauth\PassportMultiauth;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Customer;

class PassportMultiauthTest extends TestCase
{
    public function setUp(): void
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

    public function testActingAsWithUserThatNotUsesHasApiTokens()
    {
        $this->expectException(Exception::class);

        PassportMultiauth::actingAs(new Customer);
    }
}
