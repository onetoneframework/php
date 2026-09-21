<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\CharacterObject;
use PHPUnit\Framework\TestCase;

final class CharacterObjectTest extends TestCase
{
    public function testIsHanjaMatchesExpectedBytePattern(): void
    {
        $hanjaLike = chr(0xE4) . chr(0x31) . chr(0x80);
        $invalidLength = 'ab';
        $invalidLeadingByte = chr(0xC0) . chr(0x31) . chr(0x80);

        $this->assertTrue(CharacterObject::isHanja($hanjaLike));
        $this->assertFalse(CharacterObject::isHanja($invalidLength));
        $this->assertFalse(CharacterObject::isHanja($invalidLeadingByte));
    }

    public function testIsSpecialCharacterChecksExtendedAsciiSet(): void
    {
        $special = chr(0xD4);
        $normal = 'A';
        $invalidLength = 'AB';

        $this->assertTrue(CharacterObject::isSpecialCharacter($special));
        $this->assertFalse(CharacterObject::isSpecialCharacter($normal));
        $this->assertFalse(CharacterObject::isSpecialCharacter($invalidLength));
    }
}
