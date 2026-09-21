<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

use Clover\Annotation\Controller;
use Clover\Annotation\RestController;
use PHPUnit\Framework\TestCase;

final class ControllerStereotypeTest extends TestCase
{
	public function testControllerDefaultsToNoPrefix(): void
	{
		$controller = new Controller();

		$this->assertSame('', $controller->value);
	}

	public function testRestControllerDefaultsToNoPrefix(): void
	{
		$controller = new RestController();

		$this->assertSame('', $controller->value);
	}

	public function testRestControllerPreservesPrefix(): void
	{
		$controller = new RestController('/api/v2');

		$this->assertSame('/api/v2', $controller->value);
	}
}
