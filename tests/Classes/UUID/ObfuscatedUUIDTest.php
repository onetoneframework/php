<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\UUID;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\UUID\ObfuscatedUUID;
use PHPUnit\Framework\TestCase;

class ObfuscatedUUIDTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testObfuscatedUUID(): void
	{
		ObfuscatedUUID::setSalt('test-salt');
		$encode = ObfuscatedUUID::encode('123e4567-e89b-12d3-a456-426614174000');
		$decode = ObfuscatedUUID::decode($encode);
		$this->assertEquals('123e4567-e89b-12d3-a456-426614174000', $decode);
	}
}
