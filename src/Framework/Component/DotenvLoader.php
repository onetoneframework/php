<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\BaseClass;
use Dotenv\Dotenv;

use function file_exists;
use function getenv;
use function is_readable;

/**
 * Dotenv Loader
 *
 * Loads `.env` and `.env.local` exactly once per process.
 *
 * The entry point needs the environment before it can decide which kernel to build, and
 * `Runtime` needs it again once it starts. Both call this loader; the second call is a no-op,
 * so the files are read once and one place owns the rule that `.env.local` overrides `.env`.
 *
 * Reporting a missing `.env` is deliberately left to the caller: `Runtime` raises it after its
 * own error handler is installed, so the failure is still rendered by the framework rather than
 * as a raw fatal.
 */
class DotenvLoader extends BaseClass
{
	/**
	 * @var bool Whether a load attempt has already run in this process.
	 */
	private static bool $attempted = false;

	/**
	 * @var bool Whether the last load attempt produced at least one entry from `.env`.
	 */
	private static bool $succeeded = false;

	/**
	 * Load `.env`, then `.env.local` unless the process is running inside Docker.
	 *
	 * Safe to call more than once; only the first call reads the filesystem.
	 *
	 * @param string $basePath Directory holding the environment files.
	 *
	 * @return bool True when `.env` yielded at least one entry.
	 */
	public static function load(string $basePath): bool
	{
		if (self::$attempted) {
			return self::$succeeded;
		}

		self::$attempted = true;

		$dotenv = Dotenv::createImmutable($basePath);
		/**
		 * @var Dotenv $dotenv
		 */
		$dotenv = parent::setBaseProxy($dotenv);
		$loadedFiles = $dotenv->safeLoad();

		self::$succeeded = !empty($loadedFiles);

		if (!self::$succeeded) {
			return false;
		}

		if (self::shouldSkipEnvLocal()) {
			return true;
		}

		// Local overrides must win over `.env` for host CLI runs.
		$local = Dotenv::createMutable($basePath, '.env.local');
		/**
		 * @var Dotenv $local
		 */
		$local = parent::setBaseProxy($local);
		$local->safeLoad();

		return true;
	}

	/**
	 * Forget that a load has happened, so the next call reads the files again.
	 *
	 * Exists for tests that need a clean process-level state; the loaded variables themselves
	 * are owned by the Dotenv repository and are not unset here.
	 *
	 * @return void
	 */
	public static function reset(): void
	{
		self::$attempted = false;
		self::$succeeded = false;
	}

	/**
	 * Whether `.env.local` should be skipped.
	 *
	 * Container images bake their configuration in through real environment variables, so a
	 * developer's `.env.local` must not leak into them.
	 *
	 * @return bool
	 */
	private static function shouldSkipEnvLocal(): bool
	{
		$docker = getenv('RUNNING_IN_DOCKER');

		if ($docker) {
			return true;
		}

		return file_exists('/.dockerenv') && is_readable('/.dockerenv');
	}
}
