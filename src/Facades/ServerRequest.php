<?php

namespace SMartins\PassportMultiauth\Facades;

use Exception;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Request;

class ServerRequest
{
    /**
     * @todo Remove deprecated DiactorosFactory in favor of PsrHttpFactory
     * @param Request $symfonyRequest
     * @return \Psr\Http\Message\RequestInterface|\Psr\Http\Message\ServerRequestInterface|\Zend\Diactoros\ServerRequest
     */
    public static function createRequest(Request $symfonyRequest)
    {
        if (class_exists(Psr17Factory::class) && class_exists(PsrHttpFactory::class)) {
            $psr17Factory = new Psr17Factory;

            return (new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))
                ->createRequest($symfonyRequest);
        }

        if (class_exists(DiactorosFactory::class)) {
            return (new DiactorosFactory)->createRequest($symfonyRequest);
        }

        throw new Exception('Unable to resolve PSR request. Please install symfony/psr-http-message-bridge and nyholm/psr7.');
    }
}
