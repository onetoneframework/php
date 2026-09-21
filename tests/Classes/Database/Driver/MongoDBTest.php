<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database\Driver;

use Clover\Annotation\Deprecated;
use Clover\Classes\Database\Driver\MongoDataBase;
use Clover\Classes\Database\Driver\MongoDB;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use InvalidArgumentException;
use MongoDB\Driver\Manager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use RuntimeException;

final class MongoDBTest extends TestCase
{
	public function testCanonicalDriverClassIsAvailableWithoutLoadingTheExtension(): void
	{
		$reflection = new ReflectionClass(MongoDB::class);

		$this->assertSame(MongoDB::class, $reflection->getName());
		$this->assertTrue($reflection->getProperty('databaseName')->hasType());
		$this->assertTrue($reflection->getProperty('manager')->hasType());
	}

	public function testLegacyDriverNameRemainsCompatibleAndDeprecated(): void
	{
		$attributes = (new ReflectionClass(MongoDataBase::class))->getAttributes(Deprecated::class);

		$this->assertTrue(is_subclass_of(MongoDataBase::class, MongoDB::class));
		$this->assertCount(1, $attributes);
		$this->assertSame('Use MongoDB instead.', $attributes[0]->newInstance()->message);
	}

	public function testPublicDriverMethodsHaveExplicitTypes(): void
	{
		foreach (['__construct', 'executeQuery', 'bulkInsert'] as $methodName) {
			$method = new ReflectionMethod(MongoDB::class, $methodName);

			foreach ($method->getParameters() as $parameter) {
				$this->assertTrue($parameter->hasType(), $methodName . ' parameters must be typed.');
			}

			if ($methodName !== '__construct') {
				$this->assertTrue($method->hasReturnType(), $methodName . ' must declare a return type.');
			}
		}
	}

	public function testLegacyDriverPreservesNamedArgumentParameters(): void
	{
		$queryParameters = (new ReflectionMethod(MongoDataBase::class, 'executeQuery'))->getParameters();
		$insertParameters = (new ReflectionMethod(MongoDataBase::class, 'bulkInsert'))->getParameters();

		$this->assertSame(
			['collection_name', 'query', 'option'],
			array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $queryParameters)
		);
		$this->assertSame(
			['collection_name', 'data'],
			array_map(static fn (ReflectionParameter $parameter): string => $parameter->getName(), $insertParameters)
		);
	}

	public function testEmptyConnectionIsRejectedBeforeTheNativeDriverIsNeeded(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('MongoDB connection must not be empty.');

		new MongoDB('', 'application');
	}

	public function testEmptyDatabaseNameIsRejectedBeforeTheNativeDriverIsNeeded(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('MongoDB database name must not be empty.');

		new MongoDB('localhost', '');
	}

	public function testMissingNativeDriverProducesATypedFailure(): void
	{
		if (ReflectionHandler::isClassExists(Manager::class)) {
			$this->markTestSkipped('The MongoDB PHP extension is installed.');
		}

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('The MongoDB PHP extension is required.');

		new MongoDB('localhost', 'application');
	}
}
