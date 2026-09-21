<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Grok;

use Clover\Classes\Data\StringObject;
use Exception;

/**
 * Class Error
 *
 * Represents an error response from the Grok LLM API.
 *
 * @package Clover\Classes\LLM\Grok
 */
class Error
{
    private string $message;
    private string $type;

    /**
     * Error constructor.
     *
     * @param string $message
     * @param string $type
     */
    public function __construct(string $message, string $type)
    {
        $this->message = $message;
        $this->type = $type;
    }

    /**
     * Create an Error instance from an array of attributes.
     *
     * @param array $attributes
     * 
     * @return Error
     */
    public static function from(array $attributes): self
    {
        return new self(
            $attributes['message'] ?? '',
            $attributes['type'] ?? ''
        );
    }

    /**
     * Get the error message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}