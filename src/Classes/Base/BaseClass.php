<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use Clover\Classes\Proxy\BaseProxy;

/**
 * Class BaseClass
 *
 * Provides a base class with proxy support.
 * 
 * @package Clover\Classes
 */
class BaseClass
{
    /**
     * Initializes the base class instance.
     */
    public function __construct()
    {
    }

    /**
     * Set a base proxy for the given object if the USE_PROXY environment variable is set to "true"
     *
     * @param object $object The object to potentially wrap in a proxy
     * 
     * @return object The original object or a BaseProxy wrapping the object
     */
    public static function setBaseProxy(object $object): object
    {
        if (isset($_ENV['USE_PROXY']) && $_ENV['USE_PROXY'] === "true") {
            return new BaseProxy($object);
        }

        return $object;
    }

    /**
     * Releases resources when the instance is destroyed (no-op by default).
     */
    public function __destruct()
    {
        //
    }
}