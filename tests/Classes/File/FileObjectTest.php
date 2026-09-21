<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\File;

use Clover\Classes\File\FileObject;
use Clover\Exception\FileHandler\FileNotFoundException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FileObjectTest extends TestCase
{
	private const DIRECTORY_MODE_READ_ONLY = 0555;
	private const DIRECTORY_MODE_WRITABLE = 0755;
	private const RANDOM_SUFFIX_BYTES = 8;
	private const TEST_CONTENT = 'read-only source content';

	private string $temporaryDirectory;
	private string $existingFilePath;
	private string $newFilePath;

	protected function setUp(): void
	{
		$this->temporaryDirectory = sys_get_temp_dir()
			. DIRECTORY_SEPARATOR
			. 'clover_file_object_'
			. bin2hex(random_bytes(self::RANDOM_SUFFIX_BYTES));
		$this->existingFilePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'existing.txt';
		$this->newFilePath = $this->temporaryDirectory . DIRECTORY_SEPARATOR . 'new.txt';

		$this->assertTrue(mkdir($this->temporaryDirectory, self::DIRECTORY_MODE_WRITABLE, true));
		$this->assertNotFalse(file_put_contents($this->existingFilePath, self::TEST_CONTENT));
	}

	protected function tearDown(): void
	{
		if (is_dir($this->temporaryDirectory)) {
			if (!chmod($this->temporaryDirectory, self::DIRECTORY_MODE_WRITABLE)) {
				throw new RuntimeException('Unable to restore temporary directory permissions.');
			}
		}

		foreach ([$this->existingFilePath, $this->newFilePath] as $filePath) {
			if (is_file($filePath) && !unlink($filePath)) {
				throw new RuntimeException('Unable to remove a temporary file.');
			}
		}

		if (is_dir($this->temporaryDirectory) && !rmdir($this->temporaryDirectory)) {
			throw new RuntimeException('Unable to remove the temporary directory.');
		}
	}

	#[DataProvider('modeCapabilitiesProvider')]
	public function testReportsModeCapabilities(
		string $fileMode,
		bool $allowsCreation,
		bool $allowsReading
	): void {
		$fileObject = new FileObject($this->existingFilePath, false, $fileMode);

		$this->assertSame($allowsCreation, $fileObject->hasMode());
		$this->assertSame($allowsReading, $fileObject->isReadable());
	}

	/**
	 * @return iterable<string, array{string, bool, bool}>
	 */
	public static function modeCapabilitiesProvider(): iterable
	{
		yield 'read only' => ['r', false, true];
		yield 'read binary' => ['rb', false, true];
		yield 'read and write' => ['r+', false, true];
		yield 'read and write binary' => ['r+b', false, true];
		yield 'write only' => ['w', true, false];
		yield 'write and read' => ['w+', true, true];
		yield 'append only' => ['a', true, false];
		yield 'append and read binary' => ['ab+', true, true];
		yield 'exclusive create' => ['x', true, false];
		yield 'exclusive create and read' => ['x+', true, true];
		yield 'create without truncation' => ['c', true, false];
		yield 'create without truncation and read' => ['c+', true, true];
	}

	#[DataProvider('readOnlyModeProvider')]
	public function testReadsFromReadOnlyDirectory(string $fileMode): void
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			$this->markTestSkipped('Directory permission semantics require a POSIX filesystem.');
		}

		$this->assertTrue(chmod($this->temporaryDirectory, self::DIRECTORY_MODE_READ_ONLY));
		clearstatcache(true, $this->temporaryDirectory);
		if (is_writable($this->temporaryDirectory)) {
			$this->markTestSkipped('The current user bypasses directory permission bits.');
		}

		$fileObject = new FileObject($this->existingFilePath, false, $fileMode);
		$fileObject->startHandle();
		$this->assertTrue($fileObject->successToStartHandle());
		$fileObject->readAllContent();
		$this->assertSame(self::TEST_CONTENT, $fileObject->getReadedContent());
		$fileObject->closeFileHandle();
	}

	/**
	 * @return iterable<string, array{string}>
	 */
	public static function readOnlyModeProvider(): iterable
	{
		yield 'text mode' => ['r'];
		yield 'binary mode' => ['rb'];
	}

	#[DataProvider('writeModeProvider')]
	public function testRejectsWriteModesInReadOnlyDirectory(string $fileMode, bool $requiresExistingFile): void
	{
		if (DIRECTORY_SEPARATOR === '\\') {
			$this->markTestSkipped('Directory permission semantics require a POSIX filesystem.');
		}

		$this->assertTrue(chmod($this->temporaryDirectory, self::DIRECTORY_MODE_READ_ONLY));
		clearstatcache(true, $this->temporaryDirectory);
		if (is_writable($this->temporaryDirectory)) {
			$this->markTestSkipped('The current user bypasses directory permission bits.');
		}

		$filePath = $requiresExistingFile ? $this->existingFilePath : $this->newFilePath;
		$fileObject = new FileObject($filePath, false, $fileMode);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Directory is not writable');
		$fileObject->startHandle();
	}

	/**
	 * @return iterable<string, array{string, bool}>
	 */
	public static function writeModeProvider(): iterable
	{
		yield 'read and write' => ['r+', true];
		yield 'truncate and write' => ['w', false];
		yield 'append and write' => ['a', false];
		yield 'exclusive create' => ['x', false];
		yield 'create without truncation' => ['c', false];
	}

	public function testRejectsMissingFileInReadOnlyMode(): void
	{
		$fileObject = new FileObject($this->newFilePath, false, 'r');

		$this->expectException(FileNotFoundException::class);
		$fileObject->startHandle();
	}
}
