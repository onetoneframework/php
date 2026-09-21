<?php

declare(strict_types=1);

namespace Clover\Classes\GraphQL;

final class GraphQLSchema
{
	/**
	 * The constructor takes two arrays of field resolvers: one for query fields and one for mutation fields. Each resolver is a callable that accepts the parent object, arguments, and field selection, and returns the resolved value for that field.
	 * 
	 * @param array<string, callable(array<string, mixed>, array<string, mixed>, GraphQLFieldSelection): mixed> $queryFields
	 * @param array<string, callable(array<string, mixed>, array<string, mixed>, GraphQLFieldSelection): mixed> $mutationFields
	 */
	public function __construct(
		private readonly array $queryFields,
		private readonly array $mutationFields = []
	) {
	}

	/**
	 * Retrieves the resolver function for a given operation type and field name. It checks the appropriate array of resolvers based on whether the operation is a query or a mutation, and returns the resolver if found, or null if no resolver exists for that field.
	 * 
	 * @param string $operationType The type of operation ('query' or 'mutation')
	 * @param string $fieldName The name of the field to retrieve the resolver for
	 * @return callable|null The resolver function for the specified field, or null if not found
	 */
	public function getResolver(string $operationType, string $fieldName): ?callable
	{
		$fields = $operationType === 'mutation' ? $this->mutationFields : $this->queryFields;
		return $fields[$fieldName] ?? null;
	}
}
