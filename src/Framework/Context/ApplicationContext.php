<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Context;

use Clover\Abstract\Interceptor;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Proxy\BaseProxy;

/**
 * Application Context
 */
class ApplicationContext
{
    /** @var array $environment The environment variables for the application */
    private static array $environment = [];

    /** @var array[Interceptor] $interceptors The interceptors for the application */
    private static array $interceptors = [];

    /** @var ?Container $container The dependency injection container for managing application services and dependencies */
    private static ?Container $container = null;

    /**
     * Set environment variables
     *
     * @param array $environment The environment variables for the application
     *
     * @return void
     */
    public static function setEnvironment(array $environment): void
    {
        self::$environment = $environment;
    }

    /**
     * Get environment variables
     *
     * @return array
     */
    public static function getEnvironment(): array
    {
        return self::$environment;
    }

    /**
     * Clear environment variables
     * 
     * @return void
     */
    public static function clearEnvironment(): void
    {
        self::$environment = [];
    }

    /**
     * Set container
     *
     * @param BaseProxy|Container $container The dependency injection container for managing application services and dependencies
     *
     * @return void
     */
    public static function setContainer(BaseProxy|Container $container): void
    {
        self::$container = $container;
    }

    /**
     * Get container
     *
     * @return ?Container
     */
    public static function getContainer(): ?Container
    {
        return self::$container;
    }

    /**
     * Set interceptors
     *
     * @param array<Interceptor> $interceptors The interceptors for the application
     *
     * @return void
     */
    public static function setInterceptors(array $interceptors): void
    {
        self::$interceptors = array_merge(self::$interceptors, $interceptors);
    }

    /**
     * Get interceptors
     *
     * @return array<Interceptor>
     */
    public static function getInterceptors(): array
    {
        return self::$interceptors;
    }

    /**
     * Clear interceptors
     * 
     * @return void
     */
    public static function clearInterceptors(): void
    {
        self::$interceptors = [];
    }

}
