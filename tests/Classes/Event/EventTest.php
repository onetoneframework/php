<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Event;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Event\AsyncPromise;
use Clover\Classes\Event\EventLoop;
use PHPUnit\Framework\TestCase;

class EventTest extends TestCase
{

    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();
        EventLoop::reset();

        $state = EventLoop::getState();
        $this->assertEquals(0, $state['timers_count']);
        $this->assertEquals(0, $state['timer_seq']);
    }

    protected function tearDown(): void
    {
        EventLoop::reset();
        parent::tearDown();
    }

    public function testTimerExecutionOrder(): void
    {
        $array = [];

        EventLoop::setTimeout(function () use (&$array) {
            $array[] = 1;
        }, 10);

        EventLoop::setTimeout(function () use (&$array) {
            $array[] = 2;
        }, 20);

        EventLoop::setTimeout(function () use (&$array) {
            $array[] = 3;
        }, 30);

        EventLoop::setTimeout(function () use (&$array) {
            $array[] = 4;
        }, 40);

        EventLoop::setTimeout(function () use (&$array) {
            $array[] = 5;
        }, 50);

        EventLoop::run();

        $this->assertSame([1, 2, 3, 4, 5], $array);
    }

    public function testTimerOrder(): void
    {
        $results = (object) ['array' => []];

        EventLoop::delay(40, fn() => $results->array[] = 4);
        EventLoop::delay(20, fn() => $results->array[] = 2);
        EventLoop::delay(30, fn() => $results->array[] = 3);
        EventLoop::delay(10, fn() => $results->array[] = 1);
        EventLoop::delay(50, fn() => $results->array[] = 5);

        EventLoop::run();

        $this->assertSame([1, 2, 3, 4, 5], $results->array);
    }

    public function testPromiseReject(): void
    {
        $error = null;

        AsyncPromise::reject('error')
            ->catch(function ($reason) use (&$error) {
                $error = $reason;
            });

        EventLoop::run();

        $this->assertSame('error', $error);
    }

}
