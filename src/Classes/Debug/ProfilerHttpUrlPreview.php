<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use function strlen;
use function substr;
use function trim;

/**
 * Normalizes outbound HTTP URLs recorded on profiler spans.
 * Isolated from HTTP client code so transport layers stay free of display limits.
 */
final class ProfilerHttpUrlPreview
{
	/**
	 * Soft cap for extremely long URLs (tracking query strings, signed URLs, etc.).
	 */
	public const DEFAULT_MAX_LENGTH = 8192;

	public static function forTimeline(string $url): string
	{
		$trimmed = trim($url);
		if (strlen($trimmed) <= self::DEFAULT_MAX_LENGTH) {
			return $trimmed;
		}

		return substr($trimmed, 0, self::DEFAULT_MAX_LENGTH - 3) . '...';
	}
}
