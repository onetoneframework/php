<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\HTTP\Adaptor\FPM {

	use Clover\Abstract\HTTPAdapter;
	use Clover\Classes\Reflection\Handler as ReflectionHandler;

	/**
	 * Adapts the request lifecycle provided by PHP-FPM.
	 */
	class Server extends HTTPAdapter
	{
		/**
		 * Create an FPM server adapter.
		 */
		public function __construct()
		{
		}

		/**
		 * Invoke the request callback for the current FPM request.
		 */
		public function onRequest(callable $callback): void
		{
			ReflectionHandler::callMethodArray($callback, ['fpm']);
		}

		/**
		 * Invoke the server start callback.
		 */
		public function onStart(callable $callback): void
		{
			ReflectionHandler::callMethodArray($callback, [$this]);
		}

		/**
		 * Complete the synchronous FPM server lifecycle.
		 */
		public function start(): void
		{
			return;
		}
	}
}

namespace {

	/**
	 * Provides the legacy global name for the FPM server adapter.
	 *
	 * @deprecated Use Clover\Classes\HTTP\Adaptor\FPM\Server.
	 */
	class Server extends \Clover\Classes\HTTP\Adaptor\FPM\Server
	{
	}
}
