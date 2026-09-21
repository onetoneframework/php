<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

class Request
{
	private const DEFAULT_METHOD = 'GET';
	private const DEFAULT_URI = '/';
	private const MEDIA_TYPE_PARAMETER_PART_LIMIT = 2;

	public function __construct(public array $get = [], public array $post = [], public array $files = [], public array $cookie = [], public array $server = [], public string $content = '')
	{
	}

	public static function createFromGlobals(): self
	{
		return new self($_GET, $_POST, $_FILES, $_COOKIE, $_SERVER, file_get_contents('php://input') ?: '');
	}

	public static function createFromSwoole(\Swoole\Http\Request $request): self
	{
		$server = $request->server ?? [];
		foreach ($request->header ?? [] as $name => $value) {
			if (!is_string($name) || !is_scalar($value)) {
				continue;
			}

			$normalizedName = strtoupper(str_replace('-', '_', $name));
			if (!in_array($normalizedName, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true) && !str_starts_with($normalizedName, 'HTTP_')) {
				$normalizedName = 'HTTP_' . $normalizedName;
			}

			$server[$normalizedName] = (string) $value;
		}

		return new self($request->get ?? [], $request->post ?? [], $request->files ?? [], $request->cookie ?? [], $server, $request->rawContent());
	}

	/**
	 * Return the normalized request method for every supported transport.
	 */
	public function getMethod(): string
	{
		$method = $this->serverValue('REQUEST_METHOD', self::DEFAULT_METHOD);

		return $method !== '' ? strtoupper($method) : self::DEFAULT_METHOD;
	}

	/**
	 * Return the original request URI, including its query string when present.
	 */
	public function getUri(): string
	{
		$uri = $this->serverValue('REQUEST_URI', self::DEFAULT_URI);

		return $uri !== '' ? $uri : self::DEFAULT_URI;
	}

	/**
	 * Return the normalized path component of the request URI.
	 */
	public function getPath(): string
	{
		$path = parse_url($this->getUri(), PHP_URL_PATH);

		return is_string($path) && $path !== '' ? $path : self::DEFAULT_URI;
	}

	/**
	 * Resolve a request header without depending on transport-specific key casing.
	 */
	public function getHeader(string $name, string $default = ''): string
	{
		$normalizedName = strtoupper(str_replace('-', '_', trim($name)));
		if ($normalizedName === '') {
			return $default;
		}

		if (!in_array($normalizedName, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true) && !str_starts_with($normalizedName, 'HTTP_')) {
			$normalizedName = 'HTTP_' . $normalizedName;
		}

		$value = $this->serverValue($normalizedName, '');
		if ($value !== '') {
			return $value;
		}

		if (in_array($normalizedName, ['CONTENT_LENGTH', 'CONTENT_TYPE'], true)) {
			return $this->serverValue('HTTP_' . $normalizedName, $default);
		}

		return $default;
	}

	/**
	 * Determine whether the client expects a JSON-family response.
	 */
	public function wantsJson(): bool
	{
		if ($this->containsJsonMediaType($this->getHeader('Accept'))) {
			return true;
		}

		if ($this->containsJsonMediaType($this->getHeader('Content-Type'))) {
			return true;
		}

		return strtolower($this->getHeader('X-Requested-With')) === 'xmlhttprequest';
	}

	/**
	 * Read a scalar server value using case-insensitive key matching.
	 */
	private function serverValue(string $name, string $default): string
	{
		$normalizedName = strtoupper($name);
		foreach ($this->server as $key => $value) {
			if (!is_string($key) || strtoupper($key) !== $normalizedName || !is_scalar($value)) {
				continue;
			}

			return (string) $value;
		}

		return $default;
	}

	/**
	 * Match standard and vendor JSON media types in a comma-separated header.
	 */
	private function containsJsonMediaType(string $header): bool
	{
		foreach (explode(',', strtolower($header)) as $mediaRange) {
			$mediaType = trim(explode(';', $mediaRange, self::MEDIA_TYPE_PARAMETER_PART_LIMIT)[0]);
			if ($mediaType === 'application/json' || str_ends_with($mediaType, '+json')) {
				return true;
			}
		}

		return false;
	}
}
