<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;
use Socket;

/**
 * Socket Handler Interface
 *
 * Defines the contract for socket operations.
 * Provides methods for creating, managing, and communicating through network sockets.
 */
interface SocketHandlerInterface
{
	/**
	 * Create a new socket.
	 *
	 * Domain = [AF_INET, AF_INET6, AF_UNIX]
	 * Type = [SOCK_STREAM, SOCK_DGRAM, SOCK_SEQPACKET, SOCK_RAW, SOCK_RDM]
	 * Protocol = [SOL_TCP, SOL_UDP]
	 *
	 * @param int $domain The socket domain (AF_INET, AF_INET6, AF_UNIX).
	 * @param int $type The socket type (SOCK_STREAM, SOCK_DGRAM, etc.).
	 * @param int $protocol The protocol (SOL_TCP, SOL_UDP).
	 * @return Socket|bool|resource The socket resource or false on failure.
	 */
	public function create(int  $domain = AF_INET, int  $type = SOCK_STREAM, int  $protocol = SOL_TCP);

	/**
	 * Get the peer name (remote address) of a socket.
	 *
	 * @param Socket|resource $socketHandler The socket resource.
	 * @return array{ip_address: string, port: int|null} Array containing address and port information.
	 */
	public function getPeerName(mixed $socketHandler): array;

	/**
	 * Close a socket connection.
	 *
	 * @param Socket|resource $socketHandler The socket resource to close.
	 * @return void
	 */
	public function close($socketHandler): void;

	/**
	 * Monitor sockets for changes.
	 *
	 * @param array $socketArray Array of sockets to monitor.
	 * @param array|null $write Array of sockets to check for writing.
	 * @param array|null $except Array of sockets to check for exceptions.
	 * @param int $timeout Timeout in seconds.
	 * @return int|bool Number of sockets ready or false on failure.
	 */
	public function select(array $socketArray, $write = null, $except = null, $timeout = 10): int|bool;

	/**
	 * Accept an incoming connection on a listening socket.
	 *
	 * @param Socket|resource $socketHandler The listening socket resource.
	 * @return Socket|resource|bool The new socket resource or false on failure.
	 */
	public function acceptConnect(mixed $socketHandler): mixed;

	/**
	 * Start listening for incoming connections.
	 *
	 * @param Socket|resource $socketHandler The socket resource.
	 * @return bool True on success, false on failure.
	 */
	public function listen(mixed $socketHandler): bool;

	/**
	 * Bind a socket to an address and port.
	 *
	 * @param Socket|resource $socketHandler The socket resource.
	 * @param string $address The IP address to bind to.
	 * @param int $port The port number to bind to.
	 * @return bool True on success, false on failure.
	 */
	public function bind(mixed $socketHandler, string $address, int $port): bool;

	/**
	 * Read data from a socket.
	 *
	 * @param Socket|resource $socketHandler The socket resource.
	 * @param int $length The maximum number of bytes to read.
	 * @param int $type The read type (PHP_BINARY_READ, PHP_NORMAL_READ).
	 * @return bool|string The read data or false on failure.
	 */
	public function readPacket(mixed $socketHandler, int $length, int $type = PHP_BINARY_READ): bool|string;

	/**
	 * Write data to a socket.
	 *
	 * @param mixed $socketHandler The socket resource.
	 * @param string $buffer The data to write.
	 * @param int $length The number of bytes to write. -1 for all buffer data.
	 * @return int The number of bytes written or false on failure.
	 */
	public function writeSocket(mixed $socketHandler, string $buffer, int $length = -1): int;

	/**
	 * Connect to a remote socket.
	 *
	 * @param Socket|resource $socketHandler The socket resource.
	 * @param string $address The remote IP address.
	 * @param int $port The remote port number.
	 * @return bool True on success, false on failure.
	 */
	public function connect(mixed $socketHandler, string $address, int $port): bool;

	/**
	 * Get the last error message from socket operations.
	 *
	 * @return string|bool The error message or false if no error.
	 */
	public function getLastErrorMessage(): string|bool;
}
