<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Security\Auth;

use Clover\Classes\Security\Auth\TOTPGenerator;
use PHPUnit\Framework\TestCase;

final class TOTPGeneratorTest extends TestCase
{
	private const RFC_SHA1_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

	public function testGenerateCodeMatchesRfc6238Sha1Vector(): void
	{
		$generator = (new TOTPGenerator())->setDigits(8);

		self::assertSame('94287082', $generator->generateCode(self::RFC_SHA1_SECRET, 59));
	}

	public function testGenerateCodeAcceptsUnixEpochTimestamp(): void
	{
		$generator = new TOTPGenerator();

		self::assertSame('755224', $generator->generateCode(self::RFC_SHA1_SECRET, 0));
	}

	public function testGeneratedSecretUsesConfiguredLengthAndBase32Alphabet(): void
	{
		$generator = (new TOTPGenerator())->setSecretLength(48);

		$secret = $generator->generateSecret();

		self::assertSame(48, strlen($secret));
		self::assertMatchesRegularExpression('/\A[A-Z2-7]{48}\z/', $secret);
	}

	public function testVerifyAcceptsCodeFromCurrentTimeWindow(): void
	{
		$generator = new TOTPGenerator();
		$code = $generator->generateCode(self::RFC_SHA1_SECRET, time());

		self::assertTrue($generator->verify($code, self::RFC_SHA1_SECRET));
	}

	public function testQrCodeUrlEncodesIdentityAndConfiguration(): void
	{
		$generator = (new TOTPGenerator())
			->setAlgorithm('sha256')
			->setDigits(8)
			->setTimeStep(60);

		$url = $generator->getQRCodeUrl(
			'user+test@example.com',
			self::RFC_SHA1_SECRET,
			'Clover Framework'
		);

		self::assertSame(
			'otpauth://totp/user%2Btest%40example.com?secret=' . self::RFC_SHA1_SECRET
			. '&issuer=Clover+Framework&algorithm=sha256&digits=8&period=60',
			$url
		);
	}
}
