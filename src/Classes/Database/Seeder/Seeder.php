<?php
declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\Seeder;

use Clover\Classes\Database\Driver\PHPDataObject;

/**
 * Base database seeder
 *
 * Application seeders should extend this class and implement the run() method:
 *
 * class UsersTableSeeder extends Seeder {
 *     public function run(PHPDataObject $db): void {
 *         $db->executeQuery('INSERT INTO users (name) VALUES (?)', ['Alice']);
 *     }
 * }
 */
abstract class Seeder
{
    /**
     * Run the database seeds.
     */
    abstract public function run(PHPDataObject $db): void;

    /**
     * Convenient entry point to run this seeder.
     */
    public function __invoke(PHPDataObject $db): void
    {
        $this->run($db);
    }

    /**
     * Call additional seeders from within a seeder.
     *
     * @param string|array<int,string> $seeders
     */
    protected function call(string|array $seeders, PHPDataObject $db): void
    {
        $list = is_array($seeders) ? $seeders : [$seeders];

        foreach ($list as $class) {
            if (!class_exists($class)) {
                throw new \RuntimeException("Seeder class not found: {$class}");
            }

            $instance = new $class();
            if (!$instance instanceof self) {
                throw new \RuntimeException("Seeder {$class} must extend " . self::class);
            }

            $instance->run($db);
        }
    }
}
