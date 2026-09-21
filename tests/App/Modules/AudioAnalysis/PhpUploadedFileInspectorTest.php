<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\Infrastructure\PhpUploadedFileInspector;
use PHPUnit\Framework\TestCase;

final class PhpUploadedFileInspectorTest extends TestCase
{
	/** @var array<string, mixed> */
	private array $previousFiles = [];

	protected function setUp(): void
	{
		$this->previousFiles = $_FILES;
		$_FILES = [];
	}

	protected function tearDown(): void
	{
		$_FILES = $this->previousFiles;
	}

	public function testReturnsSafeDefaultsForMissingField(): void
	{
		$inspector = new PhpUploadedFileInspector();

		$this->assertFalse($inspector->has('audio'));
		$this->assertSame(UPLOAD_ERR_NO_FILE, $inspector->getErrorCode('audio'));
		$this->assertFalse($inspector->isTemporaryUploaded('audio'));
		$this->assertSame('', $inspector->getTemporaryPath('audio'));
		$this->assertSame('', $inspector->getOriginalFileName('audio'));
		$this->assertSame('', $inspector->getMimeType('audio'));
		$this->assertSame(0, $inspector->getSizeBytes('audio'));
		$this->assertSame('', $inspector->getExtension('audio'));
	}
}
