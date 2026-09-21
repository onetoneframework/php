<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Document;

use Clover\Classes\Document\Excel\ExcelStyle;
use RuntimeException;
use function strlen;
use function count;
use function sprintf;
use function implode;
use function explode;
use function str_pad;
use function str_replace;
use function substr;
use function max;
use function min;
use function array_map;
use function array_fill;
use function array_sum;
use function array_unshift;
use function hexdec;
use function date;
use function is_dir;
use function mkdir;
use function file_put_contents;
use function dirname;

/**
 * Pure-PHP PDF writer for rendering tabular data to PDF documents.
 *
 * No external dependencies — generates valid PDF 1.4 using built-in
 * Type1 fonts (Helvetica family) and manual content-stream construction.
 *
 * ──────────────────────────────────────────────────────────────────────────────
 * USAGE (standalone)
 * ──────────────────────────────────────────────────────────────────────────────
 *
 *   $writer = new PDFWriter();
 *   $writer->setTitle('Monthly Report');
 *   $writer->addPage([
 *       ['Name', 'Revenue', 'Units'],
 *       ['Alice', '98000', '142'],
 *       ['Bob',   '87500', '118'],
 *   ], [
 *       'header'      => true,       // first row is header
 *       'colWidths'   => [200, 150, 100],
 *       'sheetName'   => 'Sales',
 *   ]);
 *   $writer->write('report.pdf');
 *
 * ──────────────────────────────────────────────────────────────────────────────
 * USAGE (from Excel)
 * ──────────────────────────────────────────────────────────────────────────────
 *
 *   $excel = new Excel();
 *   $excel->exportToPdf('input.xlsx', 'output.pdf');
 *
 *   // Or with write-mode sheets:
 *   $excel->addSheet('Sales')->appendRow(['A', 'B'])->appendRow([1, 2]);
 *   $excel->exportSheetsToPdf('output.pdf');
 */
class PDFWriter
{
    // ── Page constants ────────────────────────────────────────────────────

    /** A4 width in points */
    private const PAGE_W_PORTRAIT = 595.28;
    /** A4 height in points */
    private const PAGE_H_PORTRAIT = 841.89;

    /** Page margins in points (≈20 mm) */
    private const MARGIN_TOP = 56.69;
    private const MARGIN_BOTTOM = 56.69;
    private const MARGIN_LEFT = 56.69;
    private const MARGIN_RIGHT = 56.69;

    // ── Font metrics (approximate for Helvetica) ──────────────────────────

    /**
     * Average character width as a fraction of font size.
     * Helvetica averages ~0.52 of the em square per glyph.
     */
    private const CHAR_WIDTH_FACTOR = 0.52;

    /** Vertical line height multiplier relative to font size. */
    private const LINE_HEIGHT_FACTOR = 1.4;

    // ── Font IDs for built-in PDF base 14 fonts ──────────────────────────

    private const FONT_REGULAR = 'Helvetica';
    private const FONT_BOLD = 'Helvetica-Bold';
    private const FONT_ITALIC = 'Helvetica-Oblique';
    private const FONT_BOLD_ITALIC = 'Helvetica-BoldOblique';

    // ── Border style widths (mapping Excel style names → PDF widths) ─────

    private const BORDER_WIDTHS = [
        'thin' => 0.5,
        'medium' => 1.0,
        'thick' => 1.5,
        'dashed' => 0.5,
        'dotted' => 0.5,
        'double' => 0.5,
    ];

    // ── State ─────────────────────────────────────────────────────────────

    /** @var list<array{rows: list<list<string>>, styles: list<list<ExcelStyle|null>>, options: array}> */
    private array $pages = [];

    private string $title = '';
    private string $author = 'PHP PDFWriter';
    private float $defaultFontSize = 9.0;
    private float $headerFontSize = 10.0;
    private float $cellPadding = 4.0;
    private float $borderWidth = 0.5;

    /** 'portrait' | 'landscape' | 'auto' */
    private string $orientation = 'auto';

    /** When true, alternate row backgrounds for readability. */
    private bool $zebraStripes = true;

    /** Zebra stripe color (light gray). */
    private string $zebraColor = 'FFF2F2F2';

    // ── PDF objects during build ──────────────────────────────────────────

    /** @var list<string> Raw PDF object bodies, 1-indexed (slot 0 unused). */
    private array $objects = [''];
    /** @var list<int> Byte offsets for xref table. */
    private array $offsets = [0];
    /** Buffer for the final PDF output. */
    private string $buffer = '';

    // ── Configuration ─────────────────────────────────────────────────────

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function setDefaultFontSize(float $size): static
    {
        $this->defaultFontSize = $size;
        return $this;
    }

    public function setHeaderFontSize(float $size): static
    {
        $this->headerFontSize = $size;
        return $this;
    }

    public function setCellPadding(float $pt): static
    {
        $this->cellPadding = $pt;
        return $this;
    }

    /**
     * Set page orientation.
     *
     * @param string $orientation 'portrait' | 'landscape' | 'auto'
     *   In 'auto' mode, landscape is chosen when the table is wider than
     *   the portrait content area.
     */
    public function setOrientation(string $orientation): static
    {
        $this->orientation = $orientation;
        return $this;
    }

    /**
     * Enable or disable alternating row background shading (zebra stripes).
     */
    public function setZebraStripes(bool $enabled): static
    {
        $this->zebraStripes = $enabled;
        return $this;
    }

    // ── Page building ─────────────────────────────────────────────────────

    /**
     * Add a page (or multiple pages if rows overflow) of tabular data.
     *
     * @param list<list<string>>              $rows    2D array of cell values (all stringified).
     * @param list<list<ExcelStyle|null>>|null $styles  Parallel 2D array of ExcelStyle per cell.
     * @param array{
     *     header?: bool,
     *     colWidths?: list<float>,
     *     sheetName?: string,
     *     merges?: list<array{startRow: int, startCol: int, endRow: int, endCol: int}>,
     * } $options
     */
    public function addPage(array $rows, ?array $styles = null, array $options = []): static
    {
        if (empty($rows)) {
            return $this;
        }

        // Default styles to all-null
        if ($styles === null) {
            $styles = array_map(
                fn(array $row) => array_fill(0, count($row), null),
                $rows
            );
        }

        $this->pages[] = [
            'rows' => $rows,
            'styles' => $styles,
            'options' => $options,
        ];
        return $this;
    }

    // ── Write ─────────────────────────────────────────────────────────────

    /**
     * Render all pages and write the PDF to disk.
     *
     * @param string $pdfFile Destination path (created or overwritten).
     * @throws RuntimeException
     */
    public function write(string $pdfFile): bool
    {
        if (empty($this->pages)) {
            throw new RuntimeException('No pages to write. Call addPage() first.');
        }

        $this->objects = [''];
        $this->offsets = [0];
        $this->buffer = '';

        // ── Collect all physical pages ────────────────────────────────────
        /** @var list<array{stream: string, width: float, height: float}> */
        $physicalPages = [];

        foreach ($this->pages as $page) {
            $pageResults = $this->renderTable(
                $page['rows'],
                $page['styles'],
                $page['options']
            );
            foreach ($pageResults as $pr) {
                $physicalPages[] = $pr;
            }
        }

        // ── Build PDF objects ─────────────────────────────────────────────

        // Obj 1: Catalog
        $this->addObject("<< /Type /Catalog /Pages 2 0 R >>");

        // Obj 2: Pages (placeholder — we'll replace after we know page count)
        $pageCount = count($physicalPages);
        $pageObjIds = [];
        $pagesPlaceholder = $this->addObject('PLACEHOLDER');

        // Obj 3–6: Fonts
        $fontRegId = $this->addObject("<< /Type /Font /Subtype /Type1 /BaseFont /" . self::FONT_REGULAR . " /Encoding /WinAnsiEncoding >>");
        $fontBoldId = $this->addObject("<< /Type /Font /Subtype /Type1 /BaseFont /" . self::FONT_BOLD . " /Encoding /WinAnsiEncoding >>");
        $fontItalicId = $this->addObject("<< /Type /Font /Subtype /Type1 /BaseFont /" . self::FONT_ITALIC . " /Encoding /WinAnsiEncoding >>");
        $fontBoldItalicId = $this->addObject("<< /Type /Font /Subtype /Type1 /BaseFont /" . self::FONT_BOLD_ITALIC . " /Encoding /WinAnsiEncoding >>");

        $fontResources = "/Font << /F1 {$fontRegId} 0 R /F2 {$fontBoldId} 0 R /F3 {$fontItalicId} 0 R /F4 {$fontBoldItalicId} 0 R >>";

        // Obj 7: Info dictionary
        $infoId = $this->addObject(
            "<< /Title " . $this->pdfString($this->title)
            . " /Author " . $this->pdfString($this->author)
            . " /Creator (PHP PDFWriter)"
            . " /Producer (PHP PDFWriter)"
            . " /CreationDate " . $this->pdfDate()
            . " >>"
        );

        // Page objects + content streams
        foreach ($physicalPages as $pp) {
            $streamData = $pp['stream'];
            $streamLen = strlen($streamData);
            $streamId = $this->addObject(
                "<< /Length {$streamLen} >>\nstream\n{$streamData}\nendstream"
            );
            $pageId = $this->addObject(
                "<< /Type /Page /Parent 2 0 R"
                . " /MediaBox [0 0 " . sprintf('%.2f %.2f', $pp['width'], $pp['height']) . "]"
                . " /Contents {$streamId} 0 R"
                . " /Resources << {$fontResources} >>"
                . " >>"
            );
            $pageObjIds[] = $pageId;
        }

        // Replace Pages placeholder
        $kids = implode(' ', array_map(fn(int $id) => "{$id} 0 R", $pageObjIds));
        $this->objects[$pagesPlaceholder] = "<< /Type /Pages /Kids [{$kids}] /Count {$pageCount} >>";

        // ── Assemble PDF ──────────────────────────────────────────────────
        $this->buffer = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";

        for ($i = 1; $i < count($this->objects); $i++) {
            $this->offsets[$i] = strlen($this->buffer);
            $this->buffer .= "{$i} 0 obj\n{$this->objects[$i]}\nendobj\n";
        }

        $xrefOffset = strlen($this->buffer);
        $objCount = count($this->objects);
        $this->buffer .= "xref\n0 {$objCount}\n";
        $this->buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i < $objCount; $i++) {
            $this->buffer .= sprintf("%010d 00000 n \n", $this->offsets[$i]);
        }

        $this->buffer .= "trailer\n<< /Size {$objCount} /Root 1 0 R /Info {$infoId} 0 R >>\n";
        $this->buffer .= "startxref\n{$xrefOffset}\n%%EOF\n";

        // ── Write to file ─────────────────────────────────────────────────
        $dir = dirname($pdfFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Cannot create directory: {$dir}");
        }

        $result = file_put_contents($pdfFile, $this->buffer);
        if ($result === false) {
            throw new RuntimeException("Failed to write PDF: {$pdfFile}");
        }

        return true;
    }

    // =========================================================================
    //  Table rendering
    // =========================================================================

    /**
     * Render a table into one or more page content streams.
     *
     * Automatically paginates when rows exceed the available content height.
     * When orientation is 'auto', landscape is chosen if the natural table
     * width exceeds the portrait content area.
     *
     * @return list<array{stream: string, width: float, height: float}>
     */
    private function renderTable(array $rows, array $styles, array $options): array
    {
        $hasHeader = $options['header'] ?? false;
        $sheetName = $options['sheetName'] ?? null;

        $colCount = max(1, max(array_map('count', $rows)));

        // ── Parse merge ranges ────────────────────────────────────────────
        // merges: list of {startRow, startCol, endRow, endCol} (0-based)
        $merges = $options['merges'] ?? [];
        $mergeMap = [];    // "row,col" => {spanCols, spanRows} for top-left cells
        $hiddenCells = []; // "row,col" => true for cells hidden by merges

        foreach ($merges as $mg) {
            $sr = $mg['startRow'];
            $sc = $mg['startCol'];
            $er = $mg['endRow'];
            $ec = $mg['endCol'];
            $mergeMap["{$sr},{$sc}"] = [
                'spanCols' => $ec - $sc + 1,
                'spanRows' => $er - $sr + 1,
            ];
            for ($r = $sr; $r <= $er; $r++) {
                for ($c = $sc; $c <= $ec; $c++) {
                    if ($r !== $sr || $c !== $sc) {
                        $hiddenCells["{$r},{$c}"] = true;
                    }
                }
            }
        }

        // ── Column widths ─────────────────────────────────────────────────
        $colWidths = $options['colWidths'] ?? $this->autoColumnWidths($rows, $colCount);
        $naturalW = array_sum($colWidths);

        // ── Determine orientation ─────────────────────────────────────────
        $portraitContentW = self::PAGE_W_PORTRAIT - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $landscapeContentW = self::PAGE_H_PORTRAIT - self::MARGIN_LEFT - self::MARGIN_RIGHT;

        $useLandscape = match ($this->orientation) {
            'landscape' => true,
            'portrait' => false,
            default => $naturalW > $portraitContentW, // auto
        };

        $pageW = $useLandscape ? self::PAGE_H_PORTRAIT : self::PAGE_W_PORTRAIT;
        $pageH = $useLandscape ? self::PAGE_W_PORTRAIT : self::PAGE_H_PORTRAIT;
        $contentW = $pageW - self::MARGIN_LEFT - self::MARGIN_RIGHT;
        $contentH = $pageH - self::MARGIN_TOP - self::MARGIN_BOTTOM;

        // Scale to fit content area
        $totalW = array_sum($colWidths);
        if ($totalW > $contentW) {
            $scale = $contentW / $totalW;
            $colWidths = array_map(fn(float $w) => $w * $scale, $colWidths);
            $totalW = $contentW;
        }

        // ── Pre-calculate row heights (support text wrapping) ─────────────
        $rowHeights = [];
        foreach ($rows as $ri => $row) {
            $isHeader = $hasHeader && $ri === 0;
            $fontSize = $isHeader ? $this->headerFontSize : $this->defaultFontSize;
            $lineH = $fontSize * self::LINE_HEIGHT_FACTOR;
            $maxLines = 1;

            foreach ($row as $ci => $cellValue) {
                $cw = $colWidths[$ci] ?? $colWidths[count($colWidths) - 1];
                $usable = $cw - 2 * $this->cellPadding;
                $charW = $fontSize * self::CHAR_WIDTH_FACTOR;
                $charsPerLine = max(1, (int) ($usable / $charW));
                $textLen = strlen((string) $cellValue);
                $lines = max(1, (int) ceil($textLen / $charsPerLine));
                $maxLines = max($maxLines, $lines);
            }

            $rowHeights[$ri] = $lineH * $maxLines + 2 * $this->cellPadding;
        }

        // ── Paginate ──────────────────────────────────────────────────────
        $pages = [];
        $pageRows = [];
        $pageStyles = [];
        $pageRowH = [];
        $pageOrigIdx = [];  // original row indices for merge lookup
        $usedH = 0.0;

        // Reserve space for sheet name title
        $titleH = $sheetName !== null ? 20.0 : 0.0;
        $availH = $contentH - $titleH;

        $headerRow = $hasHeader ? $rows[0] : null;
        $headerStyle = $hasHeader ? $styles[0] : null;
        $headerH = $hasHeader ? $rowHeights[0] : 0.0;

        $startIdx = $hasHeader ? 1 : 0;

        for ($ri = $startIdx; $ri < count($rows); $ri++) {
            $rh = $rowHeights[$ri];

            // Check if this row fits on current page
            $neededH = $usedH + $rh;
            if ($hasHeader && empty($pageRows)) {
                $neededH += $headerH;
            }

            if ($neededH > $availH && !empty($pageRows)) {
                // Flush current page
                if ($hasHeader && $headerRow !== null) {
                    array_unshift($pageRows, $headerRow);
                    array_unshift($pageStyles, $headerStyle);
                    array_unshift($pageRowH, $headerH);
                    array_unshift($pageOrigIdx, 0);
                }
                $pages[] = [
                    'rows' => $pageRows,
                    'styles' => $pageStyles,
                    'heights' => $pageRowH,
                    'origIdx' => $pageOrigIdx,
                ];
                $pageRows = [];
                $pageStyles = [];
                $pageRowH = [];
                $pageOrigIdx = [];
                $usedH = 0.0;
            }

            if (empty($pageRows) && $hasHeader) {
                $usedH += $headerH;
            }

            $pageRows[] = $rows[$ri];
            $pageStyles[] = $styles[$ri] ?? [];
            $pageRowH[] = $rh;
            $pageOrigIdx[] = $ri;
            $usedH += $rh;
        }

        // Flush remaining
        if (!empty($pageRows)) {
            if ($hasHeader && $headerRow !== null) {
                array_unshift($pageRows, $headerRow);
                array_unshift($pageStyles, $headerStyle);
                array_unshift($pageRowH, $headerH);
                array_unshift($pageOrigIdx, 0);
            }
            $pages[] = [
                'rows' => $pageRows,
                'styles' => $pageStyles,
                'heights' => $pageRowH,
                'origIdx' => $pageOrigIdx,
            ];
        }

        // Handle edge case: all data is header only
        if (empty($pages) && $hasHeader && $headerRow !== null) {
            $pages[] = [
                'rows' => [$headerRow],
                'styles' => [$headerStyle],
                'heights' => [$headerH],
                'origIdx' => [0],
            ];
        }

        // ── Render each physical page ─────────────────────────────────────
        $result = [];
        $totalPages = count($pages);

        foreach ($pages as $pi => $pg) {
            // Remap mergeMap & hiddenCells for this page's local row indices
            $localMergeMap = [];
            $localHidden = [];
            foreach ($pg['origIdx'] as $localRi => $origRi) {
                foreach ($mergeMap as $key => $span) {
                    [$mr, $mc] = explode(',', $key);
                    if ((int) $mr === $origRi) {
                        $localMergeMap["{$localRi},{$mc}"] = $span;
                    }
                }
                foreach ($hiddenCells as $key => $_) {
                    [$hr, $hc] = explode(',', $key);
                    if ((int) $hr === $origRi) {
                        $localHidden["{$localRi},{$hc}"] = true;
                    }
                }
            }

            $stream = $this->buildPageStream(
                $pg['rows'],
                $pg['styles'],
                $pg['heights'],
                $colWidths,
                $totalW,
                $sheetName,
                $pi + 1,
                $totalPages,
                $hasHeader,
                $pageW,
                $pageH,
                $localMergeMap,
                $localHidden
            );
            $result[] = [
                'stream' => $stream,
                'width' => $pageW,
                'height' => $pageH,
            ];
        }

        return $result;
    }

    /**
     * Build the PDF content stream for a single physical page.
     *
     * @param array<string, array{spanCols: int, spanRows: int}> $mergeMap
     * @param array<string, true> $hiddenCells
     */
    private function buildPageStream(
        array $rows,
        array $styles,
        array $heights,
        array $colWidths,
        float $totalW,
        ?string $sheetName,
        int $pageNum,
        int $totalPages,
        bool $hasHeader,
        float $pageW,
        float $pageH,
        array $mergeMap = [],
        array $hiddenCells = []
    ): string {
        $ops = [];

        // Starting Y position (PDF coordinate: bottom-up)
        $y = $pageH - self::MARGIN_TOP;
        $x0 = self::MARGIN_LEFT;

        // Track the overall row index for zebra striping (excluding header)
        $dataRowCounter = 0;

        // ── Sheet name title ──────────────────────────────────────────────
        if ($sheetName !== null) {
            $ops[] = 'BT';
            $ops[] = '/F2 12 Tf';
            $ops[] = '0.2 0.2 0.2 rg';
            $ops[] = sprintf('%.2f %.2f Td', $x0, $y - 14);
            $ops[] = '(' . $this->escPdf($sheetName) . ') Tj';
            $ops[] = 'ET';
            $y -= 20.0;
        }

        // ── Table rows ────────────────────────────────────────────────────
        foreach ($rows as $ri => $row) {
            $rh = $heights[$ri];
            $isHeader = $hasHeader && $ri === 0;

            if (!$isHeader) {
                $dataRowCounter++;
            }

            $cellX = $x0;
            foreach ($row as $ci => $cellValue) {
                $baseCw = $colWidths[$ci] ?? $colWidths[count($colWidths) - 1];

                // Skip cells hidden by a merge
                if (isset($hiddenCells["{$ri},{$ci}"])) {
                    $cellX += $baseCw;
                    continue;
                }

                // Calculate actual cell width (may span multiple columns)
                $cw = $baseCw;
                if (isset($mergeMap["{$ri},{$ci}"])) {
                    $spanCols = $mergeMap["{$ri},{$ci}"]['spanCols'];
                    $cw = 0;
                    for ($sc = $ci; $sc < $ci + $spanCols && $sc < count($colWidths); $sc++) {
                        $cw += $colWidths[$sc] ?? $colWidths[count($colWidths) - 1];
                    }
                }

                $cellStyle = $styles[$ri][$ci] ?? null;

                // ── Background fill ───────────────────────────────────────
                $bgColor = null;
                if ($cellStyle instanceof ExcelStyle && $cellStyle->bgColor !== null) {
                    $bgColor = $cellStyle->bgColor;
                } elseif ($isHeader) {
                    $bgColor = 'FF4472C4'; // Default header blue
                } elseif ($this->zebraStripes && ($dataRowCounter % 2 === 0)) {
                    $bgColor = $this->zebraColor;
                }

                if ($bgColor !== null) {
                    [$r, $g, $b] = $this->argbToRgbFractions($bgColor);
                    $ops[] = sprintf('%.3f %.3f %.3f rg', $r, $g, $b);
                    $ops[] = sprintf('%.2f %.2f %.2f %.2f re f', $cellX, $y - $rh, $cw, $rh);
                }

                // ── Cell border ───────────────────────────────────────────
                if ($cellStyle instanceof ExcelStyle && $cellStyle->borderStyle !== null) {
                    $bw = self::BORDER_WIDTHS[$cellStyle->borderStyle] ?? 0.5;
                    $ops[] = sprintf('%.2f w', $bw);

                    $bColor = $cellStyle->borderColor ?? 'FF000000';
                    [$br, $bg, $bb] = $this->argbToRgbFractions($bColor);
                    $ops[] = sprintf('%.3f %.3f %.3f RG', $br, $bg, $bb);

                    if ($cellStyle->borderStyle === 'dashed') {
                        $ops[] = '[4 2] 0 d';
                    } elseif ($cellStyle->borderStyle === 'dotted') {
                        $ops[] = '[1 2] 0 d';
                    } else {
                        $ops[] = '[] 0 d';
                    }

                    $ops[] = sprintf('%.2f %.2f %.2f %.2f re S', $cellX, $y - $rh, $cw, $rh);

                    if ($cellStyle->borderStyle === 'dashed' || $cellStyle->borderStyle === 'dotted') {
                        $ops[] = '[] 0 d';
                    }
                } else {
                    $ops[] = sprintf('%.1f w', $this->borderWidth);
                    $ops[] = '0.75 0.75 0.75 RG';
                    $ops[] = sprintf('%.2f %.2f %.2f %.2f re S', $cellX, $y - $rh, $cw, $rh);
                }

                // ── Text ──────────────────────────────────────────────────
                $text = (string) $cellValue;
                if ($text !== '') {
                    $fontSize = $isHeader ? $this->headerFontSize : $this->defaultFontSize;
                    if (!$isHeader && $cellStyle instanceof ExcelStyle && $cellStyle->fontSize !== 11) {
                        $fontSize = (float) $cellStyle->fontSize;
                    }
                    $fontCmd = $this->selectFont($cellStyle, $isHeader);
                    $lineH = $fontSize * self::LINE_HEIGHT_FACTOR;

                    // Text color
                    $textColor = '0 0 0';
                    if ($cellStyle instanceof ExcelStyle && $cellStyle->fontColor !== null) {
                        [$tr, $tg, $tb] = $this->argbToRgbFractions($cellStyle->fontColor);
                        $textColor = sprintf('%.3f %.3f %.3f', $tr, $tg, $tb);
                    } elseif ($isHeader) {
                        $textColor = '1 1 1';
                    }

                    // Alignment offset
                    $textW = strlen($text) * $fontSize * self::CHAR_WIDTH_FACTOR;
                    $usableW = $cw - 2 * $this->cellPadding;
                    $alignH = $cellStyle instanceof ExcelStyle ? ($cellStyle->alignH ?? 'left') : 'left';
                    if ($isHeader) {
                        $alignH = 'center';
                    }

                    $txOffset = match ($alignH) {
                        'center' => max(0, ($usableW - $textW) / 2),
                        'right' => max(0, $usableW - $textW),
                        default => 0,
                    };

                    // Vertical centering
                    $textY = $y - ($rh + $fontSize) / 2;

                    // Wrap long text
                    $charW = $fontSize * self::CHAR_WIDTH_FACTOR;
                    $charsPerLine = max(1, (int) ($usableW / $charW));
                    $wrappedLines = $this->wrapText($text, $charsPerLine);

                    $ops[] = 'BT';
                    $ops[] = "{$textColor} rg";
                    $ops[] = "{$fontCmd} {$fontSize} Tf";

                    if (count($wrappedLines) === 1) {
                        $tx = $cellX + $this->cellPadding + $txOffset;
                        $ops[] = sprintf('%.2f %.2f Td', $tx, $textY);
                        $ops[] = '(' . $this->escPdf($wrappedLines[0]) . ') Tj';
                    } else {
                        $startY = $y - $this->cellPadding - $lineH * 0.7;
                        $tx = $cellX + $this->cellPadding;
                        $ops[] = sprintf('%.2f %.2f Td', $tx, $startY);
                        foreach ($wrappedLines as $li => $line) {
                            if ($li > 0) {
                                $ops[] = sprintf('0 %.2f Td', -$lineH);
                            }
                            $ops[] = '(' . $this->escPdf($line) . ') Tj';
                        }
                    }

                    $ops[] = 'ET';

                    // ── Underline ──────────────────────────────────────────
                    if ($cellStyle instanceof ExcelStyle && $cellStyle->underline) {
                        $ulY = $textY - $fontSize * 0.15;
                        $ulX1 = $cellX + $this->cellPadding + $txOffset;
                        $ulX2 = $ulX1 + min($textW, $usableW);
                        $ops[] = sprintf('%.2f w', max(0.5, $fontSize * 0.05));
                        $ops[] = "{$textColor} RG";
                        $ops[] = sprintf('%.2f %.2f m %.2f %.2f l S', $ulX1, $ulY, $ulX2, $ulY);
                    }
                }

                $cellX += $baseCw; // Always advance by base column width
            }

            $y -= $rh;
        }

        // ── Page footer (page number) ─────────────────────────────────────
        $footer = "Page {$pageNum} / {$totalPages}";
        $ops[] = 'BT';
        $ops[] = '/F1 8 Tf';
        $ops[] = '0.5 0.5 0.5 rg';
        $footerW = strlen($footer) * 8 * self::CHAR_WIDTH_FACTOR;
        $footerX = ($pageW - $footerW) / 2;
        $ops[] = sprintf('%.2f %.2f Td', $footerX, self::MARGIN_BOTTOM - 20);
        $ops[] = '(' . $this->escPdf($footer) . ') Tj';
        $ops[] = 'ET';

        return implode("\n", $ops);
    }

    // =========================================================================
    //  PDF object helpers
    // =========================================================================

    /** Add a PDF object and return its 1-based object number. */
    private function addObject(string $body): int
    {
        $this->objects[] = $body;
        $this->offsets[] = 0;
        return count($this->objects) - 1;
    }

    /** Escape a string for use inside a PDF literal string `(...)`. */
    private function escPdf(string $s): string
    {
        // Only keep printable ASCII to avoid encoding issues with Type1 fonts
        $clean = '';
        for ($i = 0, $len = strlen($s); $i < $len; $i++) {
            $c = $s[$i];
            $o = ord($c);
            if ($o >= 32 && $o <= 126) {
                $clean .= $c;
            } else {
                $clean .= '?';
            }
        }

        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $clean
        );
    }

    /** Create a PDF literal string `(text)` from a PHP string. */
    private function pdfString(string $s): string
    {
        return '(' . $this->escPdf($s) . ')';
    }

    /** Return a PDF date string for the current timestamp. */
    private function pdfDate(): string
    {
        return '(D:' . date('YmdHis') . ')';
    }

    // =========================================================================
    //  Style & layout helpers
    // =========================================================================

    /**
     * Convert 8-char ARGB hex (e.g. 'FF4472C4') to [r, g, b] fractions 0–1.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private function argbToRgbFractions(string $argb): array
    {
        // Strip alpha channel (first 2 hex chars)
        $hex = strlen($argb) === 8 ? substr($argb, 2) : $argb;
        $hex = str_pad($hex, 6, '0');

        return [
            hexdec(substr($hex, 0, 2)) / 255.0,
            hexdec(substr($hex, 2, 2)) / 255.0,
            hexdec(substr($hex, 4, 2)) / 255.0,
        ];
    }

    /**
     * Select the PDF font command string based on an ExcelStyle.
     *
     * F1 = Regular, F2 = Bold, F3 = Italic, F4 = BoldItalic
     */
    private function selectFont(?ExcelStyle $style, bool $isHeader): string
    {
        if ($isHeader) {
            return '/F2'; // Bold for headers
        }
        if ($style === null) {
            return '/F1';
        }

        if ($style->bold && $style->italic) {
            return '/F4';
        }
        if ($style->bold) {
            return '/F2';
        }
        if ($style->italic) {
            return '/F3';
        }
        return '/F1';
    }

    /**
     * Auto-calculate column widths based on content.
     *
     * @return list<float>
     */
    private function autoColumnWidths(array $rows, int $colCount): array
    {
        $maxLens = array_fill(0, $colCount, 3); // minimum 3 chars

        foreach ($rows as $row) {
            foreach ($row as $ci => $val) {
                $len = strlen((string) $val);
                if ($ci < $colCount && $len > $maxLens[$ci]) {
                    $maxLens[$ci] = $len;
                }
            }
        }

        // Use landscape content width as the upper bound for a single column
        $maxSingleCol = (self::PAGE_H_PORTRAIT - self::MARGIN_LEFT - self::MARGIN_RIGHT) * 0.6;

        // Convert character lengths to point widths
        $widths = array_map(
            fn(int $len) => min(
                $maxSingleCol, // single column can't exceed 60% of landscape page
                max(30.0, $len * $this->defaultFontSize * self::CHAR_WIDTH_FACTOR + 2 * $this->cellPadding + 8)
            ),
            $maxLens
        );

        return $widths;
    }

    /**
     * Wrap text into lines that fit within the given character limit.
     *
     * @return list<string>
     */
    private function wrapText(string $text, int $charsPerLine): array
    {
        if (strlen($text) <= $charsPerLine) {
            return [$text];
        }

        $lines = [];
        $words = explode(' ', $text);
        $current = '';

        foreach ($words as $word) {
            if ($current === '') {
                $current = $word;
            } elseif (strlen($current) + 1 + strlen($word) <= $charsPerLine) {
                $current .= ' ' . $word;
            } else {
                $lines[] = $current;
                $current = $word;
            }

            // Handle single words longer than the line
            while (strlen($current) > $charsPerLine) {
                $lines[] = substr($current, 0, $charsPerLine);
                $current = substr($current, $charsPerLine);
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }
}
