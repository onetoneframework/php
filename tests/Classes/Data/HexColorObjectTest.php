<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\HexColorObject;
use PHPUnit\Framework\TestCase;

final class HexColorObjectTest extends TestCase
{
    public function testIsValidAcceptsOnlyFullHexColorWithHash(): void
    {
        $this->assertTrue(HexColorObject::isValid('#1A2b3C'));
        $this->assertFalse(HexColorObject::isValid('#FFF'));
        $this->assertFalse(HexColorObject::isValid('1A2B3C'));
        $this->assertFalse(HexColorObject::isValid(''));
        $this->assertFalse(HexColorObject::isValid(null));
    }

    public function testToRgbConvertsHexStringToChannels(): void
    {
        $rgb = HexColorObject::toRgb('#ff8040');

        $this->assertSame(['r' => 255, 'g' => 128, 'b' => 64], $rgb);
    }
}
