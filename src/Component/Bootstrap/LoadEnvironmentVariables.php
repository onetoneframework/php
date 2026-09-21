<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Bootstrap;

use Clover\Component\Contract\BootstrapperInterface;
use Clover\Component\Foundation\Application;
use Clover\Framework\Component\DotenvLoader;
use RuntimeException;

use function sprintf;

/**
 * Load Environment Variables
 *
 * Reads `.env` / `.env.local` into `$_ENV`.
 *
 * `root/index.php` has normally already done this - it has to, because `APP_ENTRY_POINT` decides
 * which kernel runs - so in the usual case this step confirms rather than performs the load. It
 * matters when an `Application` is built directly, by a test or a worker, with no entry point
 * having run first.
 */
final class LoadEnvironmentVariables implements BootstrapperInterface
{
	/**
	 * Load the environment files for this application.
	 *
	 * Two different situations, deliberately told apart rather than both treated as failure:
	 *
	 * - The application was built with no base path (`new Application()`), so there is no location
	 *   to load from. Nothing is read and nothing is raised - the caller chose an application with
	 *   no filesystem behind it, which a test harness and a worker both legitimately do.
	 * - A location *is* configured and holds no readable `.env`. That is a misconfigured
	 *   application and it is raised, because everything after this step reads configuration and
	 *   would otherwise run against silent defaults.
	 *
	 * @param Application $application The application being started.
	 *
	 * @throws RuntimeException When a configured environment directory holds no readable `.env`.
	 *
	 * @return void
	 */
	public function bootstrap(Application $application): void
	{
		$environmentPath = $application->getEnvironmentPath();

		if ($environmentPath === '') {
			return;
		}

		if (!DotenvLoader::load($environmentPath)) {
			throw new RuntimeException(sprintf(
				'Dotenv is failed to load environment file in `%s` directory',
				$environmentPath
			));
		}
	}
}
