<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\CLI;

use Clover\Classes\BaseClass;
use Clover\Classes\DependencyInjection\Injector;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\File\Functions as FileFunctions;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Classes\System\Output;
use Clover\Implement\CommandInterface;
use function in_array;
use function strlen;

class CLIRouter extends BaseClass
{
    /** @var CommandInterface[] $commands */
    private array $commands = [];

    /**
     * Discovers {@see CommandInterface} implementations and registers them for dispatch.
     */
    public function __construct()
    {
        foreach (ReflectionHandler::findDeclaredSubClasses(CommandInterface::class) as $className) {
            $command = new $className();
            Injector::inject($className, $command, true);

            if (method_exists($command, 'configure')) {
                $command->configure();
            }

            $this->register($command);
        }
    }

    /**
     * Parse annotation from file of class base route in directories
     * 
     * @param string $path
     * 
     * @return self
     */
    public function fromDirectory(string $path): self
    {
        $fileList = DirectoryHandler::getList($path, 'file', true, false, ['php']);

        if (!$fileList) {
            return $this;
        }

        foreach ($fileList as $file) {
            if (!file_exists($file) || !is_readable($file)) {
                continue;
            }

            $this->fromFile($file);
        }

        return $this;
    }

    /**
     * Loads every command class declared in a PHP file and registers each instance.
     *
     * @param string $path Absolute path to a PHP source file.
     *
     * @return self
     */
    public function fromFile(string $path): self
    {
        $classNames = FileFunctions::getClassNames($path);

        foreach ($classNames as $className) {
            if (!class_exists($className)) {
                continue;
            }

            $command = new $className();
            Injector::inject($className, $command, true);

            if (method_exists($command, 'configure')) {
                $command->configure();
            }

            $this->register($command);
        }

        return $this;
    }

    /**
     * Registers a command under its {@see CommandInterface::getName()} key.
     *
     * @param CommandInterface $command Command instance to expose on the CLI.
     *
     * @return void
     */
    public function register(CommandInterface $command): void
    {
        $this->commands[$command->getName()] = $command;
        ksort($this->commands);
    }

    /**
     * Prints a boxed list of registered command names and descriptions.
     *
     * @return void
     */
    private function printCommandList(): void
    {
        $commands = $this->commands;
        $maxNameLength = max(array_map('strlen', array_keys($commands)));
        $maxDescLength = max(array_map(fn($cmd) => strlen($cmd->getDescription()), $commands));
        $contentWidth = max($maxNameLength + $maxDescLength + 6, 50);
        $boxWidth = $contentWidth + 4;

        $topBorder = '╭' . str_repeat('─', $boxWidth - 2) . '╮';
        $bottomBorder = '╰' . str_repeat('─', $boxWidth - 2) . '╯';
        $emptyLine = '│' . str_repeat(' ', $boxWidth - 2) . '│';

        $title = 'Framework CLI - Available Commands';
        $titlePadding = (int) (($boxWidth - 2 - mb_strlen($title)) / 2);

        Output::print(PHP_EOL);
        Output::print(' ' . ColorText::color($topBorder, 'cyan') . PHP_EOL);
        Output::print(' ' . ColorText::color('│', 'cyan') . str_repeat(' ', $boxWidth - 2) . ColorText::color('│', 'cyan') . PHP_EOL);
        Output::print(' ' . ColorText::color('│', 'cyan') . str_repeat(' ', $titlePadding) . ColorText::color($title, 'white') . str_repeat(' ', $boxWidth - 2 - $titlePadding - strlen($title)) . ColorText::color('│', 'cyan') . PHP_EOL);
        Output::print(' ' . ColorText::color('│', 'cyan') . str_repeat(' ', $boxWidth - 2) . ColorText::color('│', 'cyan') . PHP_EOL);

        foreach ($commands as $name => $cmd) {
            $nameColored = ColorText::color(str_pad($name, $maxNameLength), 'green');
            $descColored = ColorText::color($cmd->getDescription(), 'white');
            $content = '  ' . $nameColored . '  ' . $descColored;
            $padding = $boxWidth - 2 - $maxNameLength - mb_strlen($cmd->getDescription()) - 4;

            Output::print(' ' . ColorText::color('│', 'cyan') . $content . str_repeat(' ', $padding) . ColorText::color('│', 'cyan') . PHP_EOL);
        }

        Output::print(' ' . ColorText::color('│', 'cyan') . str_repeat(' ', $boxWidth - 2) . ColorText::color('│', 'cyan') . PHP_EOL);
        Output::print(' ' . ColorText::color($bottomBorder, 'cyan') . PHP_EOL);
        Output::print(PHP_EOL);
    }

    /**
     * Dispatches a CLI invocation: lists commands, shows help, or runs a command.
     *
     * @param array<int, string> $argv Raw PHP argv (script path at index 0).
     *
     * @return mixed Command return value, or true after list/help handling.
     */
    public function dispatch(array $argv): mixed
    {
        $commandName = $argv[1] ?? 'list';

        if ($commandName === 'list') {
            $this->printCommandList();

            return true;
        }

        if (!isset($this->commands[$commandName])) {
            Output::print("Unknown command: $commandName\n");
            return true;
        }

        $command = $this->commands[$commandName];

        if (in_array('--help', $argv)) {
            $commandName = ColorText::color($command->getName(), 'green');
            Output::print("Command: {$commandName}\n");

            $description = ColorText::color($command->getDescription(), 'green');
            Output::print("Description: {$description}\n");

            Output::print("Usage:\n");
            Output::print(ColorText::color("  php ./bin/console {$command->getName()}", 'cyan'));

            foreach ($command->arguments ?? [] as $arg) {
                Output::print(" <{$arg->name}>");
            }

            foreach ($command->options ?? [] as $opt) {
                Output::print(ColorText::color(" [--{$opt->name}=...]", 'magenta'));
            }
            Output::print("\n");

            if (!empty($command->arguments)) {
                Output::print("\nArguments:\n");

                foreach ($command->arguments as $arg) {
                    Output::print("  {$arg->name}       {$arg->description}\n");
                }
            }

            if (!empty($command->options)) {
                Output::print("\nOptions:\n");
                foreach ($command->options as $opt) {
                    Output::print(ColorText::color("  --{$opt->name}     {$opt->description}\n", 'yellow'));
                }
            }

            return true;
        }

        $input = new Input($argv, $command->arguments ?? [], $command->options ?? []);
        return $command->run($input);
    }
}
