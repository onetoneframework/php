<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Event;

/**
 * Pcntl class
 */
class Pcntl
{
    /** @var int $pid Process ID of the child process */
    private $pid = 0;

    /**
     * Constructor
     * 
     * @throws \Exception if the pcntl extension is not loaded
     */
    public function __construct()
    {
        if (!extension_loaded('pcntl')) {
            throw new \Exception('The pcntl is not installed');
        }
    }

    /**
     * Call a callback in a separate process
     * 
     * @param callable $callback
     * 
     * @return mixed
     * 
     * @throws \Exception if pcntl_fork function is not exists or cannot fork
     */
    public function call(callable $callback): mixed
    {
        if (!function_exists('pcntl_fork')) {
            throw new \Exception('pcntl_fork function is not exists');
        }

        $this->pid = pcntl_fork();

        if ($this->pid === -1) {
            throw new \Exception('Pcntl cannot fork');
        }

        if ($this->pid === 0) {
            $status = $callback();

            return $status;
        }

        return false;
    }

    /**
     * Wait for the child process to finish and get its exit code
     * 
     * @return int Exit code of the child process
     * 
     * @throws \Exception if pcntl_waitpid or pcntl_wexitstatus function is not exists
     *                    or if called in the child process
     */
    public function wait(): mixed
    {
        if (!function_exists('pcntl_waitpid') || !function_exists('pcntl_wexitstatus')) {
            throw new \Exception('pcntl_waitpid or pcntl_wexitstatus function is not exists');
        }

        if ($this->pid === 0) {
            throw new \Exception('Cannot wait in the child process. You must call wait method in the parent process.');
        }

        pcntl_waitpid($this->pid, $status);
        $exitCode = pcntl_wexitstatus($status);

        return $exitCode;
    }

}
