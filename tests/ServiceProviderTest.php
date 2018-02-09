<?php

namespace SMartins\PassportMultiauth\Tests;

use SMartins\PassportMultiauth\Provider;
use Illuminate\Database\Capsule\Manager as DB;
use Laravel\Passport\Events\AccessTokenCreated;
use Illuminate\Database\Eloquent\Model as Eloquent;
use SMartins\PassportMultiauth\Providers\MultiauthServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->schema()->create('oauth_access_token_providers', function ($table) {
            $table->string('oauth_access_token_id', 100)->primary();
            $table->string('provider');
            $table->timestamps();
            $table->foreign('oauth_access_token_id')
                ->references('id')
                ->on('oauth_access_tokens')
                ->onDelete('cascade');
        });
    }

    public function testIfTheServiceProviderWasLoaded()
    {
        $passportMultiauth = MultiauthServiceProvider::class;
        $providers = $this->app->getLoadedProviders();

        $this->assertArrayHasKey($passportMultiauth, $providers);
    }

    public function testCreateAccessTokenProvider()
    {
        $userId = 1;
        $tokenId = 232;
        $clientId = 3;
        $provider = 'admins';

        $this->app['config']->set('auth.guards.api.provider', $provider);

        event(new AccessTokenCreated($userId, $tokenId, $clientId));

        $accessTokenProvider = Provider::first();

        $this->assertEquals($accessTokenProvider->provider, $provider);
    }

    /**
     * Schema Helpers.
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }
}
