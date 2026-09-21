<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\Component\TableView;
use Clover\Classes\CLI\{Input, InputArgument, InputOption};
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Implement\CommandInterface;

/**
 * Query Command Class
 *
 * Command-line interface for querying database tables.
 * Supports displaying table schemas and listing all tables.
 * Usage: php php_console query star --command=table:scheme
 */
class QueryCommand implements CommandInterface
{
    /**
     * @var array Command options.
     */
    public array $options = [];

    /**
     * @var array Command arguments.
     */
    public array $arguments = [];

    /**
     * @var array Text data storage.
     */
    private $texts;

    /**
     * @var array Stored keywords.
     */
    private $storedKeywords;

    /**
     * @var array Keywords to find.
     */
    private $findKeywords;

    /**
     * QueryCommand constructor.
     */
    public function __construct()
    {
        $this->texts = [];
        $this->storedKeywords = [];
        $this->findKeywords = [];
    }

    /**
     * Get the command name.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return "database:query";
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Query database tables and display schema or table list";
    }

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->options[] = new InputOption('command', 'command', 'table:list');
        $this->arguments[] = new InputArgument('table', 'table', 'table');
    }

    /**
     * Execute the command.
     *
     * @param Input $input The input object containing options and arguments.
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool
    {
        $db = new PHPDataObject();
        $db->setHostName($_ENV['MYSQL_HOST']);
        $db->setUsername($_ENV['MYSQL_USERNAME']);
        $db->setPassword($_ENV['MYSQL_PASSWORD']);
        $db->setDatabase($_ENV['MYSQL_DATABASE']);
        $db->createConnection();

        $command = $input->getOption('command');
        $table = $input->getArgument('table');

        switch ($command) {
            case 'table:scheme':
                $metadata = $db->getTableMetaData($table);

                $tableView = new TableView();
                $tableView->setOrderKeys(['TYPE', 'Field']);
                $tableView->setHeaders(['Type', 'Field']);
                $tableView->setRows($metadata);
                $tableView->render();
                break;
            case 'table:list':
                $tables = $db->getAllTables();
                $tableView = new TableView();
                $tableView->setHeaders(['TABLE']);
                $tableView->setRows($tables);
                $tableView->render();
                break;
        }

        return true;
    }

}