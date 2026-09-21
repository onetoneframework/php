<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\StringHandler;
use PHPUnit\Framework\TestCase;
use function strlen;

final class StringHandlerTest extends TestCase
{
    public function testContainsStartsWithEndsWithAndIndexOf(): void
    {
        $text = 'framework-core';

        $this->assertTrue(StringHandler::contains($text, 'work'));
        $this->assertTrue(StringHandler::startsWith($text, 'frame'));
        $this->assertTrue(StringHandler::endsWith($text, 'core'));
        $this->assertSame(5, StringHandler::indexOf($text, 'work'));
    }

    public function testSimpleTransformHelpers(): void
    {
        $this->assertSame('HelloWorld', StringHandler::camelize('hello_world'));
        $this->assertSame('a_b_c', StringHandler::toUnderScore('a b c'));
        $this->assertSame('abcdef', StringHandler::removeNullBytes("ab\0cd\x00ef"));
        $this->assertSame('abc', StringHandler::substring('zabcx', 1, 3));
    }

    public function testHexBinaryAndValidationHelpers(): void
    {
        $binary = StringHandler::hexToBinary('414243');
        $hex = StringHandler::binaryToHex($binary);

        $this->assertSame('ABC', $binary);
        $this->assertSame('414243', $hex);
        $this->assertTrue(StringHandler::isValidPhpVariableName('_name1'));
        $this->assertFalse(StringHandler::isValidPhpVariableName('1name'));
    }

    public function testRandomHelpersReturnExpectedLengths(): void
    {
        $random = StringHandler::getRandomString(12);
        $bytes = StringHandler::getRandomBytes(16);
        $hex = StringHandler::getRandomHex(16);

        $this->assertSame(12, strlen($random));
        $this->assertIsString($bytes);
        $this->assertGreaterThanOrEqual(1, strlen($bytes));
        $this->assertSame(32, strlen($hex));
    }
}
