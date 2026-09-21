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
 * Kernel Interface
 *
 * Defines the core methods for the application kernel lifecycle.
 */
interface KernelInterface
{
    /**
     * Boot the application kernel.
     */
    public function boot(): void;

    /**
     * Terminate the application kernel.
     */
    public function terminate(): void;
    
    /**
     * Get the application container key.
     */
    public function getContainer(): ContainerInterface;
}
