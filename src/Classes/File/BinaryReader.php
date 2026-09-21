<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\File;

use Clover\Enumeration\UnpackArguments;
use InvalidArgumentException;
use Exception;

use function is_resource;
use function strlen;

/**
 * Class BinaryReader
 *
 * A class for reading binary data from a stream.
 */
class BinaryReader
{
    /**
     * @var resource $stream The stream resource.
     */
    private $stream;

    /**
     * Constructor
     *
     * @param resource $stream The stream resource to read from.
     * @throws InvalidArgumentException if the provided stream is not a valid resource.
     */
    public function __construct(mixed $stream)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException("Invalid stream resource provided.");
        }

        $this->stream = $stream;
    }

    /**
     * Seeks to a specific position in the stream.
     *
     * @param int $pos The position to seek to.
     * @param int $whence The reference point for the position. Default is SEEK_SET.
     * @return int The new position in the stream.
     */
    public function seek(int $pos, int $whence = SEEK_SET): int
    {
        return fseek($this->stream, $pos, $whence);
    }

    /**
     * Gets the current position in the stream.
     *
     * @return int The current position in the stream.
     */
    public function tell(): int
    {
        return ftell($this->stream);
    }

    /**
     * Reads a single byte from the stream.
     *
     * @return int|null The byte read as an integer, or null if end of stream is reached.
     */
    public function readByte(): ?int
    {
        $byte = fread($this->stream, 1);
        if ($byte === false || strlen($byte) < 1) {
            return null;
        }

        $unpacked = unpack(UnpackArguments::UNSIGNED_CHARACTER, $byte);
        return $unpacked[1];
    }

    /**
     * Reads a specified number of bytes from the stream.
     *
     * @param int $count The number of bytes to read.
     * @return string|false The bytes read as a string, or false on failure.
     */
    public function readBytes(int $count): string|false
    {
        return fread($this->stream, $count);
    }

    /**
     * Reads a signed 16-bit integer from the stream.
     *
     * @return int|null The integer read, or null if end of stream is reached.
     */
    public function readInt16(): ?int
    {
        $bytes = fread($this->stream, 2);
        if ($bytes === false || strlen($bytes) < 2) {
            return null;
        }

        $unpacked = unpack('s<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads an unsigned 16-bit integer from the stream.
     *
     * @return int|null The integer read, or null if end of stream is reached.
     */
    public function readUint16(): ?int
    {
        $bytes = fread($this->stream, 2);
        if ($bytes === false || strlen($bytes) < 2) {
            return null;
        }

        $unpacked = unpack('S<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads a signed 32-bit integer from the stream.
     *
     * @return int|null The integer read, or null if end of stream is reached.
     */
    public function readInt32(): ?int
    {
        $bytes = fread($this->stream, 4);
        if ($bytes === false || strlen($bytes) < 4) {
            return null;
        }

        $unpacked = unpack('l<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads an unsigned 32-bit integer from the stream.
     *
     * @return int|null The integer read, or null if end of stream is reached.
     */
    public function readUint32(): ?int
    {
        $bytes = fread($this->stream, 4);
        if ($bytes === false || strlen($bytes) < 4) {
            return null;
        }

        $unpacked = unpack('L<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads a signed 64-bit integer from the stream.
     *
     * @return int|null The integer read, or null if end of stream is reached.
     */
    public function readInt64(): ?int
    {
        $bytes = fread($this->stream, 8);
        if ($bytes === false || strlen($bytes) < 8) {
            return null;
        }

        $unpacked = unpack('q<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads an unsigned 64-bit integer from the stream.
     *
     * @return int|null The integer read, or null if end of stream is reached.
     */
    public function readUint64(): ?int
    {
        $bytes = fread($this->stream, 8);
        if ($bytes === false || strlen($bytes) < 8) {
            return null;
        }

        $unpacked = unpack('Q<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads a boolean value from the stream.
     *
     * @return bool|null The boolean value read, or null if end of stream is reached.
     */
    public function readBoolean(): ?bool
    {
        $byte = fread($this->stream, 1);
        if ($byte === false || strlen($byte) < 1) {
            return null;
        }

        return $byte !== "\0";
    }

    /**
     * Reads a single character from the stream.
     *
     * @return string|false The character read as a string, or false on failure.
     */
    public function readChar(): string|false
    {
        return fread($this->stream, 1);
    }

    /**
     * Reads four unsigned 32-bit integers from the stream.
     *
     * @return array|null An array of four unsigned integers, or null if end of stream is reached.
     */
    public function read4Uint32(): ?array
    {
        $bytes = fread($this->stream, 16);
        if ($bytes === false || strlen($bytes) < 16) {
            return null;
        }

        $unpacked = unpack('L<4', $bytes);
        return $unpacked;
    }

    /**
     * Reads data from the stream according to a specified format.
     *
     * @param string $fmt The format string for unpacking.
     * @return array|null An array of unpacked values, or null if end of stream is reached.
     */
    public function readFormat(string $fmt): array|null
    {
        $size = $this->calculateSize($fmt);
        $bytes = fread($this->stream, $size);
        if ($bytes === false) {
            return null;
        }

        $result = unpack($fmt, $bytes);
        return array_values($result);
    }

    /**
     * Calculates the size in bytes required for a given format string.
     *
     * @param string $fmt The format string.
     * 
     * @return int The size in bytes.
     */
    private function calculateSize(string $fmt): int
    {
        $sizes = [
            'c' => 1,
            'C' => 1,
            'd' => 8,
            'e' => 8,
            'E' => 8,
            'f' => 4,
            'h' => 4,
            'H' => 4,
            'J' => 8,
            'l' => 4,
            'L' => 4,
            'n' => 2,
            'N' => 4,
            'P' => 8,
            'q' => 8,
            'Q' => 8,
            's' => 2,
            'S' => 2,
            'v' => 2,
            'V' => 4,
        ];

        $size = 0;
        for ($i = 0; $i < strlen($fmt); $i++) {
            $char = $fmt[$i];
            if (isset($sizes[$char])) {
                $size += $sizes[$char];
            }
        }

        return $size;
    }

    /**
     * Closes the stream resource.
     *
     * @return bool True on success, false on failure.
     */
    public function close(): bool
    {
        if (is_resource($this->stream)) {
            return fclose($this->stream);
        }

        return false;
    }
    
    /**
     * Destructor to ensure the stream is closed when the object is destroyed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Gets the current stream resource.
     *
     * @return mixed The stream resource.
     */
    public function getStream(): mixed
    {
        return $this->stream;
    }

    /**
     * Sets the stream resource.
     *
     * @param mixed $stream The stream resource to set.
     * @throws InvalidArgumentException if the provided stream is not a valid resource.
     */
    public function setStream(mixed $stream): void
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException("Invalid stream resource provided.");
        }

        $this->stream = $stream;
    }

    /**
     * Reads a double-precision floating-point number from the stream.
     *
     * @return float|null The double read, or null if end of stream is reached.
     */
    public function readDouble(): ?float
    {
        $bytes = fread($this->stream, 8);
        if ($bytes === false || strlen($bytes) < 8) {
            return null;
        }

        $unpacked = unpack('d<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads a single-precision floating-point number from the stream.
     *
     * @return float|null The float read, or null if end of stream is reached.
     */
    public function readFloat(): ?float
    {
        $bytes = fread($this->stream, 4);
        if ($bytes === false || strlen($bytes) < 4) {
            return null;
        }

        $unpacked = unpack('f<', $bytes);
        return $unpacked[1];
    }

    /**
     * Reads a string of specified length from the stream.
     *
     * @param int $length The length of the string to read.
     * @return string|null The string read, or null if end of stream is reached.
     */
    public function readString(int $length): ?string
    {
        $bytes = fread($this->stream, $length);
        if ($bytes === false || strlen($bytes) < $length) {
            return null;
        }

        return $bytes;
    }

    /**
     * Reads a line from the stream.
     *
     * @return string|false The line read as a string, or false on failure.
     */
    public function readLine(): string|false
    {
        return fgets($this->stream);
    }

    /**
     * Checks if the end of the stream has been reached.
     *
     * @return bool True if end of stream is reached, false otherwise.
     */
    public function eof(): bool
    {
        return feof($this->stream);
    }

    public function __wakeup()
    {
        throw new Exception("BinaryReader instances cannot be unserialized.");
    }

    public function __clone()
    {
        throw new Exception("BinaryReader instances cannot be cloned.");
    }

    public function __serialize(): array
    {
        throw new Exception("BinaryReader instances cannot be serialized.");
    }

    public function __unserialize(array $data): void
    {
        throw new Exception("BinaryReader instances cannot be unserialized.");
    }
}
