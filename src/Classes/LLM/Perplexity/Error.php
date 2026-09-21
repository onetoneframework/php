<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Perplexity;

class Error
{
    private string $message;
    private string $type;

    public function __construct(string $message, string $type)
    {
        $this->message = $message;
        $this->type = $type;
    }

    public static function from(array $attributes): self
    {
        return new self(
            $attributes['message'] ?? '',
            $attributes['type'] ?? ''
        );
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}