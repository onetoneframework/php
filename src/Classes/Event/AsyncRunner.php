<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

use Exception;
use Throwable;

class AsyncRunner
{
    private array $tasks = [];
    private array $childPids = []; // Stores PIDs of active child processes: [pid => taskName]
    private int $maxConcurrentTasks; // Maximum number of tasks to run concurrently

    public function __construct(int $maxConcurrentTasks = 5)
    {
        if (!function_exists('pcntl_fork')) {
            throw new Exception("The pcntl extension is not available. This class only works in Linux/Unix environments.");
        }
        $this->maxConcurrentTasks = $maxConcurrentTasks;

        pcntl_signal(SIGCHLD, function ($signo) {
            while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
                if (isset($this->childPids[$pid])) {
                    $taskName = $this->childPids[$pid];
                    unset($this->childPids[$pid]);
                }
            }
        });
    }

    /**
     * Registers an asynchronous task to be executed.
     *
     * @param callable $callback The callback function to execute (no arguments).
     * @param string $taskName The name of the task (optional).
     */
    public function addTask(callable $callback, string $taskName = 'Unnamed Task'): void
    {
        $this->tasks[] = [
            'callback' => $callback,
            'name' => $taskName
        ];
    }

    /**
     * Executes all registered tasks concurrently.
     */
    public function run(): void
    {
        foreach ($this->tasks as $task) {
            // Wait if the maximum number of concurrent tasks is reached
            while (count($this->childPids) >= $this->maxConcurrentTasks) {
                $this->waitForAChildToComplete();
            }

            $pid = pcntl_fork();

            if ($pid === -1) {
                error_log("Failed to fork child process for task: {$task['name']}");
                continue;
            } elseif ($pid === 0) {
                $currentPid = getmypid();

                try {
                    $task['callback']();
                } catch (Throwable $e) {
                    error_log("[Child Process {$currentPid}] Error in task '{$task['name']}': " . $e->getMessage());
                }
            } else {
                $this->childPids[$pid] = $task['name'];
            }
        }

        $this->waitForAllChildrenToComplete();
    }

    /**
     * Waits for a single child process to terminate.
     * This method blocks until one child exits.
     */
    private function waitForAChildToComplete(): void
    {
        pcntl_wait($status);
        pcntl_signal_dispatch();
    }

    /**
     * Waits for all child processes to terminate.
     */
    private function waitForAllChildrenToComplete(): void
    {
        while (count($this->childPids) > 0) {
            pcntl_wait($status);
            pcntl_signal_dispatch();
        }
    }
}
