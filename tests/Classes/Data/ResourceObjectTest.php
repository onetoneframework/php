<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\ResourceObject;
use PHPUnit\Framework\TestCase;

final class ResourceObjectTest extends TestCase
{
    public function testToStringReturnsStringifiedData(): void
    {
        $number = new ResourceObject(99);
        $string = new ResourceObject('resource-name');
        $boolean = new ResourceObject(true);

        $this->assertSame('99', (string) $number);
        $this->assertSame('resource-name', (string) $string);
        $this->assertSame('1', (string) $boolean);
    }
}
