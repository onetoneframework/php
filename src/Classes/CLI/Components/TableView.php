<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\CLI\Component;

/**
 * Table View Component
 */
class TableView
{
    /** @var array Table headers */
    private array $headers;
    /** @var array Table rows */
    private array $rows;
    /** @var array|null Order of keys for columns */
    private ?array $orderKeys;
    /** @var array|null Styles for table cells */
    private ?array $styles;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->headers = [];
        $this->rows = [];
        $this->orderKeys = null;
    }

    /**
     * Set table headers
     *
     * @param array $headers Table headers
     * 
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * Set table rows
     *
     * @param array $rows Table rows
     * 
     * @return void
     */
    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

    /**
     * Set styles for table cells
     *
     * @param array $styles Styles for table cells (e.g. ['key' => ['textColor' => 31, 'backgroundColor' => 47]])
     * 
     * @return void
     */
    public function setStyles(array $styles): void
    {
        $this->styles = $styles;
    }

    /**
     * Set order of keys for columns
     *
     * @param array $orderKeys Order of keys for columns (e.g. ['name', 'age', 'email'])
     * 
     * @return void
     */
    public function setOrderKeys(array $orderKeys): void
    {
        $this->orderKeys = $orderKeys;
    }

    /**
     * Get length of string without ANSI codes
     *
     * @param string $subject String to measure
     * 
     * @return int
     */
    private function getLength(string $subject): int
    {
        return mb_strwidth(preg_replace('/\x1b\[[0-9;]*m/', '', $subject), 'UTF-8');
    }

    /**
     * Get string padded to fit width
     *
     * @param string $s String to pad
     * @param int    $w Width to fit
     * 
     * @return string Padded string
     */
    private function getFitPadding(string $s, int $w): string
    {
        $pad = max(0, $w - $this->getLength($s));

        return $s . str_repeat(' ', $pad);
    }

    /**
     * Get padding length for a column
     *
     * @param array  $rows Table rows
     * @param string $key Key to measure
     * @param int    $min Minimum padding length
     * 
     * @return array|int Padding length for the column
     */
    private function getPaddingLength(array $rows, string $key, int $min = 10): array|int
    {
        return max($min, ...array_map(fn($x) => $this->getLength($x[$key] ?? ""), ($rows ?: [[]])));
    }

    /**
     * Apply style to text
     *
     * @param string $text Text to style
     * @param int    $textColor Text color code (30-37 for foreground, 90-97 for bright foreground)
     * @param int    $backgrondColor Background color code (40-47 for background, 100-107 for bright background)
     * 
     * @return string Styled text with ANSI codes
     */
    private function withStyle(string $text, int $textColor = 30, int $backgrondColor = 40): string
    {
        return \sprintf("\033[%s;%sm%s\033[0m", $textColor, $backgrondColor, $text);
    }

    /**
     * Render the table
     *
     * @param bool $unicode Whether to use Unicode characters for borders (default: true)
     * 
     * @return void
     */
    public function render(bool $unicode = true): void
    {
        $sepChar = $unicode ? '─' : '-';
        $bar = $unicode ? '│' : '|';
        $cross = $unicode ? '┼' : '+';

        $paddingMap = [];
        $keys = $this->orderKeys ?? array_keys($this->rows[0]);
        foreach ($keys as $index => $key) {
            $paddingMap[] = $this->getPaddingLength($this->rows, $key, mb_strwidth($this->headers[$index]) + 1);
        }

        foreach ($keys as $index => $key) {
            echo str_repeat($sepChar, $paddingMap[$index] + 2) . $cross;
        }

        echo PHP_EOL;

        foreach ($this->headers as $index => $key) {
            echo ' ' . $this->getFitPadding($key, $paddingMap[$index]) . ' ' . $bar;
        }

        echo PHP_EOL;

        foreach ($keys as $index => $key) {
            echo str_repeat($sepChar, $paddingMap[$index] + 2) . $cross;
        }

        echo PHP_EOL;

        foreach ($this->rows as $row) {
            foreach ($keys as $index => $key) {
                echo ' ' . $this->withStyle($this->getFitPadding($row[$key], $paddingMap[$index])) . ' ' . $bar;
            }

            echo PHP_EOL;
        }

        foreach ($keys as $index => $key) {
            echo str_repeat($sepChar, $paddingMap[$index] + 2) . $cross;
        }

        echo PHP_EOL;
    }
}
