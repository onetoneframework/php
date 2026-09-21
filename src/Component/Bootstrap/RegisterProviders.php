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

/**
 * Register Providers
 *
 * Runs `register()` on every service provider the application declares.
 *
 * Registration only binds things into the container; it must not resolve them, because a provider
 * registered later may still replace what an earlier one bound. Work that needs the finished
 * container belongs in `boot()`, which {@see BootProviders} runs afterwards.
 */
final class RegisterProviders implements BootstrapperInterface
{
	/**
	 * Register every declared provider.
	 *
	 * @param Application $application The application being started.
	 *
	 * @return void
	 */
	public function bootstrap(Application $application): void
	{
		foreach ($application->getDeclaredProviders() as $provider) {
			$application->register($provider);
		}
	}
}
