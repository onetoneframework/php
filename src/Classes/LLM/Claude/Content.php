<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Claude;

use Clover\Enumeration\Claude\Role;
use function is_array;

/**
 * Class Content
 *
 * Represents the content of a message in the Claude LLM API.
 *
 * @package Clover\Classes\LLM\Claude
 */
class Content
{
    private array $content;
    private $role;

    public function __construct($content, $role)
    {
        $this->content = $content;
        $this->role = $role;
    }

    public static function from(array $attributes): self
    {
        $content = $attributes['text'] ?? $attributes;
        $role = Role::from($attributes['role'] ?? 'assistant');

        return new self($content, $role);
    }

    public function getContent(): array
    {
        return $this->content;
    }

    public function getText(): mixed
    {
        if (is_array($this->content)) {
            foreach ($this->content as $item) {
                if (isset($item['type']) && $item['type'] === 'text') {
                    return $item['text'];
                }
            }
        }

        return $this->content;
    }

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'role' => $this->role->value,
        ];
    }
}