<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\StandardPHPLibrary;

/**
 * Class Autoload
 *
 * @package Clover\Classes\StandardPHPLibrary
 */
class Autoload
{
    /** 
     * Get all registered autoload functions
     *
     * @return array
     */
    public function getFunctions(): array
    {
        return spl_autoload_functions();
    }

    /**
     * Register a function with the SPL __autoload stack
     *
     * @param callable|null $callback
     * @param bool $throw
     * @param bool $prepend
     *
     * @return void
     */
    public function registFunction(callable|null $callback = null, bool $throw = true, bool $prepend = false): void
    {
        spl_autoload_register($callback, $throw, $prepend);
    }

    /**
     * Unregister a function from the SPL __autoload stack
     *
     * @param callable $callback
     *
     * @return void
     */
    public function unregistFunction(callable $callback): void
    {
        spl_autoload_unregister($callback);
    }

    /**
     * Get or set the file extensions that spl_autoload() will look for
     *
     * @param string|null $file_extensions
     *
     * @return string
     */
    public function setDefaultExtensions(string|null $file_extensions = null): string
    {
        return spl_autoload_extensions($file_extensions);
    }

    /**
     * Call the default implementation of the spl_autoload() function
     *
     * @param string $class
     *
     * @return void
     */
    public function callAutoloadFunction(string $class): void
    {
        spl_autoload_call($class);
    }
}