<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Hash;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Hash\Seeder;
use PHPUnit\Framework\TestCase;

class SeedTest extends TestCase
{
    public function setUp(): void
    {
    }

    public function testSeed(): void
    {
        $this->assertEquals(50, Seeder::splitmix32('4294967296')());
        $this->assertEquals(3524489345, Seeder::mulberry32('4294967296')());
        $this->assertEquals(269516800, Seeder::xorShift32('4294967296')());
        $this->assertEquals(4648317627024801792, Seeder::xorShift64('4294967296')());
        $this->assertEquals(1013904223, Seeder::lcg32('4294967296')());
        $this->assertEquals(2357136044, Seeder::mt19937('4294967296')());
        $this->assertEquals(32832, Seeder::xorshift128plus('4294967296', '4294967296')());
        $this->assertEquals(2864236472, Seeder::kiss32('4294967296')());
        $this->assertEquals(3771228272, Seeder::well512(4294967296)());
        $this->assertEquals(2864236472, Seeder::kiss32('4294967296')());
        $this->assertEquals(7783477151044991386, Seeder::aesCtrRng('4294967296123456', '4294967296123456')());
        $this->assertEquals(1750100827, Seeder::tinymt32('4294967296')());
        $this->assertEquals(770165546, Seeder::well512a(4294967296)());
        $this->assertEquals(337713548, Seeder::mwc256(4294967296)());
        $this->assertEquals(7751050468622527276, Seeder::hmac_drbg_sha256(4294967296)());
        $this->assertEquals(32, Seeder::pcg32(4294967296)());
        $this->assertEquals(2357136044, Seeder::sfmt19937(4294967296)());
        $this->assertEquals(-7308623449532074555, Seeder::chacha20_rng('42949672967842949672967812345672', '429496729678')());
    }

	public function testPcg32ProducesStableSequenceWithoutIntegerOverflow(): void
	{
		$generator = Seeder::pcg32(4294967296, 0);

		$this->assertSame(32, $generator());
		$this->assertSame(2244564672, $generator());
		$this->assertSame(3095889617, $generator());
		$this->assertSame(1287188843, $generator());
		$this->assertSame(1975034511, $generator());
	}
}
