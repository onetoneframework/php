<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Mock;

use Clover\Classes\ClientURLErrorResponse;
use Exception;

/**
 * MockClientURL
 *
 * A cURL-free replacement for ClientURL designed for unit testing.
 */
class MockClientURL
{
    /** @var MockClientURLOption  Tracks all configured options */
    public MockClientURLOption $option;

    /** @var MockClientURLLastTransferInformation  Preset transfer stats */
    public MockClientURLLastTransferInformation $information;

    /** @var string  The URL this instance was constructed with */
    private string $url;

    /** @var string  Raw preset response body */
    private string $mockBody = '';

    /** @var int  Preset HTTP status code returned after execute() */
    private int $mockStatusCode = 200;

    /** @var array<string, string>  Preset response headers */
    private array $mockResponseHeaders = [];

    /** @var int  0 = no error, >0 = cURL error code */
    private int $mockErrorCode = 0;

    /** @var string  Error message when mockErrorCode > 0 */
    private string $mockErrorMessage = '';

    /** @var bool  Whether execute() has been called at least once */
    private bool $executed = false;

    /** @var int  Number of times execute() was called */
    private int $executeCallCount = 0;

    /** @var array<int, array{body: string, statusCode: int, headers: array, errorCode: int, errorMessage: string}>
     *  Queue of mock responses consumed in order; when empty the default mock is used. */
    private array $responseQueue = [];

    /**
     * @var array{body: string, statusCode: int, headers: array}|null
     * If set, all static methods return this instead of making real requests.
     */
    private static ?array $staticMockResponse = null;

    /** @var int  Static cURL error code (0 = no error) */
    private static int $staticMockErrorCode = 0;

    /** @var string  Static cURL error message */
    private static string $staticMockErrorMessage = '';

    /**
     * Creates a mock client with optional URL preset on the option bag.
     *
     * @param string $url Initial request URL; may be empty for late binding.
     */
    public function __construct(string $url = '')
    {
        $this->url = $url;
        $this->option = new MockClientURLOption();
        $this->information = new MockClientURLLastTransferInformation();

        if ($url !== '') {
            $this->option->setURL($url);
            $this->information->setEffectiveURL($url);
        }
    }

    /**
     * Configure the response that execute() will return.
     *
     * @param string                $body       Raw response body
     * @param int                   $statusCode HTTP status code (default 200)
     * @param array<string, string> $headers    Response headers
     * @param float                 $totalTime  Simulated total request time in seconds
     */
    public function setMockResponse(string $body, int $statusCode = 200, array $headers = [], float $totalTime = 0.1): static
    {
        $this->mockBody = $body;
        $this->mockStatusCode = $statusCode;
        $this->mockResponseHeaders = $headers;
        $this->mockErrorCode = 0;
        $this->mockErrorMessage = '';

        $effectiveUrl = $this->option->getURL();
        if ($effectiveUrl === null || $effectiveUrl === '') {
            $effectiveUrl = $this->url;
        }

        $this->information
            ->setStatusCode($statusCode)
            ->setEffectiveURL($effectiveUrl)
            ->setTotalTime($totalTime)
            ->setDownloadedSize((float) strlen($body));

        if (isset($headers['Content-Type'])) {
            $this->information->setContentType($headers['Content-Type']);
        }

        return $this;
    }

    /**
     * Simulate a cURL-level error so execute() throws an Exception.
     *
     * @param int    $errorCode    cURL error code (e.g. 6 = CURLE_COULDNT_RESOLVE_HOST)
     * @param string $errorMessage Human-readable error description
     */
    public function setMockError(int $errorCode, string $errorMessage): static
    {
        $this->mockErrorCode = $errorCode;
        $this->mockErrorMessage = $errorMessage;
        return $this;
    }

    /**
     * Queue multiple responses that will be consumed one by one on each
     * successive execute() call.  Useful when testing retry logic.
     *
     * @param array{body: string, statusCode: int, headers?: array, errorCode?: int, errorMessage?: string}[] $responses
     */
    public function queueResponses(array $responses): static
    {
        foreach ($responses as $r) {
            $this->responseQueue[] = [
                'body' => $r['body'] ?? '',
                'statusCode' => $r['statusCode'] ?? 200,
                'headers' => $r['headers'] ?? [],
                'errorCode' => $r['errorCode'] ?? 0,
                'errorMessage' => $r['errorMessage'] ?? '',
            ];
        }
        return $this;
    }

    /**
     * Set the response all static methods (get, post, etc.) will return.
     *
     * @param string                $body
     * @param int                   $statusCode
     * @param array<string, string> $headers
     */
    public static function setStaticMockResponse(string $body, int $statusCode = 200, array $headers = []): void
    {
        self::$staticMockResponse = compact('body', 'statusCode', 'headers');
        self::$staticMockErrorCode = 0;
        self::$staticMockErrorMessage = '';
    }

    /**
     * Make all static methods simulate a cURL error.
     */
    public static function setStaticMockError(int $errorCode, string $errorMessage): void
    {
        self::$staticMockErrorCode = $errorCode;
        self::$staticMockErrorMessage = $errorMessage;
    }

    /** 
     * Reset all static mock state.
     **/
    public static function clearStaticMock(): void
    {
        self::$staticMockResponse = null;
        self::$staticMockErrorCode = 0;
        self::$staticMockErrorMessage = '';
    }

    /**
     * Execute the mock request.
     *
     * @throws Exception when a mock cURL error has been configured
     */
    public function execute(): mixed
    {
        $this->executed = true;
        $this->executeCallCount++;

        // Consume the next queued response if available
        if (!empty($this->responseQueue)) {
            $queued = array_shift($this->responseQueue);
            $this->mockBody = $queued['body'];
            $this->mockStatusCode = $queued['statusCode'];
            $this->mockResponseHeaders = $queued['headers'];
            $this->mockErrorCode = $queued['errorCode'];
            $this->mockErrorMessage = $queued['errorMessage'];

            $this->information
                ->setStatusCode($this->mockStatusCode)
                ->setDownloadedSize((float) strlen($this->mockBody));
        }

        if ($this->mockErrorCode > 0) {
            throw new Exception('Curl error: ' . $this->mockErrorMessage);
        }

        return $this->mockBody;
    }

    /**
     * Execute and return a structured detail array matching ClientURL::executeDetailed().
     *
     * @return array{success: bool, status: int, body: mixed, headers: array, error: ?string, error_code: int, timing: array}
     */
    public function executeDetailed(): array
    {
        $this->executed = true;
        $this->executeCallCount++;

        $hasCurlError = $this->mockErrorCode > 0;

        return [
            'success' => !$hasCurlError && $this->mockStatusCode < 400,
            'status' => $this->mockStatusCode,
            'body' => $hasCurlError ? null : $this->mockBody,
            'headers' => $this->mockResponseHeaders,
            'error' => $hasCurlError ? $this->mockErrorMessage : null,
            'error_code' => $this->mockErrorCode,
            'timing' => [
                'total' => $this->information->getTotalTransferTime(),
                'dns' => $this->information->getLookupNameTime(),
                'connect' => $this->information->getConnectionTime(),
                'ttfb' => $this->information->getStartTransferTime(),
            ],
        ];
    }

    /**
     * Execute and diagnose, matching ClientURL::executeAndDiagnose().
     *
     * @return array{success: bool, status: int, body: mixed, curl_error: ?array, http_error: ?array, summary: string}
     */
    public function executeAndDiagnose(): array
    {
        $this->executed = true;
        $this->executeCallCount++;

        $errResponse = new ClientURLErrorResponse();
        $diagnostic = $errResponse->getDiagnosticReport(
            $this->mockErrorCode,
            $this->mockStatusCode
        );

        return [
            'success' => $diagnostic['overall_success'],
            'status' => $this->mockStatusCode,
            'body' => $this->mockErrorCode > 0 ? null : $this->mockBody,
            'curl_error' => $diagnostic['curl_error'],
            'http_error' => $diagnostic['has_http_error'] ? $diagnostic['http_status'] : null,
            'summary' => $diagnostic['summary'],
        ];
    }

    /**
     * Returns the last configured HTTP status code for mock responses.
     *
     * @return int
     */
    public function getLastHttpCode(): int
    {
        return $this->mockStatusCode;
    }

    /**
     * Returns whether the last mock response counts as a successful HTTP exchange.
     *
     * @return bool True when no cURL error and status is in the 2xx range.
     */
    public function isLastRequestSuccessful(): bool
    {
        return $this->mockErrorCode === 0
            && $this->mockStatusCode >= 200
            && $this->mockStatusCode < 300;
    }

    /**
     * Returns the last simulated cURL error message when {@see setMockError()} was used.
     *
     * @return string
     */
    public function getLastErrorMessage(): string
    {
        return $this->mockErrorMessage;
    }

    /**
     * Returns the last simulated cURL error code (0 when none).
     *
     * @return int
     */
    public function getLastErrorNumber(): int
    {
        return $this->mockErrorCode;
    }

    /**
     * Looks up the human-readable message for a standard cURL error code.
     *
     * @param int $errorCode Numeric cURL error identifier.
     *
     * @return string|null Message text or null when unknown.
     */
    public function getErrorMessageByCode(int $errorCode): ?string
    {
        return (new ClientURLErrorResponse())->getErrorMessageFromCode($errorCode);
    }

    /**
     * Returns whether {@see execute()} or a detailed variant has run at least once.
     *
     * @return bool
     */
    public function wasExecuted(): bool
    {
        return $this->executed;
    }

    /**
     * Returns how many times execute-style methods have been invoked.
     *
     * @return int
     */
    public function getExecuteCallCount(): int
    {
        return $this->executeCallCount;
    }

    /**
     * Returns the preset response header map from the last mock configuration.
     *
     * @return array<string, string>
     */
    public function getResponseHeaders(): array
    {
        return $this->mockResponseHeaders;
    }

    /**
     * Returns a single response header value by name when present.
     *
     * @param string $name Header field name.
     *
     * @return string|null
     */
    public function getResponseHeader(string $name): ?string
    {
        return $this->mockResponseHeaders[$name] ?? null;
    }

    /**
     * Exposes the mutable last-transfer information object for assertions.
     *
     * @return MockClientURLLastTransferInformation
     */
    public function information(): MockClientURLLastTransferInformation
    {
        return $this->information;
    }

    /**
     * Factory helper for diagnostic reports based on ClientURL error metadata.
     *
     * @return ClientURLErrorResponse
     */
    public function errorResponse(): ClientURLErrorResponse
    {
        return new ClientURLErrorResponse();
    }

    /**
     * No-op close hook mirroring real ClientURL; releases nothing in the mock.
     *
     * @return void
     */
    public function close(): void
    {
        // No-op in mock — no handle to close
    }

    /**
     * Clears mock response state, execution counters, and nested option objects.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->executed = false;
        $this->executeCallCount = 0;
        $this->mockBody = '';
        $this->mockStatusCode = 200;
        $this->mockResponseHeaders = [];
        $this->mockErrorCode = 0;
        $this->mockErrorMessage = '';
        $this->responseQueue = [];
        $this->option->reset();
    }

    private static function executeSingleStaticRequest(string $url, string $method, array $headers = []): string
    {
        if (self::$staticMockErrorCode > 0) {
            throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
        }

        return self::$staticMockResponse['body'] ?? '';
    }

    /**
     * Returns the HTTP status configured for static mock helpers.
     *
     * @return int
     */
    private static function staticStatusCode(): int
    {
        return self::$staticMockResponse['statusCode'] ?? 200;
    }

    /**
     * Returns the headers configured for static mock helpers.
     *
     * @return array<string, string>
     */
    private static function staticHeaders(): array
    {
        return self::$staticMockResponse['headers'] ?? [];
    }

    /**
     * Simulate GET request.
     *
     * @param string                $url
     * @param array<string, mixed>  $params   Query parameters (appended to URL)
     * @param array<string, string> $headers
     *
     * @return mixed
     */
    public static function get(string $url, array $params = [], array $headers = []): mixed
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $mock = new self($url);
        $mock->option->setGetMethod(true)->setSSLVerifyPeer(false)->setReturnTransfer(true);

        if (self::$staticMockResponse !== null || self::$staticMockErrorCode > 0) {
            if (self::$staticMockErrorCode > 0) {
                throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
            }
            return self::$staticMockResponse['body'];
        }

        return $mock->mockBody;
    }

    /**
     * Simulate POST request.
     *
     * @param string                $url
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return mixed
     */
    public static function post(string $url, array $data = [], array $headers = []): mixed
    {
        $mock = new self($url);
        $mock->option->setPostMethod(true)->setSSLVerifyPeer(false)->setReturnTransfer(true);

        if (self::$staticMockResponse !== null || self::$staticMockErrorCode > 0) {
            if (self::$staticMockErrorCode > 0) {
                throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
            }
            return self::$staticMockResponse['body'];
        }

        return $mock->mockBody;
    }

    /**
     * Simulate PUT request.
     *
     * @param string                $url
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return mixed
     */
    public static function put(string $url, array $data = [], array $headers = []): mixed
    {
        $mock = new self($url);
        $mock->option->setPutMethod()->setSSLVerifyPeer(false)->setReturnTransfer(true);

        if (self::$staticMockResponse !== null || self::$staticMockErrorCode > 0) {
            if (self::$staticMockErrorCode > 0) {
                throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
            }
            return self::$staticMockResponse['body'];
        }

        return $mock->mockBody;
    }

    /**
     * Simulate PATCH request.
     *
     * @param string                $url
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return mixed
     */
    public static function patch(string $url, array $data = [], array $headers = []): mixed
    {
        $mock = new self($url);
        $mock->option->setCustomRequest('PATCH')->setSSLVerifyPeer(false)->setReturnTransfer(true);

        if (self::$staticMockResponse !== null || self::$staticMockErrorCode > 0) {
            if (self::$staticMockErrorCode > 0) {
                throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
            }
            return self::$staticMockResponse['body'];
        }

        return $mock->mockBody;
    }

    /**
     * Simulate DELETE request.
     *
     * @param string                $url
     * @param array<string, string> $headers
     *
     * @return mixed
     */
    public static function delete(string $url, array $headers = []): mixed
    {
        $mock = new self($url);
        $mock->option->setCustomRequest('DELETE')->setSSLVerifyPeer(false)->setReturnTransfer(true);

        if (self::$staticMockResponse !== null || self::$staticMockErrorCode > 0) {
            if (self::$staticMockErrorCode > 0) {
                throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
            }
            return self::$staticMockResponse['body'];
        }

        return $mock->mockBody;
    }

    /**
     * Simulate JSON request.
     *
     * @param string                $url
     * @param string                $method
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     * @param bool|null             $associative
     *
     * @return mixed Decoded JSON or raw string
     */
    public static function json(string $url, string $method = 'POST', array $data = [], array $headers = [], bool|null $associative = true): mixed
    {
        if (self::$staticMockErrorCode > 0) {
            throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
        }

        $body = self::$staticMockResponse['body'] ?? '';

        $decoded = json_decode($body, $associative);
        return ($decoded !== null) ? $decoded : $body;
    }

    /**
     * Simulate POST form data request.
     *
     * @param string                $url
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return mixed
     */
    public static function postForm(string $url, array $data = [], array $headers = []): mixed
    {
        return self::post($url, $data, $headers);
    }

    /**
     * Simulate request with Bearer token.
     *
     * @param string               $url
     * @param string               $token
     * @param string               $method
     * @param array<string, mixed> $data
     *
     * @return mixed
     */
    public static function withBearerToken(string $url, string $token, string $method = 'GET', array $data = []): mixed
    {
        return self::json($url, $method, $data, ['Authorization' => 'Bearer ' . $token]);
    }

    /**
     * Simulate request with Basic auth.
     *
     * @param string               $url
     * @param string               $username
     * @param string               $password
     * @param string               $method
     * @param array<string, mixed> $data
     *
     * @return mixed
     */
    public static function withBasicAuth(string $url, string $username, string $password, string $method = 'GET', array $data = []): mixed
    {
        if (self::$staticMockErrorCode > 0) {
            throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
        }

        return self::$staticMockResponse['body'] ?? '';
    }

    /**
     * Simulate generic request with full response array.
     *
     * @param string                $url
     * @param string                $method
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     *
     * @return array{status: int, headers: array, body: string, info: array}
     */
    public static function request(string $url, string $method = 'GET', array $data = [], array $headers = []): array
    {
        if (self::$staticMockErrorCode > 0) {
            throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
        }

        $statusCode = self::staticStatusCode();
        $responseHeaders = self::staticHeaders();
        $body = self::$staticMockResponse['body'] ?? '';

        return [
            'status' => $statusCode,
            'headers' => $responseHeaders,
            'body' => $body,
            'info' => ['http_code' => $statusCode],
        ];
    }

    /**
     * Simulate HEAD request.
     *
     * @param string $url
     *
     * @return array{headers: array, info: array}
     */
    public static function head(string $url): array
    {
        if (self::$staticMockErrorCode > 0) {
            throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
        }

        $statusCode = self::staticStatusCode();
        $responseHeaders = self::staticHeaders();

        return [
            'headers' => $responseHeaders,
            'info' => ['http_code' => $statusCode],
        ];
    }

    /**
     * Simulate OPTIONS request.
     *
     * @param string                $url
     * @param array<string, string> $headers
     *
     * @return array{allowed_methods: array, headers: array, info: array}
     */
    public static function options(string $url, array $headers = []): array
    {
        if (self::$staticMockErrorCode > 0) {
            throw new Exception('Curl error: ' . self::$staticMockErrorMessage);
        }

        $responseHeaders = self::staticHeaders();
        $allowed = [];

        if (isset($responseHeaders['Allow'])) {
            $allowed = array_map('trim', explode(',', $responseHeaders['Allow']));
        }

        return [
            'allowed_methods' => $allowed,
            'headers' => $responseHeaders,
            'info' => ['http_code' => self::staticStatusCode()],
        ];
    }
}
