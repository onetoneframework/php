<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

interface ClientURLLastTransferInformation
{
	public function getAllKnownCookies();
	public function getAverageDownloadSpeed();
	public function getAverageUploadSpeed();
	public function getConnectCode();
	public function getConnectionTime();
	public function getContentType();
	public function getCreatedConnectionCount();
	public function getDownloadContentLength();
	public function getDownloadedSize();
	public function getEffectiveURL();
	public function getHeaderOutput();
	public function getHeaderSize();
	public function getLastConnectFailureErrorNumber();
	public function getLastConnectionIPAddress();
	public function getLastConnectionPortNumber();
	public function getLookupNameTime();
	public function getNextRTSPClientCSeq();
	public function getPreTransferTime();
	public function getRecentReceivedCSeq();
	public function getRedirectCount();
	public function getRemoteTime();
	public function getResponseCode();
	public function getStartTransferTime();
	public function getStatusCode();
	public function getTotalTransferTime();
	public function getUploadContentLength();
	public function getUploadedSize();
}
