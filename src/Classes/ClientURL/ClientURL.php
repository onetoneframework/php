<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

#region use

use Clover\Classes\ClientURLErrorResponse;
use Clover\Classes\ClientURLLastTransferInformation;
use Clover\Classes\ClientURLOption;
use Clover\Classes\Data as DataObject;
use Clover\Classes\Data\CSVHandler;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Data\StringObject;
use Clover\Classes\Data\URLObject;
use Clover\Classes\Format\MultiPurposeInternetMailExtensions;
use Clover\Classes\XML\SimpleXML;
use Clover\Implement\ClientURLInterface;
use function in_array;
use function is_array;
use function is_resource;
use function strlen;
use function is_string;
use ErrorException;
use Exception;
use CURLFile;
use CurlHandle;

#endregion

/**
 * Class ClientURL
 *
 * Provides a wrapper around cURL functions to facilitate making HTTP requests and handling responses.
 * 
 * @package Clover\Classes
 */
class ClientURL extends BaseClass implements ClientURLInterface
{
	#region properties

	/**
	 * Middleware callables executed before `curl_exec()`.
	 *
	 * Signature: `fn(ClientURL $client): void`
	 *
	 * @var array<callable(ClientURL)> $requestMiddleware
	 */
	private array $requestMiddleware = [];

	/**
	 * Middleware callables executed after `curl_exec()`.
	 *
	 * Signature: `fn(ClientURL $client, mixed $response): mixed`
	 *
	 * @var array<callable(ClientURL, mixed)> $responseMiddleware
	 */
	private array $responseMiddleware = [];

	/** @var resource|CurlHandle $handle */
	private $handle;

	/** @var ClientURLOption $option */
	public ClientURLOption $option;

	/** @var ClientURLLastTransferInformation $information */
	public ClientURLLastTransferInformation $information;

	#endregion

	#region function

	/**
	 * Construct of class
	 * 
	 * @param string|StringObject $url Initial URL for the cURL session (optional)
	 * @param bool $useLocalMethod Whether to use local method for setting options and getting information (default: true)
	 * 
	 * @throws ErrorException if cURL extension is not loaded
	 */
	public function __construct(string|StringObject $url = '', bool $useLocalMethod = true)
	{
		if (!$this->isSupported()) {
			throw new ErrorException('The CURL library is not loaded');
		}

		if ($url instanceof StringObject) {
			$url = $url->__toString();
		}

		$this->handle = $this->getSession($url);

		if ($useLocalMethod) {
			$this->option = new ClientURLOption($this->handle);
			$this->information = new ClientURLLastTransferInformation($this->handle);
		}
	}

	/**
	 * Register a callable that runs before each `execute()`.
	 *
	 * Useful for logging, header injection, or metrics.
	 *
	 * @param callable(ClientURL): void $middleware
	 */
	public function addRequestMiddleware(callable $middleware): static
	{
		$this->requestMiddleware[] = $middleware;
		return $this;
	}

	/**
	 * Register a callable that runs after each `execute()`.
	 *
	 * The callable receives the raw response and may return a
	 * transformed value.
	 *
	 * @param callable(ClientURL, mixed): mixed $middleware
	 */
	public function addResponseMiddleware(callable $middleware): static
	{
		$this->responseMiddleware[] = $middleware;
		return $this;
	}

	/**
	 * Static download method
	 * 
	 * @param string $requestURL URL to send the request to
	 * @param string|null $domain Optional domain to set in the Origin and Referer headers (defaults to the domain of the request URL)
	 * 
	 * @return mixed Response from the request, or false on failure
	 */
	public static function download(string $requestURL, string|null $domain = null): mixed
	{
		$url = new URLObject($requestURL);
		$cURL = new ClientURL($requestURL);

		$cURL->option
			->setURL($requestURL)
			->setGetMethod(true)
			->setSSLVerifyPeer(false)
			->setSSLVerifyHost(false)
			->setFollowRedirects(true)
			->setHeader('Origin', $domain ?? $url->getDomain())
			->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/47.0.2526.111 Safari/537.36 OPR/34.0.2036.50')
			->setHeader('Referer', $domain ?? $url->getDomain())
			->setHeader('Accept', '*/*')
			->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
			->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7')
			->setReturnTransfer(true)
			->setAutoReferer(true)
			->setReturnHeader(true);

		$response = $cURL->execute();
		$cURL->close();

		return $response;
	}

	/**
	 * Get error message by error code
	 * 
	 * @param int $error_code cURL error code
	 * 
	 * @return string|null Error message corresponding to the error code, or null if the error code is not recognized
	 */
	public function getErrorMessageByCode(int $error_code): string|null
	{
		return curl_strerror($error_code);
	}

	/**
	 * Escape string
	 * 
	 * @param string $string String to escape
	 * 
	 * @return bool|string Escaped string on success, false on failure
	 */
	public function escapeString(string $string): bool|string
	{
		return curl_escape($this->handle, $string);
	}

	/**
	 * Unescape string
	 * 
	 * @param string $string String to unescape
	 * 
	 * @return bool|string Unescaped string on success, false on failure
	 */
	public function unescapeString(string $string): bool|string
	{
		return curl_unescape($this->handle, $string);
	}

	/**
	 * Pause connection
	 * 
	 * @param int $flags Flags to specify which operations to pause (CURLPAUSE_RECV, CURLPAUSE_RECV_CONT, CURLPAUSE_SEND, CURLPAUSE_SEND_CONT)
	 * 
	 * @return int 0 on success, or a cURL error code on failure
	 */
	public function pauseConnection(int $flags): int
	{
		return curl_pause($this->handle, $flags);
	}

	/**
	 * Get cURL version
	 * 
	 * @return array{
	 * 	version_number: number, 
	 * 	version: string, 
	 * 	ssl_version_number: number, 
	 * 	ssl_version: string, 
	 * 	libz_version: string, 
	 * 	host: mixed, 
	 * 	age: mixed, 
	 * 	features: mixed, 
	 * 	protocols: mixed, 
	 * 	feature_list: mixed
	 * }|bool
	 */
	public function getVersion(): array|bool
	{
		return curl_version();
	}

	/**
	 * Check whether cURL is supported
	 * 
	 * @return bool True if cURL extension is loaded, false otherwise
	 */
	public function isSupported(): bool
	{
		return extension_loaded('curl');
	}

	/**
	 * Copy cURL handle
	 * 
	 * @return bool|CurlHandle|resource Copied cURL handle on success, false on failure
	 */
	public function copyHandle(): mixed
	{
		return curl_copy_handle($this->handle);
	}

	/**
	 * Get cURL session handle
	 * 
	 * @param string|StringObject|null $url Optional URL to initialize the cURL session with (if not already initialized)
	 * 
	 * @return resource|CurlHandle cURL session handle
	 */
	public function getSession(string|StringObject|null $url = null): mixed
	{
		if ($this->handle == null) {
			$this->handle = $this->initialize($url);
		}

		return $this->handle;
	}

	/**
	 * Destruct of class
	 */
	public function __destruct()
	{
		$this->handle = null;
	}

	/**
	 * Get last error message
	 * 
	 * @return string Last error message from the cURL session
	 */
	public function getLastErrorMessage(): string
	{
		return curl_error($this->handle);
	}

	/**
	 * Get last error number
	 * 
	 * @return int Last error number from the cURL session
	 */
	public function getLastErrorNumber(): int
	{
		return curl_errno($this->handle);
	}

	/**
	 * Initialize cURL session
	 * 
	 * @param string|StringObject|null $instance Optional URL to initialize the cURL session with (if not already initialized)
	 * 
	 * @return mixed Initialized cURL session handle
	 */
	public function initialize(string|StringObject|null $instance = null): mixed
	{
		return curl_init((string) $instance);
	}

	/**
	 * Reset cURL session
	 */
	public function reset(): void
	{
		if (function_exists('curl_reset')) {
			curl_reset($this->handle);
		}
	}

	/**
	 * Get ClientURLOption instance
	 * 
	 * @return \Clover\Classes\ClientURLOption|null
	 */
	public function option(): ?ClientURLOption
	{
		if (!$this->option) {
			$this->option = new ClientURLOption($this->handle);
		}

		return $this->option;
	}

	/**
	 * Get ClientURLLastTransferInformation instance
	 * 
	 * @return \Clover\Classes\ClientURLLastTransferInformation|null
	 */
	public function information(): ?ClientURLLastTransferInformation
	{
		if (!$this->information) {
			$this->information = new ClientURLLastTransferInformation($this->handle);
		}

		return $this->information;
	}

	/**
	 * Set cURL option
	 * 
	 * @param int $option cURL option constant (e.g., CURLOPT_RETURNTRANSFER)
	 * @param mixed $value Value to set for the specified cURL option
	 * 
	 * @return bool True on success, false on failure
	 */
	public function setOption(int $option, mixed $value): bool
	{
		$this->option::$curlOptions[$option] = $value;

		return curl_setopt($this->handle, $option, $value);
	}

	/**
	 * Close cURL session
	 * 
	 * @return void
	 */
	public function close(): void
	{
		if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
			if (is_resource($this->handle)) {
				// @phpstan-ignore-next-line
				curl_close($this->handle);
			}
		}
	}

	/**
	 * Get cURL options
	 * 
	 * @return array Current cURL options set for the session
	 */
	public function getOptions(): array
	{
		return $this->option::$curlOptions;
	}

	/**
	 * Get ClientURLOption instance
	 * 
	 * @return ClientURLOption Current ClientURLOption instance associated with this ClientURL
	 */
	public function getOption(): ClientURLOption
	{
		return $this->option;
	}

	/**
	 * Get header options
	 * 
	 * @return array Current header options set for the cURL session
	 */
	public function getHeaderOptions(): array
	{
		return $this->option::$headers;
	}

	/**
	 * Execute cURL session with decode
	 * 
	 * @param bool $associative Whether to return decoded JSON as an associative array (true) or an object (false) when content type is JSON (default: true)
	 * @param bool $toPHPObject Whether to convert decoded JSON to a PHP object when content type is JSON (default: false)
	 * 
	 * @return mixed Decoded response based on content type (JSON, XML, CSV) or raw response if content type is not recognized
	 */
	public function executeWithDecode(bool $associative = true, bool $toPHPObject = false): mixed
	{
		$result = $this->execute();
		$contentType = $this->information()->getContentType();
		$contentTypes = explode(";", $contentType);
		$detectiveContentType = null;

		foreach ($contentTypes as $contentType) {
			$contentType = trim($contentType);
			if (in_array($contentType, MultiPurposeInternetMailExtensions::getContentTypeFromExtension('xml', true))) {
				$detectiveContentType = "xml";
			} else if (in_array($contentType, MultiPurposeInternetMailExtensions::getContentTypeFromExtension('json', true))) {
				$detectiveContentType = "json";
			} else if (in_array($contentType, MultiPurposeInternetMailExtensions::getContentTypeFromExtension('csv', true))) {
				$detectiveContentType = "csv";
			}
		}

		if ($detectiveContentType == null) {
			if (JSONHandler::isJSON($result)) {
				$detectiveContentType = "json";
			} else if (SimpleXML::isXML($result)) {
				$detectiveContentType = "xml";
			} else if (CSVHandler::isCsv($result)) {
				$detectiveContentType = "csv";
			} else {
				$detectiveContentType = "raw";
			}
		}

		if ($detectiveContentType == "json") {
			$decoded = JSONHandler::decode($result, $associative);
			return $toPHPObject ? (object) $decoded->toPHPObject() : $decoded;
		} else if ($detectiveContentType == "csv") {
			return CSVHandler::decode($result);
		} else if ($detectiveContentType == "xml") {
			$xml = new SimpleXML();
			$xml->parse($result);
			return $xml->toObjectData($xml->getData());
		}

		return $result;
	}

	/**
	 * Execute cURL session
	 * 
	 * @return mixed Response from the cURL session, or false on failure
	 * 
	 * @throws Exception if a cURL error occurs during execution
	 */
	public function execute(): mixed
	{
		// Pre-request middleware
		foreach ($this->requestMiddleware as $middleware) {
			$middleware($this);
		}

		$result = curl_exec($this->handle);

		if (curl_errno($this->handle)) {
			throw new Exception('Curl error: ' . curl_error($this->handle));
		}

		$response = (new DataObject($result))->toObject();

		// Post-response middleware
		foreach ($this->responseMiddleware as $middleware) {
			$response = $middleware($this, $response);
		}

		return $response;
	}

	/**
	 * Execute async request using multi handle
	 * 
	 * @param array $urls List of URLs to send requests to, each item should be a string URL
	 * @param array $options Optional cURL options to set for each request, specified as an associative array where keys are cURL option constants and values are the corresponding option values
	 * 
	 * @return array List of responses for each request, where each item is an associative array containing 'content' (response content), 'info' (cURL information), and 'error' (cURL error message if any)
	 */
	public static function multiRequest(array $urls, array $options = []): array
	{
		$multiHandle = curl_multi_init();
		$handles = [];
		$results = [];

		foreach ($urls as $key => $url) {
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

			foreach ($options as $opt => $val) {
				curl_setopt($ch, $opt, $val);
			}

			curl_multi_add_handle($multiHandle, $ch);
			$handles[$key] = $ch;
		}

		$running = null;
		do {
			curl_multi_exec($multiHandle, $running);
			curl_multi_select($multiHandle);
		} while ($running > 0);

		foreach ($handles as $key => $ch) {
			$results[$key] = [
				'content' => curl_multi_getcontent($ch),
				'info' => curl_getinfo($ch),
				'error' => curl_error($ch)
			];
			curl_multi_remove_handle($multiHandle, $ch);
			if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
				// @phpstan-ignore-next-line
				curl_close($ch);
			}
		}

		curl_multi_close($multiHandle);

		return $results;
	}

	/**
	 * Send GET request
	 * 
	 * @param string $url URL to send the GET request to
	 * @param array<string, mixed> $params Optional query parameters to include in the URL, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * 
	 * @return mixed Response from the GET request, or false on failure
	 */
	public static function get(string $url, array $params = [], array $headers = []): mixed
	{
		if (!empty($params)) {
			$url .= '?' . http_build_query($params);
		}

		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setGetMethod(true)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setFollowRedirects(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send POST request
	 * 
	 * @param string $url URL to send the POST request to
	 * @param array<string, mixed> $data Optional data to include in the POST request body, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * 
	 * @return mixed Response from the POST request, or false on failure
	 */
	public static function post(string $url, array $data = [], array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setPostMethod(true)
			->setPostFields($data)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setFollowRedirects(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send PUT request
	 * 
	 * @param string $url URL to send the PUT request to
	 * @param array<string, mixed> $data Optional data to include in the PUT request body, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * 
	 * @return mixed Response from the PUT request, or false on failure
	 */
	public static function put(string $url, array $data = [], array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setPutMethod()
			->setPostField(JSONHandler::encode($data))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setContentTypeApplicationJson();

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send DELETE request
	 * 
	 * @param string $url URL to send the DELETE request to
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * 
	 * @return mixed Response from the DELETE request, or false on failure
	 */
	public static function delete(string $url, array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest('DELETE')
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send PATCH request
	 * 
	 * @param string $url URL to send the PATCH request to
	 * @param array<string, mixed> $data Optional data to include in the PATCH request body, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * 
	 * @return mixed Response from the PATCH request, or false on failure
	 */
	public static function patch(string $url, array $data = [], array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest('PATCH')
			->setPostField(JSONHandler::encode($data))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setContentTypeApplicationJson();

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send JSON request
	 * 
	 * @param string $url URL to send the request to
	 * @param string $method HTTP method to use for the request (e.g., 'POST', 'PUT', 'PATCH', 'DELETE')
	 * @param array<string, mixed> $data Optional data to include in the request body, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * @param bool|null $associative Whether to return decoded JSON as an associative array (true) or an object (false) when content type is JSON (default: true)
	 * 
	 * @return mixed Decoded response from the request based on content type (JSON) or raw response if content type is not JSON, or false on failure
	 */
	public static function json(string $url, string $method = 'POST', array $data = [], array $headers = [], bool|null $associative = true): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setContentTypeApplicationJson()
			->setHeader('Accept', 'application/json');

		if (!empty($data)) {
			$curl->option->setPostField(JSONHandler::encode($data));
		}

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return JSONHandler::decode($response, $associative, raw: true);
	}

	/**
	 * Upload file
	 * 
	 * @param string $url URL to send the file upload request to
	 * @param string $filePath Path to the file to be uploaded
	 * @param string $fieldName Name of the form field for the file upload (default: 'file')
	 * @param null|string $mimeType
	 * @param null|string $postedFileName
	 * @param array<string, mixed> $additionalData Optional additional form data to include in the request, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * 
	 * @return mixed Response from the file upload request, or false on failure
	 */
	public static function uploadFile(string $url, string $filePath, string $fieldName = 'file', ?string $mimeType = null, ?string $postedFileName = null, array $additionalData = [], array $headers = []): mixed
	{
		if (!file_exists($filePath)) {
			throw new Exception("File not found: {$filePath}");
		}

		$curl = new self($url);

		$postData = array_merge($additionalData, [
			$fieldName => new CURLFile($filePath, $mimeType, $postedFileName)
		]);

		$curl->option
			->setURL($url)
			->setPostMethod(true)
			->setPostFields($postData)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Download file to path
	 * 
	 * @param string $url URL of the file to be downloaded
	 * @param string $savePath Path where the downloaded file should be saved, including the filename and extension
	 * 
	 * @return bool True if the file was downloaded and saved successfully, false otherwise
	 */
	public static function downloadToFile(string $url, string $savePath): bool
	{
		$fp = fopen($savePath, 'w+');
		if (!$fp) {
			return false;
		}

		$curl = new self($url);

		$curl->option
			->setFileHandler($fp)
			->setSSLVerifyPeer(false)
			->setFollowLocationHeader()
			->setReturnTransfer(true)
			->setTimeout(30);

		$result = $curl->execute();
		$httpCode = $curl->getLastHttpCode();

		$curl->close();
		fclose($fp);

		if (file_exists($savePath) && (!$result || $httpCode >= 400)) {
			unlink($savePath);
			return false;
		}

		return true;
	}

	/**
	 * Get response headers only
	 * 
	 * @param string $url URL to send the HEAD request to
	 * 
	 * @return array Array containing 'headers' (response headers) and 'info' (cURL information about the request)
	 */
	public static function head(string $url): array
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest('HEAD')
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setReturnHeader(true)
			->setNobody(true);

		$response = $curl->execute();
		$info = $curl->information()->getAll();
		$curl->close();

		return [
			'headers' => $response,
			'info' => $info
		];
	}

	/**
	 * Check if URL is reachable
	 * 
	 * @param string $url URL to check for reachability
	 * @param int $timeout Timeout in seconds for the request (default: 10 seconds)
	 * 
	 * @return bool True if the URL is reachable (HTTP status code 200-399), false otherwise
	 */
	public static function isReachable(string $url, int $timeout = 10): bool
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		return $httpCode >= 200 && $httpCode < 400;
	}

	/**
	 * Get with retry
	 * 
	 * @param string $url URL to send the GET request to
	 * @param int $maxRetries Maximum number of retry attempts if the request fails (default: 3)
	 * @param int $delay Initial delay in milliseconds between retry attempts (default: 1000 ms), which will be doubled after each failed attempt for exponential backoff
	 * 
	 * @return mixed Response from the GET request if successful within the retry attempts, or throws an exception if all attempts fail
	 */
	public static function getWithRetry(string $url, int $maxRetries = 3, int $delay = 1000): mixed
	{
		$lastException = null;

		for ($i = 0; $i < $maxRetries; $i++) {
			try {
				return self::get($url);
			} catch (Exception $e) {
				$lastException = $e;
				usleep($delay * 1000);
				$delay *= 2;
			}
		}

		throw $lastException;
	}

	/**
	 * Send request with bearer token
	 * 
	 * @param string $url URL to send the request to
	 * @param string $token Bearer token to include in the Authorization header for authentication
	 * @param string $method HTTP method to use for the request (e.g., 'GET', 'POST', 'PUT', 'PATCH', 'DELETE')
	 * @param array<string, mixed> $data Optional data to include in the request body, specified as an associative array where keys are parameter names and values are parameter values
	 * 
	 * @return mixed Response from the request, or false on failure
	 */
	public static function withBearerToken(string $url, string $token, string $method = 'GET', array $data = []): mixed
	{
		return self::json($url, $method, $data, [
			'Authorization' => "Bearer {$token}"
		]);
	}

	/**
	 * Send request with basic auth
	 * 
	 * @param string $url URL to send the request to
	 * @param string $username Username for basic authentication
	 * @param string $password Password for basic authentication
	 * @param string $method HTTP method to use for the request (e.g., 'GET', 'POST', 'PUT', 'PATCH', 'DELETE')
	 * @param array<string, mixed> $data Optional data to include in the request body, specified as an associative array where keys are parameter names and values are parameter values
	 * 
	 * @return mixed Response from the request, or false on failure
	 */
	public static function withBasicAuth(string $url, string $username, string $password, string $method = 'GET', array $data = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setBasicAuthentication($username, $password);

		if (!empty($data)) {
			$curl->option->setPostField(http_build_query($data));
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send form data
	 * 
	 * @param string $url URL to send the form data to
	 * @param array<string, mixed> $data Form data to include in the POST request body, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * @return mixed Response from the POST request, or false on failure
	 */
	public static function postForm(string $url, array $data, array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setPostMethod(true)
			->setPostField($data, true)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setContentTypeFormUrlEncoded();

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Stream response to callback
	 * 
	 * @param string $url URL to send the request to
	 * @param callable $callback Callback function to handle the streamed response data, which should accept a single parameter for the data chunk received from the response
	 * @return void
	 */
	public static function stream(string $url, callable $callback): void
	{
		$curl = new self($url);
		$curl->option
			->setReturnTransfer(false)
			->setSSLVerifyPeer(false)
			->setWriteFunction(function ($ch, $data) use ($callback) {
				$callback($data);
				return strlen($data);
			});

		$curl->execute();
		$curl->close();
	}

	/**
	 * Get response with full details
	 * 
	 * @param string $url URL to send the request to
	 * @param string $method HTTP method to use for the request (e.g., 'GET', 'POST', 'PUT', 'PATCH', 'DELETE')
	 * @param array<string, mixed> $data Optional data to include in the request body, specified as an associative array where keys are parameter names and values are parameter values
	 * @param array<string, string> $headers Optional headers to include in the request, specified as an associative array where keys are header names and values are header values
	 * 
	 * @return array{
	 * 	status: mixed, 
	 * 	headers: array, 
	 * 	body: string, 
	 * 	info: array{
	 * 		certinfo: mixed, 
	 * 		connect_time: mixed, 
	 * 		content_type: mixed, 
	 * 		download_content_length: mixed, 
	 * 		filetime: mixed, 
	 * 		header_size: mixed, 
	 * 		http_code: mixed, 
	 * 		local_ip: mixed, 
	 * 		local_port: mixed, 
	 * 		namelookup_time: mixed, 
	 * 		posttransfer_time_us: mixed, 
	 * 		pretransfer_time: mixed, 
	 * 		primary_ip: mixed, 
	 * 		primary_port: mixed, 
	 * 		redirect_count: mixed, 
	 * 		redirect_time: mixed, 
	 * 		redirect_url: mixed, 
	 * 		request_header: mixed, 
	 * 		request_size: mixed, 
	 * 		size_download: mixed, 
	 * 		size_upload: mixed, 
	 * 		speed_download: mixed, 
	 * 		speed_upload: mixed, 
	 * 		ssl_verify_result: mixed, 
	 * 		starttransfer_time: mixed, 
	 * 		total_time: mixed, 
	 * 		upload_content_length: mixed, 
	 * 		url: mixed
	 * 	}
	 * }|mixed} Associative array containing 'status' (HTTP status code), 'headers' (response headers), 'body' (response body), and 'info' (cURL information about the request)
	 */
	public static function request(string $url, string $method = 'GET', array $data = [], array $headers = []): array
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setReturnHeader(true);

		if (!empty($data)) {
			if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
				$curl->option
					->setPostFields(is_array($data) ? JSONHandler::encode($data) : $data)
					->setContentTypeApplicationJson();
			}
		}

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$info = $curl->information()->getAll();
		$headerSize = $curl->information()->getHeaderSize();

		$responseHeaders = substr($response, 0, $headerSize);
		$body = substr($response, $headerSize);

		$curl->close();

		return [
			'status' => $info['http_code'],
			'headers' => self::parseHeaders($responseHeaders),
			'body' => $body,
			'info' => $info
		];
	}

	/**
	 * Parse response headers string
	 * 
	 * @param string $headerString Raw response headers string to parse into an associative array of header names and values
	 * 
	 * @return array Associative array of parsed headers, where keys are header names and values are header values
	 */
	private static function parseHeaders(string $headerString): array
	{
		$headers = [];
		$lines = explode("\r\n", $headerString);

		foreach ($lines as $line) {
			if (strpos($line, ':') !== false) {
				[$key, $value] = explode(':', $line, 2);
				$headers[trim($key)] = trim($value);
			}
		}

		return $headers;
	}

	/**
	 * Check whether specific TLS version is supported
	 * 
	 * @param int $constaints TLS version constraint constant to check for support (e.g., CURL_SSLVERSION_TLSv1_2, CURL_SSLVERSION_TLSv1_3)
	 * 
	 * @return bool|int True if the specified TLS version is supported, false if cURL version information is not available, or the result of bitwise AND operation between the provided constraints and the features supported by cURL if version information is available
	 */
	public function isSupportSpecifyTlsVersion(int $constaints): bool|int
	{
		$version = $this->getVersion();
		if (!$version || !isset($version['features'])) {
			return false;
		}

		return $constaints & $version['features'];
	}

	/**
	 * Check whether TLS 1.2 is supported
	 * 
	 * @return bool|int True if TLS 1.2 is supported, false if cURL version information is not available, or the result of bitwise AND operation between CURL_SSLVERSION_TLSv1_2 and the features supported by cURL if version information is available
	 */
	public function isSupportTls12(): bool|int
	{
		return $this->isSupportSpecifyTlsVersion(\CURL_SSLVERSION_TLSv1_2);
	}

	/**
	 * Check whether TLS 1.3 is supported
	 * 
	 * @return bool|int True if TLS 1.3 is supported, false if cURL version information is not available, or the result of bitwise AND operation between CURL_SSLVERSION_TLSv1_3 and the features supported by cURL if version information is available
	 */
	public function isSupportTls13(): bool|int
	{
		return $this->isSupportSpecifyTlsVersion(\CURL_SSLVERSION_TLSv1_3);
	}

	/**
	 * Send OPTIONS request to discover allowed HTTP methods
	 * 
	 * @param string $url URL to send the OPTIONS request to
	 * @param array<string, string> $headers Optional headers to include in the request
	 * 
	 * @return array{
	 * 	allowed_methods: string[], 
	 * 	headers: array, 
	 * 	info: array{
	 * 		certinfo: mixed, 
	 * 		connect_time: mixed, 
	 * 		content_type: mixed, 
	 * 		download_content_length: mixed, 
	 * 		filetime: mixed, 
	 * 		header_size: mixed, 
	 * 		http_code: mixed, 
	 * 		local_ip: mixed, 
	 * 		local_port: mixed, 
	 * 		namelookup_time: mixed, 
	 * 		posttransfer_time_us: mixed, 
	 * 		pretransfer_time: mixed, 
	 * 		primary_ip: mixed, 
	 * 		primary_port: mixed, 
	 * 		redirect_count: mixed, 
	 * 		redirect_time: mixed, 
	 * 		redirect_url: mixed, 
	 * 		request_header: mixed, 
	 * 		request_size: mixed, 
	 * 		size_download: mixed, 
	 * 		size_upload: mixed, 
	 * 		speed_download: mixed, 
	 * 		speed_upload: mixed, 
	 * 		ssl_verify_result: mixed, 
	 * 		starttransfer_time: mixed, 
	 * 		total_time: mixed, 
	 * 		upload_content_length: mixed, 
	 * 		url: mixed
	 * 	}
	 * }|mixed} Associative array containing 'allowed_methods' (parsed Allow header), 'headers' (full response headers), and 'info' (cURL information)
	 */
	public static function options(string $url, array $headers = []): array
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest('OPTIONS')
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setReturnHeader(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$info = $curl->information()->getAll();
		$headerSize = $curl->information()->getHeaderSize();
		$responseHeaders = self::parseHeaders(substr($response, 0, $headerSize));
		$curl->close();

		$allowedMethods = [];
		if (isset($responseHeaders['Allow'])) {
			$allowedMethods = array_map('trim', explode(',', $responseHeaders['Allow']));
		}

		return [
			'allowed_methods' => $allowedMethods,
			'headers' => $responseHeaders,
			'info' => $info,
		];
	}

	/**
	 * Upload multiple files in a single request
	 * 
	 * @param string $url URL to send the multi-file upload request to
	 * @param array<string, string> $files Associative array where keys are field names and values are file paths
	 * @param array<string, mixed> $additionalData Optional additional form data to include in the request
	 * @param array<string, string> $headers Optional headers to include in the request
	 * 
	 * @return mixed Response from the upload request, or false on failure
	 * 
	 * @throws Exception if any file does not exist
	 */
	public static function uploadFiles(string $url, array $files, array $additionalData = [], array $headers = []): mixed
	{
		$postData = $additionalData;

		foreach ($files as $fieldName => $filePath) {
			if (!file_exists($filePath)) {
				throw new Exception("File not found: {$filePath}");
			}

			if (isset($postData[$fieldName])) {
				throw new Exception("`{$fieldName}` field is already used");
			}

			$postData[$fieldName] = new CURLFile($filePath);
		}

		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setPostMethod(true)
			->setPostFields($postData)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send request with custom timeout settings
	 * 
	 * @param string $url URL to send the request to
	 * @param string $method HTTP method
	 * @param int $connectTimeout Maximum time in seconds to wait for connection
	 * @param int $timeout Maximum total time in seconds for the request
	 * @param array<string, mixed> $data Optional data to include in the request body
	 * @param array<string, string> $headers Optional headers to include in the request
	 * 
	 * @return mixed Response from the request, or false on failure
	 */
	public static function withTimeout(string $url, string $method = 'GET', int $connectTimeout = 5, int $timeout = 30, array $data = [], array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setConnectionTimeout($connectTimeout)
			->setTimeout($timeout);

		if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
			$curl->option
				->setPostField(JSONHandler::encode($data))
				->setContentTypeApplicationJson();
		}

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send a request through a proxy server
	 * 
	 * @param string $url URL to send the request to
	 * @param string $proxy Proxy address in the format host:port
	 * @param string $method HTTP method
	 * @param string|null $proxyAuth Proxy authentication in the format username:password
	 * @param array<string, mixed> $data Optional data for the request body
	 * @param array<string, string> $headers Optional headers
	 * 
	 * @return mixed Response from the request, or false on failure
	 */
	public static function withProxy(string $url, string $proxy, string $method = 'GET', ?string $proxyAuth = null, array $data = [], array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setProxy($proxy);

		if ($proxyAuth !== null) {
			$curl->option->setProxyUserPassword($proxyAuth);
		}

		if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
			$curl->option
				->setPostField(JSONHandler::encode($data))
				->setContentTypeApplicationJson();
		}

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send request with cookie jar for session persistence
	 * 
	 * @param string $url URL to send the request to
	 * @param string $cookieFile Path to the cookie file for read/write
	 * @param string $method HTTP method
	 * @param array<string, mixed> $data Optional data for the request body
	 * @param array<string, string> $headers Optional headers
	 * 
	 * @return mixed Response from the request, or false on failure
	 */
	public static function withCookies(string $url, string $cookieFile, string $method = 'GET', array $data = [], array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setCookieFile($cookieFile)
			->setCookieJar($cookieFile);

		if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
			$curl->option
				->setPostField(JSONHandler::encode($data))
				->setContentTypeApplicationJson();
		}

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send multipart form data with files and fields mixed
	 * 
	 * @param string $url URL to send the request to
	 * @param array<string, mixed> $fields Form fields as key-value pairs
	 * @param array<string, array{path: string, mime?: string, name?: string}> $files Files to upload where each value has 'path' and optional 'mime' and 'name'
	 * @param array<string, string> $headers Optional headers
	 * 
	 * @return mixed Response from the request, or false on failure
	 * 
	 * @throws Exception if any file does not exist
	 */
	public static function multipart(string $url, array $fields = [], array $files = [], array $headers = []): mixed
	{
		$postData = $fields;

		foreach ($files as $fieldName => $fileInfo) {
			$filePath = $fileInfo['path'];
			if (!file_exists($filePath)) {
				throw new Exception("File not found: {$filePath}");
			}

			if (isset($postData[$fieldName])) {
				throw new Exception("`{$fieldName}` field is already used");
			}

			$postData[$fieldName] = new CURLFile($filePath, $fileInfo['mime'] ?? null, $fileInfo['name'] ?? null);
		}

		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setPostMethod(true)
			->setPostFields($postData)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Send concurrent async requests using curl_multi and return results with detailed info
	 * 
	 * @param array<int|string, array{url: string, method?: string, data?: array, headers?: array}> $requests Array of request configurations
	 * @param int $maxConcurrency Maximum number of simultaneous connections (default: 10)
	 * 
	 * @return array<int|string, array{content: string, info: array, error: string, http_code: int}> Array of results keyed by the same keys as input
	 */
	public static function asyncMultiRequest(array $requests, int $maxConcurrency = 10): array
	{
		$multiHandle = curl_multi_init();
		curl_multi_setopt($multiHandle, CURLMOPT_MAXCONNECTS, $maxConcurrency);

		$handles = [];
		$results = [];

		foreach ($requests as $key => $config) {
			$ch = curl_init($config['url']);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 30);

			$method = strtoupper($config['method'] ?? 'GET');
			if ($method !== 'GET') {
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
			}

			if (!empty($config['data']) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
				$encoded = JSONHandler::encode($config['data']);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge(
					['Content-Type: application/json'],
					self::formatHeaders($config['headers'] ?? [])
				));
			} else {
				$formatted = self::formatHeaders($config['headers'] ?? []);
				if (!empty($formatted)) {
					curl_setopt($ch, CURLOPT_HTTPHEADER, $formatted);
				}
			}

			curl_multi_add_handle($multiHandle, $ch);
			$handles[$key] = $ch;
		}

		$running = null;
		do {
			$status = curl_multi_exec($multiHandle, $running);
			if ($status > CURLM_OK) {
				break;
			}
			curl_multi_select($multiHandle, 1.0);
		} while ($running > 0);

		foreach ($handles as $key => $ch) {
			$info = curl_getinfo($ch);
			$results[$key] = [
				'content' => curl_multi_getcontent($ch),
				'info' => $info,
				'error' => curl_error($ch),
				'error_code' => curl_errno($ch),
				'http_code' => $info['http_code'] ?? 0,
			];
			curl_multi_remove_handle($multiHandle, $ch);
			if (PHP_VERSION_ID < 80500) {
				// @phpstan-ignore-next-line
				curl_close($ch);
			}
		}

		curl_multi_close($multiHandle);

		return $results;
	}

	/**
	 * Send batch GET requests concurrently
	 * 
	 * @param array<string> $urls List of URLs to fetch
	 * @param int $maxConcurrency Maximum number of simultaneous connections
	 * @param array<string, string> $headers Optional shared headers for all requests
	 * 
	 * @return array<int, array{content: string, info: array, error: string, http_code: int}> Array of results
	 */
	public static function batchGet(array $urls, int $maxConcurrency = 10, array $headers = []): array
	{
		$requests = [];
		foreach ($urls as $key => $url) {
			$requests[$key] = [
				'url' => $url,
				'method' => 'GET',
				'headers' => $headers,
			];
		}

		return self::asyncMultiRequest($requests, $maxConcurrency);
	}

	/**
	 * Send batch POST requests concurrently
	 * 
	 * @param array<int|string, array{url: string, data?: array}> $requests Array of request configs with url and optional data
	 * @param int $maxConcurrency Maximum number of simultaneous connections
	 * @param array<string, string> $headers Optional shared headers for all requests
	 * 
	 * @return array{ 'content': string, 'info': array, 'error': string, 'http_code': int }[] Array of results
	 */
	public static function batchPost(array $requests, int $maxConcurrency = 10, array $headers = []): array
	{
		$formatted = [];
		foreach ($requests as $key => $config) {
			$formatted[$key] = [
				'url' => $config['url'],
				'method' => 'POST',
				'data' => $config['data'] ?? [],
				'headers' => array_merge($headers, $config['headers'] ?? []),
			];
		}

		return self::asyncMultiRequest($formatted, $maxConcurrency);
	}

	/**
	 * Execute request with automatic retry and exponential backoff, supporting any HTTP method
	 * 
	 * @param string $url URL to send the request to
	 * @param string $method HTTP method
	 * @param array<string, mixed> $data Optional data for request body
	 * @param array<string, string> $headers Optional headers
	 * @param int $maxRetries Maximum number of retry attempts (default: 3)
	 * @param int $baseDelay Base delay in milliseconds between retries (default: 1000)
	 * @param array<int> $retryOnHttpCodes HTTP status codes that should trigger a retry (default: [429, 500, 502, 503, 504])
	 * 
	 * @return array{status: int, headers: array, body: string, info: array, attempts: int}
	 * 
	 * @throws Exception if all retry attempts fail
	 */
	public static function requestWithRetry(string $url, string $method = 'GET', array $data = [], array $headers = [], int $maxRetries = 3, int $baseDelay = 1000, array $retryOnHttpCodes = [429, 500, 502, 503, 504]): array
	{
		$lastException = null;
		$lastResponse = null;

		for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
			try {
				$response = self::request($url, $method, $data, $headers);
				$lastResponse = $response;

				if (!in_array($response['status'], $retryOnHttpCodes)) {
					$response['attempts'] = $attempt;
					return $response;
				}

				if ($response['status'] === 429 && isset($response['headers']['Retry-After'])) {
					$retryAfter = (int) $response['headers']['Retry-After'];
					usleep($retryAfter * 1000000);
					continue;
				}
			} catch (Exception $e) {
				$lastException = $e;
			}

			if ($attempt < $maxRetries) {
				$delay = $baseDelay * pow(2, $attempt - 1);
				$jitter = random_int(0, (int) ($delay * 0.1));
				usleep(($delay + $jitter) * 1000);
			}
		}

		if ($lastResponse !== null) {
			$lastResponse['attempts'] = $maxRetries;
			return $lastResponse;
		}

		throw $lastException ?? new Exception("All retry attempts failed for: {$url}");
	}

	/**
	 * Send a JSON request and decode the response automatically
	 * 
	 * @param string $url URL to send the request to
	 * @param string $method HTTP method
	 * @param array<string, mixed> $data Optional data for request body
	 * @param array<string, string> $headers Optional headers
	 * 
	 * @return array{status: int, data: mixed, headers: array, raw_body: string}
	 */
	public static function jsonRequest(string $url, string $method = 'GET', array $data = [], array $headers = []): array
	{
		$headers['Accept'] = 'application/json';
		$response = self::request($url, $method, $data, $headers);

		$decoded = null;
		if (!empty($response['body'])) {
			$decoded = JSONHandler::decode($response['body'], true, raw: true);
		}

		return [
			'status' => $response['status'],
			'data' => $decoded,
			'headers' => $response['headers'],
			'raw_body' => $response['body'],
		];
	}

	/**
	 * Send a GraphQL request
	 * 
	 * @param string $url GraphQL endpoint URL
	 * @param string $query GraphQL query string
	 * @param array<string, mixed> $variables Optional variables for the GraphQL query
	 * @param array<string, string> $headers Optional additional headers
	 * 
	 * @return mixed Decoded JSON response
	 */
	public static function graphql(string $url, string $query, array $variables = [], array $headers = []): mixed
	{
		$payload = ['query' => $query];
		if (!empty($variables)) {
			$payload['variables'] = $variables;
		}

		return self::json($url, 'POST', $payload, $headers);
	}

	/**
	 * Send XML request with XML body and parse XML response
	 * 
	 * @param string $url URL to send the request to
	 * @param string $xmlBody XML string to send as request body
	 * @param string $method HTTP method (default: POST)
	 * @param array<string, string> $headers Optional additional headers
	 * 
	 * @return array{status: int, body: string, headers: array, info: array}
	 */
	public static function xml(string $url, string $xmlBody, string $method = 'POST', array $headers = []): array
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setPostField($xmlBody, false)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setReturnHeader(true)
			->setHeader('Content-Type', 'application/xml')
			->setHeader('Accept', 'application/xml');

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$info = $curl->information()->getAll();
		$headerSize = $curl->information()->getHeaderSize();
		$responseHeaders = self::parseHeaders(substr($response, 0, $headerSize));
		$body = substr($response, $headerSize);
		$curl->close();

		return [
			'status' => $info['http_code'],
			'body' => $body,
			'headers' => $responseHeaders,
			'info' => $info,
		];
	}

	/**
	 * Send SOAP request
	 * 
	 * @param string $url SOAP endpoint URL
	 * @param string $soapBody SOAP XML envelope body
	 * @param string|null $soapAction Optional SOAPAction header value
	 * @param array<string, string> $headers Optional additional headers
	 * 
	 * @return array{status: int, body: string, headers: array, info: array}
	 */
	public static function soap(string $url, string $soapBody, ?string $soapAction = null, array $headers = []): array
	{
		if ($soapAction !== null) {
			$headers['SOAPAction'] = '"' . $soapAction . '"';
		}

		$headers['Content-Type'] = 'text/xml; charset=utf-8';

		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setPostMethod(true)
			->setPostField($soapBody, false)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setReturnHeader(true);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$info = $curl->information()->getAll();
		$headerSize = $curl->information()->getHeaderSize();
		$responseHeaders = self::parseHeaders(substr($response, 0, $headerSize));
		$body = substr($response, $headerSize);
		$curl->close();

		return [
			'status' => $info['http_code'],
			'body' => $body,
			'headers' => $responseHeaders,
			'info' => $info,
		];
	}

	/**
	 * Send a raw request body (useful for custom content types)
	 * 
	 * @param string $url URL to send the request to
	 * @param string $body Raw request body
	 * @param string $contentType Content-Type header value
	 * @param string $method HTTP method (default: POST)
	 * @param array<string, string> $headers Optional additional headers
	 * 
	 * @return array{status: int, body: string, headers: array, info: array}
	 */
	public static function rawRequest(string $url, string $body, string $contentType, string $method = 'POST', array $headers = []): array
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setPostField($body, false)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setReturnHeader(true)
			->setHeader('Content-Type', $contentType);

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$info = $curl->information()->getAll();
		$headerSize = $curl->information()->getHeaderSize();
		$responseHeaders = self::parseHeaders(substr($response, 0, $headerSize));
		$responseBody = substr($response, $headerSize);
		$curl->close();

		return [
			'status' => $info['http_code'],
			'body' => $responseBody,
			'headers' => $responseHeaders,
			'info' => $info,
		];
	}

	/**
	 * Download file with progress callback
	 * 
	 * @param string $url URL of the file to download
	 * @param string $savePath Path where the downloaded file should be saved
	 * @param callable|null $progressCallback Optional callback function receiving (downloadTotal, downloadNow, uploadTotal, uploadNow)
	 * @param int $timeout Maximum total time in seconds (default: 300)
	 * 
	 * @return bool True if download succeeded, false otherwise
	 */
	public static function downloadWithProgress(string $url, string $savePath, ?callable $progressCallback = null, int $timeout = 300): bool
	{
		$fp = fopen($savePath, 'w+');
		if (!$fp) {
			return false;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

		if ($progressCallback !== null) {
			curl_setopt($ch, CURLOPT_NOPROGRESS, false);
			curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($ch, $dlTotal, $dlNow, $ulTotal, $ulNow) use ($progressCallback) {
				$progressCallback($dlTotal, $dlNow, $ulTotal, $ulNow);
			});
		}

		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}
		fclose($fp);

		if (!$result || $httpCode >= 400) {
			unlink($savePath);
			return false;
		}

		return true;
	}

	/**
	 * Resume a partially downloaded file
	 * 
	 * @param string $url URL of the file to download
	 * @param string $savePath Path where the downloaded file should be saved (will resume if file exists)
	 * @param int $timeout Maximum total time in seconds (default: 300)
	 * 
	 * @return bool True if download succeeded, false otherwise
	 */
	public static function resumeDownload(string $url, string $savePath, int $timeout = 300): bool
	{
		$existingSize = 0;
		if (file_exists($savePath)) {
			$existingSize = filesize($savePath);
		}

		$fp = fopen($savePath, 'a');
		if (!$fp) {
			return false;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

		if ($existingSize > 0) {
			curl_setopt($ch, CURLOPT_RESUME_FROM, $existingSize);
		}

		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$errorCode = curl_errno($ch);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}
		fclose($fp);

		if (!$result && $errorCode !== 0) {
			return false;
		}

		if ($httpCode >= 400 && $httpCode !== 416) {
			return false;
		}

		return true;
	}

	/**
	 * Get the effective final URL after following all redirects
	 * 
	 * @param string $url URL to resolve
	 * @param int $timeout Timeout in seconds (default: 10)
	 * 
	 * @return string|null The final URL after all redirects, or null on failure
	 */
	public static function getEffectiveUrl(string $url, int $timeout = 10): ?string
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_exec($ch);
		$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$error = curl_errno($ch);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		return $error === 0 ? $effectiveUrl : null;
	}

	/**
	 * Get the remote file size without downloading it
	 * 
	 * @param string $url URL to check the file size for
	 * @param int $timeout Timeout in seconds (default: 10)
	 * 
	 * @return int|null File size in bytes, or null if not available
	 */
	public static function getRemoteFileSize(string $url, int $timeout = 10): ?int
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_exec($ch);
		$contentLength = curl_getinfo($ch, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
		$error = curl_errno($ch);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		if ($error !== 0 || $contentLength < 0) {
			return null;
		}

		return (int) $contentLength;
	}

	/**
	 * Get the MIME type of a remote resource
	 * 
	 * @param string $url URL to check
	 * @param int $timeout Timeout in seconds (default: 10)
	 * 
	 * @return string|null MIME type string, or null on failure
	 */
	public static function getRemoteMimeType(string $url, int $timeout = 10): ?string
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_exec($ch);
		$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		$error = curl_errno($ch);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		if ($error !== 0 || empty($contentType)) {
			return null;
		}

		$parts = explode(';', $contentType);
		return trim($parts[0]);
	}

	/**
	 * Get server response time in seconds
	 * 
	 * @param string $url URL to measure response time for
	 * @param int $timeout Timeout in seconds (default: 10)
	 * 
	 * @return float|null Response time in seconds, or null on failure
	 */
	public static function getResponseTime(string $url, int $timeout = 10): ?float
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_exec($ch);
		$totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
		$error = curl_errno($ch);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		return $error === 0 ? (float) $totalTime : null;
	}

	/**
	 * Get detailed timing breakdown for a request
	 * 
	 * @param string $url URL to analyze
	 * @param int $timeout Timeout in seconds (default: 10)
	 * 
	 * @return array{
	 * 	dns_lookup: float, 
	 * 	tcp_connect: float, 
	 * 	ssl_handshake: float, 
	 * 	ttfb: float, 
	 * 	total: float, 
	 * 	redirect: float
	 * }|null Timing breakdown in seconds, or null on failure
	 */
	public static function getTimingBreakdown(string $url, int $timeout = 10): ?array
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		curl_exec($ch);
		$error = curl_errno($ch);

		if ($error !== 0) {
			if (PHP_VERSION_ID < 80500) {
				// @phpstan-ignore-next-line
				curl_close($ch);
			}
			return null;
		}

		$info = curl_getinfo($ch);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		return [
			'dns_lookup' => (float) $info['namelookup_time'],
			'tcp_connect' => (float) ($info['connect_time'] - $info['namelookup_time']),
			'ssl_handshake' => (float) ($info['appconnect_time'] > 0 ? $info['appconnect_time'] - $info['connect_time'] : 0),
			'ttfb' => (float) ($info['starttransfer_time'] - $info['appconnect_time']),
			'total' => (float) $info['total_time'],
			'redirect' => (float) $info['redirect_time'],
		];
	}

	/**
	 * Ping a URL and return status information
	 * 
	 * @param string $url URL to ping
	 * @param int $timeout Timeout in seconds (default: 5)
	 * 
	 * @return array{
	 * 	reachable: bool, 
	 * 	http_code: int, 
	 * 	response_time: float, 
	 * 	ssl_valid: bool, 
	 * 	redirect_count: int, 
	 * 	effective_url: string
	 * }
	 */
	public static function ping(string $url, int $timeout = 5): array
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

		curl_exec($ch);
		$info = curl_getinfo($ch);
		$errorCode = curl_errno($ch);
		$sslValid = ($errorCode !== 60 && $errorCode !== 51 && $errorCode !== 58);

		if ($errorCode !== 0 && $sslValid === false) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_exec($ch);
			$info = curl_getinfo($ch);
			$errorCode = curl_errno($ch);
		}

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		return [
			'reachable' => $errorCode === 0 && $info['http_code'] > 0,
			'http_code' => (int) $info['http_code'],
			'response_time' => (float) $info['total_time'],
			'ssl_valid' => $sslValid,
			'redirect_count' => (int) $info['redirect_count'],
			'effective_url' => $info['effective_url'] ?? $url,
		];
	}

	/**
	 * Get SSL certificate information for a URL
	 * 
	 * @param string $url URL to retrieve certificate information from
	 * @param int $timeout Timeout in seconds (default: 10)
	 * 
	 * @return array|null SSL certificate information, or null on failure
	 */
	public static function getSSLCertificateInfo(string $url, int $timeout = 10): ?array
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CERTINFO, true);

		curl_exec($ch);
		$certInfo = curl_getinfo($ch, CURLINFO_CERTINFO);
		$error = curl_errno($ch);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}

		if ($error !== 0 || empty($certInfo)) {
			return null;
		}

		return is_array($certInfo) ? $certInfo : null;
	}

	/**
	 * Send request with OAuth 2.0 client credentials grant and use the obtained token
	 * 
	 * @param string $tokenUrl OAuth token endpoint URL
	 * @param string $clientId OAuth client ID
	 * @param string $clientSecret OAuth client secret
	 * @param string $requestUrl URL to send the authenticated request to
	 * @param string $method HTTP method for the authenticated request
	 * @param array<string, mixed> $data Optional data for the authenticated request body
	 * @param string $scope Optional scope parameter for the token request
	 * 
	 * @return mixed Response from the authenticated request
	 * 
	 * @throws Exception if token retrieval fails
	 */
	public static function withOAuth2ClientCredentials(string $tokenUrl, string $clientId, string $clientSecret, string $requestUrl, string $method = 'GET', array $data = [], string $scope = ''): mixed
	{
		$tokenData = [
			'grant_type' => 'client_credentials',
			'client_id' => $clientId,
			'client_secret' => $clientSecret,
		];

		if (!empty($scope)) {
			$tokenData['scope'] = $scope;
		}

		$tokenResponse = self::postForm($tokenUrl, $tokenData);

		if (is_string($tokenResponse)) {
			$tokenResponse = JSONHandler::decode($tokenResponse, true, raw: true);
		}

		if (!isset($tokenResponse['access_token'])) {
			throw new Exception('Failed to obtain OAuth2 access token');
		}

		return self::withBearerToken($requestUrl, $tokenResponse['access_token'], $method, $data);
	}

	/**
	 * Send request with API key authentication
	 * 
	 * @param string $url URL to send the request to
	 * @param string $apiKey API key value
	 * @param string $headerName Header name for the API key (default: 'X-API-Key')
	 * @param string $method HTTP method
	 * @param array<string, mixed> $data Optional data for the request body
	 * 
	 * @return mixed Response from the request
	 */
	public static function withApiKey(string $url, string $apiKey, string $headerName = 'X-API-Key', string $method = 'GET', array $data = []): mixed
	{
		return self::json($url, $method, $data, [
			$headerName => $apiKey,
		]);
	}

	/**
	 * Send request with digest authentication
	 * 
	 * @param string $url URL to send the request to
	 * @param string $username Username for digest authentication
	 * @param string $password Password for digest authentication
	 * @param string $method HTTP method
	 * @param array<string, mixed> $data Optional data for the request body
	 * 
	 * @return mixed Response from the request
	 */
	public static function withDigestAuth(string $url, string $username, string $password, string $method = 'GET', array $data = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setCustomRequest(strtoupper($method))
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setBasicAuthentication($username, $password);

		if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
			$curl->option
				->setPostField(JSONHandler::encode($data))
				->setContentTypeApplicationJson();
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Stream response to a file
	 * 
	 * @param string $url URL to stream from
	 * @param string $filePath Path to save the streamed content
	 * @param array<string, string> $headers Optional headers
	 * 
	 * @return bool True if streaming was successful
	 */
	public static function streamToFile(string $url, string $filePath, array $headers = []): bool
	{
		$fp = fopen($filePath, 'w+');
		if (!$fp) {
			return false;
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		if (!empty($headers)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, self::formatHeaders($headers));
		}

		$result = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}
		fclose($fp);

		if (!$result || $httpCode >= 400) {
			unlink($filePath);
			return false;
		}

		return true;
	}

	/**
	 * Stream Server-Sent Events (SSE) and invoke callback for each event
	 * 
	 * @param string $url SSE endpoint URL
	 * @param callable $callback Callback invoked with each event data string
	 * @param array<string, string> $headers Optional headers
	 * @param int $timeout Timeout in seconds (default: 0, no timeout)
	 * 
	 * @return void
	 */
	public static function streamSSE(string $url, callable $callback, array $headers = [], int $timeout = 0): void
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		if ($timeout > 0) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		}

		$merged = array_merge(['Accept' => 'text/event-stream', 'Cache-Control' => 'no-cache'], $headers);
		curl_setopt($ch, CURLOPT_HTTPHEADER, self::formatHeaders($merged));

		$buffer = '';
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($callback, &$buffer) {
			$buffer .= $data;

			while (($pos = strpos($buffer, "\n\n")) !== false) {
				$event = substr($buffer, 0, $pos);
				$buffer = substr($buffer, $pos + 2);

				$eventData = '';
				foreach (explode("\n", $event) as $line) {
					if (str_starts_with($line, 'data: ')) {
						$eventData .= substr($line, 6) . "\n";
					} elseif ($line === 'data:') {
						$eventData .= "\n";
					}
				}

				if (!empty(trim($eventData))) {
					$callback(trim($eventData));
				}
			}

			return strlen($data);
		});

		curl_exec($ch);
		if (PHP_VERSION_ID < 80500) {
			// @phpstan-ignore-next-line
			curl_close($ch);
		}
	}

	/**
	 * Send chunked transfer encoded request
	 * 
	 * @param string $url URL to send the request to
	 * @param string $body Request body
	 * @param string $contentType Content-Type header value
	 * @param array<string, string> $headers Optional additional headers
	 * 
	 * @return mixed Response from the request
	 */
	public static function chunkedPost(string $url, string $body, string $contentType = 'application/json', array $headers = []): mixed
	{
		$curl = new self($url);
		$curl->option
			->setURL($url)
			->setPostMethod(true)
			->setPostField($body, false)
			->setSSLVerifyPeer(false)
			->setReturnTransfer(true)
			->setHeader('Content-Type', $contentType)
			->setHeader('Transfer-Encoding', 'chunked');

		foreach ($headers as $key => $value) {
			$curl->option->setHeader($key, $value);
		}

		$response = $curl->execute();
		$curl->close();

		return $response;
	}

	/**
	 * Execute the current cURL session and return a detailed response object
	 * 
	 * @return array{success: bool, status: int, body: mixed, headers: array, error: ?string, error_code: int, timing: array}
	 */
	public function executeDetailed(): array
	{
		$this->option->setReturnHeader(true);

		$response = curl_exec($this->handle);
		$errorCode = curl_errno($this->handle);
		$errorMessage = curl_error($this->handle);
		$info = curl_getinfo($this->handle);

		$headerSize = $info['header_size'] ?? 0;
		$headers = [];
		$body = '';

		if ($response !== false) {
			$headers = self::parseHeaders(substr($response, 0, $headerSize));
			$body = substr($response, $headerSize);
		}

		return [
			'success' => $errorCode === 0 && $info['http_code'] < 400,
			'status' => (int) ($info['http_code'] ?? 0),
			'body' => (new DataObject($body))->toObject(),
			'headers' => $headers,
			'error' => $errorCode > 0 ? $errorMessage : null,
			'error_code' => $errorCode,
			'timing' => [
				'total' => (float) ($info['total_time'] ?? 0),
				'dns' => (float) ($info['namelookup_time'] ?? 0),
				'connect' => (float) ($info['connect_time'] ?? 0),
				'ttfb' => (float) ($info['starttransfer_time'] ?? 0),
			],
		];
	}

	/**
	 * Check if the last executed request was successful
	 * 
	 * @return bool True if no cURL error and HTTP code is 2xx
	 */
	public function isLastRequestSuccessful(): bool
	{
		$errorCode = curl_errno($this->handle);
		$httpCode = curl_getinfo($this->handle, CURLINFO_HTTP_CODE);

		return $errorCode === 0 && $httpCode >= 200 && $httpCode < 300;
	}

	/**
	 * Get the last HTTP response code
	 * 
	 * @return int HTTP response code
	 */
	public function getLastHttpCode(): int
	{
		return (int) curl_getinfo($this->handle, CURLINFO_HTTP_CODE);
	}

	/**
	 * Get the handle resource
	 * 
	 * @return resource|CurlHandle The underlying cURL handle
	 */
	public function getHandle(): mixed
	{
		return $this->handle;
	}

	/**
	 * Create a new ClientURLErrorResponse instance
	 * 
	 * @return ClientURLErrorResponse
	 */
	public function errorResponse(): ClientURLErrorResponse
	{
		return new ClientURLErrorResponse();
	}

	/**
	 * Execute and diagnose the request, returning structured error/success information
	 * 
	 * @return array{success: bool, status: int, body: mixed, curl_error: ?array, http_error: ?array, summary: string}
	 */
	public function executeAndDiagnose(): array
	{
		$this->option->setReturnHeader(true);
		$response = curl_exec($this->handle);
		$curlErrorCode = curl_errno($this->handle);
		$info = curl_getinfo($this->handle);
		$headerSize = $info['header_size'] ?? 0;

		$body = '';
		if ($response !== false) {
			$body = substr($response, $headerSize);
		}

		$errResponse = new ClientURLErrorResponse();
		$diagnostic = $errResponse->getDiagnosticReport($curlErrorCode, (int) ($info['http_code'] ?? 0));

		return [
			'success' => $diagnostic['overall_success'],
			'status' => (int) ($info['http_code'] ?? 0),
			'body' => (new DataObject($body))->toObject(),
			'curl_error' => $diagnostic['curl_error'],
			'http_error' => $diagnostic['has_http_error'] ? $diagnostic['http_status'] : null,
			'summary' => $diagnostic['summary'],
		];
	}

	/**
	 * Format associative headers array into "Key: Value" strings
	 * 
	 * @param array<string, string> $headers Associative array of headers
	 * 
	 * @return array<string> Formatted header strings
	 */
	private static function formatHeaders(array $headers): array
	{
		$formatted = [];
		foreach ($headers as $key => $value) {
			$formatted[] = "{$key}: {$value}";
		}
		return $formatted;
	}

	/**
	 * Clone method for deep copy
	 */
	public function __clone(): void
	{
		$this->handle = $this->copyHandle();

		if ($this->option) {
			$this->option = new ClientURLOption($this->handle);
		}

		if ($this->information) {
			$this->information = new ClientURLLastTransferInformation($this->handle);
		}
	}

	/**
	 * Wakeup method for serialization
	 */
	public function __wakeup(): void
	{
		$this->handle = $this->getSession();

		if ($this->option) {
			$this->option = new ClientURLOption($this->handle);
		}

		if ($this->information) {
			$this->information = new ClientURLLastTransferInformation($this->handle);
		}
	}

	/**
	 * Sleep method for serialization
	 * 
	 * @return array<string> List of properties to serialize, which includes 'option' and 'information' while excluding 'handle' since it cannot be serialized directly
	 */
	public function __sleep(): array
	{
		return ['option', 'information'];
	}

	/**
	 * String representation of the object
	 * 
	 * @return string String representation of the ClientURL object, which can be customized to include relevant information about the cURL session if desired
	 */
	public function __toString(): string
	{
		return 'ClientURL Object';
	}

	#endregion
}
