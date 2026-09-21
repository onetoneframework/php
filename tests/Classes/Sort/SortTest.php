<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Sort;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

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

class SortTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testSort(): void
	{
		$numbers = [4, 8, 7, 2, 9, 3, 5, 1, 6, 10];
		$correct = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

		$this->assertEquals($correct, array_values(BubbleSort::sort($numbers)));
		$this->assertEquals($correct, array_values(HeapSort::sort($numbers)));
		$this->assertEquals($correct, array_values(InsertableSort::sort($numbers)));
		$this->assertEquals($correct, array_values(MergeSort::sort($numbers)));
		$this->assertEquals($correct, array_values(QuickSort::sort($numbers)));
		$this->assertEquals($correct, array_values(RadixSort::sort($numbers)));
		$this->assertEquals($correct, array_values(RangeSort::sort($numbers)));
		$this->assertEquals($correct, array_values(ShellSort::sort($numbers)));
		$this->assertEquals($correct, array_values(TimSort::sort($numbers)));
	}
}
