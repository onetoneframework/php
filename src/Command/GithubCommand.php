<?php

declare(strict_types=1);
use Clover\Classes\CLI\CLIStyle;
use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputOption;
use Clover\Classes\Repository\Git;
use Clover\Classes\System\Output;
use Clover\Implement\CommandInterface;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

class GithubCommand implements CommandInterface
{
    /**
     * @var array Command options.
     */
    public $options = [];

    /**
     * GoogleGeminiCommand constructor.
     */
    public function __construct()
    {
        $this->options = [];
    }

    /**
     * Get the command name.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return "github";
    }


    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Git status";
    }

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->options[] = new InputOption('command', 'command', 'usage');
    }

    /**
     * Execute the command.
     *
     * @param Input $input The input object containing options and arguments.
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool
    {
        $command = $input->getOption("command");

        switch ($command) {
            case "usage": {
                $changedFileCount = Git::getChangedFileCount();
                $pullFileCount = Git::getPullCommitCount();
                $repositoryUrl = Git::getRepositoryUrl();
                $license = Git::getMainLicense();
                $branches = Git::getAllBranches();
                $cliStyle = new CLIStyle();

                echo $cliStyle->box(
                    "Git status",
                    [
                        "License : {$cliStyle->style($license, "yellow")}",
                        "Branches : {$cliStyle->style(implode(", ", $branches), "yellow")}",
                        "Changed file count : {$cliStyle->style($changedFileCount, "magenta")}",
                        "Pull file count : {$cliStyle->style($pullFileCount, "magenta")}",
                        "Repository url : {$cliStyle->style($repositoryUrl, "brightBlue")}",
                    ]
                );
                break;
            }
        }

        return true;
    }

}
