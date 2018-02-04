<?php

namespace SMartins\PassportMultiauth\Tests;

use SMartins\PassportMultiauth\Providers\MultiauthServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function testIfTheServiceProviderWasLoaded()
    {
        $passportMultiauth = MultiauthServiceProvider::class;
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey($passportMultiauth, $providers);
    }
}
