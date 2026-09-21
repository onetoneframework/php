<?php
declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\Migration;

use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\File\Functions as FileFunctions;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\System\Output;
use InvalidArgumentException;
use PDO;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

/**
 * Runs database migrations.
 */
final class MigrationRunner
{
	public const DEFAULT_ROLLBACK_STEPS = 1;

	private const FIRST_BATCH = 1;
	private const MIGRATION_IDENTIFIER_LENGTH = 255;

	private PHPDataObject $database;
	private string $migrationsTable;

	public function __construct(PHPDataObject $database, string $migrationsTable = 'migrations')
	{
		$this->assertIdentifier($migrationsTable);
		$this->database = $database;
		$this->migrationsTable = $migrationsTable;
	}

	/**
	 * @param string $migrationsDirectory Absolute or repository-relative path.
	 * @param bool $pretend Whether to inspect pending migrations without applying them.
	 */
	public function migrate(string $migrationsDirectory, bool $pretend = false): void
	{
		$migrationsDirectory = rtrim($migrationsDirectory, DIRECTORY_SEPARATOR);
		$this->ensureMigrationsTableExists();

		if (!DirectoryHandler::exists($migrationsDirectory)) {
			Output::printLine("migrations: directory not found: {$migrationsDirectory}");
			return;
		}

		$files = $this->getMigrationFiles($migrationsDirectory);
		if ($files === []) {
			Output::printLine('migrations: no migration files found');
			return;
		}

		$batch = $this->getNextBatchNumber();
		$applied = $this->getAppliedMigrationRecords();

		foreach ($files as $identifier => $file) {
			if (isset($applied[$identifier])) {
				continue;
			}

			$migrationClass = $this->discoverMigrationClassFromFile($file);
			if ($migrationClass === null) {
				throw new RuntimeException("migrations: no Migration subclass found in {$file}");
			}

			if ($pretend) {
				continue;
			}

			$instance = new $migrationClass();
			$instance->up($this->database);
			$this->recordMigrationApplied($identifier, $batch);
		}
	}

	/**
	 * Return migration state, including pending files and missing applied files.
	 *
	 * @return list<MigrationStatus>
	 */
	public function status(string $migrationsDirectory): array
	{
		$migrationsDirectory = rtrim($migrationsDirectory, DIRECTORY_SEPARATOR);
		$this->ensureMigrationsTableExists();

		$files = DirectoryHandler::exists($migrationsDirectory)
			? $this->getMigrationFiles($migrationsDirectory)
			: [];
		$applied = $this->getAppliedMigrationRecords();
		$identifiers = array_unique(array_merge(array_keys($files), array_keys($applied)));
		if (!sort($identifiers)) {
			throw new RuntimeException('migrations: failed to sort migration identifiers');
		}

		$statuses = [];
		foreach ($identifiers as $identifier) {
			$statuses[] = new MigrationStatus(
				$identifier,
				$applied[$identifier] ?? MigrationStatus::PENDING_BATCH,
				isset($files[$identifier])
			);
		}

		return $statuses;
	}

	/**
	 * Roll back the most recently applied migrations.
	 *
	 * @return int Number of migrations selected for rollback.
	 */
	public function rollback(
		string $migrationsDirectory,
		int $steps = self::DEFAULT_ROLLBACK_STEPS,
		bool $pretend = false
	): int
	{
		if ($steps < self::DEFAULT_ROLLBACK_STEPS) {
			throw new InvalidArgumentException('Rollback steps must be greater than zero.');
		}

		$migrationsDirectory = rtrim($migrationsDirectory, DIRECTORY_SEPARATOR);
		$this->ensureMigrationsTableExists();

		if (!DirectoryHandler::exists($migrationsDirectory)) {
			throw new RuntimeException("migrations: directory not found: {$migrationsDirectory}");
		}

		$files = $this->getMigrationFiles($migrationsDirectory);
		$applied = array_slice($this->getAppliedMigrationRecords(true), 0, $steps, true);
		$rollbackMigrations = [];

		foreach (array_keys($applied) as $identifier) {
			if (!isset($files[$identifier])) {
				throw new RuntimeException("migrations: applied migration file is missing: {$identifier}");
			}

			$file = $files[$identifier];
			$migrationClass = $this->discoverMigrationClassFromFile($file);
			if ($migrationClass === null) {
				throw new RuntimeException("migrations: no Migration subclass found in {$file}");
			}

			$downMethod = new ReflectionMethod($migrationClass, 'down');
			if ($downMethod->getDeclaringClass()->getName() === Migration::class) {
				throw new RuntimeException("migrations: migration is not reversible: {$identifier}");
			}

			$rollbackMigrations[$identifier] = $migrationClass;
		}

		$rollbackCount = count($rollbackMigrations);
		if ($pretend) {
			return $rollbackCount;
		}

		foreach ($rollbackMigrations as $identifier => $migrationClass) {
			$instance = new $migrationClass();
			$instance->down($this->database);
			$this->recordMigrationRolledBack($identifier);
		}

		return $rollbackCount;
	}

	/**
	 * Roll back every applied migration.
	 */
	public function reset(string $migrationsDirectory, bool $pretend = false): int
	{
		return $this->rollback($migrationsDirectory, PHP_INT_MAX, $pretend);
	}

	/**
	 * @return array<string, int> Migration identifier to batch map.
	 */
	private function getAppliedMigrationRecords(bool $descending = false): array
	{
		$direction = $descending ? 'DESC' : 'ASC';
		$statement = $this->database->prepare(
			"SELECT id, batch FROM {$this->migrationsTable} ORDER BY batch {$direction}, id {$direction}"
		);
		if (!$statement->execute()) {
			throw new RuntimeException('migrations: failed to read applied migrations');
		}
		$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

		$records = [];
		foreach ($rows as $row) {
			$identifier = (string) ($row['id'] ?? '');
			if ($identifier === '') {
				continue;
			}

			$records[$identifier] = (int) ($row['batch'] ?? MigrationStatus::PENDING_BATCH);
		}

		return $records;
	}

	private function getNextBatchNumber(): int
	{
		$statement = $this->database->prepare("SELECT MAX(batch) AS batch FROM {$this->migrationsTable}");
		if (!$statement->execute()) {
			throw new RuntimeException('migrations: failed to read the latest migration batch');
		}
		$row = $statement->fetch(PDO::FETCH_ASSOC);

		$maximumBatch = $row['batch'] ?? null;
		if ($maximumBatch === null || $maximumBatch === '') {
			return self::FIRST_BATCH;
		}

		return (int) $maximumBatch + 1;
	}

	private function ensureMigrationsTableExists(): void
	{
		$identifierLength = self::MIGRATION_IDENTIFIER_LENGTH;
		$sql = <<<SQL
CREATE TABLE IF NOT EXISTS {$this->migrationsTable} (
	id VARCHAR({$identifierLength}) PRIMARY KEY,
	batch INTEGER NOT NULL,
	applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
SQL;

		$result = $this->database->executeQuery($sql);
		if ($result === false) {
			throw new RuntimeException('migrations: failed to create the migration tracking table');
		}
	}

	/**
	 * @return array<string, string> Migration identifier to file path map.
	 */
	private function getMigrationFiles(string $migrationsDirectory): array
	{
		$files = DirectoryHandler::getList($migrationsDirectory, 'file', true, false, ['php']);
		if ($files === false) {
			throw new RuntimeException("migrations: failed to read migration directory: {$migrationsDirectory}");
		}

		if (!sort($files)) {
			throw new RuntimeException('migrations: failed to sort migration files');
		}

		$migrationFiles = [];
		foreach ($files as $file) {
			$identifier = FileFunctions::getFilenameWithoutExtension($file);
			if (isset($migrationFiles[$identifier])) {
				throw new RuntimeException("migrations: duplicate migration identifier: {$identifier}");
			}

			$migrationFiles[$identifier] = $file;
		}

		return $migrationFiles;
	}

	/**
	 * @return class-string<Migration>|null
	 */
	private function discoverMigrationClassFromFile(string $filePath): ?string
	{
		$resolved = realpath($filePath) ?: $filePath;
		$needle = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $resolved));

		$findBySourcePath = static function (string $sourcePath): ?string {
			foreach (get_declared_classes() as $candidate) {
				if (!is_subclass_of($candidate, Migration::class, true)) {
					continue;
				}

				$declaredFile = (new ReflectionClass($candidate))->getFileName();
				if ($declaredFile === false) {
					continue;
				}

				$normalizedPath = strtolower(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $declaredFile));
				if ($normalizedPath === $sourcePath) {
					return $candidate;
				}
			}

			return null;
		};

		$alreadyDeclared = $findBySourcePath($needle);
		if ($alreadyDeclared !== null) {
			return $alreadyDeclared;
		}

		if (!is_file($resolved)) {
			return null;
		}

		if (
			\function_exists('opcache_invalidate')
			&& \function_exists('opcache_is_script_cached')
			&& opcache_is_script_cached($resolved)
			&& !opcache_invalidate($resolved, true)
		) {
			throw new RuntimeException("migrations: failed to invalidate cached migration file: {$resolved}");
		}

		$declaredBefore = get_declared_classes();
		try {
			require $resolved;
		} catch (\Throwable $exception) {
			throw new RuntimeException("migrations: failed to load migration file: {$resolved}", 0, $exception);
		}

		foreach (array_values(array_diff(get_declared_classes(), $declaredBefore)) as $className) {
			if (is_subclass_of($className, Migration::class, true)) {
				return (string) $className;
			}
		}

		return $findBySourcePath($needle);
	}

	private function recordMigrationApplied(string $identifier, int $batch): void
	{
		$sql = "INSERT INTO {$this->migrationsTable} (id, batch, applied_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
		$result = $this->database->executeQuery($sql, [$identifier, $batch]);
		if ($result === false) {
			throw new RuntimeException("migrations: failed to record migration: {$identifier}");
		}
	}

	private function recordMigrationRolledBack(string $identifier): void
	{
		$sql = "DELETE FROM {$this->migrationsTable} WHERE id = ?";
		$result = $this->database->executeQuery($sql, [$identifier]);
		if ($result === false) {
			throw new RuntimeException("migrations: failed to remove migration record: {$identifier}");
		}
	}

	private function assertIdentifier(string $identifier): void
	{
		if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $identifier) !== 1) {
			throw new InvalidArgumentException('Migration table name must be a valid SQL identifier.');
		}
	}
}
