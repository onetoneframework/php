<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use function preg_replace;
use function strlen;
use function substr;
use function trim;

/**
 * Normalizes SQL text for profiler timelines and database lifecycle payloads.
 * Kept separate from database drivers to avoid embedding profiler limits in persistence code.
 */
final class ProfilerSqlPreview
{
	/**
	 * Soft cap keeps JSON snapshots bounded while still showing long queries in full for typical use.
	 */
	public const DEFAULT_MAX_LENGTH = 32768;

	/**
	 * Collapse whitespace and optionally trim to {@see DEFAULT_MAX_LENGTH}.
	 */
	public static function forTimeline(string $sql): string
	{
		$normalized = trim((string) preg_replace('/\s+/', ' ', $sql));
		if (strlen($normalized) <= self::DEFAULT_MAX_LENGTH) {
			return $normalized;
		}

		return substr($normalized, 0, self::DEFAULT_MAX_LENGTH - 3) . '...';
	}
}
