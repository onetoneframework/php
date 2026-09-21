<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Perplexity;

use Clover\Classes\Data\StringObject;
use Exception;

class Message
{
    private string $role;
    private string $content;

    public function __construct(string $role, string $content)
    {
        $this->role = $role;
        $this->content = $content;
    }

    public static function from(array $attributes): self
    {
        return new self(
            $attributes['role'],
            $attributes['content']
        );
    }

    public function getContent(): StringObject
    {
        return new StringObject($this->content);
    }
}