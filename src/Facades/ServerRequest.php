<?php

namespace SMartins\PassportMultiauth\Facades;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

/**
 * Facade to create Psr\Http\Message\ServerRequestInterface from Symfony Request.
 *
 * @author Samuel Martins <sam.martins.dev@gmail.com>
 */
class ServerRequest
{
    public static function createRequest(Request $symfonyRequest)
    {
        return (new DiactorosFactory())->createRequest($symfonyRequest);
    }
}
