<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Http;

use JsonException;
use RuntimeException;

/**
 * Response Class
 *
 * Represents an HTTP response, encapsulating standard headers, status code, and body.
 */
class Response
{
	/**
	 * Default content type for plain-text responses.
	 */
	private const DEFAULT_TEXT_CONTENT_TYPE = 'text/plain; charset=utf-8';

	/**
	 * Default content type for JSON responses.
	 */
	private const DEFAULT_JSON_CONTENT_TYPE = 'application/json; charset=utf-8';

	/**
	 * Default content type for redirect responses.
	 */
	private const DEFAULT_HTML_CONTENT_TYPE = 'text/html; charset=utf-8';

	/**
	 * Reason phrases keyed by HTTP status code.
	 *
	 * @var array<int, string>
	 */
	private const REASON_PHRASES = [
		200 => 'OK',
		201 => 'Created',
		204 => 'No Content',
		301 => 'Moved Permanently',
		302 => 'Found',
		304 => 'Not Modified',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		422 => 'Unprocessable Content',
		500 => 'Internal Server Error',
		503 => 'Service Unavailable',
	];

	/** @var int The HTTP status code. */
	protected int $statusCode;

	/** @var string The HTTP reason phrase. */
	protected string $reasonPhrase;

	/** @var array The HTTP headers. */
	protected array $headers;

	/** @var mixed The response body. */
	protected mixed $body;

	/**
	 * Response constructor.
	 *
	 * @param string|object|array $body The response body content.
	 * @param int $statusCode The HTTP status code.
	 * @param array $headers The response headers.
	 * @param string|null $reasonPhrase An optional custom reason phrase.
	 */
	public function __construct(string|object|array $body = '', int $statusCode = 200, array $headers = [], ?string $reasonPhrase = null)
	{
		$this->body = $body;
		$this->statusCode = $statusCode;
		$this->headers = $headers;
		$this->reasonPhrase = $reasonPhrase ?? (self::REASON_PHRASES[$statusCode] ?? '');

		if (!$this->hasHeader('Content-Type')) {
			if (is_array($body) || is_object($body)) {
				$this->setHeader('Content-Type', self::DEFAULT_JSON_CONTENT_TYPE);
			} else {
				$this->setHeader('Content-Type', self::DEFAULT_TEXT_CONTENT_TYPE);
			}
		}
	}

	/**
	 * Get the HTTP status code of the response.
	 *
	 * @return int The status code.
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Get the reason phrase associated with the response status.
	 *
	 * @return string The reason phrase.
	 */
	public function getReasonPhrase(): string
	{
		return $this->reasonPhrase;
	}

	/**
	 * Get the body content of the response.
	 *
	 * @return mixed The response body.
	 */
	public function getBody(): mixed
	{
		return $this->body;
	}

	/**
	 * Get the headers set for the response.
	 *
	 * @return array The response headers.
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	/**
	 * Determine whether a header exists using case-insensitive lookup.
	 *
	 * @param string $name The header name.
	 *
	 * @return bool True when the header exists.
	 */
	public function hasHeader(string $name): bool
	{
		return $this->findHeaderName($name) !== null;
	}

	/**
	 * Get one header value using case-insensitive lookup.
	 *
	 * @param string $name The header name.
	 * @param string|null $default The fallback when the header is absent.
	 *
	 * @return string|null The header value.
	 */
	public function getHeader(string $name, ?string $default = null): ?string
	{
		$headerName = $this->findHeaderName($name);
		if ($headerName === null) {
			return $default;
		}

		return (string) $this->headers[$headerName];
	}

	/**
	 * Set or replace a response header.
	 *
	 * @param string $name The header name.
	 * @param string $value The header value.
	 *
	 * @return self
	 */
	public function setHeader(string $name, string $value): self
	{
		$headerName = $this->findHeaderName($name);
		if ($headerName !== null && $headerName !== $name) {
			unset($this->headers[$headerName]);
		}

		$this->headers[$name] = $value;
		return $this;
	}

	/**
	 * Create a plain-text response.
	 *
	 * @param string $body The response body.
	 * @param int $statusCode The HTTP status code.
	 * @param array $headers Additional response headers.
	 *
	 * @return self
	 */
	public static function text(string $body = '', int $statusCode = 200, array $headers = []): self
	{
		$headers = array_merge(['Content-Type' => self::DEFAULT_TEXT_CONTENT_TYPE], $headers);
		return new self($body, $statusCode, $headers);
	}

	/**
	 * Create a JSON response with safe encoding semantics.
	 *
	 * @param array|object $body The response payload.
	 * @param int $statusCode The HTTP status code.
	 * @param array $headers Additional response headers.
	 *
	 * @return self
	 */
	public static function json(array|object $body, int $statusCode = 200, array $headers = []): self
	{
		$headers = array_merge(['Content-Type' => self::DEFAULT_JSON_CONTENT_TYPE], $headers);
		return new self(self::encodeJsonBody($body), $statusCode, $headers);
	}

	/**
	 * Create an HTTP redirect response.
	 *
	 * @param string $location The redirect target.
	 * @param int $statusCode The redirect status code.
	 * @param array $headers Additional response headers.
	 *
	 * @return self
	 */
	public static function redirect(string $location, int $statusCode = 302, array $headers = []): self
	{
		$headers = array_merge(
			[
				'Location' => $location,
				'Content-Type' => self::DEFAULT_HTML_CONTENT_TYPE,
			],
			$headers
		);

		return new self('', $statusCode, $headers);
	}

	/**
	 * Send the HTTP response headers and body to the client.
	 *
	 * @return void
	 */
	public function send(): void
	{
		if (!headers_sent()) {
			http_response_code($this->statusCode);

			foreach ($this->headers as $name => $value) {
				header("{$name}: {$value}", true);
			}
		}

		echo $this->renderBody();
	}

	/**
	 * Render the response body to a string.
	 *
	 * @return string The serialized response body.
	 */
	protected function renderBody(): string
	{
		if (is_array($this->body) || is_object($this->body)) {
			return self::encodeJsonBody($this->body);
		}

		return (string) $this->body;
	}

	/**
	 * Encode a JSON payload using strict error handling.
	 *
	 * @param array|object $payload The payload to encode.
	 *
	 * @return string The encoded JSON string.
	 *
	 * @throws RuntimeException When encoding fails.
	 */
	protected static function encodeJsonBody(array|object $payload): string
	{
		try {
			return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw new RuntimeException('Failed to encode response body as JSON.', 0, $exception);
		}
	}

	/**
	 * Find the original stored header name for case-insensitive lookup.
	 *
	 * @param string $name The header name to locate.
	 *
	 * @return string|null The stored header name, if present.
	 */
	protected function findHeaderName(string $name): ?string
	{
		$normalizedName = strtolower($name);

		foreach ($this->headers as $headerName => $value) {
			if (strtolower($headerName) === $normalizedName) {
				return $headerName;
			}
		}

		return null;
	}
}
