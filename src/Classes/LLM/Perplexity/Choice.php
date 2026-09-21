<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Perplexity;

/**
 * Class Choice
 *
 * Represents a choice in the Perplexity LLM API response.
 *
 * @package Clover\Classes\LLM\Perplexity
 */
class Choice
{
    private int $index;
    private Message $message;
    private string $finishReason;

    public function __construct(int $index, Message $message, string $finishReason)
    {
        $this->index = $index;
        $this->message = $message;
        $this->finishReason = $finishReason;
    }

    public static function from(array $attributes): self
    {
        return new self(
            $attributes['index'],
            Message::from($attributes['message']),
            $attributes['finish_reason'] ?? ''
        );
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}