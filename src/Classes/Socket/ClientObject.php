<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use Clover\Classes\Socket\Handler as SocketHandler;

use Socket;

class ClientObject
{
	private SocketHandler $socketHandlerClass;
	/**
	 * @var resource|Socket
	 */
	private mixed $socketHandler;

	public function __construct($socketHandler)
	{
		if ($socketHandler instanceof SocketHandler) {
			$this->socketHandlerClass = $socketHandler;
		}
	}

	/**
	 * Send packet to socket of server
	 * 
	 * @param string $string
	 * 
	 * @return bool
	 */
	public function sendPacket(string $string = ''): bool
	{
		$result = $this->socketHandlerClass->writeSocket($this->socketHandler, $string, strlen($string));

		if ($result === 0) {
			return false;
		}

		return true;
	}

	/**
	 * Close socket
	 * 
	 * @return void
	 */
	public function close(): void
	{
		$this->socketHandlerClass->close($this->socketHandler);
	}

	/**
	 * Connect socket
	 * 
	 * @param string $address
	 * @param int $port
	 * @param int $domain
	 * @param int $type
	 * @param int $protocol
	 * 
	 * @return bool
	 */
	public function connect(string $address, int $port, int $domain = AF_INET, int $type = SOCK_STREAM, int $protocol = SOL_TCP): bool
	{
		$this->socketHandler = $this->socketHandlerClass->create($domain, $type, $protocol);

		if (!$this->socketHandler) {
			return false;
		}

		$result = $this->socketHandlerClass->connect($this->socketHandler, $address, $port);

		if (!$result) {
			return false;
		}

		return true;
	}

	/**
	 * Connect TCP socket
	 * 
	 * @param string $address
	 * @param int $port
	 * @param int $domain
	 * 
	 * @return bool
	 */
	public function connectTCP(string $address, int $port, int $domain = AF_INET): bool
	{
		return $this->connect($address, $port, $domain, SOCK_STREAM, SOL_TCP);
	}

	/**
	 * Connect UDP socket
	 * 
	 * @param string $address
	 * @param int $port
	 * @param int $domain
	 * 
	 * @return bool
	 */
	public function connectUDP(string $address, int $port, int $domain = AF_INET): bool
	{
		return $this->connect($address, $port, $domain, SOCK_DGRAM, SOL_UDP);
	}
}
