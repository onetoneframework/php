<?php

declare(strict_types=1);

namespace Clover\Tests\Validation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Validation\FileValidation;
use PHPUnit\Framework\TestCase;

class FileValidationTest extends TestCase
{
    public function testIsReadableReturnsTrueForShortPath(): void
    {
        $this->assertTrue(FileValidation::isReadable('short.txt'));
    }

    public function testIsNotReadableNegatesIsReadable(): void
    {
        $this->assertFalse(FileValidation::isNotReadable('short.txt'));
    }

    public function testIsHTTPProtocolWithHttp(): void
    {
        $this->assertTrue(FileValidation::isHTTPProtocol('http://example.com'));
    }

    public function testIsHTTPProtocolWithHttps(): void
    {
        $this->assertTrue(FileValidation::isHTTPProtocol('https://example.com'));
    }

    public function testIsHTTPProtocolWithNonHttp(): void
    {
        $this->assertFalse(FileValidation::isHTTPProtocol('ftp://example.com'));
        $this->assertFalse(FileValidation::isHTTPProtocol('/local/path'));
    }

    public function testIsNotHTTPProtocol(): void
    {
        $this->assertFalse(FileValidation::isNotHTTPProtocol('http://example.com'));
        $this->assertTrue(FileValidation::isNotHTTPProtocol('file:///tmp'));
    }

    public function testIsPharProtocol(): void
    {
        $this->assertTrue(FileValidation::isPharProtocol('phar:///path/to/archive.phar'));
        $this->assertFalse(FileValidation::isPharProtocol('http://example.com'));
    }

    public function testIsNotPharProtocol(): void
    {
        $this->assertFalse(FileValidation::isNotPharProtocol('phar:///x'));
        $this->assertTrue(FileValidation::isNotPharProtocol('file:///x'));
    }

	public function testIsFileProtocolRecognizesLocalFileUrls(): void
	{
		$this->assertTrue(FileValidation::isFileProtocol('file:///tmp/example.txt'));
		$this->assertTrue(FileValidation::isFileProtocol('FILE:///C:/example.txt'));
	}

	public function testIsFileProtocolRejectsOtherSchemesAndPlainPaths(): void
	{
		$this->assertFalse(FileValidation::isFileProtocol('https://example.com/file.txt'));
		$this->assertFalse(FileValidation::isFileProtocol('/tmp/example.txt'));
	}

	public function testProtocolChecksAreCaseInsensitive(): void
	{
		$this->assertTrue(FileValidation::isHTTPProtocol('HTTPS://example.com'));
		$this->assertTrue(FileValidation::isPharProtocol('PHAR:///archive.phar'));
	}

    public function testHasSubfolderSyntax(): void
    {
        $this->assertTrue(FileValidation::hasSubfolderSyntax('../'));
    }

    public function testHasNotSubfolderSyntax(): void
    {
        $this->assertFalse(FileValidation::hasNotSubfolderSyntax('../'));
    }

    public function testHasExtention(): void
    {
        $this->assertTrue(FileValidation::hasExtention('file.txt'));
        $this->assertTrue(FileValidation::hasExtention('archive.zip'));
    }

    public function testHasNotExtention(): void
    {
        $this->assertTrue(FileValidation::hasNotExtention('noext'));
        $this->assertFalse(FileValidation::hasNotExtention('file.txt'));
    }

	public function testExtensionValidationAcceptsUpToFiveAlphanumericCharacters(): void
	{
		$this->assertTrue(FileValidation::hasExtention('archive.TAR12'));
		$this->assertFalse(FileValidation::hasExtention('archive.longer'));
	}
}
