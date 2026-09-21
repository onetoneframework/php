<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Document\Excel;

use Clover\Classes\Document\Excel;
use DateTime;
use RuntimeException;
use function is_bool;
use function is_int;
use function is_float;
use function is_array;
use function is_string;

/**
 * Represents a single worksheet being constructed for writing.
 *
 * Cells are stored by 1-based (row, col) indices.  All builder methods
 * return $this so calls can be chained.
 *
 *   $sheet = $excel->addSheet('Sales')
 *       ->appendRow(['Month', 'Revenue', 'Units'], $headerStyle)
 *       ->setColWidth(1, 18)
 *       ->setColWidth(2, 14)
 *       ->freeze('A2')
 *       ->autoFilter('A1:C1');
 */
class ExcelSheet
{
    /**
     * Cell data keyed as $cells[rowIndex][colIndex].
     * Each entry: ['value' => mixed, 'type' => string, 'style' => ?ExcelStyle]
     *
     * @var array<int, array<int, array{value: mixed, type: string, style: ?ExcelStyle}>>
     */
    private array $cells = [];

    /** @var array<int, float>  1-based column index => width in character units */
    private array $colWidths = [];

    /** @var array<int, float>  1-based row index => height in points */
    private array $rowHeights = [];

    /** @var list<string>  Merge range strings, e.g. ['A1:C1', 'B3:B5'] */
    private array $merges = [];

    /** @var ?array $freezeAt [colIndex, rowIndex] (both 1-based) of the freeze-pane split, or null. */
    private ?array $freezeAt = null;

    /** @var ?string $autoFilterRange AutoFilter range string, e.g. 'A1:F1', or null. */
    private ?string $autoFilterRange = null;

    /** @var ?string $tabColorArgb Tab color as 8-char ARGB hex, or null for the default tab color. */
    private ?string $tabColorArgb = null;

    /** @var int $nextRow 1-based row index that the next appendRow() will write into. */
    private int $nextRow = 1;

    public function __construct(public readonly string $name)
    {
    }

    // ── Cell writers ──────────────────────────────────────────────────────────

    /**
     * Write a single cell by 1-based row and column indices.
     *
     * The cell type is inferred automatically:
     *   - DateTime       → 'date'   (stored as OLE serial number)
     *   - bool           → 'bool'
     *   - int | float    → 'number'
     *   - string '=...'  → 'formula'
     *   - string         → 'string' (stored in the shared-strings table)
     *   - null           → empty cell (not written)
     *
     * @param int             $row   1-based row index.
     * @param int             $col   1-based column index.
     * @param mixed           $value Cell value.
     * @param ExcelStyle|null $style Optional cell style.
     */
    public function setCell(int $row, int $col, mixed $value, ?ExcelStyle $style = null): static
    {
        if ($row < 1 || $col < 1) {
            throw new RuntimeException("Row and column indices must be ≥ 1 (got row={$row}, col={$col})");
        }
        $this->cells[$row][$col] = [
            'value' => $value,
            'type' => $this->inferType($value),
            'style' => $style,
        ];
        $this->nextRow = max($this->nextRow, $row + 1);
        return $this;
    }

    /**
     * Write a single cell by A1-style reference ('A1', 'BC42', etc.).
     *
     * @param string          $ref   Cell reference, e.g. 'B4'.
     * @param mixed           $value Cell value.
     * @param ExcelStyle|null $style Optional cell style.
     */
    public function setCellByRef(string $ref, mixed $value, ?ExcelStyle $style = null): static
    {
        [$col, $row] = Excel::parseRef($ref);
        return $this->setCell($row, $col, $value, $style);
    }

    /**
     * Append a row of values after the last written row.
     *
     * Each element may be a plain scalar / DateTime, or an associative array
     * with keys 'value' and optionally 'style' to override the row default:
     *
     *   $sheet->appendRow([
     *       'January',
     *       ['value' => 9800.50, 'style' => $moneyStyle],
     *       true,
     *   ]);
     *
     * @param list<mixed|array{value: mixed, style?: ExcelStyle}> $row
     * @param ExcelStyle|null $defaultStyle  Applied to cells that have no per-cell style.
     */
    public function appendRow(array $row, ?ExcelStyle $defaultStyle = null): static
    {
        $targetRow = $this->nextRow;
        foreach (array_values($row) as $colOffset => $cell) {
            if (is_array($cell) && array_key_exists('value', $cell)) {
                $this->setCell($targetRow, $colOffset + 1, $cell['value'], $cell['style'] ?? $defaultStyle);
            } else {
                $this->setCell($targetRow, $colOffset + 1, $cell, $defaultStyle);
            }
        }
        $this->nextRow = $targetRow + 1;
        return $this;
    }

    /**
     * Append multiple rows at once.
     *
     * @param list<list<mixed>> $rows
     * @param ExcelStyle|null   $defaultStyle  Applied to every cell that has no per-cell style.
     * 
     * @return self
     */
    public function appendRows(array $rows, ?ExcelStyle $defaultStyle = null): static
    {
        foreach ($rows as $row) {
            $this->appendRow($row, $defaultStyle);
        }
        return $this;
    }

    // ── Layout & sheet options ────────────────────────────────────────────────

    /**
     * Set the width of a column in Excel character-width units (~7 px per unit).
     *
     * @param int   $col   1-based column index.
     * @param float $width Width in character units (e.g. 12.5).
     * 
     * @return self
     */
    public function setColWidth(int $col, float $width): static
    {
        $this->colWidths[$col] = $width;
        return $this;
    }

    /**
     * Set the height of a row in points.
     *
     * @param int   $row    1-based row index.
     * @param float $height Height in points (e.g. 20.0).
     * 
     * @return self
     */
    public function setRowHeight(int $row, float $height): static
    {
        $this->rowHeights[$row] = $height;
        return $this;
    }

    /**
     * Mark a cell range as merged.  The top-left cell's value is displayed.
     *
     * @param string $range A1-style range, e.g. 'A1:D1'.
     * 
     * @return self
     */
    public function merge(string $range): static
    {
        if (!str_contains($range, ':')) {
            throw new RuntimeException("Merge range must include both endpoints, e.g. 'A1:C3'. Got: {$range}");
        }
        $this->merges[] = $range;
        return $this;
    }

    /**
     * Freeze rows and/or columns at the given cell reference.
     *
     * 'A2' → freeze the top row (row 1 stays visible while scrolling down).
     * 'B1' → freeze the left column.
     * 'B2' → freeze both the top row and the left column.
     *
     * @param string $ref  The first cell of the scrollable region, e.g. 'B2'.
     * 
     * @return self
     */
    public function freeze(string $ref): static
    {
        [$col, $row] = Excel::parseRef($ref);
        $this->freezeAt = [$col, $row];
        return $this;
    }

    /**
     * Enable AutoFilter on the given range (usually the header row).
     *
     * @param string $range A1-style range, e.g. 'A1:H1'.
     * 
     * @return self
     */
    public function autoFilter(string $range): static
    {
        $this->autoFilterRange = $range;
        return $this;
    }

    /**
     * Set the worksheet tab color.
     *
     * @param string $argb 8-char ARGB hex, e.g. 'FF0070C0' (blue).
     * 
     * @return self
     */
    public function tabColor(string $argb): static
    {
        $this->tabColorArgb = $argb;
        return $this;
    }

    // ── Package-internal accessors ────────────────────────────────────────────

    /** 
     * @return array<int, array<int, array{value: mixed, type: string, style: ?ExcelStyle}>>
     **/
    public function getCells(): array
    {
        return $this->cells;
    }

    /** @return array<int, float> */
    public function getColWidths(): array
    {
        return $this->colWidths;
    }

    /** @return array<int, float> */
    public function getRowHeights(): array
    {
        return $this->rowHeights;
    }

    /** @return list<string> */
    public function getMerges(): array
    {
        return $this->merges;
    }

    public function getFreezeAt(): ?array
    {
        return $this->freezeAt;
    }

    public function getAutoFilterRange(): ?string
    {
        return $this->autoFilterRange;
    }

    public function getTabColorArgb(): ?string
    {
        return $this->tabColorArgb;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Infer the XLSX cell type token for a PHP value.
     *
     * @return 'string'|'number'|'bool'|'date'|'formula'
     */
    private function inferType(mixed $value): string
    {
        if ($value instanceof DateTime) {
            return 'date';
        }

        if (is_bool($value)) {
            return 'bool';
        }

        if (is_int($value) || is_float($value)) {
            return 'number';
        }

        if (is_string($value) && str_starts_with($value, '=')) {
            return 'formula';
        }

        return 'string';
    }
}
