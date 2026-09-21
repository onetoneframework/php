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

class KnowledgeBase
{
    private int    $type;
    private string $typeCode;
    private int    $count = 0;
    private array  $entries = [];

    public function __construct(int $type, string $typeCode)
    {
        $this->type     = $type;
        $this->typeCode = $typeCode;
    }

    public function know(mixed &$var, ?string $label = null): void
    {
        $this->count++;
        $key = strtoupper($label ?? sprintf('`%s%d', $this->typeCode, $this->count));
        $this->entries[$key] = &$var;
    }

    public function exists(string $label): bool
    {
        return array_key_exists(strtoupper($label), $this->entries);
    }

    public function getValue(string $label): mixed
    {
        return $this->entries[strtoupper($label)] ?? null;
    }

    public function put(mixed $value, string $label): void
    {
        $key = strtoupper($label);
        if (array_key_exists($key, $this->entries)) {
            $this->entries[$key] = $value;
        }
    }
}
