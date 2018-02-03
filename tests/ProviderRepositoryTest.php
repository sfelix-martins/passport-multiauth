<?php

namespace SMartins\PassportMultiauth\Tests;

use SMartins\PassportMultiauth\Provider;
use SMartins\PassportMultiauth\ProviderRepository;

class ProviderRepositoryTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(realpath(__DIR__.'/../database/migrations'));
    }

    public function testFindForToken()
    {
        $provider = new Provider;
        $provider->provider = 'companies';
        $provider->oauth_access_token_id = 'token';
        $provider->save();

        $repository = new ProviderRepository;
        $response = $repository->findForToken('token');

        $this->assertInstanceOf(Provider::class, $response);
    }

    public function testFindForTokenWithoutCreatedToken()
    {
        $repository = new ProviderRepository;
        $response = $repository->findForToken('token');

        $this->assertNull($response);
    }

    public function testCreateProvider()
    {
        $repository = new ProviderRepository;
        $response = $repository->create(1, 'users');

        $this->assertInstanceOf(Provider::class, $response);
        $this->assertEquals($response->oauth_access_token_id, 1);
        $this->assertEquals($response->provider, 'users');
    }
}
