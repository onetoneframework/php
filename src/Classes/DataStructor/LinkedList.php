<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\DataStructor;

use function array_key_exists;

class LinkedList
{
    private array $items = [];

    public function add(mixed $item): void
    {
        $this->items[] = $item;
    }

    public function first(): int|false
    {
        return empty($this->items) ? false : 0;
    }

    public function next(int $index): int|false
    {
        $next = $index + 1;
        return array_key_exists($next, $this->items) ? $next : false;
    }

    public function end(): int|false
    {
        return empty($this->items) ? false : array_key_last($this->items);
    }

    public function get(int $index): mixed
    {
        return $this->items[$index] ?? null;
    }

    public function item(int $position): mixed
    {
        return $this->items[$position] ?? null;
    }
}
