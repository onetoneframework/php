<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

use Clover\Classes\Reflection\Handler as ReflectionHandler;

use function count;

/**
 * A simple Promise implementation for asynchronous operations
 */
class Promise
{
    /** @var string $state The current state of the promise (pending, fulfilled, rejected) */
    private $state = 'pending';
    /** @var mixed $value The value with which the promise is fulfilled */
    private $value;
    /** @var mixed $reason The reason for promise rejection */
    private $reason;
    /** @var array $onFulfilledCallbacks Array of callbacks to be executed when the promise is fulfilled */
    private $onFulfilledCallbacks = [];
    /** @var array $onRejectedCallbacks Array of callbacks to be executed when the promise is rejected */
    private $onRejectedCallbacks = [];

    /**
     * Promise constructor.
     * 
     * @param callable $executor A function that is passed with the resolve and reject functions. The executor function is executed immediately by the Promise implementation, passing resolve and reject functions (the executor is responsible for calling these functions when the asynchronous operation succeeds or fails).
     */
    public function __construct(callable $executor)
    {
        $resolve = function (mixed $value = null) {
            if ($this->state === 'pending') {
                $this->state = 'fulfilled';
                $this->value = $value;
                foreach ($this->onFulfilledCallbacks as $callback) {
                    ReflectionHandler::callMethod($callback, $value);
                }
            }
        };

        $reject = function (mixed $reason = null) {
            if ($this->state === 'pending') {
                $this->state = 'rejected';
                $this->reason = $reason;
                foreach ($this->onRejectedCallbacks as $callback) {
                    ReflectionHandler::callMethod($callback, $reason);
                }
            }
        };

        try {
            $callable = ReflectionHandler::fromCallable($executor);
            $parameters = ReflectionHandler::getParameters($callable);
            if (count($parameters) != 2) {
                throw new \Exception('Promise parameter is must be count of two');
            }

            $executor($resolve, $reject);
        } catch (\Exception $e) {
            $reject($e);
        }
    }

    /**
     * Create a resolved promise
     * 
     * @param mixed $value The value to resolve the promise with
     * 
     * @return Promise
     */
    public static function resolve(mixed $value): self
    {
        return new Promise(fn($resolve) => $resolve($value));
    }

    /**
     * Create a rejected promise
     * 
     * @param mixed $reason The reason for rejecting the promise
     * 
     * @return Promise
     */
    public static function reject(mixed $reason): self
    {
        return new Promise(fn($resolve, $reject) => $reject($reason));
    }

    /**
     * Wait for all promises to be fulfilled
     * 
     * @param Promise[] $promises An array of promises to wait for
     * 
     * @return Promise
     */
    public static function all(array $promises): self
    {
        return new Promise(function ($resolve, $reject) use ($promises) {
            $results = [];
            $remaining = count($promises);

            if ($remaining === 0) {
                $resolve([]);
                return;
            }

            foreach ($promises as $index => $promise) {
                if (!($promise instanceof Promise)) {
                    $promise = Promise::resolve($promise);
                }

                $promise->then(
                    function ($value) use ($index, &$results, &$remaining, $resolve) {
                        $results[$index] = $value;
                        $remaining--;
                        if ($remaining === 0) {
                            ksort($results);
                            $resolve(array_values($results));
                        }
                    },
                    $reject
                );
            }
        });
    }

    /**
     * Add a rejection handler to the promise
     * 
     * @param callable $onRejected A function that is called when the promise is rejected. This function receives the reason for rejection as its argument.
     * 
     * @return Promise
     */
    public function catch(callable $onRejected): self
    {
        return $this->then(null, $onRejected);
    }

    /**
     * Add fulfillment and rejection handlers to the promise
     * 
     * @param callable|null $onFulfilled A function that is called when the promise is fulfilled. This function receives the fulfillment value as its argument.
     * @param callable|null $onRejected A function that is called when the promise is rejected. This function receives the reason for rejection as its argument.
     * 
     * @return Promise
     */
    public function then(?callable $onFulfilled = null, ?callable $onRejected = null): self
    {
        return new self(function ($resolve, $reject) use ($onFulfilled, $onRejected) {
            if ($this->state === 'fulfilled') {
                try {
                    $result = $onFulfilled($this->value);

                    if ($result instanceof Promise) {
                        $result->then($resolve, $reject);
                    } else {
                        $resolve($result);
                    }
                } catch (\Exception $e) {
                    $reject($e);
                }
            } elseif ($this->state === 'rejected') {
                if ($onRejected) {
                    try {
                        $result = $onRejected($this->reason);
                        if ($result instanceof Promise) {
                            $result->then($resolve, $reject);
                        } else {
                            $resolve($result);
                        }
                    } catch (\Exception $e) {
                        $reject($e);
                    }
                } else {
                    $reject($this->reason);
                }
            } else {
                $this->onFulfilledCallbacks[] = function ($value) use ($onFulfilled, $resolve, $reject) {
                    try {
                        $result = $onFulfilled($value);
                        if ($result instanceof Promise) {
                            $result->then($resolve, $reject);
                        } else {
                            $resolve($result);
                        }
                    } catch (\Exception $e) {
                        $reject($e);
                    }
                };

                $this->onRejectedCallbacks[] = function ($reason) use ($onRejected, $resolve, $reject) {
                    if ($onRejected) {
                        try {
                            $result = $onRejected($reason);
                            if ($result instanceof Promise) {
                                $result->then($resolve, $reject);
                            } else {
                                $resolve($result);
                            }
                        } catch (\Exception $e) {
                            $reject($e);
                        }
                    } else {
                        $reject($reason);
                    }
                };
            }
        });
    }
}
