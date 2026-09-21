<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\FFI;

use Clover\Interface\FFI\FFIWindowsAPIInterface;
use FFI;
use FFI\{CData, CType};
use function strlen;

/**
 * Class Handler
 *
 * A helper class for PHP FFI operations.
 * This class wraps common FFI functions for easier use.
 */
class FFIWrapper
{
    public static function isLoaded(): bool
    {
        return extension_loaded('ffi');
    }
    /**
     * Creates a C data structure definition from C code.
     *
     * @param string $code The C code definition.
     * @param string|null $lib The library to load.
     * @return FFI An FFI object.
     */
    public static function ffi(string $code = "", string|null $lib = null): FFI
    {
        return FFI::cdef($code, $lib);
    }

    /**
     * Checks if a variable is a C data structure.
     *
     * @param mixed $v The variable to check.
     * @return bool True if the variable is a C data structure, false otherwise.
     */
    public static function isCData($v): bool
    {
        return $v instanceof CData;
    }

    /**
     * Checks if a variable is null or a null C data structure.
     *
     * @param mixed $v The variable to check.
     * @return bool True if the variable is null or a null C data structure, false otherwise.
     */
    public static function isNull($v): bool
    {
        return $v === null || (self::isCData($v) && FFI::isNull($v));
    }

    /**
     * Creates a new C data structure of a given type.
     *
     * @param FFI $ffi The FFI object.
     * @param CType|string $type The type of the C data structure to create.
     * @param bool $owned Whether the C data is owned by PHP.
     * @param bool $persistent Whether the C data is persistent.
     * @return CData|null The new C data structure, or null on failure.
     */
    public static function new(FFI $ffi, CType|string $type, bool $owned = true, bool $persistent = false): ?CData
    {
        return $ffi->new($type, $owned, $persistent);
    }

    /**
     * Casts a C data structure or value to a different C type.
     *
     * @param FFI $ffi The FFI object.
     * @param CType|string $type The target C type.
     * @param CData|int|float|bool|null $ptr The C data structure or value to cast.
     * @return CData|null The casted C data structure, or null on failure.
     */
    public static function cast(FFI $ffi, CType|string $type, CData|int|float|bool|null $ptr): ?CData
    {
        return $ffi->cast($type, $ptr);
    }

    /**
     * Accesses an enum value defined in the C code.
     *
     * Note: This method relies on ffi() being called without arguments first,
     * which might not always be desired. Consider defining the CDEF explicitly.
     *
     * @param string $name The name of the enum value.
     * @return mixed The value of the enum.
     */
    public static function enum(string $name): mixed
    {
        // It's often better to get the FFI object with a specific definition
        // rather than relying on a global or previous definition.
        // For simplicity based on the original code, we keep this structure,
        // but be aware of its limitations.
        return self::ffi()->{$name};
    }

    /**
     * Accesses a member of a C struct or union, or an element of a C array.
     *
     * @param CData $cdata The C data structure (struct, union, or array).
     * @param string|int $memberOrIndex The name of the member or the array index.
     * @return mixed The value of the member or element.
     */
    public static function get(CData $cdata, string|int $memberOrIndex): mixed
    {
        return $cdata->{$memberOrIndex};
    }

    /**
     * Sets a member of a C struct or union, or an element of a C array.
     *
     * @param CData $cdata The C data structure (struct, union, or array).
     * @param string|int $memberOrIndex The name of the member or the array index.
     * @param mixed $value The value to set.
     */
    public static function set(CData $cdata, string|int $memberOrIndex, mixed $value): void
    {
        $cdata->{$memberOrIndex} = $value;
    }

    /**
     * Calls a C function defined in the FFI object.
     *
     * @param FFI $ffi The FFI object.
     * @param string $functionName The name of the C function.
     * @param mixed ...$args The arguments to pass to the function.
     * @return mixed The return value of the C function.
     */
    public static function call(FFI $ffi, string $functionName, mixed ...$args): mixed
    {
        return $ffi->{$functionName}(...$args);
    }

    /**
     * Gets the size of a C type or CData structure.
     *
     * @param CType|CData|string $typeOrCData The C type name, CType object, or CData object.
     * @return int The size in bytes.
     */
    public static function sizeof(CType|CData|string $typeOrCData): int
    {
        return FFI::sizeof($typeOrCData);
    }

    /**
     * Gets the alignment of a C type or CData structure.
     *
     * @param CType|CData|string $typeOrCData The C type name, CType object, or CData object.
     * @return int The alignment in bytes.
     */
    public static function alignof(CType|CData|string $typeOrCData): int
    {
        return FFI::alignof($typeOrCData);
    }

    /**
     * Gets the C type of a CData structure.
     *
     * @param string $type The CData object.
     * @return CType The CType object.
     */
    public static function type(FFI $ffi, string $type): CType
    {
        return $ffi->type($type);
    }

    /**
     * Loads a C library and parses the C header definitions.
     * This is an alias for ffi().
     *
     * @param string $code The C code definition.
     * @param string|null $lib The library to load.
     * @return FFI An FFI object.
     */
    public static function load(string $code = "", string|null $lib = null): FFI
    {
        return self::ffi($code, $lib);
    }

    /**
     * Creates a new pointer to a C data structure of a given type.
     *
     * @param FFI $ffi The FFI object.
     * @param CType|string $type The type of the C data structure to create.
     * @param int|null $value An optional initial value to set at the pointer.
     * @return CData A pointer to the new C data structure.
     */
    public function newPtr(FFI $ffi, $type, ?int $value = null): CData
    {
        $p = $ffi->new($type, false);
        if ($value !== null) {
            $p->cdata = $value;
        }
        return FFI::addr($p);
    }

    /**
     * Creates a new C data structure of a given type and optionally initializes it with a value.
     *
     * @param FFI $ffi The FFI object.
     * @param CType|string $type The type of the C data structure to create.
     * @param mixed|null $value An optional initial value to set in the C data structure.
     * @return CData The new C data structure.
     */
    public function newValue(FFI $ffi, $type, $value = null)
    {
        $v = $ffi->new($type);
        if ($value !== null) {
            $v->cdata = $value;
        }
        return $v;
    }

    /**
     * Converts a UTF-8 string to a UTF-16 encoded C data structure.
     *
     * @param FFI $ffi The FFI object.
     * @param string $string The UTF-8 string to convert.
     * @return CData|null A C data structure containing the UTF-16 encoded string, or null on failure.
     */
    private function utf16(FFI $ffi, string $string): ?FFI\CData
    {
        $utf16le_string = iconv("UTF-8", "UTF-16BE", $string);
        $hexed_string = bin2hex($utf16le_string);
        $buffer = $ffi->new("unsigned short[" . (strlen($string) + 1) . "]");
        for ($i = 0; $i < strlen($string); $i++) {
            $buffer[$i] = hexdec(substr($hexed_string, $i * 4, 4));
        }
        return $buffer;
    }

    /**
     * Casts a C data structure or value to a different C type.
     *
     * @param FFI $ffi The FFI object.
     * @param CType|string $type The target C type.
     * @param CData $ptr The C data structure or value to cast.
     * @return CData The casted C data structure.
     */
    private function mCast(FFI $ffi, $type, FFI\CData $ptr): CData
    {
        return $ffi->cast($type, $ptr);
    }

    /**
     * Creates a new C data structure of a given type.
     *
     * @param FFI $ffi The FFI object.
     * @param CType|string $type The type of the C data structure to create.
     * @param bool $owned Whether the C data is owned by PHP.
     * @param bool $persistent Whether the C data is persistent.
     * @return CData The new C data structure.
     */
    private function mNew(FFI $ffi, $type, $owned = true, $persistent = false): CData
    {
        return $ffi->new($type, $owned, $persistent);
    }

    /**
     * Gets the C type of a CData structure.
     *
     * @param FFI $ffi The FFI object.
     * @param string $type The name of the C type.
     * @return CType|null The CType object, or null on failure.
     */
    private function mType(FFI $ffi, $type): FFI\CType|null
    {
        return $ffi->type($type);
    }

    /**
     * Gets the size of a C type or CData structure.
     *
     * @param FFI $ffi The FFI object.
     * @param string|CData $ptr The C type name or CData object to get the size of.
     * @return int The size in bytes.
     */
    private function mSizeof(FFI $ffi, $ptr): int
    {
        $type = $ffi->type($ptr);
        return FFI::sizeof($type);
    }

    /**
     * Allocates a block of memory of a given size and returns a pointer to it.
     *
     * @param FFI $ffi The FFI object.
     * @param int $size The size of the memory block to allocate in bytes.
     * @return CData A pointer to the allocated memory block.
     */
    public static function malloc(FFI $ffi, int $size): CData
    {
        $char = $ffi->new("char[$size]");
        return $ffi->cast('void*', $char);
    }

    /**
     * Converts a UTF-8 string to a wide character array (wchar_t) in C.
     *
     * @param FFIWindowsAPIInterface|FFI $ffi The FFI object.
     * @param string $string The UTF-8 string to convert.
     * @return CData A C data structure containing the wide character array.
     */
    public static function stringToWchar(FFIWindowsAPIInterface|FFI $ffi, string $string): CData
    {
        $len = mb_strlen($string, 'UTF-8');
        $wcharArray = $ffi->new("wchar_t[$len + 1]"); // +1 for null terminator
        for ($i = 0; $i < $len; $i++) {
            $wcharArray[$i] = mb_ord(mb_substr($string, $i, 1, 'UTF-8'));
        }
        $wcharArray[$len] = 0; // Null-terminate the wide character string
        return $wcharArray;
    }

    /**
     * Reads a null-terminated C string from a byte buffer at a given offset.
     *
     * @param FFI $ffi The FFI object.
     * @param CData $byteBuf The byte buffer containing the C string.
     * @param int $offset The offset in bytes where the C string starts.
     * @param int $maxLen The maximum length of the C string to read.
     * @return string|null The read C string, or null if the offset is invalid or the string is empty.
     */
    public static function ffiReadCStrAtOffset(FFI $ffi, FFI\CData $byteBuf, int $offset, int $maxLen): ?string
    {
        if ($offset <= 0 || $offset >= $maxLen) {
            return null;
        }

        $address = FFI::addr($byteBuf[0]);
        $base = $ffi->cast('char *', $address);
        $ptr = $base + $offset;

        // Limit copy to remaining bytes; FFI::string will stop at NUL earlier if present
        $slice = FFI::string($ptr, $maxLen - $offset);
        $nulPos = strpos($slice, "\0");
        if ($nulPos !== false) {
            $slice = substr($slice, 0, $nulPos);
        }
        return $slice === '' ? null : $slice;
    }

    /**
     * Allocates a C string (null-terminated) from a PHP string.
     *
     * @param FFI $ffi The FFI object.
     * @param string $s The PHP string to convert to a C string.
     * @param int|null $len Optional length of the C string buffer. If null, it will be set to the length of the string plus one for the null terminator.
     * @return CData A C data structure containing the allocated C string.
     */
    public static function allocCString(FFI $ffi, string $s, $len = null)
    {
        if ($len === null) {
            $len = strlen($s) + 1; // +1 for null terminator
        }

        $buf = $ffi->new("char[$len]", false);
        FFI::memcpy($buf, $s, strlen($s));
        return $buf;
    }

    /**
     * Reads a little-endian 16-bit unsigned integer from a byte buffer at a given offset.
     *
     * @param FFI $ffi The FFI object.
     * @param CData $buf The byte buffer containing the data.
     * @param int $off The offset in bytes where the 16-bit integer starts.
     * @return int The read 16-bit unsigned integer.
     */
    public static function leWord(FFI $ffi, mixed $buf, int $off): int
    {
        return $ffi->cast('unsigned char', $buf[$off])->cdata
            | ($ffi->cast('unsigned char', $buf[$off + 1])->cdata << 8);
    }

    /**
     * Reads a little-endian 16-bit signed integer from a byte buffer at a given offset.
     *
     * @param FFI $ffi The FFI object.
     * @param CData $buf The byte buffer containing the data.
     * @param int $off The offset in bytes where the 16-bit integer starts.
     * @return int The read 16-bit signed integer.
     */
    public static function leShort(FFI $ffi, mixed $buf, int $off): int
    {
        $v = self::leWord($ffi, $buf, $off);
        return ($v & 0x8000) ? $v - 0x10000 : $v;
    }

    /**
     * Reads a little-endian 32-bit unsigned integer from a byte buffer at a given offset.
     *
     * @param FFI $ffi The FFI object.
     * @param CData $buf The byte buffer containing the data.
     * @param int $off The offset in bytes where the 32-bit integer starts.
     * @return int The read 32-bit unsigned integer.
     */
    public static function leDword(FFI $ffi, mixed $buf, int $off): int
    {
        return $ffi->cast('unsigned char', $buf[$off])->cdata
            | ($ffi->cast('unsigned char', $buf[$off + 1])->cdata << 8)
            | ($ffi->cast('unsigned char', $buf[$off + 2])->cdata << 16)
            | ($ffi->cast('unsigned char', $buf[$off + 3])->cdata << 24);
    }

    /**
     * Convert a uint16_t[] (WCHAR array) CData to a PHP UTF-8 string.
     * Assumes the input is a null-terminated wide string.
     * @param CData $buf The CData containing the uint16_t[] (WCHAR array).
     * @param int $maxChars The maximum number of characters to read from the buffer.
     * @return string The resulting PHP UTF-8 string.
     */
    public function wideToPhp(FFI\CData $buf, int $maxChars): string
    {
        $bytes = '';
        for ($i = 0; $i < $maxChars; $i++) {
            $c = $buf[$i];
            if ($c === 0) {
                break;
            }
            $bytes .= pack('v', $c & 0xFFFF);
        }
        return mb_convert_encoding($bytes, 'UTF-8', 'UTF-16LE');
    }
    
    /**
     * Convert UTF-8 text to UTF-16LE with trailing null terminator.
     *
     * @param string $s
     *
     * @return string
     */
    public function toWideChar(string $s): string
    {
        return mb_convert_encoding($s . "\0", 'UTF-16LE', 'UTF-8');
    }

    /**
     * Read a UTF-16LE wide char buffer and return UTF-8 text.
     *
     * @param CData $buf
     * @param int $maxBytes
     *
     * @return string
     */
    public function readWideChar(CData $buf, int $maxBytes): string
    {
        $raw = FFI::string($buf, $maxBytes);
        $len = 0;
        for ($i = 0; $i < $maxBytes - 2; $i += 2) {
            if ($raw[$i] === "\x00" && $raw[$i + 1] === "\x00") {
                break;
            }
            $len = $i + 2;
        }
        return mb_convert_encoding(substr($raw, 0, $len), 'UTF-8', 'UTF-16LE');
    }
}
