<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\HTTP;

use function is_string;
use function sprintf;

/**
 * Simple HTTP Response object used across the framework.
 */
class Response
{
    /** @var mixed $body The content to be sent in the response. */
    protected mixed $body;
    /** @var array $headers An associative array of headers for the response. */
    protected array $headers = [];
    /** @var string $type The response type (e.g., 'html', 'json', 'text'). Used to guess Content-Type if not explicitly set. */
    protected string $type = 'html';
    /** @var int $status The HTTP status code for the response (e.g., 200, 404, 500). */
    protected int $status = 200;

    /**
     * Constructor
     *
     * @param mixed $body The content to be sent in the response. Can be a string, array, object, etc.
     * @param array $headers An associative array of headers, where the keys are header names and the values are header values.
     * @param string $type The response type (e.g., 'html', 'json', 'text'). Used to guess Content-Type if not explicitly set.
     * @param int $status The HTTP status code for the response (e.g., 200, 404, 500).
     */
    public function __construct(mixed $body = null, array $headers = [], string $type = 'html', int $status = 200)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->type = $type;
        $this->status = $status;

        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = $this->guessContentType($type);
        }
    }

    /**
     * Guess the Content-Type header based on the response type.
     *
     * @param string $type The response type (e.g., 'json', 'text', 'image').
     * @return string The guessed Content-Type header value.
     */
    protected function guessContentType(string $type): string
    {
        return match (strtolower($type)) {
            'json' => 'application/json; charset=utf-8',
            'text' => 'text/plain; charset=utf-8',
            'image' => 'application/octet-stream',
            default => 'text/html; charset=utf-8',
        };
    }

    /**
     * Get the body of the response.
     *
     * @return mixed The content to be sent in the response. Can be a string, array, object, etc.
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Set the body of the response.
     *
     * @param mixed $body The content to be sent in the response. Can be a string, array, object, etc.
     */
    public function setBody(mixed $body): void
    {
        $this->body = $body;
    }

    /**
     * Set a single header for the response.
     *
     * @param string $name The name of the header (e.g., 'Content-Type').
     * @param string $value The value of the header (e.g., 'application/json; charset=utf-8').
     */
    public function setHeader(string $name, string $value): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * Set multiple headers at once.
     *
     * @param array $headers An associative array of headers, where the keys are header names and the values are header values.
     */
    public function setHeaders(array $headers): void
    {
        foreach ($headers as $k => $v) {
            $this->setHeader($k, $v);
        }
    }

    /**
     * Get all headers as an associative array.
     *
     * @return array An associative array of headers, where the keys are header names and the values are header values.
     */
    public function getResponseHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the HTTP status code for the response.
     *
     * @return int The HTTP status code (e.g., 200, 404, 500).
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Set the HTTP status code for the response.
     *
     * @param int $status The HTTP status code to set (e.g., 200, 404, 500).
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * Get the Content-Type header value, or guess it based on the response type if not explicitly set.
     *
     * @return string The Content-Type of the response.
     */
    public function getContentType(): string
    {
        return $this->headers['Content-Type'] ?? $this->guessContentType($this->type);
    }

    /**
     * Determine if the response content type is JSON.
     *
     * @return bool True if the content type is JSON, false otherwise.
     */
    public function isJson(): bool
    {
        return stripos($this->getContentType(), 'application/json') !== false || strtolower($this->type) === 'json';
    }

    /**
     * Get the response content as a string, encoding to JSON if necessary.
     *
     * @return string The response content as a string.
     */
    public function getContent(): string
    {
        if ($this->isJson()) {
            return json_encode($this->body, JSON_UNESCAPED_UNICODE);
        }

        if (is_string($this->body)) {
            return $this->body;
        }

        return (string)print_r($this->body, true);
    }

    /**
     * Send headers and body to the client. Intended as a convenience helper.
     */
    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);

            foreach ($this->headers as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
            }
        }

        echo $this->getContent();
    }
}
