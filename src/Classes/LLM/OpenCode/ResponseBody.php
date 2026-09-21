<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\OpenCode;

use Clover\Classes\Data\StringObject;

class ResponseBody
{
    private ?string $id = null;
    private ?string $object = null;
    private ?int $created = null;
    private ?string $model = null;
    private array $choices = [];
    private ?array $usage = null;
    private ?array $error = null;

    public function __construct(array $attributes)
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

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getError(): ?array
    {
        return $this->error;
    }

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

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getUsage(): ?array
    {
        return $this->usage;
    }

    public function getFinishReason(): ?string
    {
        return $this->choices[0]['finish_reason'] ?? null;
    }
}