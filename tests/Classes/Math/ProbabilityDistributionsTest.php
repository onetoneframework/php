<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\ProbabilityDistributions;
use PHPUnit\Framework\TestCase;

class ProbabilityDistributionsTest extends TestCase
{
    public function testBernoulliProbabilitySuccess(): void
    {
        $this->assertEquals(0.8, ProbabilityDistributions::bernoulliProbability(1, 0.8));
    }

    public function testBernoulliProbabilityFailure(): void
    {
        $this->assertEqualsWithDelta(0.2, ProbabilityDistributions::bernoulliProbability(0, 0.8), 0.001);
        $this->assertEqualsWithDelta(0.7, ProbabilityDistributions::bernoulliProbability(0, 0.3), 0.0001);
    }

    public function testBernoulliProbabilityException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ProbabilityDistributions::bernoulliProbability(2, 0.5);
    }

    public function testNormalProbabilityDensity(): void
    {
        // Standard normal distribution, N(0, 1), at x = 0
        // PDF should be 1 / sqrt(2 * pi)
        $expected = 1 / sqrt(2 * M_PI);
        $this->assertEqualsWithDelta($expected, ProbabilityDistributions::normalProbabilityDensity(0.0, 0.0, 1.0), 0.000001);
    }
}
