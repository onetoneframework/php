<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\UUID;

/**
 * Class ObfuscatedUUID
 * 
 * Provides methods to encode and decode UUIDs into an obfuscated format.
 */
class ObfuscatedUUID
{
    /** Default salt for obfuscation, can be set via setSalt method */
    private static string $salt = 'my-secret-salt';

    /**
     * Set the salt for obfuscation
     * 
     * @param string $salt The salt value to use for encoding and decoding UUIDs
     * 
     * @return void
     */
    public static function setSalt(string $salt): void
    {
        self::$salt = $salt;
    }

    /**
     * Encode a UUID string into an obfuscated format
     * 
     * @param string $uuid The UUID string to encode (e.g., "123e4567-e89b-12d3-a456-426614174000")
     * 
     * @return string
     */
    public static function encode(string $uuid): string
    {
        $uuid = str_replace('-', '', $uuid);
        $bin = hex2bin($uuid);

        $salt = substr(hash('sha256', self::$salt), 0, 16);
        $xor = $bin ^ $salt;

        $encoded = rtrim(strtr(base64_encode((string) $xor), '+/', '-_'), '=');

        return self::obfuscate($encoded);
    }

    /**
     * Decode an obfuscated UUID string back to its original format
     * 
     * @param string $obfuscated The obfuscated UUID string to decode
     * 
     * @return string|bool
     */
    public static function decode(string $obfuscated): string|bool
    {
        $clean = self::deobfuscate($obfuscated);

        $bin = base64_decode(strtr($clean, '-_', '+/'));
        if ($bin === false) {
            return false;
        }

        $salt = substr(hash('sha256', self::$salt), 0, 16);
        $original = $bin ^ $salt;

        $uuid = bin2hex($original);
        return substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20);
    }

    /**
     * Helper methods for obfuscation
     * 
     * @param string $base The base string to obfuscate
     * 
     * @return string
     */
    private static function obfuscate(string $base): string
    {
        $rev = strrev($base);
        return substr($rev, 0, 5) . 'x9' . substr($rev, 5, 5) . 'kZ' . substr($rev, 10);
    }

    /**
     * Helper methods for deobfuscation
     * 
     * @param string $str The obfuscated string to deobfuscate
     * 
     * @return string
     */
    private static function deobfuscate(string $str): string
    {
        $removed = substr($str, 0, 5) . substr($str, 7, 5) . substr($str, 14);
        return strrev($removed);
    }

}
