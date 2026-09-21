<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Contract;

/**
 * Container Interface
 *
 * Defines the essential methods for a dependency injection container.
 */
interface ContainerInterface
{
    /**
     * Register a shared binding in the container.
     */
    public function singleton(string $abstract, mixed $concrete = null): void;

    /**
     * Register a binding with the container.
     */
    public function bind(string $abstract, mixed $concrete = null): void;

    /**
     * Get the concrete type for a given abstract.
     */
    public function make(string $abstract, array $parameters = []): mixed;
}
