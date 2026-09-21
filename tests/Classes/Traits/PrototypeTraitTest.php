<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Traits;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Trait\PrototypeTrait;
use PHPUnit\Framework\TestCase;

final class PrototypeTraitTestStub
{
    use PrototypeTrait;
}

class PrototypeTraitTest extends TestCase
{
    public function testDefineMethodAndCall(): void
    {
        $stub = new PrototypeTraitTestStub();
        PrototypeTraitTestStub::defineMethod('greet', function (string $name) {
            return 'Hello, ' . $name;
        });

        $this->assertSame('Hello, World', $stub->greet('World'));
    }

    public function testCallUndefinedMethodThrowsBadMethodCallException(): void
    {
        $stub = new PrototypeTraitTestStub();
        $this->expectException(\BadMethodCallException::class);
        $this->expectExceptionMessage("Method 'missing' not found.");
        $stub->missing();
    }

    public function testDefinedMethodReceivesBoundContext(): void
    {
        $stub = new PrototypeTraitTestStub();
        PrototypeTraitTestStub::defineMethod('getClass', function () {
            return static::class;
        });
        $this->assertSame(PrototypeTraitTestStub::class, $stub->getClass());
    }
}
