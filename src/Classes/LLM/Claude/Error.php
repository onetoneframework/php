<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Claude;

use Clover\Classes\Data\{ArrayObject, StringObject};

/**
 * Class Error
 *
 * Represents an error response from the Claude LLM API.
 *
 * @package Clover\Classes\LLM\Claude
 */
class Error
{
    private string|StringObject $type;
    private string|StringObject $message;

    public function __construct(string|StringObject $type, string|StringObject $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    public static function from(ArrayObject|array $attributes): self
    {
        return new self($attributes['type'] ?? 'unknown', $attributes['message'] ?? 'Unknown error');
    }

    public function getMessage(): string|StringObject
    {
        return $this->message;
    }

    public function getType(): string
    {
        return $this->type;
    }
}