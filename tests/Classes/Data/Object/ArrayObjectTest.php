<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data\Object;
use Exception;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\StringObject;
use PHPUnit\Framework\TestCase;

class ArrayObjectTest extends TestCase
{
    public function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', sprintf("%s/../../../../root", __DIR__));
        }
    }

    public function testConstructorWithArray(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $this->assertEquals(3, $arr->count());
    }

    public function testConstructorThrowsOnInvalidType(): void
    {
        $this->expectException(\TypeError::class);
        new ArrayObject('invalid');
    }

    public function testSlice(): void
    {
        $arr = new ArrayObject([1, 2, 3, 4, 5]);
        $sliced = $arr->slice(1, 3);
        $this->assertEquals([2, 3, 4], $sliced->getRawData());
    }

    public function testSliceLeft(): void
    {
        $arr = new ArrayObject([1, 2, 3, 4, 5]);
        $sliced = $arr->sliceLeft(2);
        $this->assertEquals([1, 2], $sliced->getRawData());
    }

    public function testSliceRight(): void
    {
        $arr = new ArrayObject([1, 2, 3, 4, 5]);
        $sliced = $arr->sliceRight(2);
        $this->assertEquals([3, 4, 5], $sliced->getRawData());
    }

    public function testStartsWith(): void
    {
        $arr = new ArrayObject(['apple', 'apricot', 'banana']);
        $filtered = $arr->startsWith('ap');
        $this->assertCount(2, $filtered->getRawData());
        $this->assertContains('apple', $filtered->getRawData());
        $this->assertContains('apricot', $filtered->getRawData());
    }

    public function testEndsWith(): void
    {
        $arr = new ArrayObject(['test.php', 'test.txt', 'readme.txt']);
        $filtered = $arr->endsWith('.txt');
        $this->assertCount(2, $filtered->getRawData());
    }

    public function testReplace(): void
    {
        $arr = new ArrayObject(['hello', 'world', 'hello']);
        $replaced = $arr->replace('hello', 'hi');
        $data = $replaced->getRawData();
        $this->assertEquals('hi', $data[0]);
        $this->assertEquals('hi', $data[2]);
    }

    public function testFillPrimes(): void
    {
        $arr = new ArrayObject([]);
        $primes = $arr->fillPrimes(2, 10);
        $this->assertEquals([2, 3, 5, 7], $primes->getRawData());
    }

    public function testFillRange(): void
    {
        $arr = new ArrayObject([1, 2]);
        $result = $arr->fillRange(3, 5);
        $this->assertSame($arr, $result);
        $this->assertGreaterThanOrEqual(2, $arr->count());
    }

    public function testMerge(): void
    {
        $a = new ArrayObject([1, 2]);
        $b = new ArrayObject([3, 4]);
        $merged = $a->merge($b);
        $this->assertEquals([1, 2, 3, 4], $merged->getRawData());
    }

    public function testMergeUnique(): void
    {
        $a = new ArrayObject([1, 2, 3]);
        $b = new ArrayObject([2, 3, 4]);
        $merged = $a->mergeUnique($b);
        $this->assertCount(4, $merged->getRawData());
    }

    public function testFilter(): void
    {
        $arr = new ArrayObject([1, 2, 3, 4, 5]);
        $filtered = $arr->filter(fn($v) => $v % 2 === 0);
        $this->assertEquals([2, 4], array_values($filtered->getRawData()));
    }

    public function testMap(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $mapped = $arr->map(fn($v) => $v * 2);
        $this->assertEquals([2, 4, 6], $mapped->getRawData());
    }

    public function testReverse(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $reversed = $arr->reverse();
        $this->assertEquals([3, 2, 1], $reversed->getRawData());
    }

    public function testReversePop(): void
    {
        $arr = new ArrayObject([1, 2, 3, 4, 5]);
        $popped = $arr->reversePop(2);
        $this->assertEquals([1, 2, 3], $popped->getRawData());
    }

    public function testShuffle(): void
    {
        $arr = new ArrayObject([1, 2, 3, 4, 5]);
        $shuffled = $arr->shuffle();
        $this->assertCount(5, $shuffled->getRawData());
        $this->assertEqualsCanonicalizing([1, 2, 3, 4, 5], $shuffled->getRawData());
    }

    public function testChunk(): void
    {
        $arr = new ArrayObject([1, 2, 3, 4, 5]);
        $chunked = $arr->chunk(2);
        $data = $chunked->getRawData();
        $this->assertCount(3, $data);
        $this->assertCount(2, $data[0]);
        $this->assertCount(2, $data[1]);
        $this->assertCount(1, $data[2]);
    }

    public function testFlatten(): void
    {
        $arr = new ArrayObject([[1, 2], [3, 4], [5]]);
        $flattened = $arr->flatten();
        $this->assertEquals([1, 2, 3, 4, 5], $flattened->getRawData());
    }

    public function testClean(): void
    {
        $arr = new ArrayObject([1, null, 2, '', 3]);
        $cleaned = $arr->clean();
        $this->assertEquals([1, 2, 3], array_values($cleaned->getRawData()));
    }

    public function testGetKeys(): void
    {
        $arr = new ArrayObject(['a' => 1, 'b' => 2]);
        $keys = $arr->getKeys();
        $this->assertEquals(['a', 'b'], $keys->getRawData());
    }

    public function testGetChainKeys(): void
    {
        $arr = new ArrayObject(['a' => 1, 'b' => 2]);
        $chainKeys = $arr->getChainKeys();
        $this->assertInstanceOf(ArrayObject::class, $chainKeys);
    }

    public function testComputesDifference(): void
    {
        $a = new ArrayObject([1, 2, 3]);
        $b = new ArrayObject([2, 3]);
        $diff = $a->computesDifference($b);
        $this->assertEquals([1], $diff->getRawData());
    }

    public function testComputesIntersection(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $result = $arr->computeIntersection([2, 3, 4]);
        $this->assertInstanceOf(ArrayObject::class, $result);
        $this->assertGreaterThanOrEqual(0, $result->count());
    }

    public function testComputesIntersectionWithIndex(): void
    {
        $arr = new ArrayObject(['a' => 1, 'b' => 2]);
        $result = $arr->computeIntersectionWithIndex(['a' => 1, 'c' => 3]);
        $this->assertArrayHasKey('a', $result->getRawData());
        $this->assertEquals(1, $result->getRawData()['a']);
    }

    public function testIsEquals(): void
    {
        $a = new ArrayObject([1, 2, 3]);
        $b = new ArrayObject([1, 2, 3]);
        $c = new ArrayObject([1, 2]);
        $this->assertTrue($a->isEquals($b));
        $this->assertFalse($a->isEquals($c));
    }

    public function testIsContains(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $this->assertTrue($arr->isContains(2));
        $this->assertFalse($arr->isContains(5));
    }

    public function testIsContainKey(): void
    {
        $arr = new ArrayObject(['a' => 1, 'b' => 2]);
        $this->assertTrue($arr->isContainKey('a'));
        $this->assertFalse($arr->isContainKey('c'));
    }

    public function testHas(): void
    {
        $arr = new ArrayObject(['x' => 10]);
        $this->assertTrue($arr->has('x'));
        $this->assertFalse($arr->has('y'));
    }

    public function testIsList(): void
    {
        $list = new ArrayObject([1, 2, 3]);
        $assoc = new ArrayObject(['a' => 1]);
        $this->assertTrue($list->isList());
        $this->assertFalse($assoc->isList());
    }

    public function testGroup(): void
    {
        $arr = new ArrayObject([['type' => 'a', 'v' => 1], ['type' => 'b', 'v' => 2], ['type' => 'a', 'v' => 3]]);
        $grouped = $arr->group('type');
        $data = $grouped->getRawData();
        $this->assertCount(2, $data['a']);
        $this->assertCount(1, $data['b']);
    }

    public function testPluck(): void
    {
        $arr = new ArrayObject([['id' => 1, 'name' => 'a'], ['id' => 2, 'name' => 'b']]);
        $plucked = $arr->pluck('name');
        $this->assertEquals(['a', 'b'], $plucked->getRawData());
    }

    public function testClear(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $cleared = $arr->clear();
        $this->assertCount(0, $cleared->getRawData());
        $this->assertSame($arr, $cleared);
    }

    public function testRemove(): void
    {
        $arr = new ArrayObject(['a' => 1, 'b' => 2]);
        $arr->remove('a');
        $this->assertFalse($arr->has('a'));
        $this->assertEquals(1, $arr->count());
    }

    public function testRemoveThrowsOnMissingKey(): void
    {
        $arr = new ArrayObject(['a' => 1]);
        $this->expectException(Exception::class);
        $arr->remove('nonexistent');
    }

    public function testArrayAccess(): void
    {
        $arr = new ArrayObject(['a' => 1]);
        $this->assertTrue(isset($arr['a']));
        $this->assertEquals(1, $arr['a']);
        $arr['b'] = 2;
        $this->assertEquals(2, $arr['b']);
        unset($arr['a']);
        $this->assertFalse(isset($arr['a']));
    }

    public function testIterator(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $values = [];
        foreach ($arr as $v) {
            $values[] = $v;
        }
        $this->assertEquals([1, 2, 3], $values);
    }

    public function testJoin(): void
    {
        $arr = new ArrayObject(['a', 'b', 'c']);
        $joined = $arr->join('-');
        $this->assertInstanceOf(StringObject::class, $joined);
        $this->assertEquals('a-b-c', (string) $joined);
    }

    public function testSize(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $this->assertEquals(3, $arr->size());
    }

    public function testSizeGreaterThan(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $this->assertTrue($arr->sizeGreaterThan(2));
        $this->assertFalse($arr->sizeGreaterThan(5));
    }

    public function testSizeEquals(): void
    {
        $arr = new ArrayObject([1, 2, 3]);
        $this->assertTrue($arr->sizeEquals(3));
    }

    public function testSizeSmallerThan(): void
    {
        $arr = new ArrayObject([1, 2]);
        $this->assertTrue($arr->sizeSmallerThan(5));
    }

    public function testToObject(): void
    {
        $arr = new ArrayObject(['a' => 1, 'b' => 2]);
        $obj = $arr->toObject();
        $this->assertIsObject($obj);
        $this->assertEquals(1, $obj->a);
        $this->assertEquals(2, $obj->b);
    }

    public function testFromIterator(): void
    {
        $arr = new ArrayObject([]);
        $iter = new \ArrayIterator([10, 20, 30]);
        $arr->fromIterator($iter, true);
        $this->assertEquals(3, $arr->count());
    }

    public function testSort(): void
    {
        $arr = new ArrayObject([3, 1, 2]);
        $sorted = $arr->sort();
        $this->assertEquals([1, 2, 3], $sorted->getRawData());
    }

    public function testSortByKey(): void
    {
        $arr = new ArrayObject(['c' => 1, 'a' => 2, 'b' => 3]);
        $sorted = $arr->sortByKey();
        $keys = array_keys($sorted->getRawData());
        $this->assertEquals(['a', 'b', 'c'], $keys);
    }

    public function testSortByNaturalOrderAlgorithm(): void
    {
        $arr = new ArrayObject(['img2', 'img1', 'img10']);
        $sorted = $arr->sortByNaturalOrderAlgorithm();
        $data = array_values($sorted->getRawData());
        $this->assertEquals('img1', $data[0]);
        $this->assertEquals('img2', $data[1]);
        $this->assertEquals('img10', $data[2]);
    }
}
