<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Grok;

/**
 * Class Choice
 *
 * Represents a choice in the Grok LLM API response.
 *
 * @package Clover\Classes\LLM\Grok
 */
class Choice
{
    private int $index;
    private Message $message;
    private string $finishReason;

    /**
     * Choice constructor.
     *
     * @param int $index
     * @param Message $message
     * @param string $finishReason
     */
    public function __construct(int $index, Message $message, string $finishReason)
    {
        $this->index = $index;
        $this->message = $message;
        $this->finishReason = $finishReason;
    }

    /**
     * Create a Choice instance from an array of attributes.
     *
     * @param array $attributes
     * @return Choice
     */
    public static function from(array $attributes): self
    {
        return new self(
            $attributes['index'],
            Message::from($attributes['message']),
            $attributes['finish_reason'] ?? ''
        );
    }

    /**
     * Get the message of the choice.
     *
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }
}