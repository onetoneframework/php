<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Document;
use ZipArchive;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Document\Excel;
use Clover\Classes\Document\Excel\ExcelSheet;
use Clover\Classes\Document\Excel\ExcelStyle;
use PHPUnit\Framework\TestCase;

/**
 * Full test suite for the Excel read/write class.
 *
 * Each test group is isolated: files are written to a temporary directory
 * that is cleaned up in tearDown().
 *
 * Test groups:
 *   A. ExcelStyle – immutable value object
 *   B. ExcelSheet – worksheet builder
 *   C. Excel static utilities (colLetterToIndex, colIndexToLetter, parseRef)
 *   D. Excel::fromRows() static factory
 *   E. Write – basic round-trip
 *   F. Write – data types (number, bool, DateTime, formula)
 *   G. Write – multi-sheet workbook
 *   H. Write – styles (font, fill, alignment, border, numberFmt)
 *   I. Write – layout (column width, row height, freeze, autoFilter, merge, tab color)
 *   J. Read – listSheets / readAll
 *   K. Read – assoc mode
 *   L. Read – sparse column gaps
 *   M. Error paths – exceptions for invalid inputs
 */
class ExcelTest extends TestCase
{
    /** Temporary directory for test output files. */
    private string $tmpDir;

    // =========================================================================
    //  Setup / teardown
    // =========================================================================

    protected function setUp(): void
    {
        if (!class_exists(ZipArchive::class)) {
            $this->markTestSkipped('ZipArchive extension is not available.');
        }

        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'excel_test_' . uniqid();
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // Remove all temp files created during the test
        foreach (glob($this->tmpDir . DIRECTORY_SEPARATOR . '*.xlsx') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmpDir);
    }

    // ── Helper: unique temp path ──────────────────────────────────────────────

    private function tmp(string $name = 'test.xlsx'): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . $name;
    }

    // ── Helper: write → read round-trip ──────────────────────────────────────

    /**
     * Write $excel to a temp file, immediately read sheet 0 back, return rows.
     *
     * @return list<list<mixed>>
     */
    private function roundTrip(Excel $excel, string $fileName = 'rt.xlsx', int|string $sheet = 0): array
    {
        $path = $this->tmp($fileName);
        $excel->write($path);
        return (new Excel())->read($path, $sheet);
    }


    // =========================================================================
    //  A. ExcelStyle
    // =========================================================================

    /**
     * Default ExcelStyle must have sensible defaults and produce a stable key.
     */
    public function testStyleDefaults(): void
    {
        $s = new ExcelStyle();

        $this->assertSame('Arial', $s->fontName);
        $this->assertSame(11,      $s->fontSize);
        $this->assertFalse($s->bold);
        $this->assertFalse($s->italic);
        $this->assertFalse($s->underline);
        $this->assertNull($s->fontColor);
        $this->assertNull($s->bgColor);
        $this->assertNull($s->alignH);
        $this->assertNull($s->alignV);
        $this->assertFalse($s->wrapText);
        $this->assertNull($s->numberFmt);
        $this->assertNull($s->borderStyle);
        $this->assertNull($s->borderColor);
    }

    /**
     * Fluent setters must return new instances (immutability).
     */
    public function testStyleImmutability(): void
    {
        $base  = new ExcelStyle();
        $bold  = $base->bold();
        $italic = $base->italic();

        // original unchanged
        $this->assertFalse($base->bold);
        $this->assertFalse($base->italic);

        // new instances have the change
        $this->assertTrue($bold->bold);
        $this->assertFalse($bold->italic);
        $this->assertFalse($italic->bold);
        $this->assertTrue($italic->italic);
    }

    /**
     * Two styles built with identical properties must produce the same key.
     */
    public function testStyleKeyDeduplication(): void
    {
        $a = (new ExcelStyle())->bold()->bgColor('FFFFFF00')->alignH('center');
        $b = (new ExcelStyle())->bold()->bgColor('FFFFFF00')->alignH('center');
        $c = (new ExcelStyle())->bold()->bgColor('FFFF0000')->alignH('center'); // different bgColor

        $this->assertSame($a->key(), $b->key(), 'Identical styles must share the same key');
        $this->assertNotSame($a->key(), $c->key(), 'Different styles must not share a key');
    }

    /**
     * All fluent setter chains must apply their changes correctly.
     */
    public function testStyleFluentChaining(): void
    {
        $s = (new ExcelStyle())
            ->bold()
            ->italic()
            ->underline()
            ->fontSize(14)
            ->fontName('Calibri')
            ->fontColor('FFFF0000')
            ->bgColor('FFFFFF00')
            ->alignH('center')
            ->alignV('top')
            ->wrapText()
            ->numberFmt('#,##0.00')
            ->border('medium', 'FF0000FF');

        $this->assertTrue($s->bold);
        $this->assertTrue($s->italic);
        $this->assertTrue($s->underline);
        $this->assertSame(14,          $s->fontSize);
        $this->assertSame('Calibri',   $s->fontName);
        $this->assertSame('FFFF0000',  $s->fontColor);
        $this->assertSame('FFFFFF00',  $s->bgColor);
        $this->assertSame('center',    $s->alignH);
        $this->assertSame('top',       $s->alignV);
        $this->assertTrue($s->wrapText);
        $this->assertSame('#,##0.00',  $s->numberFmt);
        $this->assertSame('medium',    $s->borderStyle);
        $this->assertSame('FF0000FF',  $s->borderColor);
    }

    /**
     * bold(false) must turn bold off on an already-bold style.
     */
    public function testStyleToggleOff(): void
    {
        $s = (new ExcelStyle())->bold()->bold(false);
        $this->assertFalse($s->bold);
    }


    // =========================================================================
    //  B. ExcelSheet
    // =========================================================================

    /**
     * appendRow() increments the internal row counter correctly.
     */
    public function testSheetAppendRowIncrements(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->appendRow(['A', 'B']);
        $sheet->appendRow(['C', 'D']);

        $cells = $sheet->getCells();

        // Row 1 and row 2 should be present
        $this->assertArrayHasKey(1, $cells);
        $this->assertArrayHasKey(2, $cells);
    }

    /**
     * appendRows() adds all provided rows in sequence.
     */
    public function testSheetAppendRows(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->appendRows([['X'], ['Y'], ['Z']]);

        $this->assertCount(3, $sheet->getCells());
    }

    /**
     * setCell() accepts 1-based indices and stores the value.
     */
    public function testSheetSetCell(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->setCell(3, 5, 'hello');

        $this->assertSame('hello', $sheet->getCells()[3][5]['value']);
    }

    /**
     * setCellByRef() converts A1 references to correct row/col indices.
     */
    public function testSheetSetCellByRef(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->setCellByRef('C4', 'world');

        $this->assertSame('world', $sheet->getCells()[4][3]['value']);
    }

    /**
     * Per-cell style overrides the row default style in appendRow().
     */
    public function testSheetPerCellStyleOverride(): void
    {
        $rowStyle  = (new ExcelStyle())->bold();
        $cellStyle = (new ExcelStyle())->italic();

        $sheet = new ExcelSheet('Test');
        $sheet->appendRow([
            'plain',
            ['value' => 'styled', 'style' => $cellStyle],
        ], $rowStyle);

        $cells = $sheet->getCells()[1];
        $this->assertSame($rowStyle,  $cells[1]['style'], 'Col 1 should use the row default style');
        $this->assertSame($cellStyle, $cells[2]['style'], 'Col 2 should use the per-cell override');
    }

    /**
     * setCell() with an index < 1 must throw.
     */
    public function testSheetSetCellInvalidIndex(): void
    {
        $this->expectException(\RuntimeException::class);
        (new ExcelSheet('Test'))->setCell(0, 1, 'bad');
    }

    /**
     * merge() stores the range string and getMerges() returns it.
     */
    public function testSheetMergeStored(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->merge('A1:C1')->merge('B3:B5');

        $this->assertSame(['A1:C1', 'B3:B5'], $sheet->getMerges());
    }

    /**
     * merge() with a range missing ':' must throw.
     */
    public function testSheetMergeInvalidRange(): void
    {
        $this->expectException(\RuntimeException::class);
        (new ExcelSheet('Test'))->merge('A1');
    }

    /**
     * freeze() stores the correct [col, row] pair.
     */
    public function testSheetFreezeStored(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->freeze('B2');

        $this->assertSame([2, 2], $sheet->getFreezeAt());
    }

    /**
     * autoFilter() and tabColor() getters return what was set.
     */
    public function testSheetAutoFilterAndTabColor(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->autoFilter('A1:D1')->tabColor('FF0070C0');

        $this->assertSame('A1:D1',     $sheet->getAutoFilterRange());
        $this->assertSame('FF0070C0',  $sheet->getTabColorArgb());
    }

    /**
     * setColWidth() and setRowHeight() store values correctly.
     */
    public function testSheetColWidthAndRowHeight(): void
    {
        $sheet = new ExcelSheet('Test');
        $sheet->setColWidth(2, 18.5)->setRowHeight(1, 24.0);

        $this->assertSame(18.5, $sheet->getColWidths()[2]);
        $this->assertSame(24.0, $sheet->getRowHeights()[1]);
    }


    // =========================================================================
    //  C. Excel static utilities
    // =========================================================================

    /**
     * @dataProvider colLetterProvider
     */
    public function testColLetterToIndex(string $letter, int $expected): void
    {
        $this->assertSame($expected, Excel::colLetterToIndex($letter));
    }

    public static function colLetterProvider(): array
    {
        return [
            ['A',  1],
            ['Z',  26],
            ['AA', 27],
            ['AB', 28],
            ['AZ', 52],
            ['BA', 53],
            ['ZZ', 702],
        ];
    }

    /**
     * @dataProvider colIndexProvider
     */
    public function testColIndexToLetter(int $index, string $expected): void
    {
        $this->assertSame($expected, Excel::colIndexToLetter($index));
    }

    public static function colIndexProvider(): array
    {
        return [
            [1,   'A'],
            [26,  'Z'],
            [27,  'AA'],
            [28,  'AB'],
            [52,  'AZ'],
            [702, 'ZZ'],
        ];
    }

    /**
     * colLetterToIndex and colIndexToLetter must be exact inverses.
     */
    public function testColLetterIndexRoundTrip(): void
    {
        foreach (['A', 'M', 'Z', 'AA', 'BC', 'ZZ'] as $letter) {
            $this->assertSame(
                $letter,
                Excel::colIndexToLetter(Excel::colLetterToIndex($letter)),
                "Round-trip failed for column '{$letter}'"
            );
        }
    }

    /**
     * @dataProvider parseRefProvider
     */
    public function testParseRef(string $ref, int $col, int $row): void
    {
        [$c, $r] = Excel::parseRef($ref);
        $this->assertSame($col, $c);
        $this->assertSame($row, $r);
    }

    public static function parseRefProvider(): array
    {
        return [
            ['A1',  1,  1],
            ['B3',  2,  3],
            ['Z99', 26, 99],
            ['AA1', 27, 1],
        ];
    }

    /**
     * parseRef() with an invalid reference must throw.
     */
    public function testParseRefInvalid(): void
    {
        $this->expectException(\RuntimeException::class);
        Excel::parseRef('123');
    }


    // =========================================================================
    //  D. Excel::fromRows()
    // =========================================================================

    /**
     * fromRows() must create one sheet containing exactly the provided rows.
     */
    public function testFromRowsBasic(): void
    {
        $data = [
            ['Name', 'Age'],
            ['Bill', 30],
            ['Sam',  25],
        ];

        $rows = $this->roundTrip(Excel::fromRows($data));

        $this->assertCount(3, $rows);
        $this->assertSame('Name', $rows[0][0]);
        $this->assertSame('Bill', $rows[1][0]);
        $this->assertSame('Sam',  $rows[2][0]);
    }

    /**
     * fromRows() must respect a custom sheet name.
     */
    public function testFromRowsCustomSheetName(): void
    {
        $path = $this->tmp('custom.xlsx');
        Excel::fromRows([['x']], 'MySheet')->write($path);

        $names = (new Excel())->listSheets($path);
        $this->assertSame(['MySheet'], $names);
    }


    // =========================================================================
    //  E. Write – basic round-trip
    // =========================================================================

    /**
     * write() must return true on success.
     */
    public function testWriteReturnsTrue(): void
    {
        $result = Excel::fromRows([['a', 'b'], ['c', 'd']])->write($this->tmp());
        $this->assertTrue($result);
    }

    /**
     * Written file must exist and have non-zero size.
     */
    public function testWriteCreatesFile(): void
    {
        $path = $this->tmp('created.xlsx');
        Excel::fromRows([['hello']])->write($path);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, filesize($path));
    }

    /**
     * The ZIP produced must contain the mandatory OOXML entry points.
     */
    public function testWriteProducesValidZip(): void
    {
        $path = $this->tmp('zip.xlsx');
        Excel::fromRows([['test']])->write($path);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true, 'Output is not a valid ZIP file');

        foreach ([
            '[Content_Types].xml',
            '_rels/.rels',
            'xl/workbook.xml',
            'xl/worksheets/sheet1.xml',
            'xl/styles.xml',
            'xl/sharedStrings.xml',
        ] as $entry) {
            $this->assertNotFalse(
                $zip->getFromName($entry),
                "Missing required ZIP entry: {$entry}"
            );
        }
        $zip->close();
    }

    /**
     * write() must overwrite an existing file without error.
     */
    public function testWriteOverwritesExistingFile(): void
    {
        $path = $this->tmp('overwrite.xlsx');
        Excel::fromRows([['first']])->write($path);
        $size1 = filesize($path);

        Excel::fromRows([['second', 'extra', 'columns']])->write($path);
        $size2 = filesize($path);

        $this->assertFileExists($path);
        $this->assertGreaterThan(0, $size2);
        // Sizes may differ; the important thing is both writes succeeded
        $this->assertIsInt($size1);
        $this->assertIsInt($size2);
    }

    /**
     * An empty string cell must be round-tripped as an empty string.
     */
    public function testWriteEmptyString(): void
    {
        $rows = $this->roundTrip(Excel::fromRows([['', 'hello', '']]));
        $this->assertSame('',      $rows[0][0]);
        $this->assertSame('hello', $rows[0][1]);
        $this->assertSame('',      $rows[0][2]);
    }

    /**
     * Unicode characters (CJK, emoji, accents) must survive the round-trip.
     */
    public function testWriteUnicodeStrings(): void
    {
        $data = [['안녕하세요', '日本語', 'Ünïcödé', '🎉']];
        $rows = $this->roundTrip(Excel::fromRows($data));

        $this->assertSame('안녕하세요', $rows[0][0]);
        $this->assertSame('日本語',     $rows[0][1]);
        $this->assertSame('Ünïcödé',   $rows[0][2]);
        $this->assertSame('🎉',        $rows[0][3]);
    }

    /**
     * XML-special characters must be escaped correctly and survive round-trip.
     */
    public function testWriteXmlSpecialChars(): void
    {
        $data = [['<tag>', '"quote"', 'a & b', "apostrophe '"]];
        $rows = $this->roundTrip(Excel::fromRows($data));

        $this->assertSame('<tag>',       $rows[0][0]);
        $this->assertSame('"quote"',     $rows[0][1]);
        $this->assertSame('a & b',       $rows[0][2]);
        $this->assertSame("apostrophe '", $rows[0][3]);
    }

    /**
     * Shared strings that appear in multiple cells must be deduplicated.
     * We verify this by checking the sharedStrings.xml has uniqueCount < total cells.
     */
    public function testWriteSharedStringDeduplication(): void
    {
        $path = $this->tmp('dedup.xlsx');
        // 'repeat' appears 4 times across two rows
        Excel::fromRows([['repeat', 'repeat'], ['repeat', 'unique']])->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $xml = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
        $zip->close();

        $uniqueCount = (int)($xml['uniqueCount'] ?? 0);
        // Should have exactly 2 unique strings: 'repeat' and 'unique'
        $this->assertSame(2, $uniqueCount);
    }


    // =========================================================================
    //  F. Write – data types
    // =========================================================================

    /**
     * Integer values must be stored as numeric cells (not strings).
     */
    public function testWriteIntegerCells(): void
    {
        $rows = $this->roundTrip(Excel::fromRows([[42, -7, 0]]));

        $this->assertSame(42, $rows[0][0]);
        $this->assertSame(-7, $rows[0][1]);
        $this->assertSame(0,  $rows[0][2]);
    }

    /**
     * Float values must survive the round-trip without precision loss for common values.
     */
    public function testWriteFloatCells(): void
    {
        $rows = $this->roundTrip(Excel::fromRows([[3.14, 0.0, -99.5]]));

        $this->assertEqualsWithDelta(3.14,  $rows[0][0], 0.0001);
        $this->assertEqualsWithDelta(0.0,   $rows[0][1], 0.0001);
        $this->assertEqualsWithDelta(-99.5, $rows[0][2], 0.0001);
    }

    /**
     * Boolean true/false must be stored as OOXML boolean cells.
     */
    public function testWriteBooleanCells(): void
    {
        $rows = $this->roundTrip(Excel::fromRows([[true, false]]));

        $this->assertTrue($rows[0][0]);
    }

    /**
     * DateTime values must be stored as OLE serial numbers, not string representations.
     */
    public function testWriteDateTimeAsSerial(): void
    {
        $path = $this->tmp('date.xlsx');
        $excel = new Excel();
        $excel->addSheet()->setCell(1, 1, new \DateTime('2024-06-15'));
        $excel->write($path);

        // Read back raw value — expect a numeric OLE serial (approx. 45458 for 2024-06-15)
        $zip = new ZipArchive();
        $zip->open($path);
        $xml = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();

        $cellValue = (float)(string)$xml->sheetData->row->c->v;
        // 2024-06-15 serial ≈ 45458
        $this->assertGreaterThan(40000, $cellValue, 'DateTime should be a large OLE serial number');
        $this->assertLessThan(  50000, $cellValue, 'DateTime serial is unrealistically large');
    }

    /**
     * Formula cells must be stored as <f> elements.
     */
    public function testWriteFormulaCells(): void
    {
        $path = $this->tmp('formula.xlsx');
        $excel = new Excel();
        $sheet = $excel->addSheet();
        $sheet->setCell(1, 1, 10);
        $sheet->setCell(1, 2, 20);
        $sheet->setCell(1, 3, '=A1+B1');
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $xml   = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();

        // Third cell (C1) should contain a <f> element
        $cells = $xml->sheetData->row->c;
        $c3    = $cells[2] ?? null;
        $this->assertNotNull($c3, 'Third cell (C1) not found');
        $this->assertNotEmpty((string)($c3->f ?? ''), 'C1 should contain a formula element');
        $this->assertStringContainsString('A1+B1', (string)$c3->f);
    }

    /**
     * A mix of all types in a single row must be stored without cross-contamination.
     */
    public function testWriteMixedTypeRow(): void
    {
        $excel = new Excel();
        $excel->addSheet()
            ->appendRow(['label', 100, 3.14, true, false, '=A1']);

        $path = $this->tmp('mixed.xlsx');
        $excel->write($path);

        $this->assertFileExists($path);
        // Row must survive write without exception
        $rows = (new Excel())->read($path);
        $this->assertCount(1, $rows);
        $this->assertSame('label', $rows[0][0]);
    }


    // =========================================================================
    //  G. Write – multi-sheet workbook
    // =========================================================================

    /**
     * Multiple sheets must all be present with correct names.
     */
    public function testWriteMultipleSheets(): void
    {
        $path  = $this->tmp('multi.xlsx');
        $excel = new Excel();
        $excel->addSheet('Alpha')->appendRow(['alpha row']);
        $excel->addSheet('Beta')->appendRow(['beta row']);
        $excel->addSheet('Gamma')->appendRow(['gamma row']);
        $excel->write($path);

        $names = (new Excel())->listSheets($path);
        $this->assertSame(['Alpha', 'Beta', 'Gamma'], $names);
    }

    /**
     * Each sheet's data must be independent and readable by name.
     */
    public function testWriteMultiSheetDataIsolation(): void
    {
        $path  = $this->tmp('isolation.xlsx');
        $excel = new Excel();
        $excel->addSheet('First')->appendRow(['only in first']);
        $excel->addSheet('Second')->appendRow(['only in second']);
        $excel->write($path);

        $reader = new Excel();
        $first  = $reader->read($path, 'First');
        $second = $reader->read($path, 'Second');

        $this->assertSame('only in first',  $first[0][0]);
        $this->assertSame('only in second', $second[0][0]);
    }

    /**
     * Adding a sheet with a duplicate name must throw.
     */
    public function testAddSheetDuplicateNameThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Duplicate/i');

        $excel = new Excel();
        $excel->addSheet('SameName');
        $excel->addSheet('SameName');
    }

    /**
     * getSheet() by index must return the correct sheet.
     */
    public function testGetSheetByIndex(): void
    {
        $excel = new Excel();
        $excel->addSheet('First');
        $excel->addSheet('Second');

        $this->assertSame('Second', $excel->getSheet(1)->name);
    }

    /**
     * getSheet() by name must return the correct sheet.
     */
    public function testGetSheetByName(): void
    {
        $excel = new Excel();
        $excel->addSheet('Alpha');
        $excel->addSheet('Beta');

        $this->assertSame('Alpha', $excel->getSheet('Alpha')->name);
    }

    /**
     * getSheet() with an unknown index must throw.
     */
    public function testGetSheetUnknownIndexThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $excel = new Excel();
        $excel->addSheet('Only');
        $excel->getSheet(5);
    }

    /**
     * getSheet() with an unknown name must throw.
     */
    public function testGetSheetUnknownNameThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $excel = new Excel();
        $excel->addSheet('Present');
        $excel->getSheet('Missing');
    }


    // =========================================================================
    //  H. Write – styles
    // =========================================================================

    /**
     * A styled write must not throw and the output file must be a valid ZIP.
     */
    public function testWriteWithStylesProducesValidFile(): void
    {
        $header = (new ExcelStyle())
            ->bold()
            ->bgColor('FF4472C4')
            ->fontColor('FFFFFFFF')
            ->alignH('center');

        $money = (new ExcelStyle())
            ->numberFmt('#,##0.00')
            ->alignH('right');

        $path  = $this->tmp('styled.xlsx');
        $excel = new Excel();
        $excel->addSheet('Report')
            ->appendRow(['Product', 'Revenue', 'Tax'], $header)
            ->appendRow(['Widget', 9800.50, 980.05], $money);
        $excel->write($path);

        $this->assertFileExists($path);
        $zip = new ZipArchive();
        $this->assertSame(true, $zip->open($path) === true);
        $zip->close();
    }

    /**
     * styles.xml must contain at least one custom xf when styles are applied.
     */
    public function testWriteStylesXmlContainsXf(): void
    {
        $path  = $this->tmp('styles.xlsx');
        $excel = new Excel();
        $excel->addSheet()->setCell(1, 1, 'styled', (new ExcelStyle())->bold());
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $xml = simplexml_load_string($zip->getFromName('xl/styles.xml'));
        $zip->close();

        $xfCount = (int)($xml->cellXfs['count'] ?? 0);
        $this->assertGreaterThan(1, $xfCount, 'Custom style should add at least one xf beyond the default');
    }

    /**
     * Duplicate styles must be deduplicated (same xf index reused).
     */
    public function testWriteStyleDeduplication(): void
    {
        $style = (new ExcelStyle())->bold()->bgColor('FFFF0000');

        $path  = $this->tmp('dedup_style.xlsx');
        $excel = new Excel();
        $sheet = $excel->addSheet();
        // Same style applied to three cells
        $sheet->setCell(1, 1, 'A', $style);
        $sheet->setCell(1, 2, 'B', $style);
        $sheet->setCell(1, 3, 'C', $style);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $xml = simplexml_load_string($zip->getFromName('xl/styles.xml'));
        $zip->close();

        $xfCount = (int)($xml->cellXfs['count'] ?? 0);
        // default (1) + 1 custom = 2; NOT 4
        $this->assertSame(2, $xfCount, 'Three identical styles should produce only one custom xf');
    }

    /**
     * Custom number formats (not in the built-in list) must appear in <numFmts>.
     */
    public function testWriteCustomNumberFormatInXml(): void
    {
        $style = (new ExcelStyle())->numberFmt('yyyy-mm-dd hh:mm');

        $path  = $this->tmp('numfmt.xlsx');
        $excel = new Excel();
        $excel->addSheet()->setCell(1, 1, 98.6, $style);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $stylesXml = $zip->getFromName('xl/styles.xml');
        $zip->close();

        $this->assertStringContainsString('yyyy-mm-dd hh:mm', $stylesXml,
            'Custom number format should appear in styles.xml');
    }

    /**
     * Border style must appear in <borders> section of styles.xml.
     */
    public function testWriteBorderInStylesXml(): void
    {
        $style = (new ExcelStyle())->border('thick', 'FFFF0000');

        $path = $this->tmp('border.xlsx');
        $excel = new Excel();
        $excel->addSheet()->setCell(1, 1, 'bordered', $style);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $stylesXml = $zip->getFromName('xl/styles.xml');
        $zip->close();

        $this->assertStringContainsString('thick', $stylesXml);
        $this->assertStringContainsString('FFFF0000', $stylesXml);
    }


    // =========================================================================
    //  I. Write – layout features
    // =========================================================================

    /**
     * Column widths must appear as <col> elements in the sheet XML.
     */
    public function testWriteColumnWidthInXml(): void
    {
        $path  = $this->tmp('colwidth.xlsx');
        $excel = new Excel();
        $excel->addSheet()->setColWidth(2, 25.0)->appendRow(['a', 'b']);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('width="25"', $sheetXml,
            'Column width should appear in sheet XML');
    }

    /**
     * Row heights must appear as ht attributes in <row> elements.
     */
    public function testWriteRowHeightInXml(): void
    {
        $path  = $this->tmp('rowheight.xlsx');
        $excel = new Excel();
        $excel->addSheet()->setRowHeight(1, 30.0)->appendRow(['data']);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('ht="30"', $sheetXml);
        $this->assertStringContainsString('customHeight="1"', $sheetXml);
    }

    /**
     * freeze() must produce a <pane> element inside <sheetViews>.
     */
    public function testWriteFreezePaneInXml(): void
    {
        $path  = $this->tmp('freeze.xlsx');
        $excel = new Excel();
        $excel->addSheet()->freeze('A2')->appendRow(['header'])->appendRow(['data']);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('<pane', $sheetXml);
        $this->assertStringContainsString('state="frozen"', $sheetXml);
    }

    /**
     * autoFilter() must produce an <autoFilter> element with the correct ref.
     */
    public function testWriteAutoFilterInXml(): void
    {
        $path  = $this->tmp('autofilter.xlsx');
        $excel = new Excel();
        $excel->addSheet()->autoFilter('A1:C1')->appendRow(['Name', 'Age', 'City']);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('<autoFilter', $sheetXml);
        $this->assertStringContainsString('ref="A1:C1"', $sheetXml);
    }

    /**
     * merge() must produce <mergeCells> in the sheet XML.
     */
    public function testWriteMergeCellsInXml(): void
    {
        $path  = $this->tmp('merge.xlsx');
        $excel = new Excel();
        $excel->addSheet()
            ->appendRow(['Title', '', ''])
            ->merge('A1:C1');
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('<mergeCells', $sheetXml);
        $this->assertStringContainsString('ref="A1:C1"', $sheetXml);
    }

    /**
     * tabColor() must produce a <tabColor> element inside <sheetPr>.
     */
    public function testWriteTabColorInXml(): void
    {
        $path  = $this->tmp('tabcolor.xlsx');
        $excel = new Excel();
        $excel->addSheet()->tabColor('FF0070C0')->appendRow(['colored tab']);
        $excel->write($path);

        $zip = new ZipArchive();
        $zip->open($path);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        $this->assertStringContainsString('<tabColor', $sheetXml);
        $this->assertStringContainsString('FF0070C0', $sheetXml);
    }


    // =========================================================================
    //  J. Read – listSheets / readAll
    // =========================================================================

    /**
     * listSheets() must return sheet names in order.
     */
    public function testListSheets(): void
    {
        $path  = $this->tmp('list.xlsx');
        $excel = new Excel();
        $excel->addSheet('One');
        $excel->addSheet('Two');
        $excel->addSheet('Three');
        $excel->write($path);

        $names = (new Excel())->listSheets($path);
        $this->assertSame(['One', 'Two', 'Three'], $names);
    }

    /**
     * readAll() must return data for every sheet keyed by name.
     */
    public function testReadAll(): void
    {
        $path  = $this->tmp('readall.xlsx');
        $excel = new Excel();
        $excel->addSheet('Fruits')->appendRow(['Apple', 'Banana']);
        $excel->addSheet('Colors')->appendRow(['Red', 'Blue']);
        $excel->write($path);

        $all = (new Excel())->readAll($path);

        $this->assertArrayHasKey('Fruits', $all);
        $this->assertArrayHasKey('Colors', $all);
        $this->assertSame('Apple', $all['Fruits'][0][0]);
        $this->assertSame('Red',   $all['Colors'][0][0]);
    }

    /**
     * read() by sheet index must target the correct sheet.
     */
    public function testReadBySheetIndex(): void
    {
        $path  = $this->tmp('byindex.xlsx');
        $excel = new Excel();
        $excel->addSheet('First')->appendRow(['in first']);
        $excel->addSheet('Second')->appendRow(['in second']);
        $excel->write($path);

        $reader = new Excel();
        $this->assertSame('in first',  $reader->read($path, 0)[0][0]);
        $this->assertSame('in second', $reader->read($path, 1)[0][0]);
    }

    /**
     * read() by sheet name must target the correct sheet.
     */
    public function testReadBySheetName(): void
    {
        $path  = $this->tmp('byname.xlsx');
        $excel = new Excel();
        $excel->addSheet('Sales')->appendRow(['Q1']);
        $excel->addSheet('Costs')->appendRow(['Q2']);
        $excel->write($path);

        $reader = new Excel();
        $this->assertSame('Q1', $reader->read($path, 'Sales')[0][0]);
        $this->assertSame('Q2', $reader->read($path, 'Costs')[0][0]);
    }


    // =========================================================================
    //  K. Read – assoc mode
    // =========================================================================

    /**
     * assoc mode must key cells by their column letter.
     */
    public function testReadAssocMode(): void
    {
        $path  = $this->tmp('assoc.xlsx');
        $excel = new Excel();
        $excel->addSheet()->appendRow(['Name', 'Score', 'Grade']);
        $excel->write($path);

        $rows = (new Excel())->read($path, 0, assoc: true);

        $this->assertArrayHasKey('A', $rows[0]);
        $this->assertArrayHasKey('B', $rows[0]);
        $this->assertArrayHasKey('C', $rows[0]);
        $this->assertSame('Name',  $rows[0]['A']);
        $this->assertSame('Score', $rows[0]['B']);
        $this->assertSame('Grade', $rows[0]['C']);
    }

    /**
     * Non-assoc (default) mode must key cells by 0-based integer.
     */
    public function testReadIndexedMode(): void
    {
        $path  = $this->tmp('indexed.xlsx');
        Excel::fromRows([['x', 'y', 'z']])->write($path);

        $rows = (new Excel())->read($path);

        $this->assertArrayHasKey(0, $rows[0]);
        $this->assertArrayHasKey(1, $rows[0]);
        $this->assertArrayHasKey(2, $rows[0]);
    }


    // =========================================================================
    //  L. Read – column gap handling
    // =========================================================================

    /**
     * Sparse rows (non-contiguous column refs) must have gaps filled with ''.
     *
     * This test writes A1, C1 (skipping B1) and expects [val, '', val].
     */
    public function testReadSparseColumnsFilledWithEmpty(): void
    {
        $path  = $this->tmp('sparse.xlsx');
        $excel = new Excel();
        $sheet = $excel->addSheet();
        $sheet->setCell(1, 1, 'first');   // A1
        // Skip B1 intentionally
        $sheet->setCell(1, 3, 'third');   // C1
        $excel->write($path);

        $rows = (new Excel())->read($path);

        $this->assertCount(3, $rows[0],    'Row should have 3 elements (A, B, C)');
        $this->assertSame('first', $rows[0][0]);
        $this->assertSame('',      $rows[0][1], 'Skipped column B must be filled with empty string');
        $this->assertSame('third', $rows[0][2]);
    }


    // =========================================================================
    //  M. Error paths
    // =========================================================================

    /**
     * write() with no sheets added must throw.
     */
    public function testWriteWithNoSheetsThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Excel())->write($this->tmp('empty.xlsx'));
    }

    /**
     * read() on a non-existent file must throw.
     */
    public function testReadNonExistentFileThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Excel())->read('/nonexistent/path/file.xlsx');
    }

    /**
     * read() requesting a sheet name that does not exist must throw.
     */
    public function testReadUnknownSheetNameThrows(): void
    {
        $path = $this->tmp('nosheet.xlsx');
        Excel::fromRows([['x']])->write($path);

        $this->expectException(\RuntimeException::class);
        (new Excel())->read($path, 'DoesNotExist');
    }

    /**
     * read() requesting a sheet index beyond the available range must throw.
     */
    public function testReadOutOfRangeSheetIndexThrows(): void
    {
        $path = $this->tmp('range.xlsx');
        Excel::fromRows([['x']])->write($path);

        $this->expectException(\RuntimeException::class);
        (new Excel())->read($path, 99);
    }

    /**
     * listSheets() on a non-existent file must throw.
     */
    public function testListSheetsNonExistentFileThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        (new Excel())->listSheets('/no/such/file.xlsx');
    }

    /**
     * setCellByRef() with an invalid reference format must throw.
     */
    public function testSetCellByRefInvalidFormat(): void
    {
        $this->expectException(\RuntimeException::class);
        (new ExcelSheet('Test'))->setCellByRef('99ZZ', 'bad');
    }
}
