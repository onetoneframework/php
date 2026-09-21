<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\DataStructor;

class SimpleStack
{
    private array $entries = [];
 
    public function push(mixed $item): void
    {
        $this->entries[] = $item;
    }
 
    public function pop(): mixed
    {
        return array_pop($this->entries);
    }
}
 