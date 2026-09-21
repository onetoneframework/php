<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes;

class TokenObject
{
    // Token types
    const T_UNKNOWN     = 0;
    const T_INTEGER     = 1;
    const T_FLOAT       = 2;
    const T_STRING      = 3;
    const T_BOOL        = 4;
    const T_NULL        = 5;
    const T_IDENTIFIER  = 6;
    const T_KEYWORD     = 7;
    const T_OPERATOR    = 8;
    const T_PUNCTUATION = 9;
    const T_EOF         = 10;

    // Keywords
    const KEYWORDS = [
        'if', 'else', 'elseif', 'while', 'for', 'foreach',
        'function', 'return', 'class', 'new', 'true', 'false',
        'null', 'var', 'let', 'const', 'break', 'continue',
        'and', 'or', 'not', 'in', 'instanceof', 'typeof',
    ];

    private int    $type;
    private string $value;
    private int    $line;
    private int    $column;

    public function __construct(string $value, int $type, int $line = 0, int $column = 0)
    {
        $this->value  = $value;
        $this->type   = $type;
        $this->line   = $line;
        $this->column = $column;
    }

    public function getType(): int    { return $this->type; }
    public function getValue(): string { return $this->value; }
    public function getLine(): int    { return $this->line; }
    public function getColumn(): int  { return $this->column; }

    public function is(int $type): bool          { return $this->type === $type; }
    public function isValue(string $val): bool   { return $this->value === $val; }
    public function isKeyword(string $kw): bool  { return $this->type === self::T_KEYWORD && $this->value === $kw; }
    public function isOperator(string $op): bool { return $this->type === self::T_OPERATOR && $this->value === $op; }
    public function isPunct(string $p): bool     { return $this->type === self::T_PUNCTUATION && $this->value === $p; }
    public function isEOF(): bool                { return $this->type === self::T_EOF; }

    public function __toString(): string
    {
        return "[{$this->type}:{$this->value}]";
    }
}
