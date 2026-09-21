<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\WithQuery;
use Exception;
use PHPUnit\Framework\TestCase;

final class WithQueryTest extends TestCase
{
    public function testBuildsWithSqlIncludingRecursiveCte(): void
    {
        $sql = (new WithQuery())
            ->with('active_users', 'SELECT id FROM users WHERE active = 1')
            ->withRecursive('tree', 'SELECT id, parent_id FROM categories')
            ->select('*')
            ->from('active_users')
            ->toSql();

        $this->assertStringContainsString('WITH', $sql);
        $this->assertStringContainsString('`active_users` AS (SELECT id FROM users WHERE active = 1)', $sql);
        $this->assertStringContainsString('`tree` AS (SELECT id, parent_id FROM categories)', $sql);
        $this->assertStringContainsString('SELECT * FROM `active_users`', $sql);
    }

    public function testToSqlThrowsWhenNoCteDefined(): void
    {
        $query = new WithQuery();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No CTE defined');
        $query->toSql();
    }

    public function testExecuteGetAndFirstUseConnection(): void
    {
        $result = new class {
            public function fetchAll(): array
            {
                return [['id' => 1], ['id' => 2]];
            }
            public function fetch(): mixed
            {
                return ['id' => 1];
            }
        };

        $connection = new class($result) {
            public function __construct(private object $result)
            {
            }
            public function query(string $sql): object
            {
                return $this->result;
            }
        };

        $query = (new WithQuery($connection))
            ->with('active_users', 'SELECT id FROM users WHERE active = 1')
            ->select('*')
            ->from('active_users');

        $this->assertCount(2, $query->get());
        $this->assertSame(1, $query->first()['id']);
        $this->assertIsObject($query->execute());
    }

    public function testExecuteThrowsWhenConnectionMissing(): void
    {
        $query = (new WithQuery())
            ->with('active_users', 'SELECT id FROM users WHERE active = 1');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No database connection available');
        $query->execute();
    }
}
