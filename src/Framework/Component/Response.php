<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\{Data\ArrayObject, Data\StringObject, Header, System};
use Clover\Classes\Data\JSONHandler;
use RuntimeException;
use DateTimeInterface;
use function sprintf;
use function in_array;
use function is_string;
use function strlen;

/**
 * Response Component
 */
class Response
{
    private string $type;

    private mixed $body;

    private array $resource;

    private int $statusCode;

    private string $protocolVersion = '1.1';

    /**
     * Additional response headers
     * @var array
     */
    private array $headers = [];

    /**
     * Response cookies
     * @var array
     */
    private array $cookies = [];

    /**
     * HTTP status text mapping
     * @var array
     */
    private static array $statusTexts = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Content Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Content',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * Content type mapping
     * @var array
     */
    private static array $contentTypes = [
        'html' => 'text/html; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'text' => 'text/plain; charset=utf-8',
        'xml' => 'application/xml; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'javascript' => 'application/javascript; charset=utf-8',
        'image' => 'application/octet-stream',
        'pdf' => 'application/pdf',
        'zip' => 'application/zip',
        'csv' => 'text/csv; charset=utf-8',
    ];

    /**
     * Constructor
     *
     * @param mixed $body
     * @param array $resource
     * @param string $type
     * @param int $statusCode
     */
    public function __construct($body = '', $resource = [], $type = 'html', $statusCode = 200)
    {
        $this->body = $body;
        $this->resource = $resource;
        $this->type = strtolower($type);
        $this->statusCode = $statusCode;

        $this->setContentTypeByType($this->type);
    }

    /**
     * Set Content-Type header by type
     *
     * @param string|StringObject $type
     *
     * @return void
     */
    private function setContentTypeByType(string|StringObject $type): void
    {
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = self::$contentTypes[$type] ?? self::$contentTypes['html'];
        }
    }

    /**
     * Create JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     * @param int $jsonFlags
     *
     * @return static
     */
    public static function json(mixed $data, int $statusCode = 200, int $jsonFlags = JSON_UNESCAPED_UNICODE): static
    {
        $body = JSONHandler::encode($data, $jsonFlags);

        if ($body === false) {
            throw new RuntimeException(
                Translator::trans(
                    'framework.response.json_encoding_failed',
                    ['reason' => json_last_error_msg()],
                    'JSON encoding failed: {reason}'
                )
            );
        }

        return new static($body, [], 'json', $statusCode);
    }

    /**
     * Create redirect response
     *
     * @param string $url
     * @param int $statusCode
     *
     * @return static
     */
    public static function redirect(string $url, int $statusCode = 302): static
    {
        $response = new static('', [], 'html', $statusCode);
        $response->setHeader('Location', $url);

        return $response;
    }

    /**
     * Create file download response
     *
     * @param string $content
     * @param string $filename
     * @param string $contentType
     *
     * @return static
     */
    public static function download(string $content, string $filename, string $contentType = 'application/octet-stream'): static
    {
        $response = new static($content, [], 'image', 200);
        $response->setHeader('Content-Type', $contentType);
        $response->setHeader('Content-Disposition', 'attachment; filename="' . addslashes($filename) . '"');
        $response->setHeader('Content-Length', (string) strlen($content));

        return $response;
    }

    /**
     * Set a response header
     *
     * @param string $name
     * @param string $value
     *
     * @return static
     */
	public function setHeader(string $name, string $value): static
	{
		foreach (array_keys($this->headers) as $existingName) {
			if (strcasecmp($existingName, $name) === 0 && $existingName !== $name) {
				unset($this->headers[$existingName]);
			}
		}

		$this->headers[$name] = $value;

        return $this;
    }

    /**
     * Set multiple headers
     *
     * @param array $headers
     *
     * @return static
     */
    public function setHeaders(array $headers): static
    {
        foreach ($headers as $k => $v) {
            $this->setHeader($k, $v);
        }

        return $this;
    }

    /**
     * Remove a header
     *
     * @param string $name
     *
     * @return static
     */
	public function removeHeader(string $name): static
	{
		foreach (array_keys($this->headers) as $existingName) {
			if (strcasecmp($existingName, $name) === 0) {
				unset($this->headers[$existingName]);
			}
		}

        return $this;
    }

    /**
     * Check if header exists
     *
     * @param string $name
     *
     * @return bool
     */
	public function hasHeader(string $name): bool
	{
		foreach ($this->headers as $existingName => $value) {
			if (strcasecmp($existingName, $name) === 0) {
				return true;
			}
		}

		return false;
    }

    /**
     * Get a header value
     *
     * @param string $name
     * @param string|null $default
     *
     * @return string|null
     */
	public function getHeader(string $name, ?string $default = null): ?string
	{
		foreach ($this->headers as $existingName => $value) {
			if (strcasecmp($existingName, $name) === 0) {
				return $value;
			}
		}

		return $default;
    }

    /**
     * Get response headers
     *
     * @return array
     */
    public function getResponseHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set a cookie
     *
     * @param string $name
     * @param string $value
     * @param array $options
     *
     * @return static
     */
    public function setCookie(string $name, string $value, array $options = []): static
    {
        $this->cookies[$name] = [
            'value' => $value,
            'expires' => $options['expires'] ?? 0,
            'path' => $options['path'] ?? '/',
            'domain' => $options['domain'] ?? '',
            'secure' => $options['secure'] ?? false,
            'httponly' => $options['httponly'] ?? true,
            'samesite' => $options['samesite'] ?? 'Lax',
        ];

        return $this;
    }

    /**
     * Remove a cookie
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     *
     * @return static
     */
    public function removeCookie(string $name, string $path = '/', string $domain = ''): static
    {
        return $this->setCookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => $domain,
        ]);
    }

    /**
     * Get cookies
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Set protocol version
     *
     * @param string $version
     *
     * @return static
     */
    public function setProtocolVersion(string $version): static
    {
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * Get protocol version
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Set cache headers
     *
     * @param int $maxAge
     * @param bool $public
     *
     * @return static
     */
    public function setCache(int $maxAge, bool $public = true): static
    {
        $directive = $public ? 'public' : 'private';
        $this->setHeader('Cache-Control', "{$directive}, max-age={$maxAge}");
        $this->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');

        return $this;
    }

    /**
     * Set no cache headers
     *
     * @return static
     */
    public function setNoCache(): static
    {
        $this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Expires', 'Thu, 01 Jan 1970 00:00:00 GMT');

        return $this;
    }

    /**
     * Set ETag header
     *
     * @param string $etag
     * @param bool $weak
     *
     * @return static
     */
    public function setEtag(string $etag, bool $weak = false): static
    {
        $etag = $weak ? 'W/"' . $etag . '"' : '"' . $etag . '"';
        $this->setHeader('ETag', $etag);

        return $this;
    }

    /**
     * Set Last-Modified header
     *
     * @param DateTimeInterface|int $date
     *
     * @return static
     */
    public function setLastModified(DateTimeInterface|int $date): static
    {
        if ($date instanceof DateTimeInterface) {
            $timestamp = $date->getTimestamp();
        } else {
            $timestamp = $date;
        }

        $this->setHeader('Last-Modified', gmdate('D, d M Y H:i:s', $timestamp) . ' GMT');

        return $this;
    }

    /**
     * Set CORS headers
     *
     * @param string $origin
     * @param array $methods
     * @param array $headers
     * @param int $maxAge
     *
     * @return static
     */
    public function setCors(string $origin = '*', array $methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'], array $headers = ['Content-Type', 'Authorization'], int $maxAge = 86400): static
    {
        $this->setHeader('Access-Control-Allow-Origin', $origin);
        $this->setHeader('Access-Control-Allow-Methods', implode(', ', $methods));
        $this->setHeader('Access-Control-Allow-Headers', implode(', ', $headers));
        $this->setHeader('Access-Control-Max-Age', (string) $maxAge);

        return $this;
    }

    /**
     * Set Content-Disposition header for inline display
     *
     * @param string $filename
     *
     * @return static
     */
    public function setInlineDisposition(string $filename): static
    {
        $this->setHeader('Content-Disposition', 'inline; filename="' . addslashes($filename) . '"');

        return $this;
    }

    /**
     * Set Continue status
     *
     * @return $this
     */
    public function setContinueStatus(): Response
    {
        return $this->setStatusCode(100);
    }

    /**
     * Set Switching Protocols status
     *
     * @return $this
     */
    public function setSwitchingProtocolsStatus(): Response
    {
        return $this->setStatusCode(101);
    }

    /**
     * Set OK status
     *
     * @return $this
     */
    public function setOkStatus(): Response
    {
        return $this->setStatusCode(200);
    }

    /**
     * Set Created status
     *
     * @return $this
     */
    public function setCreatedStatus(): Response
    {
        return $this->setStatusCode(201);
    }

    /**
     * Set Accepted status
     *
     * @return $this
     */
    public function setAcceptedStatus(): Response
    {
        return $this->setStatusCode(202);
    }

    /**
     * Set Non Authoritative Information status
     *
     * @return $this
     */
    public function setNonAuthoritativeInformationStatus(): Response
    {
        return $this->setStatusCode(203);
    }

    /**
     * Set No Content status
     *
     * @return $this
     */
    public function setNoContentStatus(): Response
    {
        return $this->setStatusCode(204);
    }

    /**
     * Set Reset Content status
     *
     * @return $this
     */
    public function setResetContentStatus(): Response
    {
        return $this->setStatusCode(205);
    }

    /**
     * Set Partial Content status
     *
     * @return $this
     */
    public function setPartialContentStatus(): Response
    {
        return $this->setStatusCode(206);
    }

    /**
     * Set Multiple Choices status
     *
     * @return $this
     */
    public function setMultipleChoicesStatus(): Response
    {
        return $this->setStatusCode(300);
    }

    /**
     * Set Moved Permanently status
     *
     * @return $this
     */
    public function setMovedPermanentlyStatus(): Response
    {
        return $this->setStatusCode(301);
    }

    /**
     * Set Moved Temporarily status
     *
     * @return $this
     */
    public function setMovedTemporarilyStatus(): Response
    {
        return $this->setStatusCode(302);
    }

    /**
     * Set See Other status
     *
     * @return $this
     */
    public function setSeeOtherStatus(): Response
    {
        return $this->setStatusCode(303);
    }

    /**
     * Set Not Modified status
     *
     * @return $this
     */
    public function setNotModifiedStatus(): Response
    {
        return $this->setStatusCode(304);
    }

    /**
     * Set Use Proxy status
     *
     * @return $this
     */
    public function setUseProxyStatus(): Response
    {
        return $this->setStatusCode(305);
    }

    /**
     * Set Temporary Redirect status
     *
     * @return $this
     */
    public function setTemporaryRedirectStatus(): Response
    {
        return $this->setStatusCode(307);
    }

    /**
     * Set Permanent Redirect status
     *
     * @return $this
     */
    public function setPermanentRedirectStatus(): Response
    {
        return $this->setStatusCode(308);
    }

    /**
     * Set Bad Request status
     *
     * @return $this
     */
    public function setBadRequestStatus(): Response
    {
        return $this->setStatusCode(400);
    }

    /**
     * Set Unauthorized status
     *
     * @return $this
     */
    public function setUnauthorizedStatus(): Response
    {
        return $this->setStatusCode(401);
    }

    /**
     * Set Payment Required status
     *
     * @return $this
     */
    public function setPaymentRequiredStatus(): Response
    {
        return $this->setStatusCode(402);
    }

    /**
     * Set Forbidden status
     *
     * @return $this
     */
    public function setForbiddenStatus(): Response
    {
        return $this->setStatusCode(403);
    }

    /**
     * Set Not Found status
     *
     * @return $this
     */
    public function setNotFoundStatus(): Response
    {
        return $this->setStatusCode(404);
    }

    /**
     * Set Method Not Allowed status
     *
     * @return $this
     */
    public function setMethodNotAllowedStatus(): Response
    {
        return $this->setStatusCode(405);
    }

    /**
     * Set Not Acceptable status
     *
     * @return $this
     */
    public function setNotAcceptableStatus(): Response
    {
        return $this->setStatusCode(406);
    }

    /**
     * Set Proxy Authentication Required status
     *
     * @return $this
     */
    public function setProxyAuthenticationRequiredStatus(): Response
    {
        return $this->setStatusCode(407);
    }

    /**
     * Set Request Timeout status
     *
     * @return $this
     */
    public function setRequestTimeoutStatus(): Response
    {
        return $this->setStatusCode(408);
    }

    /**
     * Set Conflict status
     *
     * @return $this
     */
    public function setConflictStatus(): Response
    {
        return $this->setStatusCode(409);
    }

    /**
     * Set Gone status
     *
     * @return $this
     */
    public function setGoneStatus(): Response
    {
        return $this->setStatusCode(410);
    }

    /**
     * Set Length Required status
     *
     * @return $this
     */
    public function setLengthRequiredStatus(): Response
    {
        return $this->setStatusCode(411);
    }

    /**
     * Set Precondition Failed status
     *
     * @return $this
     */
    public function setPreconditionFailedStatus(): Response
    {
        return $this->setStatusCode(412);
    }

    /**
     * Set Request Entity Too Large status
     *
     * @return $this
     */
    public function setRequestEntityTooLargeStatus(): Response
    {
        return $this->setStatusCode(413);
    }

    /**
     * Set Request URI Too Large status
     *
     * @return $this
     */
    public function setRequestUriTooLargeStatus(): Response
    {
        return $this->setStatusCode(414);
    }

    /**
     * Set Unsupported Media Type status
     *
     * @return $this
     */
    public function setUnsupportedMediaTypeStatus(): Response
    {
        return $this->setStatusCode(415);
    }

    /**
     * Set Too Many Requests status
     *
     * @return $this
     */
    public function setTooManyRequestsStatus(): Response
    {
        return $this->setStatusCode(429);
    }

    /**
     * Set Internal Server Error status
     *
     * @return $this
     */
    public function setInternalServerErrorStatus(): Response
    {
        return $this->setStatusCode(500);
    }

    /**
     * Set Not Implemented status
     *
     * @return $this
     */
    public function setNotImplementedStatus(): Response
    {
        return $this->setStatusCode(501);
    }

    /**
     * Set Bad Gateway status
     *
     * @return $this
     */
    public function setBadGatewayStatus(): Response
    {
        return $this->setStatusCode(502);
    }

    /**
     * Set Service Unavailable status
     *
     * @return $this
     */
    public function setServiceUnavailableStatus(): Response
    {
        return $this->setStatusCode(503);
    }

    /**
     * Set Gateway Timeout status
     *
     * @return $this
     */
    public function setGatewayTimeoutStatus(): Response
    {
        return $this->setStatusCode(504);
    }

    /**
     * Set HTTP Version Not Supported status
     *
     * @return $this
     */
    public function setHttpVersionNotSupportedStatus(): Response
    {
        return $this->setStatusCode(505);
    }

    /**
     * Set the response status code
     *
     * @param int $statusCode
     *
     * @return $this
     */
    public function setStatusCode(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Get the response status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get status text for a code
     *
     * @param int|null $code
     *
     * @return string
     */
    public function getStatusText(?int $code = null): string
    {
        $code = $code ?? $this->statusCode;

        return self::$statusTexts[$code] ?? 'Unknown Status';
    }

    /**
     * Check if response is informational (1xx)
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Check if response is successful (2xx)
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is redirect (3xx)
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is client error (4xx)
     *
     * @return bool
     */
    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error (5xx)
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if response is OK (200)
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return $this->statusCode === 200;
    }

    /**
     * Check if response is empty (204, 304)
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->statusCode, [204, 304], true);
    }

    /**
     * Get the response type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the response type
     *
     * @param string $type
     *
     * @return static
     */
    public function setType(string $type): static
    {
        $this->type = strtolower($type);
        $this->setContentTypeByType($this->type);

        return $this;
    }

    /**
     * Get the response resource
     *
     * @return ArrayObject|array
     */
    public function getResource(): ArrayObject|array
    {
        return $this->resource;
    }

    /**
     * Set the response resource
     *
     * @param ArrayObject|array $resource
     *
     * @return static
     */
    public function setResource(ArrayObject|array $resource): static
    {
        $this->resource = $resource;

        return $this;
    }

    public function clearResources()
    {
        $this->resource = [];

        return $this;
    }

    /**
     * Get the response body
     *
     * @return mixed
     */
    public function getBody(): mixed
    {
        return $this->body;
    }

    /**
     * Set the response body
     *
     * @param mixed $body
     *
     * @return $this
     */
    public function setBody(mixed $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body length
     *
     * @return int
     */
    public function getBodyLength(): int
    {
        if (is_string($this->body)) {
            return strlen($this->body);
        }

        return 0;
    }

    /**
     * Send response headers
     *
     * @return bool
     */
    public function sendHeaders(): bool
    {
        if (Header::isSent()) {
            return false;
        }

        header(sprintf('HTTP/%s %d %s', $this->protocolVersion, $this->statusCode, $this->getStatusText()), true, $this->statusCode);

		foreach ($this->headers as $name => $value) {
			header("{$name}: {$value}", true);
		}

        foreach ($this->cookies as $name => $cookie) {
            setcookie($name, $cookie['value'], ['expires' => $cookie['expires'], 'path' => $cookie['path'], 'domain' => $cookie['domain'], 'secure' => $cookie['secure'], 'httponly' => $cookie['httponly'], 'samesite' => $cookie['samesite'],]);
        }

        return true;
    }

    /**
     * Print response body
     */
    public function printBody(): void
    {
        System\Output::print($this->body);
    }

    /**
     * Send response code header
     */
    public function responseCode(): void
    {
        http_response_code($this->statusCode);
    }

    /**
     * Send the complete response
     *
     * @return void
     */
    public function send(): void
    {
        $isSented = $this->sendHeaders();
        if (!$isSented) {
            return;
        }

        if (!$this->isEmpty()) {
            $this->printBody();
        }

        $this->responseCode();
    }

    /**
     * Flush output buffers
     *
     * @return void
     */
    public static function flushOutput(): void
    {
        $level = ob_get_level();
        while ($level > 0) {
            ob_end_flush();
            $level--;
        }

        flush();
    }

    /**
     * Prepend content to the response body
     *
     * @param string|StringObject $body
     *
     * @return static
     */
    public function preAppendBody(string|StringObject $body): static
    {
        $this->body = sprintf("%s%s", $body, $this->body);

        return $this;
    }

    /**
     * Append content to the response body
     *
     * @param string|StringObject $body
     *
     * @return static
     */
    public function appendBody(string|StringObject $body): static
    {
        $this->body .= $body;

        return $this;
    }

    /**
     * Clear the response body
     *
     * @return static
     */
    public function clearBody(): static
    {
        $this->body = '';

        return $this;
    }

    /**
     * Convert response to string
     *
     * @return string
     */
    public function __toString(): string
    {
        $output = sprintf("HTTP/%s %d %s\r\n", $this->protocolVersion, $this->statusCode, $this->getStatusText());

        foreach ($this->headers as $name => $value) {
            $output .= "{$name}: {$value}\r\n";
        }

        $output .= "\r\n";
        $output .= (string) $this->body;

        return $output;
    }
}
