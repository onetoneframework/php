<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\LLM\Gemini;
use Clover\Classes\System\Output;
use Clover\Enumeration\Gemini\Model as GeminiModel;
use Clover\Implement\CommandInterface;

/**
 * Google Gemini Command Class
 *
 * Command-line interface for interacting with Google Gemini LLM.
 * Allows users to send prompts to Google Gemini and receive responses.
 */
class GoogleGeminiCommand implements CommandInterface
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
        return "llm:gemini";
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
        $this->options[] = new InputOption('prompt', 'Prompt', 'How are you?');
    }

    /**
     * Execute the command.
     *
     * @param Input $input The input object containing options and arguments.
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool
    {
        $prompt = $input->getPrompt("Enter the prompt for request to google gemini : ");

        $geminiClient = new Gemini\Client($_ENV['GEMINI_API_KEY'], GeminiModel::GEMINI_3_1_FLASH_PREVIEW);
        $response = $geminiClient->requestPrompt($prompt);

        if ($response->hasError()) {
            $error = $response->getError();
            $response = $error->getMessage();
        } else {
            $response = $response->getText();
        }

        Output::print($response);

        return true;
    }
}