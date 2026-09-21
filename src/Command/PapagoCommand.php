<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\System\Output;
use Clover\Plugin\NaverPapago;

/**
 * Command to translate text using Naver Papago API.
 */
//#[\Deprecated('This command is deprecated and will be removed in future versions.')]
class PapagoCommand implements CommandInterface
{
    /**
     * @var array Command arguments.
     */
    public array $arguments = [];

    /**
     * @var array Command options.
     */
    public array $options = [];

    /**
     * Get the command name.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return 'translate:papago';
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return 'Translate text using Naver Papago API.';
    }

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->options[] = new InputOption('prompt', 'Prompt', 'How are you?');
    }

    /**
     * Execute the command.
     *
     * @param Input $input The input object containing options and arguments.
     * 
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool
    {
        $papago = new NaverPapago($_ENV['NAVER_CLIENT_ID'], $_ENV['NAVER_CLIENT_SECRET']);
        $response = $papago->translate($input->getOption('prompt'), 'en', 'ko');
        Output::printLine("Translation: " . $response);

        return true;
    }
}