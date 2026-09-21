<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Web;

use function gethostbyname;
use function gethostbyaddr;
use function gethostname;

/*
 * Class InternetProtocol
 *
 * Provides methods for validating and manipulating IP addresses, as well as retrieving host information.
 */
class InternetProtocol
{
	/**
	 * Validate if the given string is a valid IPv4 address
	 *
	 * @param string $address The IP address to validate
	 * @return bool True if valid IPv4, false otherwise
	 */
	public static function isVersion4(string $address): bool
	{
		return preg_match('/^((?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d?|0)(?:\.(?:25[0-5]|2[0-4]\d|1\d{2}|[1-9]\d?|0)){3})$/', $address) === 1;
	}

	/**
	 * Validate if the given string is a valid IPv6 address
	 *
	 * @param string $address The IP address to validate
	 * @return bool True if valid IPv6, false otherwise
	 */
	public static function isVersion6(string $address): bool
	{
		return preg_match('/^\[(((?:[0-9A-Fa-f]{1,4}:){7}[0-9A-Fa-f]{1,4})|((?:[0-9A-Fa-f]{1,4}:){1,7}:)|((?:[0-9A-Fa-f]{1,4}:){1,6}:[0-9A-Fa-f]{1,4})|((?:[0-9A-Fa-f]{1,4}:){1,5}(?::[0-9A-Fa-f]{1,4}){1,2})|((?:[0-9A-Fa-f]{1,4}:){1,4}(?::[0-9A-Fa-f]{1,4}){1,3})|((?:[0-9A-Fa-f]{1,4}:){1,3}(?::[0-9A-Fa-f]{1,4}){1,4})|((?:[0-9A-Fa-f]{1,4}:){1,2}(?::[0-9A-Fa-f]{1,4}){1,5})|([0-9A-Fa-f]{1,4}:(?:(?::[0-9A-Fa-f]{1,4}){1,6}))|(:((?::[0-9A-Fa-f]{1,4}){1,7}|:)))|fe80:(?::[0-9A-Fa-f]{0,4}){0,4}%[0-9A-Za-z]{1,}|::(?:ffff(?::0{1,4}){0,1}:){0,1}(?:\d{1,3}\.){3}\d{1,3}|(?:[0-9A-Fa-f]{1,4}:){1,4}:(?:\d{1,3}\.){3}\d{1,3}\]$/', $address) === 1;
	}

	/**
	 * Get the hostname of the current machine
	 *
	 * @return string|bool The hostname of the current machine
	 */
	public static function getHostName(): bool|string
	{
		return gethostname();
	}

	/**
	 * Get the hostname associated with an IP address
	 *
	 * @param string $address The IP address to resolve
	 * @return bool|string The hostname associated with the IP address
	 */
	public static function getHostByAddress(string $address): bool|string
	{
		return gethostbyaddr($address);
	}

	/**
	 * Get the IP address associated with a hostname
	 *
	 * @param string $hostname The hostname to resolve
	 * @return string The IP address associated with the hostname
	 */
	public static function getByHostname(string $hostname): string
	{
		return gethostbyname($hostname);
	}

	/**
	 * Convert an IPv4 address to its reverse octet format
	 *
	 * @param string $ip The IPv4 address to convert
	 * @return string The reverse octet format of the IP address
	 */
	public static function toReverseOctet(string $ip): string
	{
		$ipoc = explode(".", $ip);

		return $ipoc[3] . "." . $ipoc[2] . "." . $ipoc[1] . "." . $ipoc[0];
	}
}
