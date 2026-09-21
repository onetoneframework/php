<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

use Clover\Classes\CLI\Input;

/**
 * Command Interface
 *
 * Defines the contract for command-line commands in the framework.
 * All commands must implement this interface to be executable.
 */
interface CommandInterface
{
    /**
     * @var array Command arguments.
     */
    //public array $arguments { get; set; }

    /**
     * @var array Command options.
     */
    //public array $options { get; set; }

    /**
     * Get the command name.
     *
     * @return string The command name (e.g., "database:query").
     */
    public function getName(): string;

    /**
     * Get the command description.
     *
     * @return string A brief description of what the command does.
     */
    public function getDescription(): string;

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    public function configure(): void;

    /**
     * Execute the command.
     *
     * @param Input $input The input object containing options and arguments.
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool;
}
