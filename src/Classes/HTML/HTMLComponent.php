<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTML;

/**
 * HTML Component Base Class
 */
abstract class HTMLComponent
{
    public string $id;
    public string $name;
    public array $state = [];
    public array $errors = [];

    /**
     * Constructor
     */
    public function __construct(string $id, array $initial = [])
    {
        $this->id = $id;
        $this->name = static::class;
        $saved = $_SESSION['components'][$id] ?? [];
        $this->state = array_merge($initial, $saved);
    }

    public function get(string $key): mixed
    {
        return $this->state[$key];
    }

    public function set(string $key, mixed $value): void
    {
        $this->state[$key] = $value;
    }

    public function persist(): void
    {
        $_SESSION['components'][$this->id] = $this->state;
    }

    public function mount(): void
    {
    }

    public function render(): string
    {
        return '';
    }

    public function call(string $action, array $payload = []): mixed
    {
        if (!method_exists($this, $action)) {
            return false;
        }

        return $this->{$action}($payload);
    }
}
