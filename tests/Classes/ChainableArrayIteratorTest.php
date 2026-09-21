<?php

declare(strict_types=1);

namespace Clover\Tests\Classes;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\ChainableArrayIterator;
use PHPUnit\Framework\TestCase;

class ChainableArrayIteratorTest extends TestCase
{
    public function testFirst(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $result = $it->first();
        $this->assertSame($it, $result);
        $this->assertEquals(10, $it->current());
        $this->assertEquals(0, $it->key());
    }

    public function testLast(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $result = $it->last();
        $this->assertSame($it, $result);
        $this->assertEquals(30, $it->current());
        $this->assertEquals(2, $it->key());
    }

    public function testLastOnEmptyArray(): void
    {
        $it = new ChainableArrayIterator([]);
        $result = $it->last();
        $this->assertSame($it, $result);
    }

    public function testNext(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $it->rewind();
        $result = $it->next();
        $this->assertSame($it, $result);
        $this->assertEquals(20, $it->current());
    }

    public function testPrev(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $it->seek(2);
        $result = $it->prev();
        $this->assertSame($it, $result);
        $this->assertEquals(20, $it->current());
    }

    public function testPrevAtStartDoesNotMove(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $it->rewind();
        $it->prev();
        $this->assertEquals(10, $it->current());
        $this->assertEquals(0, $it->key());
    }

    public function testCurrentValue(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $it->seek(1);
        $this->assertEquals(20, $it->currentValue());
    }

    public function testHasNext(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $it->rewind();
        $this->assertTrue($it->hasNext());
        $it->seek(2);
        $this->assertFalse($it->hasNext());
    }

    public function testHasPrev(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $it->rewind();
        $this->assertFalse($it->hasPrev());
        $it->seek(1);
        $this->assertTrue($it->hasPrev());
    }

    public function testRewindReturnsSelf(): void
    {
        $it = new ChainableArrayIterator([10, 20, 30]);
        $it->seek(2);
        $result = $it->rewind();
        $this->assertSame($it, $result);
        $this->assertEquals(10, $it->current());
    }

    public function testChainedCalls(): void
    {
        $it = new ChainableArrayIterator([1, 2, 3, 4, 5]);
        $it->first()->next()->next();
        $this->assertEquals(3, $it->current());
    }
}
