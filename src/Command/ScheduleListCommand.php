<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\Component\TableView;
use Clover\Classes\CLI\Input;
use Clover\Classes\Scheduler\Scheduler;
use Clover\Classes\System\Output;
use Clover\Framework\Context\ApplicationContext;
use Clover\Implement\CommandInterface;

/**
 * Schedule List Command
 *
 * Renders a table of every registered scheduled task with its cron
 * expression, timezone, description, and last recorded outcome.
 */
final class ScheduleListCommand implements CommandInterface
{
	/**
	 * @var array<int, \Clover\Classes\CLI\InputArgument>
	 */
	public array $arguments = [];

	/**
	 * @var array<int, \Clover\Classes\CLI\InputOption>
	 */
	public array $options = [];

	/**
	 * Return the registered CLI name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return 'schedule:list';
	}

	/**
	 * Return the short description shown by `php_console list`.
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return 'List all registered scheduled tasks';
	}

	/**
	 * No command-level configuration is required.
	 *
	 * @return void
	 */
	public function configure(): void
	{
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

		$tasks = $scheduler->all();
		if ($tasks === []) {
			Output::printLine('No scheduled tasks are registered. Register them in App/Configure/schedule.php.');

			return true;
		}

		$rows = [];
		foreach ($tasks as $task) {
			$lastRunAt = $task->getLastRunAt();

			$rows[] = [
				'name' => $task->getName(),
				'cron' => $task->getCron()->getExpression(),
				'timezone' => $task->getTimezone()->getName(),
				'description' => $task->getDescription(),
				'lastRunAt' => $lastRunAt === null ? '-' : $lastRunAt->format(DATE_ATOM),
				'lastStatus' => $task->getLastStatus(),
			];
		}

		$table = new TableView();
		$table->setHeaders(['Name', 'Cron', 'Timezone', 'Description', 'Last Run', 'Status']);
		$table->setRows($rows);
		$table->setOrderKeys(['name', 'cron', 'timezone', 'description', 'lastRunAt', 'lastStatus']);
		$table->render();

		return true;
	}

	/**
	 * Fetch the Scheduler from the application container, returning null on failure.
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
}
