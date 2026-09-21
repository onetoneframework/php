<?php

declare(strict_types=1);

namespace Clover\Classes\GraphQL;

/**
 * Class GraphQLFieldSelection
 * @package Clover\Classes\GraphQL
 */
final class GraphQLFieldSelection
{
	/**
	 * The constructor is public to allow direct construction, but typically instances of this class will be created by the GraphQL parser when processing a query document.
	 * 
	 * @param string $name The name of the field being selected
	 * @param array<string, mixed> $arguments
	 * @param array<int, GraphQLFieldSelection> $children
	 */
	public function __construct(
		public readonly string $name,
		public readonly array $arguments = [],
		public readonly array $children = []
	) {
	}
}
