<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Interpreter;

/**
 * Environment
 * Lexical scope chain. Each function call / block creates a child environment.
 */
class Environment
{
    private array $variables = [];
    private ?Environment $parent;

    public function __construct(?Environment $parent = null)
    {
        $this->parent = $parent;
    }

    // -------------------------------------------------------------------------

    public function define(string $name, mixed $value): void
    {
        $this->variables[$name] = $value;
    }

    public function get(string $name): mixed
    {
        if (array_key_exists($name, $this->variables)) {
            return $this->variables[$name];
        }

        if ($this->parent !== null) {
            return $this->parent->get($name);
        }

        return null;  // undefined → null (non-strict)
    }

    public function set(string $name, mixed $value): void
    {
        if (array_key_exists($name, $this->variables)) {
            $this->variables[$name] = $value;
            return;
        }

        if ($this->parent !== null) {
            $this->parent->set($name, $value);
            return;
        }

        // Auto-define at global scope
        $this->variables[$name] = $value;
    }

    public function has(string $name): bool
    {
        if (array_key_exists($name, $this->variables))
            return true;
        return $this->parent?->has($name) ?? false;
    }

    public function child(): self
    {
        return new self($this);
    }

    public function getAll(): array
    {
        return $this->variables;
    }
}
