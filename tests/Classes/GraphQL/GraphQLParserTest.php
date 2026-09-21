<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\GraphQL;

use Clover\Classes\GraphQL\GraphQLParseException;
use Clover\Classes\GraphQL\GraphQLParser;
use PHPUnit\Framework\TestCase;

final class GraphQLParserTest extends TestCase
{
	public function testParsesSelectionSetWithArguments(): void
	{
		$parser = new GraphQLParser();
		$document = $parser->parse('query { hello(name: "onetone") ping }');

		$this->assertSame('query', $document->operationType);
		$this->assertCount(2, $document->selections);
		$this->assertSame('hello', $document->selections[0]->name);
		$this->assertSame('onetone', $document->selections[0]->arguments['name']);
		$this->assertSame('ping', $document->selections[1]->name);
	}

	public function testThrowsExceptionWhenSelectionSetIsInvalid(): void
	{
		$this->expectException(GraphQLParseException::class);
		(new GraphQLParser())->parse('query hello');
	}
}
