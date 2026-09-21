<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 *
 * Service providers registered at start-up under the `application` entry point
 * (`APP_ENTRY_POINT=application`).
 *
 * `RegisterProviders` runs `register()` on each of these in order, then `BootProviders` runs every
 * `boot()`. Order matters for `register()` - a later provider overwrites an earlier binding - and
 * not for `boot()`, which sees the finished container either way.
 *
 * The default `runtime` entry point never reads this file; it registers services through
 * `dependencies.php` instead.
 */

use Clover\Service\AIOrchestrationServiceProvider;
use Clover\Service\RoutingServiceProvider;
use Clover\Service\SchedulerServiceProvider;

return [
	RoutingServiceProvider::class,
	SchedulerServiceProvider::class,
	AIOrchestrationServiceProvider::class,
];
