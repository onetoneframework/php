<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Support;

use Clover\Contract\ContainerInterface;

/**
 * Base Service Provider
 *
 * Abstract base class for all application service providers.
 * Defines standard lifecycle methods for registering and booting services.
 */
abstract class ServiceProvider
{
    /**
     * The container instance.
     */
    protected ContainerInterface $app;

    /**
     * Create a new service provider instance.
     */
    public function __construct(ContainerInterface $app)
    {
        $this->app = $app;
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
