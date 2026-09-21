<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Debug;

use function date;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;
use function random_bytes;
use function rtrim;
use function round;
use function strtolower;
use function trim;
use const DATE_ATOM;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class ProfilerStorage
{
	private string $relativeDir;

	public function __construct(string $relativeDir = 'App/Cache/profiler')
	{
		$this->relativeDir = $relativeDir;
	}

	public function storeExceptionProfile(\Throwable $e, array $flow): ?string
	{
		$dir = $this->resolveStorageDir();
		if ($dir === null) {
			return null;
		}

		$startedAt = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(true));
		$endedAt = microtime(true);

		$payload = [
			'capturedAt' => date(DATE_ATOM),
			'durationMs' => round(($endedAt - $startedAt) * 1000, 3),
			'request' => $this->buildRequestMetadata(),
			'exception' => [
				'class' => $e::class,
				'message' => $e->getMessage(),
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'code' => $e->getCode(),
			],
			'flow' => $flow,
		];

		return $this->storePayload($dir, $payload);
	}

	public function storeRequestProfile(float $durationMs, array $flow): ?string
	{
		$dir = $this->resolveStorageDir();
		if ($dir === null) {
			return null;
		}

		$payload = [
			'capturedAt' => date(DATE_ATOM),
			'durationMs' => round($durationMs, 3),
			'request' => $this->buildRequestMetadata(),
			'flow' => $flow,
		];

		return $this->storePayload($dir, $payload);
	}

	public function loadProfile(string $id): ?array
	{
		if (!defined('BASE_PATH')) {
			return null;
		}

		$path = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . $this->relativeDir . DIRECTORY_SEPARATOR . $id . '.json';
		if (!file_exists($path)) {
			return null;
		}

		$contents = file_get_contents($path);
		if (!is_string($contents) || $contents === '') {
			return null;
		}

		$decoded = json_decode($contents, true);

		return is_array($decoded) ? $decoded : null;
	}

	public function latestProfileId(): ?string
	{
		if (!defined('BASE_PATH')) {
			return null;
		}

		$path = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . $this->relativeDir . DIRECTORY_SEPARATOR . 'latest';
		if (!file_exists($path)) {
			return null;
		}

		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			return null;
		}

		$id = strtolower(trim($contents));

		return $id !== '' ? $id : null;
	}

	private function resolveStorageDir(): ?string
	{
		if (!defined('BASE_PATH')) {
			return null;
		}

		$dir = rtrim(BASE_PATH, '/\\') . DIRECTORY_SEPARATOR . $this->relativeDir;
		if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
			return null;
		}

		return $dir;
	}

	/**
	 * @param array<string,mixed> $payload
	 */
	private function storePayload(string $dir, array $payload): ?string
	{
		$id = bin2hex(random_bytes(8));
		$payload['id'] = $id;

		$encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
		if (!is_string($encoded)) {
			return null;
		}

		file_put_contents($dir . DIRECTORY_SEPARATOR . $id . '.json', $encoded);
		file_put_contents($dir . DIRECTORY_SEPARATOR . 'latest', $id);

		return $id;
	}

	/**
	 * @return array{method:string, uri:string, host:string}
	 */
	private function buildRequestMetadata(): array
	{
		return [
			'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
			'uri' => $_SERVER['REQUEST_URI'] ?? ($_SERVER['argv'][0] ?? 'cli://unknown'),
			'host' => $_SERVER['HTTP_HOST'] ?? 'localhost',
		];
	}
}
