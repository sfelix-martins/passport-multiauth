<?php

namespace SMartins\PassportMultiauth\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Zend\Diactoros\ServerRequest createRequest(\Symfony\Component\HttpFoundation\Request $request)
 */
class ServerRequest extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ServerRequest';
    }
}
