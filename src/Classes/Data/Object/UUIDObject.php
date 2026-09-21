<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use function sprintf;
use function strlen;
use function ord;
use function count;
use function intval;
use function chr;

/**
 * Class UUIDObject
 *
 * Provides methods for generating and converting UUIDs in various formats.
 */
class UUIDObject
{
    /**
     * Generates an MD5-based UUID with a checksum from the given input.
     *
     * @param string $input The input string to generate the UUID from.
     * 
     * @return string The generated UUID with checksum.
     */
    public static function toMD5UUID(string $input): string
    {
        $hash = md5($input);

        $uuid = sprintf('%s-%s-%s-%s-%s', substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 12, 4), substr($hash, 16, 4), substr($hash, 20, 12));

        $checksum = substr(sha1($input . $uuid), 0, 5);

        return $uuid . '.' . $checksum;
    }

    /**
     * Converts a base64-encoded UUID back to its standard UUID format.
     *
     * @param string $base64 The base64-encoded UUID.
     * 
     * @return string The standard UUID format.
     */
    public static function base64ToUuid(string $base64): string
    {
        $base64 = strtr($base64, '-_', '+/');
        $bin = base64_decode($base64 . '==');
        $hex = bin2hex($bin);
        return vsprintf('%s%s%s%s-%s%s-%s%s-%s%s-%s%s%s%s%s%s', str_split($hex, 2));
    }

    /**
     * Converts a URL-safe UUID to its base64 representation.
     *
     * @param string $uuid The URL-safe UUID.
     * 
     * @return string The base64 representation of the UUID.
     */
    public static function fromURLSafeUuidUnlimited(string $uuid): string
    {
        $parts = explode('@', $uuid);
        $uuidPart = $parts[0];

        $cleanUuid = strtolower(str_replace('-', '', $uuidPart));
        $length = strlen($cleanUuid);

        if ($length === 0) {
            return '';
        }

        $base64Chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

        $hexToDecimal = [];
        $hexChars = "0123456789abcdef";
        for ($i = 0; $i < 16; $i++) {
            $hexToDecimal[$hexChars[$i]] = $i;
        }

        $result = '';

        for ($i = 0; $i < $length; $i += 3) {
            if ($i >= $length) {
                break;
            }

            $hex1 = isset($cleanUuid[$i]) ? $hexToDecimal[$cleanUuid[$i]] : 0;
            $hex2 = isset($cleanUuid[$i + 1]) ? $hexToDecimal[$cleanUuid[$i + 1]] : 0;
            $hex3 = isset($cleanUuid[$i + 2]) ? $hexToDecimal[$cleanUuid[$i + 2]] : 0;

            $b64_1 = ($hex1 << 2) | ($hex2 >> 2);
            $result .= $base64Chars[$b64_1];

            if ($i + 1 < $length) {
                $b64_2 = (($hex2 & 0x03) << 4) | $hex3;
                $result .= $base64Chars[$b64_2];
            }
        }

        $result = rtrim($result, $base64Chars[0]);

        if (count($parts) > 1) {
            $result .= '@' . $parts[1];
        }

        return $result;
    }

    /**
     * Converts a URL-safe UUID to its standard UUID format.
     *
     * @param string $base64urlUuid The URL-safe UUID.
     * 
     * @return mixed The standard UUID format.
     */
    public static function toURLSafeUuidUnlimited(string $base64urlUuid): mixed
    {
        $parts = explode('@', $base64urlUuid);
        $input = $parts[0];
        $length = strlen($input);

        if ($length === 0) {
            return $base64urlUuid;
        }

        $base64Index = array_fill(0, 123, 64);
        $base64Chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        for ($i = 0; $i < 64; $i++) {
            $base64Index[ord($base64Chars[$i])] = $i;
        }

        $hexChars = str_split("0123456789abcdef");

        $template = array_merge(array_fill(0, 8, ""), ["-"], array_fill(0, 4, ""), ["-"], array_fill(0, 4, ""), ["-"], array_fill(0, 4, ""), ["-"], array_fill(0, 12, ""));

        $positions = [];
        foreach ($template as $idx => $char) {
            if ($char !== "-") {
                $positions[] = $idx;
            }
        }

        $result = $template;

        $isNumeric = ctype_digit($input);
        if ($isNumeric) {
            $numericChars = str_split($input);
            $input = '';
            foreach ($numericChars as $char) {
                $input .= chr(ord('0') + intval($char));
            }
        }

        $outputIdx = 0;

        for ($i = 0; $i < $length; $i += 2) {
            if ($i + 1 < $length) {
                $char1 = $base64Index[ord($input[$i])];
                $char2 = $base64Index[ord($input[$i + 1])];

                if ($outputIdx < count($positions)) {
                    $result[$positions[$outputIdx++]] = $hexChars[$char1 >> 2];
                }
                if ($outputIdx < count($positions)) {
                    $result[$positions[$outputIdx++]] = $hexChars[((3 & $char1) << 2) | ($char2 >> 4)];
                }
                if ($outputIdx < count($positions)) {
                    $result[$positions[$outputIdx++]] = $hexChars[15 & $char2];
                }
            } else {
                $char1 = $base64Index[ord($input[$i])];
                if ($outputIdx < count($positions)) {
                    $result[$positions[$outputIdx++]] = $hexChars[$char1 >> 2];
                }
                if ($outputIdx < count($positions)) {
                    $result[$positions[$outputIdx++]] = $hexChars[(3 & $char1) << 2];
                }
            }
        }

        while ($outputIdx < count($positions)) {
            $result[$positions[$outputIdx++]] = "0";
        }

        $uuid = implode('', $result);
        if (count($parts) > 1) {
            $uuid .= '@' . $parts[1];
        }

        return $uuid;
    }

    /**
     * Converts a URL-safe UUID to its standard UUID format.
     *
     * @param string $base64urlUuid The URL-safe UUID.
     * 
     * @return mixed The standard UUID format.
     */
    public static function toURLSafeUuid(string $base64urlUuid): mixed
    {
        $parts = explode('@', $base64urlUuid);
        $input = $parts[0];
        $length = strlen($input);

        if (strlen($input) !== 22) {
            return $base64urlUuid;
        }

        $base64Index = array_fill(0, 123, 64);
        $base64Chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
        for ($i = 0; $i < 64; $i++) {
            $base64Index[ord($base64Chars[$i])] = $i;
        }

        $hexChars = str_split("0123456789abcdef");

        $template = array_merge(array_fill(0, 4, ""), array_fill(0, 4, ""), ["-"], array_fill(0, 4, ""), ["-"], array_fill(0, 4, ""), ["-"], array_fill(0, 4, ""), ["-"], array_fill(0, 4, ""), array_fill(0, 4, ""), array_fill(0, 4, ""));

        $positions = [];
        foreach ($template as $idx => $char) {
            if ($char !== "-") {
                $positions[] = $idx;
            }
        }

        $result = $template;

        $result[0] = $input[0];
        $result[1] = $input[1];

        $outputIdx = 2;
        for ($i = 2; $i < $length; $i += 2) {
            $char1 = $base64Index[ord($input[$i])];
            $char2 = $base64Index[ord($input[$i + 1])];

            $result[$positions[$outputIdx++]] = $hexChars[$char1 >> 2];
            $result[$positions[$outputIdx++]] = $hexChars[((3 & $char1) << 2) | ($char2 >> 4)];
            $result[$positions[$outputIdx++]] = $hexChars[15 & $char2];
        }

        return implode('', $result);
    }

    /**
     * Converts a URL-safe UUID to its base64 representation.
     *
     * @param string $uuid The URL-safe UUID.
     * 
     * @return string The base64 representation of the UUID.
     */
    public static function toBase64Url(string $uuid): string
    {
        $uuid = str_replace('-', '', $uuid);

        $base64Chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

        $result = substr($uuid, 0, 2);

        for ($i = 2; $i < strlen($uuid); $i += 3) {
            $hex1 = hexdec($uuid[$i]);
            $hex2 = $i + 1 < strlen($uuid) ? hexdec($uuid[$i + 1]) : 0;
            $hex3 = $i + 2 < strlen($uuid) ? hexdec($uuid[$i + 2]) : 0;

            $b64_1 = ($hex1 << 2) | ($hex2 >> 2);
            $b64_2 = (($hex2 & 0x3) << 4) | $hex3;

            $result .= $base64Chars[$b64_1] . $base64Chars[$b64_2];
        }

        return $result;
    }

}