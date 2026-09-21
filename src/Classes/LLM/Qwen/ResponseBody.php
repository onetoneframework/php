<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\Qwen;

use Clover\Classes\Data\StringObject;

/**
 * Class ResponseBody
 *
 * Represents the response body from the Qwen API.
 *
 * @package Clover\Classes\LLM\Qwen
 */
class ResponseBody
{
    private ?string $id = null;
    private ?string $object = null;
    private ?int $created = null;
    private ?string $model = null;
    private array $choices = [];
    private ?array $usage = null;
    private ?array $error = null;

    /**
     * ResponseBody constructor.
     *
     * @param array $attributes
     */
    public function __construct($attributes)
    {
        if (isset($attributes['error'])) {
            $this->error = $attributes['error'];
            return;
        }

        $this->id = $attributes['id'] ?? null;
        $this->object = $attributes['object'] ?? null;
        $this->created = $attributes['created'] ?? null;
        $this->model = $attributes['model'] ?? null;
        $this->choices = $attributes['choices'] ?? [];
        $this->usage = $attributes['usage'] ?? null;
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
     * @return array|null
     */
    public function getError(): ?array
    {
        return $this->error;
    }

    /**
     * Get the text content from the first choice.
     *
     * @return StringObject
     *
     * @throws \Exception
     */
    public function getText(): StringObject
    {
        if (empty($this->choices)) {
            throw new \Exception('Choices is empty');
        }

        $message = $this->choices[0]['message'] ?? null;
        if (!$message || !isset($message['content'])) {
            throw new \Exception('No content found in message');
        }

        return new StringObject($message['content']);
    }

    /**
     * Get all choices from the response.
     *
     * @return array
     */
    public function getChoices(): array
    {
        return $this->choices;
    }

    /**
     * Get usage statistics from the response.
     *
     * @return array|null
     */
    public function getUsage(): ?array
    {
        return $this->usage;
    }

    /**
     * Get the finish reason of the first choice.
     *
     * @return string|null
     */
    public function getFinishReason(): ?string
    {
        return $this->choices[0]['finish_reason'] ?? null;
    }
}