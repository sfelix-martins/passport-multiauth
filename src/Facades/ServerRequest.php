<?php

namespace SMartins\PassportMultiauth\Facades;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

class ServerRequest
{
    public static function createRequest(Request $symfonyRequest)
    {
        return (new DiactorosFactory())->createRequest($symfonyRequest);
    }
}
