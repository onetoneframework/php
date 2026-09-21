<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\CLI\{Input, InputOption};
use Clover\Classes\LLM\Claude;
use Clover\Classes\System\Output;
use Clover\Enumeration\Claude\Model as ClaudeModel;
use Clover\Implement\CommandInterface;

/**
 * Claude Command Class
 *
 * Command-line interface for interacting with Claude LLM.
 * Allows users to send prompts to Claude and receive responses.
 */
class ClaudeCommand implements CommandInterface
{
    /**
     * @var array Command options.
     */
    public $options = [];

    /**
     * ClaudeCommand constructor.
     * Initializes the options array.
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
        return "llm:claude";
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return "Make a prompt request to the Claude LLM";
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
        $prompt = $input->getPrompt("Enter the prompt for request to claude : ");

        $claudeClient = new Claude\Client($_ENV['CLAUDE_API_KEY'], ClaudeModel::HAIKU_4);
        $response = $claudeClient->requestPrompt($prompt);

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