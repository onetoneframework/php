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
 * Class ResponseBody
 *
 * Represents the response body from the Grok LLM API.
 *
 * @package Clover\Classes\LLM\Grok
 */
class ResponseBody
{
    private array $choices = [];
    private ?Error $error = null;

    /**
     * ResponseBody constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes)
    {
        if (isset($attributes['error'])) {
            $this->error = Error::from($attributes['error']);
        }

        if (isset($attributes['choices'])) {
            foreach ($attributes['choices'] as $choice) {
                $this->choices[] = Choice::from($choice);
            }
        }
    }

    /**
     * Check if the response contains an error.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Get error details.
     *
     * @return Error|null
     */
    public function getError(): ?Error
    {
        return $this->error;
    }

    /**
     * Get the text content from the first choice.
     *
     * @return StringObject
     * @throws Exception
     */
    public function getText(): StringObject
    {
        if (empty($this->choices)) {
            throw new Exception('Choices is empty');
        }

        return $this->choices[0]->getMessage()->getContent();
    }
}