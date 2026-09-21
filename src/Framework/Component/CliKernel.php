<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\BaseClass;
use Clover\Classes\CLI\CLIRouter;
use Clover\Classes\DependencyInjection\Container;

use function sprintf;

/**
 * CLI Kernel
 * 
 * The CliKernel is responsible for handling command-line interface (CLI) commands. It uses the CLIRouter to route and dispatch CLI commands defined in the specified directories. This allows developers to easily create and manage CLI commands for their applications.
 */
class CliKernel extends BaseClass
{
    /** @var Container $container The dependency injection container for managing application services and dependencies, which allows the kernel to resolve and utilize various services needed during CLI command handling, such as routing, command execution, and more. This enables the kernel to efficiently handle CLI commands while maintaining a clean separation of concerns and promoting modularity in the application architecture. */
    private Container $container;

    /**
     * Constructor
     *
     * @param Container $container The dependency injection container for managing application services and dependencies, which allows the kernel to resolve and utilize various services needed during CLI command handling, such as routing, command execution, and more. This enables the kernel to efficiently handle CLI commands while maintaining a clean separation of concerns and promoting modularity in the application architecture.
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Run CLI Kernel
     *
     * @return mixed
     */
    public function run(): mixed
    {
        $cliRouter = new CLIRouter();
        $cliRouter->fromDirectory(BASE_PATH . '/App/Command');
        $cliRouter->fromDirectory(sprintf("%s/../src/Command", BASE_PATH));

        /** @var CLIRouter $cliRouter */
        $cliRouter = self::setBaseProxy($cliRouter);

        return $cliRouter->dispatch($_SERVER['argv']);
    }
}
