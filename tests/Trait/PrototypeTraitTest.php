<?php

declare(strict_types=1);

namespace Clover\Tests\Trait;

use BadMethodCallException;
use Clover\Trait\PrototypeTrait;
use PHPUnit\Framework\TestCase;

final class PrototypeTraitTest extends TestCase
{
	public function testDefinedMethodReceivesArgumentsAndBindsToObject(): void
	{
		PrototypeTraitFixture::defineMethod('describe', function (string $suffix): string {
			return $this->name . ':' . $suffix;
		});

		$fixture = new PrototypeTraitFixture('alpha');

		$this->assertSame('alpha:ready', $fixture->describe('ready'));
	}

	public function testRedefiningMethodReplacesPreviousClosure(): void
	{
		PrototypeTraitFixture::defineMethod('label', fn(): string => 'first');
		PrototypeTraitFixture::defineMethod('label', fn(): string => 'second');

		$this->assertSame('second', (new PrototypeTraitFixture('unused'))->label());
	}

	public function testUndefinedMethodThrowsBadMethodCallException(): void
	{
		$this->expectException(BadMethodCallException::class);
		$this->expectExceptionMessage("Method 'missing' not found.");

		(new PrototypeTraitFixture('alpha'))->missing();
	}
}

final class PrototypeTraitFixture
{
	use PrototypeTrait;

	public function __construct(public string $name)
	{
	}
}
