<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Modulus;
use PHPUnit\Framework\TestCase;

class ModulusTest extends TestCase
{
    public function testAdd(): void
    {
        $this->assertEquals('2', Modulus::add('5', '7', '10'));
        $this->assertEquals('0', Modulus::add('5', '5', '10'));
    }

    public function testSubtract(): void
    {
        $this->assertEquals('8', Modulus::subtract('5', '7', '10'));
        $this->assertEquals('0', Modulus::subtract('5', '5', '10'));
    }

    public function testMultiply(): void
    {
        $this->assertEquals('5', Modulus::multiply('5', '7', '10'));
        $this->assertEquals('6', Modulus::multiply('4', '4', '10'));
    }

    public function testDouble(): void
    {
        $this->assertEquals('4', Modulus::double('7', '10'));
        $this->assertEquals('0', Modulus::double('5', '10'));
    }

    public function testSquare(): void
    {
        $this->assertEquals('9', Modulus::square('7', '10'));
        $this->assertEquals('6', Modulus::square('4', '10'));
    }
}
