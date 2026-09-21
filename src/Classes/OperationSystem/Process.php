<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use resource;

class Process
{
    /**
     * Get information about a process opened by proc_open
     * 
     * @param resource $process
     * 
     * @return array{
     *  command: string, 
     *  pid: int, 
     *  running: bool, 
     *  signaled: bool, 
     *  stopped: bool, 
     *  exitcode: int, 
     *  cached: bool, 
     *  termsig: int, 
     *  stopsig: int
     * }
     */
    public static function getStatus(mixed $process): array
    {
        return proc_get_status($process);
    }

    public static function getExitCode(mixed $process): int
    {
        return self::getStatus($process)['exitcode'] ?? -1;
    }

    public static function isStopped(mixed $process): bool
    {
        return self::getStatus($process)['stopped'] ?? false;
    }

    public static function isRunning(mixed $process): bool
    {
        return self::getStatus($process)['running'] ?? false;
    }

    public static function isSignaled(mixed $process): bool
    {
        return self::getStatus($process)['signaled'] ?? false;
    }

    public static function getTermSig(mixed $process): int
    {
        return self::getStatus($process)['termsig'] ?? false;
    }

    public static function terminate(mixed $process, int $signal = 15): bool
    {
        return proc_terminate($process, $signal);
    }

    public static function setPriority(int $priority): bool
    {
        return proc_nice($priority);
    }

    public static function close(mixed $process): int
    {
        return proc_close($process);
    }

    public static function escapeShellCommand(string $command): string
    {
        return escapeshellcmd($command);
    }

    public static function escapeShellArgument(string $argument): string
    {
        return escapeshellarg($argument);
    }

    public static function passThrough(string $command, ?int $result_code = null): bool|null
    {
        return passthru($command, $result_code);
    }
}
