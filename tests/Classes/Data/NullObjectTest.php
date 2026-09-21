<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\NullObject;
use PHPUnit\Framework\TestCase;

final class NullObjectTest extends TestCase
{
    public function testToStringCastsUnderlyingValue(): void
    {
        $empty = new NullObject(null);
        $number = new NullObject(123);
        $text = new NullObject('abc');

        $this->assertSame('', (string) $empty);
        $this->assertSame('123', (string) $number);
        $this->assertSame('abc', (string) $text);
    }
}
