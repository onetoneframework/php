<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration;

use Clover\Enumeration\ApplicationMode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ApplicationModeTest extends TestCase
{
	public function testModePredicatesIdentifyOnlyTheirOwnMode(): void
	{
		$this->assertTrue(ApplicationMode::DEVELOPMENT->isDevelopment());
		$this->assertFalse(ApplicationMode::DEVELOPMENT->isTest());
		$this->assertFalse(ApplicationMode::DEVELOPMENT->isProduction());

		$this->assertFalse(ApplicationMode::TEST->isDevelopment());
		$this->assertTrue(ApplicationMode::TEST->isTest());
		$this->assertFalse(ApplicationMode::TEST->isProduction());

		$this->assertFalse(ApplicationMode::PRODUCTION->isDevelopment());
		$this->assertFalse(ApplicationMode::PRODUCTION->isTest());
		$this->assertTrue(ApplicationMode::PRODUCTION->isProduction());
	}

	public function testFromStringAcceptsCanonicalNames(): void
	{
		$this->assertSame(ApplicationMode::DEVELOPMENT, ApplicationMode::fromString('development'));
		$this->assertSame(ApplicationMode::TEST, ApplicationMode::fromString('test'));
		$this->assertSame(ApplicationMode::PRODUCTION, ApplicationMode::fromString('production'));
	}

	public function testFromStringAcceptsShortAliases(): void
	{
		$this->assertSame(ApplicationMode::DEVELOPMENT, ApplicationMode::fromString('dev'));
		$this->assertSame(ApplicationMode::PRODUCTION, ApplicationMode::fromString('prod'));
	}

	public function testFromStringIsCaseInsensitive(): void
	{
		$this->assertSame(ApplicationMode::DEVELOPMENT, ApplicationMode::fromString('DEVELOPMENT'));
		$this->assertSame(ApplicationMode::TEST, ApplicationMode::fromString('TEST'));
		$this->assertSame(ApplicationMode::PRODUCTION, ApplicationMode::fromString('PROD'));
	}

	public function testFromStringRejectsUnknownMode(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown application mode: staging');

		ApplicationMode::fromString('staging');
	}
}
