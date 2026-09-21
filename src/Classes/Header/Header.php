<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

/**
 * Header Base Class
 */
class Header
{
	private static array $headers = [];
	private static array $cookies = [];

	/**
	 * Set a response header.
	 *
	 * @param string $name
	 * @param int|string $value
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseHeader(string $name, int|string $value, bool $replace = true): void
	{
		static::$headers[$name] = $value;
		header($name . ': ' . $value, $replace);
	}

	/**
	 * Send Cache-Control header
	 * 
	 * @param string $contentType
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseCacheControlHeader(string $contentType, bool $replace = true): void
	{
		self::responseHeader('Cache-Control', $contentType, $replace);
	}

	/**
	 * Send no-cache no-store Cache-Control header
	 * 
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseNoCacheNoStoreCacheControlHeader(bool $replace = true): void
	{
		self::responseCacheControlHeader('no-cache, no-store', $replace);
	}

	/**
	 * Send no-cache Pragma header
	 * 
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseNoCachePragmaHeader(bool $replace = true): void
	{
		self::responsePragmaHeader('no-cache', $replace);
	}

	/**
	 * Send Pragma header
	 * 
	 * @param string $contentType
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responsePragmaHeader(string $contentType, bool $replace = true): void
	{
		self::responseHeader('Pragma', $contentType, $replace);
	}

	/**
	 * Send Expires header
	 * 
	 * @param int $expires
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseExpiresHeader(int $expires = 0, bool $replace = true): void
	{
		self::responseHeader('Expires', $expires, $replace);
	}

	/**
	 * Send Accept-Ranges header
	 * 
	 * @param string $contentType
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseAcceptRangesHeader(string $contentType, bool $replace = true): void
	{
		self::responseHeader('Accept-Ranges', $contentType, $replace);
	}

	/**
	 * Send icy-name header
	 * 
	 * @param string $name
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseIcyNameHeader(string $name, bool $replace = true): void
	{
		self::responseHeader('icy-name', $name, $replace);
	}

	/**
	 * Send icy-br header
	 * 
	 * @param int $br
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseIcyBrHeader(int $br, bool $replace = true): void
	{
		self::responseHeader('icy-br', $br, $replace);
	}

	/**
	 * Send Content-Type header
	 * 
	 * @param string $contentType
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseContentTypeHeader(string $contentType, bool $replace = true): void
	{
		self::responseHeader('Content-Type', $contentType, $replace);
	}

	/**
	 * Send audio/mpeg Content-Type header
	 * 
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseAudioMpegContentTypeHeader(bool $replace = true): void
	{
		self::responseHeader('Content-Type', 'audio/mpeg', $replace);
	}

	/**
	 * Send text/event-stream Content-Type header
	 * 
	 * @param bool $replace
	 * 
	 * @return void
	 */
	public static function responseTextEventStreamContentTypeHeader(bool $replace = true): void
	{
		self::responseHeader('Content-Type', 'text/event-stream', $replace);
	}

	/**
	 * Set a cookie.
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 * 
	 * @return void
	 */
	public static function setCookie(string $name, string $value = '', int $expire = 0, string $path = '', string $domain = '', bool $secure = false, bool $httponly = false): void
	{
		static::$cookies[] = [
			'name' => $name,
			'value' => $value,
			'expire' => $expire,
			'path' => $path,
			'domain' => $domain,
			'secure' => $secure,
			'httponly' => $httponly,
		];
		setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
	}

	/**
	 * Return all set response headers as an array.
	 *
	 * @return array
	 */
	public static function getHeaders(): array
	{
		return static::$headers;
	}

	/**
	 * Return all set cookies as an array.
	 *
	 * @return array
	 */
	public static function getCookies(): array
	{
		return static::$cookies;
	}

	/**
	 * Format a cookie array into a Set-Cookie header string.
	 *
	 * @param array $cookie
	 * 
	 * @return string
	 */
	public static function formatCookieHeader(array $cookie): string
	{
		$header = urlencode($cookie['name']) . '=' . urlencode($cookie['value']);

		if ($cookie['expire'] > 0) {
			$header .= '; Expires=' . gmdate('D, d-M-Y H:i:s T', $cookie['expire']);
		}

		if (!empty($cookie['path'])) {
			$header .= '; Path=' . $cookie['path'];
		}

		if (!empty($cookie['domain'])) {
			$header .= '; Domain=' . $cookie['domain'];
		}

		if ($cookie['secure']) {
			$header .= '; Secure';
		}

		if ($cookie['httponly']) {
			$header .= '; HttpOnly';
		}

		return $header;
	}

	/**
	 * Clear all set response headers.
	 *
	 * @return void
	 */
	public static function clearHeaders(): void
	{
		static::$headers = [];
	}

	/**
	 * Clear all set cookies.
	 *
	 * @return void
	 */
	public static function clearCookies(): void
	{
		static::$cookies = [];
	}

	/**
	 * Send raw header
	 * 
	 * @param string $data
	 * @param bool $replace
	 * @param int $responseCode
	 * 
	 * @return void
	 */
	public static function response(string $data, bool $replace = true, int $responseCode = 0): void
	{
		header($data, $replace, $responseCode);
	}

	/**
	 * Check if headers have already been sent.
	 * 
	 * @return bool
	 */
	public static function isSent(): bool
	{
		return headers_sent();
	}

	/**
	 * Send header with key and array
	 * 
	 * @param string $header
	 * @param array $pair
	 * 
	 * @return void
	 */
	public static function responseWithKeyAndArray(string $header, array $pair): void
	{
		if (function_exists('create_function')) {
			// @phpstan-ignore-next-line
			array_walk($pair, \create_function('&$i,$k', '$i:" $k:$i;";'));
		} else {
			array_walk($pair, function (&$i, $k) {
				$i = $k . ":" . $i . ";";
			});
		}

		$responseData = implode("", $pair);

		self::response($responseData);
	}

	/**
	 * Send header with array
	 * 
	 * @param array $pair
	 * 
	 * @return void
	 */
	public static function responseWithArray(array $pair): void
	{
		if (function_exists('create_function')) {
			// @phpstan-ignore-next-line
			array_walk($pair, \create_function('&$i,$k', '$i:" $k:$i;";'));
		} else {
			array_walk($pair, function (&$i, $k) {
				$i = $k . ":" . $i . ";";
			});
		}

		$responseData = implode("", $pair);

		self::response($responseData);
	}

	/**
	 * Send header with key
	 * 
	 * @param string $key
	 * @param string $value
	 * @param bool $replace
	 * @param int $responseCode
	 * 
	 * @return void
	 */
	public static function responseWithKey(string $key, string $value, bool $replace = true, int $responseCode = 0): void
	{
		$responseData = "$key: $value";

		self::response($responseData, $replace, $responseCode);
	}

	/**
	 * Send Content-Type header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseContentType(string $value): void
	{
		self::responseWithKey('Content-Type', $value);
	}

	/**
	 * Send Content-Transfer-Encoding header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseContentTransferEncoding(string $value): void
	{
		self::responseWithKey('Content-Transfer-Encoding', $value);
	}

	/**
	 * Send Content-Disposition header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseContentDisposition(string $value): void
	{
		self::responseWithKey('Content-Disposition', $value);
	}

	/**
	 * Send X-XSS-Protection header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseXXSSProtection(string $value): void
	{
		self::responseWithKey('X-XSS-Protection', $value);
	}

	/**
	 * Send X-Content-Type-Options header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseXContentTypeOption(string $value): void
	{
		self::responseWithKey('X-Content-Type-Options', $value);
	}

	/**
	 * Send P3P header
	 * 
	 * @return void
	 */
	public static function responseP3P(): void
	{
		self::responseWithKey('P3P', 'CP="CURa ADMa DEVa PSAo PSDo OUR BUS UNI PUR INT DEM STA PRE COM NAV OTC NOI DSP COR"');
	}

	/**
	 * Send Connection header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseConnection(string $value): void
	{
		self::responseWithKey('Connection', $value);
	}

	/**
	 * Send Content-Encoding header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseContentEncoding(string $value): void
	{
		self::responseWithKey('Content-Encoding', $value);
	}

	/**
	 * Send Content-Length header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseContentLength($value): void
	{
		self::responseWithKey('Content-Length', $value);
	}

	/**
	 * Send Location header
	 * 
	 * @param string $value
	 * 
	 * @return void
	 */
	public static function responseRedirectLocation($value): void
	{
		self::responseWithKey('Location', $value);
	}

	/**
	 * Send status header
	 * 
	 * @param string $responseCode
	 * @param string $responseMessage
	 * @param string $protocol
	 * @param string $protocolVersion
	 * 
	 * @return void
	 */
	public static function responseStatus(string $responseCode = '200', string $responseMessage = 'OK', string $protocol = 'HTTP', string $protocolVersion = '1.0'): void
	{
		$responseData = \sprintf("%s/%s %s %s", $protocol, $protocolVersion, $responseCode, $responseMessage);

		self::response($responseData);
	}

	/**
	 * Send status header by code
	 * 
	 * @param string $code
	 * 
	 * @return void
	 */
	public static function responseStatusByCode($code): void
	{
		$responseMessage = self::getStatusMessageByCode($code);

		//TODO throw

		self::responseStatus($code, $responseMessage);
	}

	/**
	 * Send Content-Disposition attachment header
	 * 
	 * @param string $fileName
	 * 
	 * @return void
	 */
	public static function fileAttachment($fileName): void
	{
		header("Content-Disposition: attachment; filename=$fileName");
	}

	/**
	 * Get status message by code
	 * 
	 * @param string $code
	 * 
	 * @return string
	 */
	public static function getStatusMessageByCode($code): string
	{
		$stateMessage = '';

		switch ($code) {
			case "200":
				$stateMessage = 'OK';
				break;
			case "201":
				$stateMessage = 'Created';
				break;
			case "202":
				$stateMessage = 'Accepted';
				break;
			case "204":
				$stateMessage = 'No Content';
				break;
			case "300":
				$stateMessage = 'Multiple Choices';
				break;
			case "301":
				$stateMessage = 'Moved Permanently';
				break;
			case "302":
				$stateMessage = 'Moved Temporarily';
				break;
			case "304":
				$stateMessage = 'Not Modified';
				break;
			case "400":
				$stateMessage = 'Bad Request';
				break;
			case "401":
				$stateMessage = 'Unauthorized';
				break;
			case "403":
				$stateMessage = 'Forbidden';
				break;
			case "404":
				$stateMessage = 'Not Found';
				break;
			case "500":
				$stateMessage = 'Internal Server Error';
				break;
			case "501":
				$stateMessage = 'Not Implemented';
				break;
			case "502":
				$stateMessage = 'Bad Gateway';
				break;
			case "503":
				$stateMessage = 'Service Unavailable';
				break;
			default:
				break;
		}

		return $stateMessage;
	}

	/**
	 * Return the nesting level of the output buffering mechanism
	 * 
	 * @return int
	 */
	public static function getNestingLevelOfOutputBufferingMechanism(): int
	{
		return ob_get_level();
	}

	/**
	 * Send X-XSS-Protection: mode=block header
	 * 
	 * @return void
	 */
	public static function responseXSSBlock(): void
	{
		self::responseXXSSProtection('mode=block');
	}

	/**
	 * Send X-Content-Type-Options: nosniff header
	 * 
	 * @return void
	 */
	public static function responseNoSniff(): void
	{
		self::responseXContentTypeOption('nosniff');
	}
}
