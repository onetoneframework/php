<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Database\Driver;

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\StringObject;
use Clover\Classes\Database\Driver\ExtendedPdo as ExtendedPdo;
use Clover\Classes\Debug\Profiler;
use Clover\Classes\Debug\ProfilerSqlPreview;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Framework\Component\Translator;
use Clover\Framework\Event\DatabaseLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use PDO;
use Exception;
use PDOException;
use PDOStatement;
use RuntimeException;
use InvalidArgumentException;
use function sprintf;
use function count;
use function is_array;
use function is_string;
use function uniqid;

/**
 * PHPDataObject Class
 * 
 * This class provides an extended interface for interacting with databases using PDO. It includes methods for executing queries, managing database schemas, and handling errors with localized messages.
 * @package Clover\Classes\Database\Driver
 */
class PHPDataObject extends ExtendedPdo
{
	/** @var StringObject|string $driver The database driver to use (default is "mysql"). */
	private StringObject|string $driver = "mysql";

	/** @var array|null $options Optional PDO connection options. */
	private ?array $options = null;

	/** @var int|string $port The port number for the database connection (default is "3306"). */
	private null|int|string $port = "3306";

	/** @var string $username The username of the database server. */
	private string $username;

	/** @var string $password The password for the database connection. */
	private string $password;

	/** @var string $hostname The hostname of the database server. */
	private string $hostname;

	/** @var string $database The name of the database to connect to. */
	private string $database;
	private EventDispatcherAdapter $eventDispatcher;

	public function __construct()
	{
		$this->eventDispatcher = EventDispatcherAdapter::fromEventManager();
	}

	public function setEventDispatcher(EventDispatcherAdapter $eventDispatcher): static
	{
		$this->eventDispatcher = $eventDispatcher;

		return $this;
	}

	/**
	 * Set the port number for the database connection.
	 *
	 * @param string $port The port number to use for the database connection (default is "3306").
	 * @return void
	 */
	public function setPort(null|string $port)
	{
		$this->port = $port;
	}

	/**
	 * Set the hostname for the database connection.
	 *
	 * @param string $hostname The hostname of the database server.
	 * @return void
	 */
	public function setHostName(string $hostname)
	{
		$this->hostname = $hostname;
	}

	/**
	 * Set the name of the database to connect to.
	 *
	 * @param string $database The name of the database.
	 * @return void
	 */
	public function setDatabase(string $database)
	{
		$this->database = $database;
	}

	/**
	 * Get the name of the database to connect to.
	 *
	 * @return string The name of the database.
	 */
	public function getDatabase()
	{
		return $this->database;
	}

	/**
	 * Set the username for the database connection.
	 * @param string $username The username to use for the database connection.
	 * @return void
	 */
	public function setUsername(string $username)
	{
		$this->username = $username;
	}

	/**
	 *  Set the password for the database connection.
	 * @param string $password The password to use for the database connection.
	 * @return void
	 */
	public function setPassword(string $password)
	{
		$this->password = $password;
	}

	/** 
	 * Set the database driver for the connection.
	 *
	 * This method validates the provided driver against the available PDO drivers and sets it for the connection. If the driver is not supported, an exception is thrown.
	 *
	 * @param string|null|StringObject $driver The name of the database driver to use (e.g., "mysql", "pgsql"). If null, it will default to "mysql".
	 * @throws Exception If the provided driver is not supported by PDO.
	 */
	public function setDriver(string|null|StringObject $driver = null)
	{
		$available = PHPDataObject::getAvailableDrivers();

		$driver = $driver instanceof StringObject ? $driver : new StringObject($driver);

		if (!$driver->inArray($available)) {
			throw new Exception('Driver is not supported');
		}

		$this->driver = $driver;
	}

	/** 
	 * Create a new PDO connection using the specified driver, hostname, port, database, username, and password.
	 *
	 * This method constructs the Data Source Name (DSN) based on the provided connection parameters and attempts to establish a connection to the database. If the connection fails, an exception is thrown with a localized error message.
	 *
	 * @throws Exception If the connection to the database fails, an exception is thrown with a localized error message.
	 */
	public function createConnection()
	{
		try {
			$dsnParts = isset($this->database)
				? [
					'host=' . $this->hostname,
					'port=' . $this->port,
					'dbname=' . $this->database,
				]
				: [
					'host=' . $this->hostname,
				];

			$dsn = sprintf('%s:%s', $this->driver, implode(';', $dsnParts));

			parent::__construct($dsn, $this->username, $this->password, $this->options);
		} catch (Exception $e) {
			throw new Exception($this->getLocalizedErrorMessage($e));
		}
	}

	/** 
	 * Get the name of the database driver in use.
	 *
	 * This method retrieves the name of the database driver currently being used for the PDO connection. It returns a string representing the driver name (e.g., "mysql", "pgsql").
	 *
	 * @return string The name of the database driver in use.
	 */
	public function getDriverName()
	{
		return $this->getAttribute(PDO::ATTR_DRIVER_NAME);
	}

	/** 
	 * Get the connection timeout value in seconds.
	 *
	 * This method retrieves the current connection timeout setting for the PDO connection. It returns the timeout value in seconds, which indicates how long the connection will wait before timing out when attempting to connect to the database server.
	 *
	 * @return int The connection timeout value in seconds.
	 */
	public function getTimeout()
	{
		return $this->getAttribute(PDO::ATTR_TIMEOUT);
	}

	/** 
	 * Execute a SQL query with optional parameters and return the resulting PDOStatement.
	 *
	 * This method prepares the provided SQL query, executes it with the given parameters, and returns the resulting PDOStatement if successful. If the execution fails, an exception is thrown with the error information.
	 *
	 * @param string $sql The SQL query to execute.
	 * @param array $params An optional array of parameters to bind to the query (default is an empty array).
	 * @return bool|PDOStatement Returns the PDOStatement on successful execution, or false on failure.
	 * @throws Exception If the query execution fails, an exception is thrown with the error information.
	 */
	public function executeQuery(string $sql, array $params = []): bool|PDOStatement
	{
		$sqlPreview = ProfilerSqlPreview::forTimeline($sql);
		$this->eventDispatcher->dispatch(new DatabaseLifecycleEvent(
			DatabaseLifecycleEvent::QUERY_STARTED,
			['sql' => $sqlPreview, 'paramCount' => count($params)]
		));
		$profilerEnabled = Profiler::isEnabled();
		$span = '';
		if ($profilerEnabled) {
			$span = uniqid('pf_', true);
			$this->eventDispatcher->dispatch(new KernelSpanStarted(
				$span,
				'Kernel::DatabaseQuery',
				$sqlPreview
			));
		}
		try {
			$stmt = $this->prepare($sql);
			$execute = $stmt->execute($params);
		} finally {
			if ($profilerEnabled) {
				$this->eventDispatcher->dispatch(new KernelSpanFinished($span));
			}
		}

		if ($execute) {
			$this->eventDispatcher->dispatch(new DatabaseLifecycleEvent(
				DatabaseLifecycleEvent::QUERY_FINISHED,
				['sql' => $sqlPreview, 'paramCount' => count($params)]
			));
		} else {
			$this->eventDispatcher->dispatch(new DatabaseLifecycleEvent(
				DatabaseLifecycleEvent::QUERY_FAILED,
				['sql' => $sqlPreview]
			));
		}

		if ($execute) {
			return $stmt;
		}

		throw new Exception("Query execution failed: " . implode(", ", $stmt->errorInfo()));
	}

	/**
	 * This method retrieves the count of records from a specified table based on a given condition. It constructs a SQL query using the provided table name and WHERE clause, prepares and executes the query, and then fetches and returns the count result.
	 *
	 * @param string $table The name of the table to query.
	 * @param string $where The condition to filter the results (e.g., "id = 1").
	 * @return mixed The count of records that match the specified condition.
	 */
	public function getCount(string $table, string $where): mixed
	{
		$sql = "SELECT COUNT(*) FROM `$table` WHERE $where";

		$stmt = $this->prepare($sql);
		$stmt->execute();
		$fetch = $stmt->fetchColumn();

		return $fetch;
	}

	/**
	 * This method retrieves a single column value from a specified table based on a given condition. It constructs a SQL query using the provided table name, column name, and WHERE clause. The method prepares and executes the query, then fetches and returns the result.
	 *
	 * @param string $table The name of the table to query.
	 * @param string $name The name of the column to retrieve.
	 * @param string $where The condition to filter the results (e.g., "id = 1").
	 * @return mixed The value of the specified column based on the given condition.
	 */
	public function getColumn(string $table, string $name, string $where): mixed
	{
		$sql = "SELECT $name FROM `$table` WHERE $where";

		$stmt = $this->prepare($sql);
		$stmt->execute();
		$fetch = $stmt->fetchColumn();

		return $fetch;
	}

	/**
	 * This method retrieves multiple columns from a specified table based on a given condition. It constructs a SQL query using the provided table name, column name(s), and an optional WHERE clause. The method prepares and executes the query, then fetches and returns the results as an associative array.
	 *
	 * @param string $table The name of the table to query.
	 * @param string $name The column name(s) to retrieve (can be a single column or multiple columns separated by commas).
	 * @param string|null $where An optional WHERE clause to filter the results (default is null, which means no filtering).
	 * @return array An associative array containing the fetched columns based on the specified conditions.
	 */
	public function getColumns(string $table, string $name, string|null $where = null): array
	{
		$sql = "SELECT $name FROM `$table`" . ($where ? "WHERE $where" : "");

		$stmt = $this->prepare($sql);
		$stmt->execute();
		$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);

		return $fetch;
	}

	/**
	 * This method retrieves the primary key columns of a specified table in a given database. It calls the getSpecifyColumns method with the constraint name set to 'PRIMARY' to fetch the primary key columns.
	 *
	 * @param string $database The name of the database containing the table.
	 * @param string $table The name of the table for which to retrieve primary key columns.
	 * @return array An array of primary key column names for the specified table.
	 */
	public function getPrimaryColumns(string $database, string $table): array
	{
		return $this->getSpecifyColumns($database, $table);
	}

	/**
	 * This method retrieves all table names from the specified database. It executes a SQL query to fetch the table names from the information_schema.tables and returns them as an array.
	 *
	 * @return array An array of table names from the specified database.
	 */
	public function getAllTables(): array
	{
		$query = <<<EOD
		SELECT table_name
		FROM information_schema.tables
		WHERE table_schema = '{$this->database}';
EOD;

		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchAll();

		return $result;
	}

	/**
	 * This method retrieves the column names of a specified constraint (default is 'PRIMARY') for a given table in a specified database. It executes a SQL query to fetch the column names from the INFORMATION_SCHEMA.KEY_COLUMN_USAGE table and returns them as an array.
	 *
	 * @param string $database The name of the database containing the table.
	 * @param string $table The name of the table for which to retrieve the column names.
	 * @param string $constraintName The name of the constraint to retrieve columns for (default is 'PRIMARY').
	 * @return array An array of column names associated with the specified constraint.
	 */
	public function getSpecifyColumns(string $database, string $table, string $constraintName = 'PRIMARY'): array
	{
		$query = <<<EOD
		SELECT 
			COLUMN_NAME
		FROM 
			INFORMATION_SCHEMA.KEY_COLUMN_USAGE
		WHERE 
			TABLE_SCHEMA = '{$database}' AND 
			TABLE_NAME = '{$table}' AND 
			CONSTRAINT_NAME = '{$constraintName}';
		EOD;

		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchAll();

		return array_map(function ($array) {
			return $array['COLUMN_NAME'];
		}, $result);
	}

	/**
	 * This method revokes all privileges on a specified database from a user with a given name and host. It constructs a SQL query using the provided parameters and executes it using the exec() method of the PDO class.
	 *
	 * @param string $database The name of the database from which to revoke privileges.
	 * @param string $name The name of the user from whom privileges will be revoked.
	 * @param string $host The host from which the user can connect (e.g., 'localhost').
	 * @param string $grant The specific privileges to revoke (default is "*", which revokes all privileges).
	 * @return bool Returns true if the privileges were successfully revoked, false otherwise.
	 */
	public function revokePrivileges(string $database, string $name, string $host, string $grant = "*"): bool
	{
		$query = "REVOKE ALL PRIVILEGES ON {$database}.{$grant} FROM '{$name}'@'{$host}';";
		$this->exec($query);

		return true;
	}

	/**
	 * This method retrieves the privileges of a specified user from the database. It constructs a SQL query to show the grants for the user and executes it using the query() method of the PDO class. The result is fetched as an associative array and returned.
	 *
	 * @param string $name The name of the user whose privileges are to be retrieved.
	 * @param string $host The host from which the user can connect (e.g., 'localhost').
	 * @return mixed The result of the query containing the privileges of the specified user.
	 */
	public function getPrivileges(string $name, string $host): mixed
	{
		$query = "SHOW GRANTS FOR '{$name}'@'{$host}';";
		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchColumn();

		return $result;
	}

	/**
	 * This method retrieves the indexes of a specified table in a given database. It executes a SQL query to show the indexes and returns the result as an associative array.
	 *
	 * @param string $database The name of the database containing the table.
	 * @param string $table The name of the table for which to retrieve indexes.
	 * @return mixed The result of the query containing the indexes of the specified table.
	 */
	public function getIndexes(string $database, string $table): mixed
	{
		$query = "SHOW INDEXES FROM `{$table}` FROM `{$database}`;";
		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchColumn();

		return $result;
	}

	/**
	 * This method checks if the MySQL replication slave is running by executing a SQL query to retrieve the 'Slave_running' status. It returns the result of the query, which indicates whether the slave is running or not.
	 *
	 * @return mixed The result of the query indicating the status of the MySQL replication slave.
	 */
	public function isSlaveRunning(): mixed
	{
		$query = "SHOW GLOBAL STATUS LIKE 'Slave_running'";
		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetch();

		return $result;
	}

	/**
	 * This method retrieves the uptime of the MySQL server by executing a SQL query to get the 'Uptime' status. It returns the result of the query, which indicates how long the server has been running.
	 *
	 * @return mixed The result of the query indicating the uptime of the MySQL server.
	 */
	public function getUptime(): mixed
	{
		$query = "SHOW GLOBAL STATUS LIKE 'Uptime'";
		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetch();

		return $result;
	}

	/**
	 * This method retrieves the count of currently running threads in the MySQL server by executing a SQL query to get the 'Threads_running' status. It returns the result of the query, which indicates the number of threads that are currently active.
	 *
	 * @return mixed The result of the query indicating the count of running threads in the MySQL server.
	 */
	public function getRunningThreadsCount(): mixed
	{
		$query = "SHOW GLOBAL STATUS LIKE 'Threads_running'";
		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetch();

		return $result;
	}

	/**
	 * This method grants all privileges on a specified database to a user with a given name and host. It constructs a SQL query using the provided parameters and executes it using the exec() method of the PDO class.
	 * @param string $database The name of the database on which to grant privileges.
	 * @param string $name The name of the user to whom privileges will be granted.
	 * @param string $host The host from which the user can connect (e.g., 'localhost').
	 * @param string $grant The specific privileges to grant (default is "*", which grants all privileges).
	 * returns true if the privileges were successfully granted, false otherwise.
	 */
	public function grantPrivileges(string $database, string $name, string $host, string $grant = "*"): bool
	{
		$query = "GRANT ALL PRIVILEGES ON {$database}.{$grant} TO '{$name}'@'{$host}';";
		$this->exec($query);

		return true;
	}

	/**
	 * This method creates a new user in the MySQL database with the specified name, host, and password. It constructs a SQL query to create the user and executes it using the exec() method of the PDO class. The method returns true if the user was successfully created, false otherwise.
	 *
	 * @param string $name The name of the user to be created.
	 * @param string $host The host from which the user can connect (e.g., 'localhost').
	 * @param string $password The password for the new user.
	 * @return bool Returns true if the user was successfully created, false otherwise.
	 */
	public function createUser(string $name, string $host, string $password): bool
	{
		$query = "CREATE USER '{$name}'@'{$host}' IDENTIFIED BY '{$password}';";
		$this->exec($query);

		return true;
	}

	/* 
	 * This method creates a new database with the specified name. It constructs a SQL query to create the database and executes it using the exec() method of the PDO class. The method returns true if the database was successfully created, false otherwise.
	 *
	 * @param string $name The name of the database to be created.
	 * @return bool Returns true if the database was successfully created, false otherwise.
	 */
	public function createDatabase(string $name): bool
	{
		$query = "CREATE DATABASE `{$name}`;";
		$this->exec($query);

		return true;
	}

	/* 
	 * This method adds a new column to a specified table in the database. It constructs a SQL query using the provided table name, column name, and data type, and executes it using the exec() method of the PDO class. The method returns true if the operation is successful.
	 *
	 * @param string $table The name of the table to which the column will be added.
	 * @param string $column The name of the column to be added.
	 * @param string $type The data type of the column (default is 'INT').
	 * @return bool Returns true if the column was successfully added, false otherwise.
	 */
	public function addColumn(string $table, string $column, string $type = 'INT'): bool
	{
		$query = "ALTER TABLE `{$table}` ADD COLUMN {$column} {$type}";
		$this->exec($query);

		return true;
	}

	/** 
	 * This method adds an index to a specified column in a table. It constructs a SQL query using the provided table name, column name, and index name, and executes it using the exec() method of the PDO class. The method returns true if the operation is successful.
	 * 
	 * @param string $table The name of the table to which the index will be added.
	 * @param string $column The name of the column to be indexed.
	 * @param string $name The name of the index (default is 'index').
	 * @return bool Returns true if the index was successfully added, false otherwise.
	 */
	public function addIndex(string $table, string $column, string $name = 'index'): bool
	{
		$query = "ALTER TABLE {$table} ADD INDEX {$name} ({$column})";
		$this->exec($query);

		return true;
	}

	/** 
	 * This method renames a table in the database. It constructs a SQL query using the provided table name and new name, and executes it using the exec() method of the PDO class. The method
	 * 
	 * @param string $table The name of the table to be renamed.
	 * @param string $rename The new name for the table.
	 * @return bool Returns true if the table was successfully renamed, false otherwise.
	 */
	public function renameTable(string $table, string $rename): bool
	{
		$query = "ALTER TABLE {$table} RENAME TO {$rename}";
		$this->exec($query);

		return true;
	}

	/** 
	 * This method drops a column from a specified table. It constructs a SQL query using the provided table name and column name, and executes it using the exec() method of the PDO class. The method returns true if the operation is successful.
	 * 
	 * @param string $table The name of the table from which the column will be dropped.
	 * @param string $column The name of the column to be dropped.
	 * @return bool Returns true if the column was successfully dropped, false otherwise.
	 */
	public function dropColumn(string $table, string $column): bool
	{
		$query = "ALTER TABLE {$table} DROP COLUMN {$column}";
		$this->exec($query);

		return true;
	}

	/** 
	 * This method drops a table from the database if it exists. It constructs a SQL query using the provided table name and executes it using the exec() method of the PDO class. The method returns true if the operation is successful.
	 *
	 * @param string $table The name of the table to be dropped.
	 * @return bool Returns true if the table was successfully dropped, false otherwise.
	 */
	public function dropTable(string $table): bool
	{
		$query = "DROP TABLE IF EXISTS `{$table}`";
		$this->exec($query);

		return true;
	}

	/**
	 * Get the structure of a table, including column names, data types, and other attributes.
	 *
	 * @param string $database The name of the database containing the table.
	 * @param string $table The name of the table to retrieve the structure for.
	 * @return mixed An array containing the structure of the specified table.
	 */
	public function getTableStructure(string $database, string $table): mixed
	{
		$query = <<<EOD
		SELECT 
			COLUMN_NAME, 
			DATA_TYPE, 
			IS_NULLABLE, 
			COLUMN_DEFAULT, 
			COLUMN_TYPE,
			COLUMN_KEY, 
			ORDINAL_POSITION,
			CHARACTER_MAXIMUM_LENGTH,
			NUMERIC_PRECISION,
			NUMERIC_SCALE,
			EXTRA
		FROM INFORMATION_SCHEMA.COLUMNS
		WHERE TABLE_SCHEMA = '{$database}'
		AND TABLE_NAME = '{$table}';
		EOD;

		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchColumn();

		return $result;
	}

	/**
	 * Check if a table exists in the specified database.
	 *
	 * This method queries the information_schema.tables to determine if a table with the given name exists in the specified database.
	 *
	 * @param string $database The name of the database to check.
	 * @param string $table The name of the table to check for existence.
	 * @return bool Returns true if the table exists, false otherwise.
	 */
	public function isTableExists(string $database, string $table): bool
	{
		$query = <<<EOD
		SELECT 
			COUNT(TABLE_NAME) AS `COUNT`
		FROM 
			information_schema.tables 
		WHERE 
			TABLE_SCHEMA = '{$database}' AND 
			TABLE_NAME = '{$table}';
		EOD;

		$query = $this->query($query);
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchColumn();

		return $result > 0;
	}

	/**
	 * Emulate a conditional clause injection by executing a query that includes a sleep function to simulate a delay.
	 *
	 * This method constructs a SQL query that includes a conditional clause with a sleep function, which can be used to test for SQL injection vulnerabilities. The query is prepared and executed with the provided parameters.
	 *
	 * @param string $table The name of the table to query.
	 * @param string $column The name of the column to include in the WHERE clause.
	 * @param int $timeout The number of seconds to sleep (default is 5).
	 * @return void
	 */
	public function emulateConditionalClauseInjection(string $table, string $column, int $timeout = 5): void
	{
		$input = "1 AND (SELECT 1 FROM (SELECT SLEEP({$timeout}))x)";
		$stmt = $this->prepare("SELECT * FROM {$table} WHERE `{$column}` = ?");
		$stmt->execute([$input]);
	}

	/**
	 * Set the maximum execution time for queries in milliseconds.
	 *
	 * This method executes a SQL command to set the global max_execution_time variable, which limits the execution time of queries.
	 *
	 * @param int $timeout The maximum execution time in milliseconds.
	 * @return array The result of the query execution.
	 */
	public function setMaxExecutionTime(int $timeout): array
	{
		$query = $this->query(sprintf("SET GLOBAL max_execution_time = %s;", $timeout));
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchAll();

		return $result;
	}

	/**
	 * Get metadata of a table, including column names, data types, and other attributes.
	 *
	 * @param string $table The name of the table to retrieve metadata for.
	 * @return array An array containing the metadata of the specified table.
	 */
	public function getTableMetaData(string $table): array
	{
		$query = $this->query(sprintf("DESCRIBE %s", $table));
		$query->setFetchMode(PDO::FETCH_ASSOC);
		$result = $query->fetchAll();

		return $result;
	}

	/**
	 * Get a localized error message based on the exception.
	 *
	 * This method extracts the error code and arguments from the exception and returns a localized error message.
	 *
	 * @param Exception $e The exception to extract the error message from.
	 * @return string The localized error message.
	 */
	public function updateByArray(string $table, \Countable|array $columns, array $values, string $where): bool
	{
		$separatedColumns = implode(",", array_map(function ($column) {
			return sprintf("`%s` = ?", $column);
		}, $columns));

		$sql = "UPDATE `{$table}` SET {$separatedColumns} WHERE {$where}";

		$stmt = $this->prepare($sql);
		$execute = $stmt->execute(array_values($values));

		return $execute;
	}

	/**
	 * Insert a new record into the specified table using an array of column names and values.
	 *
	 * @param string $table The name of the table to insert into.
	 * @param \Countable|array $columns An array or Countable object containing the column names to insert.
	 * @param array $values An associative array of column-value pairs to be inserted.
	 * @return bool|string Returns the last inserted ID on success, or false on failure.
	 */
	public function insertByArray(string $table, \Countable|array $columns, array $values): bool|string
	{
		$columnCount = count($columns);
		$separatedColumns = implode(",", array_map(function ($column) {
			return sprintf("`%s`", htmlspecialchars($column));
		}, $columns));
		$separatedPlaceholder = implode(",", array_fill(0, $columnCount, "?"));
		$sql = "INSERT INTO `$table` ($separatedColumns) VALUES ($separatedPlaceholder)";

		$stmt = $this->prepare($sql);

		$execute = $stmt->execute(array_values($values));
		if ($execute) {
			return $this->lastInsertId();
		}

		return $execute;
	}

	/**
	 * Get a localized error message based on the provided exception.
	 *
	 * This method extracts the error code and arguments from the exception's errorInfo property and returns a localized error message based on the error code.
	 *
	 * @param $e The exception from which to extract the error information.
	 * @return string A localized error message corresponding to the error code.
	 */
	public function getLocalizedErrorMessage($e)
	{
		$errCode = $e->errorInfo[1];
		$errCodeArguments = preg_match_all("|(?:\')(.*)(?:\')|U", $e->errorInfo[2], $matches);
		if (isset($matches)) {
			$errCodeArguments = $matches[1];
		}

		$replacements = ['hostname' => $this->hostname];
		foreach ($errCodeArguments as $index => $value) {
			$replacements['arg' . $index] = (string) $value;
		}
		$serverMessage = isset($e->errorInfo[2]) && is_string($e->errorInfo[2]) ? trim($e->errorInfo[2]) : '';
		$defaultEnglishMessage = $serverMessage !== '' ? $serverMessage : sprintf('Database error (code: %s)', (string) $errCode);

		$message = "";
		switch ($errCode) {
			case "7":
				$message = Translator::trans('database_errors.pdo.7', $replacements, $defaultEnglishMessage);
				break;
			case "1004":
				$message = Translator::trans('database_errors.pdo.1004', $replacements, $defaultEnglishMessage);
				break;
			case "1005":
				$message = Translator::trans('database_errors.pdo.1005', $replacements, $defaultEnglishMessage);
				break;
			case "1006":
				$message = Translator::trans('database_errors.pdo.1006', $replacements, $defaultEnglishMessage);
				break;
			case "1007":
				$message = Translator::trans('database_errors.pdo.1007', $replacements, $defaultEnglishMessage);
				break;
			case "1008":
				$message = Translator::trans('database_errors.pdo.1008', $replacements, $defaultEnglishMessage);
				break;
			case "1009":
				$message = Translator::trans('database_errors.pdo.1009', $replacements, $defaultEnglishMessage);
				break;
			case "1010":
				$message = Translator::trans('database_errors.pdo.1010', $replacements, $defaultEnglishMessage);
				break;
			case "1011":
				$message = Translator::trans('database_errors.pdo.1011', $replacements, $defaultEnglishMessage);
				break;
			case "1012":
				$message = Translator::trans('database_errors.pdo.1012', $replacements, $defaultEnglishMessage);
				break;
			case "1013":
				$message = Translator::trans('database_errors.pdo.1013', $replacements, $defaultEnglishMessage);
				break;
			case "1014":
				$message = Translator::trans('database_errors.pdo.1014', $replacements, $defaultEnglishMessage);
				break;
			case "1015":
				$message = Translator::trans('database_errors.pdo.1015', $replacements, $defaultEnglishMessage);
				break;
			case "1016":
				$message = Translator::trans('database_errors.pdo.1016', $replacements, $defaultEnglishMessage);
				break;
			case "1017":
				$message = Translator::trans('database_errors.pdo.1017', $replacements, $defaultEnglishMessage);
				break;
			case "1018":
				$message = Translator::trans('database_errors.pdo.1018', $replacements, $defaultEnglishMessage);
				break;
			case "1019":
				$message = Translator::trans('database_errors.pdo.1019', $replacements, $defaultEnglishMessage);
				break;
			case "1020":
				$message = Translator::trans('database_errors.pdo.1020', $replacements, $defaultEnglishMessage);
				break;
			case "1021":
				$message = Translator::trans('database_errors.pdo.1021', $replacements, $defaultEnglishMessage);
				break;
			case "1022":
				$message = Translator::trans('database_errors.pdo.1022', $replacements, $defaultEnglishMessage);
				break;
			case "1023":
				$message = Translator::trans('database_errors.pdo.1023', $replacements, $defaultEnglishMessage);
				break;
			case "1024":
				$message = Translator::trans('database_errors.pdo.1024', $replacements, $defaultEnglishMessage);
				break;
			case "1025":
				$message = Translator::trans('database_errors.pdo.1025', $replacements, $defaultEnglishMessage);
				break;
			case "1026":
				$message = Translator::trans('database_errors.pdo.1026', $replacements, $defaultEnglishMessage);
				break;
			case "1027":
				$message = Translator::trans('database_errors.pdo.1027', $replacements, $defaultEnglishMessage);
				break;
			case "1028":
				$message = Translator::trans('database_errors.pdo.1028', $replacements, $defaultEnglishMessage);
				break;
			case "1029":
				$message = Translator::trans('database_errors.pdo.1029', $replacements, $defaultEnglishMessage);
				break;
			case "1030":
				$message = Translator::trans('database_errors.pdo.1030', $replacements, $defaultEnglishMessage);
				break;
			case "1031":
				$message = Translator::trans('database_errors.pdo.1031', $replacements, $defaultEnglishMessage);
				break;
			case "1032":
				$message = Translator::trans('database_errors.pdo.1032', $replacements, $defaultEnglishMessage);
				break;
			case "1033":
				$message = Translator::trans('database_errors.pdo.1033', $replacements, $defaultEnglishMessage);
				break;
			case "1034":
				$message = Translator::trans('database_errors.pdo.1034', $replacements, $defaultEnglishMessage);
				break;
			case "1035":
				$message = Translator::trans('database_errors.pdo.1035', $replacements, $defaultEnglishMessage);
				break;
			case "1036":
				$message = Translator::trans('database_errors.pdo.1036', $replacements, $defaultEnglishMessage);
				break;
			case "1037":
				$message = Translator::trans('database_errors.pdo.1037', $replacements, $defaultEnglishMessage);
				break;
			case "1038":
				$message = Translator::trans('database_errors.pdo.1038', $replacements, $defaultEnglishMessage);
				break;
			case "1039":
				$message = Translator::trans('database_errors.pdo.1039', $replacements, $defaultEnglishMessage);
				break;
			case "1040":
				$message = Translator::trans('database_errors.pdo.1040', $replacements, $defaultEnglishMessage);
				break;
			case "1041":
				$message = Translator::trans('database_errors.pdo.1041', $replacements, $defaultEnglishMessage);
				break;
			case "1042":
				$message = Translator::trans('database_errors.pdo.1042', $replacements, $defaultEnglishMessage);
				break;
			case "1043":
				$message = Translator::trans('database_errors.pdo.1043', $replacements, $defaultEnglishMessage);
				break;
			case "1044":
				$message = Translator::trans('database_errors.pdo.1044', $replacements, $defaultEnglishMessage);
				break;
			case "1045":
				$message = Translator::trans('database_errors.pdo.1045', $replacements, $defaultEnglishMessage);
				break;
			case "1046":
				$message = Translator::trans('database_errors.pdo.1046', $replacements, $defaultEnglishMessage);
				break;
			case "1047":
				$message = Translator::trans('database_errors.pdo.1047', $replacements, $defaultEnglishMessage);
				break;
			case "1048":
				$message = Translator::trans('database_errors.pdo.1048', $replacements, $defaultEnglishMessage);
				break;
			case "1049":
				$message = Translator::trans('database_errors.pdo.1049', $replacements, $defaultEnglishMessage);
				break;
			case "1050":
				$message = Translator::trans('database_errors.pdo.1050', $replacements, $defaultEnglishMessage);
				break;
			case "1051":
				$message = Translator::trans('database_errors.pdo.1051', $replacements, $defaultEnglishMessage);
				break;
			case "1052":
				$message = Translator::trans('database_errors.pdo.1052', $replacements, $defaultEnglishMessage);
				break;
			case "1053":
				$message = Translator::trans('database_errors.pdo.1053', $replacements, $defaultEnglishMessage);
				break;
			case "1054":
				$message = Translator::trans('database_errors.pdo.1054', $replacements, $defaultEnglishMessage);
				break;
			case "1055":
				$message = Translator::trans('database_errors.pdo.1055', $replacements, $defaultEnglishMessage);
				break;
			case "1056":
				$message = Translator::trans('database_errors.pdo.1056', $replacements, $defaultEnglishMessage);
				break;
			case "1057":
				$message = Translator::trans('database_errors.pdo.1057', $replacements, $defaultEnglishMessage);
				break;
			case "1058":
				$message = Translator::trans('database_errors.pdo.1058', $replacements, $defaultEnglishMessage);
				break;
			case "1059":
				$message = Translator::trans('database_errors.pdo.1059', $replacements, $defaultEnglishMessage);
				break;
			case "1060":
				$message = Translator::trans('database_errors.pdo.1060', $replacements, $defaultEnglishMessage);
				break;
			case "1061":
				$message = Translator::trans('database_errors.pdo.1061', $replacements, $defaultEnglishMessage);
				break;
			case "1062":
				$message = Translator::trans('database_errors.pdo.1062', $replacements, $defaultEnglishMessage);
				break;
			case "1063":
				$message = Translator::trans('database_errors.pdo.1063', $replacements, $defaultEnglishMessage);
				break;
			case "1064":
				$message = Translator::trans('database_errors.pdo.1064', $replacements, $defaultEnglishMessage);
				break;
			case "1146":
				$message = Translator::trans('database_errors.pdo.1146', $replacements, $defaultEnglishMessage);
				break;
			case "2002":
				$message = Translator::trans('database_errors.pdo.2002', $replacements, $defaultEnglishMessage);
				break;
			case "2054":
				$message = Translator::trans('database_errors.pdo.2054', $replacements, $defaultEnglishMessage);
				break;
			default:
				$message = $defaultEnglishMessage;
				break;
		}

		return $message;
	}

	/**
	 * Check if the given SQL query contains potentially malicious patterns.
	 *
	 * This method uses a regular expression to detect common SQL injection patterns such as SLEEP, BENCHMARK, and EXEC.
	 *
	 * @param string $query The SQL query to check for potential malicious content.
	 * @return bool Returns true if the query contains potentially malicious patterns, false otherwise.
	 */
	public function isPotentiallyMaliciousQuery(string $query)
	{
		return preg_match('/\b(SLEEP|BENCHMARK|EXEC)\b/i', $query);
	}

	/**
	 * Fetch data using the provided PDO statement according to the requested fetch type.
	 *
	 * @param PDOStatement $statement The PDO statement to fetch from.
	 * @param string $type The type of fetch to perform. Supported types:
	 *                     - 'all': Fetch all rows as an associative array.
	 *                     - 'one': Fetch the first column of the first row.
	 *                     - 'self': Fetch the next row as an associative array.
	 *                     - 'column': Fetch the next row's first column.
	 *                     - 'alias': Fetch the next row as an associative array with column aliases.
	 *                     - 'number': Fetch the next row as a numeric array.
	 *                     - 'both': Fetch the next row as both an associative and numeric array.
	 *                     - 'object': Fetch the next row as an object.
	 *                     - Default: Fetch all rows as an associative array.
	 * @return mixed The fetched data, which may be an ArrayObject if the result is an array, or a single value/object depending on the fetch type.
	 * @throws InvalidArgumentException If an unsupported fetch type is provided.
	 * @throws PDOException If there is an error executing the fetch operation.
	 * @throws RuntimeException If the provided statement is not a valid PDOStatement.
	 * @throws Exception For any other unexpected errors during fetching.
	 * @see https://www.php.net/manual/en/pdostatement.fetch.php for PDO fetch modes and details.
	 * @see https://www.php.net/manual/en/pdostatement.fetchall.php for PDO fetchAll modes and details.
	 * @see https://www.php.net/manual/en/pdo.constants.php for PDO constants used in fetch modes.
	 * @see https://www.php.net/manual/en/pdo.errorinfo.php for error handling in PDO operations.
	 * @see https://www.php.net/manual/en/pdo.prepare.php for preparing statements in PDO.
	 */
	public function fetch(PDOStatement $statement, string $type): mixed
	{
		switch ($type) {
			case 'all':
				$res = $statement->fetchAll(PDO::FETCH_ASSOC);
				break;
			case 'one':
				$res = $statement->fetch()[0] ?? "";
				break;
			case 'self':
				$res = $statement->fetch(PDO::FETCH_ASSOC);
				break;
			case 'column':
				$res = $statement->fetchColumn(PDO::FETCH_ASSOC);
				break;
			case 'alias':
				$res = $statement->fetch(PDO::FETCH_NAMED);
				break;
			case 'number':
				$res = $statement->fetch(PDO::FETCH_NUM);
				break;
			case 'both':
				$res = $statement->fetch(PDO::FETCH_BOTH);
				break;
			case 'object':
				$res = $statement->fetch(PDO::FETCH_OBJ);
				break;
			default:
				$res = $statement->fetchAll(PDO::FETCH_ASSOC);
				break;
		}

		return is_array($res) ? new ArrayObject($res) : $res;
	}

	/**
	 * Get database schema using PDO and MySQL information_schema.
	 * 
	 * @param null|string $dbName Name of the database (schema) to inspect.
	 * 
	 * @return array<
	 * 	array{
	 * 		ORDINAL_POSITION: int,
	 * 		COLUMN_NAME: string,
	 * 		COLUMN_DEFAULT: string,
	 * 		COLUMN_COMMENT: string,
	 * 		COLUMN_KEY: string,
	 * 		COLUMN_TYPE: string,
	 * 		DATA_TYPE: string,
	 * 		IS_NULLABLE: string,
	 * 		COLUMN_KEY: string,
	 * 		NUMERIC_PRECISION: int|null,
	 * 		CHARACTER_MAXIMUM_LENGTH: int|null,
	 * 		CHARACTER_OCTET_LENGTH: int|null,
	 * 		DATETIME_PRECISION: int|null
	 * 	}
	 * >
	 */
	public function getSchema(null|string $dbName = null): array
	{
		// Prepare SQL to fetch all base table names in the given database/schema.
		$dbName = $dbName ? $dbName : $this->getDatabase();
		$sqlTables = "SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = :db AND TABLE_TYPE='BASE TABLE'";
		$stmt = $this->prepare($sqlTables);
		$stmt->execute([':db' => $dbName]);

		// Fetch an array of table names (single column).
		$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
		$schema = [];

		// Prepare SQL to fetch column metadata for a specific table.
		// We request column name, data type, nullability, and key info.
		$sqlCols = "SELECT 
					`COLUMN_NAME`, 
					COLUMN_DEFAULT, 
					COLUMN_COMMENT,
					DATA_TYPE, 
					IS_NULLABLE, 
					COLUMN_KEY, 
					CHARACTER_MAXIMUM_LENGTH, 
					CHARACTER_OCTET_LENGTH, 
					NUMERIC_PRECISION, 
					NUMERIC_SCALE, 
					DATETIME_PRECISION,
					COLUMN_TYPE,
					ORDINAL_POSITION
                FROM information_schema.columns 
                WHERE table_schema = :db AND table_name = :table
                ORDER BY ORDINAL_POSITION";
		$stmtCols = $this->prepare($sqlCols);

		foreach ($tables as $table) {
			$stmtCols->execute([':db' => $dbName, ':table' => $table]);
			$cols = $stmtCols->fetchAll(PDO::FETCH_ASSOC);
			$schema[$table] = $cols;
		}

		return $schema;
	}

	/**
	 * Attempt to read slow queries from mysql.slow_log table.
	 *
	 * Requirements:
	 *  - MySQL must have slow_query_log=ON and log_output=TABLE
	 *  - The connected user must have SELECT privilege on mysql.slow_log
	 *
	 * @param int $limit  Number of rows to return
	 * @return array|null  Array of slow log rows or null if table not available
	 */
	public function getSlowLogFromTable(int $limit = 50): ?array
	{
		try {
			// Query the mysql.slow_log table if available
			$sql = "SELECT start_time, user_host, query_time, lock_time, rows_sent, rows_examined, sql_text
                FROM mysql.slow_log
                ORDER BY start_time DESC
                LIMIT :limit";
			$stmt = $this->prepare($sql);
			$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			// Table may not exist or permission denied
			return null;
		}
	}

	/**
	 * Get top slow query digests from performance_schema.
	 *
	 * This uses events_statements_summary_by_digest to get aggregated slow queries.
	 * Requirements:
	 *  - performance_schema must be enabled
	 *  - user needs SELECT on performance_schema tables
	 *
	 * @param int $limit
	 * @return array|null  Array of digests with sample SQL and metrics or null if not available
	 */
	public function getTopSlowQueriesFromPerformanceSchema(int $limit = 50): ?array
	{
		try {
			// Aggregate by digest and order by avg_timer or count as a heuristic for "slow"
			$sql = "
            SELECT 
                digest_text AS sample_sql,
                COUNT_STAR AS exec_count,
                ROUND(SUM_TIMER_WAIT/1000000000000,6) AS total_seconds,
                ROUND((SUM_TIMER_WAIT/COUNT_STAR)/1000000000000,6) AS avg_seconds,
                MAX_TIMER_WAIT/1000000000000 AS max_seconds
            FROM performance_schema.events_statements_summary_by_digest
            ORDER BY avg_seconds DESC
            LIMIT :limit
        ";
			$stmt = $this->prepare($sql);
			$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
			$stmt->execute();
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			return null;
		}
	}

	/**
	 * Explain a table or arbitrary SELECT query using PDO and MySQL EXPLAIN.
	 *
	 * If $sqlOrTable is a simple identifier (letters, numbers, underscore, dot),
	 * the function will run: EXPLAIN SELECT * FROM `schema`.`table`
	 * Otherwise, if the input looks like a SELECT query (begins with SELECT, case-insensitive),
	 * it will run: EXPLAIN <provided query>
	 *
	 * Note: Identifier quoting is applied for simple identifiers only. For complex identifiers
	 * or queries with user input, ensure you use prepared statements and never interpolate
	 * untrusted input directly into SQL.
	 *
	 * @param string $sqlOrTable Table name (simple identifier) or full SELECT query to explain.
	 * @param null|string $dbName Optional database/schema name to qualify the table when a table name is provided.
	 * @param int $limit Optional limit to append when explaining a table (0 = no LIMIT appended).
	 *
	 * @return array<
	 *  array{
	 *      id: int|null,
	 *      select_type: string|null,
	 *      table: string|null,
	 *      partitions: string|null,
	 *      type: string|null,
	 *      possible_keys: string|null,
	 *      key: string|null,
	 *      key_len: string|null,
	 *      ref: string|null,
	 *      rows: int|null,
	 *      filtered: float|null,
	 *      Extra: string|null,
	 *  }
	 * >
	 *
	 * @throws InvalidArgumentException If the provided table identifier is invalid.
	 * @throws PDOException On query errors.
	 */
	public function explainQueryOrTable(string $sqlOrTable, ?string $dbName = null, int $limit = 0): array
	{
		// Trim and normalize input
		$input = trim($sqlOrTable);

		// Helper: validate simple identifier (allow letters, numbers, underscore, dash, dot)
		// This is intentionally conservative to reduce risk of SQL injection when interpolating identifiers.
		$isSimpleIdentifier = (bool) preg_match('/^[A-Za-z0-9_\-\.]+$/', $input);

		if ($isSimpleIdentifier) {
			// Build qualified identifier if dbName provided
			if ($dbName !== null && $dbName !== '') {
				if (!preg_match('/^[A-Za-z0-9_\-]+$/', $dbName)) {
					throw new InvalidArgumentException('Invalid database name.');
				}
				// Quote identifiers using backticks to avoid reserved word issues.
				$qualified = '`' . str_replace('`', '``', $dbName) . '`.`' . str_replace('`', '``', $input) . '`';
			} else {
				$qualified = '`' . str_replace('`', '``', $input) . '`';
			}

			// Build the SELECT to explain. Optionally append LIMIT for safety.
			$selectSql = 'SELECT * FROM ' . $qualified;
			if ($limit > 0) {
				$selectSql .= ' LIMIT ' . (int) $limit;
			}

			$explainSql = 'EXPLAIN ' . $selectSql;
		} else {
			// If input looks like a SELECT query, use it directly.
			// Basic check: starts with SELECT (case-insensitive) or WITH (CTE)
			if (preg_match('/^(?:WITH|SELECT)\b/i', $input) !== 1) {
				throw new InvalidArgumentException('When passing a SQL string, it must start with SELECT or WITH. For table names, pass a simple identifier.');
			}
			$explainSql = 'EXPLAIN ' . $input;
		}

		// Execute the EXPLAIN and return associative rows.
		$stmt = $this->prepare($explainSql);
		$stmt->execute();
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Normalize types where possible: convert numeric-looking fields to int/float
		foreach ($rows as &$row) {
			foreach ($row as $k => $v) {
				if ($v === null) {
					continue;
				}

				// Convert integer-like strings to int
				if (is_string($v) && preg_match('/^\d+$/', $v)) {
					$row[$k] = (int) $v;
					continue;
				}

				// Convert float-like strings to float (e.g., filtered)
				if (is_string($v) && preg_match('/^\d+\.\d+$/', $v)) {
					$row[$k] = (float) $v;
					continue;
				}
			}
		}

		return $rows;
	}

	/**
	 * Destructor to close the database connection when the object is destroyed.
	 */
	public function __destruct()
	{
	}
}
