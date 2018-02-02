<?php

namespace SMartins\PassportMultiauth\Tests;

use SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider;

class ConfigAccessTokenCustomProviderTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    public function testTryConfigWithoutAccessTokenId()
    {
        //
    }

    public function testTryConfigWithNotExistentAccessToken()
    {
        //
    }

    public function testTryConfigWithJustOneEntityWithOnlyOneIdOnProviders()
    {
        //
    }

    public function testTryConfigWithoutGuardsOnAuthMiddleware()
    {
        //
    }

    public function testTryConfigWithMoreThanOneGuardsOnAuthMiddleware()
    {
        //
    }

    public function testTryConfigWithProviderNotEqualsDefaultGuardProvider()
    {
        //
    }
}
