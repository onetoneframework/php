<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Service;

use Clover\Classes\Scheduler\Scheduler;
use Clover\Support\ServiceProvider;
use DateTimeZone;

/**
 * Scheduler Service Provider
 *
 * Registers a shared {@see Scheduler} instance in the application container.
 *
 * The provider stays transport-agnostic: it only wires up the scheduler
 * object so callers (CLI commands, HTTP endpoints, worker loops) can fetch
 * it from the container and register or run tasks.
 */
class SchedulerServiceProvider extends ServiceProvider
{
	/**
	 * Register the Scheduler service as a singleton.
	 *
	 * @return void
	 */
	public function register(): void
	{
		$this->app->singleton(Scheduler::class, function (): Scheduler {
			return new Scheduler(self::resolveTimezone());
		});

		$this->app->bind('scheduler', Scheduler::class);
	}

	/**
	 * Resolve the default scheduler timezone from the environment.
	 *
	 * Falls back to UTC when no timezone is configured to avoid
	 * silently inheriting the host's date.timezone setting.
	 *
	 * @return DateTimeZone
	 */
	private static function resolveTimezone(): DateTimeZone
	{
		$configured = $_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE');
		if (is_string($configured) && trim($configured) !== '') {
			try {
				return new DateTimeZone(trim($configured));
			} catch (\Throwable) {
				// Fall through to UTC when the configured zone is invalid.
			}
		}

		return new DateTimeZone('UTC');
	}
}
