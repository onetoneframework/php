<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Scheduler\Scheduler;

/**
 * Task schedule definitions.
 *
 * Loaded by App/Configure/dependencies.php when the Scheduler service is
 * first resolved. Use `$scheduler->call()` to register tasks bound to
 * cron expressions or supported macros (@hourly, @daily, @weekly, ...).
 *
 * Tasks registered here become visible to:
 *   - `php ./php_console schedule:list`
 *   - `php ./php_console schedule:run`
 */
return static function (Scheduler $scheduler, Container $container): void {
	// Example heartbeat: run every minute. Safe to keep in production; it
	// only touches memory. Remove or customize as needed.
	$scheduler->call(
		'system:heartbeat',
		static function (): void {
			// Intentionally a no-op. Replace with a health check,
			// metric push, or queue-depth probe.
		},
		'* * * * *',
		'In-memory heartbeat used to verify the scheduler is alive.'
	);
};
