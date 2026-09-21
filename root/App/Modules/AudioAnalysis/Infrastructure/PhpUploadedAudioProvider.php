<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Infrastructure;

use App\Modules\AudioAnalysis\Contract\UploadedAudioProviderInterface;
use App\Modules\AudioAnalysis\Contract\UploadedFileInspectorInterface;
use App\Modules\AudioAnalysis\DataTransferObject\UploadedAudio;
use App\Modules\AudioAnalysis\Exception\AudioUploadException;
use Clover\Enumeration\HTTPStatusCode;
use InvalidArgumentException;

use function in_array;
use function strtolower;

final class PhpUploadedAudioProvider implements UploadedAudioProviderInterface
{
	public const DEFAULT_MAXIMUM_SIZE_BYTES = 1_073_741_824;

	private const WAV_EXTENSION = 'wav';

	private const WAV_MIME_TYPES = [
		'audio/wav',
		'audio/x-wav',
		'audio/wave',
		'audio/vnd.wave',
	];

	public function __construct(
		private UploadedFileInspectorInterface $uploadedFileInspector,
		private int $maximumSizeBytes
	) {
		if ($this->maximumSizeBytes <= 0) {
			throw new InvalidArgumentException('The maximum audio upload size must be positive.');
		}

		if ($this->maximumSizeBytes > self::DEFAULT_MAXIMUM_SIZE_BYTES) {
			throw new InvalidArgumentException('The maximum audio upload size exceeds the public boundary.');
		}
	}

	public function get(string $fieldName): UploadedAudio
	{
		if (!$this->uploadedFileInspector->has($fieldName)) {
			throw new AudioUploadException(
				'The requested audio upload is missing.',
				HTTPStatusCode::UNPROCESSABLE_ENTITY
			);
		}

		$errorCode = $this->uploadedFileInspector->getErrorCode($fieldName);
		if (in_array($errorCode, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
			throw new AudioUploadException(
				'The audio upload exceeds the configured size limit.',
				HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE
			);
		}

		if ($errorCode !== UPLOAD_ERR_OK || !$this->uploadedFileInspector->isTemporaryUploaded($fieldName)) {
			throw new AudioUploadException(
				'The audio upload could not be validated.',
				HTTPStatusCode::UNPROCESSABLE_ENTITY
			);
		}

		$sizeBytes = $this->uploadedFileInspector->getSizeBytes($fieldName);
		if ($sizeBytes <= 0) {
			throw new AudioUploadException(
				'The audio upload is empty.',
				HTTPStatusCode::UNPROCESSABLE_ENTITY
			);
		}

		if ($sizeBytes > $this->maximumSizeBytes) {
			throw new AudioUploadException(
				'The audio upload exceeds the configured size limit.',
				HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE
			);
		}

		$extension = strtolower($this->uploadedFileInspector->getExtension($fieldName));
		$mimeType = strtolower($this->uploadedFileInspector->getMimeType($fieldName));
		if ($extension !== self::WAV_EXTENSION || !in_array($mimeType, self::WAV_MIME_TYPES, true)) {
			throw new AudioUploadException(
				'Only WAV audio uploads are supported.',
				HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE
			);
		}

		$temporaryPath = $this->uploadedFileInspector->getTemporaryPath($fieldName);
		$originalFileName = $this->uploadedFileInspector->getOriginalFileName($fieldName);
		if ($temporaryPath === '' || $originalFileName === '') {
			throw new AudioUploadException(
				'The audio upload metadata is invalid.',
				HTTPStatusCode::UNPROCESSABLE_ENTITY
			);
		}

		return new UploadedAudio($temporaryPath, $originalFileName, $mimeType);
	}
}
