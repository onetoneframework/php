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
 * MockClientURLLastTransferInformation
 *
 * Simulates ClientURLLastTransferInformation with configurable preset values.
 * No cURL functions are called; all data is supplied programmatically so
 * tests can assert on timing, status codes, sizes, and connection details
 * without making real HTTP requests.
 */
class MockClientURLLastTransferInformation
{
    private int $statusCode = 200;
    private string $effectiveURL = '';
    private string $contentType = 'text/html';
    private int $headerSize = 0;
    private int $requestSize = 0;
    private float $totalTime = 0.0;
    private float $nameLookupTime = 0.0;
    private float $connectTime = 0.0;
    private float $appConnectTime = 0.0;
    private float $preTransferTime = 0.0;
    private float $startTransferTime = 0.0;
    private float $redirectTime = 0.0;
    private int $redirectCount = 0;
    private string $redirectURL = '';
    private float $downloadedSize = 0.0;
    private float $uploadedSize = 0.0;
    private float $downloadSpeed = 0.0;
    private float $uploadSpeed = 0.0;
    private float $downloadContentLength = -1.0;
    private float $uploadContentLength = -1.0;
    private int $sslVerifyResult = 0;
    private string $primaryIP = '';
    private int $primaryPort = 0;
    private string $localIP = '';
    private int $localPort = 0;
    private int $numConnects = 0;
    private int $remoteTime = -1;
    private string $headerOutput = '';
    private int $httpVersion = 0;
    private int $protocol = 0;
    private string $scheme = 'https';
    private int $connectCode = 0;
    private int $lastConnectErrno = 0;
    private int $conditionUnmet = 0;

    /**
     * Sets the synthetic HTTP status code returned to callers.
     *
     * @param int $code HTTP status value.
     *
     * @return static
     */
    public function setStatusCode(int $code): static
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Sets the final URL after redirects, matching cURL effective URL metadata.
     *
     * @param string $url Absolute URL string.
     *
     * @return static
     */
    public function setEffectiveURL(string $url): static
    {
        $this->effectiveURL = $url;
        return $this;
    }

    /**
     * Sets the response Content-Type header value.
     *
     * @param string $type MIME type string.
     *
     * @return static
     */
    public function setContentType(string $type): static
    {
        $this->contentType = $type;
        return $this;
    }

    /**
     * Sets the byte length of received response headers.
     *
     * @param int $size Header block size in bytes.
     *
     * @return static
     */
    public function setHeaderSize(int $size): static
    {
        $this->headerSize = $size;
        return $this;
    }

    /**
     * Sets the byte length of the outbound request.
     *
     * @param int $size Request size in bytes.
     *
     * @return static
     */
    public function setRequestSize(int $size): static
    {
        $this->requestSize = $size;
        return $this;
    }

    /**
     * Sets total transfer duration in seconds.
     *
     * @param float $seconds Elapsed wall time for the full transfer.
     *
     * @return static
     */
    public function setTotalTime(float $seconds): static
    {
        $this->totalTime = $seconds;
        return $this;
    }

    /**
     * Sets the number of payload bytes downloaded.
     *
     * @param float $bytes Downloaded byte count.
     *
     * @return static
     */
    public function setDownloadedSize(float $bytes): static
    {
        $this->downloadedSize = $bytes;
        return $this;
    }

    /**
     * Sets the number of payload bytes uploaded.
     *
     * @param float $bytes Uploaded byte count.
     *
     * @return static
     */
    public function setUploadedSize(float $bytes): static
    {
        $this->uploadedSize = $bytes;
        return $this;
    }

    /**
     * Sets average download throughput.
     *
     * @param float $bytesPerSecond Bytes per second.
     *
     * @return static
     */
    public function setDownloadSpeed(float $bytesPerSecond): static
    {
        $this->downloadSpeed = $bytesPerSecond;
        return $this;
    }

    /**
     * Sets average upload throughput.
     *
     * @param float $bytesPerSecond Bytes per second.
     *
     * @return static
     */
    public function setUploadSpeed(float $bytesPerSecond): static
    {
        $this->uploadSpeed = $bytesPerSecond;
        return $this;
    }

    /**
     * Sets how many HTTP redirects were followed.
     *
     * @param int $count Redirect hop count.
     *
     * @return static
     */
    public function setRedirectCount(int $count): static
    {
        $this->redirectCount = $count;
        return $this;
    }

    /**
     * Sets the URL of the last redirect hop when applicable.
     *
     * @param string $url Redirect target URL.
     *
     * @return static
     */
    public function setRedirectURL(string $url): static
    {
        $this->redirectURL = $url;
        return $this;
    }

    /**
     * Sets the remote server IP address for the active connection.
     *
     * @param string $ip IPv4/IPv6 string.
     *
     * @return static
     */
    public function setPrimaryIP(string $ip): static
    {
        $this->primaryIP = $ip;
        return $this;
    }

    /**
     * Sets the remote TCP port for the active connection.
     *
     * @param int $port Port number.
     *
     * @return static
     */
    public function setPrimaryPort(int $port): static
    {
        $this->primaryPort = $port;
        return $this;
    }

    /**
     * Sets the local socket IP address used for the transfer.
     *
     * @param string $ip IPv4/IPv6 string.
     *
     * @return static
     */
    public function setLocalIP(string $ip): static
    {
        $this->localIP = $ip;
        return $this;
    }

    /**
     * Sets the local TCP port used for the transfer.
     *
     * @param int $port Port number.
     *
     * @return static
     */
    public function setLocalPort(int $port): static
    {
        $this->localPort = $port;
        return $this;
    }

    /**
     * Sets DNS lookup duration in seconds.
     *
     * @param float $seconds Resolver timing segment.
     *
     * @return static
     */
    public function setNameLookupTime(float $seconds): static
    {
        $this->nameLookupTime = $seconds;
        return $this;
    }

    /**
     * Sets TCP connection establishment time in seconds.
     *
     * @param float $seconds Connect timing segment.
     *
     * @return static
     */
    public function setConnectTime(float $seconds): static
    {
        $this->connectTime = $seconds;
        return $this;
    }

    /**
     * Sets TLS handshake duration in seconds.
     *
     * @param float $seconds App connect timing segment.
     *
     * @return static
     */
    public function setAppConnectTime(float $seconds): static
    {
        $this->appConnectTime = $seconds;
        return $this;
    }

    /**
     * Sets time from start until just before the transfer begins.
     *
     * @param float $seconds Pre-transfer timing segment.
     *
     * @return static
     */
    public function setPreTransferTime(float $seconds): static
    {
        $this->preTransferTime = $seconds;
        return $this;
    }

    /**
     * Sets time until the first response byte (TTFB).
     *
     * @param float $seconds Start-transfer timing segment.
     *
     * @return static
     */
    public function setStartTransferTime(float $seconds): static
    {
        $this->startTransferTime = $seconds;
        return $this;
    }

    /**
     * Sets cumulative time spent handling redirects.
     *
     * @param float $seconds Redirect timing segment.
     *
     * @return static
     */
    public function setRedirectTime(float $seconds): static
    {
        $this->redirectTime = $seconds;
        return $this;
    }

    /**
     * Sets the declared download Content-Length when known.
     *
     * @param float $bytes Expected payload size or -1 when unknown.
     *
     * @return static
     */
    public function setDownloadContentLength(float $bytes): static
    {
        $this->downloadContentLength = $bytes;
        return $this;
    }

    /**
     * Sets the declared upload Content-Length when known.
     *
     * @param float $bytes Expected upload size or -1 when unknown.
     *
     * @return static
     */
    public function setUploadContentLength(float $bytes): static
    {
        $this->uploadContentLength = $bytes;
        return $this;
    }

    /**
     * Sets how many connection attempts were created for the transfer.
     *
     * @param int $count Connection count.
     *
     * @return static
     */
    public function setNumConnects(int $count): static
    {
        $this->numConnects = $count;
        return $this;
    }

    /**
     * Sets the OpenSSL peer verification result code.
     *
     * @param int $result Verification bitmask or status code.
     *
     * @return static
     */
    public function setSSLVerifyResult(int $result): static
    {
        $this->sslVerifyResult = $result;
        return $this;
    }

    /**
     * Sets the raw response header block captured during the transfer.
     *
     * @param string $output Header text as returned by libcurl.
     *
     * @return static
     */
    public function setHeaderOutput(string $output): static
    {
        $this->headerOutput = $output;
        return $this;
    }

    /**
     * Sets the negotiated HTTP version identifier.
     *
     * @param int $version Numeric HTTP version marker.
     *
     * @return static
     */
    public function setHTTPVersion(int $version): static
    {
        $this->httpVersion = $version;
        return $this;
    }

    /**
     * Sets the underlying protocol identifier (e.g. HTTP family constant).
     *
     * @param int $protocol Protocol enum value.
     *
     * @return static
     */
    public function setProtocol(int $protocol): static
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * Sets URL scheme (http, https, etc.).
     *
     * @param string $scheme Lowercase scheme name.
     *
     * @return static
     */
    public function setScheme(string $scheme): static
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * Returns the synthetic HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Alias of {@see getStatusCode()} for API parity with libcurl helpers.
     *
     * @return int
     */
    public function getResponseCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns the configured effective URL string.
     *
     * @return string
     */
    public function getEffectiveURL(): string
    {
        return $this->effectiveURL;
    }

    /**
     * Returns the configured Content-Type header value.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Returns the stored response header byte length.
     *
     * @return int
     */
    public function getHeaderSize(): int
    {
        return $this->headerSize;
    }

    /**
     * Returns the stored request byte length.
     *
     * @return int
     */
    public function getRequestSize(): int
    {
        return $this->requestSize;
    }

    /**
     * Returns total transfer time in seconds.
     *
     * @return float
     */
    public function getTotalTransferTime(): float
    {
        return $this->totalTime;
    }

    /**
     * Returns total transfer time in milliseconds.
     *
     * @return float
     */
    public function getTotalTimeMs(): float
    {
        return $this->totalTime * 1000;
    }

    /**
     * Returns DNS lookup duration in seconds.
     *
     * @return float
     */
    public function getLookupNameTime(): float
    {
        return $this->nameLookupTime;
    }

    /**
     * Returns TCP connect duration in seconds.
     *
     * @return float
     */
    public function getConnectionTime(): float
    {
        return $this->connectTime;
    }

    /**
     * Returns TLS handshake duration in seconds.
     *
     * @return float
     */
    public function getAppConnectTime(): float
    {
        return $this->appConnectTime;
    }

    /**
     * Returns time from start until immediately before transfer begins.
     *
     * @return float
     */
    public function getPreTransferTime(): float
    {
        return $this->preTransferTime;
    }

    /**
     * Returns time until the first byte of the response body.
     *
     * @return float
     */
    public function getStartTransferTime(): float
    {
        return $this->startTransferTime;
    }

    public function getRedirectTime(): float
    {
        return $this->redirectTime;
    }

    public function getRedirectCount(): int
    {
        return $this->redirectCount;
    }

    public function getRedirectURL(): string
    {
        return $this->redirectURL;
    }

    public function getDownloadedSize(): float
    {
        return $this->downloadedSize;
    }

    public function getUploadedSize(): float
    {
        return $this->uploadedSize;
    }

    public function getAverageDownloadSpeed(): float
    {
        return $this->downloadSpeed;
    }

    public function getAverageUploadSpeed(): float
    {
        return $this->uploadSpeed;
    }

    public function getDownloadContentLength(): float
    {
        return $this->downloadContentLength;
    }

    public function getUploadContentLength(): float
    {
        return $this->uploadContentLength;
    }

    public function getSSLVerifyResult(): int
    {
        return $this->sslVerifyResult;
    }

    public function getPrimaryIP(): string
    {
        return $this->primaryIP;
    }

    public function getLastConnectionIPAddress(): string
    {
        return $this->primaryIP;
    }

    public function getPrimaryPort(): int
    {
        return $this->primaryPort;
    }

    public function getLastConnectionPortNumber(): int
    {
        return $this->primaryPort;
    }

    public function getLocalIP(): string
    {
        return $this->localIP;
    }

    public function getLocalPort(): int
    {
        return $this->localPort;
    }

    public function getCreatedConnectionCount(): int
    {
        return $this->numConnects;
    }

    public function getRemoteTime(): int
    {
        return $this->remoteTime;
    }

    public function getHeaderOutput(): string
    {
        return $this->headerOutput;
    }

    public function getHTTPVersion(): int
    {
        return $this->httpVersion;
    }

    public function getProtocol(): int
    {
        return $this->protocol;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getConnectCode(): int
    {
        return $this->connectCode;
    }

    public function getLastConnectFailureErrorNumber(): int
    {
        return $this->lastConnectErrno;
    }

    public function getConditionUnmet(): int
    {
        return $this->conditionUnmet;
    }

    public function getCertificateInfo(): mixed
    {
        return [];
    }

    public function getAllKnownCookies(): mixed
    {
        return [];
    }

    public function getFTPServerEntryPath(): string
    {
        return '';
    }

    public function getNextRTSPClientCSeq(): int
    {
        return 0;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function hasRedirect(): bool
    {
        return $this->redirectCount > 0;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    public function isServerError(): bool
    {
        return $this->statusCode >= 500;
    }

    /**
     * @return array{
     *  dns_lookup: float, 
     *  connect: float, 
     *  app_connect: float, 
     *  pre_transfer: float, 
     *  start_transfer: float, 
     *  redirect: float, 
     *  total: float
     * }
     */
    public function getTimingBreakdown(): array
    {
        return [
            'dns_lookup' => $this->nameLookupTime,
            'connect' => $this->connectTime,
            'app_connect' => $this->appConnectTime,
            'pre_transfer' => $this->preTransferTime,
            'start_transfer' => $this->startTransferTime,
            'redirect' => $this->redirectTime,
            'total' => $this->totalTime,
        ];
    }

    /**
     * @return array{
     *  url: string, 
     *  status_code: int, 
     *  content_type: string, 
     *  total_time: float,
     *  download_size: float, 
     *  upload_size: float, 
     *  download_speed: float, 
     *  upload_speed: float, 
     *  redirect_count: int, 
     *  primary_ip: string, 
     *  primary_port: int
     * }
     */
    public function getSummary(): array
    {
        return [
            'url' => $this->effectiveURL,
            'status_code' => $this->statusCode,
            'content_type' => $this->contentType,
            'total_time' => $this->totalTime,
            'download_size' => $this->downloadedSize,
            'upload_size' => $this->uploadedSize,
            'download_speed' => $this->downloadSpeed,
            'upload_speed' => $this->uploadSpeed,
            'redirect_count' => $this->redirectCount,
            'primary_ip' => $this->primaryIP,
            'primary_port' => $this->primaryPort,
        ];
    }

    /**
     * @return array{
     *  header_size: int, 
     *  request_size: int, 
     *  download_size: float, 
     *  upload_size: float, 
     *  download_content_length: float, 
     *  upload_content_length: float
     * }
     */
    public function getSizeInfo(): array
    {
        return [
            'header_size' => $this->headerSize,
            'request_size' => $this->requestSize,
            'download_size' => $this->downloadedSize,
            'upload_size' => $this->uploadedSize,
            'download_content_length' => $this->downloadContentLength,
            'upload_content_length' => $this->uploadContentLength,
        ];
    }

    /**
     * @return array{primary_ip: string, primary_port: int, local_ip: string, local_port: int, num_connects: int}
     */
    public function getConnectionInfo(): array
    {
        return [
            'primary_ip' => $this->primaryIP,
            'primary_port' => $this->primaryPort,
            'local_ip' => $this->localIP,
            'local_port' => $this->localPort,
            'num_connects' => $this->numConnects,
        ];
    }

    public function getAll(): array
    {
        return [
            'url' => $this->effectiveURL,
            'http_code' => $this->statusCode,
            'content_type' => $this->contentType,
            'header_size' => $this->headerSize,
            'request_size' => $this->requestSize,
            'total_time' => $this->totalTime,
            'namelookup_time' => $this->nameLookupTime,
            'connect_time' => $this->connectTime,
            'appconnect_time' => $this->appConnectTime,
            'pretransfer_time' => $this->preTransferTime,
            'starttransfer_time' => $this->startTransferTime,
            'redirect_count' => $this->redirectCount,
            'redirect_url' => $this->redirectURL,
            'redirect_time' => $this->redirectTime,
            'size_download' => $this->downloadedSize,
            'size_upload' => $this->uploadedSize,
            'speed_download' => $this->downloadSpeed,
            'speed_upload' => $this->uploadSpeed,
            'download_content_length' => $this->downloadContentLength,
            'upload_content_length' => $this->uploadContentLength,
            'ssl_verify_result' => $this->sslVerifyResult,
            'primary_ip' => $this->primaryIP,
            'primary_port' => $this->primaryPort,
            'local_ip' => $this->localIP,
            'local_port' => $this->localPort,
            'num_connects' => $this->numConnects,
            'http_version' => $this->httpVersion,
            'protocol' => $this->protocol,
            'scheme' => $this->scheme,
        ];
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $count = count($units) - 1;

        while ($bytes >= 1024 && $i < $count) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Returns the downloaded size formatted as a human-readable string (e.g., "1.5 MB").
     *
     * @return string
     */
    public function getFormattedDownloadSize(): string
    {
        return self::formatBytes((int) $this->downloadedSize);
    }

    /**
     * Returns the uploaded size formatted as a human-readable string (e.g., "1.5 MB").
     *
     * @return string
     */
    public function getFormattedUploadSize(): string
    {
        return self::formatBytes((int) $this->uploadedSize);
    }
}
