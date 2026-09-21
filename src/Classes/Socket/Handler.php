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
use function strlen;

class Handler implements SocketHandlerInterface
{
	public function create(int $domain = AF_INET, int $type = SOCK_STREAM, int $protocol = SOL_TCP): mixed
	{
		return socket_create($domain, $type, $protocol);
	}

	public function getPeerName(mixed $socketHandler): array
	{
		$hasPeerInfo = socket_getpeername($socketHandler, $address, $port);

		if ($hasPeerInfo) {
			return [
				'ip_address' => $address,
				'port' => $port
			];
		}

		return [];
	}

	public function close(mixed $socketHandler): void
	{
		socket_close($socketHandler);
	}

	public function select(array $socketArray, $write = null, $except = null, $timeout = 10): bool|int
	{
		return socket_select($socketArray, $write, $except, $timeout);
	}

	public function acceptConnect(mixed $socketHandler): mixed
	{
		return socket_accept($socketHandler);
	}

	public function listen(mixed $socketHandler): bool
	{
		return socket_listen($socketHandler);
	}

	public function bind(mixed $socketHandler, string $address, int $port): bool
	{
		return socket_bind($socketHandler, $address, $port);
	}

	public function readPacket(mixed $socketHandler, int $length, int $type = PHP_BINARY_READ): bool|string
	{
		return socket_read($socketHandler, $length, $type);
	}

	public function writeSocket(mixed $socketHandler, string $buffer, int $length = -1): int
	{
		if ($length === -1) {
			$length = strlen($buffer);
		}

		return socket_write($socketHandler, $buffer, $length);
	}

	public function connect(mixed $socketHandler, string $address, int $port): bool
	{
		return socket_connect($socketHandler, $address, $port);
	}

	public function getErrorMessage(int $message = 0): string
	{
		return socket_strerror($message);
	}

	public function getLastErrorMessage(): string
	{
		return $this->getErrorMessage(socket_last_error());
	}
}
