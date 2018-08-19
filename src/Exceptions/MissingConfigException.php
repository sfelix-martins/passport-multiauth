<?php

namespace SMartins\PassportMultiauth\Exceptions;

use Exception;
use Illuminate\Contracts\Auth\Authenticatable;

class MissingConfigException extends Exception
{
    /**
     * @param string $message
     */
    public function __construct($message)
    {
        $message .= '. Please, check your config/auth.php file.';

        parent::__construct($message, 0, null);
    }

    /**
     * @param Authenticatable $model
     * @param string $driver
     * @return MissingConfigException
     */
    public static function guard(Authenticatable $model, $driver = 'passport')
    {
        $message = 'Any guard with driver "'.$driver.'" found to '.get_class($model);

        return new static($message);
    }

    /**
     * @param Authenticatable $model
     * @return MissingConfigException
     */
    public static function provider(Authenticatable $model)
    {
        $message = 'Any provider found to '.get_class($model);

        return new static($message);
    }
}
