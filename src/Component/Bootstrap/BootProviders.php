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
 * Boot Providers
 *
 * Runs `boot()` on every registered provider, after every `register()` has finished.
 *
 * This is where a provider may resolve things out of the container, because by now the container
 * holds every binding the application is going to have. Route registration happens here.
 */
final class BootProviders implements BootstrapperInterface
{
	/**
	 * Boot every registered provider.
	 *
	 * @param Application $application The application being started.
	 *
	 * @return void
	 */
	public function bootstrap(Application $application): void
	{
		$application->boot();
	}
}
