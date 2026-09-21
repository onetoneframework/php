<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Document;

use Clover\Classes\Document\Excel\ExcelSharedStrings;
use Clover\Classes\Document\Excel\ExcelSheet;
use Clover\Classes\Document\Excel\ExcelStyle;
use Clover\Classes\Document\Excel\ExcelStyleRegistry;
use DateTime;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;
use function is_int;
use function is_bool;
use function is_float;
use function is_string;
use function array_map;
use function array_values;
use function array_keys;
use function str_contains;
use function strtolower;
use function number_format;
use function intdiv;
use function strlen;
use function sprintf;
use function ord;
use function chr;
use function count;

/**
 * Pure-PHP XLSX reader and writer.  No external dependencies beyond the
 * ZipArchive and SimpleXML extensions (both enabled by default in PHP).
 *
 * ──────────────────────────────────────────────────────────────────────────────
 * READING
 * ──────────────────────────────────────────────────────────────────────────────
 *
 *   $excel = new Excel();
 *
 *   // Read the first sheet (index 0):
 *   $rows = $excel->read('report.xlsx');
 *
 *   // Read a sheet by name:
 *   $rows = $excel->read('report.xlsx', 'Summary');
 *
 *   // Read as associative arrays keyed by column letter:
 *   $rows = $excel->read('report.xlsx', 0, assoc: true);
 *   // → [['A' => 'Name', 'B' => 'Score'], ['A' => 'Alice', 'B' => 92], ...]
 *
 *   // Read every sheet at once:
 *   $allSheets = $excel->readAll('report.xlsx');
 *   // → ['Sheet1' => [[...], ...], 'Summary' => [[...], ...]]
 *
 *   // List sheet names:
 *   $names = $excel->listSheets('report.xlsx');  // ['Sheet1', 'Summary']
 *
 * ──────────────────────────────────────────────────────────────────────────────
 * WRITING
 * ──────────────────────────────────────────────────────────────────────────────
 *
 *   $excel  = new Excel();
 *   $header = (new ExcelStyle())->bold()->bgColor('FF4472C4')->fontColor('FFFFFFFF')->alignH('center');
 *   $money  = (new ExcelStyle())->numberFmt('#,##0.00')->alignH('right');
 *   $date   = (new ExcelStyle())->numberFmt('yyyy-mm-dd');
 *
 *   $sheet = $excel->addSheet('Sales')
 *       ->appendRow(['Month', 'Revenue', 'Date'], $header)
 *       ->appendRow(['January', 98000.50, new DateTime('2024-01-31')], )
 *       ->setColWidth(1, 14)->setColWidth(2, 16)->setColWidth(3, 14)
 *       ->freeze('A2')
 *       ->autoFilter('A1:C1');
 *
 *   // Override style for specific cells:
 *   $sheet->setCellByRef('B2', 98000.50, $money);
 *   $sheet->setCellByRef('C2', new DateTime('2024-01-31'), $date);
 *
 *   $excel->write('output.xlsx');
 *
 *   // Quick static factory for plain data:
 *   Excel::fromRows($data)->write('output.xlsx');
 */
class Excel
{
    /** Custom number format IDs start at 164 (0–163 are Excel built-ins). */
    public const CUSTOM_NUMFMT_BASE = 164;

    /**
     * Built-in Excel number format IDs.
     * These do NOT need a <numFmt> entry in styles.xml.
     */
    private const BUILTIN_NUMFMT = [
        'General' => 0,
        '0' => 1,
        '0.00' => 2,
        '#,##0' => 3,
        '#,##0.00' => 4,
        '0%' => 9,
        '0.00%' => 10,
        'mm-dd-yy' => 14,
        'd-mmm-yy' => 15,
        'd-mmm' => 16,
        'mmm-yy' => 17,
        'h:mm AM/PM' => 18,
        'h:mm:ss AM/PM' => 19,
        'h:mm' => 20,
        'h:mm:ss' => 21,
        'm/d/yy h:mm' => 22,
        '@' => 49,
    ];

    /** @var list<ExcelSheet> */
    private array $sheets = [];

    // ── Sheet management ──────────────────────────────────────────────────────

    /**
     * Add a new worksheet and return it for configuration.
     *
     * @param string $name  Worksheet tab name (must be unique within the workbook).
     */
    public function addSheet(string $name = 'Sheet1'): ExcelSheet
    {
        foreach ($this->sheets as $s) {
            if ($s->name === $name) {
                throw new RuntimeException("Duplicate sheet name: '{$name}'");
            }
        }
        $sheet = new ExcelSheet($name);
        $this->sheets[] = $sheet;
        return $sheet;
    }

    /**
     * Get an existing sheet by 0-based index or by name.
     *
     * @param int|string $nameOrIndex
     * @throws RuntimeException If the sheet does not exist.
     */
    public function getSheet(int|string $nameOrIndex): ExcelSheet
    {
        if (is_int($nameOrIndex)) {
            return $this->sheets[$nameOrIndex]
                ?? throw new RuntimeException("Sheet index {$nameOrIndex} does not exist.");
        }
        foreach ($this->sheets as $s) {
            if ($s->name === $nameOrIndex) {
                return $s;
            }
        }
        $available = implode(', ', array_map(fn($s) => "'{$s->name}'", $this->sheets));
        throw new RuntimeException("Sheet '{$nameOrIndex}' not found. Available: {$available}");
    }

    // ── Static factory helpers ────────────────────────────────────────────────

    /**
     * Create an Excel instance pre-populated with one sheet of plain rows.
     *
     * Useful for quick exports:
     *   Excel::fromRows($data)->write('export.xlsx');
     *
     * @param list<list<mixed>> $rows
     * @param string            $sheetName  Name for the first worksheet.
     */
    public static function fromRows(array $rows, string $sheetName = 'Sheet1'): static
    {
        $excel = new static();
        $excel->addSheet($sheetName)->appendRows($rows);
        return $excel;
    }

    // =========================================================================
    //  READ
    // =========================================================================

    /**
     * Read all rows from one worksheet.
     *
     * Correctly handles:
     *   - Sparse columns (cell gaps from non-contiguous column refs).
     *   - Shared strings, rich-text (multi-run) shared strings.
     *   - Inline strings (<is><t>).
     *   - Numbers, booleans, formula cached values.
     *   - All sheets by name or 0-based index.
     *
     * @param string     $xlsxFile  Path to the .xlsx file.
     * @param int|string $sheet     0-based index or sheet name. Default: first sheet (0).
     * @param bool       $assoc     If true, each row is keyed by column letter ('A', 'B', …).
     *                              If false (default), each row is a 0-indexed array; missing
     *                              cells within the row's column span are filled with ''.
     *
     * @return list<array<int|string, mixed>>
     * @throws RuntimeException
     */
    public function read(string $xlsxFile, int|string $sheet = 0, bool $assoc = false): array
    {
        $zip = $this->openZip($xlsxFile);
        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPath = $this->resolveSheetPath($zip, $sheet);
        $raw = $zip->getFromName($sheetPath);
        $zip->close();

        if ($raw === false) {
            throw new RuntimeException("Cannot read sheet content from: {$sheetPath}");
        }

        $xml = $this->parseXml($raw);
        $rows = [];
        $maxCol = 0;

        foreach ($xml->sheetData->row as $xmlRow) {
            // Rows in XLSX are 1-based; we store 0-based for the returned array
            $rowIdx = (int) $xmlRow['r'] - 1;
            $rowData = [];

            foreach ($xmlRow->c as $c) {
                $colLetter = rtrim((string) $c['r'], '0123456789');
                $colIdx = self::colLetterToIndex($colLetter) - 1;   // 0-based
                $maxCol = max($maxCol, $colIdx);

                $value = $this->readCellValue($c, $sharedStrings);
                $rowData[$assoc ? $colLetter : $colIdx] = $value;
            }

            $rows[$rowIdx] = $rowData;
        }

        if (!$assoc) {
            // Fill column gaps with empty strings so every row has the same span
            foreach ($rows as &$row) {
                for ($i = 0; $i <= $maxCol; $i++) {
                    $row[$i] ??= '';
                }
                ksort($row);
            }
            unset($row);
        }

        ksort($rows);
        return array_values($rows);
    }

    /**
     * Read every worksheet from an XLSX file at once.
     *
     * @param bool $assoc  Passed through to read().
     * @return array<string, list<array<int|string, mixed>>>  Sheet name → rows.
     */
    public function readAll(string $xlsxFile, bool $assoc = false): array
    {
        $names = $this->listSheets($xlsxFile);
        $result = [];
        foreach (array_keys($names) as $index) {
            $result[$names[$index]] = $this->read($xlsxFile, $index, $assoc);
        }
        return $result;
    }

    /**
     * Read a worksheet with style information preserved.
     *
     * Returns both the cell values and a parallel array of ExcelStyle objects
     * reconstructed from the XLSX styles.xml.  This is used internally by
     * exportToPdf() but is also available for callers who need formatted data.
     *
     *   $excel = new Excel();
     *   ['rows' => $rows, 'styles' => $styles] = $excel->readStyled('report.xlsx');
     *
     * @param string     $xlsxFile  Path to the .xlsx file.
     * @param int|string $sheet     0-based index or sheet name.
     * @return array{rows: list<list<mixed>>, styles: list<list<ExcelStyle|null>>}
     * @throws RuntimeException
     */
    public function readStyled(string $xlsxFile, int|string $sheet = 0): array
    {
        $zip = $this->openZip($xlsxFile);
        $sharedStrings = $this->readSharedStrings($zip);
        $sheetPath = $this->resolveSheetPath($zip, $sheet);
        $raw = $zip->getFromName($sheetPath);

        // Parse styles.xml to build ExcelStyle lookup
        $cellStyles = $this->parseStylesXml($zip);

        $zip->close();

        if ($raw === false) {
            throw new RuntimeException("Cannot read sheet content from: {$sheetPath}");
        }

        $xml = $this->parseXml($raw);
        $rowValues = [];
        $rowStyles = [];
        $maxCol = 0;

        foreach ($xml->sheetData->row as $xmlRow) {
            $rowIdx = (int) $xmlRow['r'] - 1;
            $valRow = [];
            $styRow = [];

            foreach ($xmlRow->c as $c) {
                $colLetter = rtrim((string) $c['r'], '0123456789');
                $colIdx = self::colLetterToIndex($colLetter) - 1;
                $maxCol = max($maxCol, $colIdx);

                $value = $this->readCellValue($c, $sharedStrings);
                $valRow[$colIdx] = $value;

                // Look up style index from the s="" attribute
                $sIdx = isset($c['s']) ? (int) $c['s'] : 0;
                $styRow[$colIdx] = $cellStyles[$sIdx] ?? null;
            }

            $rowValues[$rowIdx] = $valRow;
            $rowStyles[$rowIdx] = $styRow;
        }

        // Fill gaps
        foreach ($rowValues as &$row) {
            for ($i = 0; $i <= $maxCol; $i++) {
                $row[$i] ??= '';
            }
            ksort($row);
        }
        unset($row);

        foreach ($rowStyles as &$row) {
            for ($i = 0; $i <= $maxCol; $i++) {
                $row[$i] ??= null;
            }
            ksort($row);
        }
        unset($row);

        ksort($rowValues);
        ksort($rowStyles);

        return [
            'rows' => array_values(array_map('array_values', $rowValues)),
            'styles' => array_values(array_map('array_values', $rowStyles)),
        ];
    }

    /**
     * Read all worksheets with style information preserved.
     *
     * @return array<string, array{rows: list<list<mixed>>, styles: list<list<ExcelStyle|null>>}>
     */
    public function readAllStyled(string $xlsxFile): array
    {
        $names = $this->listSheets($xlsxFile);
        $result = [];
        foreach (array_keys($names) as $index) {
            $result[$names[$index]] = $this->readStyled($xlsxFile, $index);
        }
        return $result;
    }

    /**
     * Return the ordered list of worksheet names in a file.
     *
     * @return array<int, string>  0-based index => sheet name.
     */
    public function listSheets(string $xlsxFile): array
    {
        $zip = $this->openZip($xlsxFile);
        $names = $this->readSheetNames($zip);
        $zip->close();
        return $names;
    }

    // =========================================================================
    //  WRITE
    // =========================================================================

    /**
     * Write all sheets added via addSheet() into an XLSX file.
     *
     * @param string $xlsxFile  Destination path (created or overwritten).
     * @throws RuntimeException
     */
    public function write(string $xlsxFile): bool
    {
        if (empty($this->sheets)) {
            throw new RuntimeException('No sheets to write. Call addSheet() first.');
        }

        // ── Build shared registries across all sheets ──────────────────────
        $styleReg = new ExcelStyleRegistry();
        $ssTable = new ExcelSharedStrings();

        foreach ($this->sheets as $sheet) {
            foreach ($sheet->getCells() as $rowCells) {
                foreach ($rowCells as $cell) {
                    if ($cell['style'] !== null) {
                        $styleReg->register($cell['style']);
                    }
                    if ($cell['type'] === 'string' && (string) $cell['value'] !== '') {
                        $ssTable->add((string) $cell['value']);
                    }
                }
            }
        }

        // ── Open ZIP ──────────────────────────────────────────────────────
        $zip = new ZipArchive();
        if ($zip->open($xlsxFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create file: {$xlsxFile}");
        }

        $add = function (string $name, string $content) use ($zip, $xlsxFile): void {
            if (!$zip->addFromString($name, $content)) {
                $zip->close();
                throw new RuntimeException("Failed to add {$name} to {$xlsxFile}");
            }
        };

        // ── Static package parts ──────────────────────────────────────────
        $n = count($this->sheets);
        $add('[Content_Types].xml', $this->xmlContentTypes($n));
        $add('_rels/.rels', $this->xmlRootRels());
        $add('xl/workbook.xml', $this->xmlWorkbook());
        $add('xl/_rels/workbook.xml.rels', $this->xmlWorkbookRels($n));
        $add('xl/styles.xml', $styleReg->buildXml());
        $add('xl/sharedStrings.xml', $ssTable->buildXml());
        $add('docProps/core.xml', $this->xmlCoreProps());
        $add('docProps/app.xml', $this->xmlAppProps());

        // ── Sheet XML files ───────────────────────────────────────────────
        foreach ($this->sheets as $i => $sheet) {
            $add(
                'xl/worksheets/sheet' . ($i + 1) . '.xml',
                $this->buildSheetXml($sheet, $styleReg, $ssTable)
            );
        }

        if (!$zip->close()) {
            throw new RuntimeException("Failed to finalize ZIP: {$xlsxFile}");
        }

        return true;
    }

    // =========================================================================
    //  PDF EXPORT
    // =========================================================================

    /**
     * Read an existing XLSX file and export it to PDF.
     *
     * Each worksheet becomes a separate section in the output PDF,
     * with the first row optionally treated as a header (repeated on
     * every page for that sheet).
     *
     * Usage:
     *   $excel = new Excel();
     *   $excel->exportToPdf('report.xlsx', 'report.pdf');
     *
     *   // Export a specific sheet:
     *   $excel->exportToPdf('report.xlsx', 'report.pdf', sheet: 'Summary');
     *
     *   // Export with the first row as header:
     *   $excel->exportToPdf('report.xlsx', 'report.pdf', header: true);
     *
     * @param string      $xlsxFile  Path to the source .xlsx file.
     * @param string      $pdfFile   Path for the output PDF (created or overwritten).
     * @param int|string|null $sheet  Sheet index (0-based) or name. Null = all sheets.
     * @param bool        $header    Treat the first row as a repeating header.
     * @param array{title?: string, author?: string, fontSize?: float, headerFontSize?: float} $options  Optional PDF writer configuration.
     *
     * @throws RuntimeException If the file cannot be read or written.
     */
    public function exportToPdf(string $xlsxFile, string $pdfFile, int|string|null $sheet = null, bool $header = true, array $options = []): bool
    {
        $writer = new PDFWriter();

        if (isset($options['title'])) {
            $writer->setTitle($options['title']);
        } elseif ($sheet !== null && is_string($sheet)) {
            $writer->setTitle($sheet);
        }
        if (isset($options['author'])) {
            $writer->setAuthor($options['author']);
        }
        if (isset($options['fontSize'])) {
            $writer->setDefaultFontSize($options['fontSize']);
        }
        if (isset($options['headerFontSize'])) {
            $writer->setHeaderFontSize($options['headerFontSize']);
        }

        if ($sheet !== null) {
            // Single sheet with styles
            $data = $this->readStyled($xlsxFile, $sheet);
            $sheetName = is_string($sheet) ? $sheet : ($this->listSheets($xlsxFile)[$sheet] ?? "Sheet{$sheet}");
            $stringRows = $this->rowsToFormattedStrings($data['rows'], $data['styles']);
            $writer->addPage($stringRows, $data['styles'], [
                'header' => $header,
                'sheetName' => $sheetName,
            ]);
        } else {
            // All sheets with styles
            $allSheets = $this->readAllStyled($xlsxFile);
            foreach ($allSheets as $name => $data) {
                $stringRows = $this->rowsToFormattedStrings($data['rows'], $data['styles']);
                $writer->addPage($stringRows, $data['styles'], [
                    'header' => $header,
                    'sheetName' => $name,
                ]);
            }
        }

        return $writer->write($pdfFile);
    }

    /**
     * Export the in-memory sheets (created via addSheet()) to a PDF.
     *
     * This allows building a workbook programmatically and exporting
     * to PDF without writing an intermediate .xlsx file.
     *
     * Usage:
     *   $excel = new Excel();
     *   $header = (new ExcelStyle())->bold()->bgColor('FF4472C4')->fontColor('FFFFFFFF');
     *
     *   $excel->addSheet('Revenue')
     *       ->appendRow(['Month', 'Amount'], $header)
     *       ->appendRow(['January', 12500])
     *       ->appendRow(['February', 14200]);
     *
     *   $excel->exportSheetsToPdf('revenue.pdf');
     *
     * @param string $pdfFile  Path for the output PDF.
     * @param bool   $header   Treat the first row of each sheet as a repeating header.
     * @param array{title?: string,author?: string,fontSize?: float,headerFontSize?: float} $options  Optional PDF writer configuration.
     *
     * @throws RuntimeException If no sheets exist or the file cannot be written.
     */
    public function exportSheetsToPdf(string $pdfFile, bool $header = true, array $options = []): bool
    {
        if (empty($this->sheets)) {
            throw new RuntimeException('No sheets to export. Call addSheet() first.');
        }

        $writer = new PDFWriter();

        if (isset($options['title'])) {
            $writer->setTitle($options['title']);
        }
        if (isset($options['author'])) {
            $writer->setAuthor($options['author']);
        }
        if (isset($options['fontSize'])) {
            $writer->setDefaultFontSize($options['fontSize']);
        }
        if (isset($options['headerFontSize'])) {
            $writer->setHeaderFontSize($options['headerFontSize']);
        }

        foreach ($this->sheets as $sheet) {
            $cells = $sheet->getCells();
            if (empty($cells)) {
                continue;
            }

            // Convert cell data to a 2D string array and extract styles
            [$stringRows, $styleRows, $colWidthHints, $merges] = $this->sheetToArrays($sheet);

            $writer->addPage($stringRows, $styleRows, [
                'header' => $header,
                'sheetName' => $sheet->name,
                'colWidths' => !empty($colWidthHints) ? $colWidthHints : null,
                'merges' => $merges,
            ]);
        }

        return $writer->write($pdfFile);
    }

    // ── PDF export helpers ────────────────────────────────────────────────

    /**
     * Convert read() output rows to all-string 2D arrays for the PDF writer.
     *
     * @param list<array<int|string, mixed>> $rows
     * @return list<list<string>>
     */
    private function rowsToStrings(array $rows): array
    {
        return array_map(
            fn(array $row) => array_map(
                fn(mixed $v) => $this->valueToString($v),
                array_values($row)
            ),
            $rows
        );
    }

    /**
     * Convert read() output rows to formatted string arrays using style info.
     *
     * Applies Excel number formats (e.g. '#,##0.00', '0.00%', 'yyyy-mm-dd')
     * to numeric values before stringifying them.
     *
     * @param list<list<mixed>>            $rows
     * @param list<list<ExcelStyle|null>>  $styles
     * @return list<list<string>>
     */
    private function rowsToFormattedStrings(array $rows, array $styles): array
    {
        $result = [];
        foreach ($rows as $ri => $row) {
            $stringRow = [];
            foreach (array_values($row) as $ci => $value) {
                $style = $styles[$ri][$ci] ?? null;
                $stringRow[] = $this->formatCellValue($value, $style);
            }
            $result[] = $stringRow;
        }
        return $result;
    }

    /**
     * Convert an ExcelSheet's cell data into parallel string and style arrays.
     *
     * @return array{
     *  0: list<list<string>>, 
     *  1: list<list<ExcelStyle|null>>, 
     *  2: list<float>, 
     *  3: list<array{
     *      startRow: int, 
     *      startCol: int, 
     *      endRow: int, 
     *      endCol: int
     *  }>
     * }
     */
    private function sheetToArrays(ExcelSheet $sheet): array
    {
        $cells = $sheet->getCells();
        if (empty($cells)) {
            return [[], [], [], []];
        }

        // Determine row and column span
        $minRow = min(array_keys($cells));
        $maxRow = max(array_keys($cells));
        $maxCol = 1;
        foreach ($cells as $rowCells) {
            if (!empty($rowCells)) {
                $maxCol = max($maxCol, max(array_keys($rowCells)));
            }
        }

        $stringRows = [];
        $styleRows = [];

        for ($r = $minRow; $r <= $maxRow; $r++) {
            $sRow = [];
            $stRow = [];
            for ($c = 1; $c <= $maxCol; $c++) {
                if (isset($cells[$r][$c])) {
                    $cell = $cells[$r][$c];
                    $sRow[] = $this->formatCellValue($cell['value'], $cell['style']);
                    $stRow[] = $cell['style'];
                } else {
                    $sRow[] = '';
                    $stRow[] = null;
                }
            }
            $stringRows[] = $sRow;
            $styleRows[] = $stRow;
        }

        // Convert column width hints (character units → points, approx 7px per unit)
        $colWidthHints = [];
        $definedWidths = $sheet->getColWidths();
        if (!empty($definedWidths)) {
            for ($c = 1; $c <= $maxCol; $c++) {
                $colWidthHints[] = isset($definedWidths[$c])
                    ? $definedWidths[$c] * 7.0
                    : 60.0; // default 60pt
            }
        }

        // Convert merge ranges from A1-style to 0-based row/col indices
        $merges = [];
        foreach ($sheet->getMerges() as $range) {
            [$start, $end] = explode(':', $range);
            [$sc, $sr] = self::parseRef($start);
            [$ec, $er] = self::parseRef($end);
            // Convert from 1-based sheet coords to 0-based array indices
            $merges[] = [
                'startRow' => $sr - $minRow,
                'startCol' => $sc - 1,
                'endRow' => $er - $minRow,
                'endCol' => $ec - 1,
            ];
        }

        return [$stringRows, $styleRows, $colWidthHints, $merges];
    }

    /**
     * Stringify any cell value for PDF rendering (without number format).
     */
    private function valueToString(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d');
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }
        return (string) $value;
    }

    /**
     * Format a cell value using its ExcelStyle number format.
     *
     * Applies common Excel number format patterns to numeric values:
     *   '#,##0'       → thousand separators, no decimals
     *   '#,##0.00'    → thousand separators, 2 decimals
     *   '0.00%'       → percentage with 2 decimals
     *   'yyyy-mm-dd'  → date format (for OLE serial numbers)
     *   '@'           → text (no formatting)
     */
    private function formatCellValue(mixed $value, ?ExcelStyle $style): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if ($value instanceof DateTime) {
            // Apply date format from style if available
            $fmt = $style?->numberFmt;
            if ($fmt !== null && $this->isDateFormat($fmt)) {
                return $value->format($this->excelDateToPhpFormat($fmt));
            }
            return $value->format('Y-m-d');
        }
        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        // Apply number format only to numeric values
        if ($style !== null && $style->numberFmt !== null && (is_int($value) || is_float($value))) {
            return $this->applyNumberFormat($value, $style->numberFmt);
        }

        return (string) $value;
    }

    /**
     * Apply an Excel number format string to a numeric value.
     */
    private function applyNumberFormat(int|float $value, string $fmt): string
    {
        // Text format — return as-is
        if ($fmt === '@') {
            return (string) $value;
        }

        // Check if this is a date/time format applied to a serial number
        if ($this->isDateFormat($fmt) && $value > 0) {
            return $this->serialToDateString($value, $fmt);
        }

        // Percentage formats (multiply by 100)
        if (str_contains($fmt, '%')) {
            $pctValue = $value * 100;
            // Count decimal places from the format
            $decimals = 0;
            if (preg_match('/0\.(0+)%/', $fmt, $m)) {
                $decimals = strlen($m[1]);
            }
            return number_format($pctValue, $decimals) . '%';
        }

        // Scientific notation
        if (stripos($fmt, 'E+') !== false || stripos($fmt, 'E-') !== false) {
            $decimals = 2;
            if (preg_match('/0\.(0+)E/i', $fmt, $m)) {
                $decimals = strlen($m[1]);
            }
            return sprintf("%.{$decimals}E", $value);
        }

        // Numeric formats with thousand separators and decimals
        $hasThousands = str_contains($fmt, ',');
        $decimals = 0;
        if (preg_match('/\.(0+)/', $fmt, $m)) {
            $decimals = strlen($m[1]);
        } elseif (preg_match('/\.(#+)/', $fmt, $m)) {
            // '#' means optional decimals
            $maxDecimals = strlen($m[1]);
            $decimals = min($maxDecimals, max(0, strlen((string) ($value - (int) $value)) - 2));
        }

        if ($hasThousands || $decimals > 0) {
            return number_format((float) $value, $decimals, '.', $hasThousands ? ',' : '');
        }

        return (string) $value;
    }

    /**
     * Check whether a format string is a date/time format.
     */
    private function isDateFormat(string $fmt): bool
    {
        $lower = strtolower($fmt);
        // Remove quoted literal strings before checking
        $cleaned = preg_replace('/"[^"]*"|\\\\./i', '', $lower);
        return (bool) preg_match('/[ymdhs]/', $cleaned);
    }

    /**
     * Convert an OLE serial date number to a formatted date string.
     */
    private function serialToDateString(float $serial, string $fmt): string
    {
        // Excel epoch: 1899-12-30 (accounting for the Lotus 1-2-3 leap year bug)
        $epoch = new DateTime('1899-12-30');
        $days = (int) $serial;
        $fraction = $serial - $days;

        $date = clone $epoch;
        $date->modify("+{$days} days");

        // Add time component
        $totalSeconds = (int) round($fraction * 86400);
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;
        $date->setTime($hours, $minutes, $seconds);

        return $date->format($this->excelDateToPhpFormat($fmt));
    }

    /**
     * Convert an Excel date format string to a PHP date() format string.
     *
     *   'yyyy-mm-dd'    → 'Y-m-d'
     *   'dd/mm/yyyy'    → 'd/m/Y'
     *   'h:mm AM/PM'    → 'g:i A'
     *   'm/d/yy h:mm'   → 'n/j/y G:i'
     */
    private function excelDateToPhpFormat(string $excelFmt): string
    {
        // Remove color/condition codes like [Red], [$-409], etc.
        $fmt = preg_replace('/\[[^\]]*\]/', '', $excelFmt);

        $map = [
            'yyyy' => 'Y',
            'yy' => 'y',
            'mmmm' => 'F',
            'mmm' => 'M',
            'mm' => 'm',
            'm' => 'n',
            'dddd' => 'l',
            'ddd' => 'D',
            'dd' => 'd',
            'd' => 'j',
            'hh' => 'H',
            'h' => 'G',
            'ss' => 's',
            'AM/PM' => 'A',
            'am/pm' => 'A',
        ];

        // Handle 'mm' context: if preceded by 'h' or 'hh', it's minutes not months
        // Replace minute patterns first
        $fmt = preg_replace('/(?<=h|H):mm/', ':i', $fmt);
        $fmt = preg_replace('/(?<=h|H):m\b/', ':i', $fmt);
        $fmt = str_replace(':mm', ':i', $fmt);

        // Apply remaining mappings (longest first)
        foreach ($map as $excel => $php) {
            $fmt = str_replace($excel, $php, $fmt);
        }

        return $fmt;
    }

    // =========================================================================
    //  Static utilities
    // =========================================================================

    /**
     * Convert a column letter string to a 1-based integer index.
     *
     *   'A'  →  1
     *   'Z'  → 26
     *   'AA' → 27
     */
    public static function colLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        for ($i = 0, $len = strlen($letters); $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index;
    }

    /**
     * Convert a 1-based integer column index to a letter string.
     *
     *   1  → 'A'
     *   26 → 'Z'
     *   27 → 'AA'
     */
    public static function colIndexToLetter(int $index): string
    {
        $letters = '';
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $index = intdiv($index - 1, 26);
        }
        return $letters;
    }

    /**
     * Parse an A1-style cell reference into [colIndex, rowIndex] (both 1-based).
     *
     *   'A1'  → [1, 1]
     *   'C10' → [3, 10]
     *
     * @return array{0: int, 1: int}
     * @throws RuntimeException
     */
    public static function parseRef(string $ref): array
    {
        if (!preg_match('/^([A-Za-z]+)(\d+)$/', $ref, $m)) {
            throw new RuntimeException("Invalid cell reference: '{$ref}'");
        }
        return [self::colLetterToIndex($m[1]), (int) $m[2]];
    }

    // =========================================================================
    //  Private – read helpers
    // =========================================================================

    /** Open a ZIP archive with basic error handling. */
    private function openZip(string $path): ZipArchive
    {
        if (!is_readable($path)) {
            throw new RuntimeException("File not readable: {$path}");
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("Failed to open ZIP/XLSX: {$path}");
        }
        return $zip;
    }

    /**
     * Build the shared-strings array from xl/sharedStrings.xml.
     *
     * Handles both plain-text (<si><t>) and rich-text (<si><r><t>…) entries.
     *
     * @return list<string>
     */
    private function readSharedStrings(ZipArchive $zip): array
    {
        $strings = [];
        $xml = $this->zipXml($zip, 'xl/sharedStrings.xml');
        if ($xml === null) {
            return $strings;
        }

        foreach ($xml->si as $si) {
            if (isset($si->t)) {
                // Plain text entry
                $strings[] = (string) $si->t;
            } else {
                // Rich text: concatenate all run texts
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string) $r->t;
                }
                $strings[] = $text;
            }
        }
        return $strings;
    }

    /**
     * Parse xl/styles.xml and build an array of ExcelStyle objects indexed by xf index.
     *
     * This reconstructs font, fill, border, number-format, and alignment
     * properties for each <xf> entry in <cellXfs>, producing ExcelStyle
     * objects that can be passed through to the PDF writer.
     *
     * @return array<int, ExcelStyle>  xf index → ExcelStyle.
     */
    private function parseStylesXml(ZipArchive $zip): array
    {
        $xml = $this->zipXml($zip, 'xl/styles.xml');
        if ($xml === null) {
            return [];
        }

        // Register the spreadsheetml namespace for reliable XPath/child access
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

        // ── Parse fonts ──────────────────────────────────────────────────
        $fonts = [];
        foreach ($xml->fonts->font ?? [] as $font) {
            $f = [
                'bold' => isset($font->b),
                'italic' => isset($font->i),
                'underline' => isset($font->u),
                'fontSize' => isset($font->sz) ? (int) $font->sz['val'] : 11,
                'fontName' => isset($font->name) ? (string) $font->name['val'] : 'Arial',
                'fontColor' => null,
            ];
            if (isset($font->color['rgb'])) {
                $f['fontColor'] = (string) $font->color['rgb'];
            }
            $fonts[] = $f;
        }

        // ── Parse fills ──────────────────────────────────────────────────
        $fills = [];
        foreach ($xml->fills->fill ?? [] as $fill) {
            $bgColor = null;
            if (isset($fill->patternFill->fgColor['rgb'])) {
                $bgColor = (string) $fill->patternFill->fgColor['rgb'];
            } elseif (
                isset($fill->patternFill['patternType'])
                && (string) $fill->patternFill['patternType'] === 'solid'
                && isset($fill->patternFill->fgColor['theme'])
            ) {
                // Theme colors are complex; skip for now
                $bgColor = null;
            }
            $fills[] = $bgColor;
        }

        // ── Parse borders ────────────────────────────────────────────────
        $borders = [];
        foreach ($xml->borders->border ?? [] as $border) {
            $style = null;
            $color = null;

            // Check left border as representative (all-sides approach)
            if (isset($border->left['style'])) {
                $style = (string) $border->left['style'];
                if (isset($border->left->color['rgb'])) {
                    $color = (string) $border->left->color['rgb'];
                }
            } elseif (isset($border->top['style'])) {
                $style = (string) $border->top['style'];
                if (isset($border->top->color['rgb'])) {
                    $color = (string) $border->top->color['rgb'];
                }
            }
            $borders[] = ['style' => $style, 'color' => $color];
        }

        // ── Parse number formats ─────────────────────────────────────────
        $numFmts = [];
        // Built-in formats
        $builtinFmts = [
            0 => 'General',
            1 => '0',
            2 => '0.00',
            3 => '#,##0',
            4 => '#,##0.00',
            9 => '0%',
            10 => '0.00%',
            11 => '0.00E+00',
            12 => '# ?/?',
            13 => '# ??/??',
            14 => 'mm-dd-yy',
            15 => 'd-mmm-yy',
            16 => 'd-mmm',
            17 => 'mmm-yy',
            18 => 'h:mm AM/PM',
            19 => 'h:mm:ss AM/PM',
            20 => 'h:mm',
            21 => 'h:mm:ss',
            22 => 'm/d/yy h:mm',
            37 => '#,##0 ;(#,##0)',
            38 => '#,##0 ;[Red](#,##0)',
            39 => '#,##0.00;(#,##0.00)',
            40 => '#,##0.00;[Red](#,##0.00)',
            45 => 'mm:ss',
            46 => '[h]:mm:ss',
            47 => 'mmss.0',
            48 => '##0.0E+0',
            49 => '@',
        ];
        foreach ($builtinFmts as $id => $fmt) {
            $numFmts[$id] = $fmt;
        }
        // Custom formats from the file
        foreach ($xml->numFmts->numFmt ?? [] as $nf) {
            $id = (int) $nf['numFmtId'];
            $numFmts[$id] = (string) $nf['formatCode'];
        }

        // ── Parse cellXfs (the actual cell style records) ────────────────
        $cellStyles = [];
        $xfIdx = 0;
        foreach ($xml->cellXfs->xf ?? [] as $xf) {
            $fontId = (int) ($xf['fontId'] ?? 0);
            $fillId = (int) ($xf['fillId'] ?? 0);
            $borderId = (int) ($xf['borderId'] ?? 0);
            $numFmtId = (int) ($xf['numFmtId'] ?? 0);

            // Start with defaults
            $args = [
                'fontName' => 'Arial',
                'fontSize' => 11,
                'bold' => false,
                'italic' => false,
                'underline' => false,
                'fontColor' => null,
                'bgColor' => null,
                'alignH' => null,
                'alignV' => null,
                'wrapText' => false,
                'numberFmt' => null,
                'borderStyle' => null,
                'borderColor' => null,
            ];

            // Apply font
            if (isset($fonts[$fontId])) {
                $f = $fonts[$fontId];
                $args['fontName'] = $f['fontName'];
                $args['fontSize'] = $f['fontSize'];
                $args['bold'] = $f['bold'];
                $args['italic'] = $f['italic'];
                $args['underline'] = $f['underline'];
                $args['fontColor'] = $f['fontColor'];
            }

            // Apply fill (skip index 0=none, 1=gray125)
            if ($fillId >= 2 && isset($fills[$fillId]) && $fills[$fillId] !== null) {
                $args['bgColor'] = $fills[$fillId];
            }

            // Apply border
            if ($borderId > 0 && isset($borders[$borderId])) {
                $b = $borders[$borderId];
                $args['borderStyle'] = $b['style'];
                $args['borderColor'] = $b['color'] ?? 'FF000000';
            }

            // Apply number format (skip General/0)
            if ($numFmtId > 0 && isset($numFmts[$numFmtId]) && $numFmts[$numFmtId] !== 'General') {
                $args['numberFmt'] = $numFmts[$numFmtId];
            }

            // Apply alignment
            if (isset($xf->alignment)) {
                $al = $xf->alignment;
                if (isset($al['horizontal'])) {
                    $args['alignH'] = (string) $al['horizontal'];
                }
                if (isset($al['vertical'])) {
                    $args['alignV'] = (string) $al['vertical'];
                }
                if (isset($al['wrapText']) && (string) $al['wrapText'] === '1') {
                    $args['wrapText'] = true;
                }
            }

            // Only create a non-default style
            $isDefault = !$args['bold'] && !$args['italic'] && !$args['underline']
                && $args['fontColor'] === null && $args['bgColor'] === null
                && $args['alignH'] === null && $args['alignV'] === null
                && !$args['wrapText'] && $args['numberFmt'] === null
                && $args['borderStyle'] === null;

            $cellStyles[$xfIdx] = $isDefault ? null : new ExcelStyle(...$args);
            $xfIdx++;
        }

        return $cellStyles;
    }

    /**
     * Determine the zip path of the requested sheet.
     *
     * @param int|string $sheet  0-based index or sheet name.
     * @throws RuntimeException
     */
    private function resolveSheetPath(ZipArchive $zip, int|string $sheet): string
    {
        $names = $this->readSheetNames($zip);

        if (is_int($sheet)) {
            $sheetNum = $sheet + 1;
            if (!isset($names[$sheet])) {
                throw new RuntimeException("Sheet index {$sheet} does not exist (file has " . count($names) . " sheet(s)).");
            }
        } else {
            $idx = array_search($sheet, $names, true);
            if ($idx === false) {
                throw new RuntimeException("Sheet '{$sheet}' not found. Available: " . implode(', ', array_map(fn($n) => "'{$n}'", $names)));
            }
            $sheetNum = $idx + 1;
        }

        return "xl/worksheets/sheet{$sheetNum}.xml";
    }

    /**
     * Read the ordered list of sheet names from xl/workbook.xml.
     *
     * @return array<int, string>  0-based index => sheet name.
     */
    private function readSheetNames(ZipArchive $zip): array
    {
        $xml = $this->zipXml($zip, 'xl/workbook.xml');
        if ($xml === null) {
            throw new RuntimeException('xl/workbook.xml not found in ZIP');
        }

        $names = [];

        // Try the default namespace path first
        foreach ($xml->sheets->sheet ?? [] as $s) {
            $names[] = (string) ($s['name'] ?? '');
        }

        // Fallback: XPath with the standard spreadsheetml namespace
        if (empty($names)) {
            $xml->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            foreach ($xml->xpath('//ns:sheet') ?: [] as $s) {
                $names[] = (string) ($s['name'] ?? '');
            }
        }

        return $names;
    }

    /**
     * Extract the typed value from a single <c> element.
     *
     * Recognized cell types (OOXML §18.18.11):
     *   s           → shared string index
     *   b           → boolean (0 / 1)
     *   e           → error string
     *   str         → formula result as string
     *   inlineStr   → inline string in <is><t>
     *   (empty)     → numeric value (int or float)
     *
     * @param list<string> $sharedStrings
     */
    private function readCellValue(SimpleXMLElement $c, array $sharedStrings): mixed
    {
        $type = (string) ($c['t'] ?? '');

        if ($type === 'inlineStr') {
            return isset($c->is->t) ? (string) $c->is->t : '';
        }

        if (!isset($c->v)) {
            return '';
        }

        $raw = (string) $c->v;

        return match ($type) {
            's' => $sharedStrings[(int) $raw] ?? '',
            'b' => $raw === '1',
            'e' => $raw,   // propagate error string as-is
            'str' => $raw,   // formula computed string
            default => $this->coerceNumeric($raw),
        };
    }

    /** Cast a numeric string to int if it has no decimal part, otherwise float. */
    private function coerceNumeric(string $raw): int|float|string
    {
        if (!is_numeric($raw)) {
            return $raw;
        }
        return str_contains($raw, '.') ? (float) $raw : (int) $raw;
    }

    /**
     * Load a file from the ZIP as a SimpleXMLElement, or return null if absent.
     */
    private function zipXml(ZipArchive $zip, string $path): ?SimpleXMLElement
    {
        $content = $zip->getFromName($path);
        if ($content === false) {
            return null;
        }
        $xml = simplexml_load_string($content);
        return $xml === false ? null : $xml;
    }

    /** Parse XML content and throw a descriptive error on failure. */
    private function parseXml(string $content): SimpleXMLElement
    {
        $xml = simplexml_load_string($content);
        if ($xml === false) {
            throw new RuntimeException('Failed to parse XML content.');
        }
        return $xml;
    }

    // =========================================================================
    //  Private – write helpers (static XML strings)
    // =========================================================================

    private function xmlContentTypes(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= "\n  <Override PartName=\"/xl/worksheets/sheet{$i}.xml\""
                . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml"  ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml"      ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/styles.xml"        ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>
  <Override PartName="/docProps/core.xml"    ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
  <Override PartName="/docProps/app.xml"     ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>{$overrides}
</Types>
XML;
    }

    private function xmlRootRels(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument"      Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties"   Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    private function xmlWorkbook(): string
    {
        $sheetElements = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $name = htmlspecialchars($sheet->name, ENT_XML1 | ENT_QUOTES, 'UTF-8');
            $sheetElements .= "\n    <sheet name=\"{$name}\" sheetId=\"{$n}\" r:id=\"rId{$n}\"/>";
        }
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>{$sheetElements}
  </sheets>
</workbook>
XML;
    }

    private function xmlWorkbookRels(int $sheetCount): string
    {
        $body = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $body .= "\n  <Relationship Id=\"rId{$i}\""
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . " Target=\"worksheets/sheet{$i}.xml\"/>";
        }
        $ssId = $sheetCount + 1;
        $stId = $sheetCount + 2;
        $body .= "\n  <Relationship Id=\"rId{$ssId}\""
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings"'
            . ' Target="sharedStrings.xml"/>';
        $body .= "\n  <Relationship Id=\"rId{$stId}\""
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>';
        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $body
            . "\n</Relationships>";
    }

    private function xmlCoreProps(): string
    {
        $now = (new DateTime())->format('Y-m-d\TH:i:s\Z');
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:creator>PHP Excel</dc:creator>
  <cp:lastModifiedBy>PHP Excel</cp:lastModifiedBy>
  <dcterms:created xsi:type="dcterms:W3CDTF">{$now}</dcterms:created>
  <dcterms:modified xsi:type="dcterms:W3CDTF">{$now}</dcterms:modified>
</cp:coreProperties>
XML;
    }

    private function xmlAppProps(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">
  <Application>PHP Excel Writer</Application>
  <DocSecurity>0</DocSecurity>
  <ScaleCrop>false</ScaleCrop>
  <SharedDoc>false</SharedDoc>
</Properties>
XML;
    }

    // =========================================================================
    //  Private – sheet XML builder
    // =========================================================================

    /**
     * Compile the full worksheet XML for one ExcelSheet.
     */
    private function buildSheetXml(ExcelSheet $sheet, ExcelStyleRegistry $styleReg, ExcelSharedStrings $ssTable): string
    {
        $cells = $sheet->getCells();

        // ── <sheetPr> (tab color) ──────────────────────────────────────────
        $sheetPr = '';
        if (($tc = $sheet->getTabColorArgb()) !== null) {
            $sheetPr = "<sheetPr><tabColor rgb=\"{$tc}\"/></sheetPr>";
        }

        // ── <sheetViews> (freeze pane) ────────────────────────────────────
        $sheetView = '<sheetViews><sheetView workbookViewId="0">';
        if (($fr = $sheet->getFreezeAt()) !== null) {
            [$fcol, $frow] = $fr;
            $topLeft = self::colIndexToLetter($fcol) . $frow;
            $xSplit = $fcol - 1;
            $ySplit = $frow - 1;
            $sheetView .= "<pane xSplit=\"{$xSplit}\" ySplit=\"{$ySplit}\""
                . " topLeftCell=\"{$topLeft}\" activePane=\"bottomRight\" state=\"frozen\"/>";
        }
        $sheetView .= '</sheetView></sheetViews>';

        // ── <cols> (column widths) ────────────────────────────────────────
        $cols = '';
        if (!empty($sheet->getColWidths())) {
            $cols = '<cols>';
            foreach ($sheet->getColWidths() as $col => $width) {
                $cols .= "<col min=\"{$col}\" max=\"{$col}\" width=\"{$width}\" customWidth=\"1\"/>";
            }
            $cols .= '</cols>';
        }

        // ── <sheetData> ───────────────────────────────────────────────────
        $sheetData = '<sheetData>';
        $rowHeights = $sheet->getRowHeights();

        foreach ($cells as $rowIdx => $rowCells) {
            $htAttr = '';
            if (isset($rowHeights[$rowIdx])) {
                $h = $rowHeights[$rowIdx];
                $htAttr = " ht=\"{$h}\" customHeight=\"1\"";
            }
            $sheetData .= "<row r=\"{$rowIdx}\"{$htAttr}>";

            ksort($rowCells);
            foreach ($rowCells as $colIdx => $cell) {
                $ref = self::colIndexToLetter($colIdx) . $rowIdx;
                $sId = $cell['style'] !== null ? $styleReg->getId($cell['style']) : 0;
                $sAttr = $sId > 0 ? " s=\"{$sId}\"" : '';
                $sheetData .= $this->buildCellXml($ref, $cell, $sAttr, $ssTable);
            }

            $sheetData .= '</row>';
        }
        $sheetData .= '</sheetData>';

        // ── <autoFilter> ──────────────────────────────────────────────────
        $autoFilter = '';
        if (($af = $sheet->getAutoFilterRange()) !== null) {
            $autoFilter = "<autoFilter ref=\"{$af}\"/>";
        }

        // ── <mergeCells> ──────────────────────────────────────────────────
        $mergeCells = '';
        if (!empty($sheet->getMerges())) {
            $count = count($sheet->getMerges());
            $mergeCells = "<mergeCells count=\"{$count}\">";
            foreach ($sheet->getMerges() as $range) {
                $mergeCells .= "<mergeCell ref=\"{$range}\"/>";
            }
            $mergeCells .= '</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' . $sheetPr . $sheetView . $cols . $sheetData . $autoFilter . $mergeCells. '</worksheet>';
    }

    /**
     * Build the <c> element XML for a single cell.
     *
     * Type mapping:
     *   string  → shared string (<c t="s"><v>N</v></c>) or inline fallback
     *   number  → numeric value (<c><v>N</v></c>)
     *   bool    → boolean (<c t="b"><v>0|1</v></c>)
     *   date    → OLE serial (<c><v>N.N</v></c>); caller should apply a date style
     *   formula → formula element (<c><f>…</f></c>)
     */
    private function buildCellXml(string $ref, array $cell, string $sAttr, ExcelSharedStrings $ssTable): string
    {
        $value = $cell['value'];
        $type = $cell['type'];

        if ($value === null) {
            return $sAttr !== '' ? "<c r=\"{$ref}\"{$sAttr}/>" : '';
        }
        if ($value === '') {
            return "<c r=\"{$ref}\"{$sAttr}/>";
        }

        return match ($type) {
            'string' => $this->buildStringCell($ref, (string) $value, $sAttr, $ssTable),
            'number' => "<c r=\"{$ref}\"{$sAttr}><v>{$value}</v></c>",
            'bool' => "<c r=\"{$ref}\" t=\"b\"{$sAttr}><v>" . ($value ? '1' : '0') . '</v></c>',
            'date' => "<c r=\"{$ref}\"{$sAttr}><v>" . $this->dateToSerial($value) . '</v></c>',
            'formula' => "<c r=\"{$ref}\"{$sAttr}><f>"
            . htmlspecialchars(ltrim((string) $value, '='), ENT_XML1, 'UTF-8')
            . '</f></c>',
            default => $this->buildStringCell($ref, (string) $value, $sAttr, $ssTable),
        };
    }

    /**
     * Build a string cell using a shared-string index when available.
     * Falls back to an inline string if the value was not pre-registered.
     */
    private function buildStringCell(string $ref, string $value, string $sAttr, ExcelSharedStrings $ssTable): string
    {
        $idx = $ssTable->getIndex($value);
        if ($idx !== null) {
            return "<c r=\"{$ref}\" t=\"s\"{$sAttr}><v>{$idx}</v></c>";
        }
        // Inline fallback (should be rare; only for values not collected during pre-scan)
        $escaped = htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        return "<c r=\"{$ref}\" t=\"inlineStr\"{$sAttr}><is><t>{$escaped}</t></is></c>";
    }

    /**
     * Convert a PHP DateTime to an Excel OLE Automation date serial number.
     *
     * Excel's epoch is 1899-12-30 (with a deliberate 1900 leap-year bug that
     * added one extra day; we compensate by using the 30th as the epoch).
     * The fractional part encodes the time-of-day.
     */
    private function dateToSerial(DateTime $dt): float
    {
        $epoch = new DateTime('1899-12-30');
        $days = (int) $epoch->diff($dt)->days;
        $time = ((int) $dt->format('H') * 3600 + (int) $dt->format('i') * 60 + (int) $dt->format('s')) / 86400;
        return $days + $time;
    }
}
