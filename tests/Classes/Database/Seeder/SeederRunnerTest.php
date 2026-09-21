<?php

declare(strict_types=1);

namespace Clover\Tests\Database\Seeder;

use Clover\Classes\Database\Seeder\Seeder;
use Clover\Classes\Database\Seeder\SeederRunner;
use Clover\Classes\Database\Driver\PHPDataObject;
use PHPUnit\Framework\TestCase;

class SeederRunnerTest extends TestCase
{
    public function testRunExecutesSingleSeeder(): void
    {
        OrderedSeeder::$calls = [];
        $db = $this->createMock(PHPDataObject::class);
        $runner = new SeederRunner($db);

        $runner->run(OrderedSeeder::class);

        $this->assertSame([OrderedSeeder::class], OrderedSeeder::$calls);
    }

    public function testRunManyExecutesSeedersInOrder(): void
    {
        OrderedSeeder::$calls = [];
        SecondarySeeder::$calls = [];
        $db = $this->createMock(PHPDataObject::class);
        $runner = new SeederRunner($db);

        $runner->runMany([OrderedSeeder::class, SecondarySeeder::class]);

        $this->assertSame([OrderedSeeder::class], OrderedSeeder::$calls);
        $this->assertSame([SecondarySeeder::class], SecondarySeeder::$calls);
    }

    public function testRunThrowsForMissingSeederClass(): void
    {
        $db = $this->createMock(PHPDataObject::class);
        $runner = new SeederRunner($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Seeder class not found');
        $runner->run('Tests\\Database\\Seeder\\MissingSeeder');
    }

    public function testRunRethrowsSeederFailure(): void
    {
        $db = $this->createMock(PHPDataObject::class);
        $runner = new SeederRunner($db);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('seed failed');
        $runner->run(FailingSeeder::class);
    }
}

final class OrderedSeeder extends Seeder
{
    /** @var string[] */
    public static array $calls = [];

    public function run(PHPDataObject $db): void
    {
        self::$calls[] = self::class;
    }
}

final class SecondarySeeder extends Seeder
{
    /** @var string[] */
    public static array $calls = [];

    public function run(PHPDataObject $db): void
    {
        self::$calls[] = self::class;
    }
}

final class FailingSeeder extends Seeder
{
    public function run(PHPDataObject $db): void
    {
        throw new \RuntimeException('seed failed');
    }
}
