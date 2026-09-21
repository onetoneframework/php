<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Contract;

interface UploadedFileInspectorInterface
{
	public function has(string $fieldName): bool;

	public function getErrorCode(string $fieldName): int;

	public function isTemporaryUploaded(string $fieldName): bool;

	public function getTemporaryPath(string $fieldName): string;

	public function getOriginalFileName(string $fieldName): string;

	public function getMimeType(string $fieldName): string;

	public function getSizeBytes(string $fieldName): int;

	public function getExtension(string $fieldName): string;
}
