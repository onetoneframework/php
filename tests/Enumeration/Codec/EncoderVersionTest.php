<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Codec;

use Clover\Enumeration\Codec\DivXVersion;
use Clover\Enumeration\Codec\XviDVersion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EncoderVersionTest extends TestCase
{
	#[DataProvider('divXProvider')]
	public function testEveryDivXBuildHasHumanReadableLabel(DivXVersion $version): void
	{
		$this->assertNotSame('', $version->label());
		$this->assertSame($version, DivXVersion::from($version->value));
	}

	#[DataProvider('xviDProvider')]
	public function testEveryXviDBuildHasHumanReadableLabel(XviDVersion $version): void
	{
		$this->assertNotSame('', $version->label());
		$this->assertSame($version, XviDVersion::from($version->value));
	}

	public static function divXProvider(): array
	{
		$result = [];

		foreach (DivXVersion::cases() as $version) {
			$result[$version->name] = [$version];
		}

		return $result;
	}

	public static function xviDProvider(): array
	{
		$result = [];

		foreach (XviDVersion::cases() as $version) {
			$result[$version->name] = [$version];
		}

		return $result;
	}
}
