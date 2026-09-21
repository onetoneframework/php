<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Abstract;

use Clover\Classes\Reflection\Handler as ReflectionHandler;

/**
 * Singleton Abstract Class
 *
 * Provides a singleton pattern implementation.
 * Ensures only one instance of a class exists throughout the application lifecycle.
 */
abstract class Singleton
{
    /**
     * @var array<string, object> Array of singleton instances keyed by class name.
     */
    private static $instances = [];

    /**
     * Get the singleton instance of the called class.
     *
     * @return static The singleton instance of the called class.
     */
    final public static function instance()
    {
        $calledClass = ReflectionHandler::getCalledClass();

        if (!isset(self::$instances[$calledClass])) {
            self::$instances[$calledClass] = new $calledClass;
        }

        return self::$instances[$calledClass];
    }

    /**
     * Prevent cloning of the singleton instance.
     *
     * @return void
     */
    public function __clone()
    {
    }

}