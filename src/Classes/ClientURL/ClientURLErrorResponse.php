<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes;

use function in_array;

/**
 * Class ClientURLErrorResponse
 *
 * @package Clover\Classes
 */
class ClientURLErrorResponse
{
	/** @var array<string> */
	private $curlErrorMessages = [
		1 => 'CURLE_UNSUPPORTED_PROTOCOL',
		2 => 'CURLE_FAILED_INIT',
		3 => 'CURLE_URL_MALFORMAT',
		4 => 'CURLE_URL_MALFORMAT_USER',
		5 => 'CURLE_COULDNT_RESOLVE_PROXY',
		6 => 'CURLE_COULDNT_RESOLVE_HOST',
		7 => 'CURLE_COULDNT_CONNECT',
		8 => 'CURLE_FTP_WEIRD_SERVER_REPLY',
		9 => 'CURLE_FTP_ACCESS_DENIED',
		10 => 'CURLE_FTP_USER_PASSWORD_INCORRECT',
		11 => 'CURLE_FTP_WEIRD_PASS_REPLY',
		12 => 'CURLE_FTP_WEIRD_USER_REPLY',
		13 => 'CURLE_FTP_WEIRD_PASV_REPLY',
		14 => 'CURLE_FTP_WEIRD_227_FORMAT',
		15 => 'CURLE_FTP_CANT_GET_HOST',
		16 => 'CURLE_FTP_CANT_RECONNECT',
		17 => 'CURLE_FTP_COULDNT_SET_BINARY',
		18 => 'CURLE_FTP_PARTIAL_FILE or CURLE_PARTIAL_FILE',
		19 => 'CURLE_FTP_COULDNT_RETR_FILE',
		20 => 'CURLE_FTP_WRITE_ERROR',
		21 => 'CURLE_FTP_QUOTE_ERROR',
		22 => 'CURLE_HTTP_NOT_FOUND or CURLE_HTTP_RETURNED_ERROR',
		23 => 'CURLE_WRITE_ERROR',
		24 => 'CURLE_MALFORMAT_USER',
		25 => 'CURLE_FTP_COULDNT_STOR_FILE',
		26 => 'CURLE_READ_ERROR',
		27 => 'CURLE_OUT_OF_MEMORY',
		28 => 'CURLE_OPERATION_TIMEDOUT or CURLE_OPERATION_TIMEOUTED',
		29 => 'CURLE_FTP_COULDNT_SET_ASCII',
		30 => 'CURLE_FTP_PORT_FAILED',
		31 => 'CURLE_FTP_COULDNT_USE_REST',
		32 => 'CURLE_FTP_COULDNT_GET_SIZE',
		33 => 'CURLE_HTTP_RANGE_ERROR',
		34 => 'CURLE_HTTP_POST_ERROR',
		35 => 'CURLE_SSL_CONNECT_ERROR',
		36 => 'CURLE_BAD_DOWNLOAD_RESUME or CURLE_FTP_BAD_DOWNLOAD_RESUME',
		37 => 'CURLE_FILE_COULDNT_READ_FILE',
		38 => 'CURLE_LDAP_CANNOT_BIND',
		39 => 'CURLE_LDAP_SEARCH_FAILED',
		40 => 'CURLE_LIBRARY_NOT_FOUND',
		41 => 'CURLE_FUNCTION_NOT_FOUND',
		42 => 'CURLE_ABORTED_BY_CALLBACK',
		43 => 'CURLE_BAD_FUNCTION_ARGUMENT',
		44 => 'CURLE_BAD_CALLING_ORDER',
		45 => 'CURLE_HTTP_PORT_FAILED',
		46 => 'CURLE_BAD_PASSWORD_ENTERED',
		47 => 'CURLE_TOO_MANY_REDIRECTS',
		48 => 'CURLE_UNKNOWN_TELNET_OPTION',
		49 => 'CURLE_TELNET_OPTION_SYNTAX',
		50 => 'CURLE_OBSOLETE',
		51 => 'CURLE_SSL_PEER_CERTIFICATE',
		52 => 'CURLE_GOT_NOTHING',
		53 => 'CURLE_SSL_ENGINE_NOTFOUND',
		54 => 'CURLE_SSL_ENGINE_SETFAILED',
		55 => 'CURLE_SEND_ERROR',
		56 => 'CURLE_RECV_ERROR',
		57 => 'CURLE_SHARE_IN_USE',
		58 => 'CURLE_SSL_CERTPROBLEM',
		59 => 'CURLE_SSL_CIPHER',
		60 => 'CURLE_SSL_CACERT',
		61 => 'CURLE_BAD_CONTENT_ENCODING',
		62 => 'CURLE_LDAP_INVALID_URL',
		63 => 'CURLE_FILESIZE_EXCEEDED',
		64 => 'CURLE_FTP_SSL_FAILED',
		65 => 'CURLE_SEND_FAIL_REWIND',
		66 => 'CURLE_SSL_ENGINE_INITFAILED',
		67 => 'CURLE_LOGIN_DENIED',
		68 => 'CURLE_TFTP_NOTFOUND',
		69 => 'CURLE_TFTP_PERM',
		70 => 'CURLE_REMOTE_DISK_FULL',
		71 => 'CURLE_TFTP_ILLEGAL',
		72 => 'CURLE_TFTP_UNKNOWNID',
		73 => 'CURLE_REMOTE_FILE_EXISTS',
		74 => 'CURLE_TFTP_NOSUCHUSER',
		75 => 'CURLE_CONV_FAILED',
		76 => 'CURLE_CONV_REQD',
		77 => 'CURLE_SSL_CACERT_BADFILE',
		78 => 'CURLE_REMOTE_FILE_NOT_FOUND',
		79 => 'CURLE_SSH',
		80 => 'CURLE_SSL_SHUTDOWN_FAILED',
		81 => 'CURLE_AGAIN',
		82 => 'CURLE_SSL_CRL_BADFILE',
		83 => 'CURLE_SSL_ISSUER_ERROR',
		84 => 'CURLE_FTP_PRET_FAILED',
		85 => 'CURLE_RTSP_CSEQ_ERROR',
		86 => 'CURLE_RTSP_SESSION_ERROR',
		87 => 'CURLE_FTP_BAD_FILE_LIST',
		88 => 'CURLE_CHUNK_FAILED',
		89 => 'CURLE_NO_CONNECTION_AVAILABLE',
		90 => 'CURLE_SSL_PINNEDPUBKEYNOTMATCH',
		91 => 'CURLE_SSL_INVALIDCERTSTATUS',
		92 => 'CURLE_HTTP2_STREAM',
		93 => 'CURLE_RECURSIVE_API_CALL',
		94 => 'CURLE_AUTH_ERROR',
		95 => 'CURLE_HTTP3',
		96 => 'CURLE_QUIC_CONNECT_ERROR',
		97 => 'CURLE_PROXY',
		98 => 'CURLE_SSL_CLIENTCERT',
		99 => 'CURLE_UNRECOVERABLE_POLL',
	];

	/** @var array<int, string> */
	private array $httpStatusMessages = [
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
		418 => "I'm a Teapot",
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
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
	 * Get error message from code
	 * 
	 * @param int $code cURL error code to retrieve the corresponding error message for, which can be used to understand the nature of the error that occurred during a cURL request
	 * 
	 * @return string|null The error message corresponding to the provided cURL error code, or null if the code is not recognized in the predefined list of cURL error messages
	 */
	public function getErrorMessageFromCode(int $code): ?string
	{
		return $this->curlErrorMessages[$code] ?? null;
	}

	/**
	 * Get HTTP status message
	 * 
	 * @param int $code HTTP status code to retrieve the corresponding status message for, which can be used to understand the result of an HTTP request and the reason for any errors that may have occurred based on the standard HTTP status codes and their associated messages
	 * 
	 * @return string|null The HTTP status message corresponding to the provided status code, or null if the code is not recognized in the predefined list of HTTP status messages
	 */
	public function getHTTPStatusMessage(int $code): ?string
	{
		return $this->httpStatusMessages[$code] ?? null;
	}

	/**
	 * Check if error is recoverable
	 * 
	 * @param int $code cURL error code to check for recoverability, which can help determine whether the error that occurred during a cURL request is likely to be temporary and can be resolved by retrying the request or if it indicates a more permanent issue that may require further investigation or changes to the request configuration
	 * 
	 * @return bool True if the error code is considered recoverable, meaning that the error is likely to be temporary and may be resolved by retrying the request or making adjustments to the request configuration, or false if the error code is not in the predefined list of recoverable error codes, which may indicate a more permanent issue that requires further investigation or changes to the request configuration
	 */
	public function isRecoverable(int $code): bool
	{
		$recoverable = [6, 7, 18, 28, 35, 52, 55, 56, 81, 89, 92];
		return in_array($code, $recoverable);
	}

	/**
	 * Check if error is SSL related
	 * 
	 * @param int $code cURL error code to check for SSL relation, which can help determine whether the error that occurred during a cURL request is related to SSL/TLS issues, such as problems with the SSL certificate, SSL handshake failures, or other SSL-related errors that may require checking the SSL configuration, verifying the SSL certificate, or ensuring that the server supports the required TLS version for secure communication
	 * 
	 * @return bool True if the error code is considered SSL related, meaning that the error is likely to be associated with SSL/TLS issues and may require checking the SSL configuration, verifying the SSL certificate, or ensuring that the server supports the required TLS version for secure communication, or false if the error code is not in the predefined list of SSL-related error codes, which may indicate that the error is not directly related to SSL/TLS issues and may require a different approach for troubleshooting and resolution
	 */
	public function isSSLError(int $code): bool
	{
		$sslErrors = [35, 51, 53, 54, 58, 59, 60, 66, 77, 80, 82, 83, 90, 91, 98];
		return in_array($code, $sslErrors);
	}

	/**
	 * Check if error is network related
	 * 
	 * @param int $code cURL error code to check for network relation, which can help determine whether the error that occurred during a cURL request is related to network issues, such as problems with the network connection, DNS resolution failures, or other network-related errors that may require checking the network connection, verifying the URL, or ensuring that the server is reachable and responsive for successful communication
	 * 
	 * @return bool True if the error code is considered network related, meaning that the error is likely to be associated with network issues and may require checking the network connection, verifying the URL, or ensuring that the server is reachable and responsive for successful communication, or false if the error code is not in the predefined list of network-related error codes, which may indicate that the error is not directly related to network issues and may require a different approach for troubleshooting and resolution
	 */
	public function isNetworkError(int $code): bool
	{
		$networkErrors = [5, 6, 7, 28, 45, 52, 55, 56, 89, 96, 97];
		return in_array($code, $networkErrors);
	}

	/**
	 * Get suggested action for error
	 * 
	 * @param int $code cURL error code to get the suggested action for, which can provide guidance on how to address the error that occurred during a cURL request based on common issues associated with specific error codes, such as checking the network connection for connection-related errors, verifying the URL for resolution-related errors, or checking SSL configuration for SSL-related errors, among other potential actions that may help resolve the issue and successfully complete the cURL request
	 * 
	 * @return string A suggested action or recommendation for addressing the error corresponding to the provided cURL error code, which can help guide troubleshooting efforts and provide potential solutions for resolving the issue that occurred during a cURL request based on common issues associated with specific error codes
	 */
	public function getSuggestedAction(int $code): string
	{
		return match ($code) {
			1 => 'Check the URL protocol, it may not be supported',
			2 => 'cURL initialization failed, check cURL installation',
			3 => 'The URL is malformed, verify URL format',
			5 => 'Could not resolve proxy, check proxy configuration',
			6, 7 => 'Check network connection and URL',
			9, 10, 67 => 'Check FTP/server authentication credentials',
			22 => 'Server returned an HTTP error, check URL and permissions',
			23, 26 => 'File I/O error, check disk space and permissions',
			27 => 'Out of memory, reduce request size or increase available memory',
			28 => 'Increase timeout or check server response time',
			33 => 'HTTP range request error, check Range header values',
			35, 51, 58, 59, 60, 77 => 'Check SSL certificate configuration',
			47 => 'Reduce redirect limit or check for redirect loops',
			52 => 'Server returned empty response, check server status',
			55, 56 => 'Network transmission error, retry the request',
			61 => 'Bad content encoding, check Accept-Encoding header',
			63 => 'File size exceeded maximum allowed, check CURLOPT_MAXFILESIZE',
			66 => 'SSL engine initialization failed, check OpenSSL installation',
			79 => 'SSH error, check SSH keys and server configuration',
			80 => 'SSL shutdown failed, the connection may have been interrupted',
			82 => 'SSL CRL file is invalid, check CRL configuration',
			83 => 'SSL issuer verification failed, check CA certificate chain',
			89 => 'No connection available in the pool, increase pool size or retry',
			90 => 'SSL public key pinning mismatch, verify server certificate',
			92 => 'HTTP/2 stream error, retry or fall back to HTTP/1.1',
			94 => 'Authentication error, check credentials or auth method',
			95, 96 => 'HTTP/3 or QUIC connection error, fall back to HTTP/2 or HTTP/1.1',
			97 => 'Proxy error, check proxy configuration and connectivity',
			98 => 'SSL client certificate required, provide client certificate',
			default => 'Check cURL configuration and server status'
		};
	}

	/**
	 * Get all error codes
	 * 
	 * @return array<int> List of all cURL error codes defined in the class, which can be used to reference and understand the various error codes that may be encountered during cURL requests and their corresponding messages for troubleshooting and resolution purposes
	 */
	public function getAllErrorCodes(): array
	{
		return array_keys($this->curlErrorMessages);
	}

	/**
	 * Get error details
	 * 
	 * @param int $code cURL error code to get the detailed information for, which can provide a comprehensive overview of the error that occurred during a cURL request, including the error message, whether the error is recoverable, if it is related to SSL or network issues, and a suggested action for addressing the error based on common issues associated with specific error codes, which can help guide troubleshooting efforts and provide potential solutions for resolving the issue that occurred during a cURL request
	 * 
	 * @return array{
	 * 	code: int, 
	 * 	message: ?string, 
	 * 	recoverable: bool, 
	 * 	ssl_related: bool, 
	 * 	network_related: bool, 
	 * 	suggested_action: string
	 * }
	 */
	public function getErrorDetails(int $code): array
	{
		return [
			'code' => $code,
			'message' => $this->getErrorMessageFromCode($code),
			'recoverable' => $this->isRecoverable($code),
			'ssl_related' => $this->isSSLError($code),
			'network_related' => $this->isNetworkError($code),
			'suggested_action' => $this->getSuggestedAction($code),
			'category' => $this->getErrorCategory($code),
			'severity' => $this->getErrorSeverity($code),
		];
	}

	/**
	 * Check if error is authentication related
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error is related to authentication failures
	 */
	public function isAuthError(int $code): bool
	{
		$authErrors = [9, 10, 46, 67, 94];
		return in_array($code, $authErrors);
	}

	/**
	 * Check if error is a timeout error
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error is a timeout
	 */
	public function isTimeoutError(int $code): bool
	{
		return $code === 28;
	}

	/**
	 * Check if error is FTP related
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error is related to FTP operations
	 */
	public function isFTPError(int $code): bool
	{
		$ftpErrors = [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19, 20, 21, 25, 29, 30, 31, 32, 64, 68, 69, 70, 71, 72, 73, 74, 84, 87];
		return in_array($code, $ftpErrors);
	}

	/**
	 * Check if error is DNS related
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error is related to DNS resolution
	 */
	public function isDNSError(int $code): bool
	{
		$dnsErrors = [5, 6];
		return in_array($code, $dnsErrors);
	}

	/**
	 * Check if error is HTTP/2 or HTTP/3 protocol related
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error is related to HTTP/2 or HTTP/3 protocols
	 */
	public function isHTTP2OrHTTP3Error(int $code): bool
	{
		$protocolErrors = [92, 95, 96];
		return in_array($code, $protocolErrors);
	}

	/**
	 * Check if error is proxy related
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error is related to proxy issues
	 */
	public function isProxyError(int $code): bool
	{
		$proxyErrors = [5, 97];
		return in_array($code, $proxyErrors);
	}

	/**
	 * Check if error is file I/O related
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error is related to file I/O operations
	 */
	public function isFileIOError(int $code): bool
	{
		$fileErrors = [23, 26, 37, 63, 70, 73, 78];
		return in_array($code, $fileErrors);
	}

	/**
	 * Get error category as a string identifier
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return string Category name such as 'ssl', 'network', 'auth', 'ftp', 'dns', 'proxy', 'protocol', 'file_io', or 'other'
	 */
	public function getErrorCategory(int $code): string
	{
		if ($this->isSSLError($code)) {
			return 'ssl';
		} else if ($this->isDNSError($code)) {
			return 'dns';
		} else if ($this->isAuthError($code)) {
			return 'auth';
		} else if ($this->isProxyError($code)) {
			return 'proxy';
		} else if ($this->isHTTP2OrHTTP3Error($code)) {
			return 'protocol';
		} else if ($this->isFTPError($code)) {
			return 'ftp';
		} else if ($this->isFileIOError($code)) {
			return 'file_io';
		} else if ($this->isNetworkError($code)) {
			return 'network';
		} else if ($this->isTimeoutError($code)) {
			return 'timeout';
		}

		return 'other';
	}

	/**
	 * Get error severity level
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return string Severity level: 'low', 'medium', 'high', or 'critical'
	 */
	public function getErrorSeverity(int $code): string
	{
		if ($this->isRecoverable($code)) {
			return 'low';
		}

		if ($this->isTimeoutError($code) || $this->isDNSError($code)) {
			return 'medium';
		}

		if ($this->isSSLError($code) || $this->isAuthError($code)) {
			return 'high';
		}

		return 'critical';
	}

	/**
	 * Get suggested retry delay in milliseconds based on error code
	 * 
	 * @param int $code cURL error code
	 * @param int $attemptNumber Current retry attempt number (1-based), used for exponential backoff
	 * 
	 * @return int Suggested delay in milliseconds before retrying the request
	 */
	public function getRetryDelay(int $code, int $attemptNumber = 1): int
	{
		$baseDelay = match (true) {
			$this->isTimeoutError($code) => 5000,
			$this->isDNSError($code) => 2000,
			$this->isNetworkError($code) => 1000,
			$this->isSSLError($code) => 3000,
			$this->isHTTP2OrHTTP3Error($code) => 500,
			default => 1000,
		};

		return (int) ($baseDelay * pow(2, $attemptNumber - 1));
	}

	/**
	 * Get maximum recommended retry count for an error code
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return int Maximum number of retries recommended, 0 if retrying is not recommended
	 */
	public function getMaxRetries(int $code): int
	{
		if (!$this->isRecoverable($code)) {
			return 0;
		}

		return match ($code) {
			28 => 2,
			6, 7 => 3,
			52, 55, 56, 81 => 5,
			89, 92 => 3,
			default => 3,
		};
	}

	/**
	 * Check if an HTTP status code indicates success (2xx)
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return bool True if the status code is in the 2xx range
	 */
	public function isHTTPSuccess(int $code): bool
	{
		return $code >= 200 && $code < 300;
	}

	/**
	 * Check if an HTTP status code indicates a redirect (3xx)
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return bool True if the status code is in the 3xx range
	 */
	public function isHTTPRedirect(int $code): bool
	{
		return $code >= 300 && $code < 400;
	}

	/**
	 * Check if an HTTP status code indicates a client error (4xx)
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return bool True if the status code is in the 4xx range
	 */
	public function isHTTPClientError(int $code): bool
	{
		return $code >= 400 && $code < 500;
	}

	/**
	 * Check if an HTTP status code indicates a server error (5xx)
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return bool True if the status code is in the 5xx range
	 */
	public function isHTTPServerError(int $code): bool
	{
		return $code >= 500 && $code < 600;
	}

	/**
	 * Check if an HTTP status code indicates an informational response (1xx)
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return bool True if the status code is in the 1xx range
	 */
	public function isHTTPInformational(int $code): bool
	{
		return $code >= 100 && $code < 200;
	}

	/**
	 * Get HTTP status category as a string identifier
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return string Category name: 'informational', 'success', 'redirect', 'client_error', 'server_error', or 'unknown'
	 */
	public function getHTTPStatusCategory(int $code): string
	{
		return match (true) {
			$this->isHTTPInformational($code) => 'informational',
			$this->isHTTPSuccess($code) => 'success',
			$this->isHTTPRedirect($code) => 'redirect',
			$this->isHTTPClientError($code) => 'client_error',
			$this->isHTTPServerError($code) => 'server_error',
			default => 'unknown',
		};
	}

	/**
	 * Check if an HTTP status code is retryable
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return bool True if the HTTP status code suggests the request may succeed on retry
	 */
	public function isHTTPRetryable(int $code): bool
	{
		$retryable = [408, 425, 429, 500, 502, 503, 504];
		return in_array($code, $retryable);
	}

	/**
	 * Get suggested HTTP retry-after delay in seconds
	 * 
	 * @param int $code HTTP status code
	 * @param int $attemptNumber Current retry attempt number (1-based)
	 * 
	 * @return int Suggested delay in seconds, 0 if retry is not recommended
	 */
	public function getHTTPRetryAfter(int $code, int $attemptNumber = 1): int
	{
		if (!$this->isHTTPRetryable($code)) {
			return 0;
		}

		$baseDelay = match ($code) {
			429 => 60,
			503 => 30,
			408 => 5,
			502, 504 => 10,
			500 => 15,
			default => 5,
		};

		return (int) ($baseDelay * pow(2, $attemptNumber - 1));
	}

	/**
	 * Get HTTP error details including status message, category, and retry information
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return array{code: int, message: ?string, category: string, retryable: bool, retry_after: int, is_error: bool}
	 */
	public function getHTTPErrorDetails(int $code): array
	{
		return [
			'code' => $code,
			'message' => $this->getHTTPStatusMessage($code),
			'category' => $this->getHTTPStatusCategory($code),
			'retryable' => $this->isHTTPRetryable($code),
			'retry_after' => $this->getHTTPRetryAfter($code),
			'is_error' => $code >= 400,
		];
	}

	/**
	 * Get all HTTP status codes
	 * 
	 * @return array<int> List of all HTTP status codes defined in the class
	 */
	public function getAllHTTPStatusCodes(): array
	{
		return array_keys($this->httpStatusMessages);
	}

	/**
	 * Get all HTTP status codes filtered by category
	 * 
	 * @param string $category Category to filter by: 'informational', 'success', 'redirect', 'client_error', 'server_error'
	 * 
	 * @return array<int, string> Filtered HTTP status codes and their messages
	 */
	public function getHTTPStatusByCategory(string $category): array
	{
		$range = match ($category) {
			'informational' => [100, 199],
			'success' => [200, 299],
			'redirect' => [300, 399],
			'client_error' => [400, 499],
			'server_error' => [500, 599],
			default => [0, 0],
		};

		return array_filter($this->httpStatusMessages, function (string $message, int $code) use ($range): bool {
			return $code >= $range[0] && $code <= $range[1];
		}, ARRAY_FILTER_USE_BOTH);
	}

	/**
	 * Check if an HTTP status code is defined
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return bool True if the status code has a known message
	 */
	public function isKnownHTTPStatus(int $code): bool
	{
		return isset($this->httpStatusMessages[$code]);
	}

	/**
	 * Check if a cURL error code is defined
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return bool True if the error code has a known message
	 */
	public function isKnownCurlError(int $code): bool
	{
		return isset($this->curlErrorMessages[$code]);
	}

	/**
	 * Get a human-readable summary of a cURL error for end-user display
	 * 
	 * @param int $code cURL error code
	 * 
	 * @return string A user-friendly error description
	 */
	public function getHumanReadableError(int $code): string
	{
		return match ($code) {
			1 => 'The URL protocol is not supported.',
			2 => 'Failed to initialize the request.',
			3 => 'The URL format is invalid.',
			5 => 'Could not connect through the proxy server.',
			6 => 'Could not resolve the server address. Please check the URL.',
			7 => 'Could not connect to the server. It may be offline.',
			22 => 'The server returned an error response.',
			23 => 'Failed to write the received data to disk.',
			26 => 'Failed to read data to send.',
			27 => 'Not enough memory to complete the request.',
			28 => 'The request timed out. The server may be slow or unreachable.',
			35 => 'SSL/TLS connection failed. The server may have certificate issues.',
			47 => 'Too many redirects occurred. There may be a redirect loop.',
			51 => 'The server\'s SSL certificate could not be verified.',
			52 => 'The server returned an empty response.',
			55 => 'Failed to send data to the server.',
			56 => 'Failed to receive data from the server.',
			58 => 'There is a problem with the local SSL certificate.',
			59 => 'The SSL cipher could not be used.',
			60 => 'The server\'s SSL certificate authority is not trusted.',
			67 => 'Login credentials were rejected by the server.',
			77 => 'The CA certificate bundle file is invalid or missing.',
			92 => 'An HTTP/2 protocol error occurred.',
			94 => 'Authentication failed.',
			95 => 'An HTTP/3 protocol error occurred.',
			default => 'An unexpected error occurred during the request. (Error code: ' . $code . ')',
		};
	}

	/**
	 * Build a combined diagnostic report for both cURL and HTTP errors
	 * 
	 * @param int $curlCode cURL error code (0 if no cURL error)
	 * @param int $httpCode HTTP status code
	 * 
	 * @return array{
	 * 	curl_error: ?array, 
	 * 	http_status: array, 
	 * 	has_curl_error: bool, 
	 * 	has_http_error: bool, 
	 * 	overall_success: bool, 
	 * 	summary: string
	 * }
	 */
	public function getDiagnosticReport(int $curlCode, int $httpCode): array
	{
		$hasCurlError = $curlCode > 0;
		$hasHttpError = $httpCode >= 400;
		$overallSuccess = !$hasCurlError && !$hasHttpError;

		$summary = match (true) {
			$hasCurlError => 'Request failed: ' . $this->getHumanReadableError($curlCode),
			$hasHttpError => 'Server returned error: ' . ($this->getHTTPStatusMessage($httpCode) ?? 'Unknown'),
			default => 'Request completed successfully',
		};

		return [
			'curl_error' => $hasCurlError ? $this->getErrorDetails($curlCode) : null,
			'http_status' => $this->getHTTPErrorDetails($httpCode),
			'has_curl_error' => $hasCurlError,
			'has_http_error' => $hasHttpError,
			'overall_success' => $overallSuccess,
			'summary' => $summary,
		];
	}

	/**
	 * Get all cURL errors filtered by category
	 * 
	 * @param string $category Category to filter by: 'ssl', 'network', 'auth', 'ftp', 'dns', 'proxy', 'protocol', 'file_io', 'timeout', 'other'
	 * 
	 * @return array<int, string> Filtered cURL error codes and their messages
	 */
	public function getCurlErrorsByCategory(string $category): array
	{
		return array_filter($this->curlErrorMessages, function (string $message, int $code) use ($category): bool {
			return $this->getErrorCategory($code) === $category;
		}, ARRAY_FILTER_USE_BOTH);
	}

	/**
	 * Convert an HTTP status code to an appropriate exception class name suggestion
	 * 
	 * @param int $code HTTP status code
	 * 
	 * @return string Suggested exception class name
	 */
	public function suggestExceptionClass(int $code): string
	{
		return match ($code) {
			400 => 'BadRequestException',
			401 => 'UnauthorizedException',
			403 => 'ForbiddenException',
			404 => 'NotFoundException',
			405 => 'MethodNotAllowedException',
			408 => 'RequestTimeoutException',
			409 => 'ConflictException',
			410 => 'GoneException',
			413 => 'PayloadTooLargeException',
			415 => 'UnsupportedMediaTypeException',
			422 => 'UnprocessableEntityException',
			429 => 'TooManyRequestsException',
			500 => 'InternalServerErrorException',
			502 => 'BadGatewayException',
			503 => 'ServiceUnavailableException',
			504 => 'GatewayTimeoutException',
			default => $this->isHTTPClientError($code)
			? 'ClientErrorException'
			: ($this->isHTTPServerError($code) ? 'ServerErrorException' : 'HTTPException'),
		};
	}
}
