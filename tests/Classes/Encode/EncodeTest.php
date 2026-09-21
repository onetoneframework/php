<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Encode;

use Clover\Classes\Encode;
use PHPUnit\Framework\TestCase;

final class EncodeTest extends TestCase
{
	public function testDetectRecognizesUtf8Text(): void
	{
		$encode = new Encode();

		$this->assertSame('UTF-8', $encode->detect('Café 한글'));
	}

	public function testDetectRecognizesAsciiText(): void
	{
		$encode = new Encode();
		$detected = $encode->detect('plain ASCII text');

		$this->assertIsString($detected);
		$this->assertContains($detected, ['ASCII', 'UTF-8']);
	}
}
