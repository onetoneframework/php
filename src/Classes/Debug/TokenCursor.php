<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Debug\ControlFlow;
use function in_array;
use function is_array;
use function count;

class TokenCursor
{
    private array $tokens;
    private int $pos = 0;

    private const SKIP_IDS = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    public function __construct(string $source)
    {
        $this->tokens = token_get_all($source);
    }

    public function valid(): bool
    {
        return $this->pos < count($this->tokens);
    }

    private function at(int $pos): array
    {
        $t = $this->tokens[$pos] ?? null;
        if ($t === null) {
            return [0, '', 0];
        }
        return is_array($t) ? $t : [0, $t, 0];
    }

    public function id(): int
    {
        return $this->at($this->pos)[0];
    }
    public function text(): string
    {
        return $this->at($this->pos)[1];
    }

    public function advance(): static
    {
        $this->pos++;
        return $this;
    }

    public function skip(): static
    {
        while ($this->valid() && in_array($this->id(), self::SKIP_IDS, true)) {
            $this->pos++;
        }
        return $this;
    }

    public function consume(string $char): static
    {
        $this->skip();
        if ($this->text() === $char) {
            $this->pos++;
        }
        return $this;
    }

    /**
     * Collects inner content of balanced delimiters, consuming both.
     * Cursor must be at $open on entry.
     */
    public function collectInner(string $open, string $close): string
    {
        $this->skip();
        if ($this->text() !== $open) {
            return '';
        }
        $this->pos++;

        $depth = 1;
        $result = '';
        while ($this->valid() && $depth > 0) {
            $t = $this->text();
            if ($t === $open) {
                $depth++;
            }
            if ($t === $close) {
                if (--$depth === 0) {
                    $this->pos++;
                    break;
                }
            }
            $result .= ($this->id() === T_WHITESPACE) ? ' ' : $t;
            $this->pos++;
        }

        return trim(preg_replace('/\s+/', ' ', $result));
    }

    /**
     * Collects tokens until ';' at depth 0, consuming ';'.
     * Whitespace and comments are excluded from the returned array.
     *
     * @return string[]
     */
    public function collectStatement(): array
    {
        $depth = 0;
        $tokens = [];

        while ($this->valid()) {
            $text = $this->text();
            $id = $this->id();

            if (in_array($text, ['(', '[', '{'], true)) {
                $depth++;
            }
            if (in_array($text, [')', ']', '}'], true)) {
                if ($depth === 0) {
                    break;
                }
                $depth--;
                if (!in_array($id, self::SKIP_IDS, true)) {
                    $tokens[] = $text;
                }
                $this->pos++;
                continue;
            }
            if ($text === ';' && $depth === 0) {
                $this->pos++;
                break;
            }

            if (!in_array($id, self::SKIP_IDS, true)) {
                $tokens[] = $text;
            }
            $this->pos++;
        }

        return $tokens;
    }
}
