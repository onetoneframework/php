<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputOption;
use Clover\Classes\Scheduler\Scheduler;
use Clover\Classes\System\Output;
use Clover\Framework\Context\ApplicationContext;
use Clover\Implement\CommandInterface;

/**
 * Schedule Run Command
 *
 * Executes every scheduled task that is due at the current instant.
 *
 * Example:
 *   php ./php_console schedule:run
 *   php ./php_console schedule:run --task=reports:daily
 *   php ./php_console schedule:run --now=2026-01-01T00:00:00+00:00
 */
final class ScheduleRunCommand implements CommandInterface
{
	/**
	 * @var array<int, InputOption>
	 */
	public array $options = [];

	/**
	 * @var array<int, \Clover\Classes\CLI\InputArgument>
	 */
	public array $arguments = [];

	/**
	 * Return the registered CLI name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'schedule:run';
	}

	/**
	 * Return the short description shown by `php_console list`.
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return 'Run all scheduled tasks that are due now';
	}

	/**
	 * Register CLI options for this command.
	 *
	 * @return void
	 */
	public function configure(): void
	{
		$this->options[] = new InputOption('task', 'Run a single task by name, ignoring cron evaluation', null);
		$this->options[] = new InputOption('now', 'ISO-8601 timestamp used as the evaluation instant (defaults to wall clock)', null);
	}

	/**
	 * Execute the command.
	 *
	 * @param Input $input Parsed CLI input.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function run(Input $input): bool
	{
		$scheduler = $this->resolveScheduler();
		if ($scheduler === null) {
			Output::printLine('Scheduler service is not registered. Check App/Configure/dependencies.php.');

			return false;
		}

		$now = $this->resolveInstant($input);

		$taskName = $input->getOption('task');
		if (is_string($taskName) && $taskName !== '') {
			if (!$scheduler->has($taskName)) {
				Output::printLine(sprintf('Unknown scheduled task: %s', $taskName));

				return false;
			}

			$result = $scheduler->runTask($taskName, $now);
			$this->renderResults([$result]);

			return $result['status'] !== 'failed';
		}

		$results = $scheduler->run($now);
		if ($results === []) {
			Output::printLine('No scheduled tasks are due at ' . $now->format(DATE_ATOM) . '.');

			return true;
		}

		$this->renderResults($results);

		foreach ($results as $result) {
			if ($result['status'] === 'failed') {
				return false;
			}
		}

		return true;
	}

	/**
	 * Fetch the Scheduler from the application container, returning null on failure.
	 *
	 * Wrapping the lookup in a guard keeps the command usable even when the
	 * container is misconfigured, preserving a readable error message.
	 *
	 * @return Scheduler|null
	 */
	private function resolveScheduler(): ?Scheduler
	{
		try {
			$container = ApplicationContext::getContainer();
		} catch (\Throwable) {
			return null;
		}

		if (!$container->has(Scheduler::class)) {
			return null;
		}

		$service = $container->get(Scheduler::class);

		return $service instanceof Scheduler ? $service : null;
	}

	/**
	 * Resolve the evaluation instant from --now or the wall clock.
	 *
	 * @param Input $input Parsed CLI input.
	 *
	 * @return \DateTimeImmutable
	 */
	private function resolveInstant(Input $input): \DateTimeImmutable
	{
		$raw = $input->getOption('now');
		if (is_string($raw) && $raw !== '') {
			try {
				return new \DateTimeImmutable($raw);
			} catch (\Throwable) {
				Output::printLine(sprintf('Invalid --now value "%s"; using wall clock instead.', $raw));
			}
		}

		return new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
	}

	/**
	 * Render a human-readable summary of task execution results.
	 *
	 * @param array<int, array{name: string, status: string, error?: string}> $results Scheduler run results.
	 *
	 * @return void
	 */
	private function renderResults(array $results): void
	{
		foreach ($results as $result) {
			$line = sprintf('[%s] %s', strtoupper($result['status']), $result['name']);
			if (isset($result['error'])) {
				$line .= ' - ' . $result['error'];
			}
			Output::printLine($line);
		}
	}
}
