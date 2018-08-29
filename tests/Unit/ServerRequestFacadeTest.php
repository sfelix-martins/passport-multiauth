<?php

namespace SMartins\PassportMultiauth\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request;
use SMartins\PassportMultiauth\Facades\ServerRequest;

class ServerRequestFacadeTest extends TestCase
{
    public function testCreateRequest()
    {
        $symfonyRequest = Request::create('/');

        $psrRequest = ServerRequest::createRequest($symfonyRequest);

        $this->assertInstanceOf(ServerRequestInterface::class, $psrRequest);
    }
}
