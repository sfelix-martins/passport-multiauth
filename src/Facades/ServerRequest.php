<?php

namespace SMartins\PassportMultiauth\Facades;

use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

class ServerRequest
{
    /**
     * @todo Switch deprecated DiactorosFactory by PsrHttpFactory
     * @param Request $symfonyRequest
     * @return \Psr\Http\Message\RequestInterface|\Psr\Http\Message\ServerRequestInterface|\Zend\Diactoros\ServerRequest
     */
    public static function createRequest(Request $symfonyRequest)
    {
        return (new DiactorosFactory())->createRequest($symfonyRequest);
    }
}
