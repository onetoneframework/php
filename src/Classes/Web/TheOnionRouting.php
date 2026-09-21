<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Web;

use Clover\Classes\Web\InternetProtocol as InternetProtocol;
use Clover\Classes\HTTP\Request as RequestHandler;
use function sprintf;

/*
 * Class TheOnionRouting
 *
 * Provides functionality to determine if the current request is coming from a Tor exit node.
 */
class TheOnionRouting
{

	public function __construct()
	{
	}

	/**
	 * Check if the current request is coming from a Tor exit node
	 *
	 * @return bool True if the request is from a Tor exit node, false otherwise
	 */
	public static function isExitNode(): bool
	{
		$ipAddress = RequestHandler::getRemoteIPAddress();
		$serverPort = RequestHandler::getPort();
		$reverseIP = InternetProtocol::toReverseOctet($ipAddress);

		$torExitNodeHostName = sprintf("%s.%s.%s.ip-port.exitlist.torproject.org", $reverseIP, $serverPort, $reverseIP);
		$hostName = InternetProtocol::getByHostname($torExitNodeHostName);

		return $hostName === '127.0.0.2';
	}
}
