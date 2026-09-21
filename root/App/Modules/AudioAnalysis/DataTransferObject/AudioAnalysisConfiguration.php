<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\DataTransferObject;

use InvalidArgumentException;

use function filter_var;
use function in_array;
use function is_string;
use function ltrim;
use function parse_url;
use function rtrim;
use function strtolower;

final class AudioAnalysisConfiguration
{
	public const DEFAULT_BASE_URL = 'http://acoustic-analysis:8000';
	public const DEFAULT_CONNECTION_TIMEOUT_SECONDS = 3;
	public const DEFAULT_REQUEST_TIMEOUT_SECONDS = 900;

	public function __construct(
		private string $baseUrl,
		private int $connectionTimeoutSeconds,
		private int $requestTimeoutSeconds
	) {
		$this->baseUrl = rtrim($this->baseUrl, '/');

		if ($this->baseUrl === '' || filter_var($this->baseUrl, FILTER_VALIDATE_URL) === false) {
			throw new InvalidArgumentException('The acoustic analysis base URL is invalid.');
		}

		$scheme = parse_url($this->baseUrl, PHP_URL_SCHEME);
		if (!is_string($scheme) || !in_array(strtolower($scheme), ['http', 'https'], true)) {
			throw new InvalidArgumentException('The acoustic analysis base URL must use HTTP or HTTPS.');
		}

		if ($this->connectionTimeoutSeconds <= 0 || $this->requestTimeoutSeconds <= 0) {
			throw new InvalidArgumentException('Acoustic analysis timeouts must be positive integers.');
		}

		if ($this->requestTimeoutSeconds > self::DEFAULT_REQUEST_TIMEOUT_SECONDS) {
			throw new InvalidArgumentException('The acoustic analysis request timeout exceeds the public boundary.');
		}
	}

	public function getBaseUrl(): string
	{
		return $this->baseUrl;
	}

	public function getConnectionTimeoutSeconds(): int
	{
		return $this->connectionTimeoutSeconds;
	}

	public function getRequestTimeoutSeconds(): int
	{
		return $this->requestTimeoutSeconds;
	}

	public function createEndpoint(string $path): string
	{
		return $this->baseUrl . '/' . ltrim($path, '/');
	}
}
