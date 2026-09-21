<?php

declare(strict_types=1);

namespace Clover\Tests\Abstract;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Abstract\HTTPAdapter;
use Clover\Classes\HTTP\Adaptor\FPM\Server as FpmServer;
use Clover\Classes\HTTP\Adaptor\Swoole\Server as SwooleServer;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AbstractHTTPAdapterTest extends TestCase
{
	public function testNamespacedFpmAdapterInvokesStartCallback(): void
	{
		$server = new FpmServer();
		$startedServer = null;

		$server->onStart(static function (FpmServer $callbackServer) use (&$startedServer): void {
			$startedServer = $callbackServer;
		});

		self::assertSame($server, $startedServer);
	}

	public function testNamespacedFpmAdapterInvokesRequestCallback(): void
	{
		$server = new FpmServer();
		$adapterName = '';

		$server->onRequest(static function (string $callbackAdapterName) use (&$adapterName): void {
			$adapterName = $callbackAdapterName;
		});

		self::assertSame('fpm', $adapterName);
	}

	public function testNamespacedFpmAdapterStartCompletesSynchronously(): void
	{
		$server = new FpmServer();

		self::assertNull($server->start());
	}

	public function testLegacyGlobalServerRemainsAnFpmAdapter(): void
	{
		$server = new \Server();

		self::assertInstanceOf(FpmServer::class, $server);
	}

	public function testSwooleAdapterDeclarationDoesNotRequireTheExtension(): void
	{
		$reflection = new ReflectionClass(SwooleServer::class);

		self::assertTrue($reflection->isSubclassOf(HTTPAdapter::class));
	}
}
