<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\Driver;

use Clover\Annotation\Deprecated;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use InvalidArgumentException;
use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;
use MongoDB\Driver\WriteResult;
use RuntimeException;

/**
 * Executes MongoDB queries and bulk inserts through the native driver.
 */
class MongoDB
{
	private const CONNECTION_SCHEME = 'mongodb://';

	private string $databaseName;

	private Manager $manager;

	/**
	 * Creates a MongoDB driver instance.
	 *
	 * @param string $connection MongoDB host and connection options.
	 * @param string $database Database name used to qualify collections.
	 */
	public function __construct(string $connection, string $database)
	{
		if ($connection === '') {
			throw new InvalidArgumentException('MongoDB connection must not be empty.');
		}

		if ($database === '') {
			throw new InvalidArgumentException('MongoDB database name must not be empty.');
		}

		if (!ReflectionHandler::isClassExists(Manager::class)) {
			throw new RuntimeException('The MongoDB PHP extension is required.');
		}

		$this->databaseName = $database;
		$this->manager = new Manager(self::CONNECTION_SCHEME . $connection);
	}

	/**
	 * Executes a query against a collection.
	 *
	 * @param string $collectionName Collection name within the configured database.
	 * @param array<string, mixed>|object $filter Query filter document.
	 * @param array<string, mixed> $options Native query options.
	 */
	public function executeQuery(string $collectionName, array|object $filter, array $options = []): Cursor
	{
		if ($collectionName === '') {
			throw new InvalidArgumentException('MongoDB collection name must not be empty.');
		}

		$query = new Query($filter, $options);

		return $this->manager->executeQuery($this->databaseName . '.' . $collectionName, $query);
	}

	/**
	 * Inserts one document through a bulk write operation.
	 *
	 * @param string $collectionName Collection name within the configured database.
	 * @param array<string, mixed>|object $document Document to insert.
	 */
	public function bulkInsert(string $collectionName, array|object $document): WriteResult
	{
		if ($collectionName === '') {
			throw new InvalidArgumentException('MongoDB collection name must not be empty.');
		}

		$bulkWrite = new BulkWrite();
		$bulkWrite->insert($document);

		return $this->manager->executeBulkWrite($this->databaseName . '.' . $collectionName, $bulkWrite);
	}
}

/**
 * Preserves the legacy MongoDB driver class name.
 */
#[Deprecated('Use MongoDB instead.')]
class MongoDataBase extends MongoDB
{
	/**
	 * Preserves legacy named arguments for query execution.
	 *
	 * @param array<string, mixed>|object $query
	 * @param array<string, mixed> $option
	 */
	public function executeQuery(string $collection_name, array|object $query, array $option = []): Cursor
	{
		return parent::executeQuery($collection_name, $query, $option);
	}

	/**
	 * Preserves legacy named arguments for bulk inserts.
	 *
	 * @param array<string, mixed>|object $data
	 */
	public function bulkInsert(string $collection_name, array|object $data): WriteResult
	{
		return parent::bulkInsert($collection_name, $data);
	}
}
