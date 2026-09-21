<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

use Clover\Classes\Event\EventLoop;
use function count;

/** 
 * Class AsyncPromise
 *
 * A simple implementation of a Promise-like class for asynchronous operations.
 *
 * @package Clover\Classes\Event
 */
class AsyncPromise
{
    private string $state = 'pending';
    private $value;
    private $reason;
    private array $onFulfilledCallbacks = [];
    private array $onRejectedCallbacks = [];

    /** 
     * AsyncPromise constructor.
     *
     * @param callable $executor A function that takes two arguments: resolve and reject.
     *                           The executor is called immediately with these two functions as arguments.
     */
    public function __construct(callable $executor)
    {
        $resolve = function ($value = null) {
            if ($this->state !== 'pending') {
                return;
            }
            $this->state = 'fulfilled';
            $this->value = $value;
            foreach ($this->onFulfilledCallbacks as $cb) {
                EventLoop::future(fn() => $cb($value));
            }
        };

        $reject = function ($reason = null) {
            if ($this->state !== 'pending') {
                return;
            }
            $this->state = 'rejected';
            $this->reason = $reason;
            foreach ($this->onRejectedCallbacks as $cb) {
                EventLoop::future(fn() => $cb($reason));
            }
        };

        EventLoop::future(function () use ($executor, $resolve, $reject) {
            try {
                $executor($resolve, $reject);
            } catch (\Throwable $e) {
                $reject($e);
            }
        });
    }

    /** 
     * Catches any rejection from the promise.
     *
     * @param callable $onRejected A function that takes the reason for rejection as an argument.
     * @return AsyncPromise A new promise that resolves to the result of the onRejected callback or rejects if it throws an error.
     */
    public function catch(callable $onRejected): self
    {
        return $this->then(null, $onRejected);
    }

    /** 
     * Creates a promise that is resolved with the given value.
     *
     * @param mixed $v The value to resolve the promise with.
     * @return AsyncPromise A promise that is resolved with the given value.
     */
    public static function resolve($v = null): self
    {
        return new self(fn($res, $_) => $res($v));
    }

    /** 
     * Creates a promise that is rejected with the given reason.
     *
     * @param mixed $r The reason to reject the promise with.
     * @return AsyncPromise A promise that is rejected with the given reason.
     */
    public static function reject($r = null): self
    {
        return new self(fn($_, $rej) => $rej($r));
    }

    /** 
     * Waits for all promises in the given array to be fulfilled and returns a new promise that resolves to an array of their results.
     *
     * @param AsyncPromise[] $promises An array of promises to wait for.
     * @return AsyncPromise A promise that resolves to an array of results when all promises are fulfilled, or rejects if any promise is rejected.
     */
    public static function all(array $promises): self
    {
        return new self(function ($resolve, $reject) use ($promises) {
            $n = count($promises);
            if ($n === 0) {
                $resolve([]);
                return;
            }

            $left = $n;
            $out = [];
            foreach ($promises as $i => $p) {
                $p->then(
                    function ($v) use (&$left, &$out, $i, $resolve) {
                        $out[$i] = $v;
                        if (--$left === 0) {
                            ksort($out);
                            $resolve($out);
                        }
                    },
                    $reject
                );
            }
        });
    }

    /** 
     * Attaches callbacks for the resolution and/or rejection of the promise.
     *
     * @param callable|null $onFulfilled A function that takes the resolved value as an argument.
     * @param callable|null $onRejected A function that takes the reason for rejection as an argument.
     * @return AsyncPromise A new promise that resolves to the result of the onFulfilled callback or rejects if it throws an error, or resolves to the result of the onRejected callback if onFulfilled is not provided.
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
    {
        return new self(function ($resolve, $reject) use ($onFulfilled, $onRejected) {
            $handleFulfilled = function ($value) use ($onFulfilled, $resolve, $reject) {
                try {
                    $res = $onFulfilled($value);
                    if ($res instanceof self) {
                        $res->then($resolve, $reject);
                    } else {
                        $resolve($res);
                    }
                } catch (\Throwable $e) {
                    $reject($e);
                }
            };

            $handleRejected = function ($reason) use ($onRejected, $resolve, $reject) {
                if ($onRejected) {
                    try {
                        $res = $onRejected($reason);
                        if ($res instanceof self) {
                            $res->then($resolve, $reject);
                        } else {
                            $resolve($res);
                        }
                    } catch (\Throwable $e) {
                        $reject($e);
                    }
                } else {
                    $reject($reason);
                }
            };

            if ($this->state === 'fulfilled') {
                EventLoop::future(fn() => $handleFulfilled($this->value));
            } elseif ($this->state === 'rejected') {
                EventLoop::future(fn() => $handleRejected($this->reason));
            } else {
                $this->onFulfilledCallbacks[] = $handleFulfilled;
                $this->onRejectedCallbacks[] = $handleRejected;
            }
        });
    }
}
