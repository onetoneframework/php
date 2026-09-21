<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\SelectQuery;
use Exception;
use PHPUnit\Framework\TestCase;

final class SelectQueryTest extends TestCase
{
    public function testBuildsSelectSqlWithDistinctAndUnionAll(): void
    {
        $sql = (new SelectQuery())
            ->select(['id', 'email'])
            ->distinct()
            ->from('users', 'u')
            ->where('u.active', '=', 1)
            ->unionAll('SELECT id, email FROM archived_users')
            ->toSql();

        $this->assertStringContainsString('SELECT DISTINCT `id`, `email`', $sql);
        $this->assertStringContainsString('FROM `users` AS `u`', $sql);
        $this->assertStringContainsString('WHERE `u`.`active` = 1', $sql);
        $this->assertStringContainsString('UNION ALL SELECT id, email FROM archived_users', $sql);
    }

    public function testGetFirstCountPluckAndKeyByUseExecutedResult(): void
    {
        $rows = [
            ['id' => 10, 'name' => 'A', 'count' => 2],
            ['id' => 11, 'name' => 'B', 'count' => 2],
        ];

        $result = new class($rows) {
            public function __construct(private array $rows)
            {
            }
            public function fetchAll(): array
            {
                return $this->rows;
            }
            public function fetch(): mixed
            {
                return $this->rows[0] ?? null;
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

        $query = (new SelectQuery($connection))
            ->select(['id', 'name', 'count'])
            ->from('users');

        $this->assertCount(2, $query->get());
        $this->assertSame(10, $query->first()['id']);
        $this->assertSame(2, $query->count());
        $this->assertSame(['A', 'B'], $query->pluck('name'));
        $this->assertSame('A', $query->keyBy('id')[10]['name']);
    }

    public function testExecuteThrowsWhenConnectionMissing(): void
    {
        $query = (new SelectQuery())
            ->select('*')
            ->from('users');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No database connection available');
        $query->execute();
    }
}
