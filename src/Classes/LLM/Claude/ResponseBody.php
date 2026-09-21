<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Claude;

use Clover\Classes\Data\StringObject;

/**
 * Class ResponseBody
 *
 * Represents the response body from the Claude LLM API.
 *
 * @package Clover\Classes\LLM\Claude
 */
class ResponseBody
{
    private ?string $id = null;
    private ?string $type = null;
    private ?string $role = null;
    private array $content = [];
    private ?string $model = null;
    private ?string $stopReason = null;
    private ?Error $error = null;
    private ?array $usage = null;

    public function __construct($attributes)
    {
        if (isset($attributes['error'])) {
            $this->error = Error::from($attributes['error']);
            return;
        }

        $this->id = $attributes['id'] ?? null;
        $this->type = $attributes['type'] ?? null;
        $this->role = $attributes['role'] ?? null;
        $this->content = $attributes['content'] ?? [];
        $this->model = $attributes['model'] ?? null;
        $this->stopReason = $attributes['stop_reason'] ?? null;
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
     * @return Error|null
     */
    public function getError(): Error|null
    {
        return $this->error;
    }

    /**
     * Get the text content from the response.
     *
     * @return StringObject
     * @throws \Exception
     */
    public function getText(): StringObject
    {
        if (empty($this->content)) {
            throw new \Exception('Content is empty');
        }

        foreach ($this->content as $item) {
            if (isset($item['type']) && $item['type'] === 'text') {
                return new StringObject($item['text']);
            }
        }

        throw new \Exception('No text content found');
    }

    /**
     * Get the full content array from the response.
     *
     * @return array
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Get usage statistics.
     *
     * @return array|null
     */
    public function getUsage(): ?array
    {
        return $this->usage;
    }

    /**
     * Get the stop reason for the response.
     *
     * @return string|null
     */
    public function getStopReason(): ?string
    {
        return $this->stopReason;
    }
}