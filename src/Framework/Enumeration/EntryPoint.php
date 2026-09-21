<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Enumeration;

use InvalidArgumentException;

use function array_column;
use function getenv;
use function implode;
use function is_string;
use function sprintf;
use function strtolower;
use function trim;

/**
 * Entry Point Enumeration
 *
 * Which kernel stack `root/index.php` builds for a request.
 *
 * The framework ships two of them. `RUNTIME` is the default and the one the application is
 * written against: `Clover\Framework\Component\Runtime` -> `Mapper::matchRunner()` ->
 * `Framework\Component\HttpKernel` or `CliKernel`. `APPLICATION` is the provider-based stack:
 * `Clover\Component\Foundation\Application` -> `Component\Kernel\HttpKernel`, with services
 * registered by `src/Service/*ServiceProvider.php` instead of `root/App/Configure/dependencies.php`.
 *
 * Selected by the `APP_ENTRY_POINT` environment variable. An unset or empty value means
 * {@see self::default()}; any other unrecognised value is an error rather than a silent fallback,
 * because a typo that quietly boots the wrong kernel is worse than a refusal at startup.
 */
enum EntryPoint: string
{
	case RUNTIME = 'runtime';
	case APPLICATION = 'application';

	/**
	 * Environment variable that selects the entry point.
	 */
	public const ENVIRONMENT_KEY = 'APP_ENTRY_POINT';

	/**
	 * The entry point used when nothing selects one.
	 *
	 * @return self
	 */
	public static function default(): self
	{
		return self::RUNTIME;
	}

	/**
	 * Resolve an entry point from its configured name.
	 *
	 * @param string $value Name as written in configuration, case-insensitive.
	 *
	 * @throws InvalidArgumentException When the name matches no entry point.
	 *
	 * @return self
	 */
	public static function fromString(string $value): self
	{
		$normalized = strtolower(trim($value));
		$entryPoint = self::tryFrom($normalized);

		if ($entryPoint instanceof self) {
			return $entryPoint;
		}

		throw new InvalidArgumentException(sprintf(
			'Unknown %s value `%s`; expected one of: %s.',
			self::ENVIRONMENT_KEY,
			$value,
			implode(', ', array_column(self::cases(), 'value'))
		));
	}

	/**
	 * Resolve the entry point selected by the environment.
	 *
	 * Requires the environment files to be loaded already - see
	 * {@see \Clover\Framework\Component\DotenvLoader::load()}. Dotenv populates `$_ENV` and not
	 * `putenv()`, so `$_ENV` is read first and `getenv()` second, which lets a real OS-level
	 * variable still select the entry point where no `.env` is present.
	 *
	 * @throws InvalidArgumentException When `APP_ENTRY_POINT` names no entry point.
	 *
	 * @return self
	 */
	public static function fromEnvironment(): self
	{
		$configured = $_ENV[self::ENVIRONMENT_KEY] ?? getenv(self::ENVIRONMENT_KEY);

		if (!is_string($configured) || trim($configured) === '') {
			return self::default();
		}

		return self::fromString($configured);
	}
}
