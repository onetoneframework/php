<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Markdown;

#region use

use DOMNode;
use function count;
use function in_array;
use function strlen;
use function array_slice;

#endregion

/**
 * Markdown to HTML converter supporting CommonMark, GFM, and extended syntax.
 */
class Markdown
{
    #region properties

    /** @var array<string, string> Compiled inline regex patterns */
    private array $inlinePatterns;

    /** @var array<string, array{url: string, title: ?string}> Reference link definitions */
    private array $references = [];

    /** @var array<string, string> Footnote definitions keyed by ID */
    private array $footnotes = [];

    /** @var int Running counter for rendered footnote references */
    private int $footnoteCounter = 0;

    /** @var bool Strip disallowed HTML tags and dangerous attributes */
    private bool $safeMode = false;

    /** @var string[] Whitelist of HTML tag names allowed in safe mode */
    private array $allowedTags = [];

    /** @var array<string, string> Abbreviation definitions (abbr => full text) */
    private array $abbreviations = [];

    /** @var array<string, int> Tracks heading ID usage counts for deduplication */
    private array $headingIds = [];

    /** @var array<string, string> Temporary storage for escape token restoration */
    private array $escapeTokens = [];

    /** @var array<string, string> Temporary storage for inline math token restoration */
    private array $mathTokens = [];

    /** @var int Maximum nesting depth for recursive block parsing */
    private int $maxDepth = 10;

    /** @var array<string, mixed> YAML front matter key-value pairs */
    private array $frontMatter = [];

    /** @var array<string, string> Temporary storage for inline code token restoration */
    private array $inlineCodeTokens = [];

    private static ?array $emojiMap = null;

    #endregion

    #region function

    /**
     * @param bool   $safeMode    Enable HTML sanitization
     * @param string[] $allowedTags Tag whitelist for safe mode (empty = sensible defaults)
     */
    public function __construct(bool $safeMode = false, array $allowedTags = [])
    {
        $this->safeMode = $safeMode;
        $this->allowedTags = $allowedTags;
        $this->initializeInlinePatterns();
    }

    /** @return void */
    private function initializeInlinePatterns(): void
    {
        $this->inlinePatterns = [
            'escape' => '/\\\\([\\\\`*_{}[\]()#+\-.!|~^$:])/',
            'autolink_url' => '/<(https?:\/\/[^>]+)>/',
            'autolink_email' => '/<([^>]+@[^>]+)>/',
            'bare_url' => '/(?<!["\(<])(https?:\/\/[^\s<>\)\]]+)/',
            'bold_italic_asterisk' => '/\*\*\*(?=\S)(.+?)(?<=\S)\*\*\*/',
            'bold_italic_underscore' => '/___(?=\S)(.+?)(?<=\S)___/',
            'bold_asterisk' => '/\*\*(?=\S)(.+?)(?<=\S)\*\*/',
            'bold_underscore' => '/__(?=\S)(.+?)(?<=\S)__/',
            'italic_asterisk' => '/\*(?=\S)(.+?)(?<=\S)\*/',
            'italic_underscore' => '/(?<![a-zA-Z0-9])_(?=\S)(.+?)(?<=\S)_(?![a-zA-Z0-9])/',
            'strikethrough' => '/~~(?=\S)(.+?)(?<=\S)~~/',
            'highlight' => '/==(?=\S)(.+?)(?<=\S)==/',
            'insert' => '/\+\+(?=\S)(.+?)(?<=\S)\+\+/',
            'subscript' => '/~(?!~)(?=\S)([^~]+?)(?<=\S)~(?!~)/',
            'superscript' => '/\^(?!\^)(?=\S)([^\^]+?)(?<=\S)\^(?!\^)/',
            'image_with_size' => '/!\[([^\]]*)\]\(([^\s\)]+?)(?:\s+=(\d+)?x(\d+)?)?\s*(?:"([^"]*)")?\)/',
            'link_with_title' => '/\[([^\]]+)\]\(([^\s\)]+)(?:\s+"([^"]*)")?\)/',
            'reference_link' => '/\[([^\]]+)\]\[([^\]]*)\]/',
            'reference_image' => '/!\[([^\]]*)\]\[([^\]]*)\]/',
            'wikilink' => '/\[\[([^\]|]+?)(?:\|([^\]]+?))?\]\]/',
            'collapsed_ref_link' => '/\[([^\]]+)\]\[\]/',
            'shortcut_ref_link' => '/(?<!!)\[([^\]\[^]+)\](?!\[|\()/',
            'collapsed_ref_image' => '/!\[([^\]]*)\]\[\]/',
            'shortcut_ref_image' => '/!\[([^\]]+)\](?!\[|\()/',
            'kbd' => '/<<([^<>]+)>>/',
            'emoji' => '/:([a-zA-Z0-9_+\-]+):/',
            'footnote_ref' => '/\[\^([^\]]+)\]/',
            'line_break_spaces' => '/  +$/',
            'line_break_backslash' => '/\\\\\n/',
        ];
    }

    /**
     * Convert a Markdown string to HTML.
     *
     * @param  string $markdown Raw Markdown source
     * @return string Rendered HTML
     */
    public function convert(string $markdown): string
    {
        $this->references = [];
        $this->footnotes = [];
        $this->footnoteCounter = 0;
        $this->abbreviations = [];
        $this->headingIds = [];
        $this->frontMatter = [];

        $markdown = $this->normalizeLineEndings($markdown);
        $markdown = $this->extractFrontMatter($markdown);
        $markdown = $this->extractReferences($markdown);
        $markdown = $this->extractFootnotes($markdown);
        $markdown = $this->extractAbbreviations($markdown);

        $blocks = $this->parseBlocks($markdown);
        $html = $this->renderBlocks($blocks);
        $html = $this->appendFootnotes($html);
        $html = $this->applyAbbreviations($html);
        $html = $this->replaceTocMarker($html, $markdown);

        if ($this->safeMode) {
            $html = $this->sanitizeHtml($html);
        }

        return $this->cleanOutput($html);
    }

    /**
     * Normalize \r\n and \r to \n.
     *
     * @param  string $text
     * @return string
     */
    private function normalizeLineEndings(string $text): string
    {
        return str_replace(["\r\n", "\r"], "\n", $text);
    }

    private function extractFrontMatter(string $text): string
    {
        if (!preg_match('/\A---\n(.*?)\n---\n/s', $text, $m)) {
            return $text;
        }

        $yaml = $m[1];
        foreach (explode("\n", $yaml) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, ':');
            if ($pos !== false) {
                $key = trim(substr($line, 0, $pos));
                $value = trim(substr($line, $pos + 1));
                $value = trim($value, '"\'');
                $this->frontMatter[$key] = $value;
            }
        }

        return substr($text, strlen($m[0]));
    }

    /**
     * Get the parsed YAML front matter as an associative array.
     *
     * @return array<string, string> Front matter key-value pairs
     */
    public function getFrontMatter(): array
    {
        return $this->frontMatter;
    }

    /**
     * Extract and remove reference link definitions from source.
     *
     * Pattern: `[id]: url "optional title"`
     *
     * @param  string $text
     * @return string Text with reference definitions removed
     */
    private function extractReferences(string $text): string
    {
        $pattern = '/^\[([^\]]+)\]:\s*<?([^\s>]+)>?(?:\s+["\']([^"\']+)["\'])?\s*$/m';
        return preg_replace_callback($pattern, function ($m) {
            $id = strtolower($m[1]);
            $this->references[$id] = [
                'url' => $m[2],
                'title' => $m[3] ?? null,
            ];
            return '';
        }, $text);
    }

    /**
     * Extract and remove footnote definitions from source.
     *
     * Pattern: `[^id]: content` (may span multiple indented lines)
     *
     * @param  string $text
     * @return string Text with footnote definitions removed
     */
    private function extractFootnotes(string $text): string
    {
        $pattern = '/^\[\^([^\]]+)\]:\s*(.+?)(?=\n\[\^|\n\n|\z)/ms';
        return preg_replace_callback($pattern, function ($m) {
            $content = trim($m[2]);
            $content = preg_replace('/\n\s{4}/', "\n", $content);
            $this->footnotes[$m[1]] = $content;
            return '';
        }, $text);
    }

    /**
     * Extract and remove abbreviation definitions from source.
     *
     * Pattern: `*[ABBR]: Full Text`
     *
     * @param  string $text
     * @return string Text with abbreviation definitions removed
     */
    private function extractAbbreviations(string $text): string
    {
        $pattern = '/^\*\[([^\]]+)\]:\s*(.+)$/m';
        return preg_replace_callback($pattern, function ($m) {
            $this->abbreviations[$m[1]] = $m[2];
            return '';
        }, $text);
    }

    /**
     * Split Markdown text into an ordered list of block-level structures.
     *
     * @param string $text
     * @return array<int, array<string, mixed>>
     */
    private function parseBlocks(string $text): array
    {
        $lines = explode("\n", $text);
        $blocks = [];
        $i = 0;
        $len = count($lines);

        while ($i < $len) {
            $line = $lines[$i];
            $trimmed = trim($line);

            if ($trimmed === '') {
                $i++;
                continue;
            }

            if (preg_match('/^(`{3,})(\w*)(.*)$/', $trimmed, $m)) {
                $fence = str_repeat('`', strlen($m[1]));
                $lang = trim($m[2]);
                $meta = trim($m[3]);
                $title = '';
                if (preg_match('/title\s*=\s*"([^"]*)"/', $meta, $tm)) {
                    $title = $tm[1];
                }
                $result = $this->parseFencedCode($lines, $i + 1, $lang, $fence, $title);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^(~{3,})(\w*)(.*)$/', $trimmed, $m)) {
                $fence = str_repeat('~', strlen($m[1]));
                $lang = trim($m[2]);
                $meta = trim($m[3]);
                $title = '';
                if (preg_match('/title\s*=\s*"([^"]*)"/', $meta, $tm)) {
                    $title = $tm[1];
                }
                $result = $this->parseFencedCode($lines, $i + 1, $lang, $fence, $title);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^\$\$(.+)\$\$\s*$/', $trimmed, $m)) {
                $blocks[] = ['type' => 'math_display', 'content' => trim($m[1])];
                $i++;
                continue;
            }

            if ($trimmed === '$$') {
                $result = $this->parseMathBlock($lines, $i + 1);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^\\\\\[(.+)\\\\\]\s*$/', $trimmed, $m)) {
                $blocks[] = ['type' => 'math_display', 'content' => trim($m[1])];
                $i++;
                continue;
            }

            if ($trimmed === '\\[') {
                $result = $this->parseMathBlock($lines, $i + 1, '\\]');
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^:{3,}(.*)$/', $trimmed, $m)) {
                $result = $this->parseFencedDiv($lines, $i, trim($m[1]));
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^\[TOC\]$/i', $trimmed)) {
                $blocks[] = ['type' => 'toc'];
                $i++;
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+?)(?:\s+#+)?$/', $trimmed, $m)) {
                $headingContent = trim($m[2]);
                $headingAttrs = [];
                $headingCustomId = '';
                $headingClassName = '';
                if (preg_match('/\s*\{([^}]+)\}\s*$/', $headingContent, $am)) {
                    $this->parseAttrString($am[1], $headingCustomId, $headingClassName, $headingAttrs);
                    $headingContent = rtrim(substr($headingContent, 0, -strlen($am[0])));
                }
                $blocks[] = [
                    'type' => 'heading',
                    'level' => strlen($m[1]),
                    'content' => $headingContent,
                    'id' => $headingCustomId !== '' ? $headingCustomId : $this->generateUniqueHeadingId($headingContent),
                    'className' => $headingClassName,
                    'attrs' => $headingAttrs,
                ];
                $i++;
                continue;
            }

            if (
                isset($lines[$i + 1])
                && preg_match('/^(={3,}|-{3,})\s*$/', trim($lines[$i + 1]))
                && !preg_match('/^(#{1,6}\s|[-*+]\s|\d+[.)]\s|```|~~~|>|\||---+|\*\*\*+|___+|<\w)/', $trimmed)
            ) {
                $level = trim($lines[$i + 1])[0] === '=' ? 1 : 2;
                $setextContent = $trimmed;
                $setextAttrs = [];
                $setextCustomId = '';
                $setextClassName = '';
                if (preg_match('/\s*\{([^}]+)\}\s*$/', $setextContent, $am)) {
                    $this->parseAttrString($am[1], $setextCustomId, $setextClassName, $setextAttrs);
                    $setextContent = rtrim(substr($setextContent, 0, -strlen($am[0])));
                }
                $blocks[] = [
                    'type' => 'heading',
                    'level' => $level,
                    'content' => $setextContent,
                    'id' => $setextCustomId !== '' ? $setextCustomId : $this->generateUniqueHeadingId($setextContent),
                    'className' => $setextClassName,
                    'attrs' => $setextAttrs,
                ];
                $i += 2;
                continue;
            }

            if (preg_match('/^( {4}|\t)/', $line) && !$this->isPreviousBlockList($blocks)) {
                $result = $this->parseIndentedCode($lines, $i);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^\|.+\|/', $trimmed) || (str_contains($trimmed, '|') && isset($lines[$i + 1]) && preg_match('/^\|?[\s\-:|]+\|[\s\-:|]*$/', trim($lines[$i + 1])))) {
                $result = $this->parseTable($lines, $i);
                if ($result) {
                    $blocks[] = $result['block'];
                    $i = $result['index'];
                    continue;
                }
            }

            if (preg_match('/^([-*+]|\d+[.)]) \[[ xX]\]/', $trimmed)) {
                $result = $this->parseTaskList($lines, $i);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^([-*+]|\d+[.)])\s+/', $trimmed)) {
                $result = $this->parseList($lines, $i);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^>\s?/', $trimmed)) {
                $result = $this->parseBlockquote($lines, $i);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^(---+|\*\*\*+|___+)$/', $trimmed)) {
                $blocks[] = ['type' => 'hr'];
                $i++;
                continue;
            }

            // HTML comment block (<!-- ... -->)
            if (str_starts_with($trimmed, '<!--')) {
                $result = $this->parseHtmlComment($lines, $i);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^<(address|article|aside|blockquote|details|dialog|dd|div|dl|dt|fieldset|figcaption|figure|footer|form|h[1-6]|header|hgroup|hr|li|main|nav|ol|p|pre|section|table|ul)(\s|>|$)/i', $trimmed)) {
                $result = $this->parseHtmlBlock($lines, $i);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (
                isset($lines[$i + 1])
                && preg_match('/^:\s+/', trim($lines[$i + 1]))
                && !preg_match('/^([-*+]|\d+[.)])\s/', $trimmed)
            ) {
                $result = $this->parseDefinitionList($lines, $i);
                if ($result) {
                    $blocks[] = $result['block'];
                    $i = $result['index'];
                    continue;
                }
            }

            $prevI = $i;
            $result = $this->parseParagraph($lines, $i);
            $blocks[] = $result['block'];
            $i = $result['index'];
            if ($i <= $prevI) {
                $i = $prevI + 1;
            }
        }

        return $blocks;
    }

    /**
     * Check if the previous block in the list is a list (ordered, unordered, or task list).
      *
     * @param  array<int, array<string, mixed>> $blocks
     * @return bool True if the last block is a list, false otherwise
     */
    private function isPreviousBlockList(array $blocks): bool
    {
        if (empty($blocks)) {
            return false;
        }
        $last = end($blocks);
        return $last['type'] === 'list' || $last['type'] === 'tasklist';
    }

    /**
     * Parse a display math block delimited by `$$` on separate lines.
     *
     * @param  string[] $lines
     * @param  int      $start Index after the opening `$$`
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseMathBlock(array $lines, int $start, string $closer = '$$'): array
    {
        $math = '';
        $i = $start;
        $len = count($lines);

        while ($i < $len) {
            if (trim($lines[$i]) === $closer) {
                $i++;
                break;
            }
            $math .= ($math !== '' ? "\n" : '') . $lines[$i];
            $i++;
        }

        return [
            'block' => ['type' => 'math_display', 'content' => trim($math)],
            'index' => $i,
        ];
    }

    /**
     * Parse a fenced div block delimited by `:::` on separate lines.
     *
     * Supports nested containers via depth tracking.
     * Info string may be a class name or `{#id .class attr=val}` syntax.
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the opening `:::` line
     * @param  string   $info  Info string after the fence markers
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseFencedDiv(array $lines, int $start, string $info): array
    {
        $trimmedStart = trim($lines[$start]);
        preg_match('/^(:{3,})/', $trimmedStart, $fenceMatch);
        $fenceLen = strlen($fenceMatch[1] ?? ':::');

        $className = '';
        $id = '';
        $attrs = [];
        if ($info !== '') {
            if (preg_match('/^\{([^}]*)\}$/', $info, $am)) {
                $this->parseAttrString($am[1], $id, $className, $attrs);
            } else {
                $className = $info;
            }
        }

        $content = '';
        $i = $start + 1;
        $len = count($lines);
        $depth = 1;

        while ($i < $len) {
            $t = trim($lines[$i]);
            if (preg_match('/^:{3,}[^:]*$/', $t)) {
                if (preg_match('/^:{3,}$/', $t)) {
                    $depth--;
                    if ($depth === 0) {
                        $i++;
                        break;
                    }
                } else {
                    $depth++;
                }
            }
            $content .= $lines[$i] . "\n";
            $i++;
        }

        $nestedBlocks = $this->parseBlocks(rtrim($content));

        return [
            'block' => [
                'type' => 'container',
                'className' => $className,
                'id' => $id,
                'attrs' => $attrs,
                'blocks' => $nestedBlocks,
            ],
            'index' => $i,
        ];
    }

    /**
     * Parse an attribute string `{#id .class key=val}` into its components.
     *
     * @param  string   $attrStr   Raw attribute string without braces
     * @param  string   &$id       Receives the parsed `#id` value
     * @param  string   &$className Receives space-joined `.class` values
     * @param  array    &$attrs    Receives key=value pairs
     * @return void
     */
    private function parseAttrString(string $attrStr, string &$id, string &$className, array &$attrs): void
    {
        $parts = preg_split('/\s+/', trim($attrStr));
        $classes = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if ($part[0] === '#') {
                $id = substr($part, 1);
            } elseif ($part[0] === '.') {
                $classes[] = substr($part, 1);
            } elseif (str_contains($part, '=')) {
                [$k, $v] = explode('=', $part, 2);
                $attrs[trim($k)] = trim($v, '"\'');
            }
        }
        $className = implode(' ', $classes);
    }

    /**
     * Parse a fenced code block starting from the line after the opening fence.
     * Supports both backtick and tilde fences, with optional language info.
     * Handles nested fences of the same type by requiring a closing fence of equal or greater length.
     * The content is collected verbatim until a closing fence is found, allowing for any characters within the code block.
     * The returned block includes the code content, the specified language (if any), and the type 'code'.
     * The index returned points to the line immediately following the closing fence, ready for further parsing.
     * This method is designed to be robust against edge cases such as code blocks that contain fence-like sequences, by ensuring that only a properly formatted closing fence will terminate the block.
     * 
     * @param  string[] $lines  All document lines
     * @param  int      $start  Index of the first content line (after opening fence)
     * @param  string   $lang   Language identifier from the info string
     * @param  string   $fence  The fence string to match for closing (e.g. "```" or "~~~~")
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseFencedCode(array $lines, int $start, string $lang, string $fence = '```', string $title = ''): array
    {
        $code = '';
        $i = $start;
        $len = count($lines);
        $fenceChar = $fence[0];
        $fenceLen = strlen($fence);

        while ($i < $len) {
            $trimmedLine = trim($lines[$i]);
            if (
                $trimmedLine !== ''
                && $trimmedLine[0] === $fenceChar
                && preg_match('/^' . preg_quote($fenceChar, '/') . '{' . $fenceLen . ',}\s*$/', $trimmedLine)
            ) {
                $i++;
                break;
            }
            $code .= $lines[$i] . "\n";
            $i++;
        }

        return [
            'block' => [
                'type' => 'code',
                'lang' => $lang !== '' ? $lang : null,
                'content' => rtrim($code),
                'title' => $title !== '' ? $title : null,
            ],
            'index' => $i,
        ];
    }

    /**
     * Parse an indented code block (4 spaces or 1 tab).
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the first indented line
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseIndentedCode(array $lines, int $start): array
    {
        $code = '';
        $i = $start;
        $len = count($lines);

        while ($i < $len) {
            $line = $lines[$i];
            if (preg_match('/^( {4}|\t)(.*)$/', $line, $m)) {
                $code .= $m[2] . "\n";
                $i++;
            } elseif (trim($line) === '') {
                $code .= "\n";
                $i++;
            } else {
                break;
            }
        }

        return [
            'block' => [
                'type' => 'code',
                'lang' => null,
                'content' => rtrim($code),
            ],
            'index' => $i,
        ];
    }

    /**
     * Parse a GFM-style table starting from the header row.
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the header row
     * @return null|array{block: array<string, mixed>, index: int} Null if not a valid table
     */
    private function parseTable(array $lines, int $start): ?array
    {
        $headerLine = trim($lines[$start]);
        if (!isset($lines[$start + 1])) {
            return null;
        }

        $separatorLine = trim($lines[$start + 1]);
        if (!preg_match('/^\|?[\s\-:|]+\|[\s\-:|]*$/', $separatorLine)) {
            return null;
        }

        $alignments = $this->parseTableAlignments($separatorLine);
        $headers = $this->parseTableRow($headerLine);

        if (count($headers) !== count($alignments)) {
            return null;
        }

        $rows = [];
        $i = $start + 2;
        $len = count($lines);

        while ($i < $len && preg_match('/^\|.+\|?$/', trim($lines[$i]))) {
            $row = $this->parseTableRow(trim($lines[$i]));
            while (count($row) < count($headers)) {
                $row[] = '';
            }
            $rows[] = array_slice($row, 0, count($headers));
            $i++;
        }

        return [
            'block' => [
                'type' => 'table',
                'headers' => $headers,
                'alignments' => $alignments,
                'rows' => $rows,
            ],
            'index' => $i,
        ];
    }

    /**
     * Derive column alignments from the separator row.
     *
     * @param  string $separator  e.g. `|:---|:---:|---:|`
     * @return array<int, ?string> Alignment per column: 'left', 'center', 'right', or null
     */
    private function parseTableAlignments(string $separator): array
    {
        $separator = trim($separator, '|');
        $cells = explode('|', $separator);
        $alignments = [];

        foreach ($cells as $cell) {
            $cell = trim($cell);
            $left = str_starts_with($cell, ':');
            $right = str_ends_with($cell, ':');

            if ($left && $right) {
                $alignments[] = 'center';
            } elseif ($right) {
                $alignments[] = 'right';
            } elseif ($left) {
                $alignments[] = 'left';
            } else {
                $alignments[] = null;
            }
        }

        return $alignments;
    }

    /**
     * Split a pipe-delimited table row into cell strings,
     * respecting escaped pipes and inline code spans.
     *
     * @param  string $row The raw row string, e.g. `| Cell 1 | Cell \| with pipe | \`Code | Span\` |`
     * @return string[] Array of cell contents, e.g. `['Cell 1', 'Cell | with pipe', '`Code | Span`']`
     */
    private function parseTableRow(string $row): array
    {
        $row = trim($row);
        if (str_starts_with($row, '|')) {
            $row = substr($row, 1);
        }
        if (str_ends_with($row, '|') && !str_ends_with($row, '\\|')) {
            $row = substr($row, 0, -1);
        }

        $cells = [];
        $current = '';
        $escaped = false;
        $inCode = false;

        for ($i = 0, $len = strlen($row); $i < $len; $i++) {
            $char = $row[$i];

            if ($escaped) {
                $current .= $char;
                $escaped = false;
                continue;
            }

            if ($char === '\\') {
                $escaped = true;
                $current .= $char;
                continue;
            }

            if ($char === '`') {
                $inCode = !$inCode;
                $current .= $char;
                continue;
            }

            if ($char === '|' && !$inCode) {
                $cells[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        $cells[] = trim($current);
        return $cells;
    }

    /**
     * Parse a task list (list items with `[x]` or `[ ]` checkboxes).
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the first task-list item
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseTaskList(array $lines, int $start): array
    {
        $items = [];
        $i = $start;
        $len = count($lines);

        while ($i < $len) {
            $trimmed = trim($lines[$i]);

            if (preg_match('/^([-*+]|\d+[.)]) \[([ xX])\]\s*(.*)$/', $trimmed, $m)) {
                $items[] = [
                    'checked' => strtolower($m[2]) === 'x',
                    'content' => $m[3],
                ];
                $i++;
            } else {
                break;
            }
        }

        return [
            'block' => ['type' => 'tasklist', 'items' => $items],
            'index' => $i,
        ];
    }

    /**
     * Parse an ordered or unordered list, including nested sub-lists.
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the first list item
     * @param  int      $depth Current recursion depth
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseList(array $lines, int $start, int $depth = 0): array
    {
        if ($depth > $this->maxDepth) {
            return [
                'block' => ['type' => 'list', 'ordered' => false, 'start' => null, 'items' => []],
                'index' => $start + 1,
            ];
        }

        $baseIndent = $this->measureIndent($lines[$start]);
        $firstLine = trim($lines[$start]);
        preg_match('/^([-*+]|\d+[.)])\s+/', $firstLine, $m);
        $marker = $m[1];
        $ordered = is_numeric($marker[0]);
        $startNum = $ordered ? (int) $marker : null;
        $contentIndent = $baseIndent + strlen($m[0]);

        $items = [];
        $currentItem = [];
        $i = $start;
        $len = count($lines);
        $hasItem = false;

        while ($i < $len) {
            $line = $lines[$i];
            $trimmed = trim($line);
            $indent = $this->measureIndent($line);

            if ($trimmed === '') {
                if ($hasItem) {
                    $currentItem[] = '';
                }
                $i++;
                continue;
            }

            if ($indent === $baseIndent && preg_match('/^([-*+]|\d+[.)])\s+(.*)$/', $trimmed, $m)) {
                $isOrdered = is_numeric($m[1][0]);
                if ($isOrdered === $ordered || !$hasItem) {
                    if ($hasItem) {
                        $items[] = $currentItem;
                    }
                    $currentItem = [$m[2]];
                    $hasItem = true;
                    $i++;
                    continue;
                }
                break;
            }

            if ($hasItem && $indent > $baseIndent) {
                $dedented = $this->dedentLine($line, $contentIndent);
                $currentItem[] = $dedented;
                $i++;
                continue;
            }

            break;
        }

        if ($hasItem) {
            $items[] = $currentItem;
        }

        $parsedItems = [];
        foreach ($items as $itemLines) {
            $content = rtrim(implode("\n", $itemLines));
            $contentLines = explode("\n", $content);

            $textLines = [];
            $subLines = [];
            $inSub = false;

            foreach ($contentLines as $cl) {
                $clTrimmed = trim($cl);
                if (!$inSub && preg_match('/^([-*+]|\d+[.)])\s+/', $clTrimmed)) {
                    $inSub = true;
                }
                if ($inSub) {
                    $subLines[] = $cl;
                } else {
                    $textLines[] = $cl;
                }
            }

            if (!empty($subLines)) {
                $firstContent = rtrim(implode("\n", $textLines));
                $subBlocks = [];
                if ($firstContent !== '') {
                    $subBlocks[] = ['type' => 'paragraph', 'content' => $firstContent];
                }
                $subResult = $this->parseList($subLines, 0, $depth + 1);
                if (!empty($subResult['block']['items'])) {
                    $subBlocks[] = $subResult['block'];
                }
                $parsedItems[] = ['type' => 'complex', 'blocks' => $subBlocks];
            } else {
                $parsedItems[] = ['type' => 'simple', 'content' => $content];
            }
        }

        return [
            'block' => [
                'type' => 'list',
                'ordered' => $ordered,
                'start' => $startNum,
                'items' => $parsedItems,
            ],
            'index' => $i,
        ];
    }

    /**
     * Count leading spaces in a line (tabs count as 4 spaces).
     *
     * @param  string $line The line to measure
     * @return int Number of leading spaces (tabs counted as 4)
     */
    private function measureIndent(string $line): int
    {
        $count = 0;
        for ($j = 0, $len = strlen($line); $j < $len; $j++) {
            if ($line[$j] === ' ') {
                $count++;
            } elseif ($line[$j] === "\t") {
                $count += 4;
            } else {
                break;
            }
        }
        return $count;
    }

    /**
     * Remove up to $amount leading whitespace from a line.
     *
     * @param  string $line The line to dedent
     * @param  int    $amount Number of spaces to remove (tabs count as 4)
     * @return string The dedented line
     */
    private function dedentLine(string $line, int $amount): string
    {
        $removed = 0;
        $pos = 0;
        $len = strlen($line);
        while ($pos < $len && $removed < $amount) {
            if ($line[$pos] === ' ') {
                $removed++;
                $pos++;
            } elseif ($line[$pos] === "\t") {
                $removed += 4;
                $pos++;
            } else {
                break;
            }
        }
        return substr($line, $pos);
    }

    /**
     * Parse a blockquote, including nested blockquotes and other blocks.
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the first `>` line
     * @param  int      $depth Current recursion depth
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseBlockquote(array $lines, int $start, int $depth = 0): array
    {
        if ($depth > $this->maxDepth) {
            return [
                'block' => ['type' => 'blockquote', 'blocks' => []],
                'index' => $start + 1,
            ];
        }

        $content = '';
        $i = $start;
        $len = count($lines);

        while ($i < $len) {
            $trimmed = trim($lines[$i]);

            if (preg_match('/^>\s?(.*)$/', $trimmed, $m)) {
                $content .= $m[1] . "\n";
                $i++;
            } elseif ($trimmed !== '' && !preg_match('/^(#{1,6}\s|[-*+]\s|\d+[.)]\s|```|~~~|>|\||---|\*\*\*|___)/', $trimmed)) {
                $content .= $trimmed . "\n";
                $i++;
            } else {
                break;
            }
        }

        $contentTrimmed = rtrim($content);

        if (preg_match('/^\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*$/m', $contentTrimmed, $alertMatch)) {
            $alertType = strtolower($alertMatch[1]);
            $alertContent = preg_replace('/^\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\]\s*\n?/m', '', $contentTrimmed, 1);
            $nestedBlocks = $this->parseBlocksWithDepth(rtrim($alertContent), $depth + 1);
            return [
                'block' => ['type' => 'alert', 'alertType' => $alertType, 'blocks' => $nestedBlocks],
                'index' => $i,
            ];
        }

        $nestedBlocks = $this->parseBlocksWithDepth($contentTrimmed, $depth + 1);

        return [
            'block' => ['type' => 'blockquote', 'blocks' => $nestedBlocks],
            'index' => $i,
        ];
    }

    /**
     * Recursively parse blocks within a nested context (blockquotes, etc.).
     *
     * @param  string $text The text to parse into blocks
     * @param  int    $depth Current recursion depth
     * @return array<int, array<string, mixed>>
     */
    private function parseBlocksWithDepth(string $text, int $depth): array
    {
        if ($depth > $this->maxDepth) {
            return [['type' => 'paragraph', 'content' => $text]];
        }

        $lines = explode("\n", $text);
        $blocks = [];
        $i = 0;
        $len = count($lines);

        while ($i < $len) {
            $trimmed = trim($lines[$i]);

            if ($trimmed === '') {
                $i++;
                continue;
            }

            if (preg_match('/^(#{1,6})\s+(.+?)(?:\s+#+)?$/', $trimmed, $m)) {
                $hContent = trim($m[2]);
                $hAttrs = [];
                $hCustomId = '';
                $hClassName = '';
                if (preg_match('/\s*\{([^}]+)\}\s*$/', $hContent, $am)) {
                    $this->parseAttrString($am[1], $hCustomId, $hClassName, $hAttrs);
                    $hContent = rtrim(substr($hContent, 0, -strlen($am[0])));
                }
                $blocks[] = [
                    'type' => 'heading',
                    'level' => strlen($m[1]),
                    'content' => $hContent,
                    'id' => $hCustomId !== '' ? $hCustomId : $this->generateUniqueHeadingId($hContent),
                    'className' => $hClassName,
                    'attrs' => $hAttrs,
                ];
                $i++;
                continue;
            }

            if (preg_match('/^(`{3,})(\w*)/', $trimmed, $m)) {
                $fence = str_repeat('`', strlen($m[1]));
                $result = $this->parseFencedCode($lines, $i + 1, trim($m[2]), $fence);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^\$\$(.+)\$\$\s*$/', $trimmed, $m)) {
                $blocks[] = ['type' => 'math_display', 'content' => trim($m[1])];
                $i++;
                continue;
            }

            if ($trimmed === '$$') {
                $result = $this->parseMathBlock($lines, $i + 1);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^\\\\\[(.+)\\\\\]\s*$/', $trimmed, $m)) {
                $blocks[] = ['type' => 'math_display', 'content' => trim($m[1])];
                $i++;
                continue;
            }

            if ($trimmed === '\\[') {
                $result = $this->parseMathBlock($lines, $i + 1, '\\]');
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^:{3,}(.*)$/', $trimmed, $m)) {
                $result = $this->parseFencedDiv($lines, $i, trim($m[1]));
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^([-*+]|\d+[.)])\s+/', $trimmed)) {
                $result = $this->parseList($lines, $i, $depth);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^>\s?/', $trimmed)) {
                $result = $this->parseBlockquote($lines, $i, $depth);
                $blocks[] = $result['block'];
                $i = $result['index'];
                continue;
            }

            if (preg_match('/^(---+|\*\*\*+|___+)$/', $trimmed)) {
                $blocks[] = ['type' => 'hr'];
                $i++;
                continue;
            }

            $content = '';
            $first = true;
            while ($i < $len && trim($lines[$i]) !== '' && ($first || !preg_match('/^(#{1,6}\s|[-*+]\s|\d+[.)]\s|```|~~~|>|---|\*\*\*|___|\$\$|\\\\\[|:{3,})/', trim($lines[$i])))) {
                $content .= ($content !== '' ? ' ' : '') . trim($lines[$i]);
                $i++;
                $first = false;
            }
            if ($content !== '') {
                $blocks[] = ['type' => 'paragraph', 'content' => $content];
            }
        }

        return $blocks;
    }

    /**
     * Parse a raw HTML block until its closing tag.
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the opening tag line
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseHtmlBlock(array $lines, int $start): array
    {
        preg_match('/^<(\w+)/', trim($lines[$start]), $m);
        $tag = strtolower($m[1] ?? '');
        $selfClosing = in_array($tag, ['hr', 'br', 'img', 'input', 'meta', 'link', 'area', 'base', 'col', 'embed', 'source', 'track', 'wbr']);

        if ($selfClosing) {
            return [
                'block' => ['type' => 'html', 'content' => $lines[$start]],
                'index' => $start + 1,
            ];
        }

        $collected = [];
        $i = $start;
        $len = count($lines);
        $depth = 0;

        while ($i < $len) {
            $line = $lines[$i];
            $collected[] = $line;

            preg_match_all("/<{$tag}(?:\s|>)/i", $line, $opens);
            preg_match_all("/<\/{$tag}\s*>/i", $line, $closes);
            $depth += count($opens[0]) - count($closes[0]);

            $i++;
            if ($depth <= 0) {
                break;
            }
        }

        $containerTags = ['details', 'div', 'section', 'aside', 'article', 'fieldset', 'figure', 'footer', 'header', 'main', 'nav'];

        if (!in_array($tag, $containerTags)) {
            return [
                'block' => ['type' => 'html', 'content' => rtrim(implode("\n", $collected))],
                'index' => $i,
            ];
        }

        $html = $this->processHtmlContainerBlock($collected, $tag);

        return [
            'block' => ['type' => 'html', 'content' => $html],
            'index' => $i,
        ];
    }

    /**
     * Process a container HTML block, rendering Markdown content
     * found between HTML preamble/postamble separated by blank lines.
     *
     * @param  string[] $collected All lines of the HTML block (including open/close tags)
     * @param  string   $tag       The container tag name
     * @return string   Final HTML with inner Markdown rendered
     */
    private function processHtmlContainerBlock(array $collected, string $tag): string
    {
        $total = count($collected);

        $firstBlank = -1;
        for ($j = 1; $j < $total; $j++) {
            if (trim($collected[$j]) === '') {
                $firstBlank = $j;
                break;
            }
        }

        if ($firstBlank === -1) {
            return rtrim(implode("\n", $collected));
        }

        $lastLine = $total - 1;
        $closingStart = $lastLine;
        for ($j = $lastLine; $j > $firstBlank; $j--) {
            $trimmed = trim($collected[$j]);
            if ($trimmed === '') {
                break;
            }
            if (preg_match("/<\/{$tag}\s*>/i", $trimmed)) {
                $closingStart = $j;
                break;
            }
        }

        $openingLines = array_slice($collected, 0, $firstBlank);
        $closingLines = array_slice($collected, $closingStart);
        $innerLines = array_slice($collected, $firstBlank, $closingStart - $firstBlank);
        $innerContent = trim(implode("\n", $innerLines));

        if ($innerContent === '') {
            return rtrim(implode("\n", $collected));
        }

        $innerBlocks = $this->parseBlocks($innerContent);
        $renderedInner = $this->renderBlocks($innerBlocks);

        return implode("\n", $openingLines) . "\n" . $renderedInner . "\n" . implode("\n", $closingLines);
    }

    /**
     * Parse an HTML comment block (<!-- ... -->).
     *
     * Collects lines until the closing `-->` is found, preserving the
     * comment verbatim in the output. Supports multiline comments.
     *
     * @param  string[] $lines All document lines
     * @param  int      $start Index of the line containing `<!--`
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseHtmlComment(array $lines, int $start): array
    {
        $collected = [];
        $i = $start;
        $len = count($lines);

        while ($i < $len) {
            $collected[] = $lines[$i];
            if (str_contains($lines[$i], '-->')) {
                $i++;
                break;
            }
            $i++;
        }

        return [
            'block' => ['type' => 'html_comment', 'content' => rtrim(implode("\n", $collected))],
            'index' => $i,
        ];
    }

    /**
     * Detect whether a paragraph contains only a single image and should
     * be rendered as a <figure> with an optional <figcaption>.
     *
     * A paragraph is considered a standalone figure when its content matches
     * the pattern `![alt](url)` or `![alt](url "title")` with no other text.
     *
     * @param  string $content The raw paragraph content
     * @return bool True if the paragraph is a standalone image figure
     */
    private function isStandaloneImage(string $content): bool
    {
        return (bool) preg_match('/^!\[([^\]]*)\]\(([^\s\)]+)(?:\s+"([^"]*)")?\)$/', trim($content));
    }

    /**
     * Render a standalone image paragraph as a <figure> with <figcaption>.
     *
     * If the image has a title attribute it is used as the caption text.
     * Otherwise the alt text is used if non-empty.
     *
     * @param  string $content The raw paragraph content containing only the image
     * @return string HTML <figure> element
     */
    private function renderFigure(string $content): string
    {
        preg_match('/^!\[([^\]]*)\]\(([^\s\)]+)(?:\s+"([^"]*)")?\)$/', trim($content), $m);
        $alt = htmlspecialchars($m[1] ?? '', ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($m[2] ?? '', ENT_QUOTES, 'UTF-8');
        $title = $m[3] ?? '';

        $html = "<figure>\n<img src=\"{$url}\" alt=\"{$alt}\">";
        $caption = $title !== '' ? $title : ($m[1] ?? '');
        if ($caption !== '') {
            $html .= "\n<figcaption>" . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . "</figcaption>";
        }
        $html .= "\n</figure>";
        return $html;
    }

    /**
     * Parse a definition list (`<dl>`).
     *
     * Format:
     *   Term
     *   : Definition one
     *   : Definition two
     *
     * @param  string[] $lines
     * @param  int      $start Index of the first term line
     * @return null|array{block: array<string, mixed>, index: int}
     */
    private function parseDefinitionList(array $lines, int $start): ?array
    {
        $terms = [];
        $i = $start;
        $len = count($lines);

        while ($i < $len) {
            $trimmed = trim($lines[$i]);

            if ($trimmed === '') {
                $i++;
                continue;
            }

            if (!str_starts_with($trimmed, ':')) {
                if (isset($lines[$i + 1]) && str_starts_with(trim($lines[$i + 1]), ':')) {
                    $term = $trimmed;
                    $definitions = [];
                    $i++;

                    while ($i < $len && str_starts_with(trim($lines[$i]), ':')) {
                        $definitions[] = trim(substr(trim($lines[$i]), 1));
                        $i++;
                    }

                    $terms[] = ['term' => $term, 'definitions' => $definitions];
                } else {
                    break;
                }
            } else {
                break;
            }
        }

        if (empty($terms)) {
            return null;
        }

        return [
            'block' => ['type' => 'definition_list', 'terms' => $terms],
            'index' => $i,
        ];
    }

    /**
     * Parse a paragraph — consecutive non-blank, non-block lines.
     *
     * @param  string[] $lines
     * @param  int      $start Index of the first paragraph line
     * @return array{block: array<string, mixed>, index: int}
     */
    private function parseParagraph(array $lines, int $start): array
    {
        $content = '';
        $i = $start;
        $len = count($lines);
        $first = true;

        while ($i < $len) {
            $trimmed = trim($lines[$i]);

            if ($trimmed === '') {
                break;
            }

            if (!$first && preg_match('/^(#{1,6}\s|[-*+]\s|\d+[.)]\s|```|~~~|>|\|.+\||---+|\*\*\*+|___+|\$\$|\\\\\[|:{3,}|\[TOC\]$|<(address|article|aside|blockquote|details|dialog|dd|div|dl|dt|fieldset|figcaption|figure|footer|form|h[1-6]|header|hgroup|hr|li|main|nav|ol|p|pre|section|table|ul)(\s|>|$))/i', $trimmed)) {
                break;
            }

            if (str_ends_with($trimmed, '  ')) {
                $content .= ($content !== '' ? "\n" : '') . rtrim($trimmed);
                $content .= "  \n";
            } elseif (str_ends_with($trimmed, '\\')) {
                $content .= ($content !== '' ? "\n" : '') . substr($trimmed, 0, -1);
                $content .= "\\\n";
            } else {
                $content .= ($content !== '' ? ' ' : '') . $trimmed;
            }
            $i++;
            $first = false;
        }

        return [
            'block' => ['type' => 'paragraph', 'content' => rtrim($content)],
            'index' => $i,
        ];
    }

    /**
     * Strip inline Markdown syntax and produce a URL-friendly slug.
     *
     * @param  string $text Raw heading text (may contain inline Markdown)
     * @return string Lowercase, hyphen-separated slug
     */
    private function generateHeadingId(string $text): string
    {
        $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '$1', $text);
        $text = preg_replace('/___(.+?)___/', '$1', $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '$1', $text);
        $text = preg_replace('/__(.+?)__/', '$1', $text);
        $text = preg_replace('/\*(.+?)\*/', '$1', $text);
        $text = preg_replace('/_(.+?)_/', '$1', $text);
        $text = preg_replace('/~~(.+?)~~/', '$1', $text);
        $text = preg_replace('/`([^`]+)`/', '$1', $text);
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text);
        $text = preg_replace('/!\[([^\]]*)\]\([^\)]+\)/', '$1', $text);

        $text = strtolower($text);
        $text = preg_replace('/[^\w\s-]/u', '', $text);
        $text = preg_replace('/[\s_]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Generate a heading ID that is unique within this document.
     *
     * Appends `-1`, `-2`, etc. for duplicate headings.
     *
     * @param  string $text Raw heading text
     * @return string Unique slug
     */
    private function generateUniqueHeadingId(string $text): string
    {
        $base = $this->generateHeadingId($text);

        if (!isset($this->headingIds[$base])) {
            $this->headingIds[$base] = 0;
            return $base;
        }

        $this->headingIds[$base]++;
        return $base . '-' . $this->headingIds[$base];
    }

    /**
     * Render an array of parsed blocks to an HTML string.
     *
     * @param  array<int, array<string, mixed>> $blocks
     * @return string
     */
    private function renderBlocks(array $blocks): string
    {
        $html = [];

        foreach ($blocks as $block) {
            $rendered = $this->renderBlock($block);
            if ($rendered !== '') {
                $html[] = $rendered;
            }
        }

        return implode("\n", $html);
    }

    /**
     * Dispatch a single block to its renderer.
     *
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderBlock(array $block): string
    {
        return match ($block['type']) {
            'heading' => $this->renderHeading($block),
            'paragraph' => $this->renderParagraph($block),
            'code' => $this->renderCode($block),
            'list' => $this->renderList($block),
            'tasklist' => $this->renderTaskList($block),
            'blockquote' => $this->renderBlockquote($block),
            'table' => $this->renderTable($block),
            'hr' => '<hr>',
            'html' => $block['content'],
            'html_comment' => $block['content'],
            'math_display' => $this->renderMathDisplay($block),
            'definition_list' => $this->renderDefinitionList($block),
            'container' => $this->renderContainer($block),
            'alert' => $this->renderAlert($block),
            'toc' => '<nav class="table-of-contents" data-toc-placeholder="1"></nav>',
            default => '',
        };
    }

    /**
     * Render a heading, including any custom ID/class/attributes.
     * 
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderHeading(array $block): string
    {
        $content = $this->processInline($block['content']);
        $level = $block['level'];
        $id = htmlspecialchars($block['id'], ENT_QUOTES, 'UTF-8');
        $classAttr = !empty($block['className']) ? ' class="' . htmlspecialchars($block['className'], ENT_QUOTES, 'UTF-8') . '"' : '';
        $extraAttrs = '';
        foreach (($block['attrs'] ?? []) as $k => $v) {
            $extraAttrs .= ' ' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"';
        }
        return "<h{$level} id=\"{$id}\"{$classAttr}{$extraAttrs}>{$content}</h{$level}>";
    }

    /**
     * Process inline Markdown syntax within a block's content.
     * 
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderParagraph(array $block): string
    {
        // Render standalone images as <figure> with optional <figcaption>
        if ($this->isStandaloneImage($block['content'])) {
            return $this->renderFigure($block['content']);
        }
        $content = $this->processInline($block['content']);
        return "<p>{$content}</p>";
    }

    /**
     * Render a fenced code block, escaping HTML and applying language class if specified.
     * 
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderCode(array $block): string
    {
        $code = htmlspecialchars($block['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $langAttr = $block['lang'] ? ' class="language-' . htmlspecialchars($block['lang'], ENT_QUOTES, 'UTF-8') . '"' : '';
        $titleAttr = !empty($block['title']) ? ' data-title="' . htmlspecialchars($block['title'], ENT_QUOTES, 'UTF-8') . '"' : '';
        return "<pre{$titleAttr}><code{$langAttr}>{$code}</code></pre>";
    }

    /**
     * Render a display math block as `<div class="math-display">`.
     *
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderMathDisplay(array $block): string
    {
        $content = htmlspecialchars($block['content'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return '<div class="math-display">$$' . $content . '$$</div>';
    }

    /**
     * Render an ordered or unordered list, including nested sub-lists.
     * 
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderList(array $block): string
    {
        $tag = $block['ordered'] ? 'ol' : 'ul';
        $startAttr = ($block['ordered'] && $block['start'] !== null && $block['start'] !== 1)
            ? " start=\"{$block['start']}\""
            : '';

        $items = [];
        foreach ($block['items'] as $item) {
            if ($item['type'] === 'complex') {
                $innerHtml = $this->renderBlocks($item['blocks']);
                $items[] = "<li>\n{$innerHtml}\n</li>";
            } else {
                $content = $this->processInline($item['content']);
                $items[] = "<li>{$content}</li>";
            }
        }

        return "<{$tag}{$startAttr}>\n" . implode("\n", $items) . "\n</{$tag}>";
    }

    /**
     * Render a task list as a `<ul>` with checkboxes.
     * 
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderTaskList(array $block): string
    {
        $items = [];
        foreach ($block['items'] as $item) {
            $checked = $item['checked'] ? ' checked' : '';
            $content = $this->processInline($item['content']);
            $items[] = "<li><input type=\"checkbox\" disabled{$checked}> {$content}</li>";
        }

        return "<ul class=\"task-list\">\n" . implode("\n", $items) . "\n</ul>";
    }

    /**
     * Render a blockquote, including nested blocks.
     * 
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderBlockquote(array $block): string
    {
        $innerHtml = $this->renderBlocks($block['blocks']);
        return "<blockquote>\n{$innerHtml}\n</blockquote>";
    }

    /**
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderTable(array $block): string
    {
        $html = "<table>\n<thead>\n<tr>\n";

        foreach ($block['headers'] as $i => $header) {
            $align = $block['alignments'][$i] ?? null;
            $style = $align ? " style=\"text-align: {$align}\"" : '';
            $content = $this->processInline($header);
            $html .= "<th{$style}>{$content}</th>\n";
        }

        $html .= "</tr>\n</thead>\n";

        if (!empty($block['rows'])) {
            $html .= "<tbody>\n";
            foreach ($block['rows'] as $row) {
                $html .= "<tr>\n";
                foreach ($row as $i => $cell) {
                    $align = $block['alignments'][$i] ?? null;
                    $style = $align ? " style=\"text-align: {$align}\"" : '';
                    $content = $this->processInline($cell);
                    $html .= "<td{$style}>{$content}</td>\n";
                }
                $html .= "</tr>\n";
            }
            $html .= "</tbody>\n";
        }

        $html .= "</table>";
        return $html;
    }

    /**
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderDefinitionList(array $block): string
    {
        $html = "<dl>\n";

        foreach ($block['terms'] as $item) {
            $term = $this->processInline($item['term']);
            $html .= "<dt>{$term}</dt>\n";

            foreach ($item['definitions'] as $def) {
                $content = $this->processInline($def);
                $html .= "<dd>{$content}</dd>\n";
            }
        }

        $html .= "</dl>";
        return $html;
    }

    /**
     * Render a fenced div container as a `<div>` with optional id, class, and attributes.
     *
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderContainer(array $block): string
    {
        $classAttr = $block['className'] !== '' ? ' class="' . htmlspecialchars($block['className'], ENT_QUOTES, 'UTF-8') . '"' : '';
        $idAttr = $block['id'] !== '' ? ' id="' . htmlspecialchars($block['id'], ENT_QUOTES, 'UTF-8') . '"' : '';
        $extraAttrs = '';
        foreach ($block['attrs'] as $k => $v) {
            $extraAttrs .= ' ' . htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '"';
        }
        $innerHtml = $this->renderBlocks($block['blocks']);
        return "<div{$idAttr}{$classAttr}{$extraAttrs}>\n{$innerHtml}\n</div>";
    }

    /**
     * Render a GFM alert block as a styled `<div>` with a title paragraph.
     *
     * @param  array<string, mixed> $block
     * @return string
     */
    private function renderAlert(array $block): string
    {
        $type = htmlspecialchars($block['alertType'], ENT_QUOTES, 'UTF-8');
        $titles = [
            'note' => 'Note',
            'tip' => 'Tip',
            'important' => 'Important',
            'warning' => 'Warning',
            'caution' => 'Caution',
        ];
        $title = $titles[$type] ?? ucfirst($type);
        $innerHtml = $this->renderBlocks($block['blocks']);
        return "<div class=\"markdown-alert markdown-alert-{$type}\">\n<p class=\"markdown-alert-title\">{$title}</p>\n{$innerHtml}\n</div>";
    }

    /**
     * Replace the `[TOC]` placeholder element with the rendered table of contents.
     *
     * @param  string $html     Rendered HTML (may contain the TOC placeholder)
     * @param  string $markdown Original Markdown source (used to extract headings)
     * @return string
     */
    private function replaceTocMarker(string $html, string $markdown): string
    {
        if (strpos($html, 'data-toc-placeholder="1"') === false) {
            return $html;
        }
        $tocHtml = $this->renderTableOfContents($markdown);
        return str_replace(
            '<nav class="table-of-contents" data-toc-placeholder="1"></nav>',
            $tocHtml,
            $html
        );
    }

    /**
     * Process all inline Markdown syntax within a text span.
     *
     * Order matters: escapes → code → autolinks → images → links → emphasis → footnotes → breaks.
     *
     * @param  string $text
     * @return string HTML with inline elements rendered
     */
    private function processInline(string $text): string
    {
        $text = $this->processInlineCode($text);
        $text = $this->processInlineMath($text);
        $text = $this->processEscapes($text);
        $text = $this->processAutolinks($text);
        $text = $this->processImages($text);
        $text = $this->processLinks($text);
        $text = $this->processWikilinks($text);
        $text = $this->processEmphasis($text);
        $text = $this->processKbd($text);
        $text = $this->processEmoji($text);
        $text = $this->processFootnoteRefs($text);
        $text = $this->processLineBreaks($text);
        $text = $this->processSmartQuotes($text);
        $text = $this->processTypography($text);
        $text = $this->restoreInlineCodeTokens($text);
        $text = $this->restoreMathTokens($text);
        return $this->restoreEscapes($text);
    }

    /**
     * Replace backslash-escaped characters with placeholder tokens.
     *
     * @param  string $text
     * @return string
     */
    private function processEscapes(string $text): string
    {
        $this->escapeTokens = [];
        return preg_replace_callback($this->inlinePatterns['escape'], function ($m) {
            $token = "\x00ESC" . count($this->escapeTokens) . "\x00";
            $this->escapeTokens[$token] = $m[1];
            return $token;
        }, $text);
    }

    /**
     * Restore escape placeholder tokens to their HTML-entity form.
     *
     * @param  string $text
     * @return string
     */
    private function restoreEscapes(string $text): string
    {
        foreach ($this->escapeTokens as $token => $char) {
            $text = str_replace($token, htmlspecialchars($char, ENT_QUOTES, 'UTF-8'), $text);
        }
        return $text;
    }

    /**
     * Convert `<url>` and `<email>` autolinks, plus bare URLs.
     *
     * @param  string $text
     * @return string
     */
    private function processAutolinks(string $text): string
    {
        $text = preg_replace_callback($this->inlinePatterns['autolink_url'], function ($m) {
            $url = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            return "<a href=\"{$url}\">{$url}</a>";
        }, $text);

        $text = preg_replace_callback($this->inlinePatterns['autolink_email'], function ($m) {
            $email = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            return "<a href=\"mailto:{$email}\">{$email}</a>";
        }, $text);

        $text = preg_replace_callback($this->inlinePatterns['bare_url'], function ($m) {
            $url = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            return "<a href=\"{$url}\">{$url}</a>";
        }, $text);

        return $text;
    }

    /**
     * Convert inline and reference-style images.
     *
     * @param  string $text
     * @return string
     */
    private function processImages(string $text): string
    {
        // Full reference images ![alt][id]
        $text = preg_replace_callback($this->inlinePatterns['reference_image'], function ($m) {
            $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $id = strtolower($m[2] !== '' ? $m[2] : $m[1]);
            if (isset($this->references[$id])) {
                $url = htmlspecialchars($this->references[$id]['url'], ENT_QUOTES, 'UTF-8');
                $title = $this->references[$id]['title']
                    ? ' title="' . htmlspecialchars($this->references[$id]['title'], ENT_QUOTES, 'UTF-8') . '"'
                    : '';
                return "<img src=\"{$url}\" alt=\"{$alt}\"{$title}>";
            }
            return $m[0];
        }, $text);

        // Collapsed reference images ![alt][]
        $text = preg_replace_callback($this->inlinePatterns['collapsed_ref_image'], function ($m) {
            $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $id = strtolower($m[1]);
            if (isset($this->references[$id])) {
                $url = htmlspecialchars($this->references[$id]['url'], ENT_QUOTES, 'UTF-8');
                $title = $this->references[$id]['title']
                    ? ' title="' . htmlspecialchars($this->references[$id]['title'], ENT_QUOTES, 'UTF-8') . '"'
                    : '';
                return "<img src=\"{$url}\" alt=\"{$alt}\"{$title}>";
            }
            return $m[0];
        }, $text);

        // Inline images with optional size ![alt](url =WxH "title")
        $text = preg_replace_callback($this->inlinePatterns['image_with_size'], function ($m) {
            $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $url = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $attrs = '';
            if (!empty($m[3])) {
                $attrs .= ' width="' . (int) $m[3] . '"';
            }
            if (!empty($m[4])) {
                $attrs .= ' height="' . (int) $m[4] . '"';
            }
            $title = isset($m[5]) && $m[5] !== '' ? ' title="' . htmlspecialchars($m[5], ENT_QUOTES, 'UTF-8') . '"' : '';
            return "<img src=\"{$url}\" alt=\"{$alt}\"{$attrs}{$title}>";
        }, $text);

        // Shortcut reference images ![alt] (no brackets/parens following)
        $text = preg_replace_callback($this->inlinePatterns['shortcut_ref_image'], function ($m) {
            $alt = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            $id = strtolower($m[1]);
            if (isset($this->references[$id])) {
                $url = htmlspecialchars($this->references[$id]['url'], ENT_QUOTES, 'UTF-8');
                $title = $this->references[$id]['title']
                    ? ' title="' . htmlspecialchars($this->references[$id]['title'], ENT_QUOTES, 'UTF-8') . '"'
                    : '';
                return "<img src=\"{$url}\" alt=\"{$alt}\"{$title}>";
            }
            return $m[0];
        }, $text);

        return $text;
    }

    /**
     * Convert inline and reference-style links.
     *
     * @param  string $text
     * @return string
     */
    private function processLinks(string $text): string
    {
        // Full reference links [text][id]
        $text = preg_replace_callback($this->inlinePatterns['reference_link'], function ($m) {
            $linkText = $m[1];
            $id = strtolower($m[2] !== '' ? $m[2] : $linkText);
            if (isset($this->references[$id])) {
                $url = htmlspecialchars($this->references[$id]['url'], ENT_QUOTES, 'UTF-8');
                $title = $this->references[$id]['title']
                    ? ' title="' . htmlspecialchars($this->references[$id]['title'], ENT_QUOTES, 'UTF-8') . '"'
                    : '';
                return "<a href=\"{$url}\"{$title}>{$linkText}</a>";
            }
            return $m[0];
        }, $text);

        // Collapsed reference links [text][]
        $text = preg_replace_callback($this->inlinePatterns['collapsed_ref_link'], function ($m) {
            $linkText = $m[1];
            $id = strtolower($linkText);
            if (isset($this->references[$id])) {
                $url = htmlspecialchars($this->references[$id]['url'], ENT_QUOTES, 'UTF-8');
                $title = $this->references[$id]['title']
                    ? ' title="' . htmlspecialchars($this->references[$id]['title'], ENT_QUOTES, 'UTF-8') . '"'
                    : '';
                return "<a href=\"{$url}\"{$title}>{$linkText}</a>";
            }
            return $m[0];
        }, $text);

        // Inline links [text](url "title")
        $text = preg_replace_callback($this->inlinePatterns['link_with_title'], function ($m) {
            $linkText = $m[1];
            $url = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $title = isset($m[3]) && $m[3] !== '' ? ' title="' . htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8') . '"' : '';
            return "<a href=\"{$url}\"{$title}>{$linkText}</a>";
        }, $text);

        // Shortcut reference links [text] (must come last, most greedy)
        $text = preg_replace_callback($this->inlinePatterns['shortcut_ref_link'], function ($m) {
            $linkText = $m[1];
            $id = strtolower($linkText);
            if (isset($this->references[$id])) {
                $url = htmlspecialchars($this->references[$id]['url'], ENT_QUOTES, 'UTF-8');
                $title = $this->references[$id]['title']
                    ? ' title="' . htmlspecialchars($this->references[$id]['title'], ENT_QUOTES, 'UTF-8') . '"'
                    : '';
                return "<a href=\"{$url}\"{$title}>{$linkText}</a>";
            }
            return $m[0];
        }, $text);

        return $text;
    }

    /**
     * Apply emphasis (bold, italic, bold-italic), strikethrough, highlight, sub/superscript.
     *
     * @param  string $text
     * @return string
     */
    private function processEmphasis(string $text): string
    {
        $text = preg_replace($this->inlinePatterns['bold_italic_asterisk'], '<strong><em>$1</em></strong>', $text);
        $text = preg_replace($this->inlinePatterns['bold_italic_underscore'], '<strong><em>$1</em></strong>', $text);
        $text = preg_replace($this->inlinePatterns['bold_asterisk'], '<strong>$1</strong>', $text);
        $text = preg_replace($this->inlinePatterns['bold_underscore'], '<strong>$1</strong>', $text);
        $text = preg_replace($this->inlinePatterns['italic_asterisk'], '<em>$1</em>', $text);
        $text = preg_replace($this->inlinePatterns['italic_underscore'], '<em>$1</em>', $text);
        $text = preg_replace($this->inlinePatterns['strikethrough'], '<del>$1</del>', $text);
        $text = preg_replace($this->inlinePatterns['highlight'], '<mark>$1</mark>', $text);
        $text = preg_replace($this->inlinePatterns['insert'], '<ins>$1</ins>', $text);
        $text = preg_replace($this->inlinePatterns['subscript'], '<sub>$1</sub>', $text);
        $text = preg_replace($this->inlinePatterns['superscript'], '<sup>$1</sup>', $text);
        return $text;
    }

    /**
     * Convert inline code spans, supporting multi-backtick delimiters.
     *
     * Handles `` `code` ``, ``` ``code with `backtick` `` ```, etc.
     *
     * @param  string $text
     * @return string
     */
    private function processInlineCode(string $text): string
    {
        $this->inlineCodeTokens = [];
        return preg_replace_callback('/(`+)(.+?)\1/s', function ($m) {
            $code = $m[2];
            if (str_starts_with($code, ' ') && str_ends_with($code, ' ') && trim($code) !== '') {
                $code = substr($code, 1, -1);
            }
            $html = '<code>' . htmlspecialchars($code, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</code>';
            $token = "\x00CODE" . count($this->inlineCodeTokens) . "\x00";
            $this->inlineCodeTokens[$token] = $html;
            return $token;
        }, $text);
    }

    private function restoreInlineCodeTokens(string $text): string
    {
        foreach ($this->inlineCodeTokens as $token => $html) {
            $text = str_replace($token, $html, $text);
        }
        return $text;
    }

    /**
     * Convert inline math `$...$` to `<span class="math-inline">`.
     *
     * Requires non-space after opening and before closing `$`.
     * Does not match `$$` (display math).
     *
     * @param  string $text
     * @return string
     */
    private function processInlineMath(string $text): string
    {
        $this->mathTokens = [];

        $text = preg_replace_callback('/\\\\\((.+?)\\\\\)/s', function ($m) {
            $math = htmlspecialchars($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $html = '<span class="math-inline">\\(' . $math . '\\)</span>';
            $token = "\x00MATH" . count($this->mathTokens) . "\x00";
            $this->mathTokens[$token] = $html;
            return $token;
        }, $text);

        $text = preg_replace_callback('/(?<![\\\\\$])\$(?!\$)(?=\S)(.+?)(?<=\S)(?<!\\\\)\$(?!\$)/', function ($m) {
            $math = htmlspecialchars($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $html = '<span class="math-inline">$' . $math . '$</span>';
            $token = "\x00MATH" . count($this->mathTokens) . "\x00";
            $this->mathTokens[$token] = $html;
            return $token;
        }, $text);

        return $text;
    }

    /**
     * Restore inline math placeholder tokens to their rendered HTML.
     *
     * @param  string $text
     * @return string
     */
    private function restoreMathTokens(string $text): string
    {
        foreach ($this->mathTokens as $token => $html) {
            $text = str_replace($token, $html, $text);
        }
        return $text;
    }

    /**
     * Convert `[[Target]]` and `[[Target|Label]]` wikilinks to anchor tags.
     *
     * The target is slugified to produce the href.
     *
     * @param  string $text
     * @return string
     */
    private function processWikilinks(string $text): string
    {
        return preg_replace_callback($this->inlinePatterns['wikilink'], function ($m) {
            $target = $m[1];
            $label = $m[2] ?? $target;
            $slug = strtolower(trim($target));
            $slug = preg_replace('/[^\w\s-]/u', '', $slug);
            $slug = preg_replace('/[\s_]+/', '-', $slug);
            $slug = trim($slug, '-');
            $safeLabel = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
            return "<a href=\"{$slug}\">{$safeLabel}</a>";
        }, $text);
    }

    /**
     * Replace `:shortcode:` emoji tokens with their Unicode equivalents.
     *
     * @param  string $text
     * @return string
     */
    private function processEmoji(string $text): string
    {
        return preg_replace_callback($this->inlinePatterns['emoji'], function ($m) {
            $map = self::getEmojiMap();
            $code = strtolower($m[1]);
            return $map[$code] ?? $m[0];
        }, $text);
    }

    /**
     * Convert `<<key>>` sequences to `<kbd>` HTML elements for keyboard input.
     *
     * Nested key combinations like `<<Ctrl>>+<<S>>` are each wrapped individually.
     * This must run before processTypography to avoid `<<`/`>>` being converted to guillemets.
     *
     * @param  string $text
     * @return string
     */
    private function processKbd(string $text): string
    {
        return preg_replace_callback($this->inlinePatterns['kbd'], function ($m) {
            $key = htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8');
            return '<kbd>' . $key . '</kbd>';
        }, $text);
    }

    /**
     * Convert straight quotation marks to typographic (curly) smart quotes.
     *
     * Double quotes are converted to &ldquo;/&rdquo; and single quotes (apostrophes)
     * are converted to &lsquo;/&rsquo;. Processing is applied only outside of HTML tags
     * to avoid corrupting attributes.
     *
     * @param  string $text
     * @return string
     */
    private function processSmartQuotes(string $text): string
    {
        $parts = preg_split('/(<[^>]*>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0, $len = count($parts); $i < $len; $i++) {
            if ($i % 2 === 1) {
                continue; // skip HTML tags
            }
            $p = $parts[$i];
            // Double quotes: opening after whitespace/start, closing before whitespace/end
            $p = preg_replace('/(?<=\s|^)"(?=\S)/', '&ldquo;', $p);
            $p = preg_replace('/(?<=\S)"(?=\s|$|[.,;:!?\)])/', '&rdquo;', $p);
            // Single quotes / apostrophes
            $p = preg_replace('/(?<=\s|^)\'(?=\S)/', '&lsquo;', $p);
            $p = preg_replace('/(?<=\S)\'(?=\s|$|[.,;:!?\)])/', '&rsquo;', $p);
            // Apostrophes within words (e.g., "don't")
            $p = preg_replace('/(?<=\w)\'(?=\w)/', '&rsquo;', $p);
            $parts[$i] = $p;
        }
        return implode('', $parts);
    }

    /**
     * Apply typographic replacements outside of HTML tags.
     *
     * Converts: `---` → em dash, `--` → en dash, `...` → ellipsis,
     * `(c)/(r)/(tm)` → symbols, `+-` → ±, fractions, `<<`/`>>` → guillemets.
     *
     * @param  string $text
     * @return string
     */
    private function processTypography(string $text): string
    {
        $parts = preg_split('/(<\/?(?:a|img|strong|em|del|ins|mark|sub|sup|br|code|span|abbr)(?:\s[^>]*)?>)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0, $len = count($parts); $i < $len; $i++) {
            if ($i % 2 === 1) {
                continue;
            }
            $p = $parts[$i];
            $p = str_replace('---', '&mdash;', $p);
            $p = str_replace('--', '&ndash;', $p);
            $p = preg_replace('/(?<!\.)\.\.\.(?!\.)/', '&hellip;', $p);
            $p = str_replace(['(c)', '(C)'], '&copy;', $p);
            $p = str_replace(['(r)', '(R)'], '&reg;', $p);
            $p = str_replace(['(tm)', '(TM)'], '&trade;', $p);
            $p = str_replace(['(p)', '(P)'], '&sect;', $p);
            $p = str_replace('+-', '&plusmn;', $p);
            $p = preg_replace('/(?<=\s|^)1\/4(?=\s|$|[.,;])/', '&frac14;', $p);
            $p = preg_replace('/(?<=\s|^)1\/2(?=\s|$|[.,;])/', '&frac12;', $p);
            $p = preg_replace('/(?<=\s|^)3\/4(?=\s|$|[.,;])/', '&frac34;', $p);
            $p = str_replace('<<', '&laquo;', $p);
            $p = str_replace('>>', '&raquo;', $p);
            $parts[$i] = $p;
        }
        return implode('', $parts);
    }

    /**
     * Return the singleton emoji shortcode-to-Unicode map.
     *
     * @return array<string, string>
     */
    private static function getEmojiMap(): array
    {
        if (self::$emojiMap !== null) {
            return self::$emojiMap;
        }
        self::$emojiMap = [
            'smile' => '😄',
            'laughing' => '😆',
            'blush' => '😊',
            'smiley' => '😃',
            'relaxed' => '☺️',
            'smirk' => '😏',
            'heart_eyes' => '😍',
            'kissing_heart' => '😘',
            'kissing_closed_eyes' => '😚',
            'flushed' => '😳',
            'relieved' => '😌',
            'satisfied' => '😆',
            'grin' => '😁',
            'wink' => '😉',
            'stuck_out_tongue_winking_eye' => '😜',
            'stuck_out_tongue_closed_eyes' => '😝',
            'grinning' => '😀',
            'kissing' => '😗',
            'kissing_smiling_eyes' => '😙',
            'stuck_out_tongue' => '😛',
            'sleeping' => '😴',
            'worried' => '😟',
            'frowning' => '😦',
            'anguished' => '😧',
            'open_mouth' => '😮',
            'grimacing' => '😬',
            'confused' => '😕',
            'hushed' => '😯',
            'expressionless' => '😑',
            'unamused' => '😒',
            'sweat_smile' => '😅',
            'sweat' => '😓',
            'disappointed_relieved' => '😥',
            'weary' => '😩',
            'pensive' => '😔',
            'disappointed' => '😞',
            'confounded' => '😖',
            'fearful' => '😨',
            'cold_sweat' => '😰',
            'persevere' => '😣',
            'cry' => '😢',
            'sob' => '😭',
            'joy' => '😂',
            'astonished' => '😲',
            'scream' => '😱',
            'tired_face' => '😫',
            'angry' => '😠',
            'rage' => '😡',
            'triumph' => '😤',
            'sleepy' => '😪',
            'yum' => '😋',
            'mask' => '😷',
            'sunglasses' => '😎',
            'dizzy_face' => '😵',
            'imp' => '👿',
            'smiling_imp' => '😈',
            'neutral_face' => '😐',
            'no_mouth' => '😶',
            'innocent' => '😇',
            'alien' => '👽',
            'yellow_heart' => '💛',
            'blue_heart' => '💙',
            'purple_heart' => '💜',
            'heart' => '❤️',
            'green_heart' => '💚',
            'broken_heart' => '💔',
            'heartbeat' => '💓',
            'heartpulse' => '💗',
            'two_hearts' => '💕',
            'revolving_hearts' => '💞',
            'cupid' => '💘',
            'sparkling_heart' => '💖',
            'sparkles' => '✨',
            'star' => '⭐',
            'star2' => '🌟',
            'dizzy' => '💫',
            'boom' => '💥',
            'collision' => '💥',
            'anger' => '💢',
            'exclamation' => '❗',
            'question' => '❓',
            'grey_exclamation' => '❕',
            'grey_question' => '❔',
            'zzz' => '💤',
            'dash' => '💨',
            'sweat_drops' => '💦',
            'notes' => '🎶',
            'musical_note' => '🎵',
            'fire' => '🔥',
            'poop' => '💩',
            'thumbsup' => '👍',
            '+1' => '👍',
            'thumbsdown' => '👎',
            '-1' => '👎',
            'ok_hand' => '👌',
            'punch' => '👊',
            'fist' => '✊',
            'v' => '✌️',
            'wave' => '👋',
            'hand' => '✋',
            'raised_hand' => '✋',
            'open_hands' => '👐',
            'point_up' => '☝️',
            'point_down' => '👇',
            'point_left' => '👈',
            'point_right' => '👉',
            'raised_hands' => '🙌',
            'pray' => '🙏',
            'point_up_2' => '👆',
            'clap' => '👏',
            'muscle' => '💪',
            'metal' => '🤘',
            'walking' => '🚶',
            'runner' => '🏃',
            'running' => '🏃',
            'dancer' => '💃',
            'couple' => '👫',
            'family' => '👪',
            'two_men_holding_hands' => '👬',
            'two_women_holding_hands' => '👭',
            'bow' => '🙇',
            'couplekiss' => '💏',
            'couple_with_heart' => '💑',
            'massage' => '💆',
            'haircut' => '💇',
            'nail_care' => '💅',
            'boy' => '👦',
            'girl' => '👧',
            'woman' => '👩',
            'man' => '👨',
            'baby' => '👶',
            'older_woman' => '👵',
            'older_man' => '👴',
            'person_with_blond_hair' => '👱',
            'man_with_gua_pi_mao' => '👲',
            'man_with_turban' => '👳',
            'construction_worker' => '👷',
            'cop' => '👮',
            'angel' => '👼',
            'princess' => '👸',
            'ghost' => '👻',
            'skull' => '💀',
            'santa' => '🎅',
            'dog' => '🐶',
            'cat' => '🐱',
            'mouse' => '🐭',
            'hamster' => '🐹',
            'rabbit' => '🐰',
            'wolf' => '🐺',
            'frog' => '🐸',
            'tiger' => '🐯',
            'koala' => '🐨',
            'bear' => '🐻',
            'pig' => '🐷',
            'cow' => '🐮',
            'boar' => '🐗',
            'monkey_face' => '🐵',
            'monkey' => '🐒',
            'horse' => '🐴',
            'racehorse' => '🐎',
            'camel' => '🐫',
            'sheep' => '🐑',
            'elephant' => '🐘',
            'panda_face' => '🐼',
            'snake' => '🐍',
            'bird' => '🐦',
            'baby_chick' => '🐤',
            'hatched_chick' => '🐥',
            'hatching_chick' => '🐣',
            'chicken' => '🐔',
            'penguin' => '🐧',
            'turtle' => '🐢',
            'bug' => '🐛',
            'honeybee' => '🐝',
            'ant' => '🐜',
            'beetle' => '🐞',
            'snail' => '🐌',
            'octopus' => '🐙',
            'tropical_fish' => '🐠',
            'fish' => '🐟',
            'whale' => '🐳',
            'whale2' => '🐋',
            'dolphin' => '🐬',
            'cow2' => '🐄',
            'ram' => '🐏',
            'rat' => '🐀',
            'water_buffalo' => '🐃',
            'tiger2' => '🐅',
            'rabbit2' => '🐇',
            'dragon' => '🐉',
            'goat' => '🐐',
            'rooster' => '🐓',
            'dog2' => '🐕',
            'pig2' => '🐖',
            'mouse2' => '🐁',
            'ox' => '🐂',
            'dragon_face' => '🐲',
            'blowfish' => '🐡',
            'crocodile' => '🐊',
            'dromedary_camel' => '🐪',
            'leopard' => '🐆',
            'cat2' => '🐈',
            'poodle' => '🐩',
            'crab' => '🦀',
            'bouquet' => '💐',
            'cherry_blossom' => '🌸',
            'tulip' => '🌷',
            'four_leaf_clover' => '🍀',
            'rose' => '🌹',
            'sunflower' => '🌻',
            'hibiscus' => '🌺',
            'maple_leaf' => '🍁',
            'leaves' => '🍃',
            'fallen_leaf' => '🍂',
            'herb' => '🌿',
            'mushroom' => '🍄',
            'cactus' => '🌵',
            'palm_tree' => '🌴',
            'evergreen_tree' => '🌲',
            'deciduous_tree' => '🌳',
            'chestnut' => '🌰',
            'seedling' => '🌱',
            'blossom' => '🌼',
            'ear_of_rice' => '🌾',
            'shell' => '🐚',
            'earth_americas' => '🌎',
            'earth_africa' => '🌍',
            'earth_asia' => '🌏',
            'full_moon' => '🌕',
            'new_moon' => '🌑',
            'sun_with_face' => '🌞',
            'full_moon_with_face' => '🌝',
            'new_moon_with_face' => '🌚',
            'sunny' => '☀️',
            'cloud' => '☁️',
            'partly_sunny' => '⛅',
            'umbrella' => '☂️',
            'snowflake' => '❄️',
            'snowman' => '⛄',
            'zap' => '⚡',
            'cyclone' => '🌀',
            'ocean' => '🌊',
            'rainbow' => '🌈',
            'apple' => '🍎',
            'green_apple' => '🍏',
            'tangerine' => '🍊',
            'lemon' => '🍋',
            'cherries' => '🍒',
            'grapes' => '🍇',
            'watermelon' => '🍉',
            'strawberry' => '🍓',
            'peach' => '🍑',
            'melon' => '🍈',
            'banana' => '🍌',
            'pear' => '🍐',
            'pineapple' => '🍍',
            'tomato' => '🍅',
            'eggplant' => '🍆',
            'hot_pepper' => '🌶️',
            'corn' => '🌽',
            'pizza' => '🍕',
            'hamburger' => '🍔',
            'fries' => '🍟',
            'poultry_leg' => '🍗',
            'meat_on_bone' => '🍖',
            'spaghetti' => '🍝',
            'curry' => '🍛',
            'fried_shrimp' => '🍤',
            'bento' => '🍱',
            'sushi' => '🍣',
            'rice_ball' => '🍙',
            'rice_cracker' => '🍘',
            'rice' => '🍚',
            'ramen' => '🍜',
            'stew' => '🍲',
            'oden' => '🍢',
            'dango' => '🍡',
            'egg' => '🥚',
            'bread' => '🍞',
            'doughnut' => '🍩',
            'custard' => '🍮',
            'icecream' => '🍦',
            'ice_cream' => '🍨',
            'shaved_ice' => '🍧',
            'birthday' => '🎂',
            'cake' => '🍰',
            'cookie' => '🍪',
            'chocolate_bar' => '🍫',
            'candy' => '🍬',
            'lollipop' => '🍭',
            'honey_pot' => '🍯',
            'baby_bottle' => '🍼',
            'coffee' => '☕',
            'tea' => '🍵',
            'sake' => '🍶',
            'beer' => '🍺',
            'beers' => '🍻',
            'cocktail' => '🍸',
            'tropical_drink' => '🍹',
            'wine_glass' => '🍷',
            'fork_and_knife' => '🍴',
            'checkered_flag' => '🏁',
            'trophy' => '🏆',
            'football' => '🏈',
            'basketball' => '🏀',
            'soccer' => '⚽',
            'baseball' => '⚾',
            'tennis' => '🎾',
            'golf' => '⛳',
            'rocket' => '🚀',
            'airplane' => '✈️',
            'warning' => '⚠️',
            'x' => '❌',
            'o' => '⭕',
            'white_check_mark' => '✅',
            'heavy_check_mark' => '✔️',
            'heavy_multiplication_x' => '✖️',
            'bangbang' => '‼️',
            'interrobang' => '⁉️',
            'lock' => '🔒',
            'unlock' => '🔓',
            'key' => '🔑',
            'bulb' => '💡',
            'wrench' => '🔧',
            'hammer' => '🔨',
            'gear' => '⚙️',
            'link' => '🔗',
            'memo' => '📝',
            'pencil2' => '✏️',
            'book' => '📖',
            'books' => '📚',
            'clipboard' => '📋',
            'calendar' => '📅',
            'chart_with_upwards_trend' => '📈',
            'chart_with_downwards_trend' => '📉',
            'bar_chart' => '📊',
            'email' => '📧',
            'envelope' => '✉️',
            'inbox_tray' => '📥',
            'outbox_tray' => '📤',
            'phone' => '📞',
            'telephone_receiver' => '📞',
            'computer' => '💻',
            'desktop_computer' => '🖥️',
            'keyboard' => '⌨️',
            'eyes' => '👀',
            'eye' => '👁️',
            'speech_balloon' => '💬',
            'thought_balloon' => '💭',
            'clock1' => '🕐',
            'clock2' => '🕑',
            'clock3' => '🕒',
            'clock4' => '🕓',
            'clock5' => '🕔',
            'clock6' => '🕕',
            'clock7' => '🕖',
            'clock8' => '🕗',
            'clock9' => '🕘',
            'clock10' => '🕙',
            'clock11' => '🕚',
            'clock12' => '🕛',
            'arrow_right' => '➡️',
            'arrow_left' => '⬅️',
            'arrow_up' => '⬆️',
            'arrow_down' => '⬇️',
            'heavy_plus_sign' => '➕',
            'heavy_minus_sign' => '➖',
            'heavy_division_sign' => '➗',
            'recycle' => '♻️',
            'copyright' => '©️',
            'registered' => '®️',
            'tm' => '™️',
            'hundred' => '💯',
            'tada' => '🎉',
            'party_popper' => '🎉',
            'confetti_ball' => '🎊',
            'balloon' => '🎈',
            'gift' => '🎁',
            'bell' => '🔔',
            'ribbon' => '🎀',
            'crystal_ball' => '🔮',
            'camera' => '📷',
            'video_camera' => '📹',
            'movie_camera' => '🎥',
            'loudspeaker' => '📢',
            'mega' => '📣',
            'mute' => '🔇',
            'speaker' => '🔈',
            'sound' => '🔉',
            'loud_sound' => '🔊',
            'mag' => '🔍',
            'mag_right' => '🔎',
            'hourglass' => '⌛',
            'watch' => '⌚',
            'alarm_clock' => '⏰',
            'stopwatch' => '⏱️',
            'timer_clock' => '⏲️',
            'thinking' => '🤔',
            'face_with_rolling_eyes' => '🙄',
            'zipper_mouth_face' => '🤐',
            'upside_down_face' => '🙃',
            'money_mouth_face' => '🤑',
            'nerd_face' => '🤓',
            'hugs' => '🤗',
            'rofl' => '🤣',
            'shrug' => '🤷',
            'facepalm' => '🤦',
            'skull_and_crossbones' => '☠️',
            'robot' => '🤖',
            'crossed_fingers' => '🤞',
            'handshake' => '🤝',
            'palms_up_together' => '🤲',
            'brain' => '🧠',
            'fox_face' => '🦊',
            'unicorn' => '🦄',
            'butterfly' => '🦋',
            'gorilla' => '🦍',
            'owl' => '🦉',
            'shark' => '🦈',
            'avocado' => '🥑',
            'taco' => '🌮',
            'burrito' => '🌯',
            'croissant' => '🥐',
            'pancakes' => '🥞',
        ];
        return self::$emojiMap;
    }

    /**
     * Replace `[^id]` inline references with numbered superscript footnote links.
     *
     * Only references that have a matching definition are converted.
     *
     * @param  string $text
     * @return string
     */
    private function processFootnoteRefs(string $text): string
    {
        return preg_replace_callback($this->inlinePatterns['footnote_ref'], function ($m) {
            $id = $m[1];
            if (isset($this->footnotes[$id])) {
                $this->footnoteCounter++;
                $num = $this->footnoteCounter;
                $safeId = htmlspecialchars($id, ENT_QUOTES, 'UTF-8');
                return "<sup id=\"fnref:{$safeId}\"><a href=\"#fn:{$safeId}\">[{$num}]</a></sup>";
            }
            return $m[0];
        }, $text);
    }

    /**
     * Convert trailing double-spaces and backslash-newline to `<br>`.
     *
     * @param  string $text
     * @return string
     */
    private function processLineBreaks(string $text): string
    {
        $text = preg_replace('/  +\n/', "<br>\n", $text);
        $text = preg_replace('/\\\\\n/', "<br>\n", $text);
        return $text;
    }

    /**
     * Append the footnote section at the end of the document.
     *
     * @param  string $html
     * @return string
     */
    private function appendFootnotes(string $html): string
    {
        if (empty($this->footnotes) || $this->footnoteCounter === 0) {
            return $html;
        }

        $html .= "\n<hr>\n<section class=\"footnotes\">\n<ol>\n";

        $counter = 0;
        foreach ($this->footnotes as $id => $content) {
            $counter++;
            if ($counter > $this->footnoteCounter) {
                break;
            }
            $safeId = htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8');
            $content = $this->processInline($content);
            $html .= "<li id=\"fn:{$safeId}\">{$content} <a href=\"#fnref:{$safeId}\">&#8617;</a></li>\n";
        }

        $html .= "</ol>\n</section>";
        return $html;
    }

    /**
     * Wrap known abbreviations in `<abbr>` tags throughout the rendered HTML.
     *
     * @param  string $html
     * @return string
     */
    private function applyAbbreviations(string $html): string
    {
        foreach ($this->abbreviations as $abbr => $full) {
            $pattern = '/(?<![<\w])' . preg_quote($abbr, '/') . '(?![>\w])/';
            $replacement = '<abbr title="' . htmlspecialchars($full, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($abbr, ENT_QUOTES, 'UTF-8') . '</abbr>';
            $html = preg_replace($pattern, $replacement, $html);
        }
        return $html;
    }

    /**
     * Strip disallowed tags and dangerous attributes for safe-mode output.
     *
     * @param  string $html
     * @return string
     */
    private function sanitizeHtml(string $html): string
    {
        if (empty($this->allowedTags)) {
            $this->allowedTags = [
                'h1',
                'h2',
                'h3',
                'h4',
                'h5',
                'h6',
                'p',
                'br',
                'hr',
                'strong',
                'em',
                'del',
                'ins',
                'mark',
                'sub',
                'sup',
                'code',
                'pre',
                'ul',
                'ol',
                'li',
                'blockquote',
                'table',
                'thead',
                'tbody',
                'tr',
                'th',
                'td',
                'a',
                'img',
                'dl',
                'dt',
                'dd',
                'abbr',
                'input',
                'section',
                'nav',
                'div',
                'span',
                'sup',
                'kbd',
                'figure',
                'figcaption',
            ];
        }

        $allowedTagsString = '<' . implode('><', $this->allowedTags) . '>';
        $html = strip_tags($html, $allowedTagsString);

        $html = preg_replace_callback('/<(a|img)\s+([^>]*)>/i', function ($m) {
            $tag = strtolower($m[1]);
            $attrs = $m[2];

            if ($tag === 'a' && preg_match('/href\s*=\s*["\']?\s*javascript:/i', $attrs)) {
                return '';
            }

            if ($tag === 'a' && preg_match('/href\s*=\s*["\']?\s*data:/i', $attrs)) {
                return '';
            }

            if (preg_match('/on\w+\s*=/i', $attrs)) {
                $attrs = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $attrs);
                $attrs = preg_replace('/\s*on\w+\s*=\s*\S+/i', '', $attrs);
            }

            if ($tag === 'a' && preg_match('/href\s*=\s*["\']?\s*vbscript:/i', $attrs)) {
                return '';
            }

            return "<{$tag} {$attrs}>";
        }, $html);

        return $html;
    }

    /**
     * Collapse excessive blank lines and trim the final output.
     *
     * @param  string $html
     * @return string
     */
    private function cleanOutput(string $html): string
    {
        $html = preg_replace('/\n{3,}/', "\n\n", $html);
        return trim($html);
    }

    /**
     * Convert Markdown to plain text (strip all HTML tags).
     *
     * @param  string $markdown
     * @return string
     */
    public function convertToText(string $markdown): string
    {
        $html = $this->convert($markdown);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    /**
     * Convert only inline Markdown syntax to HTML, without block-level parsing.
     *
     * Useful for rendering a single line or fragment where block structures
     * (headings, lists, code blocks, etc.) are not expected.
     *
     * @param  string $text Raw inline Markdown fragment
     * @return string HTML with inline elements rendered
     */
    public function convertInline(string $text): string
    {
        return $this->processInline($text);
    }

    /**
     * Set the maximum nesting depth for recursive block parsing.
     *
     * Prevents runaway recursion in deeply nested blockquotes, lists, and
     * fenced divs. Default is 10.
     *
     * @param  int  $depth Maximum depth (must be >= 1)
     * @return self Fluent interface
     */
    public function setMaxDepth(int $depth): self
    {
        $this->maxDepth = max(1, $depth);
        return $this;
    }

    /**
     * Get the current maximum nesting depth.
     *
     * @return int
     */
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    /**
     * Enable or disable safe mode (HTML sanitization).
     *
     * When enabled, disallowed tags and dangerous attributes (javascript:,
     * event handlers, etc.) are stripped from the output.
     *
     * @param  bool $enabled Whether to enable safe mode
     * @return self Fluent interface
     */
    public function setSafeMode(bool $enabled): self
    {
        $this->safeMode = $enabled;
        return $this;
    }

    /**
     * Get the current safe mode setting.
     *
     * @return bool
     */
    public function getSafeMode(): bool
    {
        return $this->safeMode;
    }

    /**
     * Get all reference link definitions extracted during the last conversion.
     *
     * @return array<string, array{url: string, title: ?string}>
     */
    public function getReferences(): array
    {
        return $this->references;
    }

    /**
     * Get all footnote definitions extracted during the last conversion.
     *
     * @return array<string, string>
     */
    public function getFootnotes(): array
    {
        return $this->footnotes;
    }

    /**
     * Get all abbreviation definitions extracted during the last conversion.
     *
     * @return array<string, string>
     */
    public function getAbbreviations(): array
    {
        return $this->abbreviations;
    }

    /**
     * Programmatically register a reference link definition.
     *
     * Definitions added this way persist across convert() calls until
     * the next convert() resets internal state.
     *
     * @param  string      $id    Reference ID (case-insensitive)
     * @param  string      $url   Target URL
     * @param  string|null $title Optional link title
     * @return self Fluent interface
     */
    public function addReference(string $id, string $url, ?string $title = null): self
    {
        $this->references[strtolower($id)] = ['url' => $url, 'title' => $title];
        return $this;
    }

    /**
     * Programmatically register an abbreviation definition.
     *
     * @param  string $abbr     The abbreviation (e.g. "HTML")
     * @param  string $fullText The full expansion (e.g. "HyperText Markup Language")
     * @return self Fluent interface
     */
    public function addAbbreviation(string $abbr, string $fullText): self
    {
        $this->abbreviations[$abbr] = $fullText;
        return $this;
    }

    /**
     * Programmatically register a footnote definition.
     *
     * @param  string $id      Footnote identifier
     * @param  string $content Footnote body text
     * @return self Fluent interface
     */
    public function addFootnote(string $id, string $content): self
    {
        $this->footnotes[$id] = $content;
        return $this;
    }

    /**
     * Count the number of words in a Markdown document after stripping syntax.
     *
     * Converts the Markdown to plain text first, then counts whitespace-
     * delimited tokens.
     *
     * @param  string $markdown Raw Markdown source
     * @return int Word count
     */
    public function wordCount(string $markdown): int
    {
        $text = $this->convertToText($markdown);
        if (trim($text) === '') {
            return 0;
        }
        return str_word_count($text);
    }

    /**
     * Estimate reading time in minutes for a Markdown document.
     *
     * Uses an average reading speed of 200 words per minute. Returns at
     * least 1 minute for any non-empty document.
     *
     * @param  string $markdown Raw Markdown source
     * @param  int    $wpm      Words per minute (default: 200)
     * @return int Estimated reading time in minutes
     */
    public function estimateReadingTime(string $markdown, int $wpm = 200): int
    {
        $words = $this->wordCount($markdown);
        if ($words === 0) {
            return 0;
        }
        return max(1, (int) ceil($words / $wpm));
    }

    /**
     * Extract all links (URLs) found in the Markdown document.
     *
     * Returns an array of associative arrays, each containing 'url', 'text',
     * and 'title' (nullable) keys. Includes both inline and reference-style links.
     *
     * @param  string $markdown Raw Markdown source
     * @return array<int, array{url: string, text: string, title: ?string}>
     */
    public function extractLinks(string $markdown): array
    {
        $links = [];

        // Inline links [text](url "title")
        preg_match_all('/\[([^\]]+)\]\(([^\s\)]+)(?:\s+"([^"]*)")?\)/', $markdown, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $links[] = [
                'url' => $m[2],
                'text' => $m[1],
                'title' => $m[3] ?? null,
            ];
        }

        // Reference link definitions
        $this->convert($markdown); // ensure references are extracted
        foreach ($this->references as $id => $ref) {
            $links[] = [
                'url' => $ref['url'],
                'text' => $id,
                'title' => $ref['title'],
            ];
        }

        return $links;
    }

    /**
     * Extract all images found in the Markdown document.
     *
     * Returns an array of associative arrays, each containing 'url', 'alt',
     * and 'title' (nullable) keys.
     *
     * @param  string $markdown Raw Markdown source
     * @return array<int, array{url: string, alt: string, title: ?string}>
     */
    public function extractImages(string $markdown): array
    {
        $images = [];

        preg_match_all('/!\[([^\]]*)\]\(([^\s\)]+)(?:\s+"([^"]*)")?\)/', $markdown, $matches, PREG_SET_ORDER);
        foreach ($matches as $m) {
            $images[] = [
                'url' => $m[2],
                'alt' => $m[1],
                'title' => $m[3] ?? null,
            ];
        }

        return $images;
    }

    /**
     * Extract a flat list of headings for table-of-contents generation.
     *
     * @param  string $markdown
     * @return array<int, array{level: int, text: string, id: string}>
     */
    public function extractTableOfContents(string $markdown): array
    {
        $toc = [];
        $pattern = '/^(#{1,6})\s+(.+?)(?:\s+#+)?$/m';

        preg_match_all($pattern, $markdown, $matches, PREG_SET_ORDER);

        $idTracker = [];
        foreach ($matches as $m) {
            $level = strlen($m[1]);
            $text = trim($m[2]);
            $baseId = $this->generateHeadingId($text);

            if (!isset($idTracker[$baseId])) {
                $idTracker[$baseId] = 0;
                $id = $baseId;
            } else {
                $idTracker[$baseId]++;
                $id = $baseId . '-' . $idTracker[$baseId];
            }

            $toc[] = [
                'level' => $level,
                'text' => strip_tags($this->processInline($text)),
                'id' => $id,
            ];
        }

        return $toc;
    }

    /**
     * Render an HTML `<nav>` table of contents from the document headings.
     *
     * @param  string $markdown
     * @return string HTML `<nav>` element, or empty string if no headings
     */
    public function renderTableOfContents(string $markdown): string
    {
        $toc = $this->extractTableOfContents($markdown);
        if (empty($toc)) {
            return '';
        }

        $html = "<nav class=\"table-of-contents\">\n<ul>\n";
        $prevLevel = 0;

        foreach ($toc as $item) {
            $level = $item['level'];

            if ($level > $prevLevel) {
                $html .= str_repeat("<ul>\n", $level - $prevLevel);
            } elseif ($level < $prevLevel) {
                $html .= str_repeat("</li>\n</ul>\n", $prevLevel - $level);
                $html .= "</li>\n";
            } elseif ($prevLevel > 0) {
                $html .= "</li>\n";
            }

            $id = htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8');
            $text = htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8');
            $html .= "<li><a href=\"#{$id}\">{$text}</a>";
            $prevLevel = $level;
        }

        $html .= str_repeat("</li>\n</ul>\n", $prevLevel);
        $html .= "</nav>";

        return $html;
    }

    /**
     * Convert an HTML string to Markdown.
     *
     * @param  string               $html    Raw HTML source
     * @param  array<string, mixed> $options Conversion options (see file header)
     * @return string Markdown output
     */
    public function convertFromHtml(string $html, array $options = []): string
    {
        $opts = array_merge([
            'heading_style'      => 'atx',
            'bullet_char'        => '-',
            'strong_em_symbol'   => '*',
            'newline_style'      => 'spaces',
            'code_language'      => '',
            'autolinks'          => true,
            'escape_asterisks'   => true,
            'escape_underscores' => true,
        ], $options);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        // The <?xml …> declaration tells libxml to treat the source as UTF-8,
        // so multibyte characters (©, å, 한글 …) are preserved correctly.
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        // Prefer <body> to skip any <head> content; fall back to documentElement.
        $root = $dom->getElementsByTagName('body')->item(0) ?? $dom->documentElement ?? $dom;

        $md = $this->htmlNodeToMarkdown($root, $opts, 0, false);

        // ── Clean up ────────────────────────────────────────────────────────────
        // Strip trailing single spaces (but preserve "  \n" — Markdown hard break)
        // and normalise excessive blank lines.
        $md = preg_replace('/(?<! ) \n/', "\n", $md);   // lone trailing space → remove
        $md = preg_replace('/ {3,}\n/', "  \n", $md);   // 3 + trailing spaces → normalise to 2
        $md = preg_replace('/\t+\n/', "\n", $md);        // trailing tabs → remove
        $md = preg_replace('/\n{3,}/', "\n\n", $md);     // collapse 3 + blank lines

        return trim($md);
    }

    /**
     * Recursively convert a DOM node to a Markdown string.
     *
     * @param  DOMNode             $node
     * @param  array<string, mixed> $opts
     * @param  int                  $depth  Current list-nesting depth
     * @param  bool                 $inPre  True while inside a <pre> element
     * @return string
     */
    private function htmlNodeToMarkdown(DOMNode $node, array $opts, int $depth, bool $inPre): string
    {
        // ── Text node ────────────────────────────────────────────────────────────
        if ($node->nodeType === XML_TEXT_NODE) {
            $text = $node->nodeValue ?? '';
            if (!$inPre) {
                // Collapse whitespace runs to a single space, like a browser.
                $text = preg_replace('/\s+/', ' ', $text);
                if ($opts['escape_asterisks'])   {
                    $text = str_replace('*', '\\*', $text);
                }
                if ($opts['escape_underscores']) {
                    $text = str_replace('_', '\\_', $text);
                }
            }
            return $text;
        }

        // ── Only element nodes beyond this point ─────────────────────────────────
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        /** @var \DOMElement $node */
        $tag    = strtolower($node->nodeName);
        $newPre = $inPre || $tag === 'pre';

        // Build inner content first; needed by most tag handlers.
        $inner = '';
        foreach ($node->childNodes as $child) {
            $inner .= $this->htmlNodeToMarkdown($child, $opts, $depth, $newPre);
        }

        return $this->htmlTagToMarkdown($tag, $node, $inner, $opts, $depth);
    }

    /**
     * Map one HTML element to its Markdown equivalent.
     *
     * @param  string               $tag   Lower-cased element name
     * @param  \DOMElement          $node  The element (for attribute access)
     * @param  string               $inner Pre-rendered children
     * @param  array<string, mixed> $opts  Conversion options
     * @param  int                  $depth Current list-nesting depth
     * @return string
     */
    private function htmlTagToMarkdown(string $tag, \DOMElement $node, string $inner, array $opts, int $depth): string
    {
        $sym    = $opts['strong_em_symbol']; // '*' or '_'
        $bullet = $opts['bullet_char'];      // '-', '*', or '+'

        switch ($tag) {
            case 'html':
            case 'body':
            case 'span':
            case 'abbr':
            case 'cite':
            case 'bdi':
            case 'bdo':
                return $inner;

            case 'div':
            case 'section':
            case 'article':
            case 'main':
            case 'header':
            case 'footer':
            case 'aside':
            case 'nav':
            case 'details':
            case 'summary': {
                $t = trim($inner);
                return $t !== '' ? "\n\n{$t}\n\n" : '';
            }

            case 'p': {
                $t = trim($inner);
                return $t !== '' ? "\n\n{$t}\n\n" : '';
            }

            case 'br':
                // 'spaces'    → two trailing spaces + newline  (CommonMark)
                // 'backslash' → backslash + newline            (GitHub / Pandoc)
                return $opts['newline_style'] === 'backslash' ? "\\\n" : "  \n";

            case 'h1': case 'h2': case 'h3':
            case 'h4': case 'h5': case 'h6': {
                $level = (int) $tag[1];
                $t     = trim($inner);
                if ($opts['heading_style'] === 'setext' && $level <= 2) {
                    $width     = max(3, mb_strlen(strip_tags($t)));
                    $underline = $level === 1
                        ? str_repeat('=', $width)
                        : str_repeat('-', $width);
                    return "\n\n{$t}\n{$underline}\n\n";
                }
                return "\n\n" . str_repeat('#', $level) . " {$t}\n\n";
            }
            case 'strong':
            case 'b': {
                $t = trim($inner);
                return $t !== '' ? "{$sym}{$sym}{$t}{$sym}{$sym}" : '';
            }
            case 'em':
            case 'i': {
                $t = trim($inner);
                return $t !== '' ? "{$sym}{$t}{$sym}" : '';
            }
            case 'del':
            case 's':
            case 'strike': {
                $t = trim($inner);
                return $t !== '' ? "~~{$t}~~" : '';
            }
            case 'mark': {
                $t = trim($inner);
                return $t !== '' ? "=={$t}==" : '';
            }
            case 'ins': {
                $t = trim($inner);
                return $t !== '' ? "++{$t}++" : '';
            }
            case 'sub': {
                $t = trim($inner);
                return $t !== '' ? "~{$t}~" : '';
            }
            case 'sup': {
                $t = trim($inner);
                return $t !== '' ? "^{$t}^" : '';
            }
            case 'kbd': {
                $t = trim($inner);
                return $t !== '' ? "<<{$t}>>" : '';
            }
            case 'code': {
                // When directly inside <pre>, the 'pre' handler wraps everything.
                if (strtolower($node->parentNode->nodeName ?? '') === 'pre') {
                    return $inner;
                }
                $text = $node->textContent ?? '';
                // Use enough backticks so the delimiter never appears inside the code.
                $delim = '`';
                while (str_contains($text, $delim)) {
                    $delim .= '`';
                }
                // CommonMark: pad with one space on each side when code starts/ends
                // with a space, so the space is not consumed by the delimiter.
                $padded = (str_starts_with($text, ' ') || str_ends_with($text, ' ')) ? " {$text} " : $text;
                return "{$delim}{$padded}{$delim}";
            }
            case 'pre': {
                // Extract language from a nested <code class="language-*">.
                $lang     = $opts['code_language'];
                $codeNode = null;
                foreach ($node->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && strtolower($child->nodeName) === 'code') {
                        $codeNode = $child;
                        break;
                    }
                }
                if ($codeNode instanceof \DOMElement) {
                    $class = $codeNode->getAttribute('class') ?? '';
                    if (preg_match('/(?:language|lang)-(\w+)/', $class, $m)) {
                        $lang = $m[1];
                    }
                    $text = $codeNode->textContent ?? '';
                } else {
                    $text = $node->textContent ?? '';
                }
                return "\n\n```{$lang}\n" . rtrim($text) . "\n```\n\n";
            }
            case 'a': {
                $href  = $node->getAttribute('href')  ?? '';
                $title = $node->getAttribute('title') ?? '';
                $t     = trim($inner);
                if ($t === '') {
                    $t = $href;
                }
                if ($href === '') {
                    return $t;
                }
                // Autolink shorthand when text equals URL and there is no title.
                if ($opts['autolinks'] && $t === $href && $title === '') {
                    return "<{$href}>";
                }
                $titlePart = $title !== '' ? " \"{$title}\"" : '';
                return "[{$t}]({$href}{$titlePart})";
            }
            case 'img': {
                $src    = $node->getAttribute('src')    ?? '';
                $alt    = $node->getAttribute('alt')    ?? '';
                $title  = $node->getAttribute('title')  ?? '';
                $width  = $node->getAttribute('width')  ?? '';
                $height = $node->getAttribute('height') ?? '';

                // Append optional dimension hint (e.g. " =200x100").
                $sizePart  = ($width !== '' || $height !== '')
                    ? " ={$width}x{$height}"
                    : '';
                $titlePart = $title !== '' ? " \"{$title}\"" : '';
                return "![{$alt}]({$src}{$sizePart}{$titlePart})";
            }
            case 'ul':
                return "\n\n" . $this->htmlListToMarkdown($node, $opts, false, $depth) . "\n\n";
            case 'ol':
                return "\n\n" . $this->htmlListToMarkdown($node, $opts, true, $depth) . "\n\n";
            // <li> content is assembled item-by-item inside htmlListToMarkdown.
            case 'li':
                return $inner;

            case 'blockquote': {
                $t     = trim($inner);
                $lines = explode("\n", $t);
                $quoted = array_map(static fn(string $l) => '> ' . $l, $lines);
                return "\n\n" . implode("\n", $quoted) . "\n\n";
            }
            case 'hr':
                return "\n\n---\n\n";
            case 'table':
                return "\n\n" . $this->htmlTableToMarkdown($node, $opts) . "\n\n";
            // Handled inside htmlTableToMarkdown; ignored when encountered directly.
            case 'thead':
            case 'tbody':
            case 'tfoot':
            case 'tr':
            case 'th':
            case 'td':
                return $inner;
            case 'figure': {
                $t = trim($inner);
                return $t !== '' ? "\n\n{$t}\n\n" : '';
            }
            case 'figcaption': {
                $t = trim($inner);
                return $t !== '' ? "\n*{$t}*" : '';
            }
            case 'script':
            case 'style':
            case 'head':
            case 'meta':
            case 'link':
            case 'noscript':
                return '';
            default:
                return $inner;
        }
    }

    /**
     * Render a <ul> or <ol> as Markdown list lines.
     *
     * Handles nested lists (arbitrary depth) and task-list checkboxes ([ ] / [x]).
     *
     * @param  DOMNode             $listNode
     * @param  array<string, mixed> $opts
     * @param  bool                 $ordered  True for <ol>, false for <ul>
     * @param  int                  $depth    0-based nesting depth (controls indent)
     * @return string
     */
    private function htmlListToMarkdown(DOMNode $listNode, array $opts, bool $ordered, int $depth): string
    {
        $lines   = [];
        $counter = 1;
        $bullet  = $opts['bullet_char'];
        $pad     = str_repeat('    ', $depth); // 4-space indent per level

        foreach ($listNode->childNodes as $liNode) {
            if ($liNode->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            if (strtolower($liNode->nodeName) !== 'li') {
                continue;
            }

            $prefix = $ordered ? "{$counter}. " : "{$bullet} ";
            $counter++;

            // ── Detect task-list checkbox (<input type="checkbox">) ───────────────
            $checkbox    = '';
            $textParts   = [];
            $nestedLists = [];

            foreach ($liNode->childNodes as $liChild) {
                // Checkbox
                if ($liChild->nodeType === XML_ELEMENT_NODE && strtolower($liChild->nodeName) === 'input'
                    && $liChild instanceof \DOMElement && strtolower($liChild->getAttribute('type') ?? '') === 'checkbox'
                ) {
                    $checkbox = $liChild->hasAttribute('checked') ? '[x] ' : '[ ] ';
                    continue;
                }

                // Nested list → defer to recursive call
                if ($liChild->nodeType === XML_ELEMENT_NODE
                    && in_array(strtolower($liChild->nodeName), ['ul', 'ol'], true)
                ) {
                    $nestedLists[] = $liChild;
                    continue;
                }

                // Normal content
                $textParts[] = $this->htmlNodeToMarkdown($liChild, $opts, $depth, false);
            }

            $itemText = trim(implode('', $textParts));
            $lines[]  = $pad . $prefix . $checkbox . $itemText;

            // ── Render nested lists ───────────────────────────────────────────────
            foreach ($nestedLists as $nested) {
                $nestedOrdered = strtolower($nested->nodeName) === 'ol';
                $nestedMd      = $this->htmlListToMarkdown($nested, $opts, $nestedOrdered, $depth + 1);
                foreach (explode("\n", $nestedMd) as $nl) {
                    $lines[] = $nl;
                }
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Render a <table> element as a GFM pipe table.
     *
     * Column alignment is derived from `style="text-align:…"` or the legacy
     * `align` attribute on <th>/<td> cells.
     * If no <thead> is present, the first <tbody> row is promoted to the header
     * row (same behaviour as markdownify's --table-infer-header).
     *
     * @param  DOMNode             $tableNode
     * @param  array<string, mixed> $opts
     * @return string GFM table string (without surrounding blank lines)
     */
    private function htmlTableToMarkdown(DOMNode $tableNode, array $opts): string
    {
        $headers    = [];
        $alignments = [];
        $rows       = [];

        /**
         * Extract cell text and per-cell alignment from a <tr> node.
         *
         * @return array{cells: string[], aligns: (?string)[]}
         */
        $extractRow = function (DOMNode $trNode) use ($opts): array {
            $cells  = [];
            $aligns = [];

            foreach ($trNode->childNodes as $cell) {
                if ($cell->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                $cellTag = strtolower($cell->nodeName);
                if ($cellTag !== 'th' && $cellTag !== 'td') {
                    continue;
                }

                // Render cell content
                $text = '';
                foreach ($cell->childNodes as $cc) {
                    $text .= $this->htmlNodeToMarkdown($cc, $opts, 0, false);
                }
                $cells[] = trim($text);

                // Resolve alignment
                $align = null;
                if ($cell instanceof \DOMElement) {
                    $style = $cell->getAttribute('style') ?? '';
                    if (preg_match('/text-align\s*:\s*(left|center|right)/i', $style, $m)) {
                        $align = strtolower($m[1]);
                    }
                    if ($align === null) {
                        $a = strtolower(trim($cell->getAttribute('align') ?? ''));
                        if (in_array($a, ['left', 'center', 'right'], true)) {
                            $align = $a;
                        }
                    }
                }
                $aligns[] = $align;
            }

            return ['cells' => $cells, 'aligns' => $aligns];
        };

        // ── Walk the table's direct children ─────────────────────────────────────
        foreach ($tableNode->childNodes as $section) {
            if ($section->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }
            $sectionTag = strtolower($section->nodeName);

            if ($sectionTag === 'thead') {
                foreach ($section->childNodes as $tr) {
                    if ($tr->nodeType !== XML_ELEMENT_NODE || strtolower($tr->nodeName) !== 'tr') {
                        continue;
                    }
                    $extracted  = $extractRow($tr);
                    $headers    = $extracted['cells'];
                    $alignments = $extracted['aligns'];
                    break; // only the first header row
                }
            } elseif ($sectionTag === 'tbody' || $sectionTag === 'tfoot') {
                foreach ($section->childNodes as $tr) {
                    if ($tr->nodeType !== XML_ELEMENT_NODE || strtolower($tr->nodeName) !== 'tr') {
                        continue;
                    }
                    $rows[] = $extractRow($tr)['cells'];
                }
            } elseif ($sectionTag === 'tr') {
                // Bare <tr> directly inside <table> (no <thead>/<tbody>)
                $rows[] = $extractRow($section)['cells'];
            }
        }

        // ── Infer header row when <thead> is absent ────────────────────────────
        if (empty($headers) && !empty($rows)) {
            $headers    = array_shift($rows);
            $alignments = array_fill(0, count($headers), null);
        }

        if (empty($headers)) {
            return '';
        }

        $colCount = count($headers);

        // ── Build separator row ─────────────────────────────────────────────────
        $sepCells = [];
        foreach ($headers as $i => $h) {
            $align = $alignments[$i] ?? null;
            $width = max(3, mb_strlen($h));
            if ($align === 'center') {
                $sepCells[] = ':' . str_repeat('-', max(1, $width - 2)) . ':';
            } elseif ($align === 'right') {
                $sepCells[] = str_repeat('-', max(1, $width - 1)) . ':';
            } elseif ($align === 'left') {
                $sepCells[] = ':' . str_repeat('-', max(1, $width - 1));
            } else {
                $sepCells[] = str_repeat('-', $width);
            }
        }

        $md  = '| ' . implode(' | ', $headers) . " |\n";
        $md .= '| ' . implode(' | ', $sepCells) . " |\n";

        foreach ($rows as $row) {
            // Pad short rows / truncate long rows to match header column count.
            while (count($row) < $colCount) {
                $row[] = '';
            }
            $row = array_slice($row, 0, $colCount);
            $md .= '| ' . implode(' | ', $row) . " |\n";
        }

        return rtrim($md);
    }

    #endregion
}
