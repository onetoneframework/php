<?php

declare(strict_types=1);

namespace Clover\Tests\Abstract;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Abstract\Singleton;
use PHPUnit\Framework\TestCase;

/**
 * Concrete Singleton for testing (must call instance via non-static to satisfy ReflectionHandler::getCalledClass).
 */
final class SingletonTestStub extends Singleton
{
    public function getInstance(): self
    {
        return static::instance();
    }
}

class AbstractSingletonTest extends TestCase
{
    public function testInstanceReturnsSameInstanceWhenCalledViaInstanceMethod(): void
    {
        $stub = new SingletonTestStub();
        $first = $stub->getInstance();
        $second = $stub->getInstance();
        $this->assertSame($first, $second);
    }

    public function testCloneDoesNotThrow(): void
    {
        $stub = new SingletonTestStub();
        $instance = $stub->getInstance();
        $this->assertNull($instance->__clone());
    }
}
