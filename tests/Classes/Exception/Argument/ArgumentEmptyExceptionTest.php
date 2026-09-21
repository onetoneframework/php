<?php

declare(strict_types=1);

namespace Clover\Tests\Exception\Argument;

use Clover\Exception\Argument\ArgumentEmptyException;
use PHPUnit\Framework\TestCase;

class ArgumentEmptyExceptionTest extends TestCase
{
    public function testExceptionInstantiation(): void
    {
        $exception = new ArgumentEmptyException('Argument is empty', 100);

        $this->assertInstanceOf(ArgumentEmptyException::class, $exception);
        $this->assertSame('Argument is empty', $exception->getMessage());
        $this->assertSame(100, $exception->getCode());
    }
}
