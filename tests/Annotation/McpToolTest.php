<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

use Clover\Annotation\McpTool;
use PHPUnit\Framework\TestCase;

final class McpToolTest extends TestCase
{
	public function testDescriptionDefaultsToEmptyString(): void
	{
		$tool = new McpTool('framework.status');

		$this->assertSame('framework.status', $tool->name);
		$this->assertSame('', $tool->description);
	}

	public function testNameAndDescriptionArePreserved(): void
	{
		$tool = new McpTool('framework.routes', 'List framework routes');

		$this->assertSame('framework.routes', $tool->name);
		$this->assertSame('List framework routes', $tool->description);
	}
}
