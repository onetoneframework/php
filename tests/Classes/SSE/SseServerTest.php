<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\SSE;

use Clover\Classes\SSE\SseServer;
use PHPUnit\Framework\TestCase;

final class SseServerTest extends TestCase
{
	public function testEmitWritesOneDataLinePerPayloadLine(): void
	{
		$stream = fopen('php://temp', 'w+');
		$this->assertIsResource($stream);

		$server = new SseServer();
		$server->emit($stream, 'message', "first\nsecond");
		rewind($stream);

		$this->assertSame("event: message\ndata: first\ndata: second\n\n", stream_get_contents($stream));
		fclose($stream);
	}

	public function testEmitIgnoresNonResourceSocket(): void
	{
		$server = new SseServer();

		$server->emit(null, 'message', 'payload');

		$this->assertFalse($server->emitTo('missing-session', 'message', 'payload'));
		$this->assertFalse($server->hasSession('missing-session'));
		$this->assertNull($server->getSocket('missing-session'));
		$this->assertSame([], $server->getSessionIds());
	}
}
