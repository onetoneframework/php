<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Enumeration\Bitwise\Bit;
use function ord;

#[\AllowDynamicProperties]
class BinaryHandler
{

    /**
     * Read a big-endian unsigned 32-bit integer from the data string.
     *
     * @param string $data Data string.
     * @param int $offset Offset to read from.
     * @return int Unsigned 32-bit integer value.
     */
    public static function readBigEndianUint32(string $data, int $offset): int
    {
        return (ord($data[$offset]) << 24)
            | (ord($data[$offset + 1]) << 16)
            | (ord($data[$offset + 2]) << 8)
            | ord($data[$offset + 3]);
    }

    /**
     * Read a big-endian unsigned 24-bit integer from the data string.
     *
     * @param string $data Data string.
     * @param int $offset Offset to read from.
     * @return int Unsigned 24-bit integer value.
     */
    public static function readBigEndianUint24(string $data, int $offset): int
    {
        return (ord($data[$offset]) << 16)
            | (ord($data[$offset + 1]) << 8)
            | ord($data[$offset + 2]);
    }

    /**
     * Read a big-endian unsigned 16-bit integer from the data string.
     *
     * @param string $data Data string.
     * @param int $offset Offset to read from.
     * @return int Unsigned 16-bit integer value.
     */
    public static function readBigEndianUint16(string $data, int $offset): int
    {
        return (ord($data[$offset]) << 8) | ord($data[$offset + 1]);
    }

    /**
     * Read a little-endian unsigned 32-bit integer from the data string.
     *
     * @param string $data Data string.
     * @param int $offset Offset to read from.
     * @return int Unsigned 32-bit integer value.
     */
    public static function readLittleEndianUint32(string $data, int $offset): int
    {
        return ord($data[$offset])
            | (ord($data[$offset + 1]) << 8)
            | (ord($data[$offset + 2]) << 16)
            | (ord($data[$offset + 3]) << 24);
    }

    /**
     * Reads an 8.8 fixed-point value (e.g. used for track volume).
     * 
     * @param string $data
     * @param int $offset
     * @return float
     */
    public static function readFixedPoint88(string $data, int $offset): float
    {
        return ord($data[$offset]) + (ord($data[$offset + 1]) / 256.0);
    }

    /**
     * Pack a 32-bit unsigned integer as a 4-byte big-endian binary string.
     *
     * @param int $value
     * @return string
     */
    public static function writeUint32(int $value): string
    {
        return pack('N', $value);
    }

    /**
     * Pack a 24-bit unsigned integer as a 3-byte big-endian binary string.
     *
     * @param int $value
     * @return string
     */
    public static function writeUint24(int $value): string
    {
        return pack('N', $value)[1]; // Get the last 3 bytes
    }
    
    public static function writeLittleEndianUint32(int $value): string
    {
        return pack('V', $value);
    }

    /**
     * Pack a 16-bit unsigned integer as a 2-byte big-endian binary string.
     *
     * @param int $value
     * @return string
     */
    public static function writeLittleEndianUint16(int $value): string
    {
        return pack('v', $value);
    }

    /**
     * Pack a 16-bit unsigned integer as a 2-byte big-endian binary string.
     *
     * @param int $value
     * @return string
     */
    public static function writeUint16(int $value): string
    {
        return pack('n', $value);
    }

    public static function writeLittleEndianUint24(int $value): string
    {
        return pack('V', $value)[0] . pack('V', $value)[1] . pack('V', $value)[2];
    }

    /**
     * Reads a 16.16 fixed-point value (e.g. used for display matrix dimensions and playback rate).
     * 
     * @param string $data
     * @param int $offset
     * @return float
     */
    public static function readFixedPoint1616(string $data, int $offset): float
    {
        $intPart = self::readUint16($data, $offset);
        $fracPart = self::readUint16($data, $offset + 2);

        return $intPart + ($fracPart / 65536.0);
    }

    /**
     * Reads a big-endian unsigned 16-bit integer from a binary string at the given offset.
     * 
     * @param string $data
     * @param int $offset
     * @return int
     */
    public static function readUint16(string $data, int $offset): int
    {
        return (ord($data[$offset]) << 8) | ord($data[$offset + 1]);
    }

    /**
     * Reads a big-endian unsigned 64-bit integer; uses float arithmetic to avoid overflow on 32-bit PHP.
     * 
     * @param string $data
     * @param int $offset
     * @return int
     */
    public static function readUint64(string $data, int $offset): int
    {
        $high = self::readUint32($data, $offset);
        $low = self::readUint32($data, $offset + 4);

        return ($high * Bit::UINT32_MAX_PLUS_ONE) + $low;
    }

    /**
     * Reads a big-endian unsigned 32-bit integer from a binary string at the given offset.
     * 
     * @param string $data
     * @param int $offset
     * @return int
     */
    public static function readUint32(string $data, int $offset): int
    {
        return (ord($data[$offset]) << 24)
            | (ord($data[$offset + 1]) << 16)
            | (ord($data[$offset + 2]) << 8)
            | ord($data[$offset + 3]);
    }

    /**
     * Read a little-endian unsigned 16-bit integer from the data string.
     *
     * @param string $data Data string.
     * @param int $offset Offset to read from.
     * @return int Unsigned 16-bit integer value.
     */
    public static function readLittleEndianUint16(string $data, int $offset): int
    {
        return ord($data[$offset]) | (ord($data[$offset + 1]) << 8);
    }

    /**
     * Read a little-endian unsigned 24-bit integer from the data string.
     *
     * @param string $data Data string.
     * @param int $offset Offset to read from.
     * @return int Unsigned 24-bit integer value.
     */
    public static function readLittleEndianUint24(string $data, int $offset): int
    {
        return ord($data[$offset]) | (ord($data[$offset + 1]) << 8) | (ord($data[$offset + 2]) << 16);
    }

    /**
     * Read a little-endian unsigned 64-bit integer from the data string.
     *
     * @param string $data Data string.
     * @param int $offset Offset to read from.
     * @return int Unsigned 64-bit integer value.
     */
    public static function readLittleEndianUint64(string $data, int $offset): int
    {
        $low = self::readLittleEndianUint32($data, $offset);
        $high = self::readLittleEndianUint32($data, $offset + 4);

        return ($high * Bit::UINT32_MAX_PLUS_ONE) + $low;
    }
}
