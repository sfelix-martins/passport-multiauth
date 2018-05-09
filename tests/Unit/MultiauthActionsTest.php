<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use Mockery;
use Illuminate\Support\Facades\App;
use SMartins\PassportMultiauth\Tests\TestCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SMartins\PassportMultiauth\Testing\MultiauthActions;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\User;
use SMartins\PassportMultiauth\Tests\Fixtures\Models\Company;

class MultiauthActionsTest extends TestCase
{
    /**
     * Store app version.
     *
     * @var float
     */
    protected $appVersion;

    use MultiauthActions;

    public function setUp()
    {
        parent::setUp();

        $this->loadLaravelMigrations(['--database' => 'passport']);

        $this->artisan('migrate');

        $this->withFactories(__DIR__.'/../Fixtures/factories');

        $this->setAuthConfigs();

        $this->appVersion = (float) App::version();
    }

    public function tearDown()
    {
        Mockery::close();
    }

    public function testMultiauthActingsWithoutInitializePassport()
    {
        $this->expectException(ModelNotFoundException::class);

        $user = factory(User::class)->create();

        if ($this->appVersion >= 5.5) {
            $this->multiauthActingAs($user);
        } else {
            $this->multiauthAccessToken($user);
        }
    }

    public function testMultiauthActingsWithoutDefaultProvider()
    {
        $this->setUpLaravelPassport();

        $company = factory(Company::class)->create();

        if ($this->appVersion >= 5.5) {
            $httpRequest = $this->multiauthActingAs($company);

            $this->assertArrayHasKey('Authorization', $httpRequest->defaultHeaders);
            $this->assertNotNull($httpRequest->defaultHeaders['Authorization']);
        } else {
            $accessToken = $this->multiauthAccessToken($company);

            $this->assertNotNull($accessToken);
        }
    }

    public function testMultiauthActingsAsGenerateAccessToken()
    {
        $this->setUpLaravelPassport();

        $user = factory(User::class)->create();

        if ($this->appVersion >= 5.5) {
            $httpRequest = $this->multiauthActingAs($user);

            $this->assertArrayHasKey('Authorization', $httpRequest->defaultHeaders);
            $this->assertNotNull($httpRequest->defaultHeaders['Authorization']);
        } else {
            $accessToken = $this->multiauthAccessToken($user);

            $this->assertNotNull($accessToken);
        }
    }

    public function testTryUseMultiauthActingAsWithVersionLessThan55()
    {
        $this->setUpLaravelPassport();
        $this->expectException(\RuntimeException::class);

        App::shouldReceive('version')->andReturn('5.4.36');

        $this->multiauthActingAs(factory(User::class)->create());
    }
}
