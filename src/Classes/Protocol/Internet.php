<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Protocol;
use function boolval;

class Internet
{
	/**
	 * Check if the given address is a valid IPv6 protocol address.
	 *
	 * @param string $address
	 * 
	 * @return bool|int
	 */
	public static function isV6Protocol(string $address): bool|int
	{
		return preg_match("/(?:(?:(?:[0-9a-f]{1,4}:){6}|::(?:[0-9a-f]{1,4}:){5}|(?:[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){4}|(?:(?:[0-9a-f]{1,4}:){0,1}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){3}|(?:(?:[0-9a-f]{1,4}:){0,2}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:){2}|(?:(?:[0-9a-f]{1,4}:){0,3}[0-9a-f]{1,4})?::(?:[0-9a-f]{1,4}:)|(?:(?:[0-9a-f]{1,4}:){0,4}[0-9a-f]{1,4})?::)(?:[0-9a-f]{1,4}:[0-9a-f]{1,4}|(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\\.){3}(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])))|(?:(?:[0-9a-f]{1,4}:){0,5}[0-9a-f]{1,4})?::[0-9a-f]{1,4}|(?:(?:[0-9a-f]{1,4}:){0,6}[0-9a-f]{1,4})?::)/", $address, $matches);
	}

	/**
	 * Validate an internet protocol address (IPv4 or IPv6).
	 *
	 * @param string $internetProtocol
	 * @param string $type
	 * 
	 * @return bool
	 */
	public function isValid(string $internetProtocol, string $type = 'ipv4'): bool
	{
		switch (strtolower($type)) {
			case 'ipv4':
				$filter = FILTER_FLAG_IPV4;
				break;
			case 'ipv6':
				$filter = FILTER_FLAG_IPV6;
				break;
			default:
				$filter = null;
				break;
		}

		return boolval(filter_var($internetProtocol, FILTER_VALIDATE_IP, $filter));
	}
}
