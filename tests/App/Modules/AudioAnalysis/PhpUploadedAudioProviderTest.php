<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\Contract\UploadedFileInspectorInterface;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use App\Modules\AudioAnalysis\Infrastructure\PhpUploadedAudioProvider;
use Clover\Enumeration\HTTPStatusCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PhpUploadedAudioProviderTest extends TestCase
{
	private const TEST_MAXIMUM_SIZE_BYTES = 1024;

	private UploadedFileInspectorFake $uploadedFileInspector;
	private PhpUploadedAudioProvider $uploadedAudioProvider;

	protected function setUp(): void
	{
		$this->uploadedFileInspector = new UploadedFileInspectorFake();
		$this->uploadedAudioProvider = new PhpUploadedAudioProvider(
			$this->uploadedFileInspector,
			self::TEST_MAXIMUM_SIZE_BYTES
		);
	}

	public function testProvidesValidatedWavUpload(): void
	{
		$audio = $this->uploadedAudioProvider->get('audio');

		$this->assertSame('temporary-audio', $audio->getTemporaryPath());
		$this->assertSame('sample.wav', $audio->getOriginalFileName());
		$this->assertSame('audio/wav', $audio->getMimeType());
	}

	public function testRejectsMissingUploadWithUnprocessableStatus(): void
	{
		$this->uploadedFileInspector->hasUpload = false;

		try {
			$this->uploadedAudioProvider->get('audio');
			$this->fail('A missing upload must be rejected.');
		} catch (AudioUploadException $exception) {
			$this->assertSame(HTTPStatusCode::UNPROCESSABLE_ENTITY, $exception->getStatusCode());
		}
	}

	public function testMapsPhpSizeErrorToRequestEntityTooLarge(): void
	{
		$this->uploadedFileInspector->errorCode = UPLOAD_ERR_INI_SIZE;

		try {
			$this->uploadedAudioProvider->get('audio');
			$this->fail('An upload rejected by PHP for size must be rejected.');
		} catch (AudioUploadException $exception) {
			$this->assertSame(HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE, $exception->getStatusCode());
		}
	}

	public function testRejectsUploadLargerThanConfiguredLimit(): void
	{
		$this->uploadedFileInspector->sizeBytes = self::TEST_MAXIMUM_SIZE_BYTES + 1;

		try {
			$this->uploadedAudioProvider->get('audio');
			$this->fail('An oversized upload must be rejected.');
		} catch (AudioUploadException $exception) {
			$this->assertSame(HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE, $exception->getStatusCode());
		}
	}

	public function testRejectsNonWavMediaType(): void
	{
		$this->uploadedFileInspector->mimeType = 'audio/mpeg';

		try {
			$this->uploadedAudioProvider->get('audio');
			$this->fail('An unsupported audio upload must be rejected.');
		} catch (AudioUploadException $exception) {
			$this->assertSame(HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE, $exception->getStatusCode());
		}
	}

	public function testRejectsInvalidTemporaryUpload(): void
	{
		$this->uploadedFileInspector->temporaryUploaded = false;

		try {
			$this->uploadedAudioProvider->get('audio');
			$this->fail('A non-HTTP upload must be rejected.');
		} catch (AudioUploadException $exception) {
			$this->assertSame(HTTPStatusCode::UNPROCESSABLE_ENTITY, $exception->getStatusCode());
		}
	}

	public function testRejectsInvalidMaximumSizeConfiguration(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new PhpUploadedAudioProvider($this->uploadedFileInspector, 0);
	}

	public function testRejectsMaximumSizeAbovePublicBoundary(): void
	{
		$this->expectException(InvalidArgumentException::class);

		new PhpUploadedAudioProvider(
			$this->uploadedFileInspector,
			PhpUploadedAudioProvider::DEFAULT_MAXIMUM_SIZE_BYTES + 1
		);
	}
}

final class UploadedFileInspectorFake implements UploadedFileInspectorInterface
{
	public bool $hasUpload = true;
	public int $errorCode = UPLOAD_ERR_OK;
	public bool $temporaryUploaded = true;
	public string $temporaryPath = 'temporary-audio';
	public string $originalFileName = 'sample.wav';
	public string $mimeType = 'audio/wav';
	public int $sizeBytes = 512;
	public string $extension = 'wav';

	public function has(string $fieldName): bool
	{
		return $this->hasUpload;
	}

	public function getErrorCode(string $fieldName): int
	{
		return $this->errorCode;
	}

	public function isTemporaryUploaded(string $fieldName): bool
	{
		return $this->temporaryUploaded;
	}

	public function getTemporaryPath(string $fieldName): string
	{
		return $this->temporaryPath;
	}

	public function getOriginalFileName(string $fieldName): string
	{
		return $this->originalFileName;
	}

	public function getMimeType(string $fieldName): string
	{
		return $this->mimeType;
	}

	public function getSizeBytes(string $fieldName): int
	{
		return $this->sizeBytes;
	}

	public function getExtension(string $fieldName): string
	{
		return $this->extension;
	}
}
