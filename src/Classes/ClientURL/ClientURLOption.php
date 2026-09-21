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

use Clover\Classes\Data\{StringObject, URLObject};
use Clover\Classes\Format\MultiPurposeInternetMailExtensions as MIME;
use Clover\Enumeration\HTTPRequestMethod;
use Clover\Implement\ClientURLOptionInterface;
use const CURL_HTTP_VERSION_1_0;
use const CURL_HTTP_VERSION_1_1;
use const CURL_HTTP_VERSION_2_0;
use const CURL_HTTP_VERSION_NONE;
use const CURLAUTH_DIGEST;
use const CURLOPT_COOKIEFILE;
use const CURLOPT_COOKIEJAR;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_DEBUGFUNCTION;
use const CURLOPT_FORBID_REUSE;
use const CURLOPT_FRESH_CONNECT;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPAUTH;
use const CURLOPT_MAX_SEND_SPEED_LARGE;
use const CURLOPT_POSTFIELDSIZE;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_UPLOAD;
use const CURLOPT_URL;
use CURLFile;
use CurlHandle;
use Exception;
use function count;
use function defined;
use function is_array;
use function sprintf;
use function strlen;

#endregion

/**
 * Class ClientURLOption
 * 
 * Provides methods for configuring cURL options for making HTTP requests, allowing for flexible and convenient configuration of various options such as HTTP method, URL, SSL settings, timeouts, and more when using cURL in PHP to perform HTTP communication.
 *
 * @package Clover\Classes
 */
class ClientURLOption implements ClientURLOptionInterface
{
	#region properties

	/** 
	 * @var array<string> 
	 * List of HTTP headers to be included in the request, which can be used to specify additional information or parameters for the HTTP request when making requests using cURL in PHP, allowing for flexible configuration of the headers that are sent with the request to meet specific requirements or provide necessary information to the server. This array can include custom headers as well as standard HTTP headers that are commonly used in HTTP communication.
	 **/
	public static array $headers = [];

	/** 
	 * @var CurlHandle|resource
	 * cURL session resource that is used to manage the cURL session for making HTTP requests, allowing for configuration of various options and settings for the session when using cURL in PHP to perform HTTP communication. This resource can be used to set options, execute requests, and manage the overall behavior of the cURL session for making HTTP requests.
	 **/
	public static mixed $handle;

	/** 
	 * @var array<mixed> 
	 * List of all currently set options, which can be used to reference and understand the various options that have been configured for the cURL session, allowing for easy retrieval and analysis of the options that are currently in effect for making HTTP requests. This array can include custom options as well as standard cURL options that have been set for the session.
	 **/
	public static array $options = [];

	/** 
	 * @var array<mixed>
	 * List of all currently set cURL options, which can be used to reference and understand the various options that have been configured for the cURL session, allowing for easy retrieval and analysis of the options that are currently in effect for making HTTP requests.
	 */
	public static array $curlOptions = [];

	#endregion

	#region function

	/**
	 * Constructor
	 *
	 * @param CurlHandle|resource $session cURL session resource to initialize the ClientURLOption with, which can be used to set options and manage the cURL session for making HTTP requests.
	 */
	public function __construct(mixed $session)
	{
		self::$handle = $session;
		self::$options = [];
		self::$curlOptions = [];
		self::$headers = [];
	}

	/**
	 * Throw an exception if the constant is not defined
	 *
	 * @param string $constant Name of the constant to check
	 *
	 * @return void
	 *
	 * @throws \Exception If the constant is not defined, which can help ensure that the necessary constants are defined and available for use in the class, preventing
	 */
	private function throwUseUndefinedConstant(string $constant): void
	{
		if (!defined($constant)) {
			throw new Exception(sprintf("Use of undefined constant '%s'", $constant));
		}
	}

	/**
	 * Clear all set options
	 *
	 * @return ClientURLOption The current instance of ClientURLOption, which can be used for method chaining and fluent interface design when configuring options for the cURL session in a convenient and readable manner.
	 */
	public function clearOptions(): static
	{
		self::$options = [];
		return $this->returnContext();
	}

	/**
	 * Clear all set cURL options
	 *
	 * @return ClientURLOption The current instance of ClientURLOption, which can be used for method chaining and fluent interface design when configuring options for the cURL session in a convenient and readable manner.
	 */
	public function clearCurlOptions(): static
	{
		self::$curlOptions = [];
		return $this->returnContext();
	}

	/**
	 * Return all currently set cURL options.
	 *
	 * @return array<int|string, mixed> List of all currently set cURL options, which can be used to reference and understand the various options that have been configured for the cURL session, allowing for easy retrieval and analysis of the options that are currently in effect for making HTTP requests.
	 */
	public function getOptions(): array
	{
		return self::$curlOptions;
	}

	/**
	 * Return the current context
	 *
	 * @return ClientURLOption The current instance of ClientURLOption, which can be used for method chaining and fluent interface design when configuring options for the cURL session in a convenient and readable manner.
	 */
	public function returnContext(): static
	{
		return $this;
	}

	/**
	 * Set a single cURL option on the underlying handle and record it.
	 *
	 * @param mixed $key cURL option key, which can be a predefined constant or a custom key that represents a specific cURL option to be set for the cURL session, allowing for flexible configuration of the options that are applied to the session when making HTTP requests.
	 * @param mixed $value Value to set for the specified cURL option key, which can be of various types depending on the option being set, such as a string, boolean, integer, or array, and can be used to configure the behavior of the cURL session according to the desired settings for making HTTP requests.
	 *
	 * @return bool True on success.
	 */
	public function setOption(mixed $key, mixed $value): bool
	{
		if ($value instanceof StringObject) {
			$value = $value->__toString();
		}

		// Resolve human-readable constant name for logging / debugging.
		$constants = get_defined_constants(true);
		$constants = array_filter($constants['curl'] ?? [], function ($id) use ($key) {
			return $id == $key;
		});

		$resolvedKey = array_keys($constants)[0] ?? $key;
		$this->curlOptions[$resolvedKey] = $value;

		return curl_setopt(self::$handle, $key, $value);
	}

	/**
	 * Convert set cURL options to an array
	 *
	 * @return array<int, mixed> List of all currently set cURL options in an array format, which can be used to reference and understand the various options that have been configured for the cURL session, allowing for easy retrieval and analysis of the options that are currently in effect for making HTTP requests.
	 */
	public function toArray(): array
	{
		$options = self::$curlOptions;

		if (!empty(self::$headers)) {
			$formattedHeaders = [];
			foreach (self::$headers as $key => $values) {
				foreach ((array) $values as $value) {
					$formattedHeaders[] = "$key: $value";
				}
			}

			$options[CURLOPT_HTTPHEADER] = array_merge(
				$options[CURLOPT_HTTPHEADER] ?? [],
				$formattedHeaders
			);
		}

		return $options;
	}

	/**
	 * Set the HTTP method for the request
	 *
	 * @param string $method HTTP method to set for the request, which can be a standard HTTP method such as GET, POST, PUT, DELETE, etc., or a custom method that represents a specific action to be performed when making the HTTP request, allowing for flexible configuration of the HTTP method that is used for the request.
	 *
	 * @return ClientURLOption The current instance of ClientURLOption, which can be used for method chaining and fluent interface design when configuring options for the cURL session in a convenient and readable manner.
	 */
	public function setMethod(string $method)
	{
		$method = strtoupper($method);

		$result = match ($method) {
			'GET' => $this->setOption(CURLOPT_HTTPGET, true),
			'POST' => $this->setOption(CURLOPT_POST, true),
			'HEAD' => $this->setOption(CURLOPT_NOBODY, true),
			default => $this->setOption(CURLOPT_CUSTOMREQUEST, $method),
		};

		if (!$result) {
			throw new Exception("Failed to set HTTP method: $method");
		}

		return $this->returnContext();
	}

	/**
	 * Pass a long. Set to 1 to make the next transfer use a new (fresh) connection by force instead of trying to reuse an existing one. This option should be used with caution and only if you understand what it does as it may impact performance negatively.
	 * 
	 * {@see \CURLOPT_FRESH_CONNECT}
	 *
	 * @param bool $bool Whether to enable fresh connection for the request when CURLOPT_FRESH_CONNECT is enabled, which allows libcurl to create a new connection for the request instead of reusing an existing one when making HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable fresh connections, providing a convenient way to control the connection behavior for the request and ensure that a new connection is established if desired.
	 * 
	 * @return ClientURLOption
	 */
	public function setDisableCache(bool $bool): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_FRESH_CONNECT');

		$this->setOption(CURLOPT_FRESH_CONNECT, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass in a pointer to the URL to work with. The parameter should be a char * to a null-terminated string which must be URL-encoded in the following format:
	 * 
	 * {@see \CURLOPT_URL}
	 *
	 * @param string|StringObject $url URL to set for the request, which can be a string or an instance of StringObject representing the URL to which the HTTP request will be sent, allowing for flexible configuration of the target URL for the request when making HTTP requests using cURL.
	 * 
	 * @return ClientURLOption
	 */
	public function setURL(string|StringObject $url): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_URL');

		$this->setOption(CURLOPT_URL, $url);

		return $this->returnContext();
	}

	/**
	 * Pass a long. Set close to 1 to make libcurl explicitly close the connection when done with the transfer. Normally, libcurl keeps all connections alive when done with one transfer in case a succeeding one follows that can reuse them. This option should be used with caution and only if you understand what it does as it can seriously impact performance.
	 * 
	 * {@see \CURLOPT_FORBID_REUSE}
	 *
	 * @param bool $bool Whether to forbid reuse of the connection for the request when CURLOPT_FORBID_REUSE is enabled, which allows libcurl to close the connection after the request is completed when making HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable connection reuse, providing a convenient way to control the connection behavior for the request and ensure that a new connection is established for each request if desired.
	 * 
	 * @return ClientURLOption
	 */
	public function setForbidenReuse(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_FORBID_REUSE');

		$this->setOption(CURLOPT_FORBID_REUSE, $bool);

		return $this->returnContext();
	}

	/**
	 * The long parameter upload set to 1 tells the library to prepare for and perform an upload. The CURLOPT_READDATA and CURLOPT_INFILESIZE or CURLOPT_INFILESIZE_LARGE options are also interesting for uploads. If the protocol is HTTP, uploading means using the PUT request unless you tell libcurl otherwise.
	 * 
	 * {@see \CURLOPT_UPLOAD}
	 *
	 * @param bool $bool Whether to enable file upload for the request when CURLOPT_UPLOAD is enabled, which allows libcurl to handle file uploads when making HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable file upload functionality for the request, providing a convenient way to configure the request for uploading files as part of the HTTP communication.
	 * 
	 * @return ClientURLOption
	 */
	public function setUploadReady(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_UPLOAD');

		$this->setOption(CURLOPT_UPLOAD, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a long as parameter to enable or disable.
	 * This option determines whether curl verifies the authenticity of the peer's certificate. A value of 1 means curl verifies; 0 (zero) means it does not.
	 * 
	 * {@see \CURLOPT_SSL_VERIFYPEER}
	 *
	 * @param bool $bool Whether to verify the peer's SSL certificate when CURLOPT_SSL_VERIFYPEER is enabled, which allows libcurl to verify the authenticity of the peer's SSL certificate when making secure HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable peer verification, providing an additional layer of security for the request by ensuring that the peer's SSL certificate is valid and trusted.
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVerifyPeer(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_SSL_VERIFYPEER');

		$this->setOption(CURLOPT_SSL_VERIFYPEER, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a null-terminated string as parameter. The string should be the filename of your private key. The default format is "PEM" and can be changed with CURLOPT_SSLKEYTYPE.
	 * 
	 * {@see \CURLOPT_SSLKEY}
	 *
	 * @param string $key Path to the SSL key file to set for the request, which can be a string representing the file path to the SSL key that will be used for secure communication when making HTTP requests using cURL, allowing for flexible configuration of the SSL key for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLKey(string $key): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_SSLKEY');

		$this->setOption(CURLOPT_SSLKEY, $key);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a null-terminated string as parameter. The string should be the filename of your client certificate. The default format is PEM but can be changed with CURLOPT_SSLCERTTYPE.
	 * 
	 * {@see \CURLOPT_SSLCERT}
	 *
	 * @param string $certificate Path to the SSL certificate file to set for the request, which can be a string representing the file path to the SSL certificate that will be used for secure communication when making HTTP requests using cURL, allowing for flexible configuration of the SSL certificate for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLcertificate($certificate): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_SSLCERT');

		$this->setOption(CURLOPT_SSLCERT, $certificate);

		return $this->returnContext();
	}

	/**
	 * Pass a char pointer, pointing to a null-terminated string holding the list of cipher suites to use for the TLS 1.2 (1.1, 1.0) connection. The list must be syntactically correct, it consists of one or more cipher suite strings separated by colons.
	 * 
	 * {@see \CURLOPT_SSL_CIPHER_LIST}
	 *
	 * @param string $cipherList List of SSL ciphers to set for the request, which can be a string representing the list of SSL ciphers that will be used for secure communication when making HTTP requests using cURL, allowing for flexible configuration of the SSL ciphers for the request to ensure proper security and compatibility with the server's SSL configuration.
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLCipherList($cipherList): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_SSL_CIPHER_LIST');

		$this->setOption(CURLOPT_SSL_CIPHER_LIST, $cipherList);

		return $this->returnContext();
	}

	/**
	 * Pass a char pointer to a null-terminated string naming a directory holding multiple CA certificates to verify the peer with. If libcurl is built against OpenSSL, the certificate directory must be prepared using the OpenSSL c_rehash utility. This makes sense only when used in combination with the CURLOPT_SSL_VERIFYPEER option.
	 * 
	 * {@see \CURLOPT_CAPATH}
	 *
	 * @param string $path Path to the directory containing CA certificates to set for the request, which can be a string representing the directory path that contains the CA certificates that will be used for verifying the authenticity of the host's SSL certificate when making secure HTTP requests using cURL, allowing for flexible configuration of the CA certificate directory for the request to ensure proper SSL verification and security.
	 * 
	 * @return ClientURLOption
	 */
	public function setCAPath($path): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_CAPATH');

		$this->setOption(CURLOPT_CAPATH, $path);

		return $this->returnContext();
	}

	/**
	 * Pass a char pointer to a null-terminated string naming a file holding one or more certificates to verify the peer with.
	 * 
	 * {@see \CURLOPT_CAINFO}
	 *
	 * @param string $verifyHost Path to the CA certificate file to set for the request, which can be a string representing the file path to the CA certificate that will be used for verifying the authenticity of the host's SSL certificate when making secure HTTP requests using cURL, allowing for flexible configuration of the CA certificate for the request to ensure proper SSL verification and security.
	 * 
	 * @return ClientURLOption
	 */
	public function setCAInformation($verifyHost): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_CAINFO');

		$this->setOption(CURLOPT_CAINFO, $verifyHost);

		return $this->returnContext();
	}

	/**
	 * Pass a long set to 2L to make libcurl verify the host in the server's TLS certificate.
	 * 
	 * {@see \CURLOPT_SSL_VERIFYHOST}
	 *
	 * @param bool $verifyHost Whether to verify the host's SSL certificate when CURLOPT_SSL_VERIFYHOST is enabled, which allows libcurl to verify the authenticity of the host's SSL certificate when making secure HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable host verification, providing an additional layer of security for the request by ensuring that the host's SSL certificate is valid and trusted.
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVerifyHost(bool $verifyHost): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_SSL_VERIFYHOST');

		$this->setOption(CURLOPT_SSL_VERIFYHOST, $verifyHost);

		return $this->returnContext();
	}

	/**
	 * Pass a long as parameter containing timeout - the maximum time in seconds that you allow the entire transfer operation to take. The whole thing, from start to end. Normally, name lookups can take a considerable time and limiting operations risk aborting perfectly normal operations.
	 * 
	 * {@see \CURLOPT_TIMEOUT}
	 *
	 * @param int|bool $timeout Whether to set a timeout for the request when CURLOPT_TIMEOUT is enabled, which allows libcurl to limit the maximum time allowed for the entire request to complete when making HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable the timeout, providing a convenient way to control the maximum duration of the request and prevent it from hanging indefinitely in case of network issues or unresponsive servers.
	 * 
	 * @return ClientURLOption
	 */
	public function setTimeout(int|bool $timeout = true): static
	{
		$this->throwUseUndefinedConstant('CURLOPT_TIMEOUT');

		$this->setOption(CURLOPT_TIMEOUT, $timeout);

		return $this->returnContext();
	}

	/**
	 * Pass a long. If the value is set to 1 (one), libcurl converts Unix newlines to CRLF newlines on transfers. Disable this option again by setting the value to 0 (zero).
	 * 
	 * {@see \CURLOPT_CRLF}
	 *
	 * @param bool $enable Whether to convert Unix newlines to CRLF newlines in the request body when CURLOPT_CRLF is enabled, which allows libcurl to automatically convert Unix-style newlines (LF) to CRLF-style newlines (CRLF) in the request body when making HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable this newline conversion, providing a convenient way to ensure proper formatting of the request body for certain servers that require CRLF newlines.
	 * 
	 * @return ClientURLOption
	 */
	public function setCRLF(bool $enable = true): static
	{
		$this->throwUseUndefinedConstant('CURLOPT_CRLF');
		$this->setOption(CURLOPT_CRLF, $enable);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_POSTFIELDS}
	 * 
	 * Set POST fields from an array (raw multipart or URL-encoded).
	 *
	 * @param array<string, mixed> $fields POST fields to set for the request, which can be an array representing the POST fields that will be sent in the HTTP request when making requests using cURL, allowing for flexible configuration of the POST fields for the request when making HTTP requests using cURL. The array should be an associative array where the keys represent the field names and the values represent the corresponding field values that will be included in the POST request body when making HTTP requests using cURL.
	 *
	 * @return ClientURLOption
	 */
	public function setPostFields(array $fields): static
	{
		$this->throwUseUndefinedConstant('CURLOPT_POSTFIELDS');

		self::$options['post_fields'] = $fields;
		$this->setOption(CURLOPT_POSTFIELDS, $fields);

		return $this->returnContext();
	}

	/**
	 * Pass a char pointer as parameter, pointing to the data buffer to use in an HTTP POST operation or an MQTT subscribe. The data must be formatted and encoded the way you want the server to receive it. libcurl does not convert or encode it in any way. For example, a web server may assume that this data is URL encoded.
	 * 
	 * {@see \CURLOPT_POSTFIELDS}
	 *
	 * @param string|array $fields POST fields to set for the request, which can be a string or an array representing the POST fields that will be sent in the HTTP request when making requests using cURL, allowing for flexible configuration of the POST fields for the request. If an array is provided and the $query parameter is set to true, the array will be converted into a URL-encoded query string format before being set as the POST fields.
	 * @param bool $query Whether to convert the array of POST fields into a URL-encoded query string format when setting the POST fields for the request, which allows for flexible handling of the POST fields based on the desired format for the request when making HTTP requests using cURL. If set to true and an array is provided as the $fields parameter, the array will be converted into a URL-encoded query string format before being set as the POST fields for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setPostField(string|array $fields, bool $query = true): static
	{
		if (is_array($fields) && $query) {
			$parameters = [];

			foreach ($fields as $name => $value) {
				$encodedValue = urlencode((string) $value);
				$parameters[] = "{$name}={$encodedValue}";
			}

			$fields = implode('&', $parameters);
		}

		self::$options['post_fields'] = $fields;
		$this->setOption(CURLOPT_POSTFIELDS, $fields);

		return $this->returnContext();
	}

	/**
	 * Attach a file to the POST fields via CURLFile.
	 * 
	 * {@see \CURLOPT_POSTFIELDS}
	 *
	 * @param string      $key             Form field name
	 * @param string      $filename        Path on disk
	 * @param string|null $mime_type       MIME type override
	 * @param string|null $posted_filename Filename visible to the server
	 *
	 * @see CURLOPT_POSTFIELDS
	 */
	public function setPostFileField(string $key, string $filename, ?string $mime_type = null, ?string $posted_filename = null): static
	{
		if (!isset(self::$options['post_fields'])) {
			self::$options['post_fields'] = [];
		}

		self::$options['post_fields'][$key] = new CURLFile($filename, $mime_type, $posted_filename);
		$this->setOption(CURLOPT_POSTFIELDS, self::$options['post_fields']);

		return $this->returnContext();
	}

	/**
	 * Sets form fields for a future POST body (placeholder; implementation pending).
	 *
	 * @param array<string, mixed> $fields Key/value pairs intended for the request body.
	 *
	 * @return static
	 */
	public function setFields(array $fields): static
	{
		// TODO
		return $this;
	}

	/**
	 * Pass a long as parameter to control which version range of SSL/TLS versions to use.
	 * The SSL and TLS versions have typically developed from the most insecure version to be more and more secure in this order through history: SSL v2, SSLv3, TLS v1.0, TLS v1.1, TLS v1.2 and the most recent TLS v1.3.
	 * 
	 * {@see \CURLOPT_SSLVERSION}
	 *
	 * @param int $version SSL/TLS version to set for the request, which can be an integer representing the specific SSL/TLS version to be used for secure communication when making HTTP requests using cURL, allowing for flexible configuration of the SSL/TLS version for the request based on the desired security requirements and compatibility with the target server. The version can be specified using predefined constants such as CURL_SSLVERSION_TLSv1_0, CURL_SSLVERSION_TLSv1_1, CURL_SSLVERSION_TLSv1_2, etc., or a custom integer value that represents a specific SSL/TLS version.
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersion(int $version = CURL_SSLVERSION_TLSv1_0): ClientURLOption
	{
		$this->setOption(CURLOPT_SSLVERSION, $version);

		return $this->returnContext();
	}

	/**
	 * {@see \CURL_SSLVERSION_DEFAULT}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionDefault(): ClientURLOption
	{
		return $this->setSSLVersion(CURL_SSLVERSION_DEFAULT);
	}

	/**
	 * {@see \CURL_SSLVERSION_SSLv2}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionV2(): ClientURLOption
	{
		return $this->setSSLVersion(CURL_SSLVERSION_SSLv2);
	}

	/**
	 * {@see \CURL_SSLVERSION_SSLv3}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionV3(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_SSLv3');

		return $this->setSSLVersion(CURL_SSLVERSION_SSLv3);
	}

	/**
	 * {@see \CURL_SSLVERSION_TLSv1}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionTLS1(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_TLSv1');

		return $this->setSSLVersion(CURL_SSLVERSION_TLSv1);
	}

	/**
	 * {@see \CURL_SSLVERSION_TLSv1_0}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionTLS1_0(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_TLSv1_0');

		return $this->setSSLVersion(CURL_SSLVERSION_TLSv1_0);
	}

	/**
	 * {@see \CURL_SSLVERSION_TLSv1_1}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionTLS1_1(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_TLSv1_1');

		return $this->setSSLVersion(CURL_SSLVERSION_TLSv1_1);
	}

	/**
	 * {@see \CURL_SSLVERSION_TLSv1_2}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionTLS1_2(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_TLSv1_2');

		return $this->setSSLVersion(CURL_SSLVERSION_TLSv1_2);
	}

	/**
	 * {@see \CURL_SSLVERSION_TLSv1_3}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionTLS1_3(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_TLSv1_3');

		return $this->setSSLVersion(CURL_SSLVERSION_TLSv1_3);
	}

	/**
	 * {@see \CURL_SSLVERSION_MAX_DEFAULT}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionMaxDefault(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_MAX_DEFAULT');

		return $this->setSSLVersion(CURL_SSLVERSION_MAX_DEFAULT);
	}

	/**
	 * {@see \CURL_SSLVERSION_MAX_TLSv1_0}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionMaxTLS1_0(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_MAX_TLSv1_0');

		return $this->setSSLVersion(CURL_SSLVERSION_MAX_TLSv1_0);
	}

	/**
	 * {@see \CURL_SSLVERSION_MAX_TLSv1_1}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionMaxTLS1_1(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_MAX_TLSv1_1');

		return $this->setSSLVersion(CURL_SSLVERSION_MAX_TLSv1_1);
	}

	/**
	 * {@see \CURL_SSLVERSION_MAX_TLSv1_2}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionMaxTLS1_2(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_MAX_TLSv1_2');

		return $this->setSSLVersion(CURL_SSLVERSION_MAX_TLSv1_2);
	}

	/**
	 * {@see \CURL_SSLVERSION_MAX_TLSv1_3}
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLVersionMaxTLS1_3(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_SSLVERSION_MAX_TLSv1_3');

		return $this->setSSLVersion(CURL_SSLVERSION_MAX_TLSv1_3);
	}

	/**
	 * If you want to post static data to the server without having libcurl do a strlen() to measure the data size, this option must be used. When this option is used you can post fully binary data, which otherwise is likely to fail. If this size is set to -1, libcurl uses strlen() to get the size or relies on the CURLOPT_READFUNCTION (if used) to signal the end of data.
	 * 
	 * {@see \CURLOPT_POSTFIELDSIZE}
	 *
	 * @param int $size Size of the POST fields to set for the request, which can be an integer representing the size of the POST fields that will be sent in the HTTP request when making requests using cURL, allowing for flexible configuration of the POST field size for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setPostFieldSize(int $size = 0): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_POSTFIELDSIZE');

		// @phpstan-ignore-next-line
		$this->setOption(CURLOPT_POSTFIELDSIZE, $size);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_FOLLOWLOCATION}
	 *
	 * @param bool $bool Whether to follow redirects when CURLOPT_FOLLOWLOCATION is enabled, which allows libcurl to automatically follow HTTP redirects when making HTTP requests using cURL, with the option being set to a boolean value that indicates whether to enable or disable the automatic redirection handling, providing a convenient way to handle redirects without needing to manually manage them in the code.
	 * 
	 * @return ClientURLOption
	 */
	public function setFollowRedirects(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_FOLLOWLOCATION');

		if (function_exists('ini_get') && ini_get('open_basedir')) {
			throw new Exception('CURLOPT_FOLLOWLOCATION cannot be activated when an open_basedir is set');
		}

		$this->setOption(CURLOPT_FOLLOWLOCATION, $bool);

		return $this->returnContext();
	}

	/**
	 * This option tells the library to follow Location: header redirects that an HTTP server sends in a 30x response. The Location: header can specify a relative or an absolute URL to follow. The long parameter mode instructs how libcurl should act on subsequent requests.
	 * 
	 * {@see \CURLOPT_FOLLOWLOCATION}
	 *
	 * @param int $size Maximum number of redirects to follow when CURLOPT_FOLLOWLOCATION is enabled, which allows libcurl to limit the number of redirects it will follow when making HTTP requests using cURL, with the option being set to an integer value that specifies the maximum number of redirects to follow before giving up and returning an error, providing a way to prevent infinite redirect loops and control the behavior of redirection handling in cURL requests.
	 * 
	 * @return ClientURLOption
	 */
	public function setFollowLocationHeader(int $size = 0): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_FOLLOWLOCATION');

		if (function_exists('ini_get') && ini_get('open_basedir')) {
			throw new Exception('CURLOPT_FOLLOWLOCATION cannot be activated when an open_basedir is set');
		}

		$this->setOption(CURLOPT_FOLLOWLOCATION, $size);

		return $this->returnContext();
	}

	/**
	 * Pass epsv as a long. If the value is 1, it tells curl to use the EPSV command when doing passive FTP downloads (which it does by default). Using EPSV means that libcurl first attempts to use the EPSV command before using PASV. If you pass zero to this option, it does not use EPSV, only plain PASV.
	 * 
	 * {@see \CURLOPT_FTP_USE_EPSV}
	 *
	 * @param int $size Size of the EPSV command to use for FTP transfers, which can be an integer representing the size of the EPSV command that will be used for FTP transfers when making HTTP requests using cURL, allowing for flexible configuration of the FTP transfer settings based on the desired EPSV command size.
	 * 
	 * @return ClientURLOption
	 */
	public function setFTPUseEPSV(int $size = 0): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_FTP_USE_EPSV');

		$this->setOption(CURLOPT_FTP_USE_EPSV, $size);

		return $this->returnContext();
	}

	/**
	 * Pass a long. This sets the local port number of the socket used for the connection. This can be used in combination with CURLOPT_INTERFACE and you are recommended to use CURLOPT_LOCALPORTRANGE as well when this option is set. Valid port numbers are 1 - 65535.
	 * 
	 * {@see \CURLOPT_LOCALPORT}
	 *
	 * @param int $port Local port number to bind to for the request, which can be an integer representing the local port number that will be used for the outgoing connection when making HTTP requests using cURL, allowing for flexible configuration of the local port for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setLocalPort(int $port): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_LOCALPORT');

		$this->setOption(CURLOPT_LOCALPORT, $port);

		return $this->returnContext();
	}

	/**
	 * Pass a char pointer as parameter. This sets the interface name to use as outgoing network interface. The name can be an interface name, an IP address, or a hostname. If you prefer one of these, you can use the following special prefixes:
	 * 
	 * {@see \CURLOPT_INTERFACE}
	 *
	 * @param string $interface Network interface to use for the request, which can be a string representing the name of the network interface (e.g., "eth0", "en0") or an IP address associated with the interface, allowing for flexible configuration of the network interface that will be used for making HTTP requests using cURL.
	 * 
	 * @return ClientURLOption
	 */
	public function setInterface($interface): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_INTERFACE');

		$this->setOption(CURLOPT_INTERFACE, $interface);

		return $this->returnContext();
	}

	/**
	 * byte range to request
	 * 
	 * Pass a char pointer as parameter, which should contain the specified range you want to retrieve. It should be in the format "X-Y", where either X or Y may be left out and X and Y are byte indexes.
	 * 
	 * @param string $range Byte range to request, which can be a string in the format "X-Y" where X and Y are byte indexes, allowing for flexible specification of the byte range to be retrieved when making HTTP requests using cURL, enabling partial content retrieval based on the specified range.
	 * 
	 * {@see \CURLOPT_RANGE}
	 *
	 * @return ClientURLOption
	 */
	public function setRange($range): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_RANGE');

		$this->setOption(CURLOPT_RANGE, $range);

		return $this->returnContext();
	}

	/**
	 * disable proxy use for specific hosts
	 * 
	 * Pass a pointer to a null-terminated string. The string consists of a comma separated list of hostnames that do not require a proxy to get reached, even if one is specified. The only wildcard available is a single * character, which matches all hosts, and effectively disables the proxy. Each name in this list is matched as either a domain which contains the hostname, or the hostname itself. For example, "ample.com" would match ample.com, ample.com:80, and www.ample.com, but not www.example.com or ample.com.org.
	 * 
	 * @param string $noproxy Comma-separated list of hostnames that do not require a proxy, which can be used to specify hosts that should bypass the proxy settings when making HTTP requests using cURL, allowing for flexible configuration of proxy usage based on specific hostnames or domains.
	 * 
	 * {@see \CURLOPT_NOPROXY}
	 *
	 * @return ClientURLOption
	 */
	public function setNoProxy($noproxy): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_NOPROXY');

		$this->setOption(CURLOPT_NOPROXY, $noproxy);

		return $this->returnContext();
	}

	/**
	 * HTTP proxy authentication methods
	 * 
	 * Pass a long as parameter, which is set to a bitmask, to tell libcurl which HTTP authentication method(s) you want it to use for your proxy authentication. If more than one bit is set, libcurl first queries the site to see what authentication methods it supports and then it picks the best one you allow it to use. For some methods, this induces an extra network round-trip. Set the actual name and password with the CURLOPT_PROXYUSERPWD option.
	 * 
	 * {@see \CURLOPT_PROXYAUTH}
	 * 
	 * @param int $authentication Bitmask of HTTP authentication methods to use for proxy authentication, which can be an integer representing a bitmask of HTTP authentication methods that will be used for proxy authentication when making HTTP requests using cURL, allowing for flexible configuration of the authentication methods based on the desired security requirements and compatibility with the proxy server. The bitmask can be constructed using predefined constants such as CURLAUTH_BASIC, CURLAUTH_DIGEST, CURLAUTH_NTLM, etc., or a custom integer value that represents a combination of authentication methods.
	 * 
	 * @return ClientURLOption
	 */
	public function setProxyAuthentication(int $authentication): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_PROXYAUTH');

		$this->setOption(CURLOPT_PROXYAUTH, $authentication);

		return $this->returnContext();
	}

	/**
	 * Set the proxy to use for transfers with this easy handle. The parameter should be a char * to a null-terminated string holding the hostname or dotted numerical IP address. A numerical IPv6 address must be written within [brackets].
	 * 
	 * {@see \CURLOPT_PROXY}
	 *
	 * @param string $proxy The proxy to use for transfers with this easy handle, which can be a string representing the hostname or IP address of the proxy server to be used for making HTTP requests using cURL, allowing for flexible configuration of the proxy settings for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setProxy(string $proxy): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_PROXY');

		$this->setOption(CURLOPT_PROXY, $proxy);

		return $this->returnContext();
	}

	/**
	 * Pass a char pointer as parameter, which should be [username]:[password] to use for the connection to the HTTP proxy. Both the name and the password are URL decoded before used, so to include for example a colon in the username you should encode it as %3A. (This is different to how CURLOPT_USERPWD is used - beware.)
	 * 
	 * {@see \CURLOPT_PROXYUSERPWD}
	 *
	 * @param string $password The username and password to use for proxy authentication, which can be a string in the format "username:password" representing the credentials to be used for proxy authentication when making HTTP requests using cURL, allowing for flexible configuration of the proxy authentication credentials for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setProxyUserPassword(string $password): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_PROXYUSERPWD');

		$this->setOption(CURLOPT_PROXYUSERPWD, $password);

		return $this->returnContext();
	}

	/**
	 * Pass a long with this option to set the proxy port to connect to unless it is specified in the proxy string CURLOPT_PROXY or uses 443 for https proxies and 1080 for all others as default.
	 * 
	 * {@see \CURLOPT_PROXYPORT}
	 *
	 * @param int $port The port number to use for the proxy server, which can be an integer representing the port number that will be used for connecting to the proxy server when making HTTP requests using cURL, allowing for flexible configuration of the proxy settings for the request based on the desired port number for the proxy server.
	 * 
	 * @return ClientURLOption
	 */
	public function setProxyPort(int $port): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_PROXYPORT');

		$this->setOption(CURLOPT_PROXYPORT, $port);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a null-terminated string as parameter. It should point to the filename of your file holding cookie data to read. The cookie data can be in either the old Netscape / Mozilla cookie data format or regular HTTP headers (Set-Cookie style) dumped to a file.
	 * 
	 * {@see \CURLOPT_COOKIEFILE}
	 *
	 * @param string $file The path to the cookie file to be used for the request, which can be a string representing the file path to the cookie file that will be used for managing cookies when making HTTP requests using cURL, allowing for flexible configuration of the cookie management for the request based on the specified cookie file.
	 * 
	 * @return ClientURLOption
	 */
	public function setCookieFile(string $file): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_COOKIEFILE');

		$this->setOption(CURLOPT_COOKIEFILE, $file);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to your callback function, which should match the prototype shown above.
	 * CURLOPT_DEBUGFUNCTION replaces the standard debug function used when CURLOPT_VERBOSE is in effect. This callback receives debug information, as specified in the type argument. This function must return 0. The data pointed to by the char * passed to this function is not null-terminated, but is exactly of the size as told by the size argument.
	 * 
	 * {@see \CURLOPT_DEBUGFUNCTION}
	 * 
	 * @param callable $function The callback function to be used for debugging, which can be a callable (e.g., a function name, an array with an object and method, or a closure) that will be called for debugging purposes when making HTTP requests using cURL, allowing for flexible configuration of the debugging behavior for the request based on the specified callback function. The callback function should accept specific parameters as defined by the CURLOPT_DEBUGFUNCTION option in cURL documentation to handle the debug information appropriately.
	 * 
	 * @return ClientURLOption
	 */
	public function setDebugFunction(callable $function): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_DEBUGFUNCTION');

		$this->setOption(\CURLOPT_DEBUGFUNCTION, $function);

		return $this->returnContext();
	}

	/**
	 * Pass a long specifying your preferred size (in bytes) for the receive buffer in libcurl. The main point of this would be that the write callback gets called more often and with smaller chunks. Secondly, for some protocols, there is a benefit of having a larger buffer for performance.
	 * 
	 * {@see \CURLOPT_BUFFERSIZE}
	 *
	 * @param int $size The size of the buffer to use for the request, which can be an integer representing the size of the buffer that will be used for handling data during the HTTP request when making requests using cURL, allowing for flexible configuration of the buffer size based on the expected data size and performance requirements for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setBufferSize(int $size): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_BUFFERSIZE');

		$this->setOption(CURLOPT_BUFFERSIZE, $size);

		return $this->returnContext();
	}

	/**
	 * Pass a long as parameter set to 1L to enable or 0 to disable.
	 * TCP Fast Open (RFC 7413) is a mechanism that allows data to be carried in the SYN and SYN-ACK packets and consumed by the receiving end during the initial connection handshake, saving up to one full round-trip time (RTT).
	 * 
	 * {@see \CURLOPT_TCP_FASTOPEN}
	 *
	 * @param int $enable Whether to enable TCP Fast Open for the request, which allows libcurl to use TCP Fast Open when making HTTP requests using cURL, with the option being set to an integer value that indicates whether to enable or disable TCP Fast Open, providing a potential performance improvement for the request by reducing the latency of establishing a TCP connection.
	 * 
	 * @return ClientURLOption
	 */
	public function enableTCPFastOpen($enable): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_TCP_FASTOPEN');

		$this->setOption(CURLOPT_TCP_FASTOPEN, $enable);

		return $this->returnContext();
	}

	/**
	 * switch off the progress meter
	 * 
	 * {@see \CURLOPT_NOPROGRESS}
	 *
	 * @param int $number Whether to disable the progress meter for the request, which allows libcurl to disable the progress meter when making HTTP requests using cURL, with the option being set to an integer value that indicates whether to enable or disable the progress meter, providing a way to control the display of progress information during the request based on the desired behavior for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setNoProgress($number): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_NOPROGRESS');

		$this->setOption(CURLOPT_NOPROGRESS, $number);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to your callback function, which should match the prototype shown above.
	 * This option is deprecated and we encourage users to use the newer CURLOPT_XFERINFOFUNCTION instead, if you can.
	 * 
	 * {@see \CURLOPT_PROGRESSFUNCTION}
	 *
	 * @param callable $name The callback function to be used for progress updates, which can be a callable (e.g., a function name, an array with an object and method, or a closure) that will be called for progress updates when making HTTP requests using cURL, allowing for flexible configuration of the progress update behavior for the request based on the specified callback function. The callback function should accept specific parameters as defined by the CURLOPT_PROGRESSFUNCTION option in cURL documentation to handle the progress information appropriately.
	 * 
	 * @return ClientURLOption
	 */
	public function setProgressCallback(callable $name): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_PROGRESSFUNCTION');

		$this->setOption(CURLOPT_PROGRESSFUNCTION, $name);

		return $this->returnContext();
	}

	/**
	 * Pass a long. The set number is the redirection limit amount. If that many redirections have been followed, the next redirect triggers the error (CURLE_TOO_MANY_REDIRECTS). This option only makes sense if the CURLOPT_FOLLOWLOCATION is used at the same time.
	 * 
	 * {@see \CURLOPT_MAXREDIRS}
	 *
	 * @param int $number The maximum number of redirects to follow when CURLOPT_FOLLOWLOCATION is enabled, which allows libcurl to limit the number of redirects it will follow when making HTTP requests using cURL, with the option being set to an integer value that specifies the maximum number of redirects to follow before giving up and returning an error, providing a way to prevent infinite redirect loops and control the behavior of redirection handling in cURL requests.
	 * 
	 * @return ClientURLOption
	 */
	public function setMaxRedirects(int $number): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_MAXREDIRS');

		$this->setOption(CURLOPT_MAXREDIRS, $number);

		return $this->returnContext();
	}

	/**
	 * Parse a cookie jar file and return its contents as an array
	 *
	 * @param string $cookieFile The path to the cookie jar file
	 *
	 * @return array The parsed cookies
	 */
	public function parseCookieJar(string $cookieFile): array
	{
		$cookies = [];

		if (!file_exists($cookieFile)) {
			return $cookies;
		}

		$lines = file($cookieFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		foreach ($lines as $line) {
			if ($line[0] === '# ' || trim($line) === '') {
				continue;
			}

			$parts = explode("\t", $line);
			if (count($parts) >= 7) {
				$name = trim($parts[5]);
				$value = trim($parts[6]);
				$cookies[$name] = $value;
			}
		}

		return $cookies;
	}

	/**
	 * Pass a filename as a char *, null-terminated. This makes libcurl write all internally known cookies to the specified file when curl_easy_cleanup is called. If no cookies are kept in memory at that time, no file is created. Specify "-" as filename to instead have the cookies written to stdout. Using this option also enables cookies for this session, so if you for example follow a redirect it makes matching cookies get sent accordingly.
	 * 
	 * {@see \CURLOPT_COOKIEJAR}
	 *
	 * @param string $jar The path to the cookie jar file where cookies will be stored after the request is completed, allowing for persistent cookie management when making HTTP requests using cURL, with the option being set to a valid file path where the cookies can be saved and accessed for future requests.
	 * 
	 * @return ClientURLOption
	 */
	public function setCookieJar(string $jar): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_COOKIEJAR');

		$this->setOption(CURLOPT_COOKIEJAR, $jar);

		return $this->returnContext();
	}

	/**
	 * Pass one of the values below to set the type of the proxy.
	 * 
	 * {@see \CURLOPT_PROXYTYPE}
	 *
	 * @param mixed $type The type of proxy to set for the request, which can be a predefined constant representing the proxy type (e.g., CURLPROXY_HTTP, CURLPROXY_HTTPS, CURLPROXY_SOCKS5, etc.) or a custom value that indicates the specific proxy type to be used when making HTTP requests through a proxy using cURL, allowing for flexible configuration of the proxy settings for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setProxyType($type): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_PROXYTYPE');

		$this->setOption(CURLOPT_PROXYTYPE, $type);

		return $this->returnContext();
	}

	/**
	 * A long parameter set to 1 tells the library to not allow URLs that include a username.
	 * 
	 * {@see \CURLOPT_DISALLOW_USERNAME_IN_URL}
	 *
	 * @param int $disallow Whether to disallow username in URL, which allows libcurl to reject URLs that contain a username component when making HTTP requests using cURL, with the option being set to true to disallow usernames in URLs and false to allow them, providing an additional layer of security by preventing the inclusion of sensitive information in the URL when making requests.
	 * 
	 * @return ClientURLOption
	 */
	public function setDisallowUsernameInURL(int $disallow): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_DISALLOW_USERNAME_IN_URL');

		$this->setOption(CURLOPT_DISALLOW_USERNAME_IN_URL, $disallow);

		return $this->returnContext();
	}

	/**
	 * When a name is resolved and more than one IP address is returned, this function shuffles the order of all returned addresses so that they are used in a random order. This is similar to the ordering behavior of the legacy gethostbyname function which is no longer used on most platforms.
	 * 
	 * {@see \CURLOPT_DNS_SHUFFLE_ADDRESSES}
	 *
	 * @param int $onoff Whether to enable DNS shuffle addresses, which allows libcurl to randomize the order of resolved IP addresses for a given hostname when making HTTP requests using cURL, with the option being set to true to enable address shuffling and false to disable it, providing a way to improve load balancing and distribution of requests across multiple IP addresses associated with a hostname.
	 * 
	 * @return ClientURLOption
	 */
	public function setDNSShuffleAddresses(int $onoff): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_DNS_SHUFFLE_ADDRESSES');

		$this->setOption(CURLOPT_DNS_SHUFFLE_ADDRESSES, $onoff);

		return $this->returnContext();
	}

	/**
	 * Pass a long set to 2L as asking curl to verify in the HTTPS proxy's certificate name fields against the proxy name.
	 * 
	 * {@see \CURLOPT_PROXY_SSL_VERIFYHOST}
	 *
	 * @param int $verify Whether to enable proxy SSL host verification, which allows libcurl to verify the SSL certificate of the proxy server when making HTTP requests through a proxy, with the option being set to true to enable verification and false to disable it, providing an additional layer of security when communicating with proxy servers using cURL.
	 * 
	 * @return ClientURLOption
	 */
	public function setProxySSLVerifyHost(int $verify): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_PROXY_SSL_VERIFYHOST');

		$this->setOption(CURLOPT_PROXY_SSL_VERIFYHOST, $verify);

		return $this->returnContext();
	}

	/**
	 * Pass a long. If set to 1, TCP keepalive probes are used. 
	 * The delay and frequency of these probes can be controlled by the CURLOPT_TCP_KEEPIDLE, CURLOPT_TCP_KEEPINTVL, and CURLOPT_TCP_KEEPCNT options, provided the operating system supports them. 
	 * Set to 0 (default behavior) to disable keepalive probes.
	 * 
	 * {@see \CURLOPT_TCP_KEEPALIVE}
	 *
	 * @param bool $enable Whether to enable TCP keepalive probes, which allows libcurl to use TCP keepalive probes to maintain the connection alive and detect if the connection is still active when making HTTP requests using cURL, with the option being set to true to enable keepalive probes and false to disable them, allowing for better connection management and reliability in certain network conditions.
	 * 
	 * @return ClientURLOption
	 */
	public function enableTCPKeepAlive(bool $enable): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_TCP_KEEPALIVE');

		$this->setOption(CURLOPT_TCP_KEEPALIVE, $enable);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLINFO_HEADER_OUT}
	 *
	 * @param bool $enable Whether to enable the CURLINFO_HEADER_OUT option, which allows libcurl to track and provide information about the outgoing request headers when making HTTP requests using cURL, with the option being set to true to enable header tracking and false to disable it.
	 * 
	 * @return ClientURLOption
	 */
	public function setHeaderOut(bool $enable): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLINFO_HEADER_OUT');

		$this->setOption(CURLINFO_HEADER_OUT, $enable);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_FILE}
	 *
	 * @param mixed $filePointer A file pointer resource to set as the destination for the output of the cURL request, which can be a resource representing a file stream (e.g., fopen('php://temp', 'w')) that will be used to capture the response data from cURL operations, allowing for flexible handling of the output when making HTTP requests using cURL.
	 * 
	 * @return ClientURLOption
	 */
	public function setFileHandler($filePointer): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_FILE');

		$this->setOption(CURLOPT_FILE, $filePointer);

		return $this->returnContext();
	}

	/**
	 * Set the tunnel parameter to 1L to make libcurl tunnel all operations through the HTTP proxy (set with CURLOPT_PROXY). There is a big difference between using a proxy and to tunnel through it.
	 * 
	 * {@see \CURLOPT_HTTPPROXYTUNNEL}
	 * 
	 * @param bool $bool Whether to enable HTTP proxy tunneling, which allows libcurl to tunnel all operations through the specified HTTP proxy, providing a way to route requests through a proxy server when making HTTP requests using cURL, with the option being set to true to enable tunneling and false to disable it.
	 *
	 * @return ClientURLOption
	 */
	public function setProxyTunnel(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_HTTPPROXYTUNNEL');

		$this->setOption(CURLOPT_HTTPPROXYTUNNEL, $bool);

		return $this->returnContext();
	}

	/**
	 * Set the onoff parameter to 1 to make the library display a lot of verbose information about its operations on this handle. Useful for libcurl and/or protocol debugging and understanding. 
	 * The verbose information is sent to stderr, or the stream set with CURLOPT_STDERR.
	 * 
	 * {@see \CURLOPT_VERBOSE}
	 *
	 * @param bool $bool Whether to enable verbose output, which allows libcurl to provide detailed information about its operations for debugging purposes when making HTTP requests using cURL, with the verbose information being sent to stderr or a specified stream set with CURLOPT_STDERR.
	 * 
	 * @return ClientURLOption
	 */
	public function setVerbose(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_VERBOSE');

		$this->setOption(CURLOPT_VERBOSE, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a FILE * as parameter. Tell libcurl to use this stream instead of stderr when showing the progress meter and displaying CURLOPT_VERBOSE data.
	 * 
	 * {@see \CURLOPT_STDERR}
	 * 
	 * @param mixed $bool A file pointer resource to set as the destination for verbose output, which can be a resource representing a file stream (e.g., fopen('php://temp', 'w')) that will be used to capture verbose information from cURL operations, allowing for flexible handling of verbose output when making HTTP requests using cURL.
	 * 
	 * @return ClientURLOption
	 */
	public function setErrorLogHandle(mixed $bool): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_STDERR');

		$this->setOption(CURLOPT_STDERR, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a long that is a bitmask of options of how to deal with headers. The two mutually exclusive options are:
	 * CURLHEADER_UNIFIED - the headers specified in CURLOPT_HTTPHEADER are used in requests both to servers and proxies. With this option enabled, CURLOPT_PROXYHEADER does not have any effect.
	 * CURLHEADER_SEPARATE - makes CURLOPT_HTTPHEADER headers only get sent to a server and not to a proxy. Proxy headers must be set with CURLOPT_PROXYHEADER to get used. Note that if a non-CONNECT request is sent to a proxy, libcurl sends both server headers and proxy headers. When doing CONNECT, libcurl sends CURLOPT_PROXYHEADER headers only to the proxy and then CURLOPT_HTTPHEADER headers only to the server.
	 * 
	 * {@see \CURLOPT_HEADEROPT}
	 * 
	 * @param int $option The header option to set, which can be either CURLHEADER_UNIFIED or CURLHEADER_SEPARATE, determining how headers are handled in requests made using cURL, allowing for flexible configuration of header behavior for server and proxy interactions.
	 *
	 * @return ClientURLOption
	 */
	public function setHeaderOption(int $option = CURLHEADER_SEPARATE): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_HEADEROPT');

		$this->setOption(CURLOPT_HEADEROPT, $option);

		return $this->returnContext();
	}

	/**
	 * Pass a long. If the enable value is 1, it tells curl to use a global DNS cache that survives between easy handle creations and deletions. This is not thread-safe and this uses a global variable.
	 * 
	 * {@see \CURLOPT_DNS_USE_GLOBAL_CACHE}
	 *
	 * @param bool $bool Whether to enable the use of the global DNS cache, which allows libcurl to share DNS cache entries across multiple handles, improving performance by reducing redundant DNS lookups when making HTTP requests using cURL.
	 * 
	 * @return ClientURLOption
	 */
	public function setDnsUseGlobalCache(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_DNS_USE_GLOBAL_CACHE');

		$this->setOption(CURLOPT_DNS_USE_GLOBAL_CACHE, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a null-terminated string as parameter. It is used to set the User-Agent: header field in the HTTP request sent to the remote server. You can also set any custom header with CURLOPT_HTTPHEADER.
	 * 
	 * {@see \CURLOPT_USERAGENT}
	 *
	 * @param string $userAgent The User-Agent string to set in the request header, which can be a string representing the desired User-Agent that will be sent in the HTTP request header when making requests using cURL, allowing for flexible configuration of the User-Agent header for the request.
	 * 
	 * @return ClientURLOption
	 */
	public function setUserAgent($userAgent = ''): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_USERAGENT');

		$this->setOption(CURLOPT_USERAGENT, $userAgent);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_ENCODING}
	 *
	 * @param string $encoding The encoding type to set in the Accept-Encoding header, which can be a string representing the desired encoding (e.g., "gzip", "deflate", "br") or an empty string to indicate that all supported encodings are accepted, allowing for flexible configuration of the Accept-Encoding header in HTTP requests made using cURL.
	 * 
	 * @return ClientURLOption
	 */
	public function setAcceptEncoding($encoding = ''): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_ENCODING');

		$this->setOption(CURLOPT_ENCODING, $encoding);

		return $this->returnContext();
	}

	/**
	 * Set the Accept-Encoding header to gzip
	 *
	 * @return ClientURLOption
	 */
	public function setAcceptEncodingGzip(): ClientURLOption
	{
		return $this->setAcceptEncoding('gzip');
	}

	/**
	 * Pass a pointer to a null-terminated string as parameter. It is used to set one or more cookies in the HTTP request. The format of the string should be NAME=CONTENTS, where NAME is the cookie name and CONTENTS is what the cookie should contain.
	 * To set multiple cookies, set them all using a single option concatenated like this: "name1=content1; name2=content2;" etc. libcurl does not syntax check the data but assumes the application gives it what it needs to send.
	 * This option sets the cookie header explicitly in the outgoing request(s). If multiple requests are done due to authentication, followed redirections or similar, they all get this cookie passed on.
	 * 
	 * {@see \CURLOPT_COOKIE}
	 *
	 * @param mixed $cookieData The cookie data to set in the request header, either as a string or an array of cookies. If an array is provided, it will be converted to a string format suitable for the Cookie header.
	 * 
	 * @return ClientURLOption
	 */
	public function setCookieHeader(mixed $cookieData = ''): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_COOKIE');

		$this->setOption(CURLOPT_COOKIE, $cookieData);

		return $this->returnContext();
	}

	/**
	 * Pass a long set to 1 to mark this as a new cookie "session". It forces libcurl to ignore all cookies it is about to load that are "session cookies" from the previous session. By default, libcurl always loads all cookies, independent if they are session cookies or not. Session cookies are cookies without expiry date and they are meant to be alive and existing for this "session" only.
	 * 
	 * @param bool $bool Whether to enable cookie session handling
	 * 
	 * {@see \CURLOPT_COOKIESESSION}
	 *
	 * @return ClientURLOption
	 */
	public function useCookieSession(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant(constant: 'CURLOPT_COOKIESESSION');

		$this->setOption(CURLOPT_COOKIESESSION, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a long. The set amount is the maximum number of connections that libcurl may keep alive in its connection cache after use. The default is 5, and there is not much point in changing this value unless you are perfectly aware of how this works. This concerns connections using any of the protocols that support persistent connections.
	 * When reaching the maximum limit, curl closes the oldest connection present in the cache to prevent the number of connections from increasing.
	 * If you already have performed transfers with this curl handle, setting a smaller CURLOPT_MAXCONNECTS than before may cause open connections to get closed unnecessarily.
	 * 
	 * @param bool $maximumConnection Whether to enable maximum connection caching
	 * 
	 * {@see \CURLOPT_MAXCONNECTS}
	 *
	 * @return ClientURLOption
	 */
	public function setMaximumConnectionCount(bool $maximumConnection = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_MAXCONNECTS');

		$this->setOption(CURLOPT_MAXCONNECTS, $maximumConnection);

		return $this->returnContext();
	}

	/**
	 * Pass a long parameter set to 1 to enable this. When enabled, libcurl automatically sets the Referer: header field in HTTP requests to the full URL when it follows a Location: redirect to a new destination.
	 * 
	 * The automatic referer is set to the full previous URL even when redirects are done cross-origin or following redirects to insecure protocols. This is considered a minor privacy leak by some.
	 * 
	 * @param bool $bool Whether to enable automatic referer handling
	 * 
	 * {@see \CURLOPT_AUTOREFERER}
	 *
	 * @return ClientURLOption
	 */
	public function setAutoReferer(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_AUTOREFERER');

		$this->setOption(CURLOPT_AUTOREFERER, $bool);

		return $this->returnContext();
	}

	/**
	 * A long parameter set to 1 tells libcurl to not include the body-part in the output when doing what would otherwise be a download. For HTTP(S), this makes libcurl do a HEAD request. For most other protocols it means not asking to transfer the body data.
	 * 
	 * {@see \CURLOPT_NOBODY}
	 *
	 * @param bool $bool Whether to disable the request body
	 * 
	 * @return ClientURLOption
	 */
	public function setBodyEmpty(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_NOBODY');

		$this->setOption(CURLOPT_NOBODY, $bool);

		return $this->returnContext();
	}

	/**
	 * Set the body of the request
	 *
	 * @param array|string $body The request body
	 *
	 * @return bool True on success, false on failure
	 */
	public function setBody(array|string $body): bool
	{
		if (is_array($body)) {
			$body = http_build_query($body);
		}

		return $this->setOption(CURLOPT_POSTFIELDS, $body);
	}

	/**
	 * Pass a long. It sets the maximum time in seconds that you allow the connection phase to take. This timeout only limits the connection phase, it has no impact once libcurl has connected. The connection phase includes the name resolve (DNS) and all protocol handshakes and negotiations until there is an established connection with the remote side.
	 * 
	 * {@see \CURLOPT_CONNECTTIMEOUT}
	 * 
	 * @param bool|int $timeout Whether to enable connection timeout
	 * @param bool $useMilliseconds Whether to use milliseconds for the connection timeout value
	 * 
	 * @return ClientURLOption
	 */
	public function setConnectionTimeout(bool|int $timeout = true, bool $useMilliseconds = false): ClientURLOption
	{
		if ($useMilliseconds) {
			return $this->setConnectionTimeoutMilliseconds($timeout);
		} else {
			$this->throwUseUndefinedConstant('CURLOPT_CONNECTTIMEOUT');

			$this->setOption(CURLOPT_CONNECTTIMEOUT, $timeout);

			return $this->returnContext();
		}
	}

	/**
	 * Pass a long. It sets the maximum time in milliseconds that you allow the connection phase to take. This timeout only limits the connection phase, it has no impact once libcurl has connected. The connection phase includes the name resolve (DNS) and all protocol handshakes and negotiations until there is an established connection with the remote side.
	 * 
	 * {@see \CURLOPT_CONNECTTIMEOUT_MS}
	 *
	 * @param bool $timeout
	 * 
	 * @return ClientURLOption
	 */
	public function setConnectionTimeoutMilliseconds(bool $timeout = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_CONNECTTIMEOUT_MS');

		$this->setOption(CURLOPT_CONNECTTIMEOUT_MS, $timeout);

		return $this->returnContext();
	}

	/**
	 * Configure the request to not include a body
	 *
	 * @param bool $bool Whether to disable the request body
	 *
	 * @return ClientURLOption
	 */
	public function setNobody(bool $bool = true): ClientURLOption
	{
		$this->setBodyEmpty($bool);

		return $this->returnContext();
	}

	/**
	 * A parameter set to 1 tells the library to use ASCII mode for FTP transfers, instead of the default binary transfer. For Win32 systems it does not set the stdout to binary mode. This option can be usable when transferring text data between systems with different views on certain characters, such as newlines or similar.
	 * 
	 * {@see \CURLOPT_TRANSFERTEXT}
	 *
	 * @param bool $bool
	 * 
	 * @return ClientURLOption
	 */
	public function setTransferText(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_TRANSFERTEXT');

		$this->setOption(CURLOPT_TRANSFERTEXT, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a long specifying whether the TCP_NODELAY option is to be set or cleared (1L = set, 0 = clear). The option is set by default. This has no effect after the connection has been established.
	 * 
	 * {@see \CURLOPT_TCP_NODELAY}
	 *
	 * @param bool $nodelay
	 * 
	 * @return ClientURLOption
	 */
	public function setTcpNodelay(bool $nodelay = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_TCP_NODELAY');

		$this->setOption(CURLOPT_TCP_NODELAY, $nodelay);

		return $this->returnContext();
	}

	/**
	 * This option is deprecated. We strongly recommend using CURLOPT_REDIR_PROTOCOLS_STR instead because this option cannot control all available protocols.
	 * 
	 * {@see \CURLOPT_REDIR_PROTOCOLS}
	 *
	 * @return ClientURLOption
	 */
	public function setRedisProtocol(int $protocol = CURLPROTO_HTTP): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_REDIR_PROTOCOLS');

		$this->setOption(CURLOPT_REDIR_PROTOCOLS, $protocol);

		return $this->returnContext();
	}

	/**
	 * This option is deprecated. We strongly recommend using CURLOPT_PROTOCOLS_STR instead because this option cannot control all available protocols.
	 * 
	 * {@see \CURLOPT_PROTOCOLS}
	 *
	 * @return ClientURLOption
	 */
	public function setProtocol(int $protocol = CURLPROTO_HTTP): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_PROTOCOLS');

		$this->setOption(CURLOPT_PROTOCOLS, $protocol);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_BINARYTRANSFER}
	 *
	 * @return ClientURLOption
	 */
	public function setBinaryTransfer(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_BINARYTRANSFER');

		$this->setOption(CURLOPT_BINARYTRANSFER, $bool);

		return $this->returnContext();
	}

	/**
	 * Set the maximum upload speed
	 *
	 * @param int $bytePerSeconds The maximum speed in bytes per second
	 *
	 * @return ClientURLOption
	 */
	public function setMaximumUploadSpeed(int $bytePerSeconds = 1000): ClientURLOption
	{
		$this->setMaximumSendSpeed($bytePerSeconds);

		return $this->returnContext();
	}

	/**
	 * Pass a curl_off_t as parameter with the maxspeed. If an upload exceeds this speed (counted in bytes per second) the transfer pauses to keep the average speed less than or equal to the parameter value. Defaults to unlimited speed.
	 * 
	 * @param int $bytePerSeconds The maximum speed in bytes per second
	 * 
	 * {@see \CURLOPT_MAX_SEND_SPEED_LARGE}
	 *
	 * @return ClientURLOption
	 */
	public function setMaximumSendSpeed(int $bytePerSeconds = 1000): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_MAX_SEND_SPEED_LARGE');

		$this->setOption(CURLOPT_MAX_SEND_SPEED_LARGE, $bytePerSeconds);

		return $this->returnContext();
	}

	/**
	 * Set the maximum download speed
	 *
	 * @param int $bytePerSeconds The maximum speed in bytes per second
	 *
	 * @return ClientURLOption
	 */
	public function setMaximumDownloadSpeed(int $bytePerSeconds = 1000): ClientURLOption
	{
		$this->setMaximumReceiveSpeed($bytePerSeconds);

		return $this->returnContext();
	}

	/**
	 * Set the long weight to a number between 1 and 256.
	 * When using HTTP/2, this option sets the individual weight for this particular stream used by the easy handle. Setting and using weights only makes sense and is only usable when doing multiple streams over the same connections, which thus implies that you use CURLMOPT_PIPELINING.
	 * 
	 * {@see \CURLOPT_STREAM_WEIGHT}
	 *
	 * @return ClientURLOption
	 */
	public function setStreamWeight(int $weight): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_STREAM_WEIGHT');

		$this->setOption(CURLOPT_STREAM_WEIGHT, $weight);

		return $this->returnContext();
	}

	/**
	 * Pass a curl_off_t as parameter. If a download exceeds this maxspeed (counted in bytes per second) the transfer pauses to keep the average speed less than or equal to the parameter value. Defaults to unlimited speed.
	 * 
	 * {@see \CURLOPT_MAX_RECV_SPEED_LARGE}
	 *
	 * @return ClientURLOption
	 */
	public function setMaximumReceiveSpeed(int $bytePerSeconds = 1000): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_MAX_RECV_SPEED_LARGE');

		$this->setOption(CURLOPT_MAX_RECV_SPEED_LARGE, $bytePerSeconds);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_USERPWD}
	 *
	 * @return ClientURLOption
	 */
	public function setUserPassword($password): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_USERPWD');

		$this->setOption(CURLOPT_USERPWD, $password);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_RESOLVE}
	 *
	 * @return ClientURLOption
	 */
	public function setQuote(mixed $commandList): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_QUOTE');

		$this->setOption(CURLOPT_QUOTE, $commandList);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_RESOLVE}
	 *
	 * @return ClientURLOption
	 */
	public function setWriteHeader(string $headerFile): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_WRITEHEADER');

		$this->setOption(CURLOPT_WRITEHEADER, $headerFile);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a linked list of strings with hostname resolve information to use for requests with this handle. The linked list should be a fully valid list of struct curl_slist structs properly filled in. Use curl_slist_append to create the list and curl_slist_free_all to clean up an entire list.
	 * 
	 * {@see \CURLOPT_RESOLVE}
	 *
	 * @return ClientURLOption
	 */
	public function setResolve($resolve): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_RESOLVE');

		$this->setOption(CURLOPT_RESOLVE, $resolve);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to your callback function, as the prototype shows above.
	 * This callback function gets called by libcurl as soon as it needs to read data in order to send it to the peer - like if you ask it to upload or post data to the server. The data area pointed at by the pointer buffer should be filled up with at most nitems number of bytes by your function. size is always 1.
	 * 
	 * {@see \CURLOPT_READFUNCTION}
	 *
	 * @return ClientURLOption
	 */
	public function setReadFunction(\Closure $function): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_READFUNCTION');

		$this->setOption(CURLOPT_READFUNCTION, $function);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to your callback function, which should match the prototype shown above.
	 * 
	 * {@see \CURLOPT_WRITEFUNCTION}
	 * 
	 * @return ClientURLOption
	 */
	public function setWriteFunction(\Closure $function): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_WRITEFUNCTION');

		$this->setOption(CURLOPT_WRITEFUNCTION, $function);

		return $this->returnContext();
	}

	/**
	 * When uploading a file to a remote site, filesize should be used to tell libcurl what the expected size of the input file is. This value must be passed as a long. See also CURLOPT_INFILESIZE_LARGE for sending files larger than 2GB.
	 * 
	 * {@see \CURLOPT_INFILESIZE}
	 *
	 * @return ClientURLOption
	 */
	public function setInFileSize(int $size): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_INFILESIZE');

		$this->setOption(CURLOPT_INFILESIZE, $size);

		return $this->returnContext();
	}

	/**
	 * When uploading a file to a remote site, filesize should be used to tell libcurl what the expected size of the input file is. This value must be passed as a long. See also CURLOPT_INFILESIZE_LARGE for sending files larger than 2GB.
	 * 
	 * {@see \CURLOPT_INFILE}
	 *
	 * @return ClientURLOption
	 */
	public function setInFile($file): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_INFILE');

		$this->setOption(CURLOPT_INFILE, $file);

		return $this->returnContext();
	}

	/**
	 * Set the X-Requested-With header to XMLHttpRequest
	 *
	 * @return ClientURLOption
	 */
	public function setXHttpRequest(): ClientURLOption
	{
		$this->setHeader('X-Requested-With', 'XMLHttpRequest');

		return $this->returnContext();
	}

	/**
	 * Set the Origin header to the current URL domain
	 *
	 * @return ClientURLOption
	 */
	public function setOriginHeaderAsURL(): ClientURLOption
	{
		$url = new URLObject(self::$curlOptions['CURLOPT_URL']);
		$currentUrl = $url->getDomain(true);

		return $this->setHeader('Origin', $currentUrl);
	}

	/**
	 * Clear all set HTTP headers
	 *
	 * @return void
	 */
	public function clearHeaders(): void
	{
		self::$options['header'] = [];
	}

	/**
	 * Check if the header key is exists
	 * 
	 * @param string|StringObject $key
	 * @return bool
	 */
	public function hasHeader(string|StringObject $key)
	{
		return isset(self::$options[$key]);
	}

	/**
	 * Set an HTTP header
	 *
	 * @param string|StringObject $key The header key
	 * @param string|StringObject $value The header value
	 * @param bool $overwrite Whether to overwrite existing headers
	 *
	 * @return ClientURLOption
	 */
	public function setHeader(string|StringObject $key, string|StringObject $value, bool $overwrite = false): ClientURLOption
	{
		$headerData = [$key, $value];

		if (!isset(self::$options['header'])) {
			self::$options['header'] = [];
		}

		if (!$overwrite) {
			array_push(self::$options, $headerData);

			$headers = array_map(function ($header) {
				return implode(': ', $header);
			}, self::$options);

			$this->setHeaders($headers);
		} else {
			$this->setHeaders($headerData);
		}

		return $this->returnContext();
	}

	/**
	 * Set the Content-Type header to multipart/form-data
	 *
	 * @return ClientURLOption
	 */
	public function setMultipartContentType(): ClientURLOption
	{
		return $this->setContentType('multipart/form-data');
	}

	/**
	 * Sets the Content-Type header to application/x-www-form-urlencoded.
	 *
	 * @return ClientURLOption
	 */
	public function setContentTypeFormUrlEncoded(): ClientURLOption
	{
		return $this->setContentType('application/x-www-form-urlencoded');
	}

	/**
	 * Sets the Content-Type header to application/soap+xml.
	 *
	 * @return ClientURLOption
	 */
	public function setContentTypeXMLSoap(): ClientURLOption
	{
		return $this->setContentType('application/soap+xml');
	}

	/**
	 * Sets the Content-Type header to application/json.
	 *
	 * @return ClientURLOption
	 */
	public function setContentTypeApplicationJson(): ClientURLOption
	{
		return $this->setContentType('application/json');
	}

	/**
	 * Set the Content-Type header based on an application type
	 *
	 * @param string $applicationType The application type extension
	 *
	 * @return ClientURLOption
	 */
	public function setContentType(string $applicationType): ClientURLOption
	{
		$mimeType = MIME::getContentTypeFromExtension($applicationType);

		return $this->setHeader('Content-Type', $mimeType);
	}

	/**
	 * Set the Charset header
	 *
	 * @param string $charset The charset to specify
	 *
	 * @return ClientURLOption
	 */
	public function setCharset(string $charset): ClientURLOption
	{
		return $this->setHeader('Charset', $charset);
	}

	/**
	 * Set the Accept header
	 *
	 * @param string $contentType The accepted content type
	 *
	 * @return ClientURLOption
	 */
	public function setAccept(string $contentType): ClientURLOption
	{
		return $this->setHeader('Accept', $contentType);
	}

	/**
	 * Set the Accept header to XML
	 *
	 * @return ClientURLOption
	 */
	public function setAcceptXml(): ClientURLOption
	{
		return $this->setAccept('xml');
	}

	/**
	 * Set the Accept header to JSON
	 *
	 * @return ClientURLOption
	 */
	public function setAcceptJson(): ClientURLOption
	{
		return $this->setAccept('json');
	}

	/**
	 * Set the Content-Type header to JSON
	 *
	 * @return ClientURLOption
	 */
	public function setContentTypeJson(): ClientURLOption
	{
		return $this->setContentType('json');
	}

	/**
	 * Set the Content-Type header to XML
	 *
	 * @return ClientURLOption
	 */
	public function setContentTypeXML(): ClientURLOption
	{
		return $this->setContentType('text/xml');
	}

	/**
	 * Pass a pointer to a linked list of HTTP headers to pass to the server and/or proxy in your HTTP request. The same list can be used for both host and proxy requests.
	 * 
	 * {@see \CURLOPT_HTTPHEADER}
	 * 
	 * @param array $headers
	 *
	 * @return ClientURLOption
	 */
	public function setHeaders(array $headers = []): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_HTTPHEADER');

		$this->setOption(CURLOPT_HTTPHEADER, $headers);

		return $this->returnContext();
	}

	/**
	 * We discourage using this option since its scope is not obvious and hard to predict. Set the preferred port number in the URL instead.
	 * 
	 * {@see \CURLOPT_PORT}
	 *
	 * @return ClientURLOption
	 */
	public function setPort(int $port): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_PORT');

		$this->setOption(CURLOPT_PORT, $port);

		return $this->returnContext();
	}

	/**
	 * A parameter set to 1 tells libcurl to do a regular HTTP post. This also makes libcurl use a "Content-Type: application/x-www-form-urlencoded" header. This is the most commonly used POST method.
	 * 
	 * {@see \CURLOPT_POST}
	 *
	 * @return ClientURLOption
	 */
	public function setPostMethod(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_POST');

		$this->setOption(CURLOPT_POST, $bool);

		return $this->returnContext();
	}

	/**
	 * Set Basic Authentication credentials
	 *
	 * @param string $username The username
	 * @param string $password The password
	 *
	 * @return ClientURLOption
	 */
	public function setBasicAuthentication(string $username, string $password): ClientURLOption
	{
		// TODO
		return $this->returnContext();
	}

	/**
	 * Set a custom request method
	 *
	 * @param string $method The custom HTTP method
	 *
	 * @return ClientURLOption
	 */
	public function setCustomRequest(string $method): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_CUSTOMREQUEST');

		$this->setOption(CURLOPT_CUSTOMREQUEST, $method);

		return $this->returnContext();
	}

	/**
	 * Pass a long. If useget is 1, this forces the HTTP request to get back to using GET. Usable if a POST, HEAD, PUT, etc has been used previously using the same curl handle.
	 * 
	 * {@see \CURLOPT_HTTPGET}
	 *
	 * @return ClientURLOption
	 */
	public function setGetMethod(bool $bool = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_HTTPGET');

		$this->setOption(CURLOPT_HTTPGET, $bool);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to your callback function, which should match the prototype shown above.
	 * 
	 * {@see \CURLOPT_HEADERFUNCTION}
	 * 
	 * @return ClientURLOption
	 */
	public function setHeaderCallback($method): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_HEADERFUNCTION');

		$this->setOption(CURLOPT_HEADERFUNCTION, $method);

		return $this->returnContext();
	}

	/**
	 * Set a callback to receive response headers
	 *
	 * @return void
	 */
	private function receiveResponseHeader(): void
	{
		$this->setHeaderCallback(function ($ch, $header) use (&$headers) {
			$matches = [];

			if (preg_match('/^([^:]+)\s*:\s*([^\x0D\x0A]*)\x0D?\x0A?$/', $header, $matches)) {
				$headers[$matches[1]][] = $matches[2];
			}

			return strlen($header);
		});
	}

	/**
	 * {@see \CURLAUTH_ANYSAFE}
	 * 
	 * @return ClientURLOption
	 */
	public function setAnySafeAuthentication(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLAUTH_ANYSAFE');

		return $this->setAuthentication(\CURLAUTH_ANYSAFE);
	}

	/**
	 * {@see \CURLAUTH_ANY}
	 * 
	 * @return ClientURLOption
	 */
	public function setAnyAuthentication(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLAUTH_ANY');

		return $this->setAuthentication(\CURLAUTH_ANY);
	}

	/**
	 * {@see \CURLAUTH_NTLM}
	 * 
	 * @return ClientURLOption
	 */
	public function setNTLMAuthentication(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLAUTH_NTLM');

		return $this->setAuthentication(\CURLAUTH_NTLM);
	}

	/**
	 * {@see \CURLAUTH_GSSNEGOTIATE}
	 * 
	 * @return ClientURLOption
	 */
	public function setGSSNegotiateAuthentication(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLAUTH_GSSNEGOTIATE');

		return $this->setAuthentication(\CURLAUTH_GSSNEGOTIATE);
	}

	/**
	 * {@see \CURLAUTH_DIGEST}
	 * 
	 * @return ClientURLOption
	 */
	public function setDigestAuthentication(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLAUTH_DIGEST');

		return $this->setAuthentication(CURLAUTH_DIGEST);
	}

	/**
	 * {@see \CURL_HTTP_VERSION_NONE}
	 * 
	 * @return ClientURLOption
	 */
	public function setNoneHTTPVersion(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_HTTP_VERSION_NONE');

		return $this->setHTTPVersion(CURL_HTTP_VERSION_NONE);
	}

	/**
	 * {@see \CURL_HTTP_VERSION_1_0}
	 * 
	 * @return ClientURLOption
	 */
	public function setHTTPVersion_1_0(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_HTTP_VERSION_1_0');

		return $this->setHTTPVersion(CURL_HTTP_VERSION_1_0);
	}

	/**
	 * Set debug variables using a debug callback function
	 *
	 * @param mixed $currentPostBody The current post body reference
	 * @param mixed $postBodies The array reference to store post bodies
	 *
	 * @return ClientURLOption
	 */
	public function setDebugVariables($currentPostBody, $postBodies): ClientURLOption
	{
		return $this->setDebugFunction(function ($curl, $type, $data) use (&$currentPostBody, &$postBodies) {
			switch ($type) {
				case \CURLINFO_HEADER_OUT:
					if (empty($currentPostBody)) {
						return strlen($data);
					}

					$postBodies[] = $currentPostBody;
					$currentPostBody = '';
					break;
				case \CURLINFO_DATA_OUT:
					$currentPostBody .= $data;
					break;
			}

			return strlen($data);
		});
	}

	/**
	 * {@see \CURL_HTTP_VERSION_1_1}
	 * 
	 * @return ClientURLOption
	 */
	public function setHTTPVersion_1_1(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_HTTP_VERSION_1_1');

		return $this->setHTTPVersion(CURL_HTTP_VERSION_1_1);
	}

	/**
	 * {@see \CURL_HTTP_VERSION_2_0}
	 * 
	 * @return ClientURLOption
	 */
	public function setHTTPVersion_2_0(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_HTTP_VERSION_2_0');

		return $this->setHTTPVersion(CURL_HTTP_VERSION_2_0);
	}

	/**
	 * {@see \CURL_HTTP_VERSION_2TLS}
	 * 
	 * @return ClientURLOption
	 */
	public function setHTTPVersion_2_TLS(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_HTTP_VERSION_2TLS');

		return $this->setHTTPVersion(\CURL_HTTP_VERSION_2TLS);
	}

	/**
	 * Pass a long as parameter. It contains the time in number seconds that the transfer speed should be below the CURLOPT_LOW_SPEED_LIMIT for the library to consider it too slow and abort.
	 * 
	 * {@see \CURLOPT_LOW_SPEED_TIME}
	 * 
	 * @return ClientURLOption
	 */
	public function setLowSpeedLimitTime($value): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_LOW_SPEED_TIME');

		$this->setOption(CURLOPT_LOW_SPEED_TIME, $value);

		return $this->returnContext();
	}

	/**
	 * {@see \CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE}
	 * 
	 * @return ClientURLOption
	 */
	public function setHTTPPriorKnowledge(): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE');

		return $this->setHTTPVersion(\CURL_HTTP_VERSION_2_PRIOR_KNOWLEDGE);
	}

	/**
	 * Pass version a long, set to one of the values described below. They ask libcurl to use the specific HTTP versions.
	 * 
	 * {@see \CURLOPT_HTTP_VERSION}
	 *
	 * @return ClientURLOption
	 */
	public function setHTTPVersion(int $version = CURL_HTTP_VERSION_1_0): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_HTTP_VERSION');

		$this->setOption(CURLOPT_HTTP_VERSION, $version);

		return $this->returnContext();
	}

	/**
	 * Pass a long as parameter, which is set to a bitmask, to tell libcurl which authentication method(s) you want it to use speaking to the remote server.
	 * 
	 * {@see \CURLOPT_HTTPAUTH}
	 *
	 * @return ClientURLOption
	 */
	public function setAuthentication(int $authentication = CURLAUTH_ANY): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_HTTPAUTH');

		$this->setOption(CURLOPT_HTTPAUTH, $authentication);

		return $this->returnContext();
	}

	/**
	 * If onoff is 1, libcurl uses no functions that install signal handlers or any functions that cause signals to be sent to the process. This option is here to allow multi-threaded Unix applications to still set/use all timeout options etc, without risking getting signals.
	 * 
	 * {@see \CURLOPT_NOSIGNAL}
	 *
	 * @return ClientURLOption
	 */
	public function setNoSignal(bool $noSignal = false): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_NOSIGNAL');

		$this->setOption(CURLOPT_NOSIGNAL, $noSignal);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_TCP_KEEPIDLE}
	 *
	 * @return ClientURLOption
	 */
	public function setTcpKeepIdle(int $delay = 60): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_TCP_KEEPIDLE');

		if ($delay < 2147483648) {
			throw new Exception('Delay value is must be less than 2147483648');
		}

		$this->setOption(CURLOPT_TCP_KEEPIDLE, $delay);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_TCP_KEEPINTVL}
	 *
	 * @return ClientURLOption
	 */
	public function setReferer(string $where): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_REFERER');

		$this->setOption(CURLOPT_REFERER, $where);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_TCP_KEEPINTVL}
	 *
	 * @return ClientURLOption
	 */
	public function setTcpKeepInterval(int $interval = 60): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_TCP_KEEPINTVL');

		if ($interval < 2147483648) {
			throw new Exception('interval value is must be less than 2147483648');
		}

		$this->setOption(CURLOPT_TCP_KEEPINTVL, $interval);

		return $this->returnContext();
	}

	/**
	 * Pass a long as parameter. This defines how the CURLOPT_TIMEVALUE time value is treated. You can set this parameter to CURL_TIMECOND_IFMODSINCE or CURL_TIMECOND_IFUNMODSINCE.
	 * 
	 * {@see \CURLOPT_TIMECONDITION}
	 *
	 * @return ClientURLOption
	 */
	public function setTimeCondition(int $condition = CURL_TIMECOND_IFMODSINCE): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_TIMECONDITION');

		if (in_array($condition, [CURL_TIMECOND_IFMODSINCE, CURL_TIMECOND_IFUNMODSINCE])) {
			throw new Exception('You can set this parameter to CURL_TIMECOND_IFMODSINCE or CURL_TIMECOND_IFUNMODSINCE');
		}

		$this->setOption(CURLOPT_TIMECONDITION, $condition);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a null-terminated string as parameter.
	 * When changing the request method by setting CURLOPT_CUSTOMREQUEST, you do not actually change how libcurl behaves or acts: you only change the actual string sent in the request.
	 * 
	 * {@see \CURLOPT_CUSTOMREQUEST}
	 *
	 * @return ClientURLOption
	 */
	public function setCustomMethod($method): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_CUSTOMREQUEST');

		$this->setOption(CURLOPT_CUSTOMREQUEST, $method);

		return $this->returnContext();
	}

	/**
	 * Request an HTTP Options Method
	 *
	 * @return ClientURLOption
	 */
	public function setOptionsMethod(): ClientURLOption
	{
		return $this->setCustomMethod(HTTPRequestMethod::OPTIONS);
	}

	/**
	 * Request an HTTP Patch Method
	 *
	 * @return ClientURLOption
	 */
	public function setPatchMethod(): ClientURLOption
	{
		return $this->setCustomMethod(HTTPRequestMethod::PATCH);
	}

	/**
	 * Request an HTTP Head Method
	 *
	 * @return ClientURLOption
	 */
	public function setHeadMethod(): ClientURLOption
	{
		return $this->setCustomMethod(HTTPRequestMethod::HEAD);
	}

	/**
	 * Request an HTTP Put Method
	 *
	 * @return ClientURLOption
	 */
	public function setPutMethod(): ClientURLOption
	{
		return $this->setCustomMethod(HTTPRequestMethod::PUT);
	}

	/**
	 * Request an HTTP Delete Method
	 *
	 * @return ClientURLOption
	 */
	public function setDeleteMethod(): ClientURLOption
	{
		return $this->setCustomMethod(HTTPRequestMethod::DELETE);
	}

	/**
	 * {@see \CURLOPT_RETURNTRANSFER}
	 *
	 * @param bool $hasResponse
	 * 
	 * @return ClientURLOption
	 */
	public function setReturnTransfer(bool $hasResponse = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_RETURNTRANSFER');

		$this->setOption(CURLOPT_RETURNTRANSFER, $hasResponse);

		return $this->returnContext();
	}

	/**
	 * Pass a long as parameter containing timeout - the maximum time in milliseconds that you allow the libcurl transfer operation to take.
	 * 
	 * {@see \CURLOPT_TIMEOUT_MS}
	 * 
	 * @param int $value
	 */
	public function setTimeValueLarge(int $value): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_TIMEVALUE_LARGE');

		$this->setOption(CURLOPT_TIMEVALUE_LARGE, $value);

		return $this->returnContext();
	}

	/**
	 * Pass a long as parameter containing timeout - the maximum time in milliseconds that you allow the libcurl transfer operation to take.
	 * 
	 * {@see \CURLOPT_TIMEOUT_MS}
	 * 
	 * @param int $milliseconds
	 */
	public function setTimeoutMilliseconds(int $milliseconds): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_TIMEOUT_MS');

		$this->setOption(CURLOPT_TIMEOUT_MS, $milliseconds);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_XOAUTH2_BEARER}
	 * 
	 * @param string $token
	 */
	public function setXOAuth2Bearer(string $token): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_XOAUTH2_BEARER');

		$this->setOption(CURLOPT_XOAUTH2_BEARER, $token);

		return $this->returnContext();
	}

	/**
	 * {@see \CURLOPT_SSLCERTPASSWD}
	 * 
	 * @param string $password
	 */
	public function setSSLCertificationPassword(string $password): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_SSLCERTPASSWD');

		$this->setOption(CURLOPT_SSLCERTPASSWD, $password);

		return $this->returnContext();
	}

	/**
	 * Pass a long set to 1 to make the already specified crypto engine the default for (asymmetric) crypto operations.
	 * 
	 * {@see \CURLOPT_SSLENGINE_DEFAULT}
	 * 
	 * @param int $value
	 * 
	 * @return ClientURLOption
	 */
	public function setSSLEngineDefault(int $value = 1): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_SSLENGINE_DEFAULT');

		$this->setOption(CURLOPT_SSLENGINE_DEFAULT, $value);

		return $this->returnContext();
	}

	/**
	 * Pass a share handle as a parameter. The share handle must have been created by a previous call to curl_share_init. Setting this option, makes this curl handle use the data from the shared handle instead of keeping the data to itself. This enables several curl handles to share data. If the curl handles are used simultaneously in multiple threads, you must use the locking methods in the share handle. See curl_share_setopt for details.
	 * 
	 * {@see \CURLOPT_SHARE}
	 * 
	 * @param string $share
	 * 
	 * @return ClientURLOption
	 */
	public function setShare(string $share): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_SHARE');

		$this->setOption(CURLOPT_SHARE, $share);

		return $this->returnContext();
	}

	/**
	 * Set the local IPv4 address that the resolver should bind to. The argument should be of type char * and contain a single numerical IPv4 address as a string. Set this option to NULL to use the default setting (do not bind to a specific IP address).
	 * 
	 * {@see \CURLOPT_DNS_LOCAL_IP4}
	 * 
	 * @param string $address
	 * 
	 * @return ClientURLOption
	 */
	public function setDNSLocalIP4(string $address): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_DNS_LOCAL_IP4');

		$this->setOption(CURLOPT_DNS_LOCAL_IP4, $address);

		return $this->returnContext();
	}

	/**
	 * Set the local IPv6 address that the resolver should bind to. The argument should be of type char * and contain a single IPv6 address as a string. Set this option to NULL to use the default setting (do not bind to a specific IP address).
	 * 
	 * {@see \CURLOPT_DNS_LOCAL_IP6}
	 * 
	 * @param string $address
	 * 
	 * @return ClientURLOption
	 */
	public function setDNSLocalIP6(string $address): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_DNS_LOCAL_IP6');

		$this->setOption(CURLOPT_DNS_LOCAL_IP6, $address);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a null-terminated string as parameter. The string can be the filename of your pinned public key. The file format expected is "PEM" or "DER". The string can also be any number of base64 encoded sha256 hashes preceded by "sha256//" and separated by ";"
	 * 
	 * {@see \CURLOPT_PINNEDPUBLICKEY}
	 * 
	 * @param string $key
	 * 
	 * @return ClientURLOption
	 */
	public function setPinnedPublicKey(string $key): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_PINNEDPUBLICKEY');

		$this->setOption(CURLOPT_PINNEDPUBLICKEY, $key);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a linked list of aliases to be treated as valid HTTP 200 responses. Some servers respond with a custom header response line. For example, SHOUTcast servers respond with "ICY 200 OK". Also some old Icecast 1.3.x servers respond like that for certain user agent headers or in absence of such. By including this string in your list of aliases, the response gets treated as a valid HTTP header line such as "HTTP/1.0 200 OK".
	 * 
	 * {@see \CURLOPT_HTTP200ALIASES}
	 * 
	 * @param string $aliases
	 * 
	 * @return ClientURLOption
	 */
	public function setHTTP200CodeAliases(string $aliases): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_HTTP200ALIASES');

		$this->setOption(CURLOPT_HTTP200ALIASES, $aliases);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a linked list of FTP or SFTP commands to pass to the server after your FTP transfer request. The commands are only issued if no error occur. The linked list should be a fully valid list of struct curl_slist structs properly filled in as described for CURLOPT_QUOTE.
	 * Using this option multiple times makes the last set list override the previous ones. Set it to NULL to disable its use again.
	 * libcurl does not copy the list, it needs to be kept around until after the transfer has completed.
	 * 
	 * {@see \CURLOPT_POSTQUOTE}
	 * 
	 * @param mixed $commandList
	 * 
	 * @return ClientURLOption
	 */
	public function setPostQuote(mixed $commandList): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_POSTQUOTE');

		$this->setOption(CURLOPT_POSTQUOTE, $commandList);

		return $this->returnContext();
	}

	/**
	 * Pass a pointer to a linked list of strings with "connect to" information to use for establishing network connections with this handle. The linked list should be a fully valid list of struct curl_slist structs properly filled in. Use curl_slist_append to create the list and curl_slist_free_all to clean up an entire list.
	 * Each single string should be written using the format HOST:PORT:CONNECT-TO-HOST:CONNECT-TO-PORT where HOST is the host of the request, PORT is the port of the request, CONNECT-TO-HOST is the hostname to connect to, and CONNECT-TO-PORT is the port to connect to.
	 * The first string that matches the request's host and port is used.
	 * 
	 * {@see \CURLOPT_CONNECT_TO}
	 * 
	 * @param mixed $connectTo
	 * 
	 * @return ClientURLOption
	 */
	public function setConnectTo(mixed $connectTo): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_CONNECT_TO');

		$this->setOption(CURLOPT_CONNECT_TO, $connectTo);

		return $this->returnContext();
	}

	/**
	 * Enables the use of Unix domain sockets as connection endpoint and sets the path to path. If path is NULL, then Unix domain sockets are disabled.
	 * When enabled, curl connects to the Unix domain socket instead of establishing a TCP connection to the host. Since no network connection is created, curl does not resolve the DNS hostname in the URL.
	 * The maximum path length on Cygwin, Linux and Solaris is 107. On other platforms it might be even less.
	 * 
	 * {@see \CURLOPT_UNIX_SOCKET_PATH}
	 * 
	 * @param string $path
	 * 
	 * @return ClientURLOption
	 */
	public function setUnixSocketPath(string $path): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_UNIX_SOCKET_PATH');

		$this->setOption(CURLOPT_UNIX_SOCKET_PATH, $path);

		return $this->returnContext();
	}

	/**
	 * Pass the long value onoff set to 1 to ask libcurl to include the headers in the write callback (CURLOPT_WRITEFUNCTION). This option is relevant for protocols that actually have headers or other meta-data (like HTTP and FTP).
	 * 
	 * {@see \CURLOPT_HEADER}
	 *
	 * @param bool $hasResponse
	 * 
	 * @return ClientURLOption
	 */
	public function setReturnHeader(bool $hasResponse = true): ClientURLOption
	{
		$this->throwUseUndefinedConstant('CURLOPT_HEADER');

		$this->setOption(CURLOPT_HEADER, $hasResponse);

		return $this->returnContext();
	}

	#endregion
}
