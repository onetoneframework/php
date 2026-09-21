<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\DataTransferObject;

final class UploadedAudio
{
	public function __construct(
		private string $temporaryPath,
		private string $originalFileName,
		private string $mimeType
	) {
	}

	public function getTemporaryPath(): string
	{
		return $this->temporaryPath;
	}

	public function getOriginalFileName(): string
	{
		return $this->originalFileName;
	}

	public function getMimeType(): string
	{
		return $this->mimeType;
	}
}
