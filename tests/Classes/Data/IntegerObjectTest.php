<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\IntegerObject;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class IntegerObjectTest extends TestCase
{
    public function testBasicValueAccessAndDivisorCheck(): void
    {
        $number = new IntegerObject(12);

        $this->assertSame(12, $number->toInteger());
        $this->assertSame('12', (string) $number);
        $this->assertTrue($number->isDivisor(3));
        $this->assertFalse($number->isDivisor(5));
        $this->assertSame(1, $number->length());
    }

    public function testStaticNumericAndMathHelpers(): void
    {
        $this->assertTrue(IntegerObject::isNumeric('123'));
        $this->assertFalse(IntegerObject::isNumeric('abc'));
        $this->assertTrue(IntegerObject::hasZeroByte(0x00112233));
        $this->assertFalse(IntegerObject::hasZeroByte(0x11223344));
        $this->assertSame(2.35, IntegerObject::roundUp(2.341, 2));
        $this->assertSame(7, IntegerObject::findGreatestCommonDivisor(21, 14));
        $this->assertSame(42, IntegerObject::findLeastCommonMultiple(21, 14));
    }

    public function testDivisorMultipleAndStatisticsHelpers(): void
    {
        $this->assertSame([1, 2, 3, 6], IntegerObject::findDivisors(6));
        $this->assertSame([1, 2, 4], array_values(IntegerObject::findCommonDivisors(4, 8)));
        $this->assertSame([6, 12, 18], array_values(IntegerObject::findCommonMultiples(2, 3, 10)));
        $this->assertSame(2.0, IntegerObject::populationVariance([1, 2, 3, 4, 5]));
        $this->assertSame(2.5, IntegerObject::sampleVariance([1, 2, 3, 4, 5]));
    }

    public function testCombinatoricsAndProbabilityValidation(): void
    {
        $this->assertSame(20, IntegerObject::permutation(5, 2));
        $this->assertSame(10, IntegerObject::combination(5, 2));
        $this->assertSame(0.25, IntegerObject::probability(1, 4));
        $this->assertSame(0.5, IntegerObject::conditionalProbability(2, 4));

        $this->expectException(InvalidArgumentException::class);
        IntegerObject::combination(1, 2);
    }
}
