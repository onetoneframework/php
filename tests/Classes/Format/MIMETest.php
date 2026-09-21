<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Format;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Format\MultiPurposeInternetMailExtensions as MIME;

class MIMETest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testMime(): void
	{
		$this->assertContains("application/vnd.microsoft.portable-executable", MIME::getContentTypeFromExtension('exe', true));
		$this->assertContains("application/zip", MIME::getContentTypeFromExtension('zip', true));
		$this->assertContains("text/markdown", MIME::getContentTypeFromExtension('markdown', true));
	}
}
