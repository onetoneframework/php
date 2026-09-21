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
use Clover\Component\Contract\ExceptionHandlerInterface;
use Clover\Component\Exception\Handler;
use Clover\Component\Foundation\Application;

/**
 * Handle Exceptions
 *
 * Makes sure something is bound to {@see ExceptionHandlerInterface} before any request is handled.
 *
 * An application that wants its own handler binds one before start-up - in `App/Configure/kernel.php`
 * or from a provider registered earlier - and this step leaves that binding alone. Only when
 * nothing is bound does the framework's default take the slot, so a request can never reach the
 * pipeline with no way to render a failure.
 */
final class HandleExceptions implements BootstrapperInterface
{
	/**
	 * Ensure an exception handler is bound.
	 *
	 * @param Application $application The application being started.
	 *
	 * @return void
	 */
	public function bootstrap(Application $application): void
	{
		if ($application->has(ExceptionHandlerInterface::class)) {
			return;
		}

		$application->singleton(ExceptionHandlerInterface::class, new Handler($application));
	}
}
