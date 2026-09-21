<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\Exception;

use RuntimeException;

final class AudioUploadException extends RuntimeException
{
	public function __construct(string $message, private int $statusCode)
	{
		parent::__construct($message);
	}

	public function getStatusCode(): int
	{
		return $this->statusCode;
	}
}
