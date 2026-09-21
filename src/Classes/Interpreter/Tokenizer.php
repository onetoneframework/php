<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes;

class Tokenizer
{
    private string $source   = '';
    private int    $pos      = 0;
    private int    $line     = 1;
    private int    $column   = 1;
    private array  $tokens   = [];

    private const OPERATORS = [
        '**=', '**', '<<=', '>>=', '===', '!==',
        '==', '!=', '<=', '>=', '&&', '||', '++', '--',
        '+=', '-=', '*=', '/=', '%=', '&=', '|=', '^=',
        '~>', '|>', '<+', '>+', ':=', '..',  '...',
        '+', '-', '*', '/', '%', '=', '<', '>',
        '!', '&', '|', '^', '~', '?', ':', '.',
    ];

    private const PUNCTUATION = ['{', '}', '(', ')', '[', ']', ';', ',', '@'];

    public function tokenize(string $source): array
    {
        $this->source  = $source;
        $this->pos     = 0;
        $this->line    = 1;
        $this->column  = 1;
        $this->tokens  = [];

        while ($this->pos < strlen($this->source)) {
            $this->skipWhitespaceAndComments();

            if ($this->pos >= strlen($this->source)) {
                break;
            }

            $token = $this->readNextToken();
            if ($token !== null) {
                $this->tokens[] = $token;
            }
        }

        $this->tokens[] = new TokenObject('', TokenObject::T_EOF, $this->line, $this->column);
        return $this->tokens;
    }

    // -------------------------------------------------------------------------

    private function readNextToken(): ?TokenObject
    {
        $ch   = $this->peek();
        $line = $this->line;
        $col  = $this->column;

        // String literal
        if ($ch === '"' || $ch === "'") {
            return $this->readString($ch, $line, $col);
        }

        // Template literal
        if ($ch === '`') {
            return $this->readTemplateLiteral($line, $col);
        }

        // Number
        if (ctype_digit($ch) || ($ch === '.' && ctype_digit($this->peekAt(1)))) {
            return $this->readNumber($line, $col);
        }

        // Identifier or keyword
        if (ctype_alpha($ch) || $ch === '_' || $ch === '$') {
            return $this->readWord($line, $col);
        }

        // Operator (greedy - longest match first)
        foreach (self::OPERATORS as $op) {
            if (substr($this->source, $this->pos, strlen($op)) === $op) {
                $this->advance(strlen($op));
                return new TokenObject($op, TokenObject::T_OPERATOR, $line, $col);
            }
        }

        // Punctuation
        if (in_array($ch, self::PUNCTUATION, true)) {
            $this->advance();
            return new TokenObject($ch, TokenObject::T_PUNCTUATION, $line, $col);
        }

        throw new \RuntimeException("Unexpected character '{$ch}' at line {$line}, column {$col}");
    }

    private function readString(string $quote, int $line, int $col): TokenObject
    {
        $this->advance(); // skip opening quote
        $value = '';

        while ($this->pos < strlen($this->source)) {
            $ch = $this->peek();

            if ($ch === $quote) {
                $this->advance();
                return new TokenObject($value, TokenObject::T_STRING, $line, $col);
            }

            if ($ch === '\\') {
                $this->advance();
                $esc = $this->advance();
                $value .= match($esc) {
                    'n'  => "\n",
                    't'  => "\t",
                    'r'  => "\r",
                    '\\' => '\\',
                    '"'  => '"',
                    "'"  => "'",
                    default => '\\' . $esc,
                };
                continue;
            }

            $value .= $this->advance();
        }

        throw new \RuntimeException("Unterminated string at line {$line}");
    }

    private function readTemplateLiteral(int $line, int $col): TokenObject
    {
        $this->advance(); // skip `
        $value = '';

        while ($this->pos < strlen($this->source)) {
            $ch = $this->peek();
            if ($ch === '`') {
                $this->advance();
                return new TokenObject($value, TokenObject::T_STRING, $line, $col);
            }
            $value .= $this->advance();
        }

        throw new \RuntimeException("Unterminated template literal at line {$line}");
    }

    private function readNumber(int $line, int $col): TokenObject
    {
        $value   = '';
        $isFloat = false;

        while ($this->pos < strlen($this->source)) {
            $ch = $this->peek();
            if (ctype_digit($ch)) {
                $value .= $this->advance();
            } elseif ($ch === '.' && !$isFloat && ctype_digit($this->peekAt(1))) {
                $isFloat = true;
                $value  .= $this->advance();
            } else {
                break;
            }
        }

        return new TokenObject($value, $isFloat ? TokenObject::T_FLOAT : TokenObject::T_INTEGER, $line, $col);
    }

    private function readWord(int $line, int $col): TokenObject
    {
        $value = '';

        while ($this->pos < strlen($this->source)) {
            $ch = $this->peek();
            if (ctype_alnum($ch) || $ch === '_' || $ch === '$') {
                $value .= $this->advance();
            } else {
                break;
            }
        }

        // Determine type
        if (in_array($value, TokenObject::KEYWORDS, true)) {
            return new TokenObject($value, TokenObject::T_KEYWORD, $line, $col);
        }

        if ($value === 'true' || $value === 'false') {
            return new TokenObject($value, TokenObject::T_BOOL, $line, $col);
        }

        if ($value === 'null') {
            return new TokenObject($value, TokenObject::T_NULL, $line, $col);
        }

        return new TokenObject($value, TokenObject::T_IDENTIFIER, $line, $col);
    }

    private function skipWhitespaceAndComments(): void
    {
        while ($this->pos < strlen($this->source)) {
            $ch = $this->peek();

            // Whitespace
            if ($ch === ' ' || $ch === "\t" || $ch === "\r") {
                $this->advance();
                continue;
            }

            if ($ch === "\n") {
                $this->advance();
                $this->line++;
                $this->column = 1;
                continue;
            }

            // Line comment
            if ($ch === '/' && $this->peekAt(1) === '/') {
                while ($this->pos < strlen($this->source) && $this->peek() !== "\n") {
                    $this->advance();
                }
                continue;
            }

            // Block comment
            if ($ch === '/' && $this->peekAt(1) === '*') {
                $this->advance(2);
                while ($this->pos < strlen($this->source)) {
                    if ($this->peek() === '*' && $this->peekAt(1) === '/') {
                        $this->advance(2);
                        break;
                    }
                    if ($this->peek() === "\n") {
                        $this->line++;
                        $this->column = 1;
                    }
                    $this->advance();
                }
                continue;
            }

            break;
        }
    }

    // -------------------------------------------------------------------------

    private function peek(): string
    {
        return $this->pos < strlen($this->source) ? $this->source[$this->pos] : '';
    }

    private function peekAt(int $offset): string
    {
        $idx = $this->pos + $offset;
        return $idx < strlen($this->source) ? $this->source[$idx] : '';
    }

    private function advance(int $count = 1): string
    {
        $result = '';
        for ($i = 0; $i < $count; $i++) {
            if ($this->pos < strlen($this->source)) {
                $result .= $this->source[$this->pos];
                $this->pos++;
                $this->column++;
            }
        }
        return $result;
    }
}
