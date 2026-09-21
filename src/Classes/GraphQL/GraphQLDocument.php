<?php

declare(strict_types=1);

namespace Clover\Classes\GraphQL;

/**
 * Class GraphQLDocument
 * @package Clover\Classes\GraphQL
 */
final class GraphQLDocument
{
	/**
	 * GraphQLDocument constructor.
	 * @param string $operationType
	 * @param array<int, GraphQLFieldSelection> $selections
	 */
	public function __construct(
		public readonly string $operationType,
		public readonly array $selections
	) {
	}
}
