<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Infrastructure;

use App\Modules\AudioAnalysis\Contract\UploadedFileInspectorInterface;
use Clover\Classes\Upload\Handler;

use function ctype_digit;
use function is_int;
use function is_string;

final class PhpUploadedFileInspector implements UploadedFileInspectorInterface
{
	public function has(string $fieldName): bool
	{
		return Handler::has($fieldName);
	}

	public function getErrorCode(string $fieldName): int
	{
		$errorCode = Handler::getFileError($fieldName);

		if (is_int($errorCode)) {
			return $errorCode;
		}

		if (is_string($errorCode) && ctype_digit($errorCode)) {
			return (int) $errorCode;
		}

		return UPLOAD_ERR_NO_FILE;
	}

	public function isTemporaryUploaded(string $fieldName): bool
	{
		return Handler::isTemporaryUploaded($fieldName);
	}

	public function getTemporaryPath(string $fieldName): string
	{
		$temporaryPath = Handler::getTemporaryName($fieldName);

		return is_string($temporaryPath) ? $temporaryPath : '';
	}

	public function getOriginalFileName(string $fieldName): string
	{
		return Handler::getSanitizedFileName($fieldName);
	}

	public function getMimeType(string $fieldName): string
	{
		$mimeType = Handler::getMimeType($fieldName);

		return is_string($mimeType) ? $mimeType : '';
	}

	public function getSizeBytes(string $fieldName): int
	{
		$sizeBytes = Handler::getFileSize($fieldName);

		if (is_int($sizeBytes)) {
			return $sizeBytes;
		}

		if (is_string($sizeBytes) && ctype_digit($sizeBytes)) {
			return (int) $sizeBytes;
		}

		return 0;
	}

	public function getExtension(string $fieldName): string
	{
		return Handler::getExtension($fieldName);
	}
}
