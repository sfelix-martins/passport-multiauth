<?php

namespace SMartins\PassportMultiauth\Facades;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;

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
