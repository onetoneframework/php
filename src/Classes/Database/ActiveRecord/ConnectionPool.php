<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

#region use

use PDO;
use PDOException;
use PDOStatement;
use function in_array;
use function count;

#endregion

/**
 * Manages a pool of PDO connections with read/write splitting.
 *
 * Maintains one primary write connection and zero or more read replicas.
 * Read-only SQL verbs (SELECT, SHOW, EXPLAIN, DESCRIBE, DESC) are
 * automatically routed to a replica using round-robin selection; all other
 * statements and any query executed inside an active transaction are sent
 * to the write connection.
 *
 * Transaction state is tracked internally so that queries issued within
 * a transaction are always routed to the primary, preserving consistency.
 */
class ConnectionPool
{
    #region properties

    /**
     * @var PDO The primary write connection (mandatory).
     */
    private PDO $writeConnection;

    /**
     * Optional factory for recreating the write connection on transient failure.
     * Signature: fn(): PDO
     *
     * @var callable|null
     */
    private $writeFactory;

    /** @var PDO[] Array of read replica connections. */
    private array $readConnections = [];

    /**
     * Per-replica factory callables (index-aligned with $readConnections).
     * Null when no factory was provided for a given replica.
     *
     * @var (callable|null)[]
     */
    private array $readFactories = [];

    /**
     * @var int Index for round-robin selection of read replicas.
     */
    private int $readIndex = 0;
    /**
     * @var bool True when a transaction is active on the write connection.
     * When true, all queries are routed to the write connection for consistency.
     */
    private bool $activeTransaction = false;

    /**
     * Seconds a tripped circuit stays open before the half-open probe.
     *
     * @var int
     */
    private int $circuitBreakerCooldown = 30;

    /**
     * Number of consecutive failures required to trip the circuit.
     *
     * @var int
     */
    private int $circuitBreakerThreshold = 3;

    /**
     * Per-read-replica circuit state.
     * Shape: [ $index => ['failures' => int, 'openUntil' => int] ]
     *
     * @var array<int, array{failures: int, openUntil: int}>
     */
    private array $circuitState = [];

    /**
     * When true, a SELECT 1 probe is issued before routing a query to a
     * read replica.  Failures are fed into the circuit-breaker.
     *
     * @var bool
     */
    private bool $healthCheckEnabled = false;

    /**
     * Maximum number of additional attempts after the first failure on the
     * write connection when a reconnectable error is detected.
     *
     * @var int
     */
    private int $maxRetries = 1;

    /**
     * Base delay in microseconds between retry attempts (doubles each round).
     *
     * @var int
     */
    private int $retryBaseDelayUs = 50_000;

    /**
     * PDO native error codes that indicate a dropped/lost connection and
     * therefore qualify for an automatic reconnect + retry attempt.
     */
    private const RECONNECT_DRIVER_CODES = [
        2006,   // CR_SERVER_GONE_ERROR  – MySQL server has gone away
        2013,   // CR_SERVER_LOST        – Lost connection during query
        2055,   // CR_SERVER_LOST_EXTENDED
    ];

    /**
     * Maximum number of entries in the statement cache (0 = disabled).
     * When exceeded, the least-recently-used entry is evicted.
     *
     * @var int
     */
    private int $stmtCacheLimit = 100;

    /**
     * Memoised PDOStatement objects.
     * Keys are "{connectionId}:{sql}" strings.
     *
     * @var array<string, PDOStatement>
     */
    private array $stmtCache = [];

    /**
     * Insertion-order list of cache keys for O(1) LRU eviction.
     *
     * @var list<string>
     */
    private array $stmtCacheOrder = [];

    /**
     * Monotonically increasing ID assigned to each PDO instance so that
     * statement-cache keys remain stable even if spl_object_id values are
     * recycled after garbage collection.
     *
     * @var array<int, int>   spl_object_id => pool-local id
     */
    private array $connectionIds = [];
    private int $nextConnectionId = 0;

    /** @var int Nesting depth counter for nested transactions via savepoints */
    private int $transactionDepth = 0;

    /** @var list<callable> Callbacks to execute after the outermost transaction commits */
    private array $afterCommitCallbacks = [];

    /** @var array<string, bool> Currently held advisory locks keyed by lock name */
    private array $advisoryLocks = [];

    #endregion
    
    #region function

    /**
     * Initialize the pool with a mandatory write connection.
     *
     * @param PDO           $writeConnection  The primary (write) PDO connection.
     * @param callable|null $writeFactory     Optional fn(): PDO factory used to
     *                                        recreate the write connection after a
     *                                        transient failure.
     */
    public function __construct(PDO $writeConnection, ?callable $writeFactory = null)
    {
        $this->writeConnection = $writeConnection;
        $this->writeFactory = $writeFactory;
        $this->assignConnectionId($writeConnection);
    }

    /**
     * Register an additional read replica connection.
     *
     * The new connection is appended to the read pool and will be selected
     * in round-robin rotation alongside any other registered replicas.
     *
     * @param PDO           $connection  Read replica PDO connection to add.
     * @param callable|null $factory     Optional fn(): PDO factory for automatic
     *                                   reconnection of this replica.
     *
     * @return void
     */
    public function addReadConnection(PDO $connection, ?callable $factory = null): void
    {
        $this->assignConnectionId($connection);
        $this->readConnections[] = $connection;
        $this->readFactories[] = $factory;
    }

    /**
     * Return the primary write connection.
     *
     * @return PDO
     */
    public function getWriteConnection(): PDO
    {
        return $this->writeConnection;
    }

    /** 
     * Return the array of read replica connections.
     * 
     * @return PDO[] 
     **/
    public function getReadConnections(): array
    {
        return $this->readConnections;
    }

    /**
     * Set the circuit-breaker cooldown period.
     *
     * When a read replica accumulates enough consecutive failures to trip its
     * circuit breaker, it will be excluded from the read pool for this many
     * seconds before a half-open probe is attempted.
     *
     * @param int $seconds  Cooldown duration in seconds (must be > 0).
     *
     * @return void
     */
    public function setCircuitBreakerCooldown(int $seconds): void
    {
        $this->circuitBreakerCooldown = max(1, $seconds);
    }

    /**
     * Enable or disable the pre-flight health check on read replicas.
     *
     * When enabled, a lightweight SELECT 1 probe is executed against each
     * candidate replica before routing a real query to it.  A failed probe
     * is recorded in the circuit breaker and the pool falls back to the next
     * available replica (or the write connection).
     *
     * Enabling health checks adds a small amount of latency on every read
     * but eliminates the first-query failure that would otherwise surface to
     * the application.
     *
     * @param bool $enabled  True to enable, false to disable (default: false).
     *
     * @return void
     */
    public function setHealthCheckEnabled(bool $enabled): void
    {
        $this->healthCheckEnabled = $enabled;
    }

    /**
     * Configure the retry policy for transient write-connection failures.
     *
     * When the write connection drops mid-request (e.g. "MySQL server has gone
     * away"), the pool will attempt to reconnect and replay the failed
     * operation up to $maxRetries additional times with exponential backoff.
     *
     * @param int $maxRetries     Maximum number of additional attempts (0 = no retry).
     * @param int $baseDelayUs    Base delay in microseconds; doubles each attempt.
     *
     * @return void
     */
    public function setRetryPolicy(int $maxRetries, int $baseDelayUs = 50_000): void
    {
        $this->maxRetries = max(0, $maxRetries);
        $this->retryBaseDelayUs = max(0, $baseDelayUs);
    }

    /**
     * Set the maximum number of entries in the prepared-statement cache.
     *
     * The cache memoises PDOStatement objects per (connection, SQL) pair so
     * that hot queries avoid the overhead of repeated prepare() round-trips.
     * When the limit is reached, the least-recently-used entry is evicted.
     *
     * Set to 0 to disable caching entirely.
     *
     * @param int $limit  Maximum cache entries (>= 0).
     *
     * @return void
     */
    public function setStmtCacheLimit(int $limit): void
    {
        $this->stmtCacheLimit = max(0, $limit);

        // Trim the existing cache if the new limit is smaller.
        while (count($this->stmtCache) > $this->stmtCacheLimit) {
            $evict = array_shift($this->stmtCacheOrder);
            unset($this->stmtCache[$evict]);
        }
    }

    /**
     * Prepare a SQL statement on the appropriate connection.
     *
     * Routes the statement to the write or read connection based on the SQL
     * verb and current transaction state.  Prepared statements are cached per
     * (connection, sql) key with LRU eviction.
     *
     * On a reconnectable write-connection error the pool will attempt to
     * reconnect and re-prepare up to $maxRetries times.
     *
     * @param string $query    SQL with optional ? placeholders.
     * @param array  $options  Driver-specific options passed to PDO::prepare().
     *
     * @return PDOStatement  Prepared statement ready for execution.
     */
    public function prepare(string $query, array $options = []): PDOStatement
    {
        [$connection, $readIdx] = $this->resolveConnectionWithIndex($query);
        $isRead = $readIdx !== null;

        $attempt = 0;
        while (true) {
            try {
                $stmt = $this->cachedPrepare($connection, $query, $options);
                if ($isRead) {
                    $this->recordSuccess($readIdx);
                }
                return $stmt;
            } catch (PDOException $e) {
                if ($isRead) {
                    // Record the failure and fall back to the write connection.
                    $this->recordFailure($readIdx);
                    $connection = $this->writeConnection;
                    $isRead = false;
                    continue;
                }
                // Write connection – attempt reconnect + retry.
                if ($attempt < $this->maxRetries && $this->isReconnectableError($e)) {
                    $attempt++;
                    usleep($this->retryBaseDelayUs * (2 ** ($attempt - 1)));
                    $connection = $this->reconnectWrite();
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Execute a SQL statement and return the number of affected rows.
     *
     * Delegates to PDO::exec() on the resolved connection.  Primarily used
     * for DDL statements (SAVEPOINT, RELEASE SAVEPOINT, etc.) that do not
     * return result sets.
     *
     * @param string $statement  SQL statement to execute.
     *
     * @return int|false  Number of affected rows, or false on failure.
     */
    public function exec(string $statement): int|false
    {
        [$connection] = $this->resolveConnectionWithIndex($statement);

        $attempt = 0;
        while (true) {
            try {
                return $connection->exec($statement);
            } catch (PDOException $e) {
                if ($attempt < $this->maxRetries && $this->isReconnectableError($e)) {
                    $attempt++;
                    usleep($this->retryBaseDelayUs * (2 ** ($attempt - 1)));
                    $connection = $this->reconnectWrite();
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Execute a SQL query and return the result set as a PDOStatement.
     *
     * Optionally sets a fetch mode on the statement before returning it.
     * Routes to the read or write connection based on the SQL verb.
     *
     * @param string   $query          SQL query string.
     * @param int|null $fetchMode      Optional PDO::FETCH_* constant.
     * @param mixed    ...$fetchModeArgs  Additional arguments for the fetch mode.
     *
     * @return PDOStatement|false  Statement with results, or false on failure.
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        [$connection, $readIdx] = $this->resolveConnectionWithIndex($query);
        $isRead = $readIdx !== null;

        $attempt = 0;
        while (true) {
            try {
                if ($fetchMode !== null) {
                    $result = $connection->query($query, $fetchMode, ...$fetchModeArgs);
                } else {
                    $result = $connection->query($query);
                }
                if ($isRead) {
                    $this->recordSuccess($readIdx);
                }
                return $result;
            } catch (PDOException $e) {
                if ($isRead) {
                    $this->recordFailure($readIdx);
                    $connection = $this->writeConnection;
                    $isRead = false;
                    continue;
                }
                if ($attempt < $this->maxRetries && $this->isReconnectableError($e)) {
                    $attempt++;
                    usleep($this->retryBaseDelayUs * (2 ** ($attempt - 1)));
                    $connection = $this->reconnectWrite();
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Return the ID of the last inserted row from the write connection.
     *
     * Always reads from the write connection since read replicas do not
     * receive INSERT statements.
     *
     * @param string|null $name  Sequence object name (PostgreSQL; ignored in MySQL).
     *
     * @return string|false  The last insert ID as a string, or false on failure.
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->writeConnection->lastInsertId($name);
    }

    /**
     * Begin a database transaction on the write connection.
     *
     * Supports nested transactions via savepoints. The first call starts a
     * real transaction; subsequent calls create named savepoints.
     *
     * @return bool  True on success.
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionDepth === 0) {
            $this->activeTransaction = true;
            $result = $this->writeConnection->beginTransaction();
        } else {
            $this->writeConnection->exec("SAVEPOINT _sp_{$this->transactionDepth}");
            $result = true;
        }

        $this->transactionDepth++;
        return $result;
    }

    /**
     * Commit the current transaction on the write connection.
     *
     * For nested transactions, releases the corresponding savepoint.
     * The real COMMIT only happens when the outermost level commits.
     * After-commit callbacks are fired after the real COMMIT.
     *
     * @return bool  True on success.
     */
    public function commit(): bool
    {
        if ($this->transactionDepth <= 0) {
            return false;
        }

        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            $result = $this->writeConnection->commit();
            $this->activeTransaction = false;
            $this->fireAfterCommitCallbacks();
            return $result;
        }

        $this->writeConnection->exec("RELEASE SAVEPOINT _sp_{$this->transactionDepth}");
        return true;
    }

    /**
     * Roll back the current transaction on the write connection.
     *
     * For nested transactions, rolls back to the corresponding savepoint.
     * The real ROLLBACK only happens when the outermost level rolls back.
     * After-commit callbacks are discarded on rollback.
     *
     * @return bool  True on success.
     */
    public function rollBack(): bool
    {
        if ($this->transactionDepth <= 0) {
            return false;
        }

        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            $result = $this->writeConnection->rollBack();
            $this->activeTransaction = false;
            $this->afterCommitCallbacks = [];
            return $result;
        }

        $this->writeConnection->exec("ROLLBACK TO SAVEPOINT _sp_{$this->transactionDepth}");
        return true;
    }

    /**
     * Check whether a transaction is currently active.
     *
     * Returns the internally tracked state, which mirrors the write
     * connection's transaction status.
     *
     * @return bool  True if a transaction is in progress.
     */
    public function inTransaction(): bool
    {
        return $this->activeTransaction;
    }

    /**
     * Set a PDO attribute on all connections in the pool.
     *
     * Applies the attribute to the write connection first, then propagates
     * it to every registered read replica.  Returns the result of the write
     * connection call; replica failures are silently ignored.
     *
     * @param int   $attribute  PDO::ATTR_* constant.
     * @param mixed $value      Attribute value to set.
     *
     * @return bool  True if the write connection accepted the attribute.
     */
    public function setAttribute(int $attribute, mixed $value): bool
    {
        $result = $this->writeConnection->setAttribute($attribute, $value);
        foreach ($this->readConnections as $conn) {
            $conn->setAttribute($attribute, $value);
        }
        return $result;
    }

    /**
     * Resolve the appropriate PDO connection and its read-pool index.
     *
     * Returns a two-element tuple [$pdo, $readIndex|null].
     * $readIndex is null when the write connection is returned.
     *
     * Read replicas are skipped when their circuit is open or when the
     * optional health check fails.  If all replicas are unavailable the
     * write connection is returned as a fallback.
     *
     * @param string $sql  The SQL statement to evaluate.
     *
     * @return array{0: PDO, 1: int|null}
     */
    private function resolveConnectionWithIndex(string $sql): array
    {
        if ($this->activeTransaction || empty($this->readConnections)) {
            return [$this->writeConnection, null];
        }

        $verb = strtoupper(strtok(ltrim($sql), " \t\n\r"));
        if (!in_array($verb, ['SELECT', 'SHOW', 'EXPLAIN', 'DESCRIBE', 'DESC'], true)) {
            return [$this->writeConnection, null];
        }

        $count = count($this->readConnections);
        for ($i = 0; $i < $count; $i++) {
            $idx = $this->readIndex % $count;
            $this->readIndex++;

            if ($this->isCircuitOpen($idx)) {
                continue;
            }

            $candidate = $this->readConnections[$idx];

            if ($this->healthCheckEnabled && !$this->pingConnection($candidate)) {
                $this->recordFailure($idx);
                // Attempt a replica reconnect via factory if available.
                if ($this->readFactories[$idx] !== null) {
                    try {
                        $fresh = ($this->readFactories[$idx])();
                        $this->replaceReadConnection($idx, $fresh);
                        $candidate = $fresh;
                    } catch (\Throwable) {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            return [$candidate, $idx];
        }

        // All replicas unavailable – fall back to write.
        return [$this->writeConnection, null];
    }

    /**
     * Compatibility shim that delegates to resolveConnectionWithIndex()
     * and returns only the PDO connection (preserves original signature).
     *
     * @param string $sql
     *
     * @return PDO
     */
    private function resolveConnection(string $sql): PDO
    {
        [$connection] = $this->resolveConnectionWithIndex($sql);
        return $connection;
    }

    /**
     * Check whether the circuit for a given read-replica index is open
     * (i.e. the replica is currently excluded from the pool).
     *
     * If the cooldown has elapsed since the circuit was tripped, the state is
     * automatically reset to closed so a half-open probe can proceed.
     *
     * @param int $idx  Read-connection array index.
     *
     * @return bool  True if the circuit is open (replica should be skipped).
     */
    private function isCircuitOpen(int $idx): bool
    {
        if (!isset($this->circuitState[$idx])) {
            return false;
        }

        $state = $this->circuitState[$idx];

        if ($state['openUntil'] === 0) {
            return false;
        }

        if (time() >= $state['openUntil']) {
            // Cooldown elapsed – reset to half-open (closed, failures preserved).
            $this->circuitState[$idx] = ['failures' => 0, 'openUntil' => 0];
            return false;
        }

        return true;
    }

    /**
     * Record a failure for a read replica and trip its circuit if the failure
     * count reaches the configured threshold.
     *
     * @param int $idx  Read-connection array index.
     *
     * @return void
     */
    private function recordFailure(int $idx): void
    {
        if (!isset($this->circuitState[$idx])) {
            $this->circuitState[$idx] = ['failures' => 0, 'openUntil' => 0];
        }

        $this->circuitState[$idx]['failures']++;

        if ($this->circuitState[$idx]['failures'] >= $this->circuitBreakerThreshold) {
            $this->circuitState[$idx]['openUntil'] = time() + $this->circuitBreakerCooldown;
        }
    }

    /**
     * Record a successful query against a read replica and reset its circuit
     * state to fully closed.
     *
     * @param int $idx  Read-connection array index.
     *
     * @return void
     */
    private function recordSuccess(int $idx): void
    {
        $this->circuitState[$idx] = ['failures' => 0, 'openUntil' => 0];
    }

    /**
     * Perform a lightweight SELECT 1 probe against a connection.
     *
     * Returns true if the connection is alive, false on any error.
     *
     * @param PDO $connection  The connection to probe.
     *
     * @return bool
     */
    private function pingConnection(PDO $connection): bool
    {
        try {
            $connection->query('SELECT 1');
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Determine whether a PDOException represents a dropped/lost connection
     * that is worth reconnecting and retrying.
     *
     * @param PDOException $e
     *
     * @return bool
     */
    private function isReconnectableError(PDOException $e): bool
    {
        $driverCode = (int) ($e->errorInfo[1] ?? 0);
        if (in_array($driverCode, self::RECONNECT_DRIVER_CODES, true)) {
            return true;
        }
        // Some drivers surface the error only via SQLSTATE HY000 without a driver code.
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        if ($sqlState === 'HY000' && $driverCode === 0) {
            return true;
        }
        return false;
    }

    /**
     * Attempt to reconnect the write connection using the registered factory.
     *
     * Evicts all statement-cache entries that belonged to the old connection,
     * then replaces $this->writeConnection with the fresh PDO instance.
     *
     * @return PDO  The new write connection.
     * @throws \RuntimeException  When no factory is configured.
     */
    private function reconnectWrite(): PDO
    {
        if ($this->writeFactory === null) {
            throw new \RuntimeException('ConnectionPool: write connection lost and no factory configured for reconnect.');
        }

        $this->invalidateStmtCache($this->writeConnection);
        $fresh = ($this->writeFactory)();
        $this->assignConnectionId($fresh);
        $this->writeConnection = $fresh;
        return $fresh;
    }

    /**
     * Replace a read replica connection with a freshly created one.
     *
     * Evicts cached statements for the old connection, assigns a new
     * internal ID to the fresh one, and resets the circuit-breaker state.
     *
     * @param int $idx    Index in the read-connections array.
     * @param PDO $fresh  The new PDO instance to put in place.
     *
     * @return void
     */
    private function replaceReadConnection(int $idx, PDO $fresh): void
    {
        $this->invalidateStmtCache($this->readConnections[$idx]);
        $this->assignConnectionId($fresh);
        $this->readConnections[$idx] = $fresh;
        $this->recordSuccess($idx);  // reset circuit state
    }

    /**
     * Assign a stable pool-local integer identifier to a PDO instance.
     *
     * Unlike spl_object_id(), this counter is never reused within the lifetime
     * of the pool, which prevents stale cache hits after garbage collection.
     *
     * @param PDO $connection
     *
     * @return void
     */
    private function assignConnectionId(PDO $connection): void
    {
        $oid = spl_object_id($connection);
        if (!isset($this->connectionIds[$oid])) {
            $this->connectionIds[$oid] = $this->nextConnectionId++;
        }
    }

    /**
     * Return the pool-local ID for a PDO instance.
     *
     * @param PDO $connection
     *
     * @return int
     */
    private function connectionId(PDO $connection): int
    {
        $oid = spl_object_id($connection);
        // Fallback: assign on-demand if connection was created outside the pool.
        if (!isset($this->connectionIds[$oid])) {
            $this->assignConnectionId($connection);
        }
        return $this->connectionIds[$oid];
    }

    /**
     * Return a cached PDOStatement or prepare a new one, inserting it into the
     * LRU cache.  Evicts the oldest entry when the limit is reached.
     *
     * If the statement cache is disabled ($stmtCacheLimit === 0) this method
     * delegates directly to PDO::prepare() without any caching overhead.
     *
     * @param PDO    $connection  Connection to prepare on.
     * @param string $query       SQL statement.
     * @param array  $options     Driver-specific prepare options.
     *
     * @return PDOStatement
     */
    private function cachedPrepare(PDO $connection, string $query, array $options = []): PDOStatement
    {
        if ($this->stmtCacheLimit === 0 || !empty($options)) {
            // Skip caching when the limit is 0 or driver options are present
            // (options can differ per call, making a single cache entry unsafe).
            return $connection->prepare($query, $options);
        }

        $cacheKey = $this->connectionId($connection) . ':' . $query;

        if (isset($this->stmtCache[$cacheKey])) {
            // Promote to most-recently-used.
            $pos = array_search($cacheKey, $this->stmtCacheOrder, true);
            if ($pos !== false) {
                array_splice($this->stmtCacheOrder, $pos, 1);
            }
            $this->stmtCacheOrder[] = $cacheKey;
            return $this->stmtCache[$cacheKey];
        }

        $stmt = $connection->prepare($query);

        // Evict the LRU entry if the cache is full.
        if (count($this->stmtCache) >= $this->stmtCacheLimit) {
            $evict = array_shift($this->stmtCacheOrder);
            unset($this->stmtCache[$evict]);
        }

        $this->stmtCache[$cacheKey] = $stmt;
        $this->stmtCacheOrder[] = $cacheKey;

        return $stmt;
    }

    /**
     * Evict all statement-cache entries that were prepared on the given
     * connection.  Called before a connection object is replaced so that
     * stale PDOStatements are never returned from the cache.
     *
     * @param PDO $connection  The connection whose cache entries should be removed.
     *
     * @return void
     */
    private function invalidateStmtCache(PDO $connection): void
    {
        $prefix = $this->connectionId($connection) . ':';

        foreach ($this->stmtCacheOrder as $i => $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->stmtCache[$key], $this->stmtCacheOrder[$i]);
            }
        }

        $this->stmtCacheOrder = array_values($this->stmtCacheOrder);
    }

    /**
     * Return the current transaction nesting depth.
     *
     * @return int 0 when no transaction is active
     */
    public function transactionLevel(): int
    {
        return $this->transactionDepth;
    }

    /**
     * Register a callback to run after the outermost transaction commits.
     * Callbacks are discarded if the transaction is rolled back.
     *
     * @param callable $callback
     * @return void
     */
    public function afterCommit(callable $callback): void
    {
        if ($this->transactionDepth === 0) {
            $callback();
            return;
        }

        $this->afterCommitCallbacks[] = $callback;
    }

    /**
     * @return void
     */
    private function fireAfterCommitCallbacks(): void
    {
        $callbacks = $this->afterCommitCallbacks;
        $this->afterCommitCallbacks = [];

        foreach ($callbacks as $cb) {
            try {
                $cb();
            } catch (\Throwable) {
            }
        }
    }

    /**
     * Acquire a MySQL advisory lock (GET_LOCK).
     *
     * Advisory locks are cooperative: they do not block DML. They are
     * useful for application-level mutual exclusion (e.g. cron guards,
     * unique job processing).
     *
     * @param string $name     Lock name (max 64 characters)
     * @param int    $timeout  Seconds to wait for the lock (0 = no wait)
     *
     * @return bool True if the lock was acquired
     */
    public function getLock(string $name, int $timeout = 0): bool
    {
        $stmt = $this->writeConnection->prepare('SELECT GET_LOCK(?, ?)');
        $stmt->execute([$name, $timeout]);
        $result = (int) $stmt->fetchColumn() === 1;

        if ($result) {
            $this->advisoryLocks[$name] = true;
        }

        return $result;
    }

    /**
     * Release a MySQL advisory lock (RELEASE_LOCK).
     *
     * @param string $name Lock name
     *
     * @return bool True if the lock was released
     */
    public function releaseLock(string $name): bool
    {
        $stmt = $this->writeConnection->prepare('SELECT RELEASE_LOCK(?)');
        $stmt->execute([$name]);
        $result = (int) $stmt->fetchColumn() === 1;

        if ($result) {
            unset($this->advisoryLocks[$name]);
        }

        return $result;
    }

    /**
     * Check whether a MySQL advisory lock is free (IS_FREE_LOCK).
     *
     * @param string $name Lock name
     *
     * @return bool True if the lock is not held by any session
     */
    public function isFreeLock(string $name): bool
    {
        $stmt = $this->writeConnection->prepare('SELECT IS_FREE_LOCK(?)');
        $stmt->execute([$name]);
        return (int) $stmt->fetchColumn() === 1;
    }

    /**
     * Check whether the current session holds a MySQL advisory lock (IS_USED_LOCK).
     *
     * @param string $name Lock name
     *
     * @return bool True if the current connection holds this lock
     */
    public function isUsedLock(string $name): bool
    {
        $stmt = $this->writeConnection->prepare('SELECT IS_USED_LOCK(?)');
        $stmt->execute([$name]);
        $holder = $stmt->fetchColumn();
        if ($holder === false || $holder === null) {
            return false;
        }

        $connId = $this->writeConnection->query('SELECT CONNECTION_ID()')->fetchColumn();
        return (string) $holder === (string) $connId;
    }

    /**
     * Release all advisory locks held by this connection.
     *
     * @return int Number of locks released
     */
    public function releaseAllLocks(): int
    {
        $released = 0;
        foreach (array_keys($this->advisoryLocks) as $name) {
            if ($this->releaseLock($name)) {
                $released++;
            }
        }
        return $released;
    }

    /**
     * Return the list of advisory lock names currently held.
     *
     * @return string[]
     */
    public function getHeldLocks(): array
    {
        return array_keys($this->advisoryLocks);
    }

    /**
     * Execute a raw SQL statement on the write connection without preparing it.
     *
     * Use for statements that cannot or should not be prepared (e.g. SET,
     * LOCK TABLES, multi-statement strings from migrations).
     *
     * @param string $sql Raw SQL statement
     *
     * @return int|false Number of affected rows, or false on failure
     */
    public function unprepared(string $sql): int|false
    {
        return $this->writeConnection->exec($sql);
    }

    /**
     * Force-fetch the write connection, bypassing read/write routing.
     *
     * @return PDO
     */
    public function forceWriteConnection(): PDO
    {
        return $this->writeConnection;
    }

    /**
     * Execute a LOCK TABLES statement on the write connection.
     *
     * @param array<string, string> $tables  Table => lock type ('READ'|'WRITE')
     *
     * @return void
     */
    public function lockTables(array $tables): void
    {
        $parts = [];
        foreach ($tables as $table => $mode) {
            $mode = strtoupper($mode);
            $parts[] = "`{$table}` {$mode}";
        }

        $this->writeConnection->exec('LOCK TABLES ' . implode(', ', $parts));
    }

    /**
     * Release all table-level locks held by this connection.
     *
     * @return void
     */
    public function unlockTables(): void
    {
        $this->writeConnection->exec('UNLOCK TABLES');
    }

    #endregion
}
