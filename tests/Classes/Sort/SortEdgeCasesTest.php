<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Sort;

use Clover\Classes\Sort\BubbleSort;
use Clover\Classes\Sort\HeapSort;
use Clover\Classes\Sort\InsertableSort;
use Clover\Classes\Sort\MergeSort;
use Clover\Classes\Sort\QuickSort;
use Clover\Classes\Sort\RadixSort;
use Clover\Classes\Sort\RangeSort;
use Clover\Classes\Sort\ShellSort;
use Clover\Classes\Sort\TimSort;
use PHPUnit\Framework\TestCase;

final class SortEdgeCasesTest extends TestCase
{
	public function testAllSortersHandleEmptyAndSingleValueArrays(): void
	{
		foreach ($this->sorters() as $sorter) {
			$this->assertSame([], $sorter::sort([]), $sorter);
			$this->assertSame([5], array_values($sorter::sort([5])), $sorter);
		}
	}

	public function testAllSortersHandleDuplicatesZerosAndNegativeIntegers(): void
	{
		$input = [3, -1, 0, 3, -5, 2, 0];
		$expected = [-5, -1, 0, 0, 2, 3, 3];

		foreach ($this->sorters() as $sorter) {
			$this->assertSame($expected, array_values($sorter::sort($input)), $sorter);
		}
	}

	public function testComparisonBasedSortersHandleStrings(): void
	{
		$input = ['delta', 'alpha', 'charlie', 'bravo'];
		$expected = ['alpha', 'bravo', 'charlie', 'delta'];

		foreach ($this->comparisonSorters() as $sorter) {
			$this->assertSame($expected, array_values($sorter::sort($input)), $sorter);
		}
	}

	private function sorters(): array
	{
		return [
			BubbleSort::class,
			HeapSort::class,
			InsertableSort::class,
			MergeSort::class,
			QuickSort::class,
			RadixSort::class,
			RangeSort::class,
			ShellSort::class,
			TimSort::class,
		];
	}

	private function comparisonSorters(): array
	{
		return [
			BubbleSort::class,
			HeapSort::class,
			InsertableSort::class,
			MergeSort::class,
			QuickSort::class,
			RangeSort::class,
			ShellSort::class,
			TimSort::class,
		];
	}
}
