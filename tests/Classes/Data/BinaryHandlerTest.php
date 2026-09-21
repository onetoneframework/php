<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\BinaryHandler;
use PHPUnit\Framework\TestCase;

final class BinaryHandlerTest extends TestCase
{
    public function testReadBigEndianAndLittleEndianIntegers(): void
    {
        $be16Data = "\x12\x34";
        $be24Data = "\x01\x02\x03";
        $be32Data = "\x12\x34\x56\x78";
        $le16Data = "\x34\x12";
        $le32Data = "\x78\x56\x34\x12";

        $this->assertSame(0x1234, BinaryHandler::readBigEndianUint16($be16Data, 0));
        $this->assertSame(0x010203, BinaryHandler::readBigEndianUint24($be24Data, 0));
        $this->assertSame(0x12345678, BinaryHandler::readBigEndianUint32($be32Data, 0));
        $this->assertSame(0x1234, BinaryHandler::readLittleEndianUint16($le16Data, 0));
        $this->assertSame(0x12345678, BinaryHandler::readLittleEndianUint32($le32Data, 0));
    }

    public function testFixedPointAndWriteHelpers(): void
    {
        $this->assertSame(1.5, BinaryHandler::readFixedPoint88("\x01\x80", 0));
        $this->assertSame(2.5, BinaryHandler::readFixedPoint1616("\x00\x02\x80\x00", 0));

        $this->assertSame("\x12\x34", BinaryHandler::writeUint16(0x1234));
        $this->assertSame("\x12\x34\x56\x78", BinaryHandler::writeUint32(0x12345678));
    }

    public function testReadUint64CombinesHighAndLowWords(): void
    {
        $data = "\x00\x00\x00\x01\x00\x00\x00\x02";

        $this->assertSame(4294967298, BinaryHandler::readUint64($data, 0));
    }
}
