<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use Mockery;
use SMartins\PassportMultiauth\Tests\TestCase;
use SMartins\PassportMultiauth\Testing\MultiauthActions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;

class MultiauthActionsTest extends TestCase
{
    use MultiauthActions;

    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/../Fixtures/factories');

        $this->setAuthConfigs();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testMultiauthActingsWithoutInitializePassport()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = factory(User::class)->create();

        $this->multiauthActingAs($user);
    }

    public function testMultiauthActingsWithoutDefaultProvider()
    {
        $this->setUpLaravelPassport();

        $company = factory(Company::class)->create();

        $httpRequest = $this->multiauthActingAs($company);

        $this->assertArrayHasKey('Authorization', $httpRequest->defaultHeaders);
        $this->assertNotNull($httpRequest->defaultHeaders['Authorization']);
    }

    public function testMultiauthActingsAsGenerateAccessToken()
    {
        $this->setUpLaravelPassport();

        $user = factory(User::class)->create();

        $httpRequest = $this->multiauthActingAs($user);

        $this->assertArrayHasKey('Authorization', $httpRequest->defaultHeaders);
        $this->assertNotNull($httpRequest->defaultHeaders['Authorization']);
    }
}
