<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Livewire;

use function hash_equals;
use function hash_hmac;
use function is_string;
use function json_encode;
use function trim;
use function is_array;
use function defined;

/**
 * HMAC helper for Livewire component snapshots.
 *
 * Every snapshot sent to the client is signed with {@see sign()}; any
 * snapshot that comes back is verified with {@see verify()}. This is
 * the only guard that prevents the client from tampering with
 * unbound server-side state (for example, a private property that
 * happens to be public in PHP).
 *
 * The secret is read from `$_ENV['APP_KEY']` / `LIVEWIRE_KEY`. When
 * neither is set, a deterministic but per-installation fallback is
 * derived from `BASE_PATH` so local development still produces stable
 * checksums without leaking into tests.
 */
final class Checksum
{
    /**
     * Compute the HMAC checksum for a component snapshot.
     *
     * @param array{name: string, id: string, data: array<string, mixed>} $snapshot
     */
    public static function sign(array $snapshot): string
    {
        $payload = self::canonicalise($snapshot);
        return hash_hmac('sha256', $payload, self::secret());
    }

    /**
     * Verify that `$checksum` matches the snapshot using a
     * constant-time comparison.
     *
     * @param array{name: string, id: string, data: array<string, mixed>} $snapshot
     */
    public static function verify(array $snapshot, string $checksum): bool
    {
        if ($checksum === '') {
            return false;
        }

        return hash_equals(self::sign($snapshot), $checksum);
    }

    /** 
     * Canonicalise the snapshot into a JSON string with sorted keys. This ensures that the same snapshot always produces the same string, regardless of how PHP's associative arrays are ordered.
     * 
     * @param array{name: string, id: string, data: array<string, mixed>} $snapshot 
     * @return string
     **/
    private static function canonicalise(array $snapshot): string
    {
        // Sort keys so signing is independent of PHP associative order.
        $data = $snapshot['data'] ?? [];
        if (is_array($data)) {
            ksort($data);
        }
        $payload = [
            'name' => (string) ($snapshot['name'] ?? ''),
            'id' => (string) ($snapshot['id'] ?? ''),
            'data' => $data,
        ];

        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** 
     * Get the secret key for signing. This checks `LIVEWIRE_KEY` and `APP_KEY` in `$_ENV` and `getenv()`, falling back to a deterministic value based on `BASE_PATH`.
     * @return string
     **/
    private static function secret(): string
    {
        foreach (['LIVEWIRE_KEY', 'APP_KEY'] as $envName) {
            $value = $_ENV[$envName] ?? getenv($envName);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        $base = defined('BASE_PATH') ? (string) BASE_PATH : __DIR__;
        return hash('sha256', 'onetone-livewire|' . $base);
    }
}
