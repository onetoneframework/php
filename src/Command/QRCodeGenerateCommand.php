<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\Image\SimpleQRCode;
use Clover\Implement\CommandInterface;

class QRCodeGenerateCommand implements CommandInterface
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
        return "qr:generate";
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Make a prompt request to the Google Gemini LLM";
    }

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->options[] = new InputOption('data', 'data', 'testHelloWorld');
    }

    /**
     * Execute the command.
     *
     * @param Input $input The input object containing options and arguments.
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool
    {
        $qr = new SimpleQRCode($input->getOption('data'));
        $qr->saveImage(__DIR__."/qr.png");
        $decoded = SimpleQRCode::readImage(__DIR__."/qr.png");
        var_dump($decoded);

        return true;
    }
}
