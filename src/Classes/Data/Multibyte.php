<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

/**
 * Class Multibyte
 *
 * A class for handling multibyte string operations.
 */
class Multibyte
{

    /**
     * Get the internal character encoding
     * 
     * @param string|null $encoding
     * 
     * @return bool|string
     */
    public static function getInternalCharacterEncoding(string|null $encoding = null): bool|string
    {
        return mb_internal_encoding($encoding);
    }

    /**
     * Convert character encoding
     * 
     * @param array|string $string
     * @param string $to_encoding
     * @param null|array|string $from_encoding
     * 
     * @return array|bool|string|null
     */
    public static function convertCharacterEncoding(array|string $string, string $to_encoding, null|array|string $from_encoding = null): array|bool|string|null
    {
        return mb_convert_encoding($string, $to_encoding, $from_encoding);
    }

    /**
     * Convert encoding to detected encoding
     * 
     * @param string $string
     * @param array|string|null $encoding
     * 
     * @return array|bool|string|null
     */
    public static function encodingToDetectEncoding(string $string, array|string|null $encoding = 'UTF-8'): array|bool|string|null
    {
        return mb_convert_encoding($string, mb_detect_encoding($string), $encoding);
    }

    /**
     * Detect character encoding
     * 
     * @param string $string
     * @param array|string|null $encodings
     * @param bool|null $strict
     * 
     * @return mixed return a encoding of detected
     */
    public static function detectCharacterEncoding(string $string, array|string|null $encodings = null, bool|null $strict = false): mixed
    {
        return mb_detect_encoding($string, $encodings, $strict);
    }

    /**
     * Check if strings are valid for the specified encoding
     * 
     * @param array|string|null $value
     * @param string|null $encoding
     * 
     * @return bool
     */
    public static function checkEncoding(array|string|null $value = null, string|null $encoding = null): bool
    {
        return mb_check_encoding($value, $encoding);
    }

    /**
     * Get string length
     * 
     * @param string $string
     * @param string|null $encoding
     * 
     * @return int
     */
    public static function length(string $string, string|null $encoding = null): int
    {
        return mb_strlen($string, $encoding);
    }

    /**
     * Get part of string
     * 
     * @param string $string
     * @param int $start
     * @param int|null $length = null
     * @param string|null $encoding = null
     * 
     * @return string
     */
    public static function substring(string $string, int $start, int|null $length = null, string|null $encoding = null): string
    {
        return mb_substr($string, $start, $length, $encoding);
    }

    /**
     * Get Unicode code point of character
     * 
     * @param string $string
     * @param string|null $encoding
     * 
     * @return mixed
     */
    public static function getUnicodeCodePointOfCharacter(string $string, string|null $encoding = null): mixed
    {
        return mb_ord($string, $encoding);
    }

    /**
     * Pad a string to a certain length with another string
     * 
     * @param string $string
     * @param int $length
     * @param string $padString
     * @param int $type
     * @param string|null $encoding
     * 
     * @return bool|string
     */
    public static function stringPadding(string $string = "", int $length = 0, string $padString = " ", int $type = STR_PAD_RIGHT, string|null $encoding = null): bool|string
    {
        if (!function_exists("mb_str_pad")) {
            return false;
        }

        return mb_str_pad($string, $length, $padString, $type, $encoding);
    }

    /**
     * Find position of first occurrence of string in a string
     * 
     * @param string $haystack
     * @param string $needle
     * @param int|null $offset
     * @param string|null $encoding
     * 
     * @return bool|int
     */
    public static function findsPositionOfTheFirstOccurrence(string $haystack, string $needle, int|null $offset = 0, string|null $encoding = null): bool|int
    {
        if (function_exists('mb_strpos')) {
            return false;
        }

        return mb_strpos($haystack, $needle, $offset, $encoding);
    }

    /**
     * Make a string uppercase
     * 
     * @param string $string
     * @param string|null $encoding
     * 
     * @return array|bool|string|null
     */
    public static function toUpperCase(string $string, string|null $encoding = null): array|bool|string|null
    {
        return mb_strtoupper($string, $encoding);
    }
}
