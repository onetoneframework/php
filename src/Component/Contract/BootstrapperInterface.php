<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Contract;

use Clover\Component\Foundation\Application;

/**
 * Bootstrapper Interface
 *
 * One step of application start-up. The kernel owns an ordered list of these and runs each one
 * exactly once, before the first request is handled.
 *
 * A bootstrapper prepares the application; it never handles a request. Anything that needs to see
 * the request is middleware instead.
 */
interface BootstrapperInterface
{
	/**
	 * Perform this start-up step.
	 *
	 * @param Application $application The application being started.
	 *
	 * @return void
	 */
	public function bootstrap(Application $application): void;
}
