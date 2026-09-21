<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Trait;
use Closure;
use BadMethodCallException;

/**
 * Prototype Trait
 */
trait PrototypeTrait
{
    private static array $prototypeMethods = [];

    /**
     * Define a prototype method
     * 
     * @param string $name
     * @param Closure $closure
     * 
     * @return void
     */
    public static function defineMethod(string $name, Closure $closure): void
    {
        self::$prototypeMethods[$name] = $closure;
    }

    /**
     * Magic method to handle calls to prototype methods
     * 
     * @param string $name
     * @param array $arguments
     * 
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (isset(self::$prototypeMethods[$name])) {
            $bound = self::$prototypeMethods[$name]->bindTo($this, static::class);
            return $bound(...$arguments);
        }

        throw new BadMethodCallException("Method '$name' not found.");
    }
}