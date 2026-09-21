<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\StandardPHPLibrary;

use Clover\Classes\StandardPHPLibrary\Autoload;
use PHPUnit\Framework\TestCase;

final class AutoloadTest extends TestCase
{
	public function testRegisterCallAndUnregisterAutoloadFunction(): void
	{
		$autoload = new Autoload();
		$requested = [];
		$callback = static function (string $class) use (&$requested): void {
			$requested[] = $class;
		};

		$autoload->registFunction($callback);

		try {
			$this->assertContains($callback, $autoload->getFunctions());
			$autoload->callAutoloadFunction('Clover\\Tests\\Missing\\AutoloadProbe');
			$this->assertContains('Clover\\Tests\\Missing\\AutoloadProbe', $requested);
		} finally {
			$autoload->unregistFunction($callback);
		}

		$this->assertNotContains($callback, $autoload->getFunctions());
	}

	public function testDefaultExtensionsCanBeChangedAndRestored(): void
	{
		$autoload = new Autoload();
		$original = $autoload->setDefaultExtensions();

		try {
			$this->assertSame('.php,.inc', $autoload->setDefaultExtensions('.php,.inc'));
			$this->assertSame('.php,.inc', $autoload->setDefaultExtensions());
		} finally {
			$autoload->setDefaultExtensions($original);
		}

		$this->assertSame($original, $autoload->setDefaultExtensions());
	}
}
