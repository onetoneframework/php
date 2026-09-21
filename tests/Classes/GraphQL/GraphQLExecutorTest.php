<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\GraphQL;

use Clover\Classes\GraphQL\GraphQLExecutor;
use Clover\Classes\GraphQL\GraphQLParseException;
use Clover\Classes\GraphQL\GraphQLParser;
use Clover\Classes\GraphQL\GraphQLSchema;
use PHPUnit\Framework\TestCase;

final class GraphQLExecutorTest extends TestCase
{
	public function testExecutesFieldsWithArguments(): void
	{
		$document = (new GraphQLParser())->parse('query { ping hello(name: "framework") }');
		$schema = new GraphQLSchema([
			'ping' => static fn (): string => 'pong',
			'hello' => static fn (array $args): string => 'Hello, ' . ($args['name'] ?? 'world') . '!',
		]);

		$data = (new GraphQLExecutor())->execute($document, $schema);

		$this->assertSame('pong', $data['ping']);
		$this->assertSame('Hello, framework!', $data['hello']);
	}

	public function testThrowsExceptionForUnknownField(): void
	{
		$this->expectException(GraphQLParseException::class);

		$document = (new GraphQLParser())->parse('query { missingField }');
		$schema = new GraphQLSchema([]);

		(new GraphQLExecutor())->execute($document, $schema);
	}
}
