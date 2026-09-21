<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Plugins\API;

use Clover\Plugin\Stripe;
use PHPUnit\Framework\TestCase;

final class StripeTest extends TestCase
{
	public function testClassCanBeLoadedAndConstructed(): void
	{
		self::assertTrue(class_exists(Stripe::class));
		self::assertInstanceOf(Stripe::class, new Stripe('test-secret'));
	}
}
