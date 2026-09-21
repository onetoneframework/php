<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Distance;
use PHPUnit\Framework\TestCase;

class DistanceTest extends TestCase
{
    public function testEuclidean(): void
    {
        $this->assertEquals(5.0, Distance::euclidean([0, 0], [3, 4]));
        $this->assertEquals(3.0, Distance::euclidean([1, 2, 0], [1, 2, 3]));
    }

    public function testHebyshev(): void
    {
        // Also known as Chebyshev distance, max(|x1 - x2|, |y1 - y2|)
        $this->assertEquals(4.0, Distance::hebyshev([0, 0], [3, 4]));
        $this->assertEquals(3.0, Distance::hebyshev([1, 2, 0], [1, 2, 3]));
    }

    public function testManhattan(): void
    {
        // sum(|x1 - x2|)
        $this->assertEquals(7.0, Distance::manhattan([0, 0], [3, 4]));
        $this->assertEquals(3.0, Distance::manhattan([1, 2, 0], [1, 2, 3]));
    }
}
