<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\HTTP\Adaptor\Swoole;

use Clover\Abstract\HTTPAdapter;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Swoole\Http\Server as SwooleServer;
use Swoole\Runtime;
use function array_merge;
use function go;

/**
 * Adapts the asynchronous lifecycle provided by Swoole.
 */
class Server extends HTTPAdapter
{
	private const DEFAULT_DISPATCH_MODE = 2;

	protected SwooleServer $server;

	/**
	 * Create and configure the underlying Swoole server.
	 *
	 * @param string $host Listening host.
	 * @param string|null $port Listening port.
	 * @param array<string, mixed> $settings Swoole server settings.
	 */
	public function __construct(string $host, ?string $port = null, array $settings = [])
	{
		$this->server = new SwooleServer($host, $port);
		$this->server->set(array_merge($settings, [
			'open_http2_protocol' => true,
			'dispatch_mode' => self::DEFAULT_DISPATCH_MODE,
		]));
	}

	/**
	 * Register the request callback on the Swoole server.
	 */
	public function onRequest(callable $callback): void
	{
		$this->server->on('request', static function () use ($callback): void {
			go(static function () use ($callback): void {
				ReflectionHandler::callMethodArray($callback);
			});
		});
	}

	/**
	 * Register the start callback on the Swoole server.
	 */
	public function onStart(callable $callback): void
	{
		$this->server->on('start', static function () use ($callback): void {
			go(static function () use ($callback): void {
				ReflectionHandler::callMethodArray($callback);
			});
		});
	}

	/**
	 * Start the configured Swoole server.
	 */
	public function start(): bool
	{
		Runtime::enableCoroutine();

		return $this->server->start();
	}
}
