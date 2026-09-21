<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTTP;

use Clover\Classes\BaseClass;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\JSONHandler;
use Clover\Implement\RequestInterface;
use Clover\Classes\Data\{URLObject, StringObject};
use Clover\Classes\Web\TheOnionRouting;
use Clover\Enumeration\{HTTPRequestMethod, ServerIndices};

use function litespeed_finish_request;
use function in_array;
use function sprintf;
use function array_slice;

class Request extends BaseClass implements RequestInterface
{
	/** @var array<string, bool> */
	private static array $isMobileCache = [];
	/** @var array<string, array> */
	private static array $parsedUserAgentCache = [];
	/** @var array<string, ArrayObject> */
	private static array $urlSegmentsCache = [];

	/**
	 * GET parameter names that may carry the virtual path when the request URI is only the front script (no rewrite).
	 *
	 * @var string[]
	 */
	private static array $routingPathQueryKeys = [];
	/** @var mixed $body The content of the request body. */
	protected $statusCode = 200;
	/** @var array $cacheableMethods List of HTTP methods that are considered cacheable. */
	protected static $cacheableMethods = [
		HTTPRequestMethod::GET,
		HTTPRequestMethod::HEAD
	];
	/** @var array $safeMethods List of HTTP methods that are considered safe (do not modify server state). */
	protected static $safeMethods = [
		HTTPRequestMethod::GET,
		HTTPRequestMethod::HEAD,
		HTTPRequestMethod::OPTIONS,
		HTTPRequestMethod::TRACE
	];
	/** @var array $idempotentMethods List of HTTP methods that are considered idempotent (produce the same result regardless of how many times they are called). */
	protected static $idempotentMethods = [
		HTTPRequestMethod::DELETE,
		HTTPRequestMethod::GET,
		HTTPRequestMethod::HEAD,
		HTTPRequestMethod::OPTIONS,
		HTTPRequestMethod::PUT,
		HTTPRequestMethod::TRACE,
	];
	/** @var array $statusMesssages Mapping of HTTP status codes to their standard reason phrases. */
	protected static $statusMesssages = [
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
		413 => 'Request Entity Too Large',
		414 => 'Request-URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Requested Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => "I'm a teapot",
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Too Early',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		444 => 'Connection Closed Without Response',
		451 => 'Unavailable For Legal Reasons',
		499 => 'Client Closed Request',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		509 => 'Bandwidth Limit Exceeded',
		510 => 'Not Extended',
		511 => 'Network Authentication Required',
		599 => 'Network Connect Timeout Error',
	];

	/**
	 * Flushes all response data to the client
	 * 
	 * @return bool
	 */
	public static function flushLightSpeedResponseData(): bool
	{
		if (function_exists('litespeed_finish_request')) {
			return litespeed_finish_request();
		}

		return false;
	}

	/**
	 * Flushes all response data to the client
	 * 
	 * @return bool
	 */
	public static function flushFastCgiResponseData(): bool
	{
		if (function_exists('fastcgi_finish_request')) {
			return fastcgi_finish_request();
		}

		return false;
	}

	/**
	 * Get status messages
	 * 
	 * @return string[]
	 */
	public static function getStatusMesssages(): mixed
	{
		return self::$statusMesssages;
	}

	/**
	 * Get a single HTTP status message by code
	 *
	 * @param int $code HTTP status code
	 * @return string The corresponding status message, or an empty string if not found
	 */
	public static function getStatusMesssage(int $code): string
	{
		return self::$statusMesssages[$code] ?? "";
	}

	/**
	 * Get browser information using PHP's browscap configuration
	 *
	 * @return array An associative array of browser details, or an empty array if browscap is unavailable
	 */
	public static function getBrowserInformation(): array
	{
		$browserInformation = [];

		if (!empty(ini_get('browscap'))) {
			$browserInformation = print_r(get_browser(null, true));
		}

		return $browserInformation;
	}

	/**
	 * Determine if the current request is made over a secure connection (HTTPS or forwarded HTTPS)
	 *
	 * @return bool True if the connection is secure, false otherwise
	 */
	public static function isSecure(): bool
	{
		return self::isHttpsProtocol() || self::isForwardedSecure();
	}

	/**
	 * Check whether the current request was made via the HTTPS protocol
	 *
	 * @return bool True if HTTPS is active, false otherwise
	 */
	public static function isHttpsProtocol(): bool
	{
		$https = self::getServerArguments(ServerIndices::HTTPS);

		return empty($https) ? false : ($https === 'on' ? true : false);
	}

	/**
	 * Retrieve a value from the $_SERVER superglobal by key
	 *
	 * @param string $argument The $_SERVER key to look up
	 * @return mixed The value if found, or null if the key does not exist
	 */
	public static function getServerArguments(string $argument): mixed
	{
		if (isset($_SERVER[$argument])) {
			return $_SERVER[$argument];
		}

		return null;
	}

	/**
	 * Fetch all HTTP request headers
	 * 
	 * @return array
	 */
	public static function fetchAllResponseHeaders(): array
	{
		return getallheaders();
	}

	/**
	 * Register one or more query parameter names whose value should be used as the URL path when the client only requests the entry script (e.g. /index.php?route=/api/user).
	 *
	 * @param string ...$keys Non-empty GET parameter names; first present non-empty value wins.
	 */
	public static function registerRoutingPathQueryKeys(string ...$keys): void
	{
		foreach ($keys as $k) {
			$k = trim($k);
			if ($k === '') {
				continue;
			}
			if (!in_array($k, self::$routingPathQueryKeys, true)) {
				self::$routingPathQueryKeys[] = $k;
			}
		}
	}

	/**
	 * Clear virtual-path query registration and URL segment cache (e.g. when resetting the router in the same process).
	 */
	public static function clearRoutingPathQueryKeys(): void
	{
		self::$routingPathQueryKeys = [];
		self::$urlSegmentsCache = [];
	}

	private static function isRequestAtEntryScriptOnly(): bool
	{
		$uriPath = parse_url($_SERVER[ServerIndices::REQUEST_URI] ?? '', PHP_URL_PATH);
		if (!is_string($uriPath) || $uriPath === '') {
			return false;
		}

		$uriPath = str_replace('\\', '/', $uriPath);
		$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
		if ($scriptName === '') {
			return false;
		}

		$normUri = rtrim($uriPath, '/') ?: '/';
		$normScript = rtrim($scriptName, '/') ?: '/';

		return $normUri === $normScript;
	}

	/**
	 * Path used for routing segments: REQUEST_URI path, or a query-held virtual path when at the front controller only.
	 */
	private static function resolvePathForSegmentation(): string
	{
		$uriPath = parse_url($_SERVER[ServerIndices::REQUEST_URI] ?? '', PHP_URL_PATH);
		$uriPath = is_string($uriPath) ? str_replace('\\', '/', $uriPath) : '';

		if (self::$routingPathQueryKeys !== [] && self::isRequestAtEntryScriptOnly()) {
			foreach (self::$routingPathQueryKeys as $gk) {
				if (!isset($_GET[$gk])) {
					continue;
				}
				$raw = $_GET[$gk];
				if (!is_string($raw) || $raw === '') {
					continue;
				}

				return '/' . ltrim($raw, '/');
			}
		}

		return $uriPath;
	}

	/**
	 * Split the URL path into an array of segments
	 *
	 * @param string|null $preset Optional URL path string to parse; uses the current request path if null
	 * @return ArrayObject An array of non-empty path segments
	 */
	public static function getUrlPathSegments(string|null $preset = null): ArrayObject
	{
		if ($preset !== null) {
			$key = 'preset:' . $preset;
		} else {
			$resolved = self::resolvePathForSegmentation();
			$key = 'seg:' . $resolved;
			if (self::$routingPathQueryKeys !== []) {
				$key .= '|qs:' . (string) ($_SERVER[ServerIndices::QUERY_STRING] ?? '');
			} else {
				$key .= '|uri:' . (string) ($_SERVER[ServerIndices::REQUEST_URI] ?? '');
			}
		}

		if (isset(self::$urlSegmentsCache[$key])) {
			return self::$urlSegmentsCache[$key];
		}

		$pathSource = $preset ?? self::resolvePathForSegmentation();
		$url = new StringObject($pathSource);

		self::$urlSegmentsCache[$key] = $url->trim("/")->split("/");

		return self::$urlSegmentsCache[$key];
	}

	/**
	 * Get the path component of the current request URI
	 *
	 * @return string The URL path (e.g. "/foo/bar"), or an empty string if unavailable
	 */
	public static function getUrlPath(): string
	{
		return parse_url($_SERVER[ServerIndices::REQUEST_URI] ?? "", PHP_URL_PATH);
	}

	/**
	 * Get the protocol sent via the X-Forwarded-Proto proxy header
	 *
	 * @return mixed The forwarded protocol string (e.g. "https"), or null if not set
	 */
	public static function getForwarededPorto(): mixed
	{
		return self::getServerArguments(ServerIndices::HEADER_X_FORWARDED_PROTO);
	}

	/**
	 * Get the Content-Length of the request body in bytes
	 *
	 * @return mixed The Content-Length value, or null if not present
	 */
	public static function getContentLength(): mixed
	{
		return self::getServerArguments(ServerIndices::CONTENT_LENGTH);
	}

	/**
	 * Get the HTTP method override value from the X-HTTP-Method-Override header
	 *
	 * @return mixed The override method string, or null if not set
	 */
	public static function getOverrideMethod(): mixed
	{
		return self::getServerArguments(ServerIndices::X_HTTP_METHOD_OVERRIDE);
	}

	/**
	 * Get timestamp of the start of the request
	 */
	public static function getServerTime(): mixed
	{
		return self::getServerArguments(ServerIndices::REQUEST_TIMESTAMP);
	}

	/**
	 * Get the request start time as a float with microsecond precision
	 *
	 * @return mixed The REQUEST_TIME_FLOAT value, or null if unavailable
	 */
	public static function getServerFloatTime(): mixed
	{
		return self::getServerArguments(ServerIndices::REQUEST_TIME_FLOAT);
	}

	/**
	 * Get the URL scheme of the current request (e.g. "http" or "https")
	 *
	 * @return mixed The REQUEST_SCHEME value, or null if unavailable
	 */
	public static function getScheme(): mixed
	{
		return self::getServerArguments(ServerIndices::REQUEST_SCHEME);
	}

	public static function getProtocol(): string
	{
		$protocol = self::getServerArguments(ServerIndices::SERVER_PROTOCOL);
		$lowercase = strtolower($protocol);

		return substr($lowercase, 0, strpos($lowercase, '/'));
	}

	/**
	 * Get Request Uniform Resource Identifier
	 *
	 * @return string
	 */
	public static function getURI(): mixed
	{
		return self::getServerArguments(ServerIndices::REQUEST_URI);
	}

	/**
	 * The physical path of the temporary IIS application pool configuration.
	 *
	 * @return string
	 */
	public static function getTemporaryIISApplicationPhysicalPathOfPoolConfiguration(): mixed
	{
		return self::getServerArguments(ServerIndices::APP_POOL_CONFIG);
	}

	/**
	 * The metabase path of the application.
	 *
	 * @return string
	 */
	public static function getIISApplicationMetabasePath(): mixed
	{
		return self::getServerArguments(ServerIndices::APPL_MD_PATH);
	}

	/**
	 * Get the HTTP_HOST value (hostname and optional port) from the current request
	 *
	 * @return mixed The HTTP_HOST string, or null if not set
	 */
	public static function getHttpHost(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_HOST);
	}

	/**
	 * The authentication method that the server uses to validate users.
	 * It does not mean that the user was authenticated if AUTH_TYPE contains a value and the authentication scheme is not Basic or integrated Windows authentication. 
	 * The server allows authentication schemes it does not natively support because an ISAPI filter may be able to handle that particular scheme.
	 *
	 * @return string
	 */
	public static function getIISAuthenticateType(): mixed
	{
		return self::getServerArguments(ServerIndices::AUTH_TYPE);
	}

	/**
	 * The password provided by the client to authenticate using Basic Authentication.
	 *
	 * @return string
	 */
	public static function getIISAuthenticatePassword(): mixed
	{
		return self::getServerArguments(ServerIndices::AUTH_PASSWORD);
	}

	/**
	 * The name of the application pool that is running the IIS worker process handling the request.
	 *
	 * @return string
	 */
	public static function getIISApplicationPoolID(): mixed
	{
		return self::getServerArguments(ServerIndices::APP_POOL_ID);
	}

	/**
	 * The physical path of the application.
	 *
	 * @return string
	 */
	public static function getIISApplicationPhysicalPath(): mixed
	{
		return self::getServerArguments(ServerIndices::APPL_PHYSICAL_PATH);
	}

	/**
	 * Get server identification string
	 */
	public static function getServerSoftwareName(): mixed
	{
		return self::getServerArguments(ServerIndices::SERVER_SOFTWARE_NAME);
	}

	/**
	 * Get document root directory under which the current script is executing
	 */
	public static function getAbsolutePathOfDocumentRoot(): mixed
	{
		return self::getServerArguments(ServerIndices::DOCUMENT_ROOT_DIRECTORY);
	}

	/**
	 * Get the URL rewritten by the IIS ISAPI Rewrite module via the X-Rewrite-URL header
	 *
	 * @return mixed The rewritten URL string, or null if not present
	 */
	public static function getIISIsapiRewriteURL(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_X_REWRITE_URL);
	}

	/**
	 * Get the value of the HTTP Connection header (e.g. "keep-alive" or "close")
	 *
	 * @return string The Connection header value, or an empty string if not set
	 */
	public static function getHTTPConnection(): string
	{
		return self::getServerArguments(ServerIndices::HTTP_CONNECTION) ?? "";
	}

	/**
	 * The port on the server machine being used by the web server for communication
	 */
	public static function getPort(): string
	{
		return self::getServerArguments(ServerIndices::SERVER_PORT);
	}

	/**
	 * Get the Origin header value of the current request
	 *
	 * @return string The HTTP_ORIGIN value, or null if not present
	 */
	public static function getOrigin(): string
	{
		return self::getServerArguments(ServerIndices::HTTP_ORIGIN);
	}

	/**
	 * Get the HTTP Referer (referring page URL) of the current request
	 *
	 * @return mixed The HTTP_REFERER value, or null if not set
	 */
	public static function getReferrer(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_REFERER);
	}

	/**
	 * Get the Accept header value indicating the MIME types accepted by the client
	 *
	 * @return mixed The HTTP_ACCEPT value, or null if not present
	 */
	public static function getHTTPAccept(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_ACCEPT);
	}

	/**
	 * Get the Content-Type header value sent by the client
	 *
	 * @return mixed The HTTP_CONTENT_TYPE value, or null if not present
	 */
	public static function getHTTPContentType(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_CONTENT_TYPE);
	}

	/**
	 * Name and revision of the information protocol via which the page was requested
	 * 
	 * @return string
	 */
	public static function getServerProtocol(): string
	{
		return self::getServerArguments(ServerIndices::SERVER_PROTOCOL);
	}

	/**
	 * Returns true if the current request is forwarded from a request that is secure.
	 *
	 * @return boolean
	 */
	public static function isForwardedSecure(): bool
	{
		$forwarededProtocol = self::getHTTPXForwardedProtocol();

		return isset($forwarededProtocol) && strtolower($forwarededProtocol) === 'https';
	}

	/**
	 * Get the X-Forwarded-Proto header value indicating the original protocol used by a proxy
	 *
	 * @return mixed The HTTP_X_FORWARDED_PROTO value, or null if not set
	 */
	public static function getHTTPXForwardedProtocol(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_X_FORWARDED_PROTO);
	}

	/**
	 * Get the Content-Type of the request body (from the CONTENT_TYPE server variable)
	 *
	 * @return mixed The CONTENT_TYPE value, or an empty string if not set
	 */
	public static function getContentType(): mixed
	{
		return self::getServerArguments(ServerIndices::CONTENT_TYPE) ?? "";
	}

	/**
	 * Get the server signature string (web server name and version appended to pages)
	 *
	 * @return mixed The SERVER_SIGNATURE value, or null if not available
	 */
	public static function getSignature(): mixed
	{
		return self::getServerArguments(ServerIndices::SERVER_SIGNATURE);
	}

	/**
	 * Get the User-Agent string sent by the client
	 *
	 * @return mixed The HTTP_USER_AGENT string, or an empty string if not set
	 */
	public static function getUserAgent(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_USER_AGENT) ?? "";
	}

	/**
	 * Which request method was used to access the page
	 */
	public static function getMethod(): mixed
	{
		return self::getServerArguments(ServerIndices::REQUEST_METHOD);
	}

	/**
	 * Check whether the client accepts gzip-encoded responses
	 *
	 * @return bool True if "gzip" appears in the Accept-Encoding header, false otherwise
	 */
	public static function isAcceptGzipEncoding(): bool
	{
		return strpos(self::getAcceptEncoding(), 'gzip') !== false;
	}

	/**
	 * Get the Accept-Encoding header value listing compression schemes supported by the client
	 *
	 * @return mixed The HTTP_ACCEPT_ENCODING value, or null if not present
	 */
	public static function getAcceptEncoding(): mixed
	{
		return self::getServerArguments(ServerIndices::HTTP_ACCEPT_ENCODING);
	}

	/**
	 * Get the DOCUMENT_URI (the URI of the document being served, independent of query string)
	 *
	 * @return mixed The DOCUMENT_URI value, or null if not available
	 */
	public static function getDocumentUrl(): mixed
	{
		return self::getServerArguments(ServerIndices::DOCUMENT_URI);
	}

	/**
	 * Check whether the current request was made via XMLHttpRequest
	 *
	 * @return bool True if the X-Requested-With header equals "xmlhttprequest" (case-insensitive)
	 */
	public static function isXmlHttpRequest(): bool
	{
		$httpXRequestedWith = self::getServerArguments(ServerIndices::HTTP_X_REQUESTED_WITH);

		return (strtolower($httpXRequestedWith) === 'xmlhttprequest');
	}

	/**
	 * Check whether the current request is an AJAX request
	 *
	 * @return bool True if the X-Requested-With header is present and equals "xmlhttprequest"
	 */
	public static function isAjax(): bool
	{
		return (!empty(self::getServerArguments(ServerIndices::HTTP_X_REQUESTED_WITH)) && self::isXmlHttpRequest()) ? true : false;
	}

	/**
	 * Determine if the current request expects a JSON response.
	 * Checks Accept header, Content-Type or AJAX indicator.
	 *
	 * @return bool
	 */
	public function wantsJson(): bool
	{
		$accept = self::getHTTPAccept() ?? '';
		$contentType = self::getHTTPContentType() ?? '';

		if (!empty($accept) && stripos($accept, 'application/json') !== false) {
			return true;
		}

		if (!empty($contentType) && stripos($contentType, 'application/json') !== false) {
			return true;
		}

		// treat AJAX requests as expecting JSON by default
		return self::isAjax();
	}

	/**
	 * Get the X-Forwarded-For header value containing the originating client IP through proxies
	 *
	 * @return mixed The HTTP_X_FORWARDED_FOR value, or null if not set
	 */
	public static function getHTTPXForwardedFor(): mixed
	{
		if (isset($_SERVER[ServerIndices::HTTP_X_FORWARDED_FOR])) {
			return $_SERVER[ServerIndices::HTTP_X_FORWARDED_FOR];
		}

		return null;
	}

	/**
	 * Get the real client IP address as forwarded by the Cloudflare proxy (CF-Connecting-IP)
	 *
	 * @return mixed The client IP if the CF-Connecting-IP header is present, or null otherwise
	 */
	public static function getCloudFlareProxyIP(): mixed
	{
		if (isset($_SERVER[ServerIndices::HTTP_CF_CONNECTING_IP])) {
			return self::getClientIP();
		}

		return null;
	}

	/**
	 * Get the IP address from the HTTP_CLIENT_IP header
	 *
	 * @return mixed The HTTP_CLIENT_IP value, or null if not present
	 */
	public static function getClientIP(): mixed
	{
		if (isset($_SERVER[ServerIndices::HTTP_CLIENT_IP])) {
			return $_SERVER[ServerIndices::HTTP_CLIENT_IP];
		}

		return null;
	}

	/**
	 * Get the full URI of the current request relative to the script's base directory
	 *
	 * @return URLObject A URLObject representing the decoded, normalised URI path
	 */
	public static function getFullUri(): URLObject
	{
		$uri = rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/');
		$uri = '/' . trim(str_replace($uri, '', $_SERVER['REQUEST_URI']), '/');
		$uri = urldecode($uri);

		return new URLObject($uri);
	}

	/**
	 * Get query string
	 * 
	 * @return URLObject
	 */
	public static function getQueryString(): URLObject
	{
		$queryString = "";

		if (isset($_SERVER[ServerIndices::QUERY_STRING])) {
			$queryString = $_SERVER[ServerIndices::QUERY_STRING];
		}

		return new URLObject($queryString);
	}

	/**
	 * Get ip address from which the user is viewing the current page
	 * 
	 * @return URLObject|null
	 */
	public static function getRemoteIPAddress(): URLObject
	{
		$remoteAddress = "";

		if (isset($_SERVER[ServerIndices::REMOTE_IP_ADDRESS])) {
			$remoteAddress = $_SERVER[ServerIndices::REMOTE_IP_ADDRESS];
		}

		return new URLObject($remoteAddress);
	}

	/**
	 * Check whether the remote IP address is a known Tor exit node
	 *
	 * @return bool True if the current request originates from a Tor exit node, false otherwise
	 */
	public static function isTorExitNode(): bool
	{
		return TheOnionRouting::isExitNode();
	}

	/**
	 * Parse the Accept-Language header into an associative array of locale => quality pairs
	 *
	 * @param string|null $field Optional Accept-Language header string; uses the current request value if null
	 * @return mixed Associative array mapping language tags to their quality factor (float)
	 */
	public static function parseAcceptLanguage(?string $field = null): mixed
	{
		$field = $field ?? self::getAcceptLanguage();

		$fields = explode(",", $field ?? "");

		return array_reduce($fields, function ($res, $el) {
			$el = trim($el);
			if ($el === '') {
				return $res;
			}

			list($l, $q) = array_merge(explode(';q=', $el), [1]);
			if ($l === '') {
				return $res;
			}

			$res[$l] = (float) $q;
			return $res;
		}, []);
	}

	/**
	 * Get the human-readable name of the language preferred by the client
	 *
	 * @return mixed The language name string looked up from the locale codes table
	 */
	public static function getCurrentLanguage(): mixed
	{
		$key = self::getLocaleCode();

		$languages = include(BASE_PATH . "/../src/Defaults/LocaleCodes.php");

		return $languages[$key]['name'] ?? $languages['en_US']['name'] ?? 'English';
	}

	/**
	 * Get the primary locale code from the Accept-Language header (e.g. "en_US")
	 *
	 * Dashes in the locale string are normalised to underscores.
	 *
	 * @return array|string The locale code string
	 */
	public static function getLocaleCode(): array|string
	{
		$acceptLanguages = self::parseAcceptLanguage(self::getAcceptLanguage());
		if (empty($acceptLanguages)) {
			return 'en_US';
		}

		$key = array_keys($acceptLanguages)[0];
		if ($key === '') {
			return 'en_US';
		}
		$key = str_replace('-', '_', $key);

		return $key;
	}

	/**
	 * Get the raw Accept-Language header value from the current request
	 *
	 * @return mixed The HTTP_ACCEPT_LANGUAGE string, or null if not set
	 */
	public static function getAcceptLanguage(): mixed
	{
		if (isset($_SERVER[ServerIndices::HTTP_ACCEPT_LANGUAGE])) {
			return $_SERVER[ServerIndices::HTTP_ACCEPT_LANGUAGE];
		}

		return null;
	}

	/**
	 * Check whether the given (or current) HTTP method is idempotent
	 *
	 * Idempotent methods produce the same result regardless of how many times they are called.
	 *
	 * @param HTTPRequestMethod|null $method Optional method to test; uses the current request method if null
	 * @return bool True if the method is idempotent (DELETE, GET, HEAD, OPTIONS, PUT, TRACE)
	 */
	public static function isIdempotentMethod(?HTTPRequestMethod $method = null): bool
	{
		return in_array(strtoupper($method ?? self::getMethod()), self::$idempotentMethods);
	}

	/**
	 * Check whether the given (or current) HTTP method is cacheable
	 *
	 * @param HTTPRequestMethod|null $method Optional method to test; uses the current request method if null
	 * @return bool True if the method is cacheable (GET, HEAD)
	 */
	public static function isCacheableMethod(?HTTPRequestMethod $method = null): bool
	{
		return in_array(strtoupper($method ?? self::getMethod()), self::$cacheableMethods);
	}

	/**
	 * Check whether the given (or current) HTTP method is safe (i.e. read-only with no side effects)
	 *
	 * @param HTTPRequestMethod|null $method Optional method to test; uses the current request method if null
	 * @return bool True if the method is safe (GET, HEAD, OPTIONS, TRACE)
	 */
	public static function isSafeMethod(?HTTPRequestMethod $method = null): bool
	{
		return in_array(strtoupper($method ?? self::getMethod()), self::$safeMethods);
	}

	/**
	 * Check whether the current HTTP request method is HEAD
	 *
	 * @return bool True if the request method is HEAD, false otherwise
	 */
	public static function isHeadMethod(): bool
	{
		return (strtoupper(self::getMethod()) === HTTPRequestMethod::HEAD) ? true : false;
	}

	/**
	 * Check whether the current HTTP request method is PATCH
	 *
	 * @return bool True if the request method is PATCH, false otherwise
	 */
	public static function isPatchMethod(): bool
	{
		return (strtoupper(self::getMethod()) === HTTPRequestMethod::PATCH) ? true : false;
	}

	/**
	 * Check whether the current HTTP request method is PUT
	 *
	 * @return bool True if the request method is PUT, false otherwise
	 */
	public static function isPutMethod(): bool
	{
		return (strtoupper(self::getMethod()) === HTTPRequestMethod::PUT) ? true : false;
	}

	/**
	 * Check whether the current HTTP request method is OPTIONS
	 *
	 * @return bool True if the request method is OPTIONS, false otherwise
	 */
	public static function isOptionsMethod(): bool
	{
		return (strtoupper(self::getMethod()) === HTTPRequestMethod::OPTIONS) ? true : false;
	}

	/**
	 * Check whether the current HTTP request method is DELETE
	 *
	 * @return bool True if the request method is DELETE, false otherwise
	 */
	public static function isDeleteMethod(): bool
	{
		return (strtoupper(self::getMethod()) === HTTPRequestMethod::DELETE) ? true : false;
	}

	/**
	 * Check whether the current HTTP request method is GET
	 *
	 * @return bool True if the request method is GET, false otherwise
	 */
	public static function isGetMethod(): bool
	{
		return (strtoupper(self::getMethod()) === HTTPRequestMethod::GET) ? true : false;
	}

	/**
	 * Check whether the current HTTP request method is POST
	 *
	 * @return bool True if the request method is POST, false otherwise
	 */
	public static function isPostMethod(): bool
	{
		return (strtoupper(self::getMethod()) === HTTPRequestMethod::POST) ? true : false;
	}

	/**
	 * Get a parameter value from the raw multipart/JSON request body
	 *
	 * Reads from php://input, decodes it as JSON, and returns the value for the given key.
	 *
	 * @param string $parameter The parameter key to look up
	 * @return StringObject A StringObject wrapping the parameter value, or null if not found
	 */
	public static function getMultipartParameter(string $parameter): StringObject
	{
		$string = null;

		$jsonData = file_get_contents("php://input");
		$data = JSONHandler::decode($jsonData, true);

		$string = isset($data[$parameter]) ? $data[$parameter] : null;

		return new StringObject($string);
	}

	/**
	 * Get a single parameter value from the POST body
	 *
	 * Returns the value only when the current request method is POST.
	 *
	 * @param string $parameter The $_POST key to retrieve
	 * @return StringObject A StringObject wrapping the parameter value, or null if not found
	 */
	public static function getPostParameter($parameter): StringObject
	{
		$string = null;

		if (self::isPostMethod()) {
			$string = isset($_POST[$parameter]) ? $_POST[$parameter] : null;
		}

		return new StringObject($string);
	}

	/**
	 * Get a single query string parameter from a GET request
	 *
	 * Returns the value only when the current request method is GET.
	 *
	 * @param string $parameter The $_GET key to retrieve
	 * @param mixed  $default   Value to return when the key is absent (default: null)
	 * @return StringObject|null A StringObject wrapping the parameter value, or null for non-GET requests
	 */
	public static function getQueryParameter($parameter, $default = null): StringObject|null
	{
		$string = null;

		if (self::isGetMethod()) {
			$string = isset($_GET[$parameter]) ? $_GET[$parameter] : $default;
			$string = new StringObject($string);
		}

		return $string;
	}

	/**
	 * Extract all POST parameters as a plain associative array
	 *
	 * Returns null when the current request method is not POST.
	 *
	 * @return array|null Associative array of all $_POST key-value pairs, or null for non-POST requests
	 */
	public static function getExtractedPostParameters(): array|null
	{
		if (self::getMethod() === HTTPRequestMethod::POST) {
			$extracted = [];

			foreach ($_POST as $key => $val) {
				$extracted[$key] = $val;
			}

			return $extracted;
		}

		return null;
	}

	/**
	 * Extract all GET query parameters as a plain associative array
	 *
	 * Returns null when the current request method is not GET.
	 *
	 * @return array|null Associative array of all $_GET key-value pairs, or null for non-GET requests
	 */
	public static function getExtractedQueryParameters(): array|null
	{
		if (self::isGetMethod()) {
			$extracted = [];

			foreach ($_GET as $key => $val) {
				$extracted[$key] = $val;
			}

			return $extracted;
		}

		return null;
	}

	/**
	 * Get the base URI of the current request up to (but not including) the document path
	 *
	 * Combines the scheme, host, and the directory portion of the document URL to produce
	 * the base URL of the application.
	 *
	 * @return URLObject A URLObject representing the base request URI
	 */
	public static function getRequestUri(): URLObject
	{
		$port = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
		$documentUrl = (self::getDocumentUrl() ?: ($_SERVER["SCRIPT_NAME"] ?? ''));
		$host = sprintf('%s%s%s', $port, self::getHttpHost(), $documentUrl ? dirname($documentUrl) : "");

		return new URLObject($host);
	}

	/**
	 * Get the full URL of the current request including scheme, host, and request URI
	 *
	 * @return URLObject A URLObject representing the complete request URL
	 */
	public static function getRequestURL(): URLObject
	{
		$port = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
		$host = sprintf('%s%s%s', $port, self::getHttpHost(), $_SERVER['REQUEST_URI'] ?? "");

		return new URLObject($host);
	}

	/**
	 * Determine whether the current request originates from a mobile device
	 *
	 * Inspects a combination of mobile-specific HTTP headers, User-Agent patterns,
	 * device brand signatures (Samsung, LG, Nokia, BlackBerry, etc.), and known
	 * mobile browser strings to identify handsets, tablets, and other portable devices.
	 *
	 * @return bool True if the request is from a mobile device, false otherwise
	 */
	public static function isMobile(): bool
	{
		$useragent = strtolower((string) self::getUserAgent());
		if (isset(self::$isMobileCache[$useragent])) {
			return self::$isMobileCache[$useragent];
		}

		if (isset($_SERVER[ServerIndices::HTTP_X_WAP_PROFILE])) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		if (isset($_SERVER[ServerIndices::HTTP_DEVICE_STOCK_UA])) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		if (isset($_SERVER[ServerIndices::HTTP_X_UCBROWSER_DEVICE_UA])) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		if (isset($_SERVER[ServerIndices::HTTP_X_BOLT_PHONE_UA])) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		if (isset($_SERVER[ServerIndices::HTTP_X_SKYFIRE_PHONE])) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		if (isset($_SERVER[ServerIndices::HTTP_X_OPERAMINI_PHONE_UA])) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		if (preg_match('/(android|bb\d+|meego).+mobile/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Gaming Consoles
		if (preg_match('/nintendo|psp|playstation|xbox/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Operation System
		if (preg_match('/symbian|webos\//i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Korea
		if (preg_match('/samsung|lgtelecom|lg;/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Japan
		if (preg_match('/sonyericsson|docomo|panasonic|sharp|nec/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Chinese
		if (preg_match('/lenovo/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Canada
		if (preg_match('/blackberry/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// American
		if (preg_match('/novarra|appletv|motorola|ip(hone|od|ad)|nexus|windows (ce|phone)/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Finland
		if (preg_match('/alcatel|nokia/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// PDA
		if (preg_match('/palmos|palm( os)?|pda;/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Wearable
		if (preg_match('/itouch/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		// Browser
		if (preg_match('/eudoraweb|dillo|opera m(ob|in)i|netfront|iemobile|puffin|ucbrowser|fennec/i', $useragent)) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		$mobileRegex = '/avantgo|htc(_|-)|bada\/|brew|blazer|tablet|compal|teleca|minimo|wap;|elaine|hiptop|iris|kindle|lge |maemo|midp|mmp|phone|p(ixi|re)\/|plucker|pocket|series(4|6)0|treo|up\.(browser|link)|vodafone|wap|xda|xiino/i';

		$mobileRegex2 = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i';

		if (preg_match($mobileRegex, $useragent) || preg_match($mobileRegex2, substr($useragent, 0, 4))) {
			self::$isMobileCache[$useragent] = true;
			return self::$isMobileCache[$useragent];
		}

		self::$isMobileCache[$useragent] = false;

		return self::$isMobileCache[$useragent];
	}

	/**
	 * Check whether the remote host matches one or more host patterns
	 *
	 * Patterns are comma-separated and may contain wildcard (*) characters.
	 * The wildcard is expanded to ".+" in the generated regular expression.
	 *
	 * @param string $hosts Comma-separated list of host patterns (wildcards allowed)
	 * @return bool|int Non-zero (truthy) if the remote host matches, 0 or false otherwise
	 */
	public static function isMatchHost($hosts): bool|int
	{
		$host = $_SERVER[ServerIndices::REMOTE_HOST_NAME];

		$regexp = preg_quote($hosts, '#');
		$regexp = str_replace(',', '|', $regexp);
		$regexp = str_replace('\*', '.+', $regexp);

		return preg_match("#$regexp#", $host);
	}

	/**
	 * Check whether the User-Agent string matches one or more agent patterns
	 *
	 * Patterns are comma-separated and may contain wildcard (*) characters.
	 * Newline characters are stripped from the actual User-Agent before matching.
	 *
	 * @param string $agents Comma-separated list of User-Agent patterns (wildcards allowed)
	 * @return bool|int Non-zero (truthy) if the User-Agent matches, 0 or false otherwise
	 */
	public static function isMatchUserAgent(string $agents): bool|int
	{
		$agent = $_SERVER[ServerIndices::HTTP_USER_AGENT];
		$agent = preg_replace('/[\r\n]/', '', $agent);

		$regexp = preg_quote($agents, '#');
		$regexp = str_replace(',', '|', $regexp);
		$regexp = str_replace('\*', '.+', $regexp);

		return preg_match("#$regexp#", $agent);
	}

	/**
	 * Extract the version number that follows a given keyword in a User-Agent string
	 *
	 * Matches patterns such as "Chrome/120.0.0" or "Firefox 120.0" where the keyword
	 * is immediately followed by a slash or space and then a digit-led version token.
	 *
	 * @param string $userAgent The raw User-Agent string to search
	 * @param string $keyword   The product name or token preceding the version number
	 * @return string|null The version string if found, or null if the pattern does not match
	 */
	private static function extractVersion(string $userAgent, string $keyword): ?string
	{
		$escaped = preg_quote($keyword, '/');
		if (preg_match('/' . $escaped . '[\/\s]+([\d][^\s;)]*)/i', $userAgent, $m)) {
			return $m[1];
		}
		return null;
	}

	/**
	 * Parse a User-Agent string into a structured array of device and browser details
	 *
	 * Detects bots/crawlers, gaming consoles, Apple devices (iPhone, iPad, Mac),
	 * Android, Windows, Linux, ChromeOS, smart TVs, and a wide range of browsers.
	 * The returned array may contain the following keys:
	 *   - is_bot          (bool)   Whether the UA belongs to a bot or crawler
	 *   - bot_name        (string) Bot display name, if is_bot is true
	 *   - bot_version     (string) Bot version, if available
	 *   - machine         (string) Hardware platform (e.g. "iPhone", "Nintendo Switch")
	 *   - operation       (string) Operating system / platform name (e.g. "iOS", "Android")
	 *   - operation_version (string) OS version string
	 *   - device          (string) Device model or family
	 *   - device_type     (string) One of: "mobile", "tablet", "desktop", "tv", "console", "bot"
	 *   - device_version  (string) Device OS version, if detected
	 *   - locale          (string) Locale code found inside the UA system info block
	 *   - engine          (string) Rendering engine (e.g. "WebKit", "Gecko")
	 *   - engine_version  (string) Rendering engine version
	 *   - browser         (string) Browser display name (e.g. "Chrome", "Firefox")
	 *   - browser_version (string) Browser version string
	 *
	 * @param string|null $userAgent Raw User-Agent string to parse; uses the current request UA if null
	 * @return array Associative array of parsed User-Agent fields
	 */
	public static function parseUserAgent(?string $userAgent = null): array
	{
		$userAgent ??= self::getUserAgent();
		if (isset(self::$parsedUserAgentCache[$userAgent])) {
			return self::$parsedUserAgentCache[$userAgent];
		}
		$parsed = [];

		$systemInfo = '';
		if (preg_match('/\(([^)]+)\)/', $userAgent, $sysMatch)) {
			$systemInfo = $sysMatch[1];
		}
		$systemParts = array_map('trim', explode(';', $systemInfo));
		$firstPart = $systemParts[0] ?? '';

		$bots = [
			'Googlebot-Image' => 'Googlebot-Image',
			'Googlebot-News' => 'Googlebot-News',
			'Googlebot-Video' => 'Googlebot-Video',
			'Storebot-Google' => 'Storebot-Google',
			'Google-Extended' => 'Google-Extended',
			'Googlebot' => 'Googlebot',
			'AdsBot-Google' => 'AdsBot-Google',
			'Mediapartners-Google' => 'Mediapartners-Google',
			'APIs-Google' => 'APIs-Google',
			'bingbot' => 'Bingbot',
			'msnbot' => 'MSNBot',
			'Baiduspider' => 'Baiduspider',
			'YandexBot' => 'YandexBot',
			'YandexImages' => 'YandexImages',
			'DuckDuckBot' => 'DuckDuckBot',
			'Slurp' => 'Yahoo Slurp',
			'facebookexternalhit' => 'Facebook',
			'Facebot' => 'Facebook',
			'Twitterbot' => 'Twitterbot',
			'LinkedInBot' => 'LinkedInBot',
			'WhatsApp' => 'WhatsApp',
			'TelegramBot' => 'TelegramBot',
			'Discordbot' => 'Discordbot',
			'Applebot' => 'Applebot',
			'PinterestBot' => 'PinterestBot',
			'AhrefsBot' => 'AhrefsBot',
			'SemrushBot' => 'SemrushBot',
			'MJ12bot' => 'MJ12bot',
			'DotBot' => 'DotBot',
			'PetalBot' => 'PetalBot',
			'GPTBot' => 'GPTBot',
			'ChatGPT-User' => 'ChatGPT-User',
			'ClaudeBot' => 'ClaudeBot',
			'Claude-Web' => 'Claude-Web',
			'Amazonbot' => 'Amazonbot',
			'CCBot' => 'CCBot',
			'ia_archiver' => 'Alexa',
			'Sogou' => 'Sogou',
			'Exabot' => 'Exabot',
			'SeznamBot' => 'SeznamBot',
			'archive.org_bot' => 'Archive.org',
			'Bytespider' => 'Bytespider',
			'DataForSeoBot' => 'DataForSeoBot',
			'HeadlessChrome' => 'HeadlessChrome',
			'PhantomJS' => 'PhantomJS',
			'Screaming Frog' => 'Screaming Frog',
			'Nutch' => 'Nutch',
			'rogerbot' => 'Rogerbot',
			'Embedly' => 'Embedly',
			'Quora-Bot' => 'Quora-Bot',
			'Qwantify' => 'Qwantify',
			'curl/' => 'curl',
			'Wget/' => 'Wget',
			'python-requests' => 'Python Requests',
			'python-urllib' => 'Python urllib',
			'aiohttp' => 'aiohttp',
			'httpx' => 'httpx',
			'axios/' => 'Axios',
			'Go-http-client' => 'Go HTTP Client',
			'Java/' => 'Java',
			'okhttp/' => 'OkHttp',
			'Postman' => 'Postman',
			'node-fetch' => 'node-fetch',
			'undici' => 'Undici',
			'libwww-perl' => 'libwww-perl',
			'Scrapy' => 'Scrapy',
			'Apache-HttpClient' => 'Apache HttpClient',
		];

		$parsed['is_bot'] = false;
		foreach ($bots as $signature => $botName) {
			if (stripos($userAgent, $signature) !== false) {
				$parsed['is_bot'] = true;
				$parsed['bot_name'] = $botName;
				$parsed['bot_version'] = self::extractVersion($userAgent, rtrim($signature, '/'));
				$parsed['device_type'] = 'bot';
				break;
			}
		}

		$consoles = [
			'Nintendo Switch' => ['machine' => 'Nintendo Switch', 'operation' => 'Nintendo'],
			'Nintendo WiiU' => ['machine' => 'Nintendo Wii U', 'operation' => 'Nintendo'],
			'Nintendo Wii;' => ['machine' => 'Nintendo Wii', 'operation' => 'Nintendo'],
			'New Nintendo 3DS' => ['machine' => 'Nintendo 3DS', 'operation' => 'Nintendo'],
			'Nintendo 3DS' => ['machine' => 'Nintendo 3DS', 'operation' => 'Nintendo'],
			'Nintendo DS;' => ['machine' => 'Nintendo DS', 'operation' => 'Nintendo'],
			'Xbox Series' => ['machine' => 'Xbox Series', 'operation' => 'Xbox'],
			'Xbox One' => ['machine' => 'Xbox One', 'operation' => 'Xbox'],
		];

		$consoleDetected = false;
		foreach ($consoles as $signature => $info) {
			if (stripos($userAgent, $signature) !== false) {
				$parsed['machine'] = $info['machine'];
				$parsed['operation'] = $info['operation'];
				$parsed['device_type'] = 'console';
				if (str_contains($userAgent, 'NintendoBrowser')) {
					$parsed['browser'] = 'NintendoBrowser';
					$parsed['browser_version'] = self::extractVersion($userAgent, 'NintendoBrowser');
				}
				$consoleDetected = true;
				break;
			}
		}

		if (!$consoleDetected) {
			if (preg_match('/PlayStation\s*(Vita|[345])[\s\/]([\d.]+)?/i', $userAgent, $psMatch)) {
				$parsed['machine'] = 'PlayStation ' . $psMatch[1];
				$parsed['operation'] = 'PlayStation';
				$parsed['operation_version'] = !empty($psMatch[2]) ? $psMatch[2] : null;
				$parsed['device_type'] = 'console';
				$consoleDetected = true;
			} elseif (stripos($userAgent, 'PlayStation') !== false) {
				$parsed['machine'] = 'PlayStation';
				$parsed['operation'] = 'PlayStation';
				$parsed['device_type'] = 'console';
				$consoleDetected = true;
			}
		}

		if (!$consoleDetected) {
			$appleMachines = ['iPhone', 'iPad', 'iPod touch', 'iPod'];
			$foundApple = null;
			foreach ($appleMachines as $machine) {
				if (str_starts_with($firstPart, $machine)) {
					$foundApple = $machine;
					break;
				}
			}

			if ($firstPart === 'Macintosh') {
				$parsed['machine'] = 'Macintosh';
				$parsed['operation'] = 'Macintosh';
				$parsed['device_type'] = $parsed['device_type'] ?? 'desktop';

				$remainingParts = array_slice($systemParts, 1);
				foreach ($remainingParts as $part) {
					$part = trim($part);
					if ($part === '' || $part === 'U')
						continue;
					if (preg_match('/^[a-z]{2}[_-][A-Za-z]{2}$/', $part)) {
						$parsed['locale'] = $part;
					} elseif (!str_starts_with($part, 'rv:') && !isset($parsed['device'])) {
						$parsed['device'] = $part;
					}
				}
			} elseif ($foundApple) {
				$parsed['machine'] = $foundApple;
				$parsed['operation'] = $foundApple;
				$parsed['device_type'] = ($foundApple === 'iPad') ? 'tablet' : 'mobile';

				$remainingParts = array_slice($systemParts, 1);
				$remainingStr = implode(';', $remainingParts);

				if (preg_match('/CPU (?:iPhone )?OS ([0-9_]+) like Mac OS X/', $remainingStr, $iosMatch)) {
					$parsed['operation'] = 'iOS';
					$parsed['operation_version'] = str_replace('_', '.', $iosMatch[1]);
					if (!isset($parsed['device'])) {
						$parsed['device'] = 'CPU OS ' . $iosMatch[1] . ' like Mac OS X';
					}
				}

				foreach ($remainingParts as $part) {
					$part = trim($part);
					if ($part === '' || $part === 'U' || preg_match('/^[a-z]{2}$/i', $part))
						continue;
					if (preg_match('/^[a-z]{2}[_-][A-Za-z]{2}$/', $part)) {
						$parsed['locale'] = $part;
					} elseif (!str_starts_with($part, 'CPU') && !str_starts_with($part, 'rv:')) {
						if (!isset($parsed['device'])) {
							$parsed['device'] = $part;
						}
					}
				}
			} elseif (str_starts_with($userAgent, 'Roku')) {
				$parsed['machine'] = 'Roku';
				$parsed['operation'] = 'Roku';
				$parsed['device_type'] = 'tv';
			} elseif (str_starts_with($userAgent, 'AppleTV')) {
				$parsed['machine'] = 'Apple TV';
				$parsed['operation'] = 'tvOS';
				$parsed['device_type'] = 'tv';
			} elseif (stripos($systemInfo, 'Web0S') !== false || stripos($systemInfo, 'webOS') !== false) {
				$parsed['machine'] = 'LG TV';
				$parsed['operation'] = 'webOS';
				$parsed['device_type'] = 'tv';
			} elseif (stripos($systemInfo, 'CrOS') !== false) {
				$parsed['operation'] = 'ChromeOS';
				$parsed['machine'] = 'Chromebook';
				$parsed['device_type'] = 'desktop';
				if (preg_match('/CrOS\s+\w+\s+([\d.]+)/', $systemInfo, $crosMatch)) {
					$parsed['operation_version'] = $crosMatch[1];
				}
			} elseif (stripos($systemInfo, 'FreeBSD') !== false) {
				$parsed['operation'] = 'FreeBSD';
				$parsed['device_type'] = 'desktop';
			} elseif (stripos($systemInfo, 'OpenBSD') !== false) {
				$parsed['operation'] = 'OpenBSD';
				$parsed['device_type'] = 'desktop';
			} elseif (stripos($userAgent, 'HarmonyOS') !== false) {
				$parsed['operation'] = 'HarmonyOS';
				$parsed['device_type'] = 'mobile';
				$parsed['operation_version'] = self::extractVersion($userAgent, 'HarmonyOS');
			} elseif (stripos($userAgent, 'KAIOS') !== false) {
				$parsed['operation'] = 'KaiOS';
				$parsed['device_type'] = 'mobile';
				$parsed['operation_version'] = self::extractVersion($userAgent, 'KAIOS');

			} elseif (
				str_starts_with($firstPart, 'Linux') || $firstPart === 'X11' ||
				str_starts_with($firstPart, 'Windows') || $firstPart === 'SMART-TV' ||
				$firstPart === 'SAMSUNG' || $firstPart === 'PlayBook'
			) {
				if (str_starts_with($firstPart, 'Windows')) {
					$parsed['operation'] = $firstPart;
					$parsed['machine'] = $firstPart;
					$parsed['device_type'] = $parsed['device_type'] ?? 'desktop';
				} elseif ($firstPart === 'SMART-TV') {
					$parsed['operation'] = 'SMART-TV';
					$parsed['device_type'] = 'tv';
				} elseif ($firstPart === 'SAMSUNG') {
					$parsed['operation'] = 'SAMSUNG';
				} elseif ($firstPart === 'X11') {
					$parsed['operation'] = 'X11';
					$parsed['machine'] = 'X11';
					$parsed['device_type'] = $parsed['device_type'] ?? 'desktop';
				} elseif (str_starts_with($firstPart, 'Linux')) {
					$parsed['operation'] = 'Linux';
					$parsed['machine'] = 'Linux';
				} elseif ($firstPart === 'PlayBook') {
					$parsed['operation'] = 'PlayBook';
					$parsed['machine'] = 'PlayBook';
					$parsed['device_type'] = 'tablet';
				}

				$remainingParts = array_slice($systemParts, 1);
				foreach ($remainingParts as $part) {
					$part = trim($part);
					if ($part === '' || $part === 'U')
						continue;

					if (preg_match('/^Android\s+([\d.]+)$/', $part, $androidMatch)) {
						$parsed['device'] = 'Android';
						$parsed['device_version'] = $androidMatch[1];
						if (!isset($parsed['device_type']) || $parsed['device_type'] === 'desktop') {
							$parsed['device_type'] = 'mobile';
						}
					} elseif ($part === 'Android') {
						$parsed['device'] = 'Android';
						if (!isset($parsed['device_type']) || $parsed['device_type'] === 'desktop') {
							$parsed['device_type'] = 'mobile';
						}
					} elseif (preg_match('/^Tizen\s+([\d.]+)$/', $part, $tizenMatch)) {
						$parsed['operation'] = 'Tizen';
						$parsed['operation_version'] = $tizenMatch[1];
					} elseif (preg_match('/^[a-z]{2}[_-][A-Za-z]{2}$/', $part)) {
						$parsed['locale'] = $part;
					} elseif (preg_match('/^rv:[\d.]+$/', $part)) {
						continue;
					} elseif (preg_match('/^(WOW64|Win64|x64|x86_64|x86|i[36]86|arm|aarch64)$/i', $part)) {
						continue;
					} elseif (str_starts_with($part, 'wv')) {
						continue;
					} elseif (preg_match('/\b(?:Build\/[\w.]+|[\d]+\.[\d.]+)\b/i', $part) && !preg_match('/^Android/', $part)) {
						$parsed['device'] = $part;
					} elseif (!isset($parsed['device_raw'])) {
						$parsed['device_raw'] = $part;
					}
				}

				if (isset($parsed['device']) && $parsed['device'] === 'Android') {
					if (str_contains($userAgent, 'Tablet') || preg_match('/\bSM-T\w+\b|\bGT-P\w+\b/', $userAgent)) {
						$parsed['device_type'] = 'tablet';
					} else {
						$parsed['device_type'] = 'mobile';
					}
				}
			} else {
				$parsed['operation'] = $firstPart ?: null;
			}
		}

		// Engine
		if (str_contains($userAgent, 'AppleWebKit')) {
			$parsed['engine'] = 'WebKit';
			$parsed['engine_version'] = self::extractVersion($userAgent, 'AppleWebKit');
		} elseif (str_contains($userAgent, 'Gecko/')) {
			$parsed['engine'] = 'Gecko';
			$parsed['engine_version'] = self::extractVersion($userAgent, 'Gecko');
		} elseif (str_contains($userAgent, 'Presto')) {
			$parsed['engine'] = 'Presto';
			$parsed['engine_version'] = self::extractVersion($userAgent, 'Presto');
		} elseif (str_contains($userAgent, 'Trident')) {
			$parsed['engine'] = 'Trident';
			$parsed['engine_version'] = self::extractVersion($userAgent, 'Trident');
		}

		if ($parsed['is_bot']) {
			$parsed['browser'] = $parsed['browser'] ?? $parsed['bot_name'];
			$parsed['browser_version'] = $parsed['browser_version'] ?? $parsed['bot_version'] ?? null;
			$parsed['device_type'] = $parsed['device_type'] ?? 'bot';
			self::$parsedUserAgentCache[$userAgent] = $parsed;
			return self::$parsedUserAgentCache[$userAgent];
		}

		if (isset($parsed['browser'])) {
			$parsed['device_type'] = $parsed['device_type'] ?? 'desktop';
			self::$parsedUserAgentCache[$userAgent] = $parsed;
			return self::$parsedUserAgentCache[$userAgent];
		}

		$browsers = [
			['sig' => 'Puffin/', 'name' => 'Puffin Browser', 'ver' => 'Puffin'],
			['sig' => 'Mobile DuckDuckGo/', 'name' => 'DuckDuckGo Privacy Browser', 'ver' => 'DuckDuckGo'],
			['sig' => ' DuckDuckGo/', 'name' => 'DuckDuckGo Privacy Browser', 'ver' => 'DuckDuckGo'],
			['sig' => 'MQQBrowser/', 'name' => 'QQ Browser', 'ver' => 'MQQBrowser'],
			['sig' => 'QQBrowser/', 'name' => 'QQ Browser', 'ver' => 'QQBrowser'],
			['sig' => 'UCBrowser/', 'name' => 'UCBrowser', 'ver' => 'UCBrowser'],
			['sig' => 'Dolfin/', 'name' => 'Dolfin Browser', 'ver' => 'Dolfin'],
			['sig' => 'Kindle/', 'name' => 'Kindle Browser', 'ver' => 'Kindle'],
			['sig' => 'Silk/', 'name' => 'Silk', 'ver' => 'Silk'],
			['sig' => ' AVG/', 'name' => 'AVG Secure Browser', 'ver' => 'AVG'],
			['sig' => 'Midori/', 'name' => 'Midori', 'ver' => 'Midori'],
			['sig' => 'Whale/', 'name' => 'Whale', 'ver' => 'Whale'],
			['sig' => 'Brave Chrome/', 'name' => 'Brave', 'ver' => 'Brave Chrome'],
			['sig' => 'Brave Browser/', 'name' => 'Brave', 'ver' => 'Brave Browser'],
			['sig' => 'Vivaldi/', 'name' => 'Vivaldi', 'ver' => 'Vivaldi'],
			['sig' => 'YaBrowser/', 'name' => 'Yandex Browser', 'ver' => 'YaBrowser'],
			['sig' => 'Maxthon/', 'name' => 'Maxthon', 'ver' => 'Maxthon'],
			['sig' => 'Waterfox/', 'name' => 'Waterfox', 'ver' => 'Waterfox'],
			['sig' => 'PaleMoon/', 'name' => 'Pale Moon', 'ver' => 'PaleMoon'],
			['sig' => 'Basilisk/', 'name' => 'Basilisk', 'ver' => 'Basilisk'],
			['sig' => 'SeaMonkey/', 'name' => 'SeaMonkey', 'ver' => 'SeaMonkey'],
			['sig' => 'Falkon/', 'name' => 'Falkon', 'ver' => 'Falkon'],
			['sig' => 'Konqueror/', 'name' => 'Konqueror', 'ver' => 'Konqueror'],
			['sig' => 'Arora/', 'name' => 'Avant', 'ver' => 'Arora'],
			['sig' => 'NokiaBrowser/', 'name' => 'NokiaBrowser', 'ver' => 'NokiaBrowser'],
			['sig' => 'QupZilla/', 'name' => 'QupZilla', 'ver' => 'QupZilla'],
			['sig' => 'Focus/', 'name' => 'Firefox Focus', 'ver' => 'Focus'],
			['sig' => 'Iceweasel/', 'name' => 'Firefox', 'ver' => 'Iceweasel'],
			['sig' => 'Epic/', 'name' => 'Epic', 'ver' => 'Epic'],
			['sig' => 'OPR/', 'name' => 'Opera', 'ver' => 'OPR'],
			['sig' => 'OPT/', 'name' => 'Opera Touch', 'ver' => 'OPT'],
			['sig' => 'Edg/', 'name' => 'Edge', 'ver' => 'Edg'],
			['sig' => 'EdgA/', 'name' => 'Edge', 'ver' => 'EdgA'],
			['sig' => 'EdgiOS/', 'name' => 'Edge', 'ver' => 'EdgiOS'],
			['sig' => 'Edge/', 'name' => 'Edge', 'ver' => 'Edge'],
			['sig' => 'SamsungBrowser/', 'name' => 'SamsungBrowser', 'ver' => 'SamsungBrowser'],
			['sig' => 'FxiOS/', 'name' => 'Firefox', 'ver' => 'FxiOS'],
			['sig' => 'CriOS/', 'name' => 'Chrome', 'ver' => 'CriOS'],
			['sig' => 'Firefox/', 'name' => 'Firefox', 'ver' => 'Firefox'],
		];

		$browserDetected = false;
		foreach ($browsers as $b) {
			if (str_contains($userAgent, $b['sig'])) {
				$parsed['browser'] = $b['name'];
				$parsed['browser_version'] = self::extractVersion($userAgent, $b['ver']);
				$browserDetected = true;
				break;
			}
		}

		if (!$browserDetected) {
			if (str_starts_with($userAgent, 'Opera')) {
				$parsed['browser'] = 'Opera';
				$parsed['browser_version'] = self::extractVersion($userAgent, 'Version') ?? self::extractVersion($userAgent, 'Opera');
				$browserDetected = true;
			} elseif (str_contains($userAgent, 'Chrome/')) {
				$parsed['browser'] = 'Chrome';
				$parsed['browser_version'] = self::extractVersion($userAgent, 'Chrome');
				$browserDetected = true;
			}
		}

		if (!$browserDetected) {
			if (preg_match('/Version\/([\d.]+).*Safari/', $userAgent)) {
				$parsed['browser'] = 'Safari';
				$parsed['browser_version'] = self::extractVersion($userAgent, 'Version');
				$browserDetected = true;
			} elseif (str_contains($userAgent, 'Safari/')) {
				$parsed['browser'] = 'Safari';
				$parsed['browser_version'] = self::extractVersion($userAgent, 'Safari');
				$browserDetected = true;
			} elseif (str_contains($userAgent, 'MSIE')) {
				$parsed['browser'] = 'Internet Explorer';
				if (preg_match('/MSIE\s+([\d.]+)/', $userAgent, $ieMatch)) {
					$parsed['browser_version'] = $ieMatch[1];
				}
				$browserDetected = true;
			} elseif (str_contains($userAgent, 'Trident')) {
				$parsed['browser'] = 'Internet Explorer';
				if (preg_match('/rv:([\d.]+)/', $userAgent, $ieMatch)) {
					$parsed['browser_version'] = $ieMatch[1];
				}
				$browserDetected = true;
			}
		}

		// Old Android WebView: Version/X.X ... Mobile Safari/XXX (no Chrome/)
		if (($parsed['browser'] ?? '') === 'Safari' && !str_contains($userAgent, 'Chrome/') && !str_contains($userAgent, 'CriOS/')) {
			if (
				(isset($parsed['device']) && $parsed['device'] === 'Android') ||
				str_contains($userAgent, 'Android')
			) {
				if (str_contains($userAgent, 'Version/')) {
					$parsed['browser'] = 'Chrome';
					$parsed['browser_version'] = self::extractVersion($userAgent, 'Version');
				}
			}
		}

		// Chrome with Version/ + Chrome/ (Android WebView)
		if (($parsed['browser'] ?? '') === 'Chrome' && str_contains($userAgent, 'Version/') && str_contains($userAgent, 'Chrome/')) {
			$parsed['browser_version'] = self::extractVersion($userAgent, 'Chrome');
		}

		if (!isset($parsed['device_type'])) {
			if (str_contains($userAgent, 'Mobile') || str_contains($userAgent, 'Android')) {
				$parsed['device_type'] = 'mobile';
			} elseif (str_contains($userAgent, 'Tablet')) {
				$parsed['device_type'] = 'tablet';
			} elseif (str_contains($userAgent, 'TV') || str_contains($userAgent, 'SmartTV')) {
				$parsed['device_type'] = 'tv';
			} else {
				$parsed['device_type'] = 'desktop';
			}
		}

		self::$parsedUserAgentCache[$userAgent] = $parsed;

		return self::$parsedUserAgentCache[$userAgent];
	}

	/**
	 * Checks the User-Agent string against a list of known crawler signatures to determine if the request is from a crawler.
	 *
	 * @return bool|string The User-Agent string if it matches a known crawler, or false if it does not.
	 */
	public static function getCrawlerUserAgent(): bool|string
	{
		$useragent = strtolower(self::getUserAgent());

		$crawlerRegex = "/bot|archiver|apachebench|wget|curl|crawl|google|yahoo|slurp|wordpress|spider|yeti|daum|teoma|fish|hanrss|facebook|yandex|infoseek|askjeeves|stackrambler|spyder|watchmouse|pingdom\.com|feedfetcher-google/";

		if (preg_match($crawlerRegex, $useragent)) {
			return $useragent;
		}

		return false;
	}

	/**
	 * Checks if the current request is from a known crawler based on the User-Agent string.
	 *
	 * @return bool True if the request is from a crawler, false otherwise.
	 */
	public static function isCrawler(): bool
	{
		if (self::getCrawlerUserAgent()) {
			return true;
		}

		return false;
	}

	/**
	 * Checks if the current connection is using "keep-alive" based on the "Connection" header.
	 *
	 * @return bool True if the connection is keep-alive, false otherwise.
	 */
	public static function isConnectionKeepAlive(): bool
	{
		$connection = self::getHTTPConnection();

		return strtolower($connection) === 'keep-alive';
	}

	/**
	 * Checks if the current request has a referer that matches the current script URL.
	 *
	 * @return bool True if the referer matches the current script URL, false otherwise.
	 */
	public static function hasReferer(): bool
	{
		if (!isset($_SERVER[ServerIndices::HTTP_REFERER]) || !isset($_SERVER['SCRIPT_URL'])) {
			return false;
		}

		$referer = self::getReferrer();
		$url = $_SERVER['SCRIPT_URL'];

		return strpos($referer, $url) === 0;
	}

	/**
	 * Creates a multipart/form-data payload string for HTTP requests.
	 *
	 * @param string $boundary The boundary string to separate parts of the payload.
	 * @param array $fields An associative array of form fields (name => value).
	 * @param array $files An associative array of files (name => content).
	 * @return string The generated multipart/form-data payload.
	 */
	public static function createMultipartPayload($boundary, $fields, $files): string
	{
		$payload = '';
		$crlf = "\r\n";

		foreach ($fields as $fieldName => $fieldValue) {
			$payload .= "--{$boundary}{$crlf}";
			$payload .= "Content-Disposition: form-data; name=\"{$fieldName}\"{$crlf}{$crlf}";
			$payload .= "{$fieldValue}{$crlf}";
		}

		foreach ($files as $fileName => $fileContent) {
			$payload .= "--{$boundary}{$crlf}";
			$payload .= "Content-Disposition: form-data; name=\"{$fileName}\"; filename=\"{$fileName}\"{$crlf}";
			$payload .= "Content-Transfer-Encoding: binary{$crlf}{$crlf}";
			$payload .= "{$fileContent}{$crlf}";
		}

		$payload .= "--{$boundary}--{$crlf}";

		return $payload;
	}

	/**
	 * Converts the request information into an associative array format.
	 *
	 * @return array An associative array containing various details about the HTTP request.
	 */
	public function __toArray(): array
	{
		return [
			'hasReferer' => self::hasReferer(),
			'isConnectionKeepAlive' => self::isConnectionKeepAlive(),
			'isCrawler' => self::isCrawler(),
			'getCrawlerUserAgent' => self::getCrawlerUserAgent(),
			'isMobile' => self::isMobile(),
			'getRequestURL' => self::getRequestURL()->__toString(),
			'getRequestUri' => self::getRequestUri()->__toString(),
			'getExtractedQueryParameters' => self::getExtractedQueryParameters(),
			'getAcceptLanguage' => self::getAcceptLanguage(),
			'parseAcceptLanguage' => self::parseAcceptLanguage(),
			'getRemoteIPAddress' => self::getRemoteIPAddress()->__toString(),
			'getQueryString' => self::getQueryString()->__toString(),
			'getFullUri' => self::getFullUri()->__toString(),
			'getClientIP' => self::getClientIP(),
			'getCloudFlareProxyIP' => self::getCloudFlareProxyIP(),
			'getHTTPXForwardedFor' => self::getHTTPXForwardedFor(),
			'isAjax' => self::isAjax(),
			'getDocumentUrl' => self::getDocumentUrl(),
			'getAcceptEncoding' => self::getAcceptEncoding(),
			'getUserAgent' => self::getUserAgent(),
			'getServerProtocol' => self::getServerProtocol(),
			'getPort' => self::getPort(),
			'getAbsolutePathOfDocumentRoot' => self::getAbsolutePathOfDocumentRoot(),
			'getServerSoftwareName' => self::getServerSoftwareName(),
			'getHttpHost' => self::getHttpHost(),
			'getURI' => self::getURI(),
			'getProtocol' => self::getProtocol(),
			'getScheme' => self::getScheme(),
			'getUrlPath' => self::getUrlPath(),
			'getUrlPathSegments' => self::getUrlPathSegments(),
			'fetchAllResponseHeaders' => self::fetchAllResponseHeaders(),
			'isHttpsProtocol' => self::isHttpsProtocol(),
			'isSecure' => self::isSecure(),
			'getBrowserInformation' => self::getBrowserInformation()
		];
	}
}
