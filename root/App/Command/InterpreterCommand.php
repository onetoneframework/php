<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\CLI\InputArgument;
use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputOption;
use Clover\Interpreter\InterpreterBuilder;

class InterpreterCommand implements CommandInterface
{
    public array $arguments = [];
    public array $options = [];

    public function getName(): string
    {
        return 'interpreter';
    }

    public function getDescription(): string
    {
        return 'Run a sample script with the Onetone interpreter runtime';
    }

    public function configure(): void
    {
        $this->arguments[] = new InputOption('command', 'command', 'get:route');
        $this->options[] = new InputArgument('id', 'id', '196015867');
    }

    public function run(Input $input): bool
    {
        $interp = InterpreterBuilder::create()
            ->withStandardLibrary()
            ->withBuiltin('sqrt', fn($x) => sqrt($x))
            ->withMaxCallDepth(500)
            ->build();

        $sourceCode = "console.print(math.round(10.4))";
        $interp->run($sourceCode);

        return true;
    }
}
