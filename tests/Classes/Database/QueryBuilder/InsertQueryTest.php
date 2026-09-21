<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\InsertQuery;
use Exception;
use PHPUnit\Framework\TestCase;

final class InsertQueryTest extends TestCase
{
    public function testBuildsInsertSqlWithIgnoreAndDuplicateUpdate(): void
    {
        $sql = (new InsertQuery())
            ->insert('users')
            ->ignore()
            ->values(['name' => 'Alice', 'age' => 20])
            ->onDuplicateKeyUpdate(['name' => 'Alice2'])
            ->toSql();

        $this->assertSame(
            "INSERT IGNORE INTO `users` (`name`, `age`) VALUES ('Alice', 20) ON DUPLICATE KEY UPDATE `name` = 'Alice2'",
            $sql
        );
    }

    public function testInsertFromSelectBuildsExpectedSql(): void
    {
        $sql = (new InsertQuery())
            ->insert('users_archive')
            ->columns(['id', 'name'])
            ->insertFromSelect('SELECT id, name FROM users')
            ->toSql();

        $this->assertSame(
            'INSERT INTO `users_archive` (`id`, `name`) SELECT id, name FROM users',
            $sql
        );
    }

    public function testExecuteAndRowHelpersUseConnection(): void
    {
        $connection = new class {
            public string $lastQuery = '';
            public function query(string $sql): string
            {
                $this->lastQuery = $sql;
                return 'ok';
            }
            public function lastInsertId(): int
            {
                return 101;
            }
            public function rowCount(): int
            {
                return 2;
            }
        };

        $query = (new InsertQuery($connection))
            ->insert('users')
            ->values(['name' => 'Bob']);

        $this->assertSame('ok', $query->execute());
        $this->assertStringContainsString('INSERT INTO `users`', $connection->lastQuery);
        $this->assertSame(101, $query->getLastInsertId());
        $this->assertSame(2, $query->getAffectedRows());
    }

    public function testExecuteThrowsWhenConnectionMissing(): void
    {
        $query = (new InsertQuery())
            ->insert('users')
            ->values(['name' => 'NoConn']);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No database connection available');
        $query->execute();
    }
}
