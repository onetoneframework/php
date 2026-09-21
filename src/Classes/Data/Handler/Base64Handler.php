<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use function Clover\Classes\Security\Auth\base64url_decode;

class Base64Handler
{
    /**
     * Check if a string is a valid Base64 encoded string.
     * @param string $string
     * @return bool
     */
    public static function isBase64(string $string)
    {
        $decoded = base64_decode($string, true);
        if (!$decoded) {
            return $decoded;
        }

        return base64_encode($decoded) === $string;
    }

    /** 
     * Encode a string to Base64.
     * @param string $string
     * @return string
     */
    public static function encode(string $string)
    {
        return base64_encode($string);
    }

    /** 
     * Decode a Base64 encoded string.
     * @param string $string
     * @param bool $strict
     * @return bool|string
     */
    public static function decode(string $string, bool $strict = false): bool|string
    {
        return base64_decode($string, $strict);
    }

    /** 
     * URL-safe Base64 encode a string.
     * @param string $string
     * @param bool|null $strict
     * @return string
     */
    public static function urlDecode(string $string, null|bool $strict = false): bool|string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $string), $strict);
    }

    /** 
     * URL-safe Base64 encode a string.
     * @param string $string
     * @param bool|null $pad
     * @return string
     */
    public static function urlEncode(string $string, bool|null $pad = null): string
    {
        $data = str_replace(['+', '/'], ['-', '_'], base64_encode($string));

        if (!$pad) {
            $data = rtrim($data, '=');
        }

        return $data;
    }
}
