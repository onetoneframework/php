<?php

declare(strict_types=1);

namespace Clover\Tests\Classes;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\ArraySummarizer;
use PHPUnit\Framework\TestCase;

class ArraySummarizerTest extends TestCase
{
    private ArraySummarizer $summarizer;

    protected function setUp(): void
    {
        $this->summarizer = new ArraySummarizer();
    }

    /**
     * Basic functionality tests
     */
    public function testSummarizeArrayEmpty(): void
    {
        $result = $this->summarizer->summarizeArray([]);
        $this->assertSame('', $result);
    }

    public function testSummarizeArraySingleString(): void
    {
        $result = $this->summarizer->summarizeArray(['item1']);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('item', $result);
        $this->assertStringContainsString('1', $result);
    }

    public function testSummarizeArraySingleStringWithoutNumber(): void
    {
        $result = $this->summarizer->summarizeArray(['file']);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('file', $result);
    }

    public function testSummarizeArrayConsecutiveNumbers(): void
    {
        $result = $this->summarizer->summarizeArray(['file1', 'file2', 'file3']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        // Should contain range notation like [1-3]
        $this->assertStringContainsString('file', $result);
        $this->assertStringContainsString('[', $result);
    }

    public function testSummarizeArrayConsecutiveNumbersLongRange(): void
    {
        $input = array_map(fn($i) => "item$i", range(1, 50));
        $result = $this->summarizer->summarizeArray($input);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('1-50', $result);
    }

    /**
     * Non-consecutive numbers with different patterns
     */
    public function testSummarizeArrayNonConsecutiveNumbers(): void
    {
        $result = $this->summarizer->summarizeArray(['log1', 'log3', 'log5']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        // Should have pattern notation for non-consecutive numbers
        $this->assertStringContainsString('log', $result);
    }

    public function testSummarizeArrayMixedConsecutiveAndNonConsecutive(): void
    {
        $result = $this->summarizer->summarizeArray(['test1', 'test2', 'test3', 'test5', 'test10']);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('test', $result);
    }

    public function testSummarizeArrayArithmeticProgression(): void
    {
        // 숫자가 일정한 차이(2씩)를 가짐
        $result = $this->summarizer->summarizeArray(['file2', 'file4', 'file6', 'file8']);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('file', $result);
    }

    /**
     * Multiple prefixes and patterns
     */
    public function testSummarizeArrayMultipleDifferentPrefixes(): void
    {
        $result = $this->summarizer->summarizeArray([
            'test1',
            'test2',
            'file1',
            'file2',
            'log1',
            'log2'
        ]);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        // Should contain all different prefixes
        $this->assertStringContainsString('test', $result);
        $this->assertStringContainsString('file', $result);
        $this->assertStringContainsString('log', $result);
    }

    public function testSummarizeArrayWithMixedPatternsAndNumbers(): void
    {
        $result = $this->summarizer->summarizeArray([
            'app_v1',
            'app_v2',
            'app_v3',
            'test_1',
            'test_2'
        ]);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * Complex patterns
     */
    public function testSummarizeArrayWithDualNumberPattern(): void
    {
        // "file_1_a", "file_1_b", "file_2_a" 같은 패턴
        $result = $this->summarizer->summarizeArray(['test_1_1', 'test_1_2', 'test_2_1']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayWithSpecialCharacters(): void
    {
        $result = $this->summarizer->summarizeArray(['file-1', 'file-2', 'file-3']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayWithUnderscores(): void
    {
        $result = $this->summarizer->summarizeArray(['log_file_1', 'log_file_2', 'log_file_3']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * Non-string element handling
     */
    public function testSummarizeArraySkipsNonStrings(): void
    {
        $result = $this->summarizer->summarizeArray([1, 2.5, null, 'only']);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('only', $result);
    }

    public function testSummarizeArraySkipsNonStringsWithMixedArray(): void
    {
        $result = $this->summarizer->summarizeArray([
            'item1',
            'item2',
            123,
            45.67,
            true,
            false,
            null,
            'item3'
        ]);
        $this->assertNotEmpty($result);
        // Only string items should be processed
        $this->assertStringContainsString('item', $result);
    }

    public function testSummarizeArrayWithOnlyNonStrings(): void
    {
        $result = $this->summarizer->summarizeArray([1, 2, 3, true, false, null]);
        $this->assertSame('[0-1,1-3]', $result);
    }

    public function testSummarizeArrayWithArraysAsElements(): void
    {
        $result = $this->summarizer->summarizeArray([
            'valid1',
            ['nested'],
            'valid2'
        ]);
        $this->assertIsString($result);
        $this->assertStringContainsString('valid', $result);
    }

    /**
     * Edge cases
     */
    public function testSummarizeArrayWithEmptyString(): void
    {
        $result = $this->summarizer->summarizeArray(['', 'file1', 'file2']);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('file', $result);
    }

    public function testSummarizeArrayWithDuplicateItems(): void
    {
        $result = $this->summarizer->summarizeArray(['item1', 'item1', 'item2', 'item2']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayWithLargeNumbers(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file1000',
            'file1001',
            'file1002'
        ]);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('file', $result);
    }

    public function testSummarizeArrayWithLeadingZeros(): void
    {
        $result = $this->summarizer->summarizeArray(['file001', 'file002', 'file003']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayWithVeryLongStrings(): void
    {
        $prefix = 'very_long_prefix_name_' . str_repeat('a', 50);
        $result = $this->summarizer->summarizeArray([
            $prefix . '1',
            $prefix . '2',
            $prefix . '3'
        ]);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * Return type and format validation
     */
    public function testSummarizeArrayReturnsString(): void
    {
        $result = $this->summarizer->summarizeArray(['test1', 'test2']);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayAlwaysReturnsValidString(): void
    {
        $inputs = [
            [],
            ['single'],
            ['a1', 'b2', 'c3'],
            ['123', '456', '789'],
            [1, 2, 3],
            ['', '', ''],
        ];

        foreach ($inputs as $input) {
            $result = $this->summarizer->summarizeArray($input);
            $this->assertIsString($result, 'Result must always be a string');
        }
    }

    /**
     * Case sensitivity tests
     */
    public function testSummarizeArrayCaseSensitive(): void
    {
        $result = $this->summarizer->summarizeArray(['File1', 'file1', 'FILE1']);
        $this->assertNotEmpty($result);
        // Different cases should be treated separately
        $this->assertIsString($result);
    }

    /**
     * Numeric patterns tests
     */
    public function testSummarizeArrayOnlyNumbers(): void
    {
        $result = $this->summarizer->summarizeArray(['1', '2', '3']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayOnlyLetters(): void
    {
        $result = $this->summarizer->summarizeArray(['abc', 'def', 'ghi']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        // Should contain bracket notations
        $this->assertStringContainsString('[', $result);
        $this->assertStringContainsString('abc', $result);
    }

    /**
     * Real-world scenarios
     */
    public function testSummarizeArrayFileNamingPattern(): void
    {
        $files = array_map(fn($i) => "backup_$i.sql", range(1, 31));
        $result = $this->summarizer->summarizeArray($files);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('backup', $result);
    }

    public function testSummarizeArrayImageSequence(): void
    {
        $images = array_map(fn($i) => sprintf("frame_%04d.png", $i), range(0, 299));
        $result = $this->summarizer->summarizeArray($images);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('frame', $result);
    }

    public function testSummarizeArrayDatabaseTableNames(): void
    {
        $tables = array_map(fn($i) => "users_$i", range(1, 100));
        $result = $this->summarizer->summarizeArray($tables);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('users', $result);
    }

    public function testSummarizeArrayLogFiles(): void
    {
        $logs = array_merge(
            array_map(fn($i) => "error_$i.log", range(1, 10)),
            array_map(fn($i) => "warning_$i.log", range(1, 10)),
            array_map(fn($i) => "info_$i.log", range(1, 5))
        );
        $result = $this->summarizer->summarizeArray($logs);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * Boundary tests
     */
    public function testSummarizeArraySingleItemPerPattern(): void
    {
        $result = $this->summarizer->summarizeArray(['a1', 'b2', 'c3', 'd4']);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayVeryLargeArray(): void
    {
        $input = array_merge(
            array_map(fn($i) => "test_$i", range(1, 100)),
            array_map(fn($i) => "file_$i", range(1, 100)),
            array_map(fn($i) => "item_$i", range(1, 100))
        );
        $result = $this->summarizer->summarizeArray($input);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    /**
     * Internal consistency tests
     */
    public function testSummarizeArrayConsistency(): void
    {
        $input = ['doc1', 'doc2', 'doc3'];
        $result1 = $this->summarizer->summarizeArray($input);
        $result2 = $this->summarizer->summarizeArray($input);

        // Same input should produce same output
        $this->assertSame($result1, $result2);
    }

    public function testSummarizeArrayOrderIndependence(): void
    {
        $input1 = ['file1', 'file2', 'file3'];
        $input2 = ['file3', 'file1', 'file2'];

        $result1 = $this->summarizer->summarizeArray($input1);
        $result2 = $this->summarizer->summarizeArray($input2);

        // Results might differ due to processing order, but both should be non-empty
        $this->assertNotEmpty($result1);
        $this->assertNotEmpty($result2);
    }

    /**
     * Multi-depth array tests (nested arrays)
     */
    public function testSummarizeArrayWithNestedArrays(): void
    {
        $result = $this->summarizer->summarizeArray([
            'valid1',
            ['nested1', 'nested2'],
            'valid2'
        ]);
        $this->assertIsString($result);
        // Nested arrays should be skipped, only strings processed
        $this->assertStringContainsString('valid', $result);
    }

    public function testSummarizeArrayWithDeeplyNestedArrays(): void
    {
        $result = $this->summarizer->summarizeArray([
            'item1',
            [
                'level2_1',
                ['level3_1', 'level3_2']
            ],
            'item2'
        ]);
        $this->assertIsString($result);
        $this->assertStringContainsString('item', $result);
    }

    public function testSummarizeArrayWithMultipleNestedArraysAtRoot(): void
    {
        $result = $this->summarizer->summarizeArray([
            ['arr1_1', 'arr1_2'],
            ['arr2_1', 'arr2_2'],
            'str1'
        ]);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayMixedDepthsAndStrings(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file1',
            'file2',
            ['nested1', 'nested2', 'nested3'],
            'file3',
            [
                'deep1',
                ['deeper1', 'deeper2']
            ],
            'file4'
        ]);
        $this->assertIsString($result);
        // Should contain file strings
        $this->assertStringContainsString('file', $result);
    }

    public function testSummarizeArrayNestedArraysWithNumberPatterns(): void
    {
        $result = $this->summarizer->summarizeArray([
            'test1',
            ['nested1', 'nested2', 'nested3'],
            'test2',
            'test3'
        ]);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('test', $result);
    }

    public function testSummarizeArrayComplexNestedStructure(): void
    {
        $result = $this->summarizer->summarizeArray([
            'item1',
            [
                'sub1',
                [
                    'subsub1',
                    [
                        'subsubsub1'
                    ]
                ],
                'sub2'
            ],
            'item2',
            [
                'another1',
                'another2'
            ],
            'item3'
        ]);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayAllNestedArrays(): void
    {
        $result = $this->summarizer->summarizeArray([
            ['val1', 'val2'],
            ['val3', 'val4'],
            [['val5', 'val6']]
        ]);
        $this->assertNotEmpty($result);
        // Should have nested results
        $this->assertStringContainsString('[[val]', $result);
    }

    public function testSummarizeArrayMixedTypesInNestedArray(): void
    {
        $result = $this->summarizer->summarizeArray([
            'valid1',
            [1, 2, 3, 'nested1'],
            'valid2',
            [null, true, false, 'nested2']
        ]);
        $this->assertIsString($result);
        $this->assertStringContainsString('valid', $result);
    }

    public function testSummarizeArrayLargelyNestedWithPatterns(): void
    {
        $result = $this->summarizer->summarizeArray([
            'log1',
            'log2',
            [
                'log3',
                'log4',
                [
                    'log5',
                    'log6'
                ]
            ],
            'log7',
            'log8'
        ]);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('log', $result);
    }

    public function testSummarizeArrayNestedArraysWithDuplicates(): void
    {
        $result = $this->summarizer->summarizeArray([
            'dup1',
            'dup1',
            ['nested1', 'nested1'],
            'dup1'
        ]);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayNestedArraysEmptyInner(): void
    {
        $result = $this->summarizer->summarizeArray([
            'item1',
            [],
            'item2',
            [[], []],
            'item3'
        ]);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('item', $result);
    }

    public function testSummarizeArrayDeepNestingPerformance(): void
    {
        // Create a deep nested structure
        $deeply_nested = ['level1'];
        $current = &$deeply_nested;
        for ($i = 0; $i < 20; $i++) {
            $current['level' . ($i + 2)] = [];
            $current = &$current['level' . ($i + 2)];
        }
        // Add a string at the deep level
        $current[] = 'deep_string';

        // Should handle deep nesting without errors
        $result = $this->summarizer->summarizeArray($deeply_nested);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayNestedWithNumericKeys(): void
    {
        $result = $this->summarizer->summarizeArray([
            'test1',
            0 => ['nested1', 'nested2'],
            1 => 'test2',
            2 => ['nested3'],
            'test3'
        ]);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayNestedArraysFromForeachIteration(): void
    {
        $input = [];
        for ($i = 1; $i <= 3; $i++) {
            $input[] = "item$i";
            if ($i % 2 == 0) {
                $input[] = ["nested_{$i}_a", "nested_{$i}_b"];
            }
        }

        $result = $this->summarizer->summarizeArray($input);
        $this->assertIsString($result);
        $this->assertStringContainsString('item', $result);
    }

    public function testSummarizeArrayNestedArraysWithStringConversion(): void
    {
        // Objects that might be cast to array
        $result = $this->summarizer->summarizeArray([
            'valid1',
            ['object_string1', 'object_string2'],
            'valid2'
        ]);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayMixedNestingLevels(): void
    {
        $result = $this->summarizer->summarizeArray([
            'root1',
            [
                'level1_1',
                [
                    'level2_1',
                    'level2_2'
                ],
                'level1_2'
            ],
            'root2',
            [
                'level1_3'
            ],
            'root3'
        ]);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayNestedArraysPreserveTopLevel(): void
    {
        $input = [
            'preserve1',
            ['skip1', 'skip2'],
            'preserve2',
            'preserve3'
        ];
        $result = $this->summarizer->summarizeArray($input);

        // Only preserve strings should be in result
        $this->assertStringContainsString('preserve', $result);
        $this->assertNotEmpty($result);
    }

    /**
     * Highly specific nested array tests with exact output matching
     */
    public function testSummarizeArrayNestedWithExactFormat(): void
    {
        $result = $this->summarizer->summarizeArray([
            'test1',
            ['nested1', 'nested2', 'nested3'],
            'test2',
            'test3'
        ]);

        // Expected output: [test][1-3] [[nested][1-3]]
        $expected = '[test][1-3] [[nested][1-3]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayNestedSimpleFormat(): void
    {
        $result = $this->summarizer->summarizeArray([
            ['item1', 'item2']
        ]);

        // Expected output: [[item][1-2]]
        $expected = '[[item][1-2]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayTopLevelOnlyFormat(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file1',
            'file2',
            'file3'
        ]);

        // Expected output: [file][1-3]
        $expected = '[file][1-3]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayMixedNestedMultipleGroups(): void
    {
        $result = $this->summarizer->summarizeArray([
            'log1',
            'log2',
            ['error1', 'error2'],
            'log3',
            ['warn1', 'warn2', 'warn3']
        ]);

        // Expected: [log][1-3] [[error][1-2]] [[warn][1-3]]
        $expected = '[log][1-3] [[error][1-2]] [[warn][1-3]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayNestedDeeplyNested(): void
    {
        $result = $this->summarizer->summarizeArray([
            'parent1',
            [
                'child1',
                'child2'
            ],
            'parent2'
        ]);

        // Expected: [parent][1-2] [[child][1-2]]
        $expected = '[parent][1-2] [[child][1-2]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayNestedWithSingleItemInNested(): void
    {
        $result = $this->summarizer->summarizeArray([
            'item1',
            ['single'],
            'item2'
        ]);

        // Expected: [item][1-2] [item] [[single] [single]]
        $this->assertStringContainsString('[item]', $result);
        $this->assertStringContainsString('[[single]', $result);
    }

    public function testSummarizeArrayMultipleNestedArraysInSequence(): void
    {
        $result = $this->summarizer->summarizeArray([
            ['set1_1', 'set1_2'],
            ['set2_1', 'set2_2']
        ]);

        // Each nested array is summarized independently
        // set1_ and set2_ are different prefixes, so kept separate
        $expected = '[[set1_][1-2]] [[set2_][1-2]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayThreeLayerNesting(): void
    {
        $result = $this->summarizer->summarizeArray([
            'root1',
            [
                'level21',
                'level22',
                [
                    'level31',
                    'level32'
                ]
            ],
            'root2'
        ]);

        // Should contain nested structure with multiple layers
        $expected = '[root][1-2] [[level][21-22] [[level][31-32]]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayMixedPatternsInNestedExact(): void
    {
        $result = $this->summarizer->summarizeArray([
            'app1',
            'app2',
            ['module1', 'module2', 'module3'],
            'app3'
        ]);

        // Expected: [app][1-3] [[module][1-3]]
        $expected = '[app][1-3] [[module][1-3]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayNestedEmptyArray(): void
    {
        $result = $this->summarizer->summarizeArray([
            'item1',
            [],
            'item2'
        ]);

        // Empty nested array should be skipped
        // Expected: [item][1-2]
        $expected = '[item][1-2]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayNestedWithNumberPatterns(): void
    {
        $result = $this->summarizer->summarizeArray([
            'log1',
            'log2',
            ['error10', 'error12', 'error14'],
            'log3'
        ]);

        // Non-consecutive numbers are shown with arithmetic pattern
        $expected = '[log][1-3] [[error][(2 + 10n ~ 14)]]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayComplexMixedStructureExact(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file1',
            'file2',
            ['backup1', 'backup2'],
            'file3',
            ['archive1', 'archive2', 'archive3']
        ]);

        // Expected: [file][1-3] [[backup][1-2]] [[archive][1-3]]
        $expected = '[file][1-3] [[backup][1-2]] [[archive][1-3]]';
        $this->assertEquals($expected, $result);
    }

    /**
     * Multi-language support tests
     */
    public function testSummarizeArrayEnglish(): void
    {
        $result = $this->summarizer->summarizeArray([
            'document1',
            'document2',
            'document3'
        ]);

        // Expected: [document][1-3] [document]
        $this->assertStringContainsString('document', $result);
        $this->assertStringContainsString('[1-3]', $result);
    }

    public function testSummarizeArrayKorean(): void
    {
        $result = $this->summarizer->summarizeArray([
            '파일1',
            '파일2',
            '파일3'
        ]);

        // Korean text should be recognized as prefix
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('파일', $result);
        $this->assertStringContainsString('[1-3]', $result);
    }

    public function testSummarizeArrayJapanese(): void
    {
        $result = $this->summarizer->summarizeArray([
            'ファイル1',
            'ファイル2',
            'ファイル3'
        ]);

        // Japanese Katakana should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('ファイル', $result);
        $this->assertStringContainsString('[1-3]', $result);
    }

    public function testSummarizeArrayJapaneseHiragana(): void
    {
        $result = $this->summarizer->summarizeArray([
            'ファイル1',
            'ファイル2'
        ]);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayChineseSimplified(): void
    {
        $result = $this->summarizer->summarizeArray([
            '文件1',
            '文件2',
            '文件3'
        ]);

        // Simplified Chinese should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('文件', $result);
        $this->assertStringContainsString('[1-3]', $result);
    }

    public function testSummarizeArrayChineseTraditional(): void
    {
        $result = $this->summarizer->summarizeArray([
            '檔案1',
            '檔案2'
        ]);

        // Traditional Chinese should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('檔案', $result);
    }

    public function testSummarizeArrayRussian(): void
    {
        $result = $this->summarizer->summarizeArray([
            'файл1',
            'файл2',
            'файл3'
        ]);

        // Cyrillic script should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('файл', $result);
        $this->assertStringContainsString('[1-3]', $result);
    }

    public function testSummarizeArrayArabic(): void
    {
        $result = $this->summarizer->summarizeArray([
            'ملف1',
            'ملف2',
            'ملف3'
        ]);

        // Arabic script should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('ملف', $result);
    }

    public function testSummarizeArrayGreek(): void
    {
        $result = $this->summarizer->summarizeArray([
            'αρχείο1',
            'αρχείο2'
        ]);

        // Greek letters should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('αρχείο', $result);
    }

    public function testSummarizeArrayThai(): void
    {
        $result = $this->summarizer->summarizeArray([
            'ไฟล์1',
            'ไฟล์2'
        ]);

        // Thai script should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('ไฟล์', $result);
    }

    public function testSummarizeArrayHindi(): void
    {
        $result = $this->summarizer->summarizeArray([
            'फाइल1',
            'फाइल2'
        ]);

        // Devanagari script should be recognized
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayMixedLanguagesSingleLevel(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file1',
            '파일2',
            'ファイル3',
            '文件4'
        ]);

        // Mixed languages should be treated as different prefixes
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayMixedLanguagesNested(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file1',
            'file2',
            ['파일1', '파일2'],
            'file3'
        ]);

        // Mixed languages with nesting
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('file', $result);
        $this->assertStringContainsString('파일', $result);
    }

    public function testSummarizeArrayKoreanNested(): void
    {
        $result = $this->summarizer->summarizeArray([
            '문서1',
            '문서2',
            ['항목1', '항목2', '항목3'],
            '문서3'
        ]);

        // Korean nested arrays
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('문서', $result);
        $this->assertStringContainsString('항목', $result);
    }

    public function testSummarizeArrayJapaneseMixed(): void
    {
        $result = $this->summarizer->summarizeArray([
            'ファイル1',
            'ファイル2',
            ['バージョン1', 'バージョン2'],
            'ファイル3'
        ]);

        // Japanese with nesting
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('ファイル', $result);
    }

    public function testSummarizeArrayChineseMixed(): void
    {
        $result = $this->summarizer->summarizeArray([
            '文档1',
            '文档2',
            ['项目1', '项目2'],
            '文档3'
        ]);

        // Chinese with nesting
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('文档', $result);
    }

    public function testSummarizeArrayRussianMixed(): void
    {
        $result = $this->summarizer->summarizeArray([
            'документ1',
            'документ2',
            ['версия1', 'версия2'],
            'документ3'
        ]);

        // Russian with nesting
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('документ', $result);
        $this->assertStringContainsString('версия', $result);
    }

    public function testSummarizeArrayMultiLanguageComplex(): void
    {
        $result = $this->summarizer->summarizeArray([
            'document1',
            'document2',
            ['파일1', '파일2'],
            'document3',
            ['ファイル1', 'ファイル2', 'ファイル3'],
            '文件1',
            '文件2'
        ]);

        // Complex mix of English, Korean, Japanese, Chinese
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('document', $result);
        $this->assertStringContainsString('파일', $result);
        $this->assertStringContainsString('ファイル', $result);
        $this->assertStringContainsString('文件', $result);
    }

    public function testSummarizeArrayLanguageWithSpecialChars(): void
    {
        $result = $this->summarizer->summarizeArray([
            'café1',
            'café2',
            'café3'
        ]);

        // Accented characters should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('café', $result);
    }

    public function testSummarizeArrayLanguageWithUmlaut(): void
    {
        $result = $this->summarizer->summarizeArray([
            'öffnung1',
            'öffnung2'
        ]);

        // German umlaut should be recognized
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('öffnung', $result);
    }

    public function testSummarizeArrayLanguageConsistency(): void
    {
        $koreanInput = ['파일1', '파일2', '파일3'];
        $result1 = $this->summarizer->summarizeArray($koreanInput);
        $result2 = $this->summarizer->summarizeArray($koreanInput);

        // Same Korean input should produce same output
        $this->assertSame($result1, $result2);
    }

    public function testSummarizeArrayEmojisWithNumbers(): void
    {
        $result = $this->summarizer->summarizeArray([
            '📄1',
            '📄2'
        ]);

        // Emoji with numbers
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayMultiLanguageNestedComplex(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file1',
            'file2',
            [
                '파일1',
                '파일2',
                ['ファイル1', 'ファイル2']
            ],
            '文件1'
        ]);

        // Multi-deepth multi-language structure
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('file', $result);
        $this->assertStringContainsString('파일', $result);
        $this->assertStringContainsString('ファイル', $result);
        $this->assertStringContainsString('文件', $result);
    }

    /**
     * Alphabet range tests
     */
    public function testSummarizeArrayAlphabetRange(): void
    {
        $result = $this->summarizer->summarizeArray([
            'act',
            'bct',
            'cct',
            'dct'
        ]);

        // Expected: [a-d][ct]
        $expected = '[a-d][ct]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayAlphabetRangeConsecutive(): void
    {
        $result = $this->summarizer->summarizeArray([
            'a1',
            'b1',
            'c1'
        ]);

        // Expected: [a-c][1]
        $this->assertStringContainsString('a-c', $result);
        $this->assertStringContainsString('[1]', $result);
    }

    public function testSummarizeArrayAlphabetRangeWithInfix(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file_a_log',
            'file_b_log',
            'file_c_log'
        ]);

        // Expected: [file_][a-c] [_log]
        $this->assertStringContainsString('a-c', $result);
        $this->assertStringContainsString('file_', $result);
        $this->assertStringContainsString('_log', $result);
    }

    public function testSummarizeArrayMixedAlphabetAndNumbers(): void
    {
        $result = $this->summarizer->summarizeArray([
            'a1',
            'b2',
            'c3'
        ]);

        // Mixed alphabet and numbers
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayAlphabetSingleRange(): void
    {
        $result = $this->summarizer->summarizeArray([
            'xa',
            'xb',
            'xc',
            'xd'
        ]);

        // Expected: [x][a-d]
        $this->assertStringContainsString('[x]', $result);
        $this->assertStringContainsString('a-d', $result);
    }

    public function testSummarizeArrayCapitalAlphabetRange(): void
    {
        $result = $this->summarizer->summarizeArray([
            'versionA',
            'versionB',
            'versionC'
        ]);

        // Capital letters should also form ranges
        $this->assertStringContainsString('A-C', $result);
    }

    public function testSummarizeArrayMixedCaseAlphabet(): void
    {
        $result = $this->summarizer->summarizeArray([
            'itemA',
            'itemB',
            'itemZ'
        ]);

        // Mixed case handling
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayAlphabetDisjoint(): void
    {
        $result = $this->summarizer->summarizeArray([
            'doc_a_txt',
            'doc_c_txt',
            'doc_e_txt'
        ]);

        // Non-consecutive letters  
        $this->assertStringContainsString('doc_', $result);
        $this->assertStringContainsString('_txt', $result);
    }

    public function testSummarizeArrayAlphabetWithNumbers(): void
    {
        $result = $this->summarizer->summarizeArray([
            'log_a_1',
            'log_b_1',
            'log_c_1'
        ]);

        // Alphabet in middle, numbers at end
        $this->assertStringContainsString('log_', $result);
        $this->assertStringContainsString('_1', $result);
    }

    public function testSummarizeArrayLongAlphabetRange(): void
    {
        $result = $this->summarizer->summarizeArray(array_map(
            fn($i) => chr(97 + $i) . 'file',
            range(0, 25)
        ));

        // All lowercase letters a-z
        $this->assertStringContainsString('a-z', $result);
        $this->assertStringContainsString('[file]', $result);
    }

    public function testSummarizeArrayAlphabetNestedWithNumbers(): void
    {
        $result = $this->summarizer->summarizeArray([
            'section1',
            'section2',
            ['chapterA', 'chapterB', 'chapterC'],
            'section3'
        ]);

        // Numbers in top level, alphabet in nested
        $this->assertStringContainsString('section', $result);
        $this->assertStringContainsString('chapter', $result);
        $this->assertStringContainsString('A-C', $result);
    }

    public function testSummarizeArrayAlphabetOnlyPrefix(): void
    {
        $result = $this->summarizer->summarizeArray([
            'afile',
            'bfile',
            'cfile'
        ]);

        // Alphabet prefix, constant suffix
        $expected = '[a-c][file]';
        $this->assertEquals($expected, $result);
    }

    public function testSummarizeArrayAlphabetOnlySuffix(): void
    {
        $result = $this->summarizer->summarizeArray([
            'filea',
            'fileb',
            'filec'
        ]);

        // Constant prefix, alphabet suffix
        $this->assertStringContainsString('file', $result);
        $this->assertStringContainsString('a-c', $result);
    }

    public function testSummarizeArrayNumberAndAlphabetRanges(): void
    {
        $result = $this->summarizer->summarizeArray([
            'test1a',
            'test2b',
            'test3c'
        ]);

        // Both numbers and alphabet can be present
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testSummarizeArrayAlphabetWithSpecialChars(): void
    {
        $result = $this->summarizer->summarizeArray([
            'file-a-backup',
            'file-b-backup',
            'file-c-backup'
        ]);

        // Alphabet range with special characters
        $this->assertStringContainsString('a-c', $result);
        $this->assertStringContainsString('file-', $result);
        $this->assertStringContainsString('-backup', $result);
    }

    public function testSummarizeArrayConsistencyAlphabet(): void
    {
        $input = ['xa', 'xb', 'xc'];
        $result1 = $this->summarizer->summarizeArray($input);
        $result2 = $this->summarizer->summarizeArray($input);

        // Same input should produce same output
        $this->assertSame($result1, $result2);
    }

    public function testSummarizeArrayURLWithLargeNumberRange(): void
    {
        // URL range: http://www.google.com/10001.mp3 ... http://www.google.com/20004.mp3
        $urls = array_map(
            fn($n) => "http://www.google.com/{$n}.mp3",
            range(10001, 20004)
        );

        $result = $this->summarizer->summarizeArray($urls);

        // Expected: [http://www.google.com/][10001-20004] [.mp3]
        // (space before suffix is part of the algorithm)
        $this->assertEquals('[http://www.google.com/][10001-20004][.mp3]', $result);
    }

    public function testSummarizeArrayURLWithGaps(): void
    {
        // URL with gaps: 10001, 10003, 10007, 10009 (non-consecutive)
        // All same digit width (5 digits)
        $urls = [
            'http://www.google.com/10001.mp3',
            'http://www.google.com/10003.mp3',
            'http://www.google.com/10007.mp3',
            'http://www.google.com/10009.mp3'
        ];

        $result = $this->summarizer->summarizeArray($urls);

        // With gaps, arithmetic patterns are detected
        $this->assertStringContainsString('http://www.google.com', $result);
        $this->assertStringContainsString('.mp3', $result);
        // Should show the gap pattern
        $this->assertStringContainsString('(', $result); // shows arithmetic notation
        $this->assertNotEmpty($result);
        $this->assertEquals('[http://www.google.com/][(2 + 10001n ~ 10003),(2 + 10007n ~ 10009)][.mp3]', $result);
    }

    public function testSummarizeArrayURLWithDifferentDigits(): void
    {
        // URL with different digit widths: 11 (2 digits), 10003, 10007, 10009 (5 digits)
        $urls = [
            'http://www.google.com/11.mp3',
            'http://www.google.com/10003.mp3',
            'http://www.google.com/10007.mp3',
            'http://www.google.com/10009.mp3'
        ];

        $result = $this->summarizer->summarizeArray($urls);

        // Mixed digit widths with gaps detected
        $this->assertStringContainsString('http://www.google.com', $result);
        $this->assertStringContainsString('.mp3', $result);
        $this->assertNotEmpty($result);
        $this->assertIsString($result);
        $this->assertEquals('[http://www.google.com/][(9992 + 11n ~ 10003),(2 + 10007n ~ 10009)][.mp3]', $result);
    }

    /**
     * Complex pattern compression tests
     */
    public function testSummarizeArrayMultiplePrefixesSuffixes(): void
    {
        // Pattern: file_1_backup_2024.tar, file_2_backup_2024.tar, ... file_10_backup_2024.tar
        $files = array_map(
            fn($i) => "file_{$i}_backup_2024.tar",
            range(1, 10)
        );

        $result = $this->summarizer->summarizeArray($files);

        // Should compress to: [file_][1-10][_backup_2024.tar]
        $this->assertStringContainsString('file_', $result);
        $this->assertStringContainsString('_backup_2024.tar', $result);
        $this->assertStringContainsString('1-10', $result);
    }

    public function testSummarizeArrayVersionNumberPattern(): void
    {
        // Pattern: version_1.2.3.log, version_1.2.4.log, version_1.2.5.log
        $logs = [
            'version_1.2.3.log',
            'version_1.2.4.log',
            'version_1.2.5.log'
        ];

        $result = $this->summarizer->summarizeArray($logs);

        // Should recognize version prefix and log suffix
        $this->assertStringContainsString('version_', $result);
        $this->assertStringContainsString('.log', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals('[version_1.2.][3-5][.log]', $result);
    }

    public function testSummarizeArrayMultipleNumberGroups(): void
    {
        // Pattern: log_app_1_error.txt, log_app_2_error.txt, log_db_1_error.txt, log_db_2_error.txt
        $logs = [
            'log_app_1_error.txt',
            'log_app_2_error.txt',
            'log_db_1_error.txt',
            'log_db_2_error.txt'
        ];

        $result = $this->summarizer->summarizeArray($logs);

        // All items have same pattern: log_*_error.txt
        // Variable parts are: app_1, app_2, db_1, db_2 (compound pattern with prefix||prefix][number)
        // Expected result: [log_][app_||db_][1-2][_error.txt]
        $expected = '[log_][app_||db_][1-2][_error.txt]';
        $this->assertEquals($expected, $result);
    }


    public function testSummarizeArrayDomainPattern(): void
    {
        // Pattern: subdomain1.example.com, subdomain2.example.com, ..., subdomain50.example.com
        $domains = array_map(
            fn($i) => "subdomain{$i}.example.com",
            range(1, 50)
        );

        $result = $this->summarizer->summarizeArray($domains);

        // Should compress: [subdomain][1-50][.example.com]
        $this->assertStringContainsString('subdomain', $result);
        $this->assertStringContainsString('.example.com', $result);
        $this->assertStringContainsString('1-50', $result);
    }

    public function testSummarizeArrayBackupDatePattern(): void
    {
        // Pattern: backup_2024_01_01.sql, backup_2024_01_02.sql, ..., backup_2024_01_10.sql
        $backups = array_map(
            fn($i) => "backup_2024_01_" . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ".sql",
            range(1, 10)
        );

        $result = $this->summarizer->summarizeArray($backups);

        // Should compress date-based patterns
        $this->assertStringContainsString('backup_2024_01_', $result);
        $this->assertStringContainsString('.sql', $result);
        $this->assertNotEmpty($result);
    }

    public function testSummarizeArrayApplicationVersionPattern(): void
    {
        // Pattern: app_v1.0.0, app_v2.0.0, app_v3.0.0
        $versions = [
            'app_v1.0.0',
            'app_v2.0.0',
            'app_v3.0.0'
        ];

        $result = $this->summarizer->summarizeArray($versions);

        // Should recognize app_v prefix and .0.0 suffix with version number range
        $this->assertStringContainsString('app_v', $result);
        $this->assertStringContainsString('1-3', $result);
    }

    public function testSummarizeArrayMixedAlphabetNumberMulti(): void
    {
        // Pattern: chapter_a_1, chapter_b_2, chapter_c_3 (mixed alphabet and numeric variables)
        $chapters = [
            'chapter_a_1',
            'chapter_b_2',
            'chapter_c_3'
        ];

        $result = $this->summarizer->summarizeArray($chapters);

        // Should handle mixed variable types
        $this->assertStringContainsString('chapter_', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals('[chapter_][a_||b_||c_][1-3]', $result);
    }

    public function testSummarizeArrayDatabaseTablePattern(): void
    {
        // Pattern: users_2024_01, users_2024_02, ..., users_2024_12 (monthly sharding)
        $tables = array_map(
            fn($m) => "users_2024_" . str_pad((string) $m, 2, '0', STR_PAD_LEFT),
            range(1, 12)
        );

        $result = $this->summarizer->summarizeArray($tables);

        // Should recognize table prefix and handle month range
        $this->assertStringContainsString('users_2024_', $result);
        $this->assertNotEmpty($result);
    }

    public function testSummarizeArrayServerIPPattern(): void
    {
        // Pattern: server_192.168.1.1, server_192.168.1.2, ..., server_192.168.1.10
        $servers = array_map(
            fn($i) => "server_192.168.1.{$i}",
            range(1, 10)
        );

        $result = $this->summarizer->summarizeArray($servers);

        // Should compress server prefix and IP suffix
        $this->assertStringContainsString('server_', $result);
        $this->assertStringContainsString('192.168.1', $result);
        $this->assertStringContainsString('1-10', $result);
    }

    public function testSummarizeArrayCacheKeyPattern(): void
    {
        // Pattern: cache_abc123_1, cache_abc123_2, ..., cache_abc123_100
        $caches = array_map(
            fn($i) => "cache_abc123_{$i}",
            range(1, 100)
        );

        $result = $this->summarizer->summarizeArray($caches);

        // Should recognize cache prefix, hash infix, number suffix
        $this->assertStringContainsString('cache_abc123_', $result);
        $this->assertStringContainsString('1-100', $result);
    }

    public function testSummarizeArrayComplexNestedWithMultipleLevels(): void
    {
        // Top level with numbers, nested with letters, deep nested with mixed
        $complex = [
            'data1',
            'data2',
            [
                'itemA',
                'itemB',
                'itemC',
                ['log_1', 'log_2', 'log_3']
            ],
            'data3'
        ];

        $result = $this->summarizer->summarizeArray($complex);

        // Should handle multiple nesting levels
        $this->assertStringContainsString('data', $result);
        $this->assertStringContainsString('item', $result);
        $this->assertStringContainsString('log', $result);
        $this->assertStringContainsString('[', $result);
        $this->assertEquals('[data][1-3] [[item][A-C] [[log_][1-3]]]', $result);
    }

    public function testSummarizeArrayLogWithTimestampPattern(): void
    {
        // Pattern: access_2024-01-01_12:00:00.log, access_2024-01-01_12:01:00.log, etc
        $logs = [
            'access_2024-01-01_12:00:00.log',
            'access_2024-01-01_12:01:00.log',
            'access_2024-01-01_12:02:00.log',
            'access_2024-01-01_12:03:00.log'
        ];

        $result = $this->summarizer->summarizeArray($logs);

        // Should recognize access prefix and log suffix
        $this->assertStringContainsString('access_', $result);
        $this->assertStringContainsString('.log', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals('[access_2024-01-01_12:][00-03][:00.log]', $result);
    }

    public function testSummarizeArrayDocumentArchivePattern(): void
    {
        // Pattern: document_archive_backup_v1.zip, document_archive_backup_v2.zip, ...
        $archives = array_map(
            fn($v) => "document_archive_backup_v{$v}.zip",
            range(1, 5)
        );

        $result = $this->summarizer->summarizeArray($archives);

        // Should compression: [document_archive_backup_v][1-5][.zip]
        $this->assertStringContainsString('document_archive_backup_v', $result);
        $this->assertStringContainsString('.zip', $result);
        $this->assertStringContainsString('1-5', $result);
    }

    public function testSummarizeArrayMixedLanguageComplexPattern(): void
    {
        // Pattern: 파일_a_1, 파일_b_2, 파일_c_3 (Korean text with alphabet and numeric mix)
        $items = [
            '파일_a_1',
            '파일_b_2',
            '파일_c_3'
        ];

        $result = $this->summarizer->summarizeArray($items);

        // Should handle Korean prefix with mixed variables
        $this->assertStringContainsString('파일_', $result);
        $this->assertNotEmpty($result);
        $this->assertEquals('[파일_][a_||b_||c_][1-3]', $result);
    }

    public function testSummarizeArrayURLDeepNesting(): void
    {
        // Complex nested URL patterns
        $data = [
            'http://api.example.com/v1',
            'http://api.example.com/v2',
            [
                'http://api.example.com/v1/users/1',
                'http://api.example.com/v1/users/2',
                [
                    'http://api.example.com/v1/users/1/profile',
                    'http://api.example.com/v1/users/1/settings'
                ]
            ]
        ];

        $result = $this->summarizer->summarizeArray($data);

        // Should preserve nested structure
        $this->assertStringContainsString('http://api.example.com', $result);
        $this->assertStringContainsString('[[', $result); // nested markers
        $this->assertNotEmpty($result);
        $this->assertEquals('[http://api.example.com/v][1-2] [[http://api.example.com/v1/users/][1-2] [[http://api.example.com/v1/users/1/][profile,settings]]]', $result);
    }

    public function testIp(): void
    {
        $summarizer = new ArraySummarizer();
        $ips1 = array_map(fn($i) => "192.168.1.$i", range(1, 10));
        $result1 = $summarizer->summarizeArray($ips1);
        $this->assertEquals('[192.168.1.][1-10]', $result1);

        $ips2 = array_merge(
            array_map(fn($i) => "192.168.1.$i", range(1, 5)),
            array_map(fn($i) => "192.168.2.$i", range(1, 5))
        );
        $result2 = $summarizer->summarizeArray($ips2);
        $this->assertEquals('[192.168.][1.1-1.5(step:0.1),2.1-2.5(step:0.1)]', $result2);

        $ips3 = [
            '10.0.1.1',
            '10.0.1.2',
            '10.0.1.3',
            '10.1.1.1',
            '10.1.1.2',
            '10.1.1.3',
            '10.2.1.1',
            '10.2.1.2',
            '10.2.1.3'
        ];
        $result3 = $summarizer->summarizeArray($ips3);
        $this->assertEquals('[10.][0.1.||1.1.||2.1.][1-3]', $result3);

        $ips4 = array_map(fn($i) => "172.16.0.$i", range(0, 100, 10));
        $result4 = $summarizer->summarizeArray($ips4);
        $this->assertEquals('[172.16.0.][1-10][0]', $result4);

        $ips5 = array_map(fn($i) => "192.168.1.$i:8000", range(1, 5));
        $result5 = $summarizer->summarizeArray($ips5);
        $this->assertEquals('[192.168.1.][1-5][:8000]', $result5);
    }

    /**
     * Calendar data with nested weeks and day objects
     */
    public function testSummarizeArrayCalendarData(): void
    {
        // March 2026 calendar data: 5 weeks with 7 days each
        $calendarData = [
            [
                ["day" => 1, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
                ["day" => 2, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 3, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 4, "month" => 3, "year" => 2026, "current" => true, "today" => true, "weekend" => false],
                ["day" => 5, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 6, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 7, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
            ],
            [
                ["day" => 8, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
                ["day" => 9, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 10, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 11, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 12, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 13, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 14, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
            ],
            [
                ["day" => 15, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
                ["day" => 16, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 17, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 18, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 19, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 20, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 21, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
            ],
            [
                ["day" => 22, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
                ["day" => 23, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 24, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 25, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 26, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 27, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 28, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
            ],
            [
                ["day" => 29, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => true],
                ["day" => 30, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 31, "month" => 3, "year" => 2026, "current" => true, "today" => false, "weekend" => false],
                ["day" => 1, "month" => 4, "year" => 2026, "current" => false, "today" => false, "weekend" => false],
                ["day" => 2, "month" => 4, "year" => 2026, "current" => false, "today" => false, "weekend" => false],
                ["day" => 3, "month" => 4, "year" => 2026, "current" => false, "today" => false, "weekend" => false],
                ["day" => 4, "month" => 4, "year" => 2026, "current" => false, "today" => false, "weekend" => true],
            ],
        ];

        $result = $this->summarizer->summarizeArray($calendarData);

        // Should process complex calendar structure with arrays of day objects
        // Verify it returns a non-empty string
        $this->assertNotEmpty($result);
        $this->assertIsString($result);

        // Verify it processes multiple nested levels (weeks and days)
        $this->assertStringContainsString('[', $result);
        $this->assertStringContainsString(']', $result);

        // Verify year values are captured
        $this->assertStringContainsString('2026', $result);
        $this->assertEquals('[[[0-1,1,(2023 + 3n ~ 2026)]] [[0,1-3,2026]] [[0,(2 + 1n ~ 3),(2023 + 3n ~ 2026)]] [[0-1,(2 + 1n ~ 3),(2022 + 4n ~ 2026)]] [[0,(2 + 1n ~ 5),2026]] [[0,(2 + 1n ~ 3),(2020 + 6n ~ 2026)]] [[0-1,(2 + 1n ~ 3),(2019 + 7n ~ 2026)]]] [[[0-1,(2 + 1n ~ 3),(2018 + 8n ~ 2026)]] [[0,(2 + 1n ~ 3),(2017 + 9n ~ 2026)]] [[0,(2 + 1n ~ 3),(2016 + 10n ~ 2026)]] [[0,(2 + 1n ~ 3),(2015 + 11n ~ 2026)]] [[0,(2 + 1n ~ 3),(2014 + 12n ~ 2026)]] [[0,(2 + 1n ~ 3),(2013 + 13n ~ 2026)]] [[0-1,(2 + 1n ~ 3),(2012 + 14n ~ 2026)]]] [[[0-1,(2 + 1n ~ 3),(2011 + 15n ~ 2026)]] [[0,(2 + 1n ~ 3),(2010 + 16n ~ 2026)]] [[0,(2 + 1n ~ 3),(2009 + 17n ~ 2026)]] [[0,(2 + 1n ~ 3),(2008 + 18n ~ 2026)]] [[0,(2 + 1n ~ 3),(2007 + 19n ~ 2026)]] [[0,(2 + 1n ~ 3),(2006 + 20n ~ 2026)]] [[0-1,(2 + 1n ~ 3),(2005 + 21n ~ 2026)]]] [[[0-1,(2 + 1n ~ 3),(2004 + 22n ~ 2026)]] [[0,(2 + 1n ~ 3),(2003 + 23n ~ 2026)]] [[0,(2 + 1n ~ 3),(2002 + 24n ~ 2026)]] [[0,(2 + 1n ~ 3),(2001 + 25n ~ 2026)]] [[0,(2 + 1n ~ 3),(2000 + 26n ~ 2026)]] [[0,(2 + 1n ~ 3),(1999 + 27n ~ 2026)]] [[0-1,(2 + 1n ~ 3),(1998 + 28n ~ 2026)]]] [[[0-1,(2 + 1n ~ 3),(1997 + 29n ~ 2026)]] [[0,(2 + 1n ~ 3),(1996 + 30n ~ 2026)]] [[0,(2 + 1n ~ 3),(1995 + 31n ~ 2026)]] [[0,(3 + 1n ~ 4),2026]] [[0,(2 + 2n ~ 4),2026]] [[0,3-4,2026]] [[0,(3 + 1n ~ 4),(2022 + 4n ~ 2026)]]]', $result);
    }

    public function testSummarizeArrayPaddedNumbers(): void
    {
        $padded = ['file_001', 'file_002', 'file_003'];
        $result = $this->summarizer->summarizeArray($padded);
        $this->assertEquals('[file_][001-003]', $result);
    }

    public function testSummarizeArrayDescending(): void
    {
        $descending = ['file_3', 'file_2', 'file_1'];
        $result = $this->summarizer->summarizeArray($descending);
        $this->assertEquals('[file_][3-1]', $result);

        $descendingPadded = ['v05', 'v04', 'v03'];
        $result2 = $this->summarizer->summarizeArray($descendingPadded);
        $this->assertEquals('[v][05-03]', $result2);
    }

    public function testSummarizeArrayFloats(): void
    {
        $floats = ['v1.0', 'v2.0', 'v3.0'];
        $result = $this->summarizer->summarizeArray($floats);
        $this->assertEquals('[v][1-3][.0]', $result);
    }

    public function testSummarizeArrayFloatStep(): void
    {
        $floatSteps = ['app_1.5', 'app_2.0', 'app_2.5'];
        $result = $this->summarizer->summarizeArray($floatSteps);
        $this->assertEquals('[app_][1.5-2.5(step:0.5)]', $result);
    }

    public function testSummaryToRegex(): void
    {
        $summarizer = new ArraySummarizer();

        $tests = [
            '[file_][001-003]' => ['file_001', 'file_002', 'file_003'],
            '[v][05-03]' => ['v05', 'v04', 'v03'],
            '[app_v][1.0.||2.0.||3.0.][0]' => ['app_v1.0.0', 'app_v2.0.0', 'app_v3.0.0'],
            '[log_][app_||db_][1-2][_error.txt]' => ['log_app_1_error.txt', 'log_db_2_error.txt'],
            '[http://www.google.com/][11,10003,10007,10009][.mp3]' => ['http://www.google.com/11.mp3', 'http://www.google.com/10003.mp3', 'http://www.google.com/10009.mp3'],
            '[access_2024-01-01_12:][00-03][:00.log]' => ['access_2024-01-01_12:00:00.log', 'access_2024-01-01_12:03:00.log'],
            '[root][1-2] [[level][21-22] [[level][31-32]]]' => ['root1', 'level21', 'level32'],
            '[192.168.][1.1-1.5,2.1-2.5]' => ['192.168.1.1', '192.168.1.5', '192.168.2.3']
        ];

        foreach ($tests as $summary => $expectedMatches) {
            $regex = $summarizer->summaryToRegex($summary);
            $this->assertNotEmpty($regex, "Regex should not be empty for summary: $summary");

            // Verify it is a valid regex
            $this->assertNotFalse(@preg_match($regex, ''), "Generated regex is invalid: $regex");

            foreach ($expectedMatches as $match) {
                // Assert that the generated regex accurately matches the string it summarized
                $this->assertEquals(1, preg_match($regex, $match), "Regex '$regex' failed to match expected string '$match'");
            }
        }
    }
}
