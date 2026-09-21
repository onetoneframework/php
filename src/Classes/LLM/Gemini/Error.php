<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\LLM\Gemini;

use Clover\Classes\Data\{ArrayObject, StringObject};

/**
 * Class Error
 *
 * @package Clover\Classes\LLM\Gemini
 */
class Error
{

    private int $code;
    private string|StringObject $message;
    private string|StringObject $status;

    /**
     * Error constructor.
     *
     * @param int                     $code
     * @param string|StringObject     $message
     * @param string|StringObject     $status
     */
    public function __construct(int $code, string|StringObject $message, string|StringObject $status)
    {
        $this->code = $code;
        $this->message = $message;
        $this->status = $status;
    }

    /**
     * Create an Error instance from an array or ArrayObject of attributes.
     *
     * @param ArrayObject|array $attributes
     * @return Error
     */
    public static function from(ArrayObject|array $attributes): self
    {
        return new self($attributes['code'] ?? null, $attributes['message'] ?? null, $attributes['status'] ?? null);
    }

    /**
     * Get the error message.
     *
     * @return string|StringObject
     */
    public function getMessage(): string|StringObject
    {
        return $this->message;
    }

}