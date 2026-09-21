<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

/**
 * Client URL Option Interface
 *
 * Defines the contract for configuring URL client options.
 * Provides methods for setting various cURL options and request parameters.
 */
interface ClientURLOptionInterface
{
	public function clearOptions();
	public function setAcceptEncoding($encoding = '');
	public function setAcceptJson();
	public function setAcceptXml();
	public function setAutoReferer(bool $bool = true);
	public function setBinaryTransfer(bool $bool = true);
	public function setBodyEmpty(bool $bool = true);
	public function setConnectionTimeout(bool $timeout = true, bool $useMilliseconds = false);
	public function setConnectionTimeoutMilliseconds(bool $timeout = true);
	public function setContentType(string $applicationType);
	public function setCookieHeader($cookieData = '');
	public function setDisableCache(bool $bool);
	public function setDnsUseGlobalCache(bool $bool = true);
	public function setFileHandler($filePointer);
	public function setFollowLocationHeader(int $size = 0);
	public function setForbidenReuse(bool $bool = true);
	public function setFTPUseEPSV(int $size = 0);
	public function setGetMethod(bool $bool = true);
	public function setHeader(string $key, string $value, bool $overwrite = false);
	public function setHeaders(array $headers = []);
	public function setMaximumConnectionCount(bool $maximumConnection = true);
	public function setMaximumDownloadSpeed(int $bytePerSeconds = 1000);
	public function setMaximumReceiveSpeed(int $bytePerSeconds = 1000);
	public function setMaximumSendSpeed(int $bytePerSeconds = 1000);
	public function setMaximumUploadSpeed(int $bytePerSeconds = 1000);
	public function setNobody(bool $bool = true);
	public function setOption($key, $value);
	public function setPort(int $port);
	public function setPostField(string|array $fields);
	public function setPostFieldSize(int $size = 0);
	public function setPostMethod(bool $bool = true);
	public function setProxyTunnel(bool $bool = true);
	public function setReturnHeader(bool $hasResponse = true);
	public function setReturnTransfer(bool $hasResponse = true);
	public function setSSLVerifyPeer(bool $bool = true);
	public function setTimeout(bool $timeout = true);
	public function setUploadReady(bool $bool = true);
	public function setURL(string $url);
	public function setUserAgent($userAgent = '');
	public function useCookieSession(bool $bool = true);
}
