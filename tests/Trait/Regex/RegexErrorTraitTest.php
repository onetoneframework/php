<?php

declare(strict_types=1);

namespace Clover\Tests\Trait\Regex;

use Clover\Traits\Regex\RegexError;
use PHPUnit\Framework\TestCase;

final class RegexErrorTraitTest extends TestCase
{
	public function testSuccessfulRegexReportsNoError(): void
	{
		preg_match('/alpha/', 'alpha');

		$this->assertSame(PREG_NO_ERROR, RegexErrorFixture::getErrorCode());
		$this->assertTrue(RegexErrorFixture::noError());
		$this->assertSame('No error', RegexErrorFixture::getErrorMessage());
	}

	public function testInvalidUtf8ReportsBadUtf8Error(): void
	{
		preg_match('//u', "\xFF");

		$this->assertSame(PREG_BAD_UTF8_ERROR, RegexErrorFixture::getErrorCode());
		$this->assertTrue(RegexErrorFixture::hasBadUTF8Error());
		$this->assertSame('Malformed UTF-8 characters, possibly incorrectly encoded', RegexErrorFixture::getErrorMessage());
	}
}

final class RegexErrorFixture
{
	use RegexError;
}
