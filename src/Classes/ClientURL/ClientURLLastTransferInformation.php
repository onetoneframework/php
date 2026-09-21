<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use CurlHandle;
use function count;

/**
 * Class ClientURLLastTransferInformation
 *
 * @package Clover\Classes
 */
class ClientURLLastTransferInformation
{
	/** @var resource */
	private static $session;

	/**
	 * Constructor
	 *
	 * @param CurlHandle|resource $session cURL session resource to retrieve transfer information from, which is typically obtained from a cURL request and can be used to access various details about the last transfer, such as the effective URL, HTTP status code, content type, total time taken for the transfer, download and upload sizes, speeds, and other relevant information that can help analyze the performance and outcome of the cURL request
	 */
	public function __construct(mixed $session)
	{
		self::$session = $session;
	}

	/**
	 * Get information about the last transfer
	 *
	 * @param int|null $key Optional cURL information key to retrieve specific information about the last transfer, such as CURLINFO_EFFECTIVE_URL, CURLINFO_HTTP_CODE, CURLINFO_CONTENT_TYPE, etc., which can be used to access specific details about the last transfer based on the provided key. If no key is provided, it will return an associative array containing all available information about the last transfer.
	 *
	 * @return array{
	 * 	url:mixed, 
	 * 	content_type:mixed, 
	 * 	http_code:mixed, 
	 * 	header_size:mixed, 
	 * 	request_size:mixed, 
	 * 	filetime:mixed,
	 * 	ssl_verify_result:mixed, 
	 * 	redirect_count:mixed, 
	 * 	total_time:mixed, 
	 * 	namelookup_time:mixed, 
	 * 	connect_time:mixed, 
	 * 	pretransfer_time:mixed, 
	 * 	size_upload:mixed, 
	 * 	size_download:mixed, 
	 * 	speed_download:mixed, 
	 * 	speed_upload:mixed, 
	 * 	download_content_length:mixed, 
	 * 	upload_content_length:mixed, 
	 * 	starttransfer_time:mixed, 
	 * 	redirect_time:mixed, 
	 * 	certinfo:mixed, 
	 * 	primary_ip:mixed, 
	 * 	primary_port:mixed, 
	 * 	local_ip:mixed, 
	 * 	local_port:mixed, 
	 * 	redirect_url:mixed, 
	 * 	request_header:mixed, 
	 * 	posttransfer_time_us:mixed
	 * }|mixed Information about the last transfer based on the provided key, or an associative array of all available information if no key is provided. The specific information returned will depend on the key used, and it may include details such as the effective URL, HTTP status code, content type, total time taken for the transfer, download and upload sizes, speeds, and other relevant information about the last transfer.
	 */
	private function getInformation(?int $key = -1): mixed
	{
		if (isset($key) && $key > 0) {
			return curl_getinfo(self::$session, $key);
		}

		return curl_getinfo(self::$session);
	}

	/**
	 * Get Content-Type
	 *
	 * @return mixed Content-Type of the last transfer, which indicates the media type of the response received from the server during the last cURL request, and can be used to understand the format of the data returned by the server, such as "text/html", "application/json", "image/png", etc., which can help determine how to process and handle the response data appropriately based on its content type.
	 */
	public function getContentType(): mixed
	{
		return $this->getInformation(CURLINFO_CONTENT_TYPE);
	}

	/**
	 * Get size of retrieved headers
	 *
	 * @return mixed Size of the headers retrieved during the last transfer, which indicates the total size in bytes of the headers received from the server in response to the cURL request, and can be used to analyze the amount of header data returned by the server, which may include information such as cookies, caching directives, content type, content length, and other relevant metadata about the response that can help understand the nature of the response and how to handle it appropriately.
	 */
	public function getHeaderSize(): mixed
	{
		return $this->getInformation(CURLINFO_HEADER_SIZE);
	}

	/**
	 * Get the number of uploaded bytes
	 *
	 * @return mixed Number of bytes uploaded during the last transfer, which indicates the total size in bytes of the data sent to the server in the request body during the cURL request, and can be used to analyze the amount of data uploaded to the server, which may include form data, JSON payloads, file uploads, or other types of data that were sent as part of the request, and can help understand the size of the data being transmitted to the server and its potential impact on performance and network usage.
	 */
	public function getUploadedSize(): mixed
	{
		return $this->getInformation(CURLINFO_SIZE_UPLOAD);
	}

	/**
	 * Get the number of downloaded bytes
	 *
	 * @return mixed Number of bytes downloaded during the last transfer, which indicates the total size in bytes of the data received from the server in the response body during the cURL request, and can be used to analyze the amount of data downloaded from the server, which may include HTML content, JSON responses, file downloads, or other types of data that were returned as part of the response, and can help understand the size of the data being received from the server and its potential impact on performance and network usage.
	 */
	public function getDownloadedSize(): mixed
	{
		return $this->getInformation(CURLINFO_SIZE_DOWNLOAD);
	}

	/**
	 * Get the number of uploaded bytes
	 *
	 * @return mixed Average upload speed during the last transfer, which indicates the average speed in bytes per second at which data was uploaded to the server during the cURL request, and can be used to analyze the performance of the upload process, which may be affected by factors such as network conditions, server response time, and the size of the data being uploaded, and can help understand the efficiency of the upload operation and identify potential bottlenecks or issues that may need to be addressed for optimal performance.
	 */
	public function getAverageUploadSpeed(): mixed
	{
		return $this->getInformation(CURLINFO_SPEED_UPLOAD);
	}

	/**
	 * Get download speed
	 *
	 * @return mixed Average download speed during the last transfer, which indicates the average speed in bytes per second at which data was downloaded from the server during the cURL request, and can be used to analyze the performance of the download process, which may be affected by factors such as network conditions, server response time, and the size of the data being downloaded, and can help understand the efficiency of the download operation and identify potential bottlenecks or issues that may need to be addressed for optimal performance.
	 */
	public function getAverageDownloadSpeed(): mixed
	{
		return $this->getInformation(CURLINFO_SPEED_DOWNLOAD);
	}

	/**
	 * Get the specified size of the upload
	 *
	 * @return mixed Content-length of the upload during the last transfer, which indicates the total size in bytes of the data that was intended to be uploaded to the server as specified in the request headers during the cURL request, and can be used to analyze the expected size of the data being uploaded, which may be different from the actual uploaded size due to factors such as network conditions, server response time, or other issues that may affect the upload process, and can help understand the intended size of the data being transmitted to the server for better analysis and troubleshooting.
	 */
	public function getUploadContentLength(): mixed
	{
		return $this->getInformation(CURLINFO_CONTENT_LENGTH_UPLOAD);
	}

	/**
	 * Get content-length of download
	 *
	 * @return mixed Content-length of the download during the last transfer, which indicates the total size in bytes of the data that was intended to be downloaded from the server as specified in the response headers during the cURL request, and can be used to analyze the expected size of the data being downloaded, which may be different from the actual downloaded size due to factors such as network conditions, server response time, or other issues that may affect the download process, and can help understand the intended size of the data being received from the server for better analysis and troubleshooting.
	 */
	public function getDownloadContentLength(): mixed
	{
		return $this->getInformation(CURLINFO_CONTENT_LENGTH_DOWNLOAD);
	}

	/**
	 * Get SSL verification result
	 *
	 * @return mixed SSL verification result of the last transfer, which indicates the outcome of the SSL certificate verification process during the cURL request, and can be used to analyze the security of the connection to the server, which may be affected by factors such as invalid or expired SSL certificates, mismatched hostnames, or other SSL-related issues that may impact the trustworthiness of the connection and require attention for secure communication with the server.
	 */
	public function getSSLVerifyResult(): mixed
	{
		return $this->getInformation(CURLINFO_SSL_VERIFYRESULT);
	}

	/**
	 * Get last sent header
	 *
	 * @return mixed Last sent header during the last transfer, which indicates the most recent header that was sent to the server as part of the cURL request, and can be used to analyze the headers being transmitted to the server, which may include information such as cookies, authentication tokens, content type, or other relevant metadata that can help understand the nature of the request being made to the server and how it may impact the response received.
	 */
	public function getHeaderOutput(): mixed
	{
		return $this->getInformation(CURLINFO_HEADER_OUT);
	}

	/**
	 * Get last effective URL
	 *
	 * @return mixed Effective URL of the last transfer, which indicates the final URL that was accessed during the cURL request after any redirects or other URL modifications that may have occurred, and can be used to analyze the actual endpoint that was reached during the request, which may be different from the original URL specified in the request due to factors such as server-side redirects, URL rewriting, or other URL transformations that may impact the outcome of the request and require attention for accurate analysis and troubleshooting.
	 */
	public function getEffectiveURL(): mixed
	{
		return $this->getInformation(CURLINFO_EFFECTIVE_URL);
	}

	/**
	 * Get the remote time of the retrieved document
	 *
	 * @return mixed Remote time of the retrieved document during the last transfer, which indicates the time in seconds since the Unix epoch (January 1, 1970) when the document was last modified on the server, and can be used to analyze the freshness of the data being retrieved from the server, which may be relevant for caching purposes or for understanding the age of the content being accessed, and can help determine if the data is up-to-date or if it may require refreshing for accurate analysis and troubleshooting.
	 */
	public function getRemoteTime(): mixed
	{
		return $this->getInformation(CURLINFO_FILETIME);
	}

	/**
	 * Get HTTP status code
	 *
	 * @return mixed HTTP status code of the last transfer, which indicates the response status code returned by the server during the cURL request, and can be used to analyze the outcome of the request, which may indicate success (2xx), client errors (4xx), server errors (5xx), or other relevant status codes that can help understand the result of the request and identify potential issues that may require attention for accurate analysis and troubleshooting.
	 */
	public function getStatusCode(): mixed
	{
		return $this->getInformation(CURLINFO_HTTP_CODE);
	}

	/**
	 * Get the time until connect
	 *
	 * @return mixed Time taken to establish the connection to the server during the last transfer, which indicates the time in seconds that it took to connect to the server after initiating the cURL request, and can be used to analyze the performance of the connection process, which may be affected by factors such as network conditions, server response time, or other issues that may impact the ability to establish a connection with the server and require attention for optimal performance and troubleshooting.
	 */
	public function getConnectionTime(): mixed
	{
		return $this->getInformation(CURLINFO_CONNECT_TIME);
	}

	/**
	 * Get the time until the file transfer start
	 *
	 * @return mixed Time taken until the file transfer starts during the last transfer, which indicates the time in seconds that it took from the initiation of the cURL request until the first byte of the response is received from the server, and can be used to analyze the performance of the initial response phase of the request, which may be affected by factors such as network conditions, server response time, or other issues that may impact the time it takes for the server to start sending data back in response to the request and require attention for optimal performance and troubleshooting.
	 */
	public function getPreTransferTime(): mixed
	{
		return $this->getInformation(CURLINFO_PRETRANSFER_TIME);
	}

	/**
	 * Get the time until the first byte is received
	 *
	 * @return mixed Time taken until the first byte is received during the last transfer, which indicates the time in seconds that it took from the initiation of the cURL request until the first byte of the response is received from the server, and can be used to analyze the performance of the initial response phase of the request, which may be affected by factors such as network conditions, server response time, or other issues that may impact the time it takes for the server to start sending data back in response to the request and require attention for optimal performance and troubleshooting.
	 */
	public function getStartTransferTime(): mixed
	{
		return $this->getInformation(CURLINFO_STARTTRANSFER_TIME);
	}

	/**
	 * Get the time taken for all redirections
	 *
	 * @return mixed Time taken for all redirections during the last transfer, which indicates the total time in seconds that it took for all redirection steps to complete during the cURL request, and can be used to analyze the performance of the redirection process, which may be affected by factors such as network conditions, server response time, or other issues that may impact the time it takes for the server to handle redirects and require attention for optimal performance and troubleshooting.
	 */
	public function getRedirectTime(): mixed
	{
		return $this->getInformation(CURLINFO_REDIRECT_TIME);
	}

	/**
	 * Get the number of redirects
	 *
	 * @return mixed Number of redirects during the last transfer, which indicates the total count of redirection steps that occurred during the cURL request, and can be used to analyze the behavior of the server in handling redirects, which may be relevant for understanding the flow of the request and response, as well as identifying potential issues with excessive redirects or redirect loops that may require attention for accurate analysis and troubleshooting.
	 */
	public function getRedirectCount(): mixed
	{
		return $this->getInformation(CURLINFO_REDIRECT_COUNT);
	}

	/**
	 * Get IP address of last connection
	 *
	 * @return mixed IP address of the last connection made during the last transfer, which indicates the IP address of the server that was connected to during the cURL request, and can be used to analyze the network connection and identify the server that was accessed, which may be relevant for understanding the source of the response, troubleshooting connectivity issues, or performing other network-related analysis based on the IP address of the server involved in the transfer.
	 */
	public function getLastConnectionIPAddress(): mixed
	{
		return $this->getInformation(CURLINFO_PRIMARY_IP);
	}

	/**
	 * Get the latest destination port number
	 *
	 * @return mixed Port number of the last connection made during the last transfer, which indicates the port number on the server that was connected to during the cURL request, and can be used to analyze the network connection and identify the specific service or application that was accessed on the server, which may be relevant for understanding the nature of the response, troubleshooting connectivity issues, or performing other network-related analysis based on the port number of the server involved in the transfer.
	 */
	public function getLastConnectionPortNumber(): mixed
	{
		return $this->getInformation(CURLINFO_PRIMARY_IP);
	}

	/**
	 * Get the name lookup time
	 *
	 * @return mixed Time taken for the name lookup during the last transfer, which indicates the time in seconds that it took to resolve the hostname to an IP address during the cURL request, and can be used to analyze the performance of the DNS resolution process, which may be affected by factors such as network conditions, DNS server response time, or other issues that may impact the time it takes to resolve the hostname and require attention for optimal performance and troubleshooting.
	 */
	public function getLookupNameTime(): mixed
	{
		return $this->getInformation(CURLINFO_NAMELOOKUP_TIME);
	}

	/**
	 * Get the last response code
	 *
	 * @return mixed HTTP response code of the last transfer, which indicates the status code returned by the server in response to the cURL request, and can be used to analyze the outcome of the request, which may indicate success (2xx), client errors (4xx), server errors (5xx), or other relevant status codes that can help understand the result of the request and identify potential issues that may require attention for accurate analysis and troubleshooting.
	 */
	public function getResponseCode(): mixed
	{
		return $this->getInformation(CURLINFO_RESPONSE_CODE);
	}

	/**
	 * Get size of the request
	 *
	 * @return mixed Size of the request sent during the last transfer, which indicates the total size in bytes of the data that was sent to the server as part of the cURL request, including headers and body, and can be used to analyze the amount of data being transmitted to the server, which may be relevant for understanding the performance of the request, troubleshooting issues related to request size limits, or performing other analysis based on the size of the data being sent in the request.
	 */
	public function getRequestSize(): mixed
	{
		return $this->getInformation(CURLINFO_REQUEST_SIZE);
	}

	/**
	 * Get primary port number
	 *
	 * @return mixed Port number of the last connection made during the last transfer, which indicates the port number on the server that was connected to during the cURL request, and can be used to analyze the network connection and identify the specific service or application that was accessed on the server, which may be relevant for understanding the nature of the response, troubleshooting connectivity issues, or performing other network-related analysis based on the port number of the server involved in the transfer.
	 */
	public function getPrimaryPort(): mixed
	{
		return $this->getInformation(CURLINFO_PRIMARY_PORT);
	}

	/**
	 * Get primary IP address
	 *
	 * @return mixed IP address of the last connection made during the last transfer, which indicates the IP address of the server that was connected to during the cURL request, and can be used to analyze the network connection and identify the server that was accessed, which may be relevant for understanding the source of the response, troubleshooting connectivity issues, or performing other network-related analysis based on the IP address of the server involved in the transfer.
	 */
	public function getPrimaryIP(): mixed
	{
		return $this->getInformation(CURLINFO_PRIMARY_IP);
	}

	/**
	 * Get local port number
	 *
	 * @return mixed Port number of the local end of the connection during the last transfer, which indicates the port number on the client side that was used for the connection to the server during the cURL request, and can be used to analyze the network connection and identify the specific port being used on the client side for communication with the server, which may be relevant for understanding the nature of the connection, troubleshooting connectivity issues, or performing other network-related analysis based on the local port number involved in the transfer.
	 */
	public function getLocalPort(): mixed
	{
		return $this->getInformation(CURLINFO_LOCAL_PORT);
	}

	/**
	 * Get local IP address
	 *
	 * @return mixed IP address of the local end of the connection during the last transfer, which indicates the IP address on the client side that was used for the connection to the server during the cURL request, and can be used to analyze the network connection and identify the specific IP address being used on the client side for communication with the server, which may be relevant for understanding the nature of the connection, troubleshooting connectivity issues, or performing other network-related analysis based on the local IP address involved in the transfer.
	 */
	public function getLocalIP(): mixed
	{
		return $this->getInformation(CURLINFO_LOCAL_IP);
	}

	/**
	 * Get total time of previous transfer
	 *
	 * @return mixed Total time taken for the entire transfer during the last transfer, which indicates the total time in seconds that it took to complete the cURL request from start to finish, including all phases of the request such as DNS resolution, connection establishment, data transfer, and response processing, and can be used to analyze the overall performance of the request, identify potential bottlenecks or issues that may impact the time taken for the transfer, and perform other analysis based on the total time of the transfer for accurate analysis and troubleshooting.
	 */
	public function getTotalTransferTime(): mixed
	{
		return $this->getInformation(CURLINFO_TOTAL_TIME);
	}

	/**
	 * Get number of created connections
	 *
	 * @return mixed Number of connections created during the last transfer, which indicates the total count of connections that were established to the server during the cURL request, and can be used to analyze the connection behavior of the request, which may be relevant for understanding the performance of the request, troubleshooting issues related to connection limits or timeouts, or performing other analysis based on the number of connections created during the transfer.
	 */
	public function getCreatedConnectionCount(): mixed
	{
		return $this->getInformation(CURLINFO_NUM_CONNECTS);
	}

	/**
	 * Get the recently received CSeq
	 *
	 * @return mixed Most recent CSeq received during the last transfer, which indicates the value of the CSeq header that was received from the server in response to the cURL request, and can be used to analyze the communication between the client and server, which may be relevant for understanding the sequence of requests and responses, troubleshooting issues related to CSeq mismatches or other protocol-related problems, or performing other analysis based on the recently received CSeq value for accurate analysis and troubleshooting.
	 */
	public function getRecentReceivedCSeq(): mixed
	{
		return $this->getInformation(CURLINFO_RTSP_CSEQ_RECV);
	}

	/**
	 * Get the next RTSP client CSeq
	 *
	 * @return mixed Next RTSP client CSeq during the last transfer, which indicates the value of the CSeq header that is expected to be sent in the next RTSP request from the client to the server, and can be used to analyze the communication between the client and server in RTSP protocol interactions, which may be relevant for understanding the sequence of requests and responses, troubleshooting issues related to CSeq mismatches or other protocol-related problems, or performing other analysis based on the next RTSP client CSeq value for accurate analysis and troubleshooting in RTSP-based communication scenarios.
	 */
	public function getNextRTSPClientCSeq(): mixed
	{
		return $this->getInformation(CURLINFO_RTSP_CLIENT_CSEQ);
	}


	/**
	 * Get FTP server entry path
	 *
	 * @return mixed FTP server entry path during the last transfer, which indicates the path on the FTP server that was accessed during the cURL request, and can be used to analyze the FTP communication and identify the specific location on the server that was accessed, which may be relevant for understanding the nature of the FTP request, troubleshooting issues related to FTP paths or permissions, or performing other analysis based on the FTP server entry path involved in the transfer.
	 */
	public function getFTPServerEntryPath(): mixed
	{
		return $this->getInformation(CURLINFO_FTP_ENTRY_PATH);
	}

	/**
	 * Get all known cookies
	 *
	 * @return mixed All known cookies during the last transfer, which indicates the cookies that were sent to the server in the request or received from the server in the response during the cURL request, and can be used to analyze the cookie behavior of the request, which may be relevant for understanding session management, authentication, or other aspects of cookie handling in the communication between the client and server, and can help identify potential issues related to cookies for accurate analysis and troubleshooting.
	 */
	public function getAllKnownCookies(): mixed
	{
		return $this->getInformation(CURLINFO_COOKIELIST);
	}

	/**
	 * Get the CONNECT response code
	 *
	 * @return mixed CONNECT response code during the last transfer, which indicates the HTTP response code received from the server in response to a CONNECT request made during the cURL request, and can be used to analyze the outcome of the CONNECT request, which may indicate success (2xx), client errors (4xx), server errors (5xx), or other relevant status codes that can help understand the result of the CONNECT request and identify potential issues that may require attention for accurate analysis and troubleshooting in scenarios where a CONNECT request is involved in the communication with the server.
	 */
	public function getConnectCode(): mixed
	{
		return $this->getInformation(CURLINFO_HTTP_CONNECTCODE);
	}

	/**
	 * Get errno number from last connect failure
	 *
	 * @return mixed Errno number from the last connect failure during the last transfer, which indicates the error number associated with the most recent connection failure that occurred during the cURL request, and can be used to analyze the nature of the connection failure, which may be relevant for understanding issues related to network connectivity, server availability, or other factors that may impact the ability to establish a connection with the server, and can help identify potential issues for accurate analysis and troubleshooting based on the errno number from the last connect failure.
	 */
	public function getLastConnectFailureErrorNumber(): mixed
	{
		return $this->getInformation(CURLINFO_OS_ERRNO);
	}

	/**
	 * Get all transfer information
	 *
	 * @return array{
	 * 	url:mixed, 
	 * 	content_type:mixed, 
	 * 	http_code:mixed, 
	 * 	header_size:mixed, 
	 * 	request_size:mixed, 
	 * 	filetime:mixed, 
	 * 	ssl_verify_result:mixed, 
	 * 	redirect_count:mixed, 
	 * 	total_time:mixed, 
	 * 	namelookup_time:mixed, 
	 * 	connect_time:mixed, 
	 * 	pretransfer_time:mixed, 
	 * 	size_upload:mixed, 
	 * 	size_download:mixed, 
	 * 	speed_download:mixed, 
	 * 	speed_upload:mixed, 
	 * 	download_content_length:mixed, 
	 * 	upload_content_length:mixed, 
	 * 	starttransfer_time:mixed, 
	 * 	redirect_time:mixed, 
	 * 	certinfo:mixed, 
	 * 	primary_ip:mixed, 
	 * 	primary_port:mixed, 
	 * 	local_ip:mixed, 
	 * 	local_port:mixed, 
	 * 	redirect_url:mixed, 
	 * 	request_header:mixed, 
	 * 	posttransfer_time_us:mixed
	 * }|mixed All available information about the last transfer, which includes various details such as the effective URL, HTTP status code, content type, total time taken for the transfer, download and upload sizes, speeds, and other relevant information that can help analyze the performance and outcome of the cURL request in a comprehensive manner for accurate analysis and troubleshooting.
	 */
	public function getAll(): mixed
	{
		return $this->getInformation();
	}

	/**
	 * Get HTTP version used
	 *
	 * @return mixed HTTP version used during the last transfer, which indicates the version of the HTTP protocol that was used for the communication between the client and server during the cURL request, and can be used to analyze the compatibility and behavior of the request based on the HTTP version, which may be relevant for understanding issues related to protocol support, performance, or other factors that may impact the communication with the server and require attention for accurate analysis and troubleshooting.
	 */
	public function getHTTPVersion(): mixed
	{
		return $this->getInformation(CURLINFO_HTTP_VERSION);
	}

	/**
	 * Get protocol used
	 *
	 * @return mixed Protocol used during the last transfer, which indicates the protocol that was used for the communication between the client and server during the cURL request, such as HTTP, HTTPS, FTP, etc., and can be used to analyze the behavior of the request based on the protocol used, which may be relevant for understanding issues related to protocol support, performance, or other factors that may impact the communication with the server and require attention for accurate analysis and troubleshooting.
	 */
	public function getProtocol(): mixed
	{
		return $this->getInformation(CURLINFO_PROTOCOL);
	}

	/**
	 * Get scheme used
	 *
	 * @return mixed Scheme used during the last transfer, which indicates the scheme of the URL that was accessed during the cURL request, such as "http", "https", "ftp", etc., and can be used to analyze the behavior of the request based on the scheme used, which may be relevant for understanding issues related to protocol support, performance, or other factors that may impact the communication with the server and require attention for accurate analysis and troubleshooting.
	 */
	public function getScheme(): mixed
	{
		return $this->getInformation(CURLINFO_SCHEME);
	}

	/**
	 * Get app connect time
	 *
	 * @return mixed Time taken to establish the SSL/TLS connection to the server during the last transfer, which indicates the time in seconds that it took to complete the SSL/TLS handshake and establish a secure connection with the server during the cURL request, and can be used to analyze the performance of the secure connection establishment process, which may be affected by factors such as network conditions, server response time, or other issues that may impact the time it takes to establish a secure connection with the server and require attention for optimal performance and troubleshooting in scenarios where SSL/TLS is involved in the communication with the server.
	 */
	public function getAppConnectTime(): mixed
	{
		return $this->getInformation(CURLINFO_APPCONNECT_TIME);
	}

	/**
	 * Get certificate info
	 *
	 * @return mixed Certificate information of the server during the last transfer, which includes details about the SSL/TLS certificate presented by the server during the cURL request, such as the certificate's subject, issuer, validity period, and other relevant information that can be used to analyze the security of the connection and identify potential issues related to SSL/TLS certificates for accurate analysis and troubleshooting in scenarios where secure communication with the server is involved.
	 */
	public function getCertificateInfo(): mixed
	{
		return $this->getInformation(CURLINFO_CERTINFO);
	}

	/**
	 * Get condition unmet
	 *
	 * @return mixed Condition unmet during the last transfer, which indicates any conditions that were not met during the cURL request, such as SSL/TLS certificate verification failures, authentication issues, or other conditions that may have impacted the success of the request and can be used to analyze potential issues for accurate analysis and troubleshooting in scenarios where specific conditions are expected to be met for successful communication with the server.
	 */
	public function getConditionUnmet(): mixed
	{
		return $this->getInformation(CURLINFO_CONDITION_UNMET);
	}

	/**
	 * Get redirect URL
	 *
	 * @return mixed Redirect URL during the last transfer, which indicates the URL that the server redirected to during the cURL request, if any redirects occurred, and can be used to analyze the behavior of the server in handling redirects, which may be relevant for understanding the flow of the request and response, as well as identifying potential issues with excessive redirects or redirect loops that may require attention for accurate analysis and troubleshooting.
	 */
	public function getRedirectURL(): mixed
	{
		return $this->getInformation(CURLINFO_REDIRECT_URL);
	}

	/**
	 * Check if request was successful
	 *
	 * @return bool True if the HTTP status code indicates success (2xx), false otherwise
	 */
	public function isSuccessful(): bool
	{
		$code = $this->getStatusCode();
		return $code >= 200 && $code < 300;
	}

	/**
	 * Check if redirect occurred
	 *
	 * @return bool True if the number of redirects is greater than 0, false otherwise
	 */
	public function hasRedirect(): bool
	{
		return $this->getRedirectCount() > 0;
	}

	/**
	 * Check if client error
	 *
	 * @return bool True if the HTTP status code indicates a client error (4xx), false otherwise
	 */
	public function isClientError(): bool
	{
		$code = $this->getStatusCode();
		return $code >= 400 && $code < 500;
	}

	/**
	 * Check if server error
	 *
	 * @return bool True if the HTTP status code indicates a server error (5xx), false otherwise
	 */
	public function isServerError(): bool
	{
		$code = $this->getStatusCode();
		return $code >= 500;
	}

	/**
	 * Get total time in milliseconds
	 *
	 * @return float Total time taken for the entire transfer in milliseconds, which is calculated by multiplying the total transfer time in seconds by 1000, and can be used to analyze the overall performance of the request in a more granular manner for accurate analysis and troubleshooting.
	 */
	public function getTotalTimeMs(): float
	{
		return $this->getTotalTransferTime() * 1000;
	}

	/**
	 * Get timing breakdown
	 *
	 * @return array{
	 * 	dns_lookup: mixed, 
	 * 	connect: mixed, 
	 * 	app_connect: mixed, 
	 * 	pre_transfer: mixed, 
	 * 	start_transfer: mixed, 
	 * 	redirect: mixed, 
	 * 	total: mixed
	 * }
	 */
	public function getTimingBreakdown(): array
	{
		return [
			'dns_lookup' => $this->getLookupNameTime(),
			'connect' => $this->getConnectionTime(),
			'app_connect' => $this->getAppConnectTime(),
			'pre_transfer' => $this->getPreTransferTime(),
			'start_transfer' => $this->getStartTransferTime(),
			'redirect' => $this->getRedirectTime(),
			'total' => $this->getTotalTransferTime(),
		];
	}

	/**
	 * Get transfer summary
	 *
	 * @return array{
	 * 	url: mixed, 
	 * 	status_code: mixed, 
	 * 	content_type: mixed, 
	 * 	total_time: mixed, 
	 * 	download_size: mixed, 
	 * 	upload_size: mixed, 
	 * 	download_speed: mixed, 
	 * 	upload_speed: mixed, 
	 * 	redirect_count: mixed, 
	 * 	primary_ip: mixed, 
	 * 	primary_port: mixed
	 * }
	 */
	public function getSummary(): array
	{
		return [
			'url' => $this->getEffectiveURL(),
			'status_code' => $this->getStatusCode(),
			'content_type' => $this->getContentType(),
			'total_time' => $this->getTotalTransferTime(),
			'download_size' => $this->getDownloadedSize(),
			'upload_size' => $this->getUploadedSize(),
			'download_speed' => $this->getAverageDownloadSpeed(),
			'upload_speed' => $this->getAverageUploadSpeed(),
			'redirect_count' => $this->getRedirectCount(),
			'primary_ip' => $this->getPrimaryIP(),
			'primary_port' => $this->getPrimaryPort(),
		];
	}

	/**
	 * Get size information
	 *
	 * @return array{
	 * 	header_size: mixed, 
	 * 	request_size:mixed, 
	 * 	download_size:mixed, 
	 * 	upload_size:mixed, 
	 * 	download_content_length:mixed, 
	 * 	upload_content_length:mixed
	 * }
	 */
	public function getSizeInfo(): array
	{
		return [
			'header_size' => $this->getHeaderSize(),
			'request_size' => $this->getRequestSize(),
			'download_size' => $this->getDownloadedSize(),
			'upload_size' => $this->getUploadedSize(),
			'download_content_length' => $this->getDownloadContentLength(),
			'upload_content_length' => $this->getUploadContentLength(),
		];
	}

	/**
	 * Get connection info
	 *
	 * @return array{primary_ip: mixed, primary_port: mixed, local_ip:mixed, local_port:mixed, num_connects:mixed}
	 */
	public function getConnectionInfo(): array
	{
		return [
			'primary_ip' => $this->getPrimaryIP(),
			'primary_port' => $this->getPrimaryPort(),
			'local_ip' => $this->getLocalIP(),
			'local_port' => $this->getLocalPort(),
			'num_connects' => $this->getCreatedConnectionCount(),
		];
	}

	/**
	 * Format size to human readable
	 *
	 * @param int $bytes Number of bytes to format
	 * 
	 * @return string Formatted size in human-readable format, which converts the given number of bytes into a more human-friendly representation using appropriate units such as KB, MB, GB, etc., and can be used to analyze the size of data being transmitted in a more understandable way for accurate analysis and troubleshooting.
	 */
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
	 * Get formatted download size
	 *
	 * @return string
	 */
	public function getFormattedDownloadSize(): string
	{
		return self::formatBytes((int) $this->getDownloadedSize());
	}

	/**
	 * Get formatted upload size
	 *
	 * @return string
	 */
	public function getFormattedUploadSize(): string
	{
		return self::formatBytes((int) $this->getUploadedSize());
	}
}
