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
 * Class Message
 *
 * Represents a message in the Grok LLM API.
 *
 * @package Clover\Classes\LLM\Grok
 */
class Message
{
    private string $role;
    private string $content;

    /**
     * Message constructor.
     *
     * @param string $role
     * @param string $content
     */
    public function __construct(string $role, string $content)
    {
        $this->role = $role;
        $this->content = $content;
    }

    /**
     * Create a Message instance from an array of attributes.
     *
     * @param array $attributes
     * @return Message
     */
    public static function from(array $attributes): self
    {
        return new self(
            $attributes['role'],
            $attributes['content']
        );
    }

    /**
     * Get the role of the message.
     *
     * @return string
     */
    public function getContent(): StringObject
    {
        return new StringObject($this->content);
    }
}