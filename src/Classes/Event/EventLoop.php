<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Event;

use SplQueue;
use InvalidArgumentException;
use Throwable;
use function count;
use function is_resource;

/**
 * Simple Event Loop implementation
 */
final class EventLoop
{
    /** @var SplQueue $microtasks Queue for microtasks to be executed after I/O events */
    private static SplQueue $microtasks;
    /** @var array $timers Array of scheduled timers with their execution time and callback */
    private static array $timers = [];
    /** @var int $timerSeq Sequence number for generating unique timer IDs */
    private static int $timerSeq = 0;
    /** @var array $readWatchers Array of read stream watchers with their stream and callback */
    private static array $readWatchers = [];
    /** @var array $writeWatchers Array of write stream watchers with their stream and callback */
    private static array $writeWatchers = [];
    /** @var bool $running Flag indicating whether the event loop is currently running */
    private static bool $running = false;

    /**
     * Initialize microtask queue
     *
     * @return void
     */
    private static function init(): void
    {
        if (!isset(self::$microtasks)) {
            self::$microtasks = new SplQueue();
        }
    }

    /**
     * Get current state of the event loop
     *
     * @return array{
     *  timers_count: int, 
     *  timer_seq: int, 
     *  read_watchers: int, 
     *  write_watchers: int, 
     *  microtasks_count: int, 
     *  running: bool
     * }
     */
    public static function getState(): array
    {
        return [
            'timers_count' => count(self::$timers),
            'timer_seq' => self::$timerSeq,
            'read_watchers' => count(self::$readWatchers),
            'write_watchers' => count(self::$writeWatchers),
            'microtasks_count' => isset(self::$microtasks) ? self::$microtasks->count() : 0,
            'running' => self::$running,
        ];
    }

    /**
     * Schedule a callback to be executed immediately after I/O events
     *
     * @param callable $callback The callback to execute
     * 
     * @return void
     */
    public static function setImmediate(callable $callback): void
    {
        self::future($callback);
    }

    /**
     * Create a promise that resolves after a delay
     *
     * @param int $ms The delay in milliseconds
     * @param mixed $value The value to resolve the promise with
     * 
     * @return AsyncPromise The promise that resolves after the delay
     */
    public static function delayPromise(int $ms, mixed $value = null): AsyncPromise
    {
        return new AsyncPromise(function ($resolve) use ($ms, $value) {
            self::setTimeout(fn() => $resolve($value), $ms);
        });
    }

    /**
     * Schedule a recurring callback at specified intervals
     *
     * @param callable $callback The callback to execute
     * @param int $ms The interval in milliseconds
     * 
     * @return int The timer ID
     */
    public static function setInterval(callable $callback, int $ms): int
    {
        $timerId = null;
        $wrapper = function () use ($callback, $ms, &$timerId, &$wrapper) {
            try {
                $callback();
            } catch (Throwable $e) {
            }
            $timerId = self::delay($ms, $wrapper);
        };

        $timerId = self::delay($ms, $wrapper);
        return $timerId;
    }

    /**
     * Schedule a callback to be executed after a delay
     *
     * @param callable $callback The callback to execute
     * @param int $ms The delay in milliseconds
     * 
     * @return int The timer ID
     */
    public static function setTimeout(callable $callback, int $ms): int
    {
        return self::delay($ms, $callback);
    }

    /**
     * Schedule a microtask to be executed after I/O events
     *
     * @param callable $cb The callback to execute
     * 
     * @return void
     */
    public static function future(callable $cb): void
    {
        self::init();
        self::$microtasks->enqueue($cb);
    }

    /**
     * Schedule a callback to be executed after a delay
     *
     * @param int $ms The delay in milliseconds
     * @param callable $cb The callback to execute
     * 
     * @return int The timer ID
     */
    public static function delay(int $ms, callable $cb): int
    {
        self::init();
        $id = ++self::$timerSeq;
        self::$timers[$id] = [
            'at' => microtime(true) + $ms / 1000,
            'cb' => $cb
        ];

        return $id;
    }

    /**
     * Reset the event loop state
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$timers = [];
        self::$timerSeq = 0;
        self::$readWatchers = [];
        self::$writeWatchers = [];
        self::$running = false;

        if (!isset(self::$microtasks)) {
            return;
        }

        while (!self::$microtasks->isEmpty()) {
            self::$microtasks->dequeue();
        }
    }

    /**
     * Cancel a scheduled timer
     *
     * @param int $timerId The timer ID to cancel
     * 
     * @return void
     */
    public static function cancel(int $timerId): void
    {
        unset(self::$timers[$timerId]);
    }

    /**
     * Add a read stream watcher
     *
     * @param resource $stream The stream resource to watch
     * @param callable $cb The callback to execute when the stream is readable
     * 
     * @return void
     * 
     * @throws InvalidArgumentException If the provided stream is not a resource
     */
    public static function addReadStream(mixed $stream, callable $cb): void
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('read stream must be resource');
        }

        self::$readWatchers[(int) $stream] = ['stream' => $stream, 'cb' => $cb];
    }

    /**
     * Add a write stream watcher
     *
     * @param resource $stream The stream resource to watch
     * @param callable $cb The callback to execute when the stream is writable
     * 
     * @return void
     * 
     * @throws InvalidArgumentException If the provided stream is not a resource
     */
    public static function addWriteStream(mixed $stream, callable $cb): void
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('write stream must be resource');
        }

        self::$writeWatchers[(int) $stream] = ['stream' => $stream, 'cb' => $cb];
    }

    /**
     * Remove a read stream watcher
     *
     * @param resource $stream The stream resource to remove
     * 
     * @return void
     */
    public static function removeReadStream(mixed $stream): void
    {
        unset(self::$readWatchers[(int) $stream]);
    }

    /**
     * Remove a write stream watcher
     *
     * @param resource $stream The stream resource to remove
     * 
     * @return void
     */
    public static function removeWriteStream(mixed $stream): void
    {
        unset(self::$writeWatchers[(int) $stream]);
    }

    /**
     * Run the event loop
     *
     * @return void
     */
    public static function run(): void
    {
        self::init();
        self::$running = true;

        while (self::$running) {
            $now = microtime(true);
            $readyTimers = [];
            foreach (self::$timers as $id => $t) {
                if ($t['at'] <= $now) {
                    $readyTimers[$id] = $t;
                }
            }

            uasort($readyTimers, fn($a, $b) => $a['at'] <=> $b['at']);
            foreach ($readyTimers as $id => $t) {
                unset(self::$timers[$id]);
                try {
                    ($t['cb'])();
                } catch (Throwable) {
                }
            }

            while (!self::$microtasks->isEmpty()) {
                $task = self::$microtasks->dequeue();
                try {
                    $task();
                } catch (Throwable) {
                }
            }

            if (self::$microtasks->isEmpty() && empty(self::$timers) && empty(self::$readWatchers) && empty(self::$writeWatchers)) {
                break;
            }

            $r = array_column(self::$readWatchers, 'stream');
            $w = array_column(self::$writeWatchers, 'stream');
            $timeoutSec = 0;
            $timeoutUsec = 500_000;

            if (!empty(self::$timers)) {
                $nextAt = min(array_column(self::$timers, 'at'));
                $delta = max(0.0, $nextAt - microtime(true));
                $timeoutSec = (int) $delta;
                $timeoutUsec = (int) (($delta - $timeoutSec) * 1_000_000);
            }

            if (empty($r) && empty($w)) {
                usleep($timeoutSec * 1_000_000 + $timeoutUsec);
                continue;
            }

            $rArr = $r;
            $wArr = $w;
            $except = null;
            $n = @stream_select($rArr, $wArr, $except, $timeoutSec, $timeoutUsec);
            if ($n === false) {
                continue;
            }

            foreach ($rArr as $st) {
                $info = self::$readWatchers[(int) $st] ?? null;
                if ($info) {
                    try {
                        ($info['cb'])($st);
                    } catch (Throwable) {
                    }
                }
            }
            foreach ($wArr as $st) {
                $info = self::$writeWatchers[(int) $st] ?? null;

                if ($info) {
                    try {
                        ($info['cb'])($st);
                    } catch (Throwable) {
                    }
                }
            }
        }

        self::$running = false;
    }

    /**
     * Stop the event loop
     *
     * @return void
     */
    public static function stop(): void
    {
        self::$running = false;
    }
}
