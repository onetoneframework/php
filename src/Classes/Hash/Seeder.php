<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Hash;

use function strlen;
use function ord;
use Clover\Enumeration\Bitwise\Bit;
use Clover\Enumeration\Bitwise\MaskingBit;
use Closure;

/**
 * Pseudo-Random Number Generator (PRNG) Collection
 * 
 * Provides various deterministic random number generators for seeding purposes.
 * Each method returns a closure that generates the next random value when called.
 * 
 * Categories:
 * - Linear Congruential Generators (LCG): lcg32, lehmer64
 * - Xorshift Family: xorShift32, xorShift64, xorshift128plus, xoshiro128ss, xoshiro256pp, xoshiro256ss
 * - Xoroshiro Family: xoroshiro64ss, xoroshiro128p, xoroshiro128ss
 * - Mersenne Twister: mt19937, sfmt19937
 * - WELL Family: well512, well512a, well1024a, well19937a
 * - PCG Family: pcg32, pcg32_2, pcg64
 * - Cryptographically Secure: aesCtrRng, hmac_drbg_sha256, chacha20, chacha20_rng, urandom
 * - Other: mulberry32, splitmix32, splitmix64, kiss32, kiss64, tinymt32, mwc256, jsf32, msws, blumBlumShub, lfsr113, threefry2x64
 */
class Seeder
{
	private const UNSIGNED_16_MASK = 0xFFFF;
	private const UNSIGNED_32_MASK = 0xFFFFFFFF;
	private const UNSIGNED_32_BITS = 32;
	private const PCG32_MULTIPLIER = 6364136223846793005;

	/**
	 * Multiplies two unsigned 32-bit values and returns the low 32 bits.
	 */
	private static function multiplyUnsigned32(int $left, int $right): int
	{
		$leftLow = $left & self::UNSIGNED_16_MASK;
		$leftHigh = $left >> 16;
		$rightLow = $right & self::UNSIGNED_16_MASK;
		$rightHigh = $right >> 16;
		$middle = ($leftHigh * $rightLow) + ($leftLow * $rightHigh);

		return (($leftLow * $rightLow) + (($middle & self::UNSIGNED_16_MASK) << 16)) & self::UNSIGNED_32_MASK;
	}

	/**
	 * Applies unsigned 64-bit multiplication and addition with modulo 2^64 wrapping.
	 */
	private static function multiplyAddUnsigned64(int $multiplicand, int $multiplier, int $increment): int
	{
		$multiplicandLow = $multiplicand & self::UNSIGNED_32_MASK;
		$multiplicandHigh = self::shiftRightUnsigned64($multiplicand, self::UNSIGNED_32_BITS);
		$multiplierLow = $multiplier & self::UNSIGNED_32_MASK;
		$multiplierHigh = self::shiftRightUnsigned64($multiplier, self::UNSIGNED_32_BITS);

		$multiplicandLowLow = $multiplicandLow & self::UNSIGNED_16_MASK;
		$multiplicandLowHigh = $multiplicandLow >> 16;
		$multiplierLowLow = $multiplierLow & self::UNSIGNED_16_MASK;
		$multiplierLowHigh = $multiplierLow >> 16;
		$middle = ($multiplicandLowHigh * $multiplierLowLow) + ($multiplicandLowLow * $multiplierLowHigh);
		$lowWide = ($multiplicandLowLow * $multiplierLowLow) + (($middle & self::UNSIGNED_16_MASK) << 16);
		$low = $lowWide & self::UNSIGNED_32_MASK;
		$high = (
			($multiplicandLowHigh * $multiplierLowHigh)
			+ ($middle >> 16)
			+ intdiv($lowWide, Bit::UINT32_MAX_PLUS_ONE)
			+ self::multiplyUnsigned32($multiplicandHigh, $multiplierLow)
			+ self::multiplyUnsigned32($multiplicandLow, $multiplierHigh)
		) & self::UNSIGNED_32_MASK;

		$incrementLow = $increment & self::UNSIGNED_32_MASK;
		$lowWide = $low + $incrementLow;
		$low = $lowWide & self::UNSIGNED_32_MASK;
		$high = (
			$high
			+ self::shiftRightUnsigned64($increment, self::UNSIGNED_32_BITS)
			+ intdiv($lowWide, Bit::UINT32_MAX_PLUS_ONE)
		) & self::UNSIGNED_32_MASK;

		return ($high << self::UNSIGNED_32_BITS) | $low;
	}

	/**
	 * Shifts a signed PHP integer as an unsigned 64-bit value.
	 */
	private static function shiftRightUnsigned64(int $value, int $bits): int
	{
		if ($bits === 0) {
			return $value;
		}

		return ($value >> $bits) & (PHP_INT_MAX >> ($bits - 1));
	}

    private static function normalizeIntegerSeed(int|string|null $seed, int $defaultMinimum = 0, int $defaultMaximum = 0xFFFFFFFF, bool $requireNonZero = false): int
    {
        if ($seed === null || $seed === '') {
            $seed = rand($defaultMinimum, $defaultMaximum);
        } elseif (is_string($seed)) {
            $seed = is_numeric($seed) ? (int) $seed : (crc32($seed) & 0xFFFFFFFF);
        }

        if ($requireNonZero && $seed === 0) {
            return 1;
        }

        return $seed;
    }

    /**
     * SplitMix32 - Fast 32-bit state mixer
     * 
     * Used primarily for seeding other PRNGs. Produces well-distributed 8-bit outputs.
     * Based on MurmurHash3's finalizer with golden ratio increment.
     * 
     * @param int $s Initial seed value
     * 
     * @return Closure Returns random byte (0-255) on each call
     */
    public static function splitmix32(int|string|null $s = null): Closure
    {
        $s = self::normalizeIntegerSeed($s);

        return function () use (&$s): int {
            $s = ($s + Bit::GOLDEN_RATIO_APPROXIMATION) & 0xFFFFFFFF;

            $t = (($s ^ ($s >> 16)) * Bit::BIT_SCRAMBLING_CONSTANT_A) & 0xFFFFFFFF;
            $t = (($t ^ ($t >> 15)) * Bit::BIT_SCRAMBLING_CONSTANT_B) & 0xFFFFFFFF;

            return ($t ^ ($t >> 15)) & 0xFF;
        };
    }

    /**
     * Mulberry32 - Minimalistic 32-bit PRNG
     * 
     * Fast generator with good statistical properties for non-cryptographic use.
     * Period: 2^32. Passes BigCrush when properly seeded.
     * 
     * @param string|null $seed String seed (converted to integer) or null for random
     * @param bool $normalize If true, returns float in [0,1); otherwise returns uint32
     * 
     * @return Closure Returns random value on each call
     */
    public static function mulberry32(?string $seed = null, bool $normalize = false): Closure
    {
        $a = 0;
        if ($seed !== null && $seed !== '') {
            $len = strlen($seed);
            for ($i = 0; $i < $len; $i++) {
                $a = ($a + ord($seed[$i])) | 0;
            }
        } else {
            $a = rand(0, 2 ** 31 - 1);
        }

        return function () use (&$a, $normalize): float|int {
            $a |= 0;
            $a = ($a + Bit::MULBERRY32_INCREMENT) | 0;
            $t = ($a ^ ($a >> Bit::MULBERRY32_SHIFT1));
            $t = (($t * (Bit::MULBERRY32_MULTIPLIER_MASK | $a)) & 0xFFFFFFFF);
            $t = ($t + ((($t ^ ($t >> Bit::MULBERRY32_SHIFT2)) * (Bit::MULBERRY32_SECOND_MULTIPLIER | $t)) & 0xFFFFFFFF)) ^ $t;
            $result = ($t ^ ($t >> Bit::MULBERRY32_SHIFT3)) & 0xFFFFFFFF;

            return $normalize ? $result / Bit::UINT32_MAX_PLUS_ONE : $result;
        };
    }

    /**
     * Xorshift32 - 32-bit Xorshift PRNG by George Marsaglia
     * 
     * Simple and fast generator using XOR and bit shifts.
     * Period: 2^32 - 1. Not suitable for cryptographic purposes.
     * 
     * @param int|null $seed Initial state (must be non-zero)
     * 
     * @return Closure Returns uint32 on each call
     */
    public static function xorShift32(int|string|null $seed = null): Closure
    {
        $x = self::normalizeIntegerSeed($seed, 1, MaskingBit::DWORD, true);

        return function () use (&$x): int {
            $x ^= $x << 13;
            $x ^= $x >> 17;
            $x ^= $x << 5;

            return $x & MaskingBit::DWORD;
        };
    }

    /**
     * Xorshift64 - 64-bit Xorshift PRNG by George Marsaglia
     * 
     * Extended version with 64-bit state.
     * Period: 2^64 - 1.
     * 
     * @param int|null $seed Initial state (must be non-zero)
     * 
     * @return Closure Returns int64 on each call
     */
    public static function xorShift64(int|string|null $seed = null): Closure
    {
        $x = self::normalizeIntegerSeed($seed, 1, MaskingBit::QWORD, true);

        return function () use (&$x): int {
            $x ^= $x << 13;
            $x ^= $x >> 7;
            $x ^= $x << 17;

            return $x;
        };
    }

    /**
     * LCG32 - 32-bit Linear Congruential Generator
     * 
     * Classic PRNG using formula: state = (a * state + c) mod m.
     * Uses Numerical Recipes constants (a=1664525, c=1013904223).
     * Period: 2^32.
     * 
     * @param int|null $seed Initial state
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function lcg32(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $state = self::normalizeIntegerSeed($seed, 0, MaskingBit::DWORD);
        $a = 0x0019660d;
        $c = 0x3c6ef35f;

        return function () use (&$state, $a, $c, $normalize): float|int {
            $state = ($a * $state + $c) & MaskingBit::DWORD;
            return $normalize ? $state / Bit::UINT32_MAX_PLUS_ONE : $state;
        };
    }

    /**
     * Mersenne Twister 19937 (MT19937)
     * 
     * Industry-standard PRNG with period 2^19937-1.
     * Passes most statistical tests but not cryptographically secure.
     * State: 624 × 32-bit words.
     * 
     * @param int|null $seed Initial seed
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function mt19937(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $mt = [];
        $index = 624;

        $seed = self::normalizeIntegerSeed($seed);
        $mt[0] = $seed & 0xFFFFFFFF;
        for ($i = 1; $i < 624; $i++) {
            $mt[$i] = (0x6c078965 * ($mt[$i - 1] ^ ($mt[$i - 1] >> 30)) + $i) & 0xFFFFFFFF;
        }

        $generate = function () use (&$mt, &$index): int {
            if ($index >= 624) {
                for ($i = 0; $i < 624; $i++) {
                    $lowerMask = 0x7FFFFFFF;
                    $upperMask = 0x80000000;

                    $y = ($mt[$i] & $upperMask) | ($mt[($i + 1) % 624] & $lowerMask);
                    $mt[$i] = $mt[($i + 397) % 624] ^ ($y >> 1);
                    if ($y & 1) {
                        $mt[$i] ^= 0x9908b0df;
                    }
                }
                $index = 0;
            }

            $y = $mt[$index++];
            $y ^= $y >> 11;
            $y ^= ($y << 7) & 0x9d2c5680;
            $y ^= ($y << 15) & 0xefc60000;
            $y ^= $y >> 18;

            return $y & MaskingBit::DWORD;
        };

        return function () use ($generate, $normalize): float|int {
            return $normalize ? $generate() / Bit::UINT32_MAX_PLUS_ONE : $generate();
        };
    }

    /**
     * Xorshift128+ - Fast 128-bit state Xorshift variant
     * 
     * Used in major JavaScript engines (V8, SpiderMonkey).
     * Excellent speed and statistical quality. Period: 2^128 - 1.
     * 
     * @param int|null $seed1 First state word
     * @param int|null $seed2 Second state word
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function xorshift128plus(int|string|null $seed1 = null, int|string|null $seed2 = null, bool $normalize = false): Closure
    {
        $s1 = self::normalizeIntegerSeed($seed1, 1, 0xFFFFFFFF, true);
        $s2 = self::normalizeIntegerSeed($seed2, 1, 0xFFFFFFFF, true);

        return function () use (&$s1, &$s2, $normalize): float|int {
            $x = $s1;
            $y = $s2;
            $s1 = $y;
            $x ^= ($x << 23) & 0xFFFFFFFF;
            $s2 = ($x ^ $y ^ ($x >> 17) ^ ($y >> 26)) & 0xFFFFFFFF;
            $result = ($s2 + $y) & 0xFFFFFFFF;

            return $normalize ? $result / Bit::UINT32_MAX_PLUS_ONE : $result;
        };
    }

    /**
     * Xoshiro128** - Scrambled 128-bit state generator
     * 
     * High-quality all-purpose generator by Blackman & Vigna.
     * Passes BigCrush. Period: 2^128 - 1.
     * 
     * @param int|null $seed1 State word 0
     * @param int|null $seed2 State word 1
     * @param int|null $seed3 State word 2
     * @param int|null $seed4 State word 3
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function xoshiro128ss(
        ?int $seed1 = null,
        ?int $seed2 = null,
        ?int $seed3 = null,
        ?int $seed4 = null,
        bool $normalize = false
    ): Closure {
        $s = [
            $seed1 ?? rand(1, 0xFFFFFFFF),
            $seed2 ?? rand(1, 0xFFFFFFFF),
            $seed3 ?? rand(1, 0xFFFFFFFF),
            $seed4 ?? rand(1, 0xFFFFFFFF),
        ];

        return function () use (&$s, $normalize): float|int {
            $result = (($s[1] * 5) & 0xFFFFFFFF);
            $result = (((($result << 7) | ($result >> 25)) * 9) & 0xFFFFFFFF);

            $t = ($s[1] << 9) & 0xFFFFFFFF;
            $s[2] ^= $s[0];
            $s[3] ^= $s[1];
            $s[1] ^= $s[2];
            $s[0] ^= $s[3];
            $s[2] ^= $t;
            $s[3] = (($s[3] << 11) | ($s[3] >> 21)) & 0xFFFFFFFF;

            return $normalize ? $result / Bit::UINT32_MAX_PLUS_ONE : $result;
        };
    }

    /**
     * KISS32 - Keep It Simple Stupid 32-bit composite PRNG
     * 
     * Combines multiply-with-carry, xorshift, and LCG.
     * Very long period (~2^123). Good statistical properties.
     * 
     * @param int|null $seed Initial seed
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function kiss32(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $x = self::normalizeIntegerSeed($seed);
        $y = 362436069;
        $z = 521288629;
        $w = 88675123;
        $c = 0;

        return function () use (&$x, &$y, &$z, &$w, &$c, $normalize): float|int {
            $y ^= ($y << 5);
            $y ^= ($y >> 7);
            $y ^= ($y << 22);

            $t = ($z + $w + $c) & 0xFFFFFFFF;
            $c = $t < $z ? 1 : 0;
            $z = $w;
            $w = $t;

            $x = (69069 * $x + 12345) & 0xFFFFFFFF;
            $result = ($x + $y + $w) & 0xFFFFFFFF;

            return $normalize ? $result / Bit::UINT32_MAX_PLUS_ONE : $result;
        };
    }

    /**
     * WELL512 - Well Equidistributed Long-period Linear 512-bit
     * 
     * Improved Mersenne Twister variant with better equidistribution.
     * State: 16 × 32-bit words. Period: 2^512 - 1.
     * 
     * @param int|null $seed Initial seed for state initialization
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function well512(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $state = [];
        $index = 0;

        $seed = self::normalizeIntegerSeed($seed);
        mt_srand($seed);
        for ($i = 0; $i < 16; $i++) {
            $state[$i] = mt_rand(0, 0xFFFFFFFF);
        }

        return function () use (&$state, &$index, $normalize): float|int {
            $a = $state[$index];
            $c = $state[($index + 13) & 15];
            $b = $a ^ $c ^ ($a << 16) ^ ($c << 15);
            $c = $state[($index + 9) & 15];
            $c ^= ($c >> 11);
            $a = $state[$index] = $b ^ $c;
            $d = $a ^ (($a << 5) & 0xda442d24);
            $index = ($index + 15) & 15;
            $a = $state[$index];
            $state[$index] = ($a ^ $b ^ $d ^ ($a << 2) ^ ($b << 18) ^ ($c << 28)) & 0xFFFFFFFF;

            return $normalize ? $state[$index] / Bit::UINT32_MAX_PLUS_ONE : $state[$index];
        };
    }

    /**
     * AES-CTR RNG - Counter mode AES-based PRNG
     * 
     * Cryptographically secure PRNG using AES-128 in counter mode.
     * Requires OpenSSL extension.
     * 
     * @param string|null $key 16-byte AES key (random if null)
     * @param string|null $iv 16-byte initialization vector (random if null)
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function aesCtrRng(?string $key = null, ?string $iv = null, bool $normalize = false): Closure
    {
        $key = $key ?? random_bytes(16);
        $iv = $iv ?? random_bytes(16);
        $counter = 0;

        return function () use (&$key, &$iv, &$counter, $normalize): float|int {
            $counterBytes = pack("N", $counter);
            $data = str_pad($counterBytes, 16, "\0", STR_PAD_LEFT);
            $enc = openssl_encrypt($data, 'AES-128-CTR', $key, OPENSSL_RAW_DATA, $iv);
            $counter++;
            $val = unpack("P", substr($enc, 0, 8))[1];

            return $normalize ? $val / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE) : $val;
        };
    }

    /**
     * TinyMT32 - Tiny Mersenne Twister 32-bit
     * 
     * Lightweight MT variant for embedded systems.
     * State: 3 × 32-bit words. Period: 2^127 - 1.
     * 
     * @param int|null $seed Initial seed
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function tinymt32(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $mask = 0xFFFFFFFF;
        $state = [
            self::normalizeIntegerSeed($seed, 1, 0xFFFFFFFF, true),
            0x8f7011ee,
            0xfc78ff1f,
        ];

        return function () use (&$state, $mask, $normalize): float|int {
            $y = $state[0] & 0x7fffffff;
            $y ^= $state[1] & $mask;
            $y ^= ($y << 12) & $mask;
            $y ^= ($y >> 20) & $mask;
            $y ^= ($y << 4) & $mask;
            $y ^= ($y >> 7) & $mask;

            $t = ($state[0] & 0x7fffffff) ^ $state[1] ^ ($state[2] << 16);
            $state[0] = $state[1];
            $state[1] = $state[2];
            $state[2] = ($t ^ ($t >> 1)) & $mask;

            return $normalize ? ($y & $mask) / Bit::UINT32_MAX_PLUS_ONE : ($y & $mask);
        };
    }

    /**
     * WELL512a - WELL variant with improved tempering
     * 
     * @param int|string|null $seed Initial seed
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function well512a(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $mask = 0xFFFFFFFF;
        $state = [];
        for ($i = 0; $i < 16; $i++) {
            $state[$i] = $seed !== null ? (crc32($seed . $i) & $mask) : random_int(0, $mask);
        }
        $index = 0;

        return function () use (&$state, &$index, $mask, $normalize): float|int {
            $a = $state[$index];
            $c = $state[($index + 13) & 15];
            $b = $a ^ $c ^ ($a << 16) ^ ($c << 15);
            $c = $state[($index + 9) & 15];
            $c ^= ($c >> 11);
            $a = $state[$index] = $b ^ $c;
            $d = $a ^ (($a << 5) & 0xDA442D24);

            $index = ($index + 15) & 15;
            $a = $state[$index];
            $state[$index] = ($a ^ $b ^ $d ^ ($a << 2) ^ ($b << 18) ^ ($c << 28)) & $mask;

            return $normalize ? ($state[$index] & $mask) / Bit::UINT32_MAX_PLUS_ONE : ($state[$index] & $mask);
        };
    }

    /**
     * MWC256 - Multiply-With-Carry with 256-word state
     * 
     * Very long period generator (~2^8222).
     * State: 4096 × 32-bit words plus carry.
     * 
     * @param int|string|null $seed Initial seed
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function mwc256(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $mask32 = 0xFFFFFFFF;
        $r = 4096;
        $state = [];
        for ($i = 0; $i < $r; $i++) {
            $state[$i] = ($seed !== null) ? (crc32($seed . (string) $i) & $mask32) : random_int(0, $mask32);
        }
        $c = 362436;
        $idx = 0;

        return function () use (&$state, &$c, &$idx, $r, $mask32, $normalize): float|int {
            $a = 809430660;
            $t = ($a * $state[$idx] + $c);
            $c = ($t >> 32) & $mask32;
            $state[$idx] = $t & $mask32;
            $v = $state[$idx];
            $idx = ($idx + 1) % $r;

            return $normalize ? $v / Bit::UINT32_MAX_PLUS_ONE : $v;
        };
    }

    /**
     * HMAC-DRBG SHA-256 - Deterministic Random Bit Generator
     * 
     * NIST SP 800-90A compliant CSPRNG using HMAC-SHA256.
     * Cryptographically secure for key generation.
     * 
     * @param string $seed Entropy input for instantiation
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function hmac_drbg_sha256(int|string $seed, bool $normalize = false): Closure
    {
        $seed = (string) $seed;
        $K = str_repeat("\0", 32);
        $V = str_repeat("\x01", 32);

        $K = hash_hmac('sha256', $V . "\x00" . $seed, $K, true);
        $V = hash_hmac('sha256', $V, $K, true);

        $K = hash_hmac('sha256', $V . "\x01" . $seed, $K, true);
        $V = hash_hmac('sha256', $V, $K, true);

        return function () use (&$K, &$V, $normalize): float|int {
            $V = hash_hmac('sha256', $V, $K, true);
            $val = unpack("P", substr($V, 0, 8))[1];

            return $normalize ? $val / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE) : $val;
        };
    }

    /**
     * PCG32 - Permuted Congruential Generator 32-bit output
     * 
     * Modern PRNG with excellent statistical properties.
     * Small state (64-bit), fast, and passes all tests.
     * 
     * @param int|null $seed Initial state seed
     * @param int|null $seq Stream sequence selector
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function pcg32(int|string|null $seed = null, int|string|null $seq = null, bool $normalize = false): Closure
    {
        $state = self::normalizeIntegerSeed($seed);
        $inc = (self::normalizeIntegerSeed($seq) << 1) | 1;

        return function () use (&$state, $inc, $normalize): float|int {
            $oldstate = $state;
			$state = self::multiplyAddUnsigned64($oldstate, self::PCG32_MULTIPLIER, $inc);
			$xorshifted = self::shiftRightUnsigned64(self::shiftRightUnsigned64($oldstate, 18) ^ $oldstate, 27) & self::UNSIGNED_32_MASK;
			$rotation = self::shiftRightUnsigned64($oldstate, 59) & 0x1F;
			$result = (($xorshifted >> $rotation) | ($xorshifted << ((-$rotation) & 31))) & self::UNSIGNED_32_MASK;

            return $normalize ? $result / Bit::UINT32_MAX_PLUS_ONE : $result;
        };
    }

    /**
     * SFMT19937 - SIMD-oriented Fast Mersenne Twister
     * 
     * Vectorized MT variant with improved performance.
     * Period: 2^19937 - 1.
     * 
     * @param int|null $seed Initial seed
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function sfmt19937(int|string|null $seed = null, bool $normalize = false): Closure
    {
        $state = [];
        $index = 0;

        $seed = $seed ?? rand(0, 0xFFFFFFFF);
        $state[0] = $seed & 0xFFFFFFFF;
        for ($i = 1; $i < 624; $i++) {
            $state[$i] = (0x6c078965 * ($state[$i - 1] ^ ($state[$i - 1] >> 30)) + $i) & 0xFFFFFFFF;
        }

        $twist = function () use (&$state): void {
            for ($i = 0; $i < 624; $i++) {
                $y = ($state[$i] & 0x80000000) | ($state[($i + 1) % 624] & 0x7fffffff);
                $state[$i] = $state[($i + 397) % 624] ^ ($y >> 1);
                if ($y & 1) {
                    $state[$i] ^= 0x9908b0df;
                }
            }
        };

        return function () use (&$state, &$index, $twist, $normalize): float|int {
            if ($index === 0) {
                $twist();
            }

            $y = $state[$index];
            $y ^= ($y >> 11);
            $y ^= ($y << 7) & 0x9d2c5680;
            $y ^= ($y << 15) & 0xefc60000;
            $y ^= ($y >> 18);

            $index = ($index + 1) % 624;
            return $normalize ? ($y & 0xFFFFFFFF) / Bit::UINT32_MAX_PLUS_ONE : ($y & 0xFFFFFFFF);
        };
    }

    /**
     * ChaCha20 RNG - Full ChaCha20 quarter-round implementation
     * 
     * Software implementation of ChaCha20 stream cipher for PRNG use.
     * Cryptographically secure when properly seeded.
     * 
     * @param string|null $key 32-byte key (random if null)
     * @param string|null $nonce 12-byte nonce (random if null)
     * @param bool $normalize If true, returns float in [0,1)
     * 
     * @return Closure Returns random value on each call
     */
    public static function chacha20_rng(?string $key = null, ?string $nonce = null, bool $normalize = false): Closure
    {
        $key = $key ?? random_bytes(32);
        $nonce = $nonce ?? random_bytes(12);
        $counter = 0;

        $quarterRound = function (&$a, &$b, &$c, &$d): void {
            $a = ($a + $b) & 0xFFFFFFFF;
            $d ^= $a;
            $d = (($d << 16) | ($d >> 16)) & 0xFFFFFFFF;
            $c = ($c + $d) & 0xFFFFFFFF;
            $b ^= $c;
            $b = (($b << 12) | ($b >> 20)) & 0xFFFFFFFF;
            $a = ($a + $b) & 0xFFFFFFFF;
            $d ^= $a;
            $d = (($d << 8) | ($d >> 24)) & 0xFFFFFFFF;
            $c = ($c + $d) & 0xFFFFFFFF;
            $b ^= $c;
            $b = (($b << 7) | ($b >> 25)) & 0xFFFFFFFF;
        };

        return function () use (&$key, &$nonce, &$counter, $quarterRound, $normalize): float|int {
            $state = [0x61707865, 0x3320646e, 0x79622d32, 0x6b206574];
            $state = array_merge($state, array_values(unpack("V*", $key)));
            $state[] = $counter++;
            $state = array_merge($state, array_values(unpack("V*", $nonce)));

            $workingState = $state;
            for ($i = 0; $i < 10; $i++) {
                $quarterRound($workingState[0], $workingState[4], $workingState[8], $workingState[12]);
                $quarterRound($workingState[1], $workingState[5], $workingState[9], $workingState[13]);
                $quarterRound($workingState[2], $workingState[6], $workingState[10], $workingState[14]);
                $quarterRound($workingState[3], $workingState[7], $workingState[11], $workingState[15]);
                $quarterRound($workingState[0], $workingState[5], $workingState[10], $workingState[15]);
                $quarterRound($workingState[1], $workingState[6], $workingState[11], $workingState[12]);
                $quarterRound($workingState[2], $workingState[7], $workingState[8], $workingState[13]);
                $quarterRound($workingState[3], $workingState[4], $workingState[9], $workingState[14]);
            }

            for ($i = 0; $i < 16; $i++) {
                $workingState[$i] = ($workingState[$i] + $state[$i]) & 0xFFFFFFFF;
            }

            $out = pack("V*", ...$workingState);
            $val = unpack("P", substr($out, 0, 8))[1];

            return $normalize ? $val / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE) : $val;
        };
    }

    /**
     * Xoroshiro64** - 64-bit state scrambled rotated Xorshift
     * 
     * Compact generator suitable for 32-bit systems.
     * Period: 2^64 - 1.
     * 
     * @param int|null $seed1 First state word
     * @param int|null $seed2 Second state word
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function xoroshiro64ss(?int $seed1 = null, ?int $seed2 = null): Closure
    {
        $s0 = $seed1 ?? rand(0, 0xFFFFFFFF);
        $s1 = $seed2 ?? rand(0, 0xFFFFFFFF);

        $rotl = function (int $x, int $k): int {
            return (($x << $k) | ($x >> (32 - $k))) & 0xFFFFFFFF;
        };

        return function () use (&$s0, &$s1, $rotl): float {
            $result = ($rotl(($s0 * 0x9E3779BB) & 0xFFFFFFFF, 5) * 5) & 0xFFFFFFFF;
            $t = $s1 ^ $s0;
            $s0 = $rotl($s0, 26) ^ $t ^ (($t << 9) & 0xFFFFFFFF);
            $s1 = $rotl($t, 13);

            return $result / Bit::UINT32_MAX_PLUS_ONE;
        };
    }

    /**
     * JSF32 - Bob Jenkins' Small Fast 32-bit PRNG
     * 
     * Fast non-cryptographic generator with 128-bit state.
     * Period: ~2^126.
     * 
     * @param int|null $seed Initial seed value
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function jsf32(?int $seed = null): Closure
    {
        $a = $seed ?? rand(0, 0xFFFFFFFF);
        $b = 0x9E3779B9;
        $c = 0x9E3779B9;
        $d = 0x9E3779B9;

        return function () use (&$a, &$b, &$c, &$d): float {
            $e = ($a - (($b << 27) | ($b >> 5))) & 0xFFFFFFFF;
            $a = ($b ^ (($c << 17) | ($c >> 15))) & 0xFFFFFFFF;
            $b = ($c + $d) & 0xFFFFFFFF;
            $c = ($d + $e) & 0xFFFFFFFF;
            $d = ($a + $e) & 0xFFFFFFFF;

            return $d / Bit::UINT32_MAX_PLUS_ONE;
        };
    }

    /**
     * MSWS - Middle Square Weyl Sequence
     * 
     * Simple generator combining squared middle extraction with Weyl sequence.
     * Fast and passes statistical tests.
     * 
     * @param int|null $seed Initial seed
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function msws(?int $seed = null): Closure
    {
        $x = $seed ?? rand(0, 0xFFFFFFFF);
        $w = 0;
        $s = 0xb5ad4eceda1ce2a9;

        return function () use (&$x, &$w, $s): float {
            $x = ($x * $x) & 0xFFFFFFFFFFFFFFFF;
            $w = ($w + $s) & 0xFFFFFFFFFFFFFFFF;
            $x = ($x + $w) & 0xFFFFFFFFFFFFFFFF;
            $x = (($x >> 32) | ($x << 32)) & 0xFFFFFFFFFFFFFFFF;

            return ($x & 0xFFFFFFFF) / Bit::UINT32_MAX_PLUS_ONE;
        };
    }

    /**
     * WELL1024a - WELL with 1024-bit state
     * 
     * Extended WELL variant for longer period.
     * State: 32 × 32-bit words. Period: 2^1024 - 1.
     * 
     * @param int|null $seed Initial seed
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function well1024a(?int $seed = null): Closure
    {
        $state = [];
        $index = 0;

        $seed = $seed ?? rand(0, 0xFFFFFFFF);
        mt_srand($seed);
        for ($i = 0; $i < 32; $i++) {
            $state[$i] = mt_rand(0, 0xFFFFFFFF);
        }

        return function () use (&$state, &$index): float {
            $z0 = $state[($index + 31) & 31];
            $z1 = $state[$index] ^ ($state[($index + 3) & 31] ^ ($state[($index + 3) & 31] >> 8));
            $z2 = ($state[($index + 24) & 31] ^ ($state[($index + 24) & 31] << 19)) ^ $state[($index + 10) & 31];
            $newV1 = $z1 ^ $z2;
            $newV0 = ($z0 ^ ($z0 << 11) ^ ($z1 ^ ($z1 << 7)) ^ ($z2 ^ ($z2 << 13))) & 0xFFFFFFFF;

            $state[$index] = $newV0;
            $index = ($index + 31) & 31;
            $state[$index] = $newV1 & 0xFFFFFFFF;

            return $state[$index] / Bit::UINT32_MAX_PLUS_ONE;
        };
    }

    /**
     * WELL19937a - WELL with 19937-bit state
     * 
     * Large state WELL for maximum period.
     * State: 624 × 32-bit words. Period: 2^19937 - 1.
     * 
     * @param int|null $seed Initial seed
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function well19937a(?int $seed = null): Closure
    {
        $state = [];
        $index = 0;

        $seed = $seed ?? rand(0, 0xFFFFFFFF);
        mt_srand($seed);
        for ($i = 0; $i < 624; $i++) {
            $state[$i] = mt_rand(0, 0xFFFFFFFF);
        }

        return function () use (&$state, &$index): float {
            $z0 = $state[($index + 623) % 624];
            $z1 = ($state[$index] ^ ($state[$index] << 25)) ^ ($state[($index + 70) % 624] ^ ($state[($index + 70) % 624] >> 27));
            $z2 = $state[($index + 179) % 624] ^ ($state[($index + 179) % 624] >> 9);

            $newV1 = $z1 ^ $z2;
            $newV0 = ($z0 ^ ($z0 << 21) ^ $z1 ^ ($z1 << 19) ^ $z2 ^ ($z2 << 6)) & 0xFFFFFFFF;

            $state[$index] = $newV0;
            $index = ($index + 623) % 624;
            $state[$index] = $newV1 & 0xFFFFFFFF;

            return $state[$index] / Bit::UINT32_MAX_PLUS_ONE;
        };
    }

    /**
     * PCG32 variant 2 - Alternative PCG implementation
     * 
     * Properly initialized PCG with stream support.
     * 
     * @param int|null $seed Initial seed
     * @param int $seq Stream sequence (default 54)
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function pcg32_2(?int $seed = null, int $seq = 54): Closure
    {
        $inc = ($seq << 1) | 1;
        $seed = $seed ?? random_int(0, 0xFFFFFFFF);

        $state = 0;
        $state = ($state * 6364136223846793005 + $inc) & 0xFFFFFFFFFFFFFFFF;
        $state = ($state + $seed) & 0xFFFFFFFFFFFFFFFF;
        $state = ($state * 6364136223846793005 + $inc) & 0xFFFFFFFFFFFFFFFF;

        return function () use (&$state, $inc): float {
            $oldstate = $state;
            $state = ($state * 6364136223846793005 + $inc) & 0xFFFFFFFFFFFFFFFF;
            $xorshifted = ((($oldstate >> 18) ^ $oldstate) >> 27) & 0xFFFFFFFF;
            $rot = ($oldstate >> 59) & 0x1F;

            return ((($xorshifted >> $rot) | ($xorshifted << ((-$rot) & 31))) & 0xFFFFFFFF) / Bit::UINT32_MAX_PLUS_ONE;
        };
    }

    /**
     * PCG64 - 64-bit output PCG
     * 
     * Extended PCG for 64-bit random numbers.
     * 128-bit state, 64-bit output.
     * 
     * @param int|null $seed Initial seed
     * @param int $seq Stream sequence
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function pcg64(?int $seed = null, int $seq = 1442695040888963407): Closure
    {
        $inc = ($seq << 1) | 1;
        $seed = $seed ?? random_int(0, PHP_INT_MAX);

        $state = 0;
        $state = ($state * 6364136223846793005 + $inc) & 0xFFFFFFFFFFFFFFFF;
        $state = ($state + $seed) & 0xFFFFFFFFFFFFFFFF;
        $state = ($state * 6364136223846793005 + $inc) & 0xFFFFFFFFFFFFFFFF;

        return function () use (&$state, $inc): float {
            $oldstate = $state;
            $state = ($state * 6364136223846793005 + $inc) & 0xFFFFFFFFFFFFFFFF;
            $xorshifted = ((($oldstate >> 29) ^ $oldstate) >> 58) & 0xFFFFFFFFFFFFFFFF;
            $rot = ($oldstate >> 61) & 0x1F;

            return ((($xorshifted >> $rot) | ($xorshifted << ((-$rot) & 63))) & 0xFFFFFFFFFFFFFFFF) / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * SplitMix64 - Fast 64-bit state splitter
     * 
     * High-quality finalizer for seeding other PRNGs.
     * Used in Java's SplittableRandom.
     * 
     * @param int|null $seed Initial seed
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function splitmix64(?int $seed = null): Closure
    {
        $x = $seed ?? random_int(0, 0xFFFFFFFFFFFFFFFF);

        return function () use (&$x): float {
            $x = ($x + 0x9E3779B97F4A7C15) & 0xFFFFFFFFFFFFFFFF;
            $z = $x;
            $z = (($z ^ ($z >> 30)) * 0xBF58476D1CE4E5B9) & 0xFFFFFFFFFFFFFFFF;
            $z = (($z ^ ($z >> 27)) * 0x94D049BB133111EB) & 0xFFFFFFFFFFFFFFFF;

            return (($z ^ ($z >> 31)) & 0xFFFFFFFFFFFFFFFF) / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * 64-bit rotate left helper
     * 
     * @param int $x Value to rotate
     * @param int $k Rotation amount
     * 
     * @return int Rotated value
     */
    private static function rotl64(int $x, int $k): int
    {
        $mask = 0xFFFFFFFFFFFFFFFF;
        return ((($x << $k) & $mask) | (($x >> (64 - $k)) & $mask)) & $mask;
    }

    /**
     * Xoshiro256++ - 256-bit state scrambled generator
     * 
     * Recommended all-purpose 64-bit generator by Blackman & Vigna.
     * Passes BigCrush and PractRand. Period: 2^256 - 1.
     * 
     * @param int|null $s0 State word 0
     * @param int|null $s1 State word 1
     * @param int|null $s2 State word 2
     * @param int|null $s3 State word 3
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function xoshiro256pp(?int $s0 = null, ?int $s1 = null, ?int $s2 = null, ?int $s3 = null): Closure
    {
        $mask = 0xFFFFFFFFFFFFFFFF;
        $s = [
            $s0 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s1 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s2 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s3 ?? random_int(0, PHP_INT_MAX) & $mask,
        ];

        return function () use (&$s, $mask): float {
            $result = (self::rotl64((($s[0] + $s[3]) & $mask), 23) + $s[0]) & $mask;

            $t = ($s[1] << 17) & $mask;

            $s[2] ^= $s[0];
            $s[3] ^= $s[1];
            $s[1] ^= $s[2];
            $s[0] ^= $s[3];

            $s[2] ^= $t;
            $s[3] = self::rotl64($s[3], 45);

            return $result / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * Xoshiro256** - Scrambled 256-bit state generator
     * 
     * Alternative scrambler to xoshiro256++.
     * Excellent statistical properties. Period: 2^256 - 1.
     * 
     * @param int|null $s0 State word 0
     * @param int|null $s1 State word 1
     * @param int|null $s2 State word 2
     * @param int|null $s3 State word 3
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function xoshiro256ss(?int $s0 = null, ?int $s1 = null, ?int $s2 = null, ?int $s3 = null): Closure
    {
        $mask = 0xFFFFFFFFFFFFFFFF;
        $s = [
            $s0 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s1 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s2 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s3 ?? random_int(0, PHP_INT_MAX) & $mask,
        ];

        return function () use (&$s, $mask): float {
            $res1 = ($s[1] * 5) & $mask;
            $result = (self::rotl64($res1, 7) * 9) & $mask;

            $t = ($s[1] << 17) & $mask;

            $s[2] ^= $s[0];
            $s[3] ^= $s[1];
            $s[1] ^= $s[2];
            $s[0] ^= $s[3];

            $s[2] ^= $t;
            $s[3] = self::rotl64($s[3], 45);

            return $result / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * Xoroshiro128+ - 128-bit state rotated Xorshift
     * 
     * Fast generator for floating-point numbers.
     * Lowest bits have linear artifacts; use upper bits.
     * Period: 2^128 - 1.
     * 
     * @param int|null $s0 First state word
     * @param int|null $s1 Second state word
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function xoroshiro128p(?int $s0 = null, ?int $s1 = null): Closure
    {
        $mask = 0xFFFFFFFFFFFFFFFF;
        $s = [
            $s0 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s1 ?? random_int(0, PHP_INT_MAX) & $mask,
        ];

        return function () use (&$s, $mask): float {
            $result = ($s[0] + $s[1]) & $mask;

            $s1 = $s[0] ^ $s[1];
            $s[0] = self::rotl64($s[0], 55) ^ $s1 ^ (($s1 << 14) & $mask);
            $s[1] = self::rotl64($s1, 36);

            return $result / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * Xoroshiro128** - Scrambled 128-bit rotated Xorshift
     * 
     * Improved variant with better low-bit behavior.
     * Period: 2^128 - 1.
     * 
     * @param int|null $s0 First state word
     * @param int|null $s1 Second state word
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function xoroshiro128ss(?int $s0 = null, ?int $s1 = null): Closure
    {
        $mask = 0xFFFFFFFFFFFFFFFF;
        $s = [
            $s0 ?? random_int(0, PHP_INT_MAX) & $mask,
            $s1 ?? random_int(0, PHP_INT_MAX) & $mask,
        ];

        return function () use (&$s, $mask): float {
            $result = self::rotl64(($s[0] * 5) & $mask, 7);
            $result = ($result * 9) & $mask;

            $s1 = $s[0] ^ $s[1];
            $s[0] = self::rotl64($s[0], 24) ^ $s1 ^ (($s1 << 16) & $mask);
            $s[1] = self::rotl64($s1, 37);

            return $result / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * ChaCha20 - Libsodium-based CSPRNG
     * 
     * Uses sodium_crypto_stream for cryptographically secure randomness.
     * Requires libsodium extension.
     * 
     * @param string|null $key 32-byte key (random if null)
     * @param string|null $nonce 24-byte nonce (random if null)
     * @param int $counter Initial counter value
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function chacha20(?string $key = null, ?string $nonce = null, int $counter = 1): Closure
    {
        $key = $key ?? random_bytes(32);
        $nonce = $nonce ?? random_bytes(24);

        return function () use (&$key, &$nonce, &$counter): float {
            $out = sodium_crypto_stream(8, $nonce, $key);
            $counter++;
            $val = unpack("P", $out)[1];

            return $val / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * Blum Blum Shub - Cryptographically secure PRNG
     * 
     * Based on quadratic residues modulo n = p*q where p ≡ q ≡ 3 (mod 4).
     * Provably secure under integer factorization hardness.
     * Very slow compared to other generators.
     * 
     * @param int|null $seed Initial seed (must be coprime to n)
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function blumBlumShub(?int $seed = null): Closure
    {
        $p = 10007;
        $q = 10009;
        $n = $p * $q;

        $state = ($seed ?? random_int(2, $n - 1)) % $n;
        if (gmp_gcd($state, $n) != 1) {
            $state++;
        }

        return function () use (&$state, $n): float {
            $state = ($state * $state) % $n;
            return $state / $n;
        };
    }

    /**
     * LFSR113 - Combined Tausworthe generator by Pierre L'Ecuyer
     * 
     * Four combined LFSRs with period ~2^113.
     * Passes all DIEHARD tests.
     * 
     * @param int|null $z1 LFSR state 1
     * @param int|null $z2 LFSR state 2
     * @param int|null $z3 LFSR state 3
     * @param int|null $z4 LFSR state 4
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function lfsr113(?int $z1 = null, ?int $z2 = null, ?int $z3 = null, ?int $z4 = null): Closure
    {
        $z1 = $z1 ?? random_int(1, 4294967087);
        $z2 = $z2 ?? random_int(1, 4294967087);
        $z3 = $z3 ?? random_int(1, 4294967087);
        $z4 = $z4 ?? random_int(1, 4294967087);

        return function () use (&$z1, &$z2, &$z3, &$z4): float {
            $b = (($z1 << 6) ^ $z1) >> 13;
            $z1 = (($z1 & 4294967294) << 18) ^ $b;

            $b = (($z2 << 2) ^ $z2) >> 27;
            $z2 = (($z2 & 4294967288) << 2) ^ $b;

            $b = (($z3 << 13) ^ $z3) >> 21;
            $z3 = (($z3 & 4294967280) << 7) ^ $b;

            $b = (($z4 << 3) ^ $z4) >> 12;
            $z4 = (($z4 & 4294967168) << 13) ^ $b;

            $res = ($z1 ^ $z2 ^ $z3 ^ $z4) & 0xFFFFFFFF;
            return $res / Bit::UINT32_MAX_PLUS_ONE;
        };
    }

    /**
     * urandom - OS-level CSPRNG wrapper
     * 
     * Uses PHP's random_bytes() which sources from /dev/urandom on Unix
     * or CryptGenRandom on Windows.
     * 
     * @param int $bytes Number of random bytes to generate per call
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function urandom(int $bytes = 8): Closure
    {
        return function () use ($bytes): float {
            $data = random_bytes($bytes);
            $packed = str_pad($data, 8, "\0", STR_PAD_RIGHT);
            $v = unpack("P", $packed)[1];

            return $v / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * Threefry 2x64 - Counter-based PRNG
     * 
     * Simplified Threefish-like mixer from Random123 library.
     * Deterministic stream from counter and key.
     * 
     * @param int|null $key Encryption key
     * @param int|null $nonce Initial counter value
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function threefry2x64(?int $key = null, ?int $nonce = null): Closure
    {
        $mask = 0xFFFFFFFFFFFFFFFF;
        $k0 = $key !== null ? $key & $mask : random_int(0, PHP_INT_MAX) & $mask;
        $k1 = random_int(0, PHP_INT_MAX) & $mask;
        $ctr = $nonce !== null ? $nonce & $mask : 0;

        $rotl = function (int $x, int $r) use ($mask): int {
            return ((($x << $r) & $mask) | ($x >> (64 - $r))) & $mask;
        };

        return function () use (&$k0, &$k1, &$ctr, $rotl, $mask): float {
            $x0 = $ctr;
            $x1 = $k0 ^ $k1;

            $x0 = ($x0 + $x1) & $mask;
            $x1 = $rotl($x1, 41) ^ $x0;

            $x0 = ($x0 + $x1) & $mask;
            $x1 = $rotl($x1, 17) ^ $x0;

            $x0 = ($x0 + $k0) & $mask;
            $x1 = ($x1 + $k1) & $mask;

            $ctr = ($ctr + 1) & $mask;

            return $x0 / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * KISS64 - 64-bit Keep It Simple Stupid composite PRNG
     * 
     * Combines LCG, xorshift, and multiply-with-carry.
     * Very long period (~2^250).
     * 
     * @param int|null $seed Initial seed
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function kiss64(?int $seed = null): Closure
    {
        $mask = 0xFFFFFFFFFFFFFFFF;
        $x = $seed ?? random_int(1, PHP_INT_MAX) & $mask;
        $y = 4101842887655102017;
        $z = 1;
        $c = 1;

        return function () use (&$x, &$y, &$z, &$c, $mask): float {
            $x = (6906969069 * $x + 1234567) & $mask;
            $y ^= ($y << 13) & $mask;
            $y ^= ($y >> 17) & $mask;
            $y ^= ($y << 43) & $mask;

            $t = ($z << 58) + $c;
            $c = ($z >> 6) & 0xFFFFFFFF;
            $z = ($z + $t) & $mask;

            $res = ($x + $y + $z) & $mask;
            return $res / (Bit::UINT32_MAX_PLUS_ONE * Bit::UINT32_MAX_PLUS_ONE);
        };
    }

    /**
     * Lehmer64 - 64-bit Lehmer/Park-Miller PRNG
     * 
     * Fast LCG using 128-bit multiplication via BCMath.
     * Multiplier: 0xda942042e4dd58b5.
     * 
     * @param int|null $seed Initial seed
     * 
     * @return Closure Returns float in [0,1) on each call
     */
    public static function lehmer64(?int $seed = null): Closure
    {
        $seed = $seed ?? random_int(1, PHP_INT_MAX);
        $state = bcmod((string) $seed, Bit::MASK_64);

        return function () use (&$state): float {
            $MASK64 = bcpow('2', '64');
            $SHIFT32 = bcpow('2', '32');
            $UINT32_MAX_PLUS_ONE = bcpow('2', '32');

            $state = bcmod(bcmul($state, Bit::LEHMER_64), $MASK64);
            $upper32 = bcdiv($state, $SHIFT32, 0);
            $result = bcdiv($upper32, $UINT32_MAX_PLUS_ONE, 16);

            return (float) $result;
        };
    }
}
