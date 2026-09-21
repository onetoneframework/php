<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Math\Basic;
use PHPUnit\Framework\TestCase;

class MathTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testMath(): void
	{
		$this->assertEquals(0.007416666029069652, Basic::getCosineDistance([1,2,3], [4,6,8]));
		$this->assertEquals(12.0, Basic::getManhattanDistance([1,2,3], [4,6,8]));
		$this->assertEquals(7.0710678118654755, Basic::getEuclideanDistance([1,2,3], [4,6,8]));
	}
}
