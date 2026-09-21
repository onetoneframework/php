<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Event;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Event\EventLoop;
use Clover\Classes\Event\Promise;
use PHPUnit\Framework\TestCase;

class PromiseTest extends TestCase
{

    public function setUp(): void
    {
    }

    public function testPromiseReject(): void
    {
        $error = null;

        Promise::reject('error')
            ->catch(function ($reason) use (&$error) {
                $error = $reason;
            });

        EventLoop::run();

        $this->assertSame('error', $error);
    }

    public function testPromiseAllEmpty(): void
    {
        $result = null;

        Promise::all([])
            ->then(function ($values) use (&$result) {
                $result = $values;
            });

        EventLoop::run();

        $this->assertSame([], $result);
    }

    public function testCatchAndContinue(): void
    {
        $results = [];

        Promise::reject('error')
            ->catch(function ($e) use (&$results) {
                $results[] = 'caught: ' . $e;
                return 'recovered';
            })
            ->then(function ($value) use (&$results) {
                $results[] = 'continued: ' . $value;
            });

        EventLoop::run();

        $this->assertSame(['caught: error', 'continued: recovered'], $results);
    }

}
