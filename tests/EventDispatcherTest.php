<?php

declare(strict_types=1);

namespace Clover\Tests;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\File\Handler as FileHandler;

class EventDispatcherTest extends TestCase
{

	protected $factory;

	public function setUp(): void
	{
		$this->factory = new FileHandler();

		$this->factory->write(__DIR__."/testFile.txt", "testSuccess");
	}

	protected function tearDown(): void
    {
		$this->factory->delete(__DIR__."/testFile.txt");
	}

	public function testFileCount()
	{
		$this->assertSame("testSuccess", $this->factory->readAllContent(__DIR__ . "/testFile.txt")->getRawData());
	}
}
