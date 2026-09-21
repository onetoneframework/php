<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputArgument;
use Clover\Classes\CLI\InputOption;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\Database\Migration\MigrationRunner;
use Clover\Classes\System\Output;
use Clover\Implement\CommandInterface;

/**
 * Manage database migrations.
 */
final class DatabaseMigrateCommand implements CommandInterface
{
	private const ACTION_MIGRATE = 'migrate';
	private const ACTION_RESET = 'reset';
	private const ACTION_ROLLBACK = 'rollback';
	private const ACTION_STATUS = 'status';
	private const DEFAULT_MIGRATIONS_PATH = 'App/Database/Migrations';
	private const DEFAULT_MIGRATIONS_TABLE = 'migrations';
	private const SUPPORTED_ACTIONS = [
		self::ACTION_MIGRATE,
		self::ACTION_RESET,
		self::ACTION_ROLLBACK,
		self::ACTION_STATUS,
	];

	/** @var array<int, InputArgument> */
	public array $arguments = [];

	/** @var array<int, InputOption> */
	public array $options = [];

	public function getName(): string
	{
		return 'database:migrate';
	}

	public function getDescription(): string
	{
		return 'Manage database migrations';
	}

	public function configure(): void
	{
		$this->options[] = new InputOption('path', 'Migrations directory', self::DEFAULT_MIGRATIONS_PATH);
		$this->options[] = new InputOption('table', 'Migrations tracking table name', self::DEFAULT_MIGRATIONS_TABLE);
		$this->options[] = new InputOption('pretend', 'Inspect the action without changing the database', false);
		$this->options[] = new InputOption('action', 'Action: migrate, rollback, reset, or status', self::ACTION_MIGRATE);
		$this->options[] = new InputOption('steps', 'Number of recent migrations to roll back', MigrationRunner::DEFAULT_ROLLBACK_STEPS);
	}

	public function run(Input $input): bool
	{
		$pathOption = $input->getOption('path') ?? self::DEFAULT_MIGRATIONS_PATH;
		$tableOption = $input->getOption('table') ?? self::DEFAULT_MIGRATIONS_TABLE;
		$actionOption = $input->getOption('action') ?? self::ACTION_MIGRATE;
		$stepsOption = $input->getOption('steps') ?? MigrationRunner::DEFAULT_ROLLBACK_STEPS;
		if (!is_string($pathOption) || !is_string($tableOption) || !is_string($actionOption)) {
			Output::printLine('Migration path, table, and action must be strings.');
			return false;
		}

		if (is_int($stepsOption)) {
			$steps = $stepsOption;
		} elseif (is_string($stepsOption) && ctype_digit($stepsOption)) {
			$steps = (int) $stepsOption;
		} else {
			Output::printLine('Migration steps must be a positive integer.');
			return false;
		}

		if ($steps < MigrationRunner::DEFAULT_ROLLBACK_STEPS) {
			Output::printLine('Migration steps must be a positive integer.');
			return false;
		}

		$pretend = $input->getOption('pretend') === true;
		$action = strtolower($actionOption);
		if (!in_array($action, self::SUPPORTED_ACTIONS, true)) {
			Output::printLine('Unknown migration action: ' . $action);
			return false;
		}

		$resolvedPath = $this->resolvePath($pathOption);

		$database = new PHPDataObject();
		$database->setHostName($_ENV['MYSQL_HOST']);
		$database->setUsername($_ENV['MYSQL_USERNAME']);
		$database->setPassword($_ENV['MYSQL_PASSWORD']);
		$database->setDatabase($_ENV['MYSQL_DATABASE']);
		$database->createConnection();

		$runner = new MigrationRunner($database, $tableOption);
		if ($action === self::ACTION_MIGRATE) {
			Output::printLine('Running database migrations...');
			$runner->migrate($resolvedPath, $pretend);
			Output::printLine('Database migrations completed.');
			return true;
		}

		if ($action === self::ACTION_ROLLBACK) {
			$rollbackCount = $runner->rollback($resolvedPath, $steps, $pretend);
			Output::printLine('Selected migrations for rollback: ' . $rollbackCount);
			return true;
		}

		if ($action === self::ACTION_RESET) {
			$rollbackCount = $runner->reset($resolvedPath, $pretend);
			Output::printLine('Selected migrations for reset: ' . $rollbackCount);
			return true;
		}

		if ($action === self::ACTION_STATUS) {
			$statuses = $runner->status($resolvedPath);
			if ($statuses === []) {
				Output::printLine('No migrations found.');
				return true;
			}

			foreach ($statuses as $status) {
				$state = $status->isApplied()
					? 'applied (batch ' . $status->getBatch() . ')'
					: 'pending';
				if (!$status->isAvailable()) {
					$state .= ', file missing';
				}

				Output::printLine($status->getIdentifier() . ': ' . $state);
			}

			return true;
		}

		Output::printLine('Unknown migration action: ' . $action);
		return false;
	}

	private function resolvePath(string $path): string
	{
		if (preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path) === 1 || str_starts_with($path, DIRECTORY_SEPARATOR)) {
			return $path;
		}

		$normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
		$basePath = defined('BASE_PATH') ? BASE_PATH : '';
		if ($basePath === '') {
			return $normalizedPath;
		}

		return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalizedPath;
	}
}
