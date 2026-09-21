<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Hash;

use Clover\Classes\Hash\Handler;
use Clover\Classes\Hash\MD2;
use Clover\Classes\Hash\MD5;
use Clover\Classes\Hash\SHA256;
use Clover\Classes\Hash\SHA384;
use Clover\Classes\Hash\SHA512;
use PHPUnit\Framework\TestCase;

final class HandlerTest extends TestCase
{
	public function testHandlerDefaultsToSha256AndMatchesKnownDigest(): void
	{
		$handler = new Handler();

		self::assertSame('sha256', $handler->algorithm);
		self::assertSame(
			'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
			$handler->hash('abc')
		);
	}

	public function testNamedHashHandlersMatchKnownDigests(): void
	{
		self::assertSame('da853b0d3f88d99b30283a69e6ded6bb', (new MD2())->hash('abc'));
		self::assertSame('900150983cd24fb0d6963f7d28e17f72', (new MD5())->hash('abc'));
		self::assertSame(
			'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
			(new SHA256())->hash('abc')
		);
		self::assertSame(
			'cb00753f45a35e8bb5a03d699ac65007272c32ab0eded1631a8b605a43ff5bed8086072ba1e7cc2358baeca134c825a7',
			(new SHA384())->hash('abc')
		);
	}

	public function testHandlerCanReturnRawBinaryDigest(): void
	{
		$handler = new SHA256();

		self::assertSame(
			'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad',
			bin2hex($handler->hash('abc', true))
		);
	}

	public function testSha512ReturnsKnownRawAndBase64Digest(): void
	{
		$expectedHex = 'ddaf35a193617abacc417349ae204131'
			. '12e6fa4e89a97ea20a9eeee64b55d39a'
			. '2192992a274fc1a836ba3c23a3feebbd'
			. '454d4423643ce80e2a9ac94fa54ca49f';
		$expectedRaw = hex2bin($expectedHex);
		self::assertNotFalse($expectedRaw);
		$sha512 = new SHA512();

		self::assertSame($expectedRaw, $sha512->encrypt('abc', false));
		self::assertSame(base64_encode($expectedRaw), $sha512->encrypt('abc'));
	}

	public function testGetAlgorithmsContainsConfiguredAlgorithm(): void
	{
		$handler = new SHA384();

		self::assertContains($handler->algorithm, $handler->getAlgorithms());
	}
}
