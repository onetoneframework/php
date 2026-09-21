<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\UUID;
use function sprintf;
use function chr;
use function ord;

/**
 * Class UUID
 *
 * Generates RFC 4122 / RFC 9562 compliant UUIDs for versions 1 through 7.
 *
 * Version overview:
 *  v1 – Gregorian time + MAC address
 *  v2 – DCE Security (Gregorian time + POSIX domain/id)
 *  v3 – Name-based, MD5 hash
 *  v4 – Randomly generated
 *  v5 – Name-based, SHA-1 hash
 *  v6 – Reordered Gregorian time (sortable, RFC 9562)
 *  v7 – Unix Epoch millisecond time + random (sortable, RFC 9562)
 */
class UUID
{
    // ── Namespace constants (RFC 4122 §4.3) ─────────────────────────────────
    public const NAMESPACE_DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';
    public const NAMESPACE_X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    // ── DCE domain constants (v2) ────────────────────────────────────────────
    public const DCE_DOMAIN_PERSON = 0;
    public const DCE_DOMAIN_GROUP = 1;
    public const DCE_DOMAIN_ORG = 2;

    // ── Gregorian epoch offset (100-ns ticks between 1582-10-15 and 1970-01-01)
    private const GREGORIAN_OFFSET = 0x01B21DD213814000;

    /**
     * UUID v1 – Gregorian time-based with MAC address.
     *
     * @param  string|null $node  48-bit MAC address (hex, e.g. "0123456789ab").
     *                            Omit to use a random multicast node.
     * @param  int|null    $clockSeq  14-bit clock sequence (0–16383).
     *
     * @return string
     */
    public static function v1(?string $node = null, ?int $clockSeq = null): string
    {
        [$timeLow, $timeMid, $timeHiAndVersion] = self::gregorianTimeParts();

        $clockSeq = $clockSeq ?? random_int(0, 0x3FFF);
        $clockSeqHiRes = (($clockSeq >> 8) & 0x3F) | 0x80;
        $clockSeqLow = $clockSeq & 0xFF;

        $node = $node !== null
            ? str_pad(substr(preg_replace('/[^0-9a-fA-F]/', '', $node), 0, 12), 12, '0')
            : self::randomMulticastNode();

        return self::format(sprintf('%08x', $timeLow), sprintf('%04x', $timeMid), sprintf('%04x', $timeHiAndVersion | 0x1000), sprintf('%02x%02x', $clockSeqHiRes, $clockSeqLow), $node);
    }

    /**
     * UUID v2 – DCE Security.
     *
     * Embeds a POSIX UID or GID in the time_low field.
     *
     * @param  int         $domain     DCE_DOMAIN_PERSON | DCE_DOMAIN_GROUP | DCE_DOMAIN_ORG
     * @param  int|null    $localId    32-bit local identifier. Defaults to
     *                                  getmyuid() (PERSON) or getmygid() (GROUP).
     * @param  string|null $node       48-bit MAC address hex string.
     *
     * @return string
     */
    public static function v2(int $domain = self::DCE_DOMAIN_PERSON, ?int $localId = null, ?string $node = null): string
    {
        if ($localId === null) {
            $localId = match ($domain) {
                self::DCE_DOMAIN_PERSON => function_exists('getmyuid') ? getmyuid() : 0,
                self::DCE_DOMAIN_GROUP => function_exists('getmygid') ? getmygid() : 0,
                default => 0,
            };
        }

        [, $timeMid, $timeHiAndVersion] = self::gregorianTimeParts();

        // clock_seq_low becomes the DCE domain
        $clockSeqHiRes = (random_int(0, 0x3F)) | 0x80;

        $node = $node !== null
            ? str_pad(substr(preg_replace('/[^0-9a-fA-F]/', '', $node), 0, 12), 12, '0')
            : self::randomMulticastNode();

        return self::format(sprintf('%08x', $localId & 0xFFFFFFFF), sprintf('%04x', $timeMid), sprintf('%04x', $timeHiAndVersion | 0x2000), sprintf('%02x%02x', $clockSeqHiRes, $domain & 0xFF), $node);
    }

    /**
     * UUID v3 – Name-based, MD5 hash.
     *
     * @param  string $namespace  A valid UUID string (use the NAMESPACE_* constants).
     * @param  string $name
     *
     * @return string
     */
    public static function v3(string $namespace, string $name): string
    {
        return self::nameBasedUuid($namespace, $name, 'md5', 0x3000);
    }

    /**
     * UUID v4 – Randomly generated.
     *
     * @return string
     * @throws \Random\RandomException
     */
    public static function v4(): string
    {
        $bytes = random_bytes(16);

        // Set version (4) and variant bits (RFC 4122)
        $bytes[6] = chr((ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3F) | 0x80);

        $hex = bin2hex($bytes);

        return self::format(substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }

    /**
     * UUID v5 – Name-based, SHA-1 hash.
     *
     * @param  string $namespace  A valid UUID string (use the NAMESPACE_* constants).
     * @param  string $name
     *
     * @return string
     */
    public static function v5(string $namespace, string $name): string
    {
        return self::nameBasedUuid($namespace, $name, 'sha1', 0x5000);
    }

    /**
     * UUID v6 – Reordered Gregorian time (RFC 9562).
     *
     * Fields are rearranged so that lexicographic sort equals chronological sort.
     *
     * @param  string|null $node 48-bit MAC address (hex, e.g. "0123456789ab"). Omit to use a random multicast node.
     * @param  int|null    $clockSeq 14-bit clock sequence (0–16383).
     *
     * @return string
     */
    public static function v6(?string $node = null, ?int $clockSeq = null): string
    {
        // 60-bit timestamp in 100-ns intervals since Gregorian epoch
        $ticks = self::gregorianTicks();

        // v6 layout: [time_high(48)] [version(4)] [time_low(12)] [variant(2)] [clock_seq(14)] [node(48)]
        $timeHigh = ($ticks >> 12) & 0xFFFFFFFFFFFF; // top 48 bits
        $timeLow = $ticks & 0xFFF;                  // bottom 12 bits

        $clockSeq = $clockSeq ?? random_int(0, 0x3FFF);
        $clockSeqHiRes = (($clockSeq >> 8) & 0x3F) | 0x80;
        $clockSeqLow = $clockSeq & 0xFF;

        $node = $node !== null
            ? str_pad(substr(preg_replace('/[^0-9a-fA-F]/', '', $node), 0, 12), 12, '0')
            : self::randomMulticastNode();

        // Pack into UUID fields
        $f1 = ($timeHigh >> 16) & 0xFFFFFFFF;
        $f2 = ($timeHigh >> 0) & 0xFFFF;
        $f3 = 0x6000 | ($timeLow & 0x0FFF);

        return self::format(sprintf('%08x', $f1), sprintf('%04x', $f2), sprintf('%04x', $f3), sprintf('%02x%02x', $clockSeqHiRes, $clockSeqLow), $node);
    }

    /**
     * UUID v7 – Unix Epoch millisecond timestamp (RFC 9562).
     *
     * The most practical modern UUID: globally unique, k-sortable, DB-friendly.
     *
     * Layout (128 bits):
     *   [unix_ts_ms: 48][ver: 4][rand_a: 12][var: 2][rand_b: 62]
     *
     * @param  int|null $unixMs  Unix timestamp in milliseconds. Defaults to now.
     *
     * @return string
     * @throws \Random\RandomException
     */
    public static function v7(?int $unixMs = null): string
    {
        $ms = $unixMs ?? (int) (microtime(true) * 1000);

        // 48-bit millisecond timestamp
        $tsMsHex = sprintf('%012x', $ms & 0xFFFFFFFFFFFF);

        // 76 random bits: 12 (rand_a) + 64 (rand_b) — we'll carve variant bits from rand_b
        $rand = random_bytes(10); // 80 bits; we'll use 76

        // rand_a: 12 bits (top 12 bits of $rand)
        $randA = ((ord($rand[0]) << 4) | (ord($rand[1]) >> 4)) & 0x0FFF;

        // rand_b: 64 bits — but variant consumes the top 2, leaving 62 random bits
        $randBBytes = substr($rand, 2, 8);
        $randBBytes[0] = chr((ord($randBBytes[0]) & 0x3F) | 0x80); // variant 10xx

        $randBHex = bin2hex($randBBytes);

        return self::format(substr($tsMsHex, 0, 8), substr($tsMsHex, 8, 4), sprintf('%04x', 0x7000 | $randA), substr($randBHex, 0, 4), substr($randBHex, 4, 12));
    }

    /**
     * Validate a UUID string (any version, any variant).
     *
     * @param  string $uuid
     *
     * @return bool
     */
    public static function isValid(string $uuid): bool
    {
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-7][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Return the version number (1–7) of a given UUID, or null if unrecognised.
     *
     * @param  string $uuid
     *
     * @return int|null
     */
    public static function version(string $uuid): ?int
    {
        if (!self::isValid($uuid)) {
            return null;
        }
        return (int) $uuid[14];
    }

    /**
     * Convert a UUID string to its 16-byte binary representation.
     *
     * @param  string $uuid
     *
     * @return string
     */
    public static function toBinary(string $uuid): string
    {
        return hex2bin(str_replace('-', '', $uuid));
    }

    /**
     * Convert a 16-byte binary string back to a UUID.
     *
     * @param  string $bin
     *
     * @return string
     */
    public static function fromBinary(string $bin): string
    {
        $hex = bin2hex($bin);
        return self::format(substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20, 12));
    }

    /**
     * Extract the Unix timestamp (in seconds, float) from a v1, v6, or v7 UUID.
     *
     * @param  string $uuid
     *
     * @return float|null  null if the UUID version does not carry a timestamp.
     */
    public static function extractTimestamp(string $uuid): ?float
    {
        $version = self::version($uuid);
        $hex = str_replace('-', '', $uuid);

        return match ($version) {
            1 => self::timestampFromV1($hex),
            6 => self::timestampFromV6($hex),
            7 => self::timestampFromV7($hex),
            default => null,
        };
    }

    /**
     * Current Gregorian epoch tick count (100-ns intervals since 1582-10-15).
     * 
     * @return int The current time in 100-ns ticks since the Gregorian epoch
     */
    private static function gregorianTicks(): int
    {
        // microtime(true) gives Unix seconds with microsecond precision.
        // Convert to 100-ns ticks and add the Gregorian offset.
        return (int) (microtime(true) * 1e7) + self::GREGORIAN_OFFSET;
    }

    /**
     * Return [time_low, time_mid, time_hi] as separate 32/16/16-bit integers
     * in the v1 field layout.
     *
     * @return array{int, int, int}
     */
    private static function gregorianTimeParts(): array
    {
        $ticks = self::gregorianTicks();

        $timeLow = $ticks & 0xFFFFFFFF;
        $timeMid = ($ticks >> 32) & 0xFFFF;
        $timeHiAndVersion = ($ticks >> 48) & 0x0FFF;

        return [$timeLow, $timeMid, $timeHiAndVersion];
    }

    /**
     * Shared logic for v3 (MD5) and v5 (SHA-1) name-based UUIDs.
     * 
     * @param string $namespace A valid UUID string to use as the namespace
     * @param string $name The name from which to generate the UUID
     * @param string $algo The hashing algorithm to use ('md5' or 'sha1')
     * @param int $versionMask The version bits to set in the UUID (e.g. 0x3000 for v3, 0x5000 for v5)
     * @return string The generated UUID string
     */
    private static function nameBasedUuid(string $namespace, string $name, string $algo, int $versionMask): string
    {
        $nsBin = self::toBinary($namespace);
        $hash = hash($algo, $nsBin . $name);

        // Use only the first 32 hex characters (128 bits)
        $hash = substr($hash, 0, 32);

        // Apply version and variant bits
        $hash[12] = dechex(hexdec($hash[12]) & 0x0F | ($versionMask >> 12));
        $hash[16] = dechex(hexdec($hash[16]) & 0x3F | 0x80);

        return self::format(substr($hash, 0, 8), substr($hash, 8, 4), substr($hash, 12, 4), substr($hash, 16, 4), substr($hash, 20, 12));
    }

    /**
     * Generate a random multicast node identifier (RFC 4122 §4.5).
     * The multicast bit (LSB of first octet) is set to 1 to indicate
     * that this node was not taken from a real network interface.
     * 
     * @return string A 12-character hexadecimal string representing the node ID
     */
    private static function randomMulticastNode(): string
    {
        $bytes = random_bytes(6);
        $bytes[0] = chr(ord($bytes[0]) | 0x01); // set multicast bit
        return bin2hex($bytes);
    }

    /**
     * Assemble five hex groups into a hyphenated UUID string.
     * 
     * @param string $f1 First group (8 hex chars)
     * @param string $f2 Second group (4 hex chars)
     * @param string $f3 Third group (4 hex chars)
     * @param string $f4 Fourth group (4 hex chars)
     * @param string $f5 Fifth group (12 hex chars)
     * @return string Formatted UUID string
     */
    private static function format(string $f1, string $f2, string $f3, string $f4, string $f5): string
    {
        return strtolower("{$f1}-{$f2}-{$f3}-{$f4}-{$f5}");
    }

    /**
     * Extract the Unix timestamp from a v1 UUID's time fields.
     *
     * @param string $hex The 32-character hex string of the UUID (no hyphens)
     * @return float Unix timestamp in seconds (with microsecond precision)
     */
    private static function timestampFromV1(string $hex): float
    {
        // v1: time_low(32) time_mid(16) time_hi_and_version(16)
        $timeLow = hexdec(substr($hex, 0, 8));
        $timeMid = hexdec(substr($hex, 8, 4));
        $timeHi = hexdec(substr($hex, 12, 4)) & 0x0FFF;

        $ticks = ($timeHi << 48) | ($timeMid << 32) | $timeLow;
        return ($ticks - self::GREGORIAN_OFFSET) / 1e7;
    }

    /**
     * Extract the Unix timestamp from a v6 UUID's time fields.
     *
     * @param string $hex The 32-character hex string of the UUID (no hyphens)
     * @return float Unix timestamp in seconds (with microsecond precision)
     */
    private static function timestampFromV6(string $hex): float
    {
        // v6: timeHigh(48) ver(4) timeLow(12)
        $high = hexdec(substr($hex, 0, 12));          // 48 bits
        $low = hexdec(substr($hex, 13, 3)) & 0x0FFF; // 12 bits (skip version nibble)

        $ticks = ($high << 12) | $low;
        return ($ticks - self::GREGORIAN_OFFSET) / 1e7;
    }

    /**
     * Extract the Unix timestamp from a v7 UUID's time fields.
     *
     * @param string $hex The 32-character hex string of the UUID (no hyphens)
     * @return float Unix timestamp in seconds (with millisecond precision)
     */
    private static function timestampFromV7(string $hex): float
    {
        $ms = hexdec(substr($hex, 0, 12)); // 48-bit millisecond timestamp
        return $ms / 1000.0;
    }
}
