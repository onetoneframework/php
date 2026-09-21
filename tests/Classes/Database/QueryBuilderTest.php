<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Database\QueryBuilder;
use PHPUnit\Framework\TestCase;

class QueryBuilderTest extends TestCase
{

    public function setUp(): void
    {
    }

    public function testSelect(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->select(['id', 'name', 'email'])
            ->from('users')
            ->where('active', 1)
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->toSql();

        $this->assertEquals('SELECT `id`, `name`, `email` FROM `users` WHERE `active` = 1 ORDER BY `name` ASC LIMIT 10', $sql);

        $sql = $qb->select(['u.name', 'p.title'])
            ->from('users', 'u')
            ->innerJoin('posts', 'p.user_id', '=', 'u.id')
            ->where('u.active', 1)
            ->toSql();

        $this->assertEquals('SELECT `u`.`name`, `p`.`title` FROM `users` AS `u` INNER JOIN `posts` ON `p`.`user_id` = `u`.`id` WHERE `u`.`active` = 1', $sql);

        $sql = $qb->select(['id', 'name', 'email'])
            ->from('users')
            ->where('active', 1)
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->toSql();

        $expected = "SELECT `id`, `name`, `email` FROM `users` WHERE `active` = 1 ORDER BY `name` ASC LIMIT 10";
        $this->assertEquals($sql, $expected, "Basic SELECT");

        $sql = $qb->select('DISTINCT country')
            ->from('users')
            ->toSql();

        // `DISTINCT country` is an expression rather than an identifier, so the SELECT list passes
        // it through unquoted; only the table is quoted here.
        $expected = "SELECT DISTINCT country FROM `users`";
        $this->assertEquals($sql, $expected, "DISTINCT SELECT");

        $sql = $qb->select(['u.name', 'p.title'])
            ->from('users', 'u')
            ->innerJoin('posts', 'p.user_id', '=', 'u.id')
            ->where('u.active', 1)
            ->toSql();

        $expected = "SELECT `u`.`name`, `p`.`title` FROM `users` AS `u` INNER JOIN `posts` ON `p`.`user_id` = `u`.`id` WHERE `u`.`active` = 1";
        $this->assertEquals($sql, $expected, "INNER JOIN");

        $sql = $qb->select(['u.name', 'COUNT(p.id) as post_count'])
            ->from('users', 'u')
            ->leftJoin('posts', 'p.user_id', '=', 'u.id')
            ->groupBy(['u.id', 'u.name'])
            ->toSql();

        $expected = "SELECT `u`.`name`, COUNT(p.id) as post_count FROM `users` AS `u` LEFT JOIN `posts` ON `p`.`user_id` = `u`.`id` GROUP BY `u`.`id`, `u`.`name`";
        $this->assertEquals($sql, $expected, "LEFT JOIN with GROUP BY");

        $sql = $qb->select('*')
            ->from('users')
            ->where('age', '>=', 18)
            ->where('country', 'IN', ['US', 'CA', 'UK'])
            ->where('email', 'LIKE', '%@gmail.com')
            ->whereNotNull('phone')
            ->toSql();

        $expected = "SELECT * FROM `users` WHERE `age` >= 18 AND `country` IN ('US', 'CA', 'UK') AND `email` LIKE '%@gmail.com' AND `phone` IS NOT NULL";
        $this->assertEquals($sql, $expected, "Complex WHERE");

        $sql = $qb->select('*')
            ->from('users')
            ->whereBetween('age', 18, 65)
            ->toSql();

        $expected = "SELECT * FROM `users` WHERE `age` BETWEEN 18 AND 65";
        $this->assertEquals($sql, $expected, "BETWEEN");

        $subQuery = $qb->select(['user_id', 'COUNT(*) as order_count'])
            ->from('orders')
            ->groupBy('user_id')
            ->toSql();

        $sql = $qb->with('user_orders', $subQuery)
            ->select(['u.name', 'uo.order_count'])
            ->from('users', 'u')
            ->join('user_orders', 'u.id', '=', 'uo.user_id')
            ->toSql();

        $expected = "WITH `user_orders` AS (SELECT `user_id`, COUNT(*) as order_count FROM `orders` GROUP BY `user_id`) SELECT `u`.`name`, `uo`.`order_count` FROM `users` AS `u` INNER JOIN `user_orders` ON `u`.`id` = `uo`.`user_id`";
        $this->assertEquals($sql, $expected, "Basic CTE");

        $subQuery = $qb->select('user_id')
            ->from('posts')
            ->where('created_at', '>', '2023-01-01')
            ->toSql();

        $sql = $qb->select('u.*')
            ->from('users', 'u')
            ->joinSub($subQuery, 'recent_posts', 'u.id', '=', 'recent_posts.user_id')
            ->toSql();

        $expected = "SELECT u.* FROM `users` AS `u` INNER JOIN (SELECT `user_id` FROM `posts` WHERE `created_at` > '2023-01-01') AS `recent_posts` ON `u`.`id` = `recent_posts`.`user_id`";
        $this->assertEquals($sql, $expected, "JOIN with subquery");

        $sql = $qb->select('*')
            ->from('users')
            ->where('age', '>=', 18)
            ->whereGroup(function ($query) {
                $query->where('status', 'active')
                    ->orWhere('status', 'pending');
            })
            ->toSql();

        $expected = "SELECT * FROM `users` WHERE `age` >= 18 AND (`status` = 'active' OR `status` = 'pending')";
        $this->assertEquals($sql, $expected, "Grouped WHERE");

        $recursiveQuery = "
            SELECT id, name, parent_id, 0 as level
            FROM categories
            WHERE parent_id IS NULL
            UNION ALL
            SELECT c.id, c.name, c.parent_id, rc.level + 1
            FROM categories c
            INNER JOIN category_tree rc ON c.parent_id = rc.id
        ";

        $sql = $qb->with('category_tree', $recursiveQuery)
            ->select('*')
            ->from('category_tree')
            ->orderBy('level', 'ASC')
            ->toSql();

        $expected = "WITH `category_tree` AS (
            SELECT id, name, parent_id, 0 as level
            FROM categories
            WHERE parent_id IS NULL
            UNION ALL
            SELECT c.id, c.name, c.parent_id, rc.level + 1
            FROM categories c
            INNER JOIN category_tree rc ON c.parent_id = rc.id
        ) SELECT * FROM `category_tree` ORDER BY `level` ASC";

        $this->assertEquals($sql, $expected, "WITH");
    }

    public function testInsert(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->insert('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30
            ])
            ->toSql();

        $this->assertEquals("INSERT INTO `users` (`name`, `email`, `age`) VALUES ('John Doe', 'john@example.com', 30)", $sql);

        $sql = $qb->insert('users')
            ->values([
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'age' => 30
            ])
            ->toSql();

        $expected = "INSERT INTO `users` (`name`, `email`, `age`) VALUES ('John Doe', 'john@example.com', 30)";
        $this->assertEquals($sql, $expected, "Basic INSERT");

        $sql = $qb->insert('users')
            ->insertMultiple([
                ['name' => 'Alice', 'email' => 'alice@example.com'],
                ['name' => 'Bob', 'email' => 'bob@example.com']
            ])
            ->toSql();

        $expected = "INSERT INTO `users` (`name`, `email`) VALUES ('Alice', 'alice@example.com'), ('Bob', 'bob@example.com')";
        $this->assertEquals($sql, $expected, "Multiple INSERT");

        $sql = $qb->insert('users')
            ->ignore()
            ->values(['name' => 'John', 'email' => 'john@example.com'])
            ->toSql();

        $expected = "INSERT IGNORE INTO `users` (`name`, `email`) VALUES ('John', 'john@example.com')";
        $this->assertEquals($sql, $expected, "CREATE TABLE");

        $sql = $qb->insert('users')
            ->values(['id' => 1, 'name' => 'John Updated'])
            ->onDuplicateKeyUpdate(['name' => 'John Updated', 'updated_at' => 'NOW()'])
            ->toSql();

        $expected = "INSERT INTO `users` (`id`, `name`) VALUES (1, 'John Updated') ON DUPLICATE KEY UPDATE `name` = 'John Updated', `updated_at` = 'NOW()'";
        $this->assertEquals($sql, $expected, "CREATE TABLE");
    }

    public function testUpdate(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->update('users')
            ->set(['name' => 'John Updated', 'updated_at' => 'NOW()'])
            ->where('id', 1)
            ->toSql();

        $expected = "UPDATE `users` SET `name` = 'John Updated', `updated_at` = 'NOW()' WHERE `id` = 1";
        $this->assertEquals($sql, $expected, "Basic UPDATE");

        $sql = $qb->update('products')
            ->increment('stock', 10)
            ->where('id', 1)
            ->toSql();

        $expected = "UPDATE `products` SET `stock` = `stock` + 10 WHERE `id` = 1";
        $this->assertEquals($sql, $expected, "INCREMENT");
    }

    public function testDelete(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->delete('users')
            ->where('last_login', '<', '2023-01-01')
            ->toSql();

        $expected = "DELETE FROM `users` WHERE `last_login` < '2023-01-01'";
        $this->assertEquals($sql, $expected, "Basic DELETE");

        $sql = $qb->delete('temp_table')
            ->truncate()
            ->toSql();

        $expected = "TRUNCATE TABLE `temp_table`";
        $this->assertEquals($sql, $expected, "TRUNCATE");

        $sql = $qb->delete('users')
            ->join('sessions', 'sessions.user_id', '=', 'users.id')
            ->where('sessions.expired', 1)
            ->toSql();

        $expected = "DELETE FROM `users` INNER JOIN `sessions` ON `sessions`.`user_id` = `users`.`id` WHERE `sessions`.`expired` = 1";
        $this->assertEquals($sql, $expected, "CREATE TABLE");

    }

    public function testCreate(): void
    {
        $qb = new QueryBuilder();

        $sql = $qb->createTable('users')
            ->integer('id', 11, ['auto_increment' => true, 'nullable' => false])
            ->string('name', 100, ['nullable' => false])
            ->string('email', 255, ['unique' => true])
            ->integer('age', 3)
            ->dateTime('created_at', ['default' => 'CURRENT_TIMESTAMP'])
            ->primaryKey('id')
            ->index('idx_email', 'email')
            ->toSql();

        $expected = "CREATE TABLE `users` (`id` INT(11) NOT NULL AUTO_INCREMENT, `name` VARCHAR(100) NOT NULL, `email` VARCHAR(255) UNIQUE, `age` INT(3), `created_at` DATETIME DEFAULT 'CURRENT_TIMESTAMP', PRIMARY KEY (`id`), INDEX `idx_email` (`email`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->assertEquals($sql, $expected, "CREATE TABLE");
    }
}
