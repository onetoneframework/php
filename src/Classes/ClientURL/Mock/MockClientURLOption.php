<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Mock;

/**
 * MockClientURLOption
 *
 * A drop-in replacement for ClientURLOption that records every option
 * set on it without calling any real cURL function. Useful in unit tests
 * to assert which options were configured without making HTTP requests.
 */
class MockClientURLOption
{
    /** @var array<string, string> Recorded headers (name => value) */
    public array $headers = [];

    /** @var array<int|string, mixed> Recorded cURL option constants => values */
    public array $curlOptions = [];

    /** @var array<string, mixed> Application-level options */
    public array $options = [];

    /** @var string|null The URL that was configured */
    private ?string $url = null;

    /** @var string HTTP method (GET, POST, PUT, etc.) */
    private string $method = 'GET';

    /** @var mixed POST body or fields */
    private mixed $postField = null;

    /** @var bool Whether return-transfer is enabled */
    private bool $returnTransfer = false;

    /** @var bool Whether header output is included in the response */
    private bool $returnHeader = false;

    /** @var bool Whether SSL peer verification is enabled */
    private bool $sslVerifyPeer = true;

    /** @var bool|int Whether SSL host verification is enabled */
    private bool|int $sslVerifyHost = true;

    /** @var bool Whether redirects are followed */
    private bool $followRedirects = false;

    /** @var int|null Timeout in seconds */
    private ?int $timeout = null;

    /** @var int|null Connection timeout in seconds */
    private ?int $connectTimeout = null;

    /** @var string|null Proxy address */
    private ?string $proxy = null;

    /** @var string|null Cookie file path */
    private ?string $cookieFile = null;

    /** @var string|null Cookie jar path */
    private ?string $cookieJar = null;

    /** @var string|null User-Agent string */
    private ?string $userAgent = null;

    public function setURL(string $url): static
    {
        $this->url = $url;
        $this->curlOptions[CURLOPT_URL] = $url;
        return $this;
    }

    public function setGetMethod(bool $enable = true): static
    {
        $this->method = 'GET';
        $this->curlOptions[CURLOPT_HTTPGET] = $enable;
        return $this;
    }

    public function setPostMethod(bool $enable = true): static
    {
        $this->method = 'POST';
        $this->curlOptions[CURLOPT_POST] = $enable;
        return $this;
    }

    public function setPutMethod(): static
    {
        $this->method = 'PUT';
        $this->curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
        return $this;
    }

    public function setCustomRequest(string $method): static
    {
        $this->method = strtoupper($method);
        $this->curlOptions[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
        return $this;
    }

    public function setPostField(mixed $data, bool $urlEncode = false): static
    {
        $this->postField = $data;
        $this->curlOptions[CURLOPT_POSTFIELDS] = $data;
        $this->options['post_fields'] = $data;
        return $this;
    }

    public function setPostFields(mixed $data): static
    {
        return $this->setPostField($data);
    }

    public function setReturnTransfer(bool $enable): static
    {
        $this->returnTransfer = $enable;
        $this->curlOptions[CURLOPT_RETURNTRANSFER] = $enable;
        return $this;
    }

    public function setReturnHeader(bool $enable): static
    {
        $this->returnHeader = $enable;
        $this->curlOptions[CURLOPT_HEADER] = $enable;
        return $this;
    }

    public function setSSLVerifyPeer(bool $enable): static
    {
        $this->sslVerifyPeer = $enable;
        $this->curlOptions[CURLOPT_SSL_VERIFYPEER] = $enable;
        return $this;
    }

    public function setSSLVerifyHost(bool|int $enable): static
    {
        // Match the production fix: bool → int (true → 2, false → 0)
        if (is_bool($enable)) {
            $this->sslVerifyHost = $enable ? 2 : 0;
        } else {
            $this->sslVerifyHost = $enable;
        }

        $this->curlOptions[CURLOPT_SSL_VERIFYHOST] = $this->sslVerifyHost;
        return $this;
    }

    public function setFollowRedirects(bool $enable): static
    {
        $this->followRedirects = $enable;
        $this->curlOptions[CURLOPT_FOLLOWLOCATION] = $enable;
        return $this;
    }

    public function setFollowLocationHeader(): static
    {
        return $this->setFollowRedirects(true);
    }

    public function setTimeout(int $seconds): static
    {
        $this->timeout = $seconds;
        $this->curlOptions[CURLOPT_TIMEOUT] = $seconds;
        return $this;
    }

    public function setConnectionTimeout(int $seconds): static
    {
        $this->connectTimeout = $seconds;
        $this->curlOptions[CURLOPT_CONNECTTIMEOUT] = $seconds;
        return $this;
    }

    public function setAutoReferer(bool $enable): static
    {
        $this->curlOptions[CURLOPT_AUTOREFERER] = $enable;
        return $this;
    }

    public function setNobody(bool $enable): static
    {
        $this->curlOptions[CURLOPT_NOBODY] = $enable;
        return $this;
    }

    public function setHeader(string $name, string $value): static
    {
        $this->headers[$name] = $value;

        // Rebuild formatted headers array
        $formatted = [];
        foreach ($this->headers as $k => $v) {
            $formatted[] = "{$k}: {$v}";
        }
        $this->curlOptions[CURLOPT_HTTPHEADER] = $formatted;

        return $this;
    }

    public function setContentTypeApplicationJson(): static
    {
        return $this->setHeader('Content-Type', 'application/json');
    }

    public function setContentTypeFormUrlEncoded(): static
    {
        return $this->setHeader('Content-Type', 'application/x-www-form-urlencoded');
    }

    public function setBasicAuthentication(string $username, string $password): static
    {
        $this->curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        $this->curlOptions[CURLOPT_USERPWD] = "{$username}:{$password}";
        return $this;
    }

    public function setDigestAuthentication(string $username, string $password): static
    {
        $this->curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
        $this->curlOptions[CURLOPT_USERPWD] = "{$username}:{$password}";
        return $this;
    }

    public function setFileHandler(mixed $handle): static
    {
        $this->curlOptions[CURLOPT_FILE] = $handle;
        return $this;
    }

    public function setProxy(string $proxy): static
    {
        $this->proxy = $proxy;
        $this->curlOptions[CURLOPT_PROXY] = $proxy;
        return $this;
    }

    public function setProxyUserPassword(string $credentials): static
    {
        $this->curlOptions[CURLOPT_PROXYUSERPWD] = $credentials;
        return $this;
    }

    public function setCookieFile(string $path): static
    {
        $this->cookieFile = $path;
        $this->curlOptions[CURLOPT_COOKIEFILE] = $path;
        return $this;
    }

    public function setCookieJar(string $path): static
    {
        $this->cookieJar = $path;
        $this->curlOptions[CURLOPT_COOKIEJAR] = $path;
        return $this;
    }

    public function setUserAgent(string $agent): static
    {
        $this->userAgent = $agent;
        $this->curlOptions[CURLOPT_USERAGENT] = $agent;
        return $this;
    }

    public function setEncoding(string $encoding): static
    {
        $this->curlOptions[CURLOPT_ENCODING] = $encoding;
        return $this;
    }

    public function setMaxRedirects(int $max): static
    {
        $this->curlOptions[CURLOPT_MAXREDIRS] = $max;
        return $this;
    }

    public function setVerbose(bool $enable = true): static
    {
        $this->curlOptions[CURLOPT_VERBOSE] = $enable;
        return $this;
    }

    public function setHTTPVersion(int $version): static
    {
        $this->curlOptions[CURLOPT_HTTP_VERSION] = $version;
        return $this;
    }

    public function setTimeoutMs(int $ms): static
    {
        $this->curlOptions[CURLOPT_TIMEOUT_MS] = $ms;
        return $this;
    }

    public function setConnectionTimeoutMs(int $ms): static
    {
        $this->curlOptions[CURLOPT_CONNECTTIMEOUT_MS] = $ms;
        return $this;
    }

    public function setTCPKeepAlive(bool $enable): static
    {
        $this->curlOptions[CURLOPT_TCP_KEEPALIVE] = (int) $enable;
        return $this;
    }

    public function setTCPNoDelay(bool $enable = true): static
    {
        $this->curlOptions[CURLOPT_TCP_NODELAY] = (int) $enable;
        return $this;
    }

    public function setWriteFunction(callable $callback): static
    {
        $this->curlOptions[CURLOPT_WRITEFUNCTION] = $callback;
        return $this;
    }

    public function setProgressFunction(callable $callback): static
    {
        $this->curlOptions[CURLOPT_NOPROGRESS] = false;
        $this->curlOptions[CURLOPT_PROGRESSFUNCTION] = $callback;
        return $this;
    }

    public function setLowSpeedLimit(int $bytesPerSecond, int $seconds): static
    {
        $this->curlOptions[CURLOPT_LOW_SPEED_LIMIT] = $bytesPerSecond;
        $this->curlOptions[CURLOPT_LOW_SPEED_TIME] = $seconds;
        return $this;
    }

    public function setMaxFileSize(int $bytes): static
    {
        $this->curlOptions[CURLOPT_MAXFILESIZE] = $bytes;
        return $this;
    }

    public function setOption(mixed $key, mixed $value): bool
    {
        $this->curlOptions[$key] = $value;
        return true;
    }

    public function setMethod(string $method): bool
    {
        $this->method = strtoupper($method);
        return true;
    }

    public function clearOptions(): static
    {
        $this->options = [];
        return $this;
    }

    public function clearCurlOptions(): static
    {
        $this->curlOptions = [];
        return $this;
    }

    public function toArray(): array
    {
        return $this->curlOptions;
    }

    public function getURL(): ?string
    {
        return $this->url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPostField(): mixed
    {
        return $this->postField;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    public function getCurlOption(int $option): mixed
    {
        return $this->curlOptions[$option] ?? null;
    }

    public function getOptions(): array
    {
        return $this->curlOptions;
    }

    public function isReturnTransfer(): bool
    {
        return $this->returnTransfer;
    }

    public function isReturnHeader(): bool
    {
        return $this->returnHeader;
    }

    public function isSSLVerifyPeer(): bool
    {
        return $this->sslVerifyPeer;
    }

    public function getSSLVerifyHost(): bool|int
    {
        return $this->sslVerifyHost;
    }

    public function isFollowRedirects(): bool
    {
        return $this->followRedirects;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }

    public function getConnectionTimeout(): ?int
    {
        return $this->connectTimeout;
    }

    public function getProxy(): ?string
    {
        return $this->proxy;
    }

    public function getCookieFile(): ?string
    {
        return $this->cookieFile;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /** Reset all recorded state (useful between test cases). */
    public function reset(): void
    {
        $this->headers = [];
        $this->curlOptions = [];
        $this->options = [];
        $this->url = null;
        $this->method = 'GET';
        $this->postField = null;
        $this->returnTransfer = false;
        $this->returnHeader = false;
        $this->sslVerifyPeer = true;
        $this->sslVerifyHost = true;
        $this->followRedirects = false;
        $this->timeout = null;
        $this->connectTimeout = null;
        $this->proxy = null;
        $this->cookieFile = null;
        $this->cookieJar = null;
        $this->userAgent = null;
    }
}
