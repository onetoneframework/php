<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\ByteArray;
use PHPUnit\Framework\TestCase;

class ByteArrayTest extends TestCase
{
    public function testBytesToInt(): void
    {
        $bytes = "\x00\x01";
        $result = ByteArray::bytesToInt($bytes);
        $this->assertEquals(1, $result);

        $bytes = "\x01\x00";
        $result = ByteArray::bytesToInt($bytes);
        $this->assertEquals(256, $result);
    }

    public function testBytesToIntSingleByte(): void
    {
        $bytes = "\x7F";
        $result = ByteArray::bytesToInt($bytes);
        $this->assertEquals(127, $result);
    }

    public function testBytesToIntEmptyString(): void
    {
        $result = ByteArray::bytesToInt('');
        $this->assertEquals(0, $result);
    }

    public function testSyncsafeToInt(): void
    {
        $bytes = pack('C4', 0, 0, 0, 255);
        $result = ByteArray::syncsafeToInt($bytes);
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }
}
