<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Component;

use function ctype_digit;
use function explode;
use function getenv;
use function inet_pton;
use function intdiv;
use function is_string;
use function ord;
use function str_contains;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Resolves the set of peers whose {@code X-Forwarded-*} headers may be believed.
 *
 * Read from {@code APP_TRUSTED_PROXIES} as a comma-separated list. An unset or empty value trusts
 * nothing, so forwarded headers are ignored entirely — that is the safe default and must stay the
 * default, because every one of those headers is attacker-controlled on a direct connection.
 *
 * Supported entry forms:
 *  - exact IPv4 address, e.g. {@code 10.0.0.1}
 *  - exact IPv6 address, e.g. {@code ::1} (compared in binary form, so any spelling of the same
 *    address matches)
 *  - IPv4 CIDR, e.g. {@code 10.0.0.0/8}
 *  - IPv6 CIDR, e.g. {@code fd00::/8}
 *
 * A CIDR entry only ever matches peers of its own address family; an IPv4-mapped IPv6 peer
 * (::ffff:10.0.0.1) is not matched by an IPv4 range and must be listed in its IPv6 form.
 *
 * There is deliberately no {@code *} wildcard: "trust whoever connected" re-opens the hole this
 * class exists to close, and inventing non-standard syntax for it makes the dangerous case the
 * easiest one to type. An operator who genuinely terminates TLS behind an unknowable peer address
 * can still write the standard {@code 0.0.0.0/0} or {@code ::/0}, which trusts every peer of that
 * family — only do that when nothing but the proxy can open a socket to this application, since
 * otherwise any client can dictate whether HSTS is sent.
 */
final class TrustedProxies
{
    private const ENV_KEY = 'APP_TRUSTED_PROXIES';
    private const CIDR_PART_LIMIT = 2;
    private const BITS_PER_BYTE = 8;
    private const BYTE_MASK = 0xFF;

    /**
     * @param list<string> $entries Normalized, non-empty proxy entries.
     */
    private function __construct(private readonly array $entries)
    {
    }

    /**
     * Build the trusted set from the runtime environment.
     *
     * Call this per request rather than caching it: the environment is mutable at runtime and a
     * stale copy would silently keep trusting a proxy that has since been removed.
     */
    public static function fromEnv(): self
    {
        $raw = $_ENV[self::ENV_KEY] ?? getenv(self::ENV_KEY);

        return self::fromList(is_string($raw) ? explode(',', $raw) : []);
    }

    /**
     * Build the trusted set from an explicit list, e.g. supplied by a test or a container binding.
     *
     * @param array<array-key, mixed> $entries
     */
    public static function fromList(array $entries): self
    {
        $normalized = [];
        foreach ($entries as $entry) {
            if (!is_string($entry)) {
                continue;
            }
            $value = self::normalize($entry);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return new self($normalized);
    }

    /**
     * True when no proxy is trusted, i.e. forwarded headers must be ignored.
     */
    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    /**
     * Decide whether the immediate peer address is a configured proxy.
     *
     * Only the immediate peer counts. An address taken from a forwarded header is not a peer and
     * must never be passed here.
     */
    public function trusts(mixed $peer): bool
    {
        if ($this->entries === [] || !is_string($peer)) {
            return false;
        }

        $peerBinary = @inet_pton(self::normalize($peer));
        if (!is_string($peerBinary)) {
            return false;
        }

        foreach ($this->entries as $entry) {
            if (self::matches($peerBinary, $entry)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match one packed peer address against one configured entry.
     */
    private static function matches(string $peerBinary, string $entry): bool
    {
        if (!str_contains($entry, '/')) {
            $entryBinary = @inet_pton($entry);

            return is_string($entryBinary) && $entryBinary === $peerBinary;
        }

        [$network, $prefix] = explode('/', $entry, self::CIDR_PART_LIMIT);
        if ($prefix === '' || !ctype_digit($prefix)) {
            return false;
        }

        $networkBinary = @inet_pton(trim($network));
        if (!is_string($networkBinary) || strlen($networkBinary) !== strlen($peerBinary)) {
            return false;
        }

        $bits = (int) $prefix;
        if ($bits > strlen($networkBinary) * self::BITS_PER_BYTE) {
            return false;
        }

        return self::sharesPrefix($peerBinary, $networkBinary, $bits);
    }

    /**
     * Compare the leading $bits bits of two equal-length packed addresses.
     */
    private static function sharesPrefix(string $left, string $right, int $bits): bool
    {
        $wholeBytes = intdiv($bits, self::BITS_PER_BYTE);
        if ($wholeBytes > 0 && substr($left, 0, $wholeBytes) !== substr($right, 0, $wholeBytes)) {
            return false;
        }

        $remainingBits = $bits % self::BITS_PER_BYTE;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = self::BYTE_MASK << (self::BITS_PER_BYTE - $remainingBits) & self::BYTE_MASK;

        return (ord($left[$wholeBytes]) & $mask) === (ord($right[$wholeBytes]) & $mask);
    }

    /**
     * Trim surrounding whitespace and the brackets used to wrap IPv6 literals.
     */
    private static function normalize(string $value): string
    {
        $value = trim($value);
        if (strlen($value) > 1 && $value[0] === '[') {
            $closing = strpos($value, ']');
            if ($closing !== false) {
                return trim(substr($value, 1, $closing - 1));
            }
        }

        return $value;
    }
}
