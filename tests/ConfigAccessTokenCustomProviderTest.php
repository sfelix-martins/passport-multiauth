<?php

namespace SMartins\PassportMultiauth\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use League\OAuth2\Server\ResourceServer;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use SMartins\PassportMultiauth\Http\Middleware\ConfigAccessTokenCustomProvider;
use SMartins\PassportMultiauth\ProviderRepository;
use SMartins\PassportMultiauth\Provider;

class ConfigAccessTokenCustomProviderTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    public function testTryConfigWithoutAccessTokenId()
    {
        $server = \Mockery::mock(ResourceServer::class);

        $repository = \Mockery::mock(ProviderRepository::class);
        $repository->shouldReceive('findForToken')->andReturn(new Provider);

        $middleware = new ConfigAccessTokenCustomProvider($server, $repository, new App);

        $request = $this->createRequest();
        $response = $middleware->handle($request, function () {
            return 'response';
        });

        dd($response);
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

    public function createRequest()
    {
        $request = \Mockery::mock(Request::class);
        $request->shouldReceive('headers')->andReturn(new HeaderBag([
            "host" => [
                "testing.dev"
            ],
            "user-agent" => [
                "Symfony/3.X"
            ],
            "accept" => [
                "application/json"
            ],
            "accept-language" => [
                "en-us,en;q=0.5"
            ],
            "accept-charset" => [
                "ISO-8859-1,utf-8;q=0.7,*;q=0.7"
            ],
            "content-length" => [
                 148
            ],
            "content-type" => [
                "application/json"
            ],
        ]));

        dd($request->headers);

        dd($request->headers->all());

        $request->shouldReceive('server->all()')->andReturn([
            "SERVER_NAME" => "testing.dev",
            "SERVER_PORT" => 80,
            "HTTP_HOST" => "testing.dev",
            "HTTP_USER_AGENT" => "Symfony/3.X",
            "HTTP_ACCEPT" => "application/json",
            "HTTP_ACCEPT_LANGUAGE" => "en-us,en;q=0.5",
            "HTTP_ACCEPT_CHARSET" => "ISO-8859-1,utf-8;q=0.7,*;q=0.7",
            "REMOTE_ADDR" => "127.0.0.1",
            "SCRIPT_NAME" => "",
            "SCRIPT_FILENAME" => "",
            "SERVER_PROTOCOL" => "HTTP/1.1",
            "REQUEST_TIME" => 1517577267,
            "HTTP_CONTENT_LENGTH" => 146,
            "CONTENT_TYPE" => "application/json",
            "PATH_INFO" => "",
            "REQUEST_METHOD" => "POST",
            "REQUEST_URI" => "/v0/users",
            "QUERY_STRING" => "",
        ]);

        return $request;
    }
}
