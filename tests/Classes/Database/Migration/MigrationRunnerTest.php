<?php

declare(strict_types=1);

namespace Clover\Tests\Database\Migration;

use Clover\Classes\Database\Migration\MigrationRunner;
use Clover\Classes\Database\Migration\MigrationStatus;
use Clover\Classes\Database\Driver\PHPDataObject;
use PHPUnit\Framework\TestCase;

class MigrationRunnerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'otf_migration_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . DIRECTORY_SEPARATOR . '*');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);
    }

    public function testMigrateAppliesPendingMigrationAndRecordsBatch(): void
    {
        $GLOBALS['migration_up_calls'] = 0;
        $className = 'GeneratedMigration' . uniqid();
        $filePath = $this->tempDir . DIRECTORY_SEPARATOR . '20260101010101_test_migration_for_runner.php';
        file_put_contents(
            $filePath,
            "<?php\nfinal class {$className} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$db): void { \$GLOBALS['migration_up_calls']++; } }\n"
        );

        $db = new FakeMigrationPdo([], ['batch' => null]);
        $runner = new MigrationRunner($db);

        $runner->migrate($this->tempDir);

        $this->assertSame(1, $GLOBALS['migration_up_calls']);
        $this->assertNotEmpty($db->executedQueries);
        $this->assertStringContainsString('INSERT INTO', $db->executedQueries[1]['sql']);
        $this->assertSame(['20260101010101_test_migration_for_runner', 1], $db->executedQueries[1]['params']);
    }

    public function testMigrateInPretendModeDoesNotRunUpOrInsert(): void
    {
        $GLOBALS['migration_up_calls'] = 0;
        $className = 'GeneratedPretendMigration' . uniqid();
        $filePath = $this->tempDir . DIRECTORY_SEPARATOR . '20260101010102_test_migration_for_runner.php';
        file_put_contents(
            $filePath,
            "<?php\nfinal class {$className} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$db): void { \$GLOBALS['migration_up_calls']++; } }\n"
        );

        $db = new FakeMigrationPdo([], ['batch' => 2]);
        $runner = new MigrationRunner($db);

        $runner->migrate($this->tempDir, true);

        $this->assertSame(0, $GLOBALS['migration_up_calls']);
        $this->assertCount(1, $db->executedQueries);
    }

    public function testMigrateThrowsWhenMigrationSubclassIsMissing(): void
    {
        $filePath = $this->tempDir . DIRECTORY_SEPARATOR . '20260101010103_invalid.php';
        file_put_contents($filePath, '<?php class InvalidMigrationPlaceholder {}');

        $db = new FakeMigrationPdo([], ['batch' => null]);
        $runner = new MigrationRunner($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no Migration subclass found');
        $runner->migrate($this->tempDir);
    }

	public function testStatusReportsAppliedPendingAndMissingMigrations(): void
	{
		$appliedIdentifier = '20260101010104_applied';
		$pendingIdentifier = '20260101010105_pending';
		$missingIdentifier = '20260101010103_missing';
		file_put_contents($this->tempDir . DIRECTORY_SEPARATOR . $appliedIdentifier . '.php', '<?php');
		file_put_contents($this->tempDir . DIRECTORY_SEPARATOR . $pendingIdentifier . '.php', '<?php');

		$database = new FakeMigrationPdo([
			['id' => $appliedIdentifier, 'batch' => 2],
			['id' => $missingIdentifier, 'batch' => 1],
		], ['batch' => 2]);
		$statuses = (new MigrationRunner($database))->status($this->tempDir);

		$this->assertCount(3, $statuses);
		$this->assertContainsOnlyInstancesOf(MigrationStatus::class, $statuses);
		$this->assertSame($missingIdentifier, $statuses[0]->getIdentifier());
		$this->assertTrue($statuses[0]->isApplied());
		$this->assertFalse($statuses[0]->isAvailable());
		$this->assertSame(2, $statuses[1]->getBatch());
		$this->assertTrue($statuses[1]->isAvailable());
		$this->assertFalse($statuses[2]->isApplied());
	}

	public function testRollbackRunsDownBeforeRemovingMigrationRecord(): void
	{
		$identifier = '20260101010106_reversible';
		$className = 'GeneratedReversibleMigration' . uniqid();
		$filePath = $this->tempDir . DIRECTORY_SEPARATOR . $identifier . '.php';
		file_put_contents(
			$filePath,
			"<?php\nfinal class {$className} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void {} public function down(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void { \$database->executeQuery('ROLLBACK BODY'); } }\n"
		);

		$database = new FakeMigrationPdo([['id' => $identifier, 'batch' => 3]], ['batch' => 3]);
		$rollbackCount = (new MigrationRunner($database))->rollback($this->tempDir);

		$this->assertSame(1, $rollbackCount);
		$this->assertSame('ROLLBACK BODY', $database->executedQueries[1]['sql']);
		$this->assertStringContainsString('DELETE FROM migrations', $database->executedQueries[2]['sql']);
		$this->assertSame([$identifier], $database->executedQueries[2]['params']);
	}

	public function testRollbackRejectsMigrationWithoutDownImplementation(): void
	{
		$identifier = '20260101010107_irreversible';
		$className = 'GeneratedIrreversibleMigration' . uniqid();
		$filePath = $this->tempDir . DIRECTORY_SEPARATOR . $identifier . '.php';
		file_put_contents(
			$filePath,
			"<?php\nfinal class {$className} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void {} }\n"
		);

		$database = new FakeMigrationPdo([['id' => $identifier, 'batch' => 1]], ['batch' => 1]);
		$runner = new MigrationRunner($database);

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('migration is not reversible');
		$runner->rollback($this->tempDir);
	}

	public function testRollbackValidatesEveryMigrationBeforeChangingDatabase(): void
	{
		$latestIdentifier = '20260101010114_reversible';
		$earlierIdentifier = '20260101010113_irreversible';
		$latestClassName = 'GeneratedPreflightReversibleMigration' . uniqid();
		$earlierClassName = 'GeneratedPreflightIrreversibleMigration' . uniqid();
		$latestBytes = file_put_contents(
			$this->tempDir . DIRECTORY_SEPARATOR . $latestIdentifier . '.php',
			"<?php\nfinal class {$latestClassName} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void {} public function down(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void { \$database->executeQuery('ROLLBACK PREFLIGHT'); } }\n"
		);
		$earlierBytes = file_put_contents(
			$this->tempDir . DIRECTORY_SEPARATOR . $earlierIdentifier . '.php',
			"<?php\nfinal class {$earlierClassName} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void {} }\n"
		);
		$this->assertNotFalse($latestBytes);
		$this->assertNotFalse($earlierBytes);

		$database = new FakeMigrationPdo([
			['id' => $latestIdentifier, 'batch' => 2],
			['id' => $earlierIdentifier, 'batch' => 1],
		], ['batch' => 2]);
		$runner = new MigrationRunner($database);

		try {
			$runner->rollback($this->tempDir, 2);
			$this->fail('Expected an irreversible migration to stop rollback preflight.');
		} catch (\RuntimeException $exception) {
			$this->assertStringContainsString('migration is not reversible', $exception->getMessage());
		}

		$this->assertCount(1, $database->executedQueries);
	}

	public function testRollbackRejectsNonPositiveStepCount(): void
	{
		$runner = new MigrationRunner(new FakeMigrationPdo([], ['batch' => null]));

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('greater than zero');
		$runner->rollback($this->tempDir, 0);
	}

	public function testRollbackPretendModeDoesNotRunDownOrDeleteRecord(): void
	{
		$identifier = '20260101010112_pretend';
		$className = 'GeneratedPretendRollbackMigration' . uniqid();
		$filePath = $this->tempDir . DIRECTORY_SEPARATOR . $identifier . '.php';
		$bytesWritten = file_put_contents(
			$filePath,
			"<?php\nfinal class {$className} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void {} public function down(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void { \$database->executeQuery('ROLLBACK PRETEND'); } }\n"
		);
		$this->assertNotFalse($bytesWritten);

		$database = new FakeMigrationPdo([['id' => $identifier, 'batch' => 1]], ['batch' => 1]);
		$rollbackCount = (new MigrationRunner($database))->rollback($this->tempDir, 1, true);

		$this->assertSame(1, $rollbackCount);
		$this->assertCount(1, $database->executedQueries);
	}

	public function testConstructorRejectsUnsafeMigrationTableName(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new MigrationRunner(new FakeMigrationPdo([], ['batch' => null]), 'migrations; DROP TABLE users');
	}

	public function testResetRollsBackEveryAppliedMigration(): void
	{
		$latestIdentifier = '20260101010109_latest';
		$earlierIdentifier = '20260101010108_earlier';
		foreach ([$latestIdentifier, $earlierIdentifier] as $identifier) {
			$className = 'GeneratedResetMigration' . str_replace('_', '', $identifier) . uniqid();
			$filePath = $this->tempDir . DIRECTORY_SEPARATOR . $identifier . '.php';
			file_put_contents(
				$filePath,
				"<?php\nfinal class {$className} extends \\Clover\\Classes\\Database\\Migration\\Migration { public function up(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void {} public function down(\\Clover\\Classes\\Database\\Driver\\PHPDataObject \$database): void { \$database->executeQuery('ROLLBACK {$identifier}'); } }\n"
			);
		}

		$database = new FakeMigrationPdo([
			['id' => $latestIdentifier, 'batch' => 2],
			['id' => $earlierIdentifier, 'batch' => 1],
		], ['batch' => 2]);
		$rollbackCount = (new MigrationRunner($database))->reset($this->tempDir);

		$this->assertSame(2, $rollbackCount);
		$this->assertSame('ROLLBACK ' . $latestIdentifier, $database->executedQueries[1]['sql']);
		$this->assertSame('ROLLBACK ' . $earlierIdentifier, $database->executedQueries[3]['sql']);
	}

	public function testMigrationStatusRepresentsPendingMigration(): void
	{
		$status = new MigrationStatus('20260101010110_example', MigrationStatus::PENDING_BATCH, true);

		$this->assertFalse($status->isApplied());
		$this->assertTrue($status->isAvailable());
		$this->assertSame(MigrationStatus::PENDING_BATCH, $status->getBatch());
	}

	public function testMigrationStatusRejectsEmptyIdentifier(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new MigrationStatus('', MigrationStatus::PENDING_BATCH, true);
	}

	public function testMigrationStatusRejectsNegativeBatch(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		new MigrationStatus('20260101010111_invalid', -1, true);
	}
}

final class FakeMigrationPdo extends PHPDataObject
{
    /** @var array<int,array<string,mixed>> */
    public array $executedQueries = [];

	/** @param array<int, array{id: string, batch?: int}> $appliedRows */
    public function __construct(private array $appliedRows, private array $maxBatchRow)
    {
    }

    #[\ReturnTypeWillChange]
    public function prepare($query, $options = [])
    {
        if (str_contains((string) $query, 'SELECT id')) {
            return new FakeStatement($this->appliedRows, null);
        }

        if (str_contains((string) $query, 'SELECT MAX(batch)')) {
            return new FakeStatement([], $this->maxBatchRow);
        }

        return new FakeStatement([], null);
    }

    public function executeQuery(string $sql, array $params = []): bool
    {
        $this->executedQueries[] = ['sql' => $sql, 'params' => $params];
        return true;
    }
}

final class FakeStatement
{
    /** @param array<int,array<string,mixed>> $rows */
    public function __construct(private array $rows, private ?array $row)
    {
    }

    public function execute(?array $params = null): bool
    {
        return true;
    }

    /** @return array<int,array<string,mixed>> */
    public function fetchAll(int $mode = 0): array
    {
        return $this->rows;
    }

    public function fetch(int $mode = 0): ?array
    {
        return $this->row;
    }
}
