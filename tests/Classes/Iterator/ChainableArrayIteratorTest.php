<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Iterator;

use Clover\Classes\ChainableArrayIterator;
use PHPUnit\Framework\TestCase;

final class ChainableArrayIteratorTest extends TestCase
{
	public function testNavigationMethodsAreChainableAndExposeCurrentValue(): void
	{
		$iterator = new ChainableArrayIterator(['first', 'second', 'third']);

		$this->assertSame($iterator, $iterator->first());
		$this->assertSame('first', $iterator->currentValue());
		$this->assertFalse($iterator->hasPrev());
		$this->assertTrue($iterator->hasNext());

		$this->assertSame($iterator, $iterator->next());
		$this->assertSame('second', $iterator->currentValue());
		$this->assertTrue($iterator->hasPrev());
		$this->assertTrue($iterator->hasNext());

		$this->assertSame($iterator, $iterator->last());
		$this->assertSame('third', $iterator->currentValue());
		$this->assertTrue($iterator->hasPrev());
		$this->assertFalse($iterator->hasNext());

		$this->assertSame($iterator, $iterator->prev());
		$this->assertSame('second', $iterator->currentValue());
	}

	public function testEmptyIteratorNavigationRemainsSafe(): void
	{
		$iterator = new ChainableArrayIterator([]);

		$this->assertSame($iterator, $iterator->first()->last()->prev());
		$this->assertNull($iterator->currentValue());
		$this->assertFalse($iterator->hasPrev());
	}
}
