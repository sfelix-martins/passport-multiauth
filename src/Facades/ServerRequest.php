<?php

namespace SMartins\PassportMultiauth\Facades;

use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

class ServerRequest
{
    public static function createRequest(Request $symfonyRequest)
    {
        return (new DiactorosFactory())->createRequest($symfonyRequest);
    }
}
