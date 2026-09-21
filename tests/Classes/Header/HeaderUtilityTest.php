<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Header;

use Clover\Classes\Header;
use PHPUnit\Framework\TestCase;

final class HeaderUtilityTest extends TestCase
{
	public function testStatusMessagesCoverCommonSuccessRedirectAndErrorCodes(): void
	{
		$this->assertSame('OK', Header::getStatusMessageByCode('200'));
		$this->assertSame('Created', Header::getStatusMessageByCode('201'));
		$this->assertSame('Moved Permanently', Header::getStatusMessageByCode('301'));
		$this->assertSame('Not Found', Header::getStatusMessageByCode('404'));
		$this->assertSame('Service Unavailable', Header::getStatusMessageByCode('503'));
		$this->assertSame('', Header::getStatusMessageByCode('599'));
	}

	public function testFormatCookieHeaderEncodesValueAndIncludesAttributes(): void
	{
		$expires = 1_700_000_000;
		$header = Header::formatCookieHeader([
			'name' => 'session id',
			'value' => 'a+b c',
			'expire' => $expires,
			'path' => '/app',
			'domain' => 'example.test',
			'secure' => true,
			'httponly' => true,
		]);

		$this->assertSame(
			'session+id=a%2Bb+c; Expires=' . gmdate('D, d-M-Y H:i:s T', $expires)
			. '; Path=/app; Domain=example.test; Secure; HttpOnly',
			$header
		);
	}

	public function testFormatCookieHeaderOmitsUnusedAttributes(): void
	{
		$this->assertSame('token=value', Header::formatCookieHeader([
			'name' => 'token',
			'value' => 'value',
			'expire' => 0,
			'path' => '',
			'domain' => '',
			'secure' => false,
			'httponly' => false,
		]));
	}
}
