<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\CLI;

use Clover\Classes\OperationSystem;

class CLIStyle
{
    private bool $enabled;
    private int $termWidth;

    // ── Border presets ────────────────────────────────────────────
    public const BORDER_ROUNDED = ['╭', '─', '╮', '│', '╰', '─', '╯', '│', '├', '┤', '┬', '┴'];
    public const BORDER_SHARP = ['┌', '─', '┐', '│', '└', '─', '┘', '│', '├', '┤', '┬', '┴'];
    public const BORDER_DOUBLE = ['╔', '═', '╗', '║', '╚', '═', '╝', '║', '╠', '╣', '╦', '╩'];
    public const BORDER_HEAVY = ['┏', '━', '┓', '┃', '┗', '━', '┛', '┃', '┣', '┫', '┳', '┻'];
    public const BORDER_ASCII = ['+', '-', '+', '|', '+', '-', '+', '|', '+', '+', '+', '+'];

    // ── Status icons ──────────────────────────────────────────────
    public const ICON_OK = '✔';
    public const ICON_FAIL = '✘';
    public const ICON_WARN = '⚠';
    public const ICON_INFO = '●';
    public const ICON_ARROW = '›';
    public const ICON_BULLET = '•';
    public const ICON_TREE_MID = '├─ ';
    public const ICON_TREE_LAST = '└─ ';
    public const ICON_TREE_PIPE = '│  ';
    public const ICON_TREE_NONE = '   ';

    /**
     * Detects TTY capabilities and caches the terminal width for layout helpers.
     */
    public function __construct()
    {
        $this->enabled = $this->isTty();
        $this->termWidth = $this->detectTerminalWidth();
    }

    /**
     * Returns whether STDOUT is an interactive terminal that can render ANSI codes.
     *
     * @return bool
     */
    private function isTty(): bool
    {
        if (!defined('STDOUT') || !is_resource(STDOUT)) {
            return false;
        }
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDOUT);
        }
        if (function_exists('stream_isatty')) {
            return stream_isatty(STDOUT);
        }
        if (function_exists('sapi_windows_vt100_support')) {
            return sapi_windows_vt100_support(STDOUT);
        }
        $term = getenv('TERM');
        return $term !== false && $term !== '';
    }

    /**
     * Resolves the terminal column count using environment variables or shell tools.
     *
     * @param int $max      Upper bound for reported width.
     * @param int $fallback Width when detection fails.
     *
     * @return int
     */
    private function detectTerminalWidth(int $max = 240, int $fallback = 80): int
    {
        $cols = getenv('COLUMNS');
        if ($cols !== false && (int) $cols > 0) {
            return min((int) $cols, $max);
        }

        foreach ([['tput cols'], ['stty size'], ['mode CON']] as [$cmd]) {
            $out = [];
            $rc = 0;
            $out = OperationSystem::executeExternal($cmd, $rc, true);
            if ($rc !== 0 || empty($out)) {
                @exec($cmd . ' 2>NUL', $out, $rc);
            }
            if ($rc === 0 && !empty($out)) {
                if ($cmd === 'stty size') {
                    $parts = preg_split('/\s+/', trim($out[0]));
                    if (isset($parts[1]) && (int) $parts[1] > 0) {
                        return min((int) $parts[1], $max);
                    }
                } elseif (str_starts_with($cmd, 'mode')) {
                    foreach ($out as $line) {
                        if (preg_match('/Columns:\s*(\d+)/i', $line, $m)) {
                            return min((int) $m[1], $max);
                        }
                    }
                } elseif ((int) $out[0] > 0) {
                    return min((int) $out[0], $max);
                }
            }
        }
        return $fallback;
    }

    /**
     * Returns the cached terminal width used for layout.
     *
     * @return int
     */
    public function getTerminalWidth(): int
    {
        return $this->termWidth;
    }

    /**
     * Applies ANSI foreground, background, and text attributes when styling is enabled.
     *
     * @param int|string            $text  Raw text.
     * @param array|string|null     $fg    Foreground color name, hex, or RGB tuple.
     * @param array|string|null     $bg    Background color name, hex, or RGB tuple.
     * @param array<int, string>    $attrs Attribute names (bold, dim, underline, …).
     *
     * @return string
     */
    public function style(int|string $text, array|string|null $fg = null, array|string|null $bg = null, array $attrs = []): string
    {
        if (!$this->enabled) {
            return $text;
        }

        $attrMap = ['bold' => '1', 'dim' => '2', 'italic' => '3', 'underline' => '4', 'blink' => '5', 'inverse' => '7', 'strike' => '9'];
        $codes = array_filter(array_map(fn($a) => $attrMap[$a] ?? '', $attrs));

        if ($fg !== null && ($c = $this->colorCode($fg, false)) !== '') {
            $codes[] = $c;
        }
        if ($bg !== null && ($c = $this->colorCode($bg, true)) !== '') {
            $codes[] = $c;
        }

        return empty($codes) ? $text : "\033[" . implode(';', $codes) . "m{$text}\033[0m";
    }

    /**
     * Builds a 24-bit ANSI SGR segment for foreground or background color.
     *
     * @param array|string $color Color name, hex string, or RGB triple.
     * @param bool         $isBg  When true, emit background (48) codes instead of 38.
     *
     * @return string Empty string when the color cannot be resolved.
     */
    private function colorCode(array|string $color, bool $isBg): string
    {
        static $names = [
        'black' => [0, 0, 0],
        'red' => [205, 49, 49],
        'green' => [49, 205, 49],
        'yellow' => [205, 205, 49],
        'blue' => [49, 49, 205],
        'magenta' => [205, 49, 205],
        'cyan' => [49, 205, 205],
        'white' => [229, 229, 229],
        'gray' => [128, 128, 128],
        'darkgray' => [64, 64, 64],
        'brightRed' => [255, 85, 85],
        'brightGreen' => [85, 255, 85],
        'brightBlue' => [85, 85, 255],
        'brightYellow' => [255, 255, 85],
        'brightCyan' => [85, 255, 255],
        'brightMagenta' => [255, 85, 255],
        'orange' => [215, 135, 0],
        'pink' => [255, 175, 175],
        'teal' => [0, 175, 175],
        'lime' => [135, 215, 0],
        ];

        if (is_string($color)) {
            $c = trim($color);
            if (isset($names[$c])) {
                [$r, $g, $b] = $names[$c];
            } elseif (preg_match('/^#?([0-9a-fA-F]{6})$/', $c, $m)) {
                $r = hexdec(substr($m[1], 0, 2));
                $g = hexdec(substr($m[1], 2, 2));
                $b = hexdec(substr($m[1], 4, 2));
            } else {
                return '';
            }
        } elseif (is_array($color) && count($color) >= 3) {
            [$r, $g, $b] = array_map('intval', array_slice($color, 0, 3));
        } else {
            return '';
        }

        return ($isBg ? '48;2' : '38;2') . ';' . max(0, min(255, $r)) . ';' . max(0, min(255, $g)) . ';' . max(0, min(255, $b));
    }

    /**
     * Removes ANSI escape sequences from a string.
     *
     * @param string $s Input that may contain ANSI styling.
     *
     * @return string Plain text without escape codes.
     */
    public function stripAnsi(string $s): string
    {
        return preg_replace('/\x1B\[[0-9;]*[mGKHF]/', '', $s) ?? $s;
    }

    /**
     * Measures visible character width after stripping ANSI styling.
     *
     * @param int|string $s Text or numeric value rendered as text.
     *
     * @return int
     */
    public function visibleWidth(int|string $s): int
    {
        $plain = $this->stripAnsi((string) $s);
        return function_exists('mb_strwidth') ? mb_strwidth($plain) : strlen($plain);
    }

    // ── Text wrapping ─────────────────────────────────────────────

    /**
     * Wraps text to a maximum visible width, preserving ANSI sequences when present.
     *
     * @param int|string $text     Text to wrap.
     * @param int        $maxWidth Maximum visible width per line.
     *
     * @return array<int, int|string> Wrapped lines.
     */
    public function wrap(int|string $text, int $maxWidth): array
    {
        if ($this->visibleWidth($text) <= $maxWidth) {
            return [$text];
        }

        $result = [];
        $hasAnsi = $text !== $this->stripAnsi($text);

        if (!$hasAnsi) {
            $s = $text;
            while ($this->visibleWidth($s) > $maxWidth) {
                $chars = mb_str_split($s);
                $part = '';
                $i = 0;
                foreach ($chars as $ch) {
                    if ($this->visibleWidth($part . $ch) > $maxWidth) {
                        break;
                    }
                    $part .= $ch;
                    $i++;
                }
                $brk = mb_strrpos($part, ' ');
                if ($brk > 0) {
                    $result[] = rtrim(mb_substr($s, 0, $brk));
                    $s = ltrim(mb_substr($s, $brk));
                } else {
                    $result[] = $part;
                    $s = mb_substr($s, $i);
                }
            }
            if ($s !== '') {
                $result[] = $s;
            }
            return $result;
        }

        // ANSI-aware: char-by-char
        $chars = mb_str_split($text);
        $current = '';
        foreach ($chars as $ch) {
            $current .= $ch;
            if ($this->visibleWidth($current) > $maxWidth) {
                $plain = $this->stripAnsi($current);
                $brk = mb_strrpos($plain, ' ');
                if ($brk > 0) {
                    // rebuild prefix at brk
                    $prefix = '';
                    $acc = '';
                    foreach (mb_str_split($current) as $c2) {
                        $acc .= $c2;
                        if ($this->visibleWidth($this->stripAnsi($acc)) > $brk) {
                            break;
                        }
                        $prefix = $acc;
                    }
                    $result[] = rtrim($prefix);
                    $current = ltrim(mb_substr($current, mb_strlen($prefix)));
                } else {
                    $take = '';
                    $rest = $current;
                    foreach (mb_str_split($current) as $c2) {
                        if ($this->visibleWidth($take . $c2) > $maxWidth) {
                            break;
                        }
                        $take .= $c2;
                    }
                    $result[] = $take ?: mb_substr($current, 0, 1);
                    $current = mb_substr($current, mb_strlen($result[count($result) - 1]));
                }
            }
        }

        if ($current !== '') {
            $result[] = $current;
        }
        return $result;
    }

    /**
     * Pads text on the right until the visible width reaches the target total.
     *
     * @param int|string $text  Cell contents.
     * @param int        $total Target visible width.
     * @param string     $pad   Padding character.
     *
     * @return string
     */
    private function padRight(int|string $text, int $total, string $pad = ' '): string
    {
        $need = $total - $this->visibleWidth($text);
        return $need > 0 ? $text . str_repeat($pad, $need) : $text;
    }

    /**
     * Renders a horizontal rule across the terminal, optionally centered with a label.
     *
     * @param string               $label Optional title embedded in the rule.
     * @param array|string|null    $fg    Foreground color for the rule.
     * @param string               $char  Repeat character for the line.
     *
     * @return string Text including trailing newline.
     */
    public function rule(string $label = '', array|string|null $fg = 'gray', string $char = '─'): string
    {
        $width = $this->termWidth;
        if ($label === '') {
            return $this->style(str_repeat($char, $width), $fg) . PHP_EOL;
        }
        $visible = $this->visibleWidth($label);
        $half = (int) (($width - $visible - 2) / 2);
        $left = $half > 0 ? str_repeat($char, $half) : '';
        $right = str_repeat($char, $width - mb_strlen($left) - $visible - 2);
        $line = $left . ' ' . $label . ' ' . $right;
        return $this->style($line, $fg) . PHP_EOL;
    }

    /**
     * Renders compact pill-style text with a contrasting background.
     *
     * @param string               $text Label text.
     * @param array|string|null    $fg   Foreground color.
     * @param array|string|null    $bg   Background color.
     *
     * @return string
     */
    public function badge(string $text, array|string|null $fg = 'black', array|string|null $bg = 'white'): string
    {
        return $this->style(" {$text} ", $fg, $bg, ['bold']);
    }

    /**
     * Renders a status line with an icon prefix (ok, fail, warn, info).
     *
     * @param string $text Human-readable message.
     * @param string $type Icon and color preset key.
     *
     * @return string Text including trailing newline.
     */
    public function status(string $text, string $type = 'info'): string
    {
        $map = [
            'ok' => [self::ICON_OK, 'brightGreen', null],
            'fail' => [self::ICON_FAIL, 'brightRed', null],
            'warn' => [self::ICON_WARN, 'brightYellow', null],
            'info' => [self::ICON_INFO, 'brightBlue', null],
        ];
        [$icon, $color] = $map[$type] ?? $map['info'];
        return $this->style($icon . ' ', $color) . $text . PHP_EOL;
    }

    /**
     * Renders a filled/unfilled progress bar with a percentage label.
     *
     * @param float               $pct   Completion ratio between 0.0 and 1.0.
     * @param int                 $width Bar width in characters.
     * @param array|string|null   $fg    Filled segment color.
     * @param array|string|null   $bg    Empty segment color.
     *
     * @return string
     */
    public function progressBar(float $pct, int $width = 30, array|string|null $fg = 'brightGreen', array|string|null $bg = 'darkgray'): string
    {
        $pct = max(0.0, min(1.0, $pct));
        $filled = (int) round($pct * $width);
        $empty = $width - $filled;
        $bar = $this->style(str_repeat('█', $filled), $fg) . $this->style(str_repeat('░', $empty), $bg);
        $label = $this->style(sprintf(' %3d%%', (int) ($pct * 100)), 'white');
        return $bar . $label;
    }

    /**
     * Renders key/value pairs in aligned columns.
     *
     * @param array<string|int, mixed> $rows  Map of labels to values.
     * @param array|string|null        $keyFg Key column color.
     * @param array|string|null        $valFg Value column color.
     * @param string                   $sep   Separator between columns.
     *
     * @return string Text including trailing newlines per row.
     */
    public function kvTable(array $rows, array|string|null $keyFg = 'gray', array|string|null $valFg = 'white', string $sep = ' : '): string
    {
        if (empty($rows)) {
            return '';
        }
        $maxKey = max(array_map(fn($k) => $this->visibleWidth((string) $k), array_keys($rows)));
        $out = '';
        foreach ($rows as $k => $v) {
            $key = $this->padRight((string) $k, $maxKey);
            $out .= $this->style($key, $keyFg) . $this->style($sep, 'darkgray') . $this->style((string) $v, $valFg) . PHP_EOL;
        }
        return $out;
    }

    /**
     * $nodes = [['label'=>'...', 'children'=>[...]], ...]
     */
    public function tree(array $nodes, array|string|null $fg = 'white', string $prefix = ''): string
    {
        $out = '';
        $last = count($nodes) - 1;
        foreach ($nodes as $i => $node) {
            $isLast = $i === $last;
            $connector = $isLast ? self::ICON_TREE_LAST : self::ICON_TREE_MID;
            $childPfx = $prefix . ($isLast ? self::ICON_TREE_NONE : self::ICON_TREE_PIPE);
            $out .= $prefix . $this->style($connector, 'gray') . $this->style($node['label'], $fg) . PHP_EOL;
            if (!empty($node['children'])) {
                $out .= $this->tree($node['children'], $fg, $childPfx);
            }
        }
        return $out;
    }

    /**
     * @param array       $lines    Content lines (may contain pre-styled text)
     * @param array|null  $meta     Optional right-aligned meta string per line (same count as $lines)
     * @param array       $border   One of the BORDER_* constants
     */
    public function box(string $title, array $lines, array|string|null $fg = 'white', array|string|null $bg = null, int $padding = 1, array $border = self::BORDER_ROUNDED, ?string $subtitle = null, ?string $footer = null, ): string
    {
        [$tl, $th, $tr, $sr, $bl, $bh, $br, $sl, $ml, $mr] = array_slice($border, 0, 10);

        $maxInner = max(10, $this->termWidth - 2);

        // compute natural width
        $nat = $this->visibleWidth($title);
        if ($subtitle) {
            $nat = max($nat, $this->visibleWidth($subtitle));
        }
        foreach ($lines as $l) {
            $nat = max($nat, $this->visibleWidth($l));
        }

        $inner = min($nat + $padding * 2, $maxInner);

        $S = fn(string $t, array $a = []) => $this->style($t, $fg, $bg, $a);
        $SN = fn(string $t, array $a = []) => $this->style($t, $fg, null, $a); // no bg for content

        $out = $S($tl . str_repeat($th, $inner) . $tr) . PHP_EOL;

        // title
        $titleTrunc = $this->truncate($title, $inner - $padding * 2);
        $tLen = $this->visibleWidth($titleTrunc);
        $lPad = (int) (($inner - $tLen) / 2);
        $rPad = $inner - $lPad - $tLen;
        $out .= $S($sl . str_repeat(' ', $lPad) . $titleTrunc . str_repeat(' ', $rPad) . $sr, ['bold']) . PHP_EOL;

        // optional subtitle
        if ($subtitle !== null) {
            $sub = $this->truncate($subtitle, $inner - $padding * 2);
            $sLen = $this->visibleWidth($sub);
            $lS = (int) (($inner - $sLen) / 2);
            $rS = $inner - $lS - $sLen;
            $out .= $S($sl . str_repeat(' ', $lS) . $sub . str_repeat(' ', $rS) . $sr, ['dim']) . PHP_EOL;
        }

        // separator
        $out .= $S($ml . str_repeat($th, $inner) . $mr) . PHP_EOL;

        // content
        $availW = $inner - $padding * 2;
        foreach ($lines as $line) {
            if ($line === '') {
                $out .= $S($sl . str_repeat(' ', $inner) . $sr) . PHP_EOL;
                continue;
            }

            foreach ($this->wrap($line, $availW) as $wline) {
                $padded = $this->padRight($wline, $availW);
                $out .= $S($sl . str_repeat(' ', $padding)) . $padded . $S(str_repeat(' ', $padding) . $sr) . PHP_EOL;
            }
        }

        // footer
        if ($footer !== null) {
            $out .= $S($ml . str_repeat($th, $inner) . $mr) . PHP_EOL;
            $fTrunc = $this->truncate($footer, $inner - $padding * 2);
            $fLen = $this->visibleWidth($fTrunc);
            $lF = $inner - $fLen - $padding;
            $out .= $S($sl . str_repeat(' ', max(0, $lF)) . $fTrunc . str_repeat(' ', $padding) . $sr, ['dim']) . PHP_EOL;
        }

        $out .= $S($bl . str_repeat($bh, $inner) . $br) . PHP_EOL;
        return $out;
    }

    /** Render $items side-by-side, equally spaced */
    public function columns(array $items, int $gap = 2): string
    {
        $n = count($items);
        if ($n === 0) {
            return '';
        }
        $colW = (int) (($this->termWidth - $gap * ($n - 1)) / $n);
        $rows = array_map(fn($item) => is_array($item) ? $item : [$item], $items);
        $maxRows = max(array_map('count', $rows));
        $out = '';
        for ($r = 0; $r < $maxRows; $r++) {
            $parts = [];
            foreach ($rows as $col) {
                $cell = $col[$r] ?? '';
                $parts[] = $this->padRight($cell, $colW);
            }
            $out .= implode(str_repeat(' ', $gap), $parts) . PHP_EOL;
        }
        return $out;
    }

    /**
     * Renders a stack trace with tree-style connectors.
     *
     * Each $frame:
     *   ['text'=>?, 'annotation'=>?, 'file'=>?, 'line'=>?, 'class'=>?, 'comment'=>?]
     */
    public function traceBlock(array $frames, string $heading = 'Stack Trace'): string
    {
        $out = '';
        $out .= $this->rule($this->style($heading, 'brightBlue', null, ['bold']), 'gray', '─');

        $last = count($frames) - 1;
        foreach ($frames as $i => $f) {
            $isLast = $i === $last;
            $conn = $this->style($isLast ? self::ICON_TREE_LAST : self::ICON_TREE_MID, 'gray');
            $pipe = $this->style($isLast ? self::ICON_TREE_NONE : self::ICON_TREE_PIPE, 'gray');

            // ── frame header ──────────────────────────────────────
            $header = '';
            if (!empty($f['text'])) {
                $header .= $this->badge($f['text'], 'white', [180, 0, 0]) . ' ';
            }
            if (!empty($f['annotation'])) {
                $header .= $this->badge($f['annotation'], 'black', 'brightYellow') . ' ';
            }
            $frameIdx = $this->style("#{$i}", 'darkgray', null, ['dim']);
            $out .= $conn . $frameIdx . ' ' . ($header ?: $this->style('(anonymous)', 'darkgray')) . PHP_EOL;

            // ── location ──────────────────────────────────────────
            if (!empty($f['file'])) {
                $loc = $f['file'] . (!empty($f['line']) ? ':' . $f['line'] : '');
                $out .= $pipe . $this->style('   at ', 'darkgray') . $this->style($loc, 'gray', null, ['underline']) . PHP_EOL;
            } elseif (!empty($f['class'])) {
                $out .= $pipe . $this->style('   in ', 'darkgray') . $this->style($f['class'], 'magenta') . PHP_EOL;
            }

            // ── comment ───────────────────────────────────────────
            if (!empty($f['comment'])) {
                $out .= $pipe . $this->style('   ' . self::ICON_ARROW . ' ', 'darkgray') . $this->style($f['comment'], 'teal') . PHP_EOL;
            }

            if (!$isLast) {
                $out .= $pipe . PHP_EOL;
            }
        }

        return $out;
    }

    /**
     * Truncates text to a maximum visible width, preserving ANSI when shorter than max.
     *
     * @param string $text     Input text.
     * @param int    $max      Maximum visible width including ellipsis.
     * @param string $ellipsis Trailing ellipsis characters.
     *
     * @return string
     */
    private function truncate(string $text, int $max, string $ellipsis = '…'): string
    {
        if ($this->visibleWidth($text) <= $max) {
            return $text;
        }
        $plain = $this->stripAnsi($text);
        $avail = max(0, $max - $this->visibleWidth($ellipsis));
        return mb_substr($plain, 0, $avail) . $ellipsis;
    }
}
