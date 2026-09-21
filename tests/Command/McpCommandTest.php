<?php

declare(strict_types=1);

namespace Clover\Tests\Command;

use Command\McpCommand;
use PHPUnit\Framework\TestCase;

final class McpCommandTest extends TestCase
{
	public function testMetadataDescribesTheConfiguredSseEndpoint(): void
	{
		$command = new McpCommand();

		$this->assertSame('boost:mcp', $command->getName());
		$this->assertSame(
			'Start MCP Server using SSE on tcp://127.0.0.1:8002',
			$command->getDescription()
		);
	}

	public function testPublicToolsReturnDeterministicStatusInformation(): void
	{
		$command = new McpCommand();

		$this->assertSame('PHP Version: ' . PHP_VERSION, $command->toolGetPhpVersion());
		$this->assertSame(
			'Onetone Framework is running correctly via SSE.',
			$command->toolFrameworkStatus()
		);
	}
}
