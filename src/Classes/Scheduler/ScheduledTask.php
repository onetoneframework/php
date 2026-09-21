<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Scheduler;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

/**
 * Scheduled Task
 *
 * Binds a callable to a cron expression and records execution metadata.
 *
 * Each task owns its own overlap guard (in-process) and a pluggable
 * timezone so callers can anchor schedules to business-local time
 * without touching the global date.timezone setting.
 */
final class ScheduledTask
{
	/**
	 * @var string Logical task name used for lookup and logging.
	 */
	private string $name;

	/**
	 * @var string Human-readable description for the `schedule:list` command.
	 */
	private string $description;

	/**
	 * Parsed cron expression.
	 */
	private CronExpression $cron;

	/**
	 * Callable to invoke when the task fires.
	 *
	 * @var Closure
	 */
	private Closure $callback;

	/**
	 * @var DateTimeZone Timezone used to evaluate the cron expression.
	 */
	private DateTimeZone $timezone;

	/**
	 * @var ?DateTimeImmutable Last time this task ran, or null if it has never run.
	 */
	private ?DateTimeImmutable $lastRunAt = null;

	/**
	 * @var string Last outcome of the task.
	 *
	 * One of: 'idle', 'success', 'failed'.
	 */
	private string $lastStatus = 'idle';

	/**
	 * @var bool When true, skip execution if the previous run for this task is still
	 * in-flight within the same process.
	 */
	private bool $preventOverlap = true;

	/**
	 * @var bool Whether the task is currently running.
	 */
	private bool $running = false;

	/**
	 * Create a new scheduled task.
	 *
	 * @param string $name Unique task name.
	 * @param CronExpression $cron Cron expression governing execution.
	 * @param callable $callback Task body (any PHP callable).
	 * @param DateTimeZone|null $timezone Timezone used to evaluate the cron expression.
	 * @param string $description Optional human-readable description.
	 */
	public function __construct(string $name, CronExpression $cron, callable $callback, ?DateTimeZone $timezone = null, string $description = '')
	{
		$this->name = $name;
		$this->cron = $cron;
		$this->callback = Closure::fromCallable($callback);
		$this->timezone = $timezone ?? new DateTimeZone('UTC');
		$this->description = $description;
	}

	/**
	 * Return the task's logical name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Return the task's description.
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * Return the parsed cron expression.
	 *
	 * @return CronExpression
	 */
	public function getCron(): CronExpression
	{
		return $this->cron;
	}

	/**
	 * Return the task timezone used when evaluating the cron expression.
	 *
	 * @return DateTimeZone
	 */
	public function getTimezone(): DateTimeZone
	{
		return $this->timezone;
	}

	/**
	 * Return the last time the task actually ran.
	 *
	 * @return DateTimeImmutable|null
	 */
	public function getLastRunAt(): ?DateTimeImmutable
	{
		return $this->lastRunAt;
	}

	/**
	 * Return the last execution outcome: 'idle', 'success', or 'failed'.
	 *
	 * @return string
	 */
	public function getLastStatus(): string
	{
		return $this->lastStatus;
	}

	/**
	 * Allow or disallow overlapping executions in the same process.
	 *
	 * @param bool $preventOverlap True to block overlapping runs.
	 *
	 * @return self
	 */
	public function preventOverlapping(bool $preventOverlap = true): self
	{
		$this->preventOverlap = $preventOverlap;

		return $this;
	}

	/**
	 * Whether the task is currently executing inside this process.
	 *
	 * @return bool
	 */
	public function isRunning(): bool
	{
		return $this->running;
	}

	/**
	 * Whether the given instant should trigger this task.
	 *
	 * @param DateTimeImmutable $now Current instant.
	 *
	 * @return bool
	 */
	public function isDue(DateTimeImmutable $now): bool
	{
		return $this->cron->isDue($now, $this->timezone);
	}

	/**
	 * Execute the task callback.
	 *
	 * The callback may return any value; callers typically ignore it.
	 * Exceptions bubble up after the task is marked as failed so the
	 * scheduler can log them in a single place.
	 *
	 * @param DateTimeImmutable $now Current instant (recorded as last run).
	 *
	 * @return mixed Callback return value.
	 */
	public function run(DateTimeImmutable $now): mixed
	{
		if ($this->preventOverlap && $this->running) {
			$this->lastStatus = 'skipped';

			return null;
		}

		$this->running = true;
		try {
			$result = ($this->callback)();
			$this->lastStatus = 'success';

			return $result;
		} catch (Throwable $throwable) {
			$this->lastStatus = 'failed';

			throw $throwable;
		} finally {
			$this->running = false;
			$this->lastRunAt = $now;
		}
	}
}
