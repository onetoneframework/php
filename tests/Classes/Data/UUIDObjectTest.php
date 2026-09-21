<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\UUIDObject;
use PHPUnit\Framework\TestCase;

class UUIDObjectTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testFailure(): void
	{
		$string = UUIDObject::toURLSafeUuidUnlimited('0105OPPeQNdtOg');
        $this->assertEquals('0105OPPeQNdtOg',  UUIDObject::fromURLSafeUuidUnlimited($string));
	}
}
