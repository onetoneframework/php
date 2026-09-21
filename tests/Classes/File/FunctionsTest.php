<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\File;

use Clover\Classes\File\Functions;
use PHPUnit\Framework\TestCase;

final class FunctionsTest extends TestCase
{
	public function testInvalidPharFileReturnsFalseForNativeUnexpectedValueException(): void
	{
		if ((bool) ini_get('phar.readonly')) {
			self::markTestSkipped('This exception path requires phar.readonly=0.');
		}

		self::assertFalse(Functions::isValidPharFile(__FILE__));
	}
}
