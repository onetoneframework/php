<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputArgument;
use Clover\Classes\CLI\InputOption;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\Database\Seeder\SeederRunner;
use Clover\Classes\System\Output;
use Clover\Implement\CommandInterface;

/**
 * Database Seed Command
 *
 * Database seeding entrypoint.
 *
 * Examples:
 *  php php_console database:seed
 *  php php_console database:seed App\\Database\\Seeders\\UserSeeder
 */
final class DatabaseSeedCommand implements CommandInterface
{
    /**
     * @var array<int,InputArgument>
     */
    public array $arguments = [];

    /**
     * @var array<int,InputOption>
     */
    public array $options = [];

    public function getName(): string
    {
        return 'database:seed';
    }

    public function getDescription(): string
    {
        return 'Run database seeders';
    }

    public function configure(): void
    {
        $this->arguments[] = new InputArgument(
            'class',
            'Root seeder class (FQCN)',
            'App\\Database\\Seeders\\DatabaseSeeder'
        );

        $this->options[] = new InputOption(
            'force',
            'Run in production (no safety check implemented yet)',
            false
        );
    }

    public function run(Input $input): bool
    {
        $seederClass = (string) ($input->getArgument('class') ?? 'App\\Database\\Seeders\\DatabaseSeeder');

        $db = new PHPDataObject();
        $db->setHostName($_ENV['MYSQL_HOST']);
        $db->setUsername($_ENV['MYSQL_USERNAME']);
        $db->setPassword($_ENV['MYSQL_PASSWORD']);
        $db->setDatabase($_ENV['MYSQL_DATABASE']);
        $db->createConnection();

        Output::printLine('Running database seeds...');

        $runner = new SeederRunner($db);
        $runner->run($seederClass);

        Output::printLine('Database seeding completed.');

        return true;
    }
}
