<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

use InvalidArgumentException;

use function strlen;
use function count;
use function ltrim;
use function rtrim;
use function trim;
use function strtolower;
use function is_numeric;
use function strpos;
use function preg_match;
use function array_pop;
use function end;

/**
 * YAML Handler Class
 *
 * @package Clover\Classes\Data
 */
class YamlHandler
{
    /** @var string[] */
    private array $lines = [];

    /** @var mixed[] */
    private array $data = [];

    /** @var mixed[] Anchor registry for &name / *name support */
    private array $anchors = [];

    /**
     * @param string[] $lines Lines of a YAML document.
     * @throws InvalidArgumentException When $lines is empty.
     */
    public function __construct(array $lines)
    {
        $this->lines = array_values($lines);
        $this->parse();
    }

    /**
     * Return the parsed document as a PHP array.
     *
     * @return mixed[]
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Walk every content line and build the nested data structure.
     *
     * The algorithm uses two parallel stacks:
     *  - $stack       – references to the array currently being filled
     *  - $indentStack – the indentation level that opened each stack frame
     *
     * When the current indent drops below the top of $indentStack the
     * frame is popped, climbing back up the hierarchy.
     */
    private function parse(): void
    {
        $lines = $this->lines;
        $totalLines = count($lines);
        $stack = [&$this->data];
        $indentStack = [-1];        // sentinel so the root is never popped
        $i = 0;

        while ($i < $totalLines) {
            $originalLine = rtrim($lines[$i]);
            $contentLine = ltrim($originalLine);

            // Skip blank lines and full-line comments
            if ($contentLine === '' || $contentLine[0] === '#') {
                $i++;
                continue;
            }

            // Skip YAML document markers
            if ($contentLine === '---' || $contentLine === '...') {
                $i++;
                continue;
            }

            $indent = strlen($originalLine) - strlen($contentLine);

            // Strip trailing inline comment (only outside quoted strings)
            $contentLine = $this->stripInlineComment($contentLine);

            // Pop frames whose indent is >= current indent
            while ($indent <= end($indentStack) && count($stack) > 1) {
                array_pop($stack);
                array_pop($indentStack);
            }

            // ---- Block scalars: | and > --------------------------------
            if (preg_match('/^([^:]+):\s*([|>][+-]?)(\s*#.*)?$/', $contentLine, $bm)) {
                $key = trim($bm[1]);
                $indicator = rtrim($bm[2]);
                [$value, $consumed] = $this->parseBlockScalar($indicator, $indent, $i + 1, $lines);
                $stack[count($stack) - 1][$key] = $value;
                $i += $consumed + 1;
                continue;
            }

            // ---- Sequence item: starts with "- " -----------------------
            if (preg_match('/^-(\s+(.*))?$/', $contentLine, $sm)) {
                $rest = isset($sm[2]) ? trim($sm[2]) : '';

                if ($rest === '') {
                    // Empty item – next lines will be a nested mapping
                    $newItem = [];
                    $stack[count($stack) - 1][] = &$newItem;
                    $stack[] = &$newItem;
                    $indentStack[] = $indent;
                    unset($newItem);
                } elseif (preg_match('/^([^:]+):\s*(.*)$/', $rest, $kvm)) {
                    // Inline key-value under a sequence item
                    $newItem = [];
                    $newItem[trim($kvm[1])] = $this->parseValue(trim($kvm[2]));
                    $stack[count($stack) - 1][] = &$newItem;
                    $stack[] = &$newItem;
                    $indentStack[] = $indent + 1;
                    unset($newItem);
                } else {
                    $stack[count($stack) - 1][] = $this->parseValue($rest);
                }

                $i++;
                continue;
            }

            // ---- Key-value pair ----------------------------------------
            if (preg_match('/^([^:]+):\s*(.*)$/', $contentLine, $kv)) {
                $key = $this->resolveAnchorInKey(trim($kv[1]));
                $valueStr = trim($kv[2]);

                // Anchor on the key itself? (&name key: ...)
                // (unusual but legal; handled via resolveAnchorInKey)

                if ($valueStr === '') {
                    // Value is a nested mapping – push a new frame
                    $stack[count($stack) - 1][$key] = [];
                    $stack[] = &$stack[count($stack) - 1][$key];
                    $indentStack[] = $indent;
                } else {
                    // Detect anchor on value:  key: &anchor value
                    $anchorName = null;
                    if (preg_match('/^&([A-Za-z0-9_-]+)\s+(.*)$/', $valueStr, $am)) {
                        $anchorName = $am[1];
                        $valueStr = $am[2];
                    }

                    $parsed = $this->parseValue($valueStr);

                    if ($anchorName !== null) {
                        $this->anchors[$anchorName] = $parsed;
                    }

                    $stack[count($stack) - 1][$key] = $parsed;
                }

                $i++;
                continue;
            }

            $i++;
        }
    }

    /**
     * Collect the indented lines that form a block scalar.
     *
     * @param  string   $indicator  '|', '|-', '|+', '>', '>-', '>+'
     * @param  int      $keyIndent  Indentation of the key line
     * @param  int      $startLine  Index of the first content line
     * @param  string[] $lines      All document lines
     * @return array{0: string, 1: int}  [value, lines consumed]
     */
    private function parseBlockScalar(string $indicator, int $keyIndent, int $startLine, array $lines): array
    {
        $isFolded = str_starts_with($indicator, '>');
        $chomping = 'clip';  // default
        if (str_ends_with($indicator, '-')) {
            $chomping = 'strip';
        } elseif (str_ends_with($indicator, '+')) {
            $chomping = 'keep';
        }

        $blockIndent = null;
        $collected = [];
        $consumed = 0;
        $total = count($lines);

        for ($j = $startLine; $j < $total; $j++) {
            $raw = rtrim($lines[$j]);
            $trimmed = ltrim($raw);

            // Blank line inside block
            if ($trimmed === '') {
                $collected[] = '';
                $consumed++;
                continue;
            }

            $lineIndent = strlen($raw) - strlen($trimmed);

            // First non-empty line sets the block indentation
            if ($blockIndent === null) {
                if ($lineIndent <= $keyIndent) {
                    // No block content at all
                    break;
                }
                $blockIndent = $lineIndent;
            }

            // De-dented line ends the block
            if ($lineIndent < $blockIndent) {
                break;
            }

            $collected[] = substr($raw, $blockIndent);
            $consumed++;
        }

        // Apply chomping
        $value = $isFolded ? $this->foldLines($collected) : implode("\n", $collected);

        $value = match ($chomping) {
            'strip' => rtrim($value, "\n"),
            'keep' => $value,
            default => rtrim($value, "\n") . "\n",   // clip
        };

        return [$value, $consumed];
    }

    /**
     * Fold lines per YAML spec: single newlines become spaces,
     * multiple consecutive newlines are preserved as (n-1) newlines.
     *
     * @param string[] $lines
     */
    private function foldLines(array $lines): string
    {
        $result = '';
        $pending = 0;

        foreach ($lines as $line) {
            if ($line === '') {
                $pending++;
            } else {
                if ($pending > 0) {
                    $result .= str_repeat("\n", $pending);
                    $pending = 0;
                } elseif ($result !== '') {
                    $result .= ' ';
                }
                $result .= $line;
            }
        }

        if ($pending > 0) {
            $result .= str_repeat("\n", $pending);
        }

        return $result;
    }

    /**
     * Convert a YAML value token to the appropriate PHP type.
     *
     * Handles: booleans, null, integers, floats, aliases (*name),
     * inline sequences, inline mappings, quoted strings, bare strings.
     *
     * @return mixed
     */
    private function parseValue(string $value): mixed
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Alias: *anchorName
        if (str_starts_with($value, '*')) {
            $name = substr($value, 1);
            return $this->anchors[$name] ?? null;
        }

        // Inline sequence: [a, b, c]
        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            return $this->parseInlineSequence($value);
        }

        // Inline mapping: {key: val, ...}
        if (str_starts_with($value, '{') && str_ends_with($value, '}')) {
            return $this->parseInlineMapping($value);
        }

        // Double-quoted string – unescape standard escapes
        if (str_starts_with($value, '"') && str_ends_with($value, '"') && strlen($value) >= 2) {
            return $this->unescapeDoubleQuoted(substr($value, 1, -1));
        }

        // Single-quoted string – only '' -> ' inside
        if (str_starts_with($value, "'") && str_ends_with($value, "'") && strlen($value) >= 2) {
            return str_replace("''", "'", substr($value, 1, -1));
        }

        $lower = strtolower($value);

        if ($lower === 'true' || $lower === 'yes' || $lower === 'on') {
            return true;
        }
        if ($lower === 'false' || $lower === 'no' || $lower === 'off') {
            return false;
        }
        if ($lower === 'null' || $value === '~') {
            return null;
        }

        // Explicit float infinity / nan
        if ($lower === '.inf' || $lower === '+.inf') {
            return INF;
        }
        if ($lower === '-.inf') {
            return -INF;
        }
        if ($lower === '.nan') {
            return NAN;
        }

        // Hex / octal / binary literals
        if (preg_match('/^0x[0-9A-Fa-f]+$/', $value)) {
            return hexdec($value);
        }
        if (preg_match('/^0o[0-7]+$/', $value)) {
            return octdec(substr($value, 2));
        }
        if (preg_match('/^0b[01]+$/', $value)) {
            return bindec(substr($value, 2));
        }

        // Numeric – preserve int vs float distinction
        if (is_numeric($value)) {
            if (str_contains($value, '.') || str_contains(strtolower($value), 'e')) {
                return (float) $value;
            }
            return (int) $value;
        }

        return $value;
    }

    /**
     * Parse an inline YAML sequence: [val1, val2, ...]
     *
     * @return mixed[]
     */
    private function parseInlineSequence(string $raw): array
    {
        $inner = substr($raw, 1, -1);
        $result = [];

        foreach ($this->splitInlineItems($inner) as $item) {
            $result[] = $this->parseValue(trim($item));
        }

        return $result;
    }

    /**
     * Parse an inline YAML mapping: {key1: val1, key2: val2}
     *
     * @return mixed[]
     */
    private function parseInlineMapping(string $raw): array
    {
        $inner = substr($raw, 1, -1);
        $result = [];

        foreach ($this->splitInlineItems($inner) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            $colonPos = strpos($item, ':');
            if ($colonPos === false) {
                $result[$item] = null;
            } else {
                $k = trim(substr($item, 0, $colonPos));
                $v = trim(substr($item, $colonPos + 1));
                $result[$k] = $this->parseValue($v);
            }
        }

        return $result;
    }

    /**
     * Split comma-separated inline items while respecting nested brackets/braces
     * and quoted strings.
     *
     * @return string[]
     */
    private function splitInlineItems(string $input): array
    {
        $items = [];
        $depth = 0;
        $inSq = false;
        $inDq = false;
        $buf = '';
        $len = strlen($input);

        for ($i = 0; $i < $len; $i++) {
            $ch = $input[$i];

            if ($inDq) {
                $buf .= $ch;
                if ($ch === '\\') {
                    $buf .= $input[++$i] ?? '';
                } elseif ($ch === '"') {
                    $inDq = false;
                }
                continue;
            }

            if ($inSq) {
                $buf .= $ch;
                if ($ch === "'" && ($input[$i + 1] ?? '') !== "'") {
                    $inSq = false;
                } elseif ($ch === "'" && ($input[$i + 1] ?? '') === "'") {
                    $buf .= "'";
                    $i++;
                }
                continue;
            }

            if ($ch === '"') {
                $inDq = true;
                $buf .= $ch;
                continue;
            }
            if ($ch === "'") {
                $inSq = true;
                $buf .= $ch;
                continue;
            }
            if ($ch === '[' || $ch === '{') {
                $depth++;
                $buf .= $ch;
                continue;
            }
            if ($ch === ']' || $ch === '}') {
                $depth--;
                $buf .= $ch;
                continue;
            }

            if ($ch === ',' && $depth === 0) {
                $items[] = $buf;
                $buf = '';
                continue;
            }

            $buf .= $ch;
        }

        if ($buf !== '') {
            $items[] = $buf;
        }

        return $items;
    }

    /**
     * Remove a trailing inline comment from a content line.
     * A comment begins with " #" (space-hash) outside quoted regions.
     */
    private function stripInlineComment(string $line): string
    {
        $inDq = false;
        $inSq = false;
        $len = strlen($line);

        for ($i = 0; $i < $len; $i++) {
            $ch = $line[$i];

            if ($inDq) {
                if ($ch === '\\') {
                    $i++;
                    continue;
                }
                if ($ch === '"') {
                    $inDq = false;
                }
                continue;
            }
            if ($inSq) {
                if ($ch === "'" && ($line[$i + 1] ?? '') === "'") {
                    $i++;
                    continue;
                }
                if ($ch === "'") {
                    $inSq = false;
                }
                continue;
            }

            if ($ch === '"') {
                $inDq = true;
                continue;
            }
            if ($ch === "'") {
                $inSq = true;
                continue;
            }

            if ($ch === '#' && $i > 0 && $line[$i - 1] === ' ') {
                return rtrim(substr($line, 0, $i));
            }
        }

        return $line;
    }

    /**
     * Process escape sequences inside a double-quoted YAML string.
     */
    private function unescapeDoubleQuoted(string $s): string
    {
        return (string) preg_replace_callback(
            '/\\\\(u[0-9A-Fa-f]{4}|["\\\\\\/bfnrt])/',
            static function (array $m): string {
                return match ($m[1]) {
                    'n' => "\n",
                    't' => "\t",
                    'r' => "\r",
                    'b' => "\x08",
                    'f' => "\x0C",
                    'a' => "\x07",
                    '\\' => '\\',
                    '"' => '"',
                    '/' => '/',
                    default => (strlen($m[1]) === 5 && $m[1][0] === 'u')
                    ? mb_chr((int) hexdec(substr($m[1], 1)), 'UTF-8')
                    : $m[0],
                };
            },
            $s
        );
    }

    /**
     * Strip a leading anchor declaration from a key token.
     * e.g. "&baseKey myKey" → "myKey"  (anchor stored separately)
     */
    private function resolveAnchorInKey(string $key): string
    {
        if (preg_match('/^&([A-Za-z0-9_-]+)\s+(.+)$/', $key, $m)) {
            // Anchor on the key; we ignore key-anchors (uncommon)
            return $m[2];
        }

        return $key;
    }
}
