<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Http;

/**
 * Request Class
 *
 * Represents an HTTP request, encapsulating server parameters, query parameters,
 * and parsed body data.
 */
class Request
{
	/**
	 * The fallback request method when no server value is present.
	 */
	private const DEFAULT_METHOD = 'GET';

	/**
	 * The fallback URI when no server value is present.
	 */
	private const DEFAULT_URI = '/';

	/** @var array Server and execution environment parameters ($_SERVER). */
	protected array $serverParams;

	/** @var array Query string variables ($_GET). */
	protected array $queryParams;

	/** @var array|object|null Parsed request body data ($_POST or similar). */
	protected array|object|null $parsedBody;

	/** @var array Cookie parameters ($_COOKIE). */
	protected array $cookies;

	/** @var array Uploaded file parameters ($_FILES). */
	protected array $files;

	/** @var array<string, string> Normalized request headers. */
	protected array $headers = [];

	/** @var array Custom request attributes. */
	protected array $attributes = [];

	/** @var string The HTTP request method. */
	protected string $method;

	/** @var string The HTTP request URI. */
	protected string $uri;

	/** @var string The raw request body content. */
	protected string $rawContent;

	/**
	 * Request constructor.
	 *
	 * @param array $serverParams Server parameters.
	 * @param array $queryParams Query parameters.
	 * @param array $parsedBody Parsed body data.
	 * @param array $cookies Cookie parameters.
	 * @param array $files Uploaded files.
	 */
	public function __construct(array $serverParams = [], array $queryParams = [], array|object|null $parsedBody = [], array $cookies = [], array $files = [], string $rawContent = '')
	{
		$this->serverParams = $serverParams;
		$this->cookies = $cookies;
		$this->files = $files;
		$this->rawContent = $rawContent;
		$rawUri = (string) ($serverParams['REQUEST_URI'] ?? self::DEFAULT_URI);
		$this->queryParams = $queryParams !== [] ? $queryParams : $this->extractQueryParamsFromUri($rawUri);
		$this->headers = $this->extractHeaders($serverParams);
		$this->parsedBody = $this->normalizeParsedBody($parsedBody, $rawContent);
		$this->method = $this->normalizeMethod((string) ($serverParams['REQUEST_METHOD'] ?? self::DEFAULT_METHOD));
		$this->uri = $this->normalizeUri($rawUri);
	}

	/**
	 * Create a new Request instance from standard PHP superglobals.
	 *
	 * @return self A new Request instance.
	 */
	public static function createFromGlobals(): self
	{
		$rawContent = file_get_contents('php://input');
		if (!is_string($rawContent)) {
			$rawContent = '';
		}

		return new self($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES, $rawContent);
	}

	/**
	 * Get the HTTP method of the request.
	 *
	 * @return string The HTTP method.
	 */
	public function getMethod(): string
	{
		return $this->method;
	}

	/**
	 * Get the URI of the request.
	 *
	 * @return string The request URI.
	 */
	public function getUri(): string
	{
		return $this->uri;
	}

	/**
	 * Get the normalized request path.
	 *
	 * @return string The normalized path.
	 */
	public function getPath(): string
	{
		return $this->uri;
	}

	/**
	 * Get the query parameters of the request.
	 *
	 * @return array The query parameters.
	 */
	public function getQueryParams(): array
	{
		return $this->queryParams;
	}

	/**
	 * Get one query parameter by key.
	 *
	 * @param string $key The query parameter key.
	 * @param mixed $default The fallback value when the key is missing.
	 *
	 * @return mixed The resolved query value.
	 */
	public function getQueryParam(string $key, mixed $default = null): mixed
	{
		if (array_key_exists($key, $this->queryParams)) {
			return $this->queryParams[$key];
		}

		return $default;
	}

	/**
	 * Get the parsed body data of the request.
	 *
	 * @return array|object|null The parsed body data.
	 */
	public function getParsedBody(): array|object|null
	{
		return $this->parsedBody;
	}

	/**
	 * Get one parsed body value by key.
	 *
	 * @param string $key The body key.
	 * @param mixed $default The fallback value when the key is missing.
	 *
	 * @return mixed The resolved body value.
	 */
	public function getParsedBodyValue(string $key, mixed $default = null): mixed
	{
		if (!is_array($this->parsedBody)) {
			return $default;
		}

		if (array_key_exists($key, $this->parsedBody)) {
			return $this->parsedBody[$key];
		}

		return $default;
	}

	/**
	 * Resolve an input value from parsed body first, then query parameters.
	 *
	 * @param string $key The input key.
	 * @param mixed $default The fallback value when the key is missing.
	 *
	 * @return mixed The resolved input value.
	 */
	public function input(string $key, mixed $default = null): mixed
	{
		if (is_array($this->parsedBody) && array_key_exists($key, $this->parsedBody)) {
			return $this->parsedBody[$key];
		}

		if (array_key_exists($key, $this->queryParams)) {
			return $this->queryParams[$key];
		}

		return $default;
	}

	/**
	 * Get the request cookies.
	 *
	 * @return array The cookie values.
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}

	/**
	 * Get the uploaded files.
	 *
	 * @return array The uploaded file values.
	 */
	public function getFiles(): array
	{
		return $this->files;
	}

	/**
	 * Get the raw request body content.
	 *
	 * @return string The raw body content.
	 */
	public function getRawContent(): string
	{
		return $this->rawContent;
	}

	/**
	 * Get the server parameters.
	 *
	 * @return array The server parameter bag.
	 */
	public function getServerParams(): array
	{
		return $this->serverParams;
	}

	/**
	 * Get all normalized headers using lowercase keys.
	 *
	 * @return array<string, string> The header map.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Get a header by name using case-insensitive lookup.
	 *
	 * @param string $name The header name.
	 * @param string|null $default The fallback value when the header is missing.
	 *
	 * @return string|null The header value, if available.
	 */
	public function getHeader(string $name, ?string $default = null): ?string
	{
		$normalizedName = strtolower($name);
		if (array_key_exists($normalizedName, $this->headers)) {
			return $this->headers[$normalizedName];
		}

		return $default;
	}

	/**
	 * Set a custom request attribute.
	 *
	 * @param string $key The attribute key.
	 * @param mixed $value The attribute value.
	 *
	 * @return void
	 */
	public function setAttribute(string $key, mixed $value): void
	{
		$this->attributes[$key] = $value;
	}

	/**
	 * Determine whether an attribute exists.
	 *
	 * @param string $key The attribute key.
	 *
	 * @return bool True when the attribute exists.
	 */
	public function hasAttribute(string $key): bool
	{
		return array_key_exists($key, $this->attributes);
	}

	/**
	 * Get an attribute by key.
	 *
	 * @param string $key The attribute key.
	 * @param mixed $default The fallback value when the key is missing.
	 *
	 * @return mixed The resolved attribute value.
	 */
	public function getAttribute(string $key, mixed $default = null): mixed
	{
		if (array_key_exists($key, $this->attributes)) {
			return $this->attributes[$key];
		}

		return $default;
	}

	/**
	 * Get all request attributes.
	 *
	 * @return array The attribute bag.
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * Normalize the request method and honor supported override inputs.
	 *
	 * @param string $method The original server request method.
	 *
	 * @return string The normalized HTTP method.
	 */
	protected function normalizeMethod(string $method): string
	{
		$normalizedMethod = strtoupper($method !== '' ? $method : self::DEFAULT_METHOD);
		if ($normalizedMethod !== 'POST') {
			return $normalizedMethod;
		}

		$override = $this->getHeader('x-http-method-override');
		if ($override === null || $override === '') {
			$override = (string) $this->input('_method', '');
		}

		if ($override === '') {
			return $normalizedMethod;
		}

		return strtoupper($override);
	}

	/**
	 * Normalize the request URI to a path-only representation.
	 *
	 * @param string $uri The raw request URI.
	 *
	 * @return string The normalized path.
	 */
	protected function normalizeUri(string $uri): string
	{
		$path = parse_url($uri, PHP_URL_PATH);
		if (!is_string($path) || $path === '') {
			return self::DEFAULT_URI;
		}

		if ($path !== self::DEFAULT_URI) {
			$path = rtrim($path, '/');
		}

		if ($path === '') {
			return self::DEFAULT_URI;
		}

		return $path;
	}

	/**
	 * Extract query parameters from the request URI when they were not passed explicitly.
	 *
	 * @param string $uri The raw request URI.
	 *
	 * @return array The parsed query parameters.
	 */
	protected function extractQueryParamsFromUri(string $uri): array
	{
		$queryString = parse_url($uri, PHP_URL_QUERY);
		if (!is_string($queryString) || $queryString === '') {
			return [];
		}

		$queryParams = [];
		parse_str($queryString, $queryParams);

		return is_array($queryParams) ? $queryParams : [];
	}

	/**
	 * Extract normalized request headers from server parameters.
	 *
	 * @param array $serverParams The server parameter bag.
	 *
	 * @return array<string, string> The normalized header map.
	 */
	protected function extractHeaders(array $serverParams): array
	{
		$headers = [];

		foreach ($serverParams as $key => $value) {
			if (!is_string($value)) {
				continue;
			}

			if (str_starts_with($key, 'HTTP_')) {
				$headerName = strtolower(str_replace('_', '-', substr($key, 5)));
				$headers[$headerName] = $value;
				continue;
			}

			if ($key === 'CONTENT_TYPE' || $key === 'CONTENT_LENGTH' || $key === 'CONTENT_MD5') {
				$headerName = strtolower(str_replace('_', '-', $key));
				$headers[$headerName] = $value;
			}
		}

		return $headers;
	}

	/**
	 * Normalize parsed body data and decode JSON payloads when appropriate.
	 *
	 * @param array|object|null $parsedBody The explicit parsed body data.
	 * @param string $rawContent The raw request body.
	 *
	 * @return array|object|null The normalized parsed body.
	 */
	protected function normalizeParsedBody(array|object|null $parsedBody, string $rawContent): array|object|null
	{
		if ($parsedBody instanceof \stdClass) {
			return $parsedBody;
		}

		if (is_array($parsedBody) && $parsedBody !== []) {
			return $parsedBody;
		}

		$contentType = $this->headers['content-type'] ?? '';
		if (!str_contains(strtolower($contentType), 'application/json')) {
			return $parsedBody;
		}

		if ($rawContent === '') {
			return $parsedBody;
		}

		$decoded = json_decode($rawContent, true);
		if (is_array($decoded)) {
			return $decoded;
		}

		if ($decoded === null && trim($rawContent) === 'null') {
			return null;
		}

		return $parsedBody;
	}
}
