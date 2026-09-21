<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Scheduler;

use Clover\Classes\Logging\Logger;
use Clover\Enumeration\LoggingLevel;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Throwable;
use function array_key_exists;
use function array_values;
use function sprintf;

/**
 * Scheduler
 *
 * Registry of {@see ScheduledTask} instances with a single entry-point
 * ({@see Scheduler::run()}) that executes all tasks due at a given instant.
 *
 * The scheduler is transport-agnostic: it does not care whether it is
 * triggered by the HTTP runtime, a CLI command, or an external supervisor
 * (e.g. systemd timer, Kubernetes CronJob). This keeps scheduling policy
 * decoupled from the underlying cron wake-up mechanism.
 */
final class Scheduler
{
	/**
	 * Registered tasks keyed by name.
	 *
	 * @var array<string, ScheduledTask>
	 */
	private array $tasks = [];

	/**
	 * @var DateTimeZone Default timezone used when a task does not specify one.
	 */
	private DateTimeZone $defaultTimezone;

	/**
	 * @var ?Logger Framework logger used to report task failures (optional).
	 */
	private ?Logger $logger;

	/**
	 * Create a new scheduler instance.
	 *
	 * @param DateTimeZone|null $defaultTimezone Default timezone (UTC when null).
	 * @param Logger|null $logger Framework logger used to record task failures.
	 */
	public function __construct(?DateTimeZone $defaultTimezone = null, ?Logger $logger = null)
	{
		$this->defaultTimezone = $defaultTimezone ?? new DateTimeZone('UTC');
		$this->logger = $logger;
	}

	/**
	 * Register a closure/callable against a cron expression.
	 *
	 * @param string $name Unique task name.
	 * @param callable $callback Task body.
	 * @param string|CronExpression $cron 5-field cron expression or pre-parsed object.
	 * @param string $description Optional human-readable description.
	 * @param DateTimeZone|null $timezone Optional per-task timezone override.
	 *
	 * @return ScheduledTask Registered task (for fluent configuration).
	 *
	 * @throws InvalidArgumentException When a task with the same name already exists.
	 */
	public function call(string $name, callable $callback, string|CronExpression $cron, string $description = '', ?DateTimeZone $timezone = null): ScheduledTask
	{
		if ($name === '') {
			throw new InvalidArgumentException('Scheduled task name must not be empty.');
		}

		if (array_key_exists($name, $this->tasks)) {
			throw new InvalidArgumentException(sprintf('Scheduled task "%s" is already registered.', $name));
		}

		$cronExpression = $cron instanceof CronExpression ? $cron : new CronExpression($cron);

		$task = new ScheduledTask($name, $cronExpression, $callback, $timezone ?? $this->defaultTimezone, $description);

		$this->tasks[$name] = $task;

		return $task;
	}

	/**
	 * Return all registered tasks in registration order.
	 *
	 * @return ScheduledTask[]
	 */
	public function all(): array
	{
		return array_values($this->tasks);
	}

	/**
	 * Return the tasks due at the given instant.
	 *
	 * @param DateTimeImmutable $now Current instant.
	 *
	 * @return ScheduledTask[]
	 */
	public function dueTasks(DateTimeImmutable $now): array
	{
		$due = [];
		foreach ($this->tasks as $task) {
			if ($task->isDue($now)) {
				$due[] = $task;
			}
		}

		return $due;
	}

	/**
	 * Check whether a task with the given name is registered.
	 *
	 * @param string $name Task name.
	 *
	 * @return bool
	 */
	public function has(string $name): bool
	{
		return array_key_exists($name, $this->tasks);
	}

	/**
	 * Retrieve a registered task by name.
	 *
	 * @param string $name Task name.
	 *
	 * @return ScheduledTask
	 *
	 * @throws InvalidArgumentException When the task is not registered.
	 */
	public function get(string $name): ScheduledTask
	{
		if (!array_key_exists($name, $this->tasks)) {
			throw new InvalidArgumentException(sprintf('Scheduled task "%s" is not registered.', $name));
		}

		return $this->tasks[$name];
	}

	/**
	 * Execute all tasks that are due at the given instant.
	 *
	 * Exceptions thrown by a single task are logged and do not prevent
	 * the remaining due tasks from running.
	 *
	 * @param DateTimeImmutable|null $now Current instant (defaults to now in UTC).
	 *
	 * @return array<int, array{name: string, status: string, error?: string}>
	 *  One result entry per executed task.
	 */
	public function run(?DateTimeImmutable $now = null): array
	{
		$instant = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
		$results = [];

		foreach ($this->dueTasks($instant) as $task) {
			$entry = ['name' => $task->getName(), 'status' => 'success'];

			try {
				$task->run($instant);
				$entry['status'] = $task->getLastStatus();
			} catch (Throwable $throwable) {
				$entry['status'] = 'failed';
				$entry['error'] = $throwable->getMessage();

				$this->logFailure($task->getName(), $throwable);
			}

			$results[] = $entry;
		}

		return $results;
	}

	/**
	 * Execute exactly one task by name, bypassing cron evaluation.
	 *
	 * Useful for manual reruns from `schedule:run --task=<name>`.
	 *
	 * @param string $name Task name.
	 * @param DateTimeImmutable|null $now Current instant (defaults to now in UTC).
	 *
	 * @return array{name: string, status: string, error?: string}
	 */
	public function runTask(string $name, ?DateTimeImmutable $now = null): array
	{
		$task = $this->get($name);
		$instant = $now ?? new DateTimeImmutable('now', new DateTimeZone('UTC'));
		$entry = ['name' => $task->getName(), 'status' => 'success'];

		try {
			$task->run($instant);
			$entry['status'] = $task->getLastStatus();
		} catch (Throwable $throwable) {
			$entry['status'] = 'failed';
			$entry['error'] = $throwable->getMessage();

			$this->logFailure($task->getName(), $throwable);
		}

		return $entry;
	}

	/**
	 * Swap the logger after construction (e.g. after the DI container is ready).
	 *
	 * @param Logger|null $logger Framework logger, or null to disable logging.
	 *
	 * @return void
	 */
	public function setLogger(?Logger $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * Emit a task-failure log entry, if a logger is configured.
	 *
	 * @param string $taskName Logical name of the failed task.
	 * @param Throwable $throwable Exception raised by the task callback.
	 *
	 * @return void
	 */
	private function logFailure(string $taskName, Throwable $throwable): void
	{
		if ($this->logger === null) {
			return;
		}

		$this->logger->write(
			sprintf('Scheduled task "%s" failed: %s', $taskName, $throwable->getMessage()),
			LoggingLevel::ERROR,
			'scheduler',
			[
				'task' => $taskName,
				'exception_class' => $throwable::class,
				'exception_file' => $throwable->getFile(),
				'exception_line' => $throwable->getLine(),
			]
		);
	}

	/**
	 * Return the default timezone used for tasks without an explicit override.
	 *
	 * @return DateTimeZone
	 */
	public function getDefaultTimezone(): DateTimeZone
	{
		return $this->defaultTimezone;
	}
}
