<?php

declare(strict_types=1);

namespace Clover\Classes\GraphQL;

use function array_key_exists;
use function is_array;
use function is_object;

/**
 * Class GraphQLExecutor
 * @package Clover\Classes\GraphQL
 */
final class GraphQLExecutor
{
	/**
	 * Execute a GraphQL document against a schema with an optional context.
	 * 
	 * @param array<string, mixed> $context
	 * @param GraphQLDocument $document The GraphQL document to execute
	 * @param GraphQLSchema $schema The GraphQL schema to execute against
	 * @return array<string, mixed>
	 */
	public function execute(GraphQLDocument $document, GraphQLSchema $schema, array $context = []): array
	{
		$data = [];
		foreach ($document->selections as $selection) {
			$resolver = $schema->getResolver($document->operationType, $selection->name);
			if ($resolver === null) {
				throw new GraphQLParseException('Unknown field `' . $selection->name . '`.');
			}

			$value = $resolver($selection->arguments, $context, $selection);
			$data[$selection->name] = $this->resolveChildren($value, $selection);
		}

		return $data;
	}

	/**
	 * Recursively resolve child selections for a given value.
	 * 
	 * @param mixed $value The value to resolve children from
	 * @param GraphQLFieldSelection $selection The field selection containing child selections
	 * @return mixed The resolved value with children
	 */
	private function resolveChildren(mixed $value, GraphQLFieldSelection $selection): mixed
	{
		if ($selection->children === []) {
			return $value;
		}

		$result = [];
		foreach ($selection->children as $child) {
			$result[$child->name] = $this->extractChildValue($value, $child->name);
		}

		return $result;
	}

	/**
	 * Extract the value of a child field from a parent value.
	 * 
	 * @param mixed $value The parent value to extract from
	 * @param string $childName The name of the child field to extract
	 * @return mixed The extracted child value, or null if not found
	 */
	private function extractChildValue(mixed $value, string $childName): mixed
	{
		if (is_array($value) && array_key_exists($childName, $value)) {
			return $value[$childName];
		}
		if (is_object($value) && isset($value->{$childName})) {
			return $value->{$childName};
		}

		return null;
	}
}
