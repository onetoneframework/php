<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\LLM\ChatGPT\Client;
use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\System\Output;

/**
 * ChatGPT Command Class
 *
 * Command-line interface for interacting with ChatGPT LLM.
 * Allows users to send prompts to ChatGPT and receive responses.
 */
class ChatGPTCommand implements CommandInterface
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
        return 'llm:chatgpt';
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return 'Make a prompt request to the ChatGPT LLM';
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
        $prompt = $input->getOption('prompt');
        $chatgpt = new Client($_ENV['CHATGPT_API_KEY']);
        $response = $chatgpt->requestPrompt($prompt);
        
        if ($response->hasError()) {
            $response = $response->getError();
        } else {
            $response = $response->getText();
        }

        Output::print($response);

        return true;
    }
}