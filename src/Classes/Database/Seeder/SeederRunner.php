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
use Clover\Classes\System\Output;
use RuntimeException;
use Throwable;

/**
 * Orchestrates execution of one or more database seeders.
 */
final class SeederRunner
{
    private PHPDataObject $db;

    public function __construct(PHPDataObject $db)
    {
        $this->db = $db;
    }

    /**
     * Run a single seeder class.
     *
     * @param class-string<Seeder> $seederClass
     */
    public function run(string $seederClass): void
    {
        $this->runMany([$seederClass]);
    }

    /**
     * Run multiple seeders in order.
     *
     * @param array<int,string> $seederClasses
     */
    public function runMany(array $seederClasses): void
    {
        foreach ($seederClasses as $class) {
            $this->runOne($class);
        }
    }

    /**
     * @param class-string<Seeder> $class
     */
    private function runOne(string $class): void
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Seeder class not found: {$class}");
        }

        $instance = new $class();
        if (!$instance instanceof Seeder) {
            throw new RuntimeException("Seeder {$class} must extend " . Seeder::class);
        }

        try {
            $instance->run($this->db);
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
