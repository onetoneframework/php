<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Socket;

use Clover\Implement\SocketHandlerInterface;
use function count;

class ServerObject
{
	private $arrayAcceptedSocket      = [];
	private $arrayAcceptedSocketCount = 0;
	private $arrayAcceptedSocketInfo  = [];
	private $arrayClientSocket        = [];
	private $clientBindHandler;
	private $socketHandler;
	private SocketHandlerInterface $socketHandlerClass;

	public function __construct(SocketHandlerInterface $socketHandler)
	{
		$this->socketHandlerClass = $socketHandler;
	}

	public function exceptSocketInArray($arrayClientSocket, $arrayAcceptedSocketInfo, $acceptedSocketInArray)
	{
		if (isset($arrayClientSocket[$acceptedSocketInArray])) {
			unset($arrayClientSocket[$acceptedSocketInArray]);
		}

		if (isset($arrayAcceptedSocketInfo[$acceptedSocketInArray])) {
			unset($arrayAcceptedSocketInfo[$acceptedSocketInArray]);
		}

		if (!$this->getAcceptedClientInArray()) {
			$this->arrayAcceptedSocketInfo = [];
			$this->arrayAcceptedSocket     = [];
		}
	}

	public function getAcceptedClientInArray(): bool
	{
		if (count($this->arrayAcceptedSocketInfo) === 0) {
			return false;
		}

		return true;
	}

	public function hasAcceptedClientInArray(): bool
	{
		if ($this->getAcceptedClientInArray() > 0) {
			return true;
		}

		return false;
	}

	public function selectArrayClient(int $timeout = 10, array|null $write = null, array|null $except = null): void
	{
		$this->arrayAcceptedSocketCount = $this->socketHandlerClass->select($this->arrayAcceptedSocket, $write, $except, $timeout);
	}

	public function setArrayClient(): void
	{
		$this->arrayAcceptedSocket = array_merge([$this->socketHandler], $this->arrayClientSocket);
	}

	public function close(): void
	{
		$this->socketHandlerClass->close($this->socketHandler);
	}

	public function connect($address, $port, $domain = AF_INET, $type = SOCK_STREAM, $protocol = SOL_TCP)
	{
		$this->socketHandler = $this->socketHandlerClass->create($domain, $type, $protocol);

		if (!$this->socketHandler) {
			return false;
		}

		$result = $this->socketHandlerClass->bind($this->socketHandler, $address, $port);
		if (!$result) {
			return false;
		}

		$result = $this->socketHandlerClass->listen($this->socketHandler);
		if (!$result) {
			return false;
		}

		return true;
	}

	public function acceptClient()
	{
		$bind = $this->socketHandlerClass->acceptConnect($this->socketHandler);

		if ($bind) {
			$this->clientBindHandler = $bind;

			return true;
		}

		return false;
	}
}
