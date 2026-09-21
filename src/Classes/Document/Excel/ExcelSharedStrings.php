<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Document\Excel;
use function count;

/**
 * Maintains the shared string table written to xl/sharedStrings.xml.
 *
 * Sharing strings (instead of inline t="str" cells) is required for correct
 * display in Excel and is more space-efficient when strings repeat.
 */
class ExcelSharedStrings
{
    /** @var array<string, int>  value → index */
    private array $map = [];
    /** @var list<string>  in insertion order */
    private array $strings = [];

    /**
     * Register a string value and return its 0-based index.
     * Duplicate values reuse the same index.
     * 
     * @param string $value
     * 
     * @return int
     */
    public function add(string $value): int
    {
        if (!isset($this->map[$value])) {
            $this->map[$value] = count($this->strings);
            $this->strings[] = $value;
        }
        return $this->map[$value];
    }

    /**
     * Return the index of a previously registered string, or null if absent.
     * 
     * @param string $value
     * 
     * @return ?int
     */
    public function getIndex(string $value): ?int
    {
        return $this->map[$value] ?? null;
    }

    /**
     * Build the complete sharedStrings.xml content string.
     */
    public function buildXml(): string
    {
        $count = count($this->strings);
        $entries = '';
        foreach ($this->strings as $str) {
            $escaped = htmlspecialchars($str, ENT_XML1 | ENT_COMPAT, 'UTF-8');
            // Preserve leading/trailing whitespace using xml:space="preserve"
            $space = ($str !== trim($str)) ? ' xml:space="preserve"' : '';
            $entries .= "<si><t{$space}>{$escaped}</t></si>";
        }
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . " count=\"{$count}\" uniqueCount=\"{$count}\">"
            . $entries
            . '</sst>';
    }
}
