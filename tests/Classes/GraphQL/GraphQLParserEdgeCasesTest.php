<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\GraphQL;

use Clover\Classes\GraphQL\GraphQLFieldSelection;
use Clover\Classes\GraphQL\GraphQLParseException;
use Clover\Classes\GraphQL\GraphQLParser;
use Clover\Classes\GraphQL\GraphQLSchema;
use PHPUnit\Framework\TestCase;

final class GraphQLParserEdgeCasesTest extends TestCase
{
	public function testParsesShorthandNestedSelectionsCommentsAndScalarArgumentTypes(): void
	{
		$query = <<<'GRAPHQL'
# leading comment
{
	user(id: 7, active: true, ratio: 1.5, missing: null, status: READY) {
		id
		name
	}
}
GRAPHQL;
		$document = (new GraphQLParser())->parse($query);

		$this->assertSame('query', $document->operationType);
		$this->assertCount(1, $document->selections);
		$user = $document->selections[0];
		$this->assertSame('user', $user->name);
		$this->assertSame([
			'id' => 7,
			'active' => true,
			'ratio' => 1.5,
			'missing' => null,
			'status' => 'READY',
		], $user->arguments);
		$this->assertSame(['id', 'name'], array_map(static fn($field) => $field->name, $user->children));
	}

	public function testParsesMutationOperation(): void
	{
		$document = (new GraphQLParser())->parse('mutation { update(id: 1) { id } }');

		$this->assertSame('mutation', $document->operationType);
		$this->assertSame('update', $document->selections[0]->name);
		$this->assertSame(1, $document->selections[0]->arguments['id']);
		$this->assertSame('id', $document->selections[0]->children[0]->name);
	}

	public function testUnterminatedStringRaisesParseException(): void
	{
		$this->expectException(GraphQLParseException::class);
		$this->expectExceptionMessage('Unterminated string.');

		(new GraphQLParser())->parse('{ hello(name: "unterminated) }');
	}

	public function testSchemaSelectsResolversByOperationType(): void
	{
		$queryResolver = static fn(array $parent, array $arguments, GraphQLFieldSelection $selection): string => 'query';
		$mutationResolver = static fn(array $parent, array $arguments, GraphQLFieldSelection $selection): string => 'mutation';
		$schema = new GraphQLSchema(['item' => $queryResolver], ['item' => $mutationResolver]);

		$this->assertSame($queryResolver, $schema->getResolver('query', 'item'));
		$this->assertSame($mutationResolver, $schema->getResolver('mutation', 'item'));
		$this->assertNull($schema->getResolver('query', 'missing'));
	}
}
