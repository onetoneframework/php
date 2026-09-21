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

use ArrayObject;
use Clover\Annotation;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Classes\Database\RelationLoader;
use Closure;
use Exception;
use InvalidArgumentException;
use PDO;
use ReflectionClass;
use function is_array;
use function is_object;
use function count;
use function get_class;
use function is_string;
use function in_array;
use function array_key_exists;
use function strlen;

#endregion

/**
 * Abstract ActiveRecord implementation for ORM functionality
 * 
 * Provides database mapping, CRUD operations, relationships (OneToMany, ManyToOne,
 * OneToOne, ManyToMany), eager loading, transactions, soft deletes, pagination,
 * event hooks, and query building capabilities.
 * 
 * Supports annotation-based entity mapping with @Table, @Column, @Id annotations.
 *
 * Identifier safety: column names, table names, aliases, comparison operators and sort directions
 * are structure rather than data, so no prepared-statement placeholder exists for them. Every
 * builder method that accepts one — orderBy(), having(), join(), whereColumn(), whereFullText()
 * and the rest — routes it through {@see SqlIdentifier}, which admits only `[A-Za-z0-9_]` (plus a
 * single qualifying dot) and a closed whitelist of operators and directions, and raises
 * InvalidArgumentException on anything else. Callers may therefore pass a column name straight
 * from a request without escaping it themselves.
 *
 * The `*Raw` family is the deliberate exception and is TRUSTED INPUT ONLY: whereRaw(),
 * orWhereRaw(), selectRaw(), groupByRaw(), havingRaw(), orderByRaw(), whereExists(),
 * whereNotExists(), and the subquery arguments of whereSubquery(), whereInSubquery(),
 * addSubSelect() and withCte() are passed to the driver untouched, by design — that is what they
 * are for. NEVER build any part of those expressions from user input; interpolating a request
 * value into one is a SQL injection. Bind values through the `$bindings` argument instead, and
 * when the *structure* itself is caller-chosen, use the validating builder method rather than a
 * Raw variant.
 *
 * @package Clover\Classes\Database
 * 
 * @phpstan-type ColumnMeta array{name: string, type: string, nullable: bool}
 * @phpstan-type JoinColumnMeta array{name: string|null, referencedColumnName: string|null}
 * @phpstan-type JoinTableMeta array{name: string, joinColumn: JoinColumnMeta, inverseJoinColumn: JoinColumnMeta}
 * @phpstan-type RelationshipMeta array{type: string, targetEntity: string, mappedBy: string|null, inversedBy: string|null, joinColumn?: JoinColumnMeta, joinTable?: JoinTableMeta}
 * @phpstan-type HasManyMeta array{property: string, model: string}
 * @phpstan-type EntityMetadata array{table: string, columns: array<string, ColumnMeta>, relationships: array<string, RelationshipMeta|HasManyMeta[]>, primaryKey: string|null, format: string|null}
 * @phpstan-type QueryCondition array{value: mixed, operator: string}
 * @phpstan-type WhereRawEntry array{expression: string, bindings: list<mixed>, boolean: string}
 * @phpstan-type HavingEntry array{column?: string, operator?: string, value?: mixed, raw?: string, bindings?: list<mixed>}
 * @phpstan-type JoinEntry array{type: string, table: string, first: string, operator: string, second: string}
 * @phpstan-type QueryOptions array{conditions?: array<string, mixed|QueryCondition>, or_conditions?: array<string, mixed|QueryCondition>, where_raw?: list<WhereRawEntry>, group_by?: list<string>, having?: list<HavingEntry>, order?: string, arrange?: string, order_raw?: list<string>, limit?: int, offset?: int, lock?: string, joins?: list<JoinEntry>, distinct?: bool, select?: list<string>}
 * @phpstan-type PaginationResult array{data: list<static>, total: int, per_page: int, current_page: int, last_page: int}
 * @phpstan-type QueryLogEntry array{sql: string, bindings: list<mixed>, time_ms: float}
 * @phpstan-type CacheEntry array{data: mixed, expires: int}
 */
abstract class ActiveRecord extends ArrayObject
{
    #region properties

    /**
     * Database table name
     * 
     * @var string|null
     */
    protected ?string $table = null;

    /**
     * Primary key column name
     * 
     * @var string|null
     */
    private ?string $primaryKey = null;

    /**
     * Cached reflection metadata indexed by class name
     *
     * Avoids repeated ReflectionClass parsing for the same entity class.
     * Each entry contains table name, column definitions, relationship
     * mappings, primary key info, and optional format specifier.
     * 
     * @var array<string, array{table: string, columns: array, relationships: array, primaryKey: string|null, format: string|null}>
     * @phpstan-var array<class-string<static>, EntityMetadata>
     */
    private static array $reflectionCache = [];

    /**
     * @var ConnectionPool|null
     */
    protected static ?ConnectionPool $db = null;

    /**
     * Column name for soft delete timestamp (null if disabled)
     * 
     * @var string|null
     */
    protected ?string $softDeleteColumn = null;

    /**
     * Event hooks storage indexed by class and event name.
     *
     * Supported events: creating, created, updating, updated,
     * saving, saved, deleting, deleted, restoring, restored.
     * 
     * @var array<string, array<string, callable[]>>
     * @phpstan-var array<class-string, array<string, list<callable(static): void>>>
     */
    protected static array $eventHooks = [];

    /**
     * Original data snapshot for dirty checking
     * 
     * @var array<string, mixed>
     */
    private array $originalData = [];

    /**
     * Fields modified since last sync/save
     * 
     * @var array<string, mixed>
     */
    private array $dirtyFields = [];

    /**
     * Whether entity exists in database
     * 
     * @var bool
     */
    private bool $exists = false;

    /**
     * Mass-assignable attributes whitelist
     * 
     * @var string[]
     */
    protected array $fillable = [];

    /**
     * Mass-assignment protected attributes blacklist (['*'] guards all)
     * 
     * @var string[]
     */
    protected array $guarded = ['*'];

    /**
     * Attributes hidden from array/JSON serialization
     * 
     * @var string[]
     */
    protected array $hidden = [];

    /**
     * Attribute type casting definitions
     * 
     * @var array<string, string>
     */
    protected array $casts = [];

    /**
     * Virtual attributes to append to array/JSON output
     * 
     * @var string[]
     */
    protected array $appends = [];

    /**
     * Enable automatic timestamp management
     * 
     * @var bool
     */
    protected bool $timestamps = false;

    /**
     * Created timestamp column name
     * 
     * @var string
     */
    protected string $createdAtColumn = 'created_at';

    /**
     * Updated timestamp column name
     * 
     * @var string
     */
    protected string $updatedAtColumn = 'updated_at';

    /**
     * Key marking an entry of {@see $queryConditions} as a structured condition record rather than
     * a legacy column-keyed entry or a raw expression string.
     */
    private const CONDITION_MARKER = '__condition';

    /** Comparison against a bound value. */
    private const CONDITION_TYPE_BASIC = 'basic';

    /** Membership test against a bound value list. */
    private const CONDITION_TYPE_IN = 'in';

    /** Negated membership test. */
    private const CONDITION_TYPE_NOT_IN = 'not_in';

    /** IS NULL test. */
    private const CONDITION_TYPE_NULL = 'null';

    /** IS NOT NULL test. */
    private const CONDITION_TYPE_NOT_NULL = 'not_null';

    /** Parenthesised set of nested conditions. */
    private const CONDITION_TYPE_GROUP = 'group';

    /** Pre-rendered SQL fragment carried through from a legacy options array. */
    private const CONDITION_TYPE_EXPRESSION = 'expression';

    /**
     * Scope for soft deletes (e.g., withTrashed, onlyTrashed)
     * @var string|null
     */
    private ?string $softDeleteScope = null;

    /** @var ActiveRecordQuery $activeQuery The query this instance is accumulating: conditions, ordering, joins and the rest. */
    private ActiveRecordQuery $activeQuery;

    /**
     * Default page parameter for paginate helper.
     *
     * @var int
     */
    protected int $perPage = 15;

    /**
     * The primary key type (int, string, etc.) for casting.
     *
     * @var string
     */
    protected string $keyType = 'int';

    /**
     * TTL in seconds for the next query cache operation (0 = disabled).
     *
     * @var int
     */
    private int $queryCacheTtl = 0;

    /**
     * Maximum number of entities per INSERT chunk in batchInsert().
     * MySQL's max_allowed_packet limits how large a single INSERT can be.
     * Splitting into chunks avoids packet-size errors at high volumes.
     *
     * @var int
     */
    protected static int $batchChunkSize = 500;

    /**
     * Number of retry attempts for deadlock/serialization errors in transaction().
     *
     * @var int
     */
    protected static int $transactionRetries = 3;

    /**
     * Base delay in microseconds between transaction retries (doubles each attempt).
     *
     * @var int
     */
    protected static int $transactionRetryDelayUs = 50_000;

    /**
     * Factory callable for creating new write PDO connections (enables auto-reconnect).
     * Signature: fn(): PDO
     *
     * @var callable|null
     */
    private static $connectionFactory = null;

    /**
     * Callable that establishes the connection on first use, or null.
     *
     * @var callable|null
     */
    private static $connectionResolver = null;

    /**
     * Track which classes have already been booted to prevent duplicate initialization
     *
     * @var array<string, bool>
     */
    private static array $booted = [];

    #endregion

    #region function

    /**
     * Initialize the ActiveRecord instance
     * 
     * @return void
     */
    public function __construct()
    {
        parent::__construct([], ArrayObject::ARRAY_AS_PROPS);

        $this->activeQuery = new ActiveRecordQuery();

        if ($this->table === null) {
            // Get table name from class name if not explicitly set
            $this->table = $this->resolveTableName();
        }

        $this->processAnnotations();
        $this->bootIfNotBooted();
        self::resolveConnection();
    }

    /**
     * Register how the connection is obtained the first time an entity needs one.
     *
     * The connection lives in a static that {@see setDatabaseConnection()} populates. Application
     * wiring registers that connection through a container factory, and a container factory only
     * runs when something resolves it — so without a resolver the static stays empty and every
     * query fails with "Database connection not set" however the environment is configured.
     *
     * A resolver rather than an eager connection, so that booting the container performs no I/O and
     * a request touching no entity opens no connection.
     *
     * @param callable|null $resolver Invoked once, and expected to call setDatabaseConnection()
     *
     * @return void
     */
    public static function setConnectionResolver(?callable $resolver): void
    {
        self::$connectionResolver = $resolver;
    }

    /**
     * Run the registered resolver if no connection has been established yet.
     *
     * The resolver is cleared before it runs, so a resolver that fails — or that never calls
     * setDatabaseConnection() — is not retried on every entity constructed thereafter.
     *
     * @return void
     */
    private static function resolveConnection(): void
    {
        if (self::$db !== null || self::$connectionResolver === null) {
            return;
        }

        $resolver = self::$connectionResolver;
        self::$connectionResolver = null;

        $resolver();
    }

    /**
     * Resolve table name from class name using snake_case convention
     * 
     * @return string Generated table name (e.g., UserProfile -> user_profiles)
     */
    private function resolveTableName(): string
    {
        $className = ReflectionHandler::getClassShortName($this);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . 's';
    }

    /**
     * Ensure database connection is established
     * 
     * @return void
     * @throws Exception When database connection is not set
     */
    protected function ensureConnection(): void
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }
    }

    /**
     * Process class annotations to set up relationships and properties
     * 
     * @return void
     */
    private function processAnnotations(): void
    {
        $className = get_class($this);

        // Use cached reflection data if available
        if (!isset(self::$reflectionCache[$className])) {
            $reflection = new ReflectionClass($this);
            $properties = $reflection->getProperties();

            $metadata = [
                'table' => $this->table,
                'columns' => [],
                'relationships' => [],
                'primaryKey' => null,
                'format' => null
            ];

			// Resolve table metadata from attributes before legacy docblocks.
			$tableAnnotations = ReflectionHandler::getAnnotations($reflection, Annotation\Entity\Table::class);
			$classDocComment = $reflection->getDocComment();
			if (count($tableAnnotations) > 0) {
				$tableAnnotation = $tableAnnotations[0];
				$metadata['table'] = $tableAnnotation->name;
				$this->table = $tableAnnotation->name;
			} elseif ($classDocComment) {
				preg_match('/@Table\(name="([^"]+)"\)/', $classDocComment, $tableMatches);
				if (!empty($tableMatches[1])) {
					$metadata['table'] = $tableMatches[1];
					$this->table = $tableMatches[1];
				}
			}

            // Process property annotations
            foreach ($properties as $property) {
                $docComment = $property->getDocComment();
                $propertyName = $property->getName();

                if ($docComment && preg_match('/@hasMany\(([^)]+)\)/', $docComment, $matches)) {
                    $relatedModel = trim($matches[1], '"');
                    $metadata['relationships']['hasMany'][] = [
                        'property' => $property->getName(),
                        'model' => $relatedModel
                    ];
                }

                // Check for @Column annotation
                $columnAnnotations = ReflectionHandler::getPropertyAnnotations($property, Annotation\Entity\Column::class);
                if (isset($columnAnnotations) && count($columnAnnotations)) {
                    $columnAnnotations = $columnAnnotations[0];

                    $metadata['columns'][$propertyName] = [
                        'name' => $columnAnnotations->name,
                        'type' => $columnAnnotations->type,
                        'nullable' => $columnAnnotations->nullable
                    ];
                } elseif ($docComment && preg_match('/@Column\(\s*name="([^"]+)"\s*,\s*type="([^"]+)"\s*(?:,\s*nullable=(true|false))?\s*\)/', $docComment, $colMatches)) {
                    $metadata['columns'][$propertyName] = [
                        'name' => $colMatches[1],
                        'type' => $colMatches[2],
                        'nullable' => isset($colMatches[3]) ? $colMatches[3] === 'true' : false
                    ];
                }

                // Check for @Id annotation
                $idAnnotations = ReflectionHandler::getPropertyAnnotations($property, Annotation\Entity\ID::class);
                if (isset($idAnnotations) && count($idAnnotations)) {
                    $metadata['primaryKey'] = $propertyName;
                } elseif ($docComment && preg_match('/@Id\b/', $docComment)) {
                    $metadata['primaryKey'] = $propertyName;
                }

				// Resolve format metadata from attributes before legacy docblocks.
				$formatAnnotations = ReflectionHandler::getPropertyAnnotations($property, Annotation\Entity\Format::class);
				if (count($formatAnnotations) > 0) {
					$formatAnnotation = $formatAnnotations[0];
					$metadata['format'] = $formatAnnotation->value;
				} elseif ($docComment && preg_match('/@Format\(value="([^"]+)"\)/', $docComment, $matches)) {
					$metadata['format'] = $matches[1];
				}

                // Check for relationship annotations
                $this->parseRelationshipAnnotation($property, $propertyName, Annotation\Entity\OneToMany::class, 'oneToMany', $metadata);
                $this->parseRelationshipAnnotation($property, $propertyName, Annotation\Entity\ManyToOne::class, 'manyToOne', $metadata);
                $this->parseRelationshipAnnotation($property, $propertyName, Annotation\Entity\OneToOne::class, 'oneToOne', $metadata);
                $this->parseRelationshipAnnotation($property, $propertyName, Annotation\Entity\ManyToMany::class, 'manyToMany', $metadata);

                // Check for @JoinColumn annotation
                $joinColumnAnnotations = ReflectionHandler::getPropertyAnnotations($property, Annotation\Entity\JoinColumn::class);
                if (isset($joinColumnAnnotations) && count($joinColumnAnnotations)) {
                    $joinColumnAnnotations = $joinColumnAnnotations[0];
                    $metadata['relationships'][$propertyName]['joinColumn'] = [
                        'name' => $joinColumnAnnotations->name,
                        'referencedColumnName' => $joinColumnAnnotations->referencedColumnName
                    ];
                }

                // Check for @JoinTable annotation (for ManyToMany)
                if ($docComment && preg_match('/@JoinTable\(name="([^"]+)"(?:, joinColumns=@JoinColumn\(name="([^"]+)", referencedColumnName="([^"]+)"\))?(?:, inverseJoinColumns=@JoinColumn\(name="([^"]+)", referencedColumnName="([^"]+)"\))?\)/', $docComment, $matches)) {
                    if (isset($metadata['relationships'][$propertyName]) && $metadata['relationships'][$propertyName]['type'] === 'manyToMany') {
                        $metadata['relationships'][$propertyName]['joinTable'] = [
                            'name' => $matches[1],
                            'joinColumn' => [
                                'name' => $matches[2] ?? null,
                                'referencedColumnName' => $matches[3] ?? null
                            ],
                            'inverseJoinColumn' => [
                                'name' => $matches[4] ?? null,
                                'referencedColumnName' => $matches[5] ?? null
                            ]
                        ];
                    }
                }
            }

            // If no primary key specified, default to 'id'
            if ($metadata['primaryKey'] === null && isset($metadata['columns']['id'])) {
                $metadata['primaryKey'] = 'id';
            }

            self::$reflectionCache[$className] = $metadata;
        }

        // Apply metadata to this instance
        $this->applyMetadata(self::$reflectionCache[$className]);
    }

    /**
     * Parse relationship annotations from property docblock
     * 
     * @param \ReflectionProperty $property Property reflection instance
     * @param string $propertyName Property name
     * @param string $annotationName Fully qualified annotation class name
     * @param string $propertyType Relationship type (oneToMany, manyToOne, etc.)
     * @param array &$metadata Metadata array to populate
     * 
     * @return void
     */
    private function parseRelationshipAnnotation(\ReflectionProperty $property, string $propertyName, string $annotationName, string $propertyType, array &$metadata): void
    {
        $annotations = ReflectionHandler::getPropertyAnnotations($property, $annotationName);
        if (isset($annotations) && count($annotations)) {
            $annotations = $annotations[0];

            $metadata['relationships'][$propertyName] = [
                'type' => $propertyType,
                'targetEntity' => $annotations->targetEntity,
                'mappedBy' => $annotations->mappedBy,
                'inversedBy' => $annotations->inversedBy
            ];
        }
    }

    /**
     * Apply parsed metadata to current instance
     * 
     * @param array $metadata Parsed reflection metadata
     * 
     * @return void
     */
    private function applyMetadata(array $metadata): void
    {
        $this->table = $metadata['table'];
        $this->primaryKey = $metadata['primaryKey'];
    }

    /**
     * Set database connection
     *
     * Accepts either a PDO instance directly, or a factory callable
     * that produces PDO connections. When a factory is provided, the
     * ConnectionPool can automatically reconnect on "server has gone away"
     * or similar transient failures — critical for long-running workers.
     *
     * @param PDO|callable $connection  PDO instance or fn(): PDO factory
     *
     * @return void
     */
    public static function setDatabaseConnection(PDO|callable $connection): void
    {
        $factory = null;

        if (is_callable($connection)) {
            $factory = $connection;
            $connection = $factory();
        }

        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        self::$db = new ConnectionPool($connection, $factory);
        self::$connectionFactory = $factory;
    }

    /**
     * Add a read replica connection to the connection pool.
     *
     * Accepts either a PDO instance or a factory callable. When a factory
     * is provided, the ConnectionPool can automatically reconnect failed
     * replicas via its circuit breaker mechanism.
     *
     * @param PDO|callable $connection Read replica PDO connection or fn(): PDO factory
     *
     * @return void
     * @throws Exception When no write connection has been set yet
     */
    public static function addReadConnection(PDO|callable $connection): void
    {
        if (!self::$db) {
            throw new Exception('Write connection must be set before adding read connections');
        }

        $factory = null;
        if (is_callable($connection)) {
            $factory = $connection;
            $connection = $factory();
        }

        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $connection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        self::$db->addReadConnection($connection, $factory);
    }

    /**
     * Magic setter for property values
     * 
     * Handles mass-assignment protection (fillable/guarded), type casting,
     * and dirty field tracking.
     * 
     * @param string $variable Property name
     * @param mixed $value Value to set
     * 
     * @return void
     */
    public function __set(string $variable, mixed $value): void
    {
        $className = get_class($this);
        $metadata = self::$reflectionCache[$className] ?? null;

        if (!empty($this->fillable)) {
            if ($metadata && isset($metadata['columns'][$variable]) && !in_array($variable, $this->fillable)) {
                return;
            }
        } else {
            if (!empty($this->guarded) && ($this->guarded[0] === '*' || in_array($variable, $this->guarded))) {
                if ($metadata && isset($metadata['columns'][$variable])) {
                    return;
                }
            }
        }

        // Apply mutator if defined (e.g., setUsernameAttribute)
        $value = $this->mutateAttribute($variable, $value);

        if (isset($this->casts[$variable])) {
            $value = $this->castAttribute($variable, $value);
        }

        // Encrypt value if the attribute is in the encrypted list
        if (in_array($variable, $this->encrypted) && $value !== null && is_string($value) && AttributeEncrypter::hasKey()) {
            $value = static::encryptValue($value);
        }

        $this->offsetSet($variable, $value);
    }

    /**
     * Cast attribute value to specified type
     * 
     * Supports: int, float, decimal, string, bool, object, array, json,
     * collection, date, datetime, timestamp, and custom class casters.
     * 
     * @param string $key Attribute name
     * @param mixed $value Raw value to cast
     * 
     * @return mixed Casted value
     */
    protected function castAttribute(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $castType = $this->casts[$key] ?? null;
        if (!$castType) {
            return $value;
        }

        switch ($castType) {
            case 'int':
            case 'integer':
                return (int) $value;
            case 'real':
            case 'float':
            case 'double':
                return (float) $value;
            case 'decimal':
                return number_format((float) $value, 2, '.', '');
            case 'string':
                return (string) $value;
            case 'bool':
            case 'boolean':
                return (bool) $value;
            case 'object':
                return is_string($value) ? json_decode($value, false) : (object) $value;
            case 'array':
            case 'json':
                return is_string($value) ? json_decode($value, true) : (array) $value;
            case 'collection':
                return new ArrayObject(is_string($value) ? json_decode($value, true) : (array) $value);
            case 'date':
            case 'datetime':
            case 'timestamp':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            default:
                if (str_starts_with($castType, 'decimal:')) {
                    return number_format((float) $value, (int) substr($castType, 8), '.', '');
                }

                if (class_exists($castType)) {
                    return new $castType($value);
                }

                return $value;
        }
    }

    /**
     * Magic getter for property values
     * 
     * Supports lazy loading for relationships, type casting, and accessor methods.
     * Accessor format: get{PropertyName}Attribute()
     * 
     * @param string $variable Property name
     * 
     * @return mixed Property value or null
     */
    public function &__get(string $variable): mixed
    {
        $className = get_class($this);
        $metadata = self::$reflectionCache[$className] ?? null;

        // Lazy loading for relationships
        if ($metadata && isset($metadata['relationships'][$variable]) && !$this->offsetExists($variable)) {
            (new RelationLoader($this))->load($variable, $metadata['relationships'][$variable]);
        }

        // Return value if exists
        if ($this->offsetExists($variable)) {
            $value = $this->offsetGet($variable);
            if (isset($this->casts[$variable])) {
                $value = $this->castAttribute($variable, $value);
            }
            // Decrypt value if the attribute is in the encrypted list
            if (in_array($variable, $this->encrypted) && $value !== null && is_string($value) && AttributeEncrypter::hasKey()) {
                try {
                    $value = static::decryptValue($value);
                } catch (\Throwable $e) {
                    // Value may not be encrypted yet (e.g. raw DB value); return as-is
                }
            }
            return $value;
        }

        $accessor = 'get' . str_replace('_', '', ucwords($variable, '_')) . 'Attribute';
        if (method_exists($this, $accessor)) {
            $value = $this->$accessor();
            return $value;
        }

        // Create null reference for non-existent properties
        $null = null;
        return $null;
    }

    /**
     * Check if property is set
     * 
     * @param mixed $variable Property name
     * 
     * @return bool True if property exists or has accessor
     */
    public function __isset(mixed $variable): bool
    {
        return $this->offsetExists($variable) || method_exists($this, 'get' . str_replace('_', '', ucwords($variable, '_')) . 'Attribute');
    }

    /**
     * Override ArrayObject::offsetSet to track dirty fields.
     *
     * When the entity has been persisted (exists=true), any value change
     * is recorded in the dirtyFields array for later use by save(), isDirty(),
     * and getDirty(). This enables efficient UPDATE queries that only
     * touch modified columns.
     *
     * @param mixed $key   Property/attribute name
     * @param mixed $value New value to set
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet(mixed $key, mixed $value): void
    {
        if ($this->exists && $key !== null) {
            $oldValue = $this->offsetExists($key) ? $this->offsetGet($key) : null;
            if ($oldValue !== $value) {
                $this->dirtyFields[$key] = $value;
            }
        }

        parent::offsetSet($key, $value);
    }





    /**
     * Map database row to entity instance
     * 
     * @param string|null $targetClass
     * @param array $targetMetadata
     * @param array|bool|null $row
     * @param array $metadata
     * @param bool $array
     * @param bool $castType
     * 
     * @return object|null
     * @phpstan-param class-string<static>|null $targetClass
     * @phpstan-param EntityMetadata $targetMetadata
     * @phpstan-param EntityMetadata $metadata
     * @phpstan-return static|null
     */
    public function mapRowToEntity(?string $targetClass = null, array $targetMetadata = [], array|bool|null $row = [], array $metadata = [], bool $array = true, bool $castType = true): object|null
    {
        if (!$row) {
            return null;
        }

        $entity = new $targetClass();

        foreach ($targetMetadata['columns'] as $targetProperty => $columnInfo) {
            $columnName = $columnInfo['name'];
            if (array_key_exists($columnName, $row)) {
                $value = $row[$columnName];

                if ($castType && $value !== null) {
                    $value = $this->castValueToType($value, $columnInfo['type'], $metadata);
                }

                if ($array) {
                    $entity->offsetSet($targetProperty, $value);
                } else {
                    $entity->$targetProperty = $value;
                }
            }
        }

        $entity->exists = true;
        $entity->syncOriginal();

        return $entity;
    }


    /**
     * Cast database value to PHP type
     * 
     * @param mixed $value Raw database value
     * @param string $type Column type from metadata
     * @param array $metadata Entity metadata
     * 
     * @return mixed Converted PHP value
     */
    public function castValueToType(mixed $value, string $type, array $metadata = []): mixed
    {
        if ($value === null) {
            return null;
        }

        switch (strtolower($type)) {
            case 'integer':
            case 'int':
            case 'bigint':
            case 'smallint':
            case 'tinyint':
                return (int) $value;
            case 'float':
            case 'double':
            case 'decimal':
            case 'numeric':
            case 'real':
                return (float) $value;
            case 'boolean':
            case 'bool':
                return (bool) $value;
            case 'datetime':
            case 'timestamp':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            case 'date':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            case 'time':
                return $value instanceof \DateTime ? $value : new \DateTime($value);
            case 'json':
            case 'array':
            case 'jsonb':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'binary':
            case 'blob':
                return $value;
            case 'uuid':
            case 'guid':
                return (string) $value;
            case 'string':
            case 'text':
            case 'varchar':
            case 'char':
            default:
                return (string) $value;
        }
    }

    /**
     * Prepare PHP value for database storage
     * 
     * @param mixed $value PHP value to convert
     * @param string $type Column type from metadata
     * 
     * @return mixed Database-ready value
     */
    private function prepareValueForDatabase(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        switch (strtolower($type)) {
            case 'datetime':
            case 'timestamp':
                if ($value instanceof \DateTime)
                    return $value->format('Y-m-d H:i:s');
                if (is_string($value))
                    return (new \DateTime($value))->format('Y-m-d H:i:s');
                return $value;
            case 'date':
                if ($value instanceof \DateTime)
                    return $value->format('Y-m-d');
                if (is_string($value))
                    return (new \DateTime($value))->format('Y-m-d');
                return $value;
            case 'time':
                if ($value instanceof \DateTime)
                    return $value->format('H:i:s');
                return $value;
            case 'json':
            case 'array':
            case 'jsonb':
                return is_array($value) || is_object($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            case 'boolean':
            case 'bool':
                return $value ? 1 : 0;
            default:
                return $value;
        }
    }


    /**
     * Magic method for method calls
     * 
     * @param string $name
     * @param array $arguments
     * 
     * @return mixed
     */
    public function __call(string $name, array $arguments = []): mixed
    {
        if (method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }

        // Handle getter and setter methods
        if (preg_match('/^get([A-Z][a-zA-Z0-9]*)$/', $name, $matches)) {
            $property = lcfirst($matches[1]);
            return $this->__get($property);
        } elseif (preg_match('/^set([A-Z][a-zA-Z0-9]*)$/', $name, $matches)) {
            $property = lcfirst($matches[1]);
            $this->__set($property, $arguments[0] ?? null);
            return $this;
        } elseif (preg_match('/^has([A-Z][a-zA-Z0-9]*)$/', $name, $matches)) {
            $property = lcfirst($matches[1]);
            return isset($this[$property]) && $this[$property] !== null;
        } elseif (preg_match('/^add([A-Z][a-zA-Z0-9]*)$/', $name, $matches)) {
            $property = lcfirst($matches[1]);

            // Handle singular to plural conversion for common endings
            if (substr($property, -1) === 's') {
                $collectionName = $property;
            } else {
                $collectionName = $property . 's';
            }

            // Check if this is a collection
            $metadata = $this->getMetadata();
            if (isset($metadata['relationships'][$collectionName])) {
                $property = $collectionName;
            }

            if (!isset($this[$property])) {
                $this[$property] = [];
            }

            if (is_array($this[$property])) {
                $this[$property][] = $arguments[0];
            }

            return $this;
        } elseif (preg_match('/^remove([A-Z][a-zA-Z0-9]*)$/', $name, $matches)) {
            $property = lcfirst($matches[1]);

            // Handle singular to plural conversion for common endings
            if (substr($property, -1) === 's') {
                $collectionName = $property;
            } else {
                $collectionName = $property . 's';
            }

            // Check if this is a collection
            $metadata = $this->getMetadata();
            if (isset($metadata['relationships'][$collectionName])) {
                $property = $collectionName;
            }

            if (isset($this[$property]) && is_array($this[$property])) {
                $item = $arguments[0];
                $itemId = null;

                if (is_object($item) && method_exists($item, 'getId')) {
                    $itemId = $item->getId();
                }

                foreach ($this[$property] as $key => $value) {
                    $valueId = null;
                    if (is_object($value) && method_exists($value, 'getId')) {
                        $valueId = $value->getId();
                    }

                    if ($value === $item || ($itemId !== null && $valueId !== null && $itemId === $valueId)) {
                        unset($this[$property][$key]);
                        break;
                    }
                }

                // Reindex array
                $this[$property] = array_values($this[$property]);
            }

            return $this;
        }

        // Handle finder methods
        if (preg_match('/^findBy([A-Z][a-zA-Z0-9]*)$/', $name, $matches)) {
            $property = lcfirst($matches[1]);
            return $this->findBy($property, $arguments[0] ?? null);
        }

        if ($name === 'countRecords') {
            return $this->count(...$arguments);
        }

        throw new Exception("Method $name not found");
    }

    /**
     * Get cached reflection metadata for this entity.
     *
     * Returns the parsed annotation metadata (table, columns, relationships,
     * primary key, format) for the current entity class from the static cache.
     *
     * @return array{
     *  columns: array, 
     *  format: string|null, 
     *  primaryKey: string|null, 
     *  relationships: array, 
     *  table: string
     * }|null Metadata array or null
     * 
     * @phpstan-return EntityMetadata|null
     */
    public function getMetadata(): ?array
    {
        $className = get_class($this);
        return self::$reflectionCache[$className] ?? null;
    }

    /**
     * The cached metadata for a class, or null when it has none.
     *
     * The reflection cache stays private; this is the one way out of it, and it
     * exists because RelationLoader reads the *target* entity's metadata, which
     * is a different class from the one being loaded.
     *
     * @param string $className The entity class to look up.
     *
     * @return array<string, mixed>|null
     */
    public static function metadataFor(string $className): ?array
    {
        return self::$reflectionCache[$className] ?? null;
    }

    /**
     * Find by multiple conditions
     * 
     * null returns when empty
     * @param array $arguments
     * @throws \Exception
     * @return object|object[]|null
     */
    public function findByMultipleCondition(...$arguments): array|static|null
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        if (count($arguments) % 2 !== 0) {
            throw new Exception("Arguments should be in key-value pairs");
        }

        $conditions = [];
        $values = [];

        for ($i = 0; $i < count($arguments); $i += 2) {
            $property = $arguments[$i];
            $value = $arguments[$i + 1];

            if (!isset($metadata['columns'][$property])) {
                throw new Exception("Property $property does not exist or is not mapped to a database column");
            }

            $columnName = $metadata['columns'][$property]['name'];
            $conditions[] = "$columnName = ?";
            $values[] = $value;
        }

        $whereClause = implode(" AND ", $conditions);
        $sql = "SELECT * FROM `{$tableName}` WHERE $whereClause";
        $stmt = self::$db->prepare($sql);
        $stmt->execute($values);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) > 1) {
            $className = get_class($this);
            $results = [];

            foreach ($rows as $row) {
                $entity = new $className();

                foreach ($metadata['columns'] as $property => $columnInfo) {
                    $dbColumnName = $columnInfo['name'];
                    if (isset($row[$dbColumnName])) {
                        $entity->offsetSet($property, $this->castValueToType($row[$dbColumnName], $columnInfo['type'], $metadata));
                    }
                }

                $entity->exists = true;
                $entity->syncOriginal();
                $results[] = $entity;
            }

            return $results;
        }

        if (empty($rows)) {
            return null;
        }

        $row = $rows[0];
        $targetClass = get_class($this);

        return $this->mapRowToEntity($targetClass, $metadata, $row, $metadata, true);
    }

    /**
     * Find records by field value
     * 
     * @param string $field Column name to search
     * @param mixed $value Value to match
     * @param string $operator Comparison operator (=, >, <, etc.)
     * 
     * @return array|object|null Single entity, array of entities, or null
     * @throws Exception When property not mapped or database not connected
     */
    public function findBy(string $field, mixed $value, string $operator = "="): array|static|null
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        // Find the database column name for the property
        $columnName = null;
        foreach ($metadata['columns'] as $property => $columnInfo) {
            if (strtolower($property) === strtolower($field)) {
                $columnName = $columnInfo['name'];
                break;
            }
        }

        if (!$columnName) {
            throw new Exception("Property $field does not exist or is not mapped to a database column");
        }

        $quotedTable = SqlIdentifier::quote($tableName);
        $quotedColumn = SqlIdentifier::quote($columnName);
        $safeOperator = SqlIdentifier::operator($operator);

        $sql = "SELECT * FROM {$quotedTable} WHERE {$quotedColumn} {$safeOperator} ?";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$value]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 1) {
            $className = get_class($this);
            $results = [];

            foreach ($rows as $row) {
                $entity = new $className();

                foreach ($metadata['columns'] as $property => $columnInfo) {
                    $dbColumnName = $columnInfo['name'];
                    if (isset($row[$dbColumnName])) {
                        $entity->offsetSet($property, $this->castValueToType($row[$dbColumnName], $columnInfo['type'], $metadata));
                    }
                }

                $entity->exists = true;
                $entity->syncOriginal();
                $results[] = $entity;
            }

            return $results;
        }

        if (!$rows) {
            return null;
        }

        $targetClass = get_class($this);
        $row = $rows[0];

        return $this->mapRowToEntity($targetClass, $metadata, $row, $metadata, true, false);
    }

    /**
     * Magic method to unset a property
     * 
     * @param string $variable Property name to unset
     * 
     * @return void
     */
    public function __unset(string $variable): void
    {
        if ($this->offsetExists($variable)) {
            $this->offsetUnset($variable);
        }
    }

    /**
     * Save entity to database (INSERT or UPDATE)
     *
     * Determines operation based on exists flag and primary key.
     * Handles automatic timestamps if enabled.
     *
     * @param array $debug Debug options
     *
     * @return static This entity instance
     * @throws Exception When database connection not set or optimistic lock fails
     */
    public function save(array $debug = []): static
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];

        $storedKeys = $this->getArrayCopy();
        $isInsert = !$this->exists || !array_key_exists($pkName, $storedKeys) || empty($storedKeys[$pkName]);

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if ($isInsert && isset($metadata['columns'][$this->createdAtColumn])) {
                $this[$this->createdAtColumn] = $now;
            }
            if (isset($metadata['columns'][$this->updatedAtColumn])) {
                $this[$this->updatedAtColumn] = $now;
            }
        }

        $tableName = $metadata['table'];
        $columns = $metadata['columns'];

        if ($isInsert) {
            // ── INSERT ────────────────────────────────────────────────
            $storedData = $this->getArrayCopy();
            $data = [];
            foreach ($columns as $property => $columnInfo) {
                if (array_key_exists($property, $storedData)) {
                    $data[$columnInfo['name']] = $this->prepareValueForDatabase($storedData[$property], $columnInfo['type']);
                }
            }

            // Set initial version for optimistic locking
            if ($this->versionColumn && isset($columns[$this->versionColumn])) {
                $verCol = $columns[$this->versionColumn]['name'];
                if (!isset($data[$verCol]) || $data[$verCol] === null) {
                    $data[$verCol] = 1;
                    $this->offsetSet($this->versionColumn, 1);
                }
            }

            $columnNames = implode(', ', array_map(fn($col) => "`$col`", array_keys($data)));
            $placeholders = implode(', ', array_fill(0, count($data), '?'));

            $sql = "INSERT INTO `{$tableName}` ({$columnNames}) VALUES ({$placeholders})";
            $stmt = self::$db->prepare($sql);
            $stmt->execute(array_values($data));

            if (self::$db->lastInsertId()) {
                $pkType = $metadata['columns'][$pkName]['type'] ?? 'string';
                $this[$pkName] = $this->castValueToType(self::$db->lastInsertId(), $pkType);
            }
            $this->exists = true;
        } else {
            // ── UPDATE (dirty columns only) ───────────────────────────
            $dirtyData = $this->dirtyFields;

            // If timestamps triggered a change, include updatedAt
            if ($this->timestamps && isset($columns[$this->updatedAtColumn])) {
                $dirtyData[$this->updatedAtColumn] = $this[$this->updatedAtColumn];
            }

            // Nothing changed — skip the query entirely
            if (empty($dirtyData)) {
                return $this;
            }

            $setParts = [];
            $values = [];

            foreach ($dirtyData as $property => $value) {
                if (!isset($columns[$property])) {
                    continue;
                }
                $colName = $columns[$property]['name'];
                if ($colName === $columns[$pkName]['name']) {
                    continue; // Never update the PK
                }
                $setParts[] = "`{$colName}` = ?";
                $values[] = $this->prepareValueForDatabase($value, $columns[$property]['type']);
            }

            if (empty($setParts)) {
                return $this;
            }

            $pkColumn = $columns[$pkName]['name'];

            // Optimistic locking: auto-increment version and guard with WHERE
            if ($this->versionColumn && isset($columns[$this->versionColumn])) {
                $verCol = $columns[$this->versionColumn]['name'];
                $currentVersion = $this->originalData[$this->versionColumn] ?? $this[$this->versionColumn] ?? 0;
                $setParts[] = "`{$verCol}` = `{$verCol}` + 1";

                $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts)
                    . " WHERE `{$pkColumn}` = ? AND `{$verCol}` = ?";
                $values[] = $this[$pkName];
                $values[] = $currentVersion;
            } else {
                $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts)
                    . " WHERE `{$pkColumn}` = ?";
                $values[] = $this[$pkName];
            }

            $stmt = self::$db->prepare($sql);
            $stmt->execute($values);

            // Optimistic lock failure: no rows affected means version mismatch
            if ($this->versionColumn && $stmt->rowCount() === 0) {
                throw new Exception("Optimistic lock failure: entity {$tableName}#{$this[$pkName]} was modified by another process (expected version {$currentVersion})");
            }

            // Update local version counter
            if ($this->versionColumn) {
                $this->offsetSet($this->versionColumn, ($currentVersion ?? 0) + 1);
            }
        }

        $this->syncOriginal();
        $this->dirtyFields = [];

        return $this;
    }

    /**
     * Delete the current entity from the database.
     *
     * If a soft delete column is configured on this entity, the record is
     * soft-deleted (timestamp written to the soft delete column) instead of
     * being physically removed. To bypass soft delete and permanently remove
     * the record, use forceDelete().
     *
     * Fires 'deleting' and 'deleted' event hooks when they are registered.
     *
     * @return bool True if the record was successfully deleted
     * @throws Exception When database connection not set or primary key missing
     */
    public function delete(): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        if ($this->softDeleteColumn) {
            return $this->softDelete();
        }

        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];

        if (!$this->offsetExists($pkName) || empty($this[$pkName])) {
            throw new Exception("Cannot delete entity without primary key");
        }

        $tableName = $metadata['table'];
        $pkColumn = $metadata['columns'][$pkName]['name'];

        $sql = "DELETE FROM `{$tableName}` WHERE `{$pkColumn}` = ?";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$this[$pkName]]);

        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            $this->exists = false;
        }

        return $deleted;
    }

    /**
     * Find entity by primary key
     * 
     * @param mixed $id Primary key value
     * @param array $options Query options (conditions, order, limit, offset)
     * 
     * @return object|null Found entity or null
     * @throws Exception When database connection not set
     */
    protected function find(mixed $id, array $options = []): ?static
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }
        $params = [];

        $metadata = $this->getMetadata();

        $tableName = $metadata['table'];
        $pkName = $metadata['primaryKey'];
        $pkColumn = $metadata['columns'] ? $metadata['columns'][$pkName]['name'] : "";

        $query = "SELECT * FROM `{$tableName}`";
        if (empty($options['conditions'][$pkColumn])) {
            $options['conditions'][$pkColumn] = ['value' => $id, 'operator' => '='];
        }

        $this->applySoftDeleteScope($options);

        $modified = $this->modifyQueryByOptions($query, $options);
        $query = $modified['query'];
        $params = $modified['parameters'];

        $stmt = self::$db->prepare($query);
        $stmt->execute($params);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $targetClass = get_class($this);
        return $this->mapRowToEntity($targetClass, $metadata, $row, $metadata, true, false);
    }

    /**
     * Count entities by multiple conditions
     * 
     * @param array $arguments Key-value pairs of conditions
     * 
     * @return mixed
     */
    public function countByMultipleConditions(...$arguments): mixed
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        if (count($arguments) % 2 !== 0) {
            throw new Exception("Arguments should be in key-value pairs");
        }

        $conditions = [];
        for ($i = 0; $i < count($arguments); $i += 2) {
            $property = $arguments[$i];
            $value = $arguments[$i + 1];
            $conditions[$property] = $value;
        }

        $options = [];
        if (!empty($conditions)) {
            $options['conditions'] = $conditions;
        }

        return $this->count($options);
    }

    /**
     * Count entities matching the given query options.
     *
     * Executes a SELECT COUNT(*) query. Strips limit, offset, order, and select
     * options since they are irrelevant for counting. Respects WHERE conditions,
     * JOINs, GROUP BY, and HAVING clauses.
     * 
     * @param array $options Query options (conditions, joins, group_by, having)
     * 
     * @return int Count of records
     * @phpstan-param QueryOptions $options
     * @throws Exception When database not connected
     */
    #[\ReturnTypeWillChange]
    public function count(array $options = []): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $joinSql = '';
        if (!empty($options['joins'])) {
            foreach ($options['joins'] as $j) {
                // Every component was validated and quoted by buildJoinEntry() / crossJoin().
                $joinSql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        $countOptions = $options;
        unset($countOptions['limit'], $countOptions['offset'], $countOptions['order'], $countOptions['arrange'], $countOptions['order_raw'], $countOptions['select']);

        $sql = "SELECT COUNT(*) as _cnt FROM `{$tableName}`{$joinSql}";
        $modified = $this->modifyQueryByOptions($sql, $countOptions);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) ($row['_cnt'] ?? 0);
    }

    /**
     * Find all entities matching the given query options.
     *
     * Builds and executes a SELECT query with optional WHERE, JOIN, ORDER BY,
     * LIMIT, OFFSET, GROUP BY, HAVING, DISTINCT, and row-level lock clauses.
     * Each result row is hydrated into an entity instance with exists=true.
     *
     * @param array $options Query options conforming to QueryOptions shape
     *
     * @return list<static> Array of hydrated entity instances
     * @phpstan-param QueryOptions $options
     * @phpstan-return list<static>
     * @throws Exception When database connection not set
     */
    protected function findAll(array $options = []): array
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $distinct = !empty($options['distinct']) ? 'DISTINCT ' : '';
        $selectRaw = !empty($options['select']) ? implode(', ', $options['select']) : "`{$tableName}`.*";
        $joinSql = '';
        if (!empty($options['joins'])) {
            foreach ($options['joins'] as $j) {
                // Every component was validated and quoted by buildJoinEntry() / crossJoin().
                $joinSql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        $prefixBindings = [];

        if (!empty($options['ctes'])) {
            [$ctePrefix, $cteBindings] = $this->buildCtePrefix($options['ctes']);
            $prefixBindings = array_merge($prefixBindings, $cteBindings);
        } else {
            $ctePrefix = '';
        }

        if (!empty($options['from_subquery'])) {
            $fsq = $options['from_subquery'];
            $prefixBindings = array_merge($prefixBindings, $fsq['bindings']);
            $fsqAlias = SqlIdentifier::quote($fsq['alias']);
            $sql = "{$ctePrefix}SELECT {$distinct}{$selectRaw} FROM ({$fsq['sql']}) AS {$fsqAlias}{$joinSql}";
        } else {
            $sql = "{$ctePrefix}SELECT {$distinct}{$selectRaw} FROM `{$tableName}`{$joinSql}";
        }

        $modified = $this->modifyQueryByOptions($sql, $options);
        $params = array_merge($prefixBindings, $modified['parameters']);

        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($params);

        $entities = [];
        $calledClass = get_class($this);
        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = new $calledClass();

            foreach ($metadata['columns'] as $property => $columnInfo) {
                $columnName = $columnInfo['name'];
                if (array_key_exists($columnName, $data)) {
                    $instance->offsetSet($property, $data[$columnName]);
                }
            }

            $instance->exists = true;
            $instance->syncOriginal();
            $entities[] = $instance;
        }

        return $entities;
    }

    /**
     * Convert a condition list into structured records.
     *
     * Three shapes reach this method and all remain supported: structured records produced by the
     * fluent builder, legacy column-keyed entries such as `['id' => 5]` or
     * `['id' => ['operator' => '>', 'value' => 5]]` that callers still pass through options arrays,
     * and numerically-keyed pre-rendered SQL fragments.
     *
     * @param array<array-key,mixed> $conditions
     *
     * @return list<array<string,mixed>>
     */
    private function normalizeConditions(array $conditions): array
    {
        $records = [];

        foreach ($conditions as $key => $value) {
            if (is_array($value) && isset($value[self::CONDITION_MARKER])) {
                $records[] = $value;
                continue;
            }

            if (is_numeric($key)) {
                $records[] = [
                    self::CONDITION_MARKER => true,
                    'type' => self::CONDITION_TYPE_EXPRESSION,
                    'boolean' => 'AND',
                    'column' => null,
                    'operator' => null,
                    'value' => (string) $value,
                    'nested' => [],
                    'negate' => false,
                ];
                continue;
            }

            if (!is_array($value)) {
                $records[] = [
                    self::CONDITION_MARKER => true,
                    'type' => self::CONDITION_TYPE_BASIC,
                    'boolean' => 'AND',
                    'column' => (string) $key,
                    'operator' => '=',
                    'value' => $value,
                    'nested' => [],
                    'negate' => false,
                ];
                continue;
            }

            $operator = strtoupper((string) ($value['operator'] ?? '='));
            $type = match ($operator) {
                'IN' => self::CONDITION_TYPE_IN,
                'NOT IN' => self::CONDITION_TYPE_NOT_IN,
                'IS NULL' => self::CONDITION_TYPE_NULL,
                'IS NOT NULL' => self::CONDITION_TYPE_NOT_NULL,
                default => self::CONDITION_TYPE_BASIC,
            };

            $records[] = [
                self::CONDITION_MARKER => true,
                'type' => $type,
                'boolean' => strtoupper((string) ($value['boolean'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
                'column' => (string) $key,
                'operator' => $value['operator'] ?? '=',
                'value' => $value['value'] ?? null,
                'nested' => [],
                'negate' => false,
            ];
        }

        return $records;
    }

    /**
     * Render condition records into a SQL fragment, collecting bound parameters in order.
     *
     * The boolean of the first record is ignored: a fragment cannot open with AND or OR.
     *
     * @param list<array<string,mixed>> $records
     * @param list<mixed>               $params Bound parameters, appended in placeholder order
     *
     * @return string SQL fragment without a leading WHERE keyword
     */
    private function compileConditions(array $records, array &$params): string
    {
        $sql = '';

        foreach ($records as $record) {
            $fragment = $this->compileCondition($record, $params);

            if ($fragment === '') {
                continue;
            }

            if ($sql === '') {
                $sql = $fragment;
                continue;
            }

            $sql .= ' ' . $record['boolean'] . ' ' . $fragment;
        }

        return $sql;
    }

    /**
     * Render a single condition record.
     *
     * @param array<string,mixed> $record
     * @param list<mixed>         $params Bound parameters, appended in placeholder order
     *
     * @return string SQL fragment, or an empty string when the record contributes nothing
     */
    private function compileCondition(array $record, array &$params): string
    {
        $type = (string) $record['type'];

        if ($type === self::CONDITION_TYPE_EXPRESSION) {
            return (string) $record['value'];
        }

        if ($type === self::CONDITION_TYPE_GROUP) {
            $nested = $this->compileConditions((array) $record['nested'], $params);

            if ($nested === '') {
                return '';
            }

            return ($record['negate'] ? 'NOT (' : '(') . $nested . ')';
        }

        $column = SqlIdentifier::quote((string) $record['column']);

        if ($type === self::CONDITION_TYPE_NULL) {
            return $column . ' IS NULL';
        }

        if ($type === self::CONDITION_TYPE_NOT_NULL) {
            return $column . ' IS NOT NULL';
        }

        if ($type === self::CONDITION_TYPE_IN || $type === self::CONDITION_TYPE_NOT_IN) {
            $values = array_values((array) $record['value']);

            // An empty IN list is not valid SQL. Emit a constant of the correct truth value so the
            // query still runs and still means what the caller asked for.
            if ($values === []) {
                return $type === self::CONDITION_TYPE_IN ? '1 = 0' : '1 = 1';
            }

            foreach ($values as $value) {
                $params[] = $value;
            }

            $placeholders = implode(', ', array_fill(0, count($values), '?'));
            $prefix = $type === self::CONDITION_TYPE_NOT_IN ? 'NOT ' : '';

            return $column . ' ' . $prefix . 'IN (' . $placeholders . ')';
        }

        $operator = SqlIdentifier::operator((string) $record['operator']);
        $params[] = $record['value'];

        return $column . ' ' . $operator . ' ?';
    }

    /**
     * Append WHERE, GROUP BY, HAVING, ORDER BY, LIMIT, OFFSET, and LOCK
     * clauses to a base SQL query based on the provided options array.
     *
     * Supports AND conditions, OR conditions, raw WHERE expressions,
     * IN / NOT IN / IS NULL / IS NOT NULL operators, grouping, having,
     * raw ORDER BY, and row-level locking (FOR UPDATE / LOCK IN SHARE MODE).
     *
     * @param string|null $query  Base SQL query string
     * @param array       $options {
     *     @type array       $conditions     AND conditions (key => value or key => ['value' => v, 'operator' => op])
     *     @type array       $or_conditions  OR conditions
     *     @type array       $where_raw      Raw WHERE expressions with bindings
     *     @type array       $group_by       GROUP BY column expressions
     *     @type array       $having         HAVING clauses
     *     @type string      $order          ORDER BY column name
     *     @type string      $arrange        ORDER direction (ASC/DESC)
     *     @type array       $order_raw      Raw ORDER BY expressions
     *     @type int         $limit          Max rows to return
     *     @type int         $offset         Number of rows to skip
     *     @type string      $lock           Row-level lock clause
     * }
     *
     * @return array{query: string, parameters: array} Modified SQL and bound parameters
     */
    private function modifyQueryByOptions(?string $query = null, array $options = []): array
    {
        $params = [];

        if (!empty($options['conditions'])) {
            if (is_array($options['conditions'])) {
                $whereSql = $this->compileConditions($this->normalizeConditions($options['conditions']), $params);

                if ($whereSql !== '') {
                    $query .= " WHERE " . $whereSql;
                }
            } else {
                $query .= " WHERE " . $options['conditions'];
            }
        }

        if (!empty($options['or_conditions'])) {
            $orRecords = $this->normalizeConditions($options['or_conditions']);

            foreach ($orRecords as $index => $record) {
                $orRecords[$index]['boolean'] = 'OR';
            }

            $orSql = $this->compileConditions($orRecords, $params);

            if ($orSql !== '') {
                if (strpos($query, ' WHERE ') !== false) {
                    $query .= ' OR (' . $orSql . ')';
                } else {
                    $query .= ' WHERE (' . $orSql . ')';
                }
            }
        }

        if (!empty($options['where_raw'])) {
            foreach ($options['where_raw'] as $raw) {
                $boolean = strtoupper($raw['boolean'] ?? 'AND');
                if (strpos($query, ' WHERE ') !== false) {
                    $query .= " {$boolean} ({$raw['expression']})";
                } else {
                    $query .= " WHERE ({$raw['expression']})";
                }
                foreach ($raw['bindings'] as $b) {
                    $params[] = $b;
                }
            }
        }

        if (!empty($options['where_subqueries'])) {
            foreach ($options['where_subqueries'] as $ws) {
                $boolean = strtoupper($ws['boolean'] ?? 'AND');
                $operator = strtoupper($ws['operator']);
                if ($operator === 'EXISTS' || $operator === 'NOT EXISTS') {
                    $clause = "{$operator} ({$ws['sql']})";
                } else {
                    // The subquery body is a builder-produced expression and stays raw; the column
                    // and operator are caller input and are validated.
                    $quotedColumn = SqlIdentifier::quote($ws['column']);
                    $safeOperator = SqlIdentifier::operator($ws['operator']);
                    $clause = "{$quotedColumn} {$safeOperator} ({$ws['sql']})";
                }
                $query .= strpos($query, ' WHERE ') !== false
                    ? " {$boolean} {$clause}"
                    : " WHERE {$clause}";
                foreach ($ws['bindings'] as $b) {
                    $params[] = $b;
                }
            }
        }

        if (!empty($options['group_by'])) {
            $query .= ' GROUP BY ' . implode(', ', $options['group_by']);
        }

        if (!empty($options['having'])) {
            $havingParts = [];
            foreach ($options['having'] as $h) {
                if (isset($h['raw'])) {
                    $havingParts[] = $h['raw'];
                    foreach ($h['bindings'] as $b) {
                        $params[] = $b;
                    }
                } else {
                    $quotedColumn = SqlIdentifier::quote($h['column']);
                    $safeOperator = SqlIdentifier::operator($h['operator']);
                    $havingParts[] = "{$quotedColumn} {$safeOperator} ?";
                    $params[] = $h['value'];
                }
            }
            $query .= ' HAVING ' . implode(' AND ', $havingParts);
        }

        if (!empty($options['order'])) {
            // orderBy() stores the column already quoted. An options array assembled by hand may
            // still carry a bare name, so quote that here rather than trusting it.
            $order = (string) $options['order'];
            if (strpos($order, '`') === false) {
                $order = SqlIdentifier::quote($order);
            }

            $arrange = SqlIdentifier::direction($options['arrange'] ?? 'ASC');
            $options['arrange'] = $arrange;
            $query .= " ORDER BY {$order} {$arrange}";
        }

        if (!empty($options['order_raw'])) {
            $separator = strpos($query, ' ORDER BY ') !== false ? ', ' : ' ORDER BY ';
            $query .= $separator . implode(', ', $options['order_raw']);
        }

        if (!empty($options['limit'])) {
            $query .= " LIMIT " . (int) $options['limit'];
        }

        if (!empty($options['offset'])) {
            $query .= " OFFSET " . (int) $options['offset'];
        }

        if (!empty($options['lock'])) {
            $query .= ' ' . $options['lock'];
        }

        return ['query' => $query, 'parameters' => $params];
    }

    /**
     * Eager load related entities
     * 
     * @param array $relations Relations to eager load
     * 
     * @return $this
     */
    public function with(array $relations): static
    {
        if (empty($relations) || !self::$db) {
            return $this;
        }

        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this[$pkName] ?? null;

        if (empty($pkValue)) {
            return $this;
        }

        foreach ($relations as $relation) {
            if (!isset($metadata['relationships'][$relation])) {
                continue;
            }

            $relationshipData = $metadata['relationships'][$relation];
            $type = $relationshipData['type'];

            switch ($type) {
                case 'oneToMany':
                    (new RelationLoader($this))->eagerLoadOneToMany($relation, $relationshipData);
                    break;
                case 'manyToOne':
                    (new RelationLoader($this))->eagerLoadManyToOne($relation, $relationshipData);
                    break;
                case 'oneToOne':
                    (new RelationLoader($this))->eagerLoadOneToOne($relation, $relationshipData);
                    break;
                case 'manyToMany':
                    (new RelationLoader($this))->eagerLoadManyToMany($relation, $relationshipData);
                    break;
            }
        }

        return $this;
    }










    /**
     * Eager load relationships for a collection of entities.
     *
     * Kept here because application code calls it as Model::eagerLoadForCollection();
     * the work is RelationLoader's.
     *
     * @param array<int, static> $entities  Entities to load relationships onto.
     * @param string[]           $relations Relationship property names.
     *
     * @return array<int, static>|object
     */
    public static function eagerLoadForCollection(array $entities, array $relations): array|object
    {
        return RelationLoader::eagerLoadForCollection($entities, $relations);
    }

    /**
     * Enhanced version of findAll with eager loading capabilities
     * 
     * @param array $options Query options
     * @param array $with Relations to eager load
     * 
     * @return array
     */
    public function findAllWith($options = [], $with = []): array
    {
        $entities = $this->findAll($options);

        if (!empty($entities) && !empty($with)) {
            $entities = RelationLoader::eagerLoadForCollection($entities, $with);
        }

        return $entities;
    }

    /**
     * Enhanced version of find with eager loading capabilities
     * 
     * @param mixed $id Primary key value
     * @param array $options Query options
     * @param array $with Relations to eager load
     * @return object|null
     */
    public function findWith($id, $options = [], $with = []): object|null
    {
        $entity = $this->find($id, $options);

        if ($entity && !empty($with)) {
            $entity->with($with);
        }

        return $entity;
    }

    /**
     * Begin database transaction
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->beginTransaction();
    }

    /**
     * Commit database transaction
     * @return bool
     */
    public static function commit(): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->commit();
    }

    /**
     * Rollback database transaction
     * @return bool
     */
    public static function rollback(): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->rollBack();
    }

    /**
     * Execute a callback within a database transaction.
     *
     * Begins a transaction, executes the callback, and commits on success.
     * If a deadlock or serialization error occurs, the entire transaction
     * is automatically retried with exponential backoff (up to
     * $transactionRetries attempts). Other exceptions are re-thrown
     * immediately after rollback.
     *
     * @template TReturn
     * @param callable(): TReturn $callback Business logic to execute atomically
     * @param int|null $retries   Override default retry count (null = use class default)
     *
     * @return TReturn The callback's return value
     * @throws \Throwable Re-throws any non-retryable exception after rollback
     */
    public static function transaction(callable $callback, ?int $retries = null): mixed
    {
        $maxRetries = $retries ?? static::$transactionRetries;
        $lastException = null;

        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            self::beginTransaction();
            try {
                $result = $callback();
                self::commit();
                return $result;
            } catch (\Throwable $e) {
                self::rollback();
                $lastException = $e;

                // Only retry on deadlock / serialization errors
                if (!self::isDeadlockException($e) || $attempt >= $maxRetries) {
                    throw $e;
                }

                // Exponential backoff with jitter
                $delay = static::$transactionRetryDelayUs * (2 ** $attempt);
                $jitter = (int) ($delay * 0.25 * (mt_rand() / mt_getrandmax() * 2 - 1));
                usleep(max(0, $delay + $jitter));
            }
        }

        throw $lastException;
    }

    /**
     * Determine if an exception represents a deadlock or serialization error.
     *
     * @param \Throwable $e
     * @return bool
     */
    private static function isDeadlockException(\Throwable $e): bool
    {
        $code = (string) $e->getCode();
        if (in_array($code, ['40001', '40P01'], true)) {
            return true;
        }
        $msg = strtolower($e->getMessage());
        return str_contains($msg, 'deadlock')
            || str_contains($msg, 'lock wait timeout')
            || str_contains($msg, 'serialization failure');
    }

    /**
     * Insert multiple entities in bulk using chunked multi-row INSERT statements.
     *
     * Splits the entities into chunks of $batchChunkSize (default 500) to
     * avoid exceeding MySQL's max_allowed_packet. Each chunk is a single
     * multi-row INSERT, and the entire operation is wrapped in a transaction
     * for atomicity and performance (reduces fsync overhead).
     *
     * @param list<static> $entities Array of entity instances to insert
     * @param int|null     $chunkSize Override chunk size (null = use class default)
     *
     * @return int Total number of affected rows
     * @throws Exception When database connection not set or entities array is empty
     */
    public static function batchInsert(array $entities, ?int $chunkSize = null): int
    {
        if (empty($entities) || !self::$db) {
            return 0;
        }

        $first = reset($entities);
        $metadata = self::$reflectionCache[get_class($first)] ?? null;
        if (!$metadata) {
            throw new Exception("Metadata not found");
        }

        $tableName = $metadata['table'];
        $columns = $metadata['columns'];
        $pkName = $metadata['primaryKey'];

        $columnNames = [];
        $columnKeys = [];
        foreach ($columns as $property => $columnInfo) {
            if ($property !== $pkName || !$first->getIncrementing()) {
                $columnNames[] = "`{$columnInfo['name']}`";
                $columnKeys[] = $property;
            }
        }

        $chunk = $chunkSize ?? static::$batchChunkSize;
        $totalAffected = 0;
        $entityChunks = array_chunk($entities, $chunk);

        $useTransaction = !self::$db->inTransaction();
        if ($useTransaction) {
            self::$db->beginTransaction();
        }

        try {
            foreach ($entityChunks as $entityGroup) {
                $placeholderRow = '(' . implode(',', array_fill(0, count($columnNames), '?')) . ')';
                $allPlaceholders = implode(',', array_fill(0, count($entityGroup), $placeholderRow));

                $sql = "INSERT INTO `{$tableName}` (" . implode(',', $columnNames) . ") VALUES {$allPlaceholders}";

                $values = [];
                foreach ($entityGroup as $entity) {
                    $entityData = $entity->getArrayCopy();
                    foreach ($columnKeys as $key) {
                        $colType = $columns[$key]['type'] ?? 'string';
                        $val = $entityData[$key] ?? null;
                        $values[] = $entity->prepareValueForDatabase($val, $colType);
                    }
                }

                $stmt = self::$db->prepare($sql);
                $stmt->execute($values);
                $totalAffected += $stmt->rowCount();
            }

            if ($useTransaction) {
                self::$db->commit();
            }
        } catch (\Throwable $e) {
            if ($useTransaction) {
                self::$db->rollBack();
            }
            throw $e;
        }

        return $totalAffected;
    }

    /**
     * Bulk upsert: INSERT ... ON DUPLICATE KEY UPDATE.
     *
     * Inserts entities in bulk; if a unique key conflict occurs, the
     * specified columns are updated instead. This is far more efficient
     * than individual updateOrCreate() calls under high write volume.
     *
     * @param list<static> $entities      Entities to upsert
     * @param string[]     $updateColumns Column properties to update on conflict
     * @param int|null     $chunkSize     Override chunk size
     *
     * @return int Total affected rows (1 = inserted, 2 = updated per MySQL convention)
     * @throws Exception
     */
    public static function batchUpsert(array $entities, array $updateColumns, ?int $chunkSize = null): int
    {
        if (empty($entities) || !self::$db) {
            return 0;
        }

        $first = reset($entities);
        $metadata = self::$reflectionCache[get_class($first)] ?? null;
        if (!$metadata) {
            throw new Exception("Metadata not found");
        }

        $tableName = $metadata['table'];
        $columns = $metadata['columns'];

        // All column names for INSERT
        $columnNames = [];
        $columnKeys = [];
        foreach ($columns as $property => $columnInfo) {
            $columnNames[] = "`{$columnInfo['name']}`";
            $columnKeys[] = $property;
        }

        // Build ON DUPLICATE KEY UPDATE clause
        $updateParts = [];
        foreach ($updateColumns as $prop) {
            if (isset($columns[$prop])) {
                $colName = $columns[$prop]['name'];
                $updateParts[] = "`{$colName}` = VALUES(`{$colName}`)";
            }
        }

        if (empty($updateParts)) {
            throw new Exception("No valid update columns specified for upsert");
        }

        $onDuplicateSql = " ON DUPLICATE KEY UPDATE " . implode(', ', $updateParts);

        $chunk = $chunkSize ?? static::$batchChunkSize;
        $totalAffected = 0;
        $entityChunks = array_chunk($entities, $chunk);

        $useTransaction = !self::$db->inTransaction();
        if ($useTransaction) {
            self::$db->beginTransaction();
        }

        try {
            foreach ($entityChunks as $entityGroup) {
                $placeholderRow = '(' . implode(',', array_fill(0, count($columnNames), '?')) . ')';
                $allPlaceholders = implode(',', array_fill(0, count($entityGroup), $placeholderRow));

                $sql = "INSERT INTO `{$tableName}` (" . implode(',', $columnNames)
                    . ") VALUES {$allPlaceholders}" . $onDuplicateSql;

                $values = [];
                foreach ($entityGroup as $entity) {
                    $entityData = $entity->getArrayCopy();
                    foreach ($columnKeys as $key) {
                        $colType = $columns[$key]['type'] ?? 'string';
                        $val = $entityData[$key] ?? null;
                        $values[] = $first->prepareValueForDatabase($val, $colType);
                    }
                }

                $stmt = self::$db->prepare($sql);
                $stmt->execute($values);
                $totalAffected += $stmt->rowCount();
            }

            if ($useTransaction) {
                self::$db->commit();
            }
        } catch (\Throwable $e) {
            if ($useTransaction) {
                self::$db->rollBack();
            }
            throw $e;
        }

        return $totalAffected;
    }

    /**
     * Batch update entities
     * 
     * @param array $conditions
     * @param array $data
     * 
     * @return int
     */
    public function batchUpdate(array $conditions, array $data): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $setParts = [];
        $params = [];
        foreach ($data as $property => $value) {
            if (isset($metadata['columns'][$property])) {
                $columnName = $metadata['columns'][$property]['name'];
                $setParts[] = "`{$columnName}` = ?";
                $params[] = $value;
            }
        }

        $whereParts = [];
        foreach ($conditions as $property => $value) {
            if (isset($metadata['columns'][$property])) {
                $columnName = $metadata['columns'][$property]['name'];
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $whereParts[] = "`{$columnName}` IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    $whereParts[] = "`{$columnName}` = ?";
                    $params[] = $value;
                }
            }
        }

        $sql = "UPDATE `{$tableName}` SET " . implode(',', $setParts);
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Batch delete entities
     * 
     * @param array $conditions
     * 
     * @return int
     */
    public function batchDelete(array $conditions): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $whereParts = [];
        $params = [];
        foreach ($conditions as $property => $value) {
            if (isset($metadata['columns'][$property])) {
                $columnName = $metadata['columns'][$property]['name'];
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $whereParts[] = "`{$columnName}` IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    $whereParts[] = "`{$columnName}` = ?";
                    $params[] = $value;
                }
            }
        }

        $sql = "DELETE FROM `{$tableName}`";
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Enable soft delete
     * 
     * @param string $column
     * 
     * @return static
     */
    public function enableSoftDelete(string $column = 'deleted_at'): static
    {
        $this->softDeleteColumn = $column;
        return $this;
    }

    /**
     * Perform a soft delete on this entity.
     *
     * Sets the configured soft delete column to the current timestamp and
     * persists the change. After soft deletion, the entity remains in the
     * database but is excluded from default queries. Use restore() to
     * reverse a soft delete.
     *
     * @return bool True if the entity was successfully soft-deleted
     * @throws Exception When soft delete is not enabled or entity has no primary key
     */
    public function softDelete(): bool
    {
        if (!$this->softDeleteColumn) {
            throw new Exception("Soft delete not enabled");
        }

        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];

        if (empty($this[$pkName])) {
            throw new Exception("Cannot soft delete entity without primary key");
        }

        $this[$this->softDeleteColumn] = date('Y-m-d H:i:s');
        return $this->save() !== null;
    }

    /**
     * Restore a soft-deleted entity by clearing its soft delete timestamp.
     *
     * Sets the soft delete column to NULL and persists the change.
     * Fires 'restoring' and 'restored' event hooks if registered.
     *
     * @return bool True if the entity was successfully restored
     * @throws Exception When soft delete is not enabled
     */
    public function restore(): bool
    {
        if (!$this->softDeleteColumn) {
            throw new Exception("Soft delete not enabled");
        }

        $this[$this->softDeleteColumn] = null;
        return $this->save() !== null;
    }

    /**
     * Find all including soft deleted
     * 
     * @param array $options
     * 
     * @return array
     */
    public function findAllWithTrashed($options = []): array
    {
        return $this->findAll($options);
    }

    /**
     * Find only soft deleted
     * 
     * @param array $options
     * 
     * @return array
     */
    public function findOnlyTrashed($options = []): array
    {
        if (!$this->softDeleteColumn) {
            return [];
        }

        $metadata = $this->getMetadata();
        if (!isset($metadata['columns'][$this->softDeleteColumn])) {
            return [];
        }

        $columnName = $metadata['columns'][$this->softDeleteColumn]['name'];
        $options['conditions'] = $options['conditions'] ?? [];
        $options['conditions'][] = "`{$columnName}` IS NOT NULL";

        return $this->findAll($options);
    }

    /**
     * Register event hook
     * 
     * @param string $event
     * @param callable $callback
     * 
     * @return void
     */
    public static function on(string $event, callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }

        if (!isset(self::$eventHooks[$class][$event])) {
            self::$eventHooks[$class][$event] = [];
        }

        self::$eventHooks[$class][$event][] = $callback;
    }

    /**
     * Trigger event
     * 
     * @param string $event
     * @param mixed $data
     * 
     * @return bool
     */
    protected function trigger(string $event, $data = null): bool
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class][$event])) {
            return true;
        }

        foreach (self::$eventHooks[$class][$event] as $callback) {
            $result = $callback($this, $data);
            if ($result === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save with events
     * 
     * @param array $debug
     * 
     * @return static|null
     */
    public function saveWithEvents($debug = []): ActiveRecord|null
    {
        $isNew = !$this->exists;

        if ($isNew) {
            if (!$this->trigger('creating')) {
                return null;
            }
        } else {
            if (!$this->trigger('updating')) {
                return null;
            }
        }

        if (!$this->trigger('saving')) {
            return null;
        }

        $result = $this->save($debug);

        if ($isNew) {
            $this->trigger('created');
        } else {
            $this->trigger('updated');
        }

        $this->trigger('saved');

        return $result;
    }

    /**
     * Delete with events
     * 
     * @return bool
     */
    public function deleteWithEvents(): bool
    {
        if (!$this->trigger('deleting')) {
            return false;
        }

        $result = $this->delete();

        $this->trigger('deleted');

        return $result;
    }

    /**
     * Query builder instance
     * 
     * @return QueryBuilder
     */
    public function query(): QueryBuilder
    {
        return new QueryBuilder($this);
    }

    /**
     * Create new query builder for static context
     * 
     * @return QueryBuilder
     */
    public static function newQuery(): QueryBuilder
    {
        $class = static::class;
        return new QueryBuilder(new $class());
    }

    /**
     * Paginate query results with metadata.
     *
     * Executes a COUNT query for the total, then fetches the requested page
     * slice. Returns an ArrayObject with pagination metadata compatible
     * with common frontend pagination components.
     *
     * @param int   $perPage  Number of records per page (default 15)
     * @param int   $page     Current page number (1-based, default 1)
     * @param array $options  Additional query options (conditions, order, etc.)
     *
     * @return array{
     *  data: array, 
     *  total: int, 
     *  per_page: int, 
     *  current_page: int, 
     *  last_page: int, 
     *  has_more: bool,
     *  from: int,
     *  to: mixed
     * }|ArrayObject Pagination result with keys: data, total, per_page,
     *               current_page, last_page, has_more, from, to
     * @phpstan-param QueryOptions $options
     * @phpstan-return ArrayObject<string, mixed>&PaginationResult
     */
    protected function paginate(int $perPage = 15, int $page = 1, array $options = []): ArrayObject
    {
        $total = $this->count($options);
        $totalPages = (int) ceil($total / $perPage);
        $page = max(1, min($page, $totalPages ?: 1));

        $options['limit'] = $perPage;
        $options['offset'] = ($page - 1) * $perPage;

        $items = $this->findAll($options);

        return new ArrayObject([
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $totalPages,
            'has_more' => $page < $totalPages,
            'from' => $total > 0 ? ($page - 1) * $perPage + 1 : 0,
            'to' => min($page * $perPage, $total)
        ], ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get first record
     * 
     * @param array $options
     * 
     * @return object|null
     */
    protected function first($options = []): mixed
    {
        if (empty($options) && $this->activeQuery->hasConditions()) {
            $options = $this->buildQueryOptions();
        }

        $options['limit'] = 1;
        $results = $this->findAll($options);
        $this->resetQuery();
        return $results[0] ?? null;
    }

    /**
     * Get last record
     * 
     * @param array $options
     * 
     * @return object|null
     */
    protected function last($options = []): mixed
    {
        $metadata = $this->getMetadata();
        $pkColumn = $metadata['columns'][$metadata['primaryKey']]['name'];
        $options['order'] = $pkColumn;
        $options['arrange'] = 'DESC';
        $options['limit'] = 1;
        $results = $this->findAll($options);
        return $results[0] ?? null;
    }

    /**
     * Check whether at least one record matching the conditions exists.
     *
     * Uses SELECT 1 ... LIMIT 1 instead of COUNT(*) for better performance —
     * the database can stop scanning after finding the first match rather
     * than counting every matching row.
     *
     * @param array<string, mixed> $conditions Column name => value pairs
     *
     * @return bool True if at least one matching record exists
     */
    public function exists(array $conditions = []): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $sql = "SELECT 1 FROM `{$tableName}`";
        $options = [];
        if (!empty($conditions)) {
            $options['conditions'] = $conditions;
        }
        $options['limit'] = 1;

        $modified = $this->modifyQueryByOptions($sql, $options);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);

        return $stmt->fetch(PDO::FETCH_NUM) !== false;
    }

    /**
     * Create a new entity instance, fill it with attributes, and persist to the database.
     *
     * Fires 'creating' and 'created' event hooks. Respects fillable/guarded
     * mass-assignment protection defined on the entity class.
     *
     * @param array<string, mixed> $attributes Column name => value pairs to fill
     *
     * @return static The persisted entity instance with auto-generated primary key
     * @phpstan-param array<string, mixed> $attributes
     */
    public static function create(array $attributes): static
    {
        $instance = new static();
        $instance->fill($attributes);
        $instance->saveWithEvents();
        return $instance;
    }

    /**
     * Find the first entity matching the given attributes, or create and persist a new one.
     *
     * Race-condition safe: catches duplicate key errors from concurrent
     * inserts and falls back to a SELECT, ensuring idempotent behavior.
     *
     * @param array<string, mixed> $attributes Unique lookup conditions
     * @param array<string, mixed> $values     Additional attributes to set on creation
     *
     * @return static Found or newly created entity
     */
    public static function firstOrCreate(array $attributes, array $values = []): static
    {
        $instance = new static();
        $conditions = [];
        foreach ($attributes as $key => $value) {
            $conditions[$key] = $value;
        }

        $entity = $instance->first(['conditions' => $conditions]);

        if ($entity) {
            return $entity;
        }

        try {
            $newEntity = new static();
            foreach (array_merge($attributes, $values) as $key => $value) {
                $newEntity[$key] = $value;
            }
            $newEntity->save();
            return $newEntity;
        } catch (\Throwable $e) {
            // Duplicate key = race condition; re-fetch
            $msg = strtolower($e->getMessage());
            $code = (string) $e->getCode();
            if (str_contains($msg, 'duplicate') || $code === '23000' || $code === '23505') {
                $entity = $instance->first(['conditions' => $conditions]);
                if ($entity) {
                    return $entity;
                }
            }
            throw $e;
        }
    }

    /**
     * Update or create entity (race-condition safe).
     *
     * Under high concurrency, two processes may simultaneously find that
     * no record exists and both attempt to INSERT, causing a duplicate key
     * error. This implementation catches that error and retries as an UPDATE,
     * ensuring exactly one record exists after completion.
     *
     * @param array $attributes  Unique lookup conditions
     * @param array $values      Columns to set/update
     *
     * @return static
     */
    public static function updateOrCreate(array $attributes, array $values = []): static
    {
        $instance = new static();
        $conditions = [];
        foreach ($attributes as $key => $value) {
            $conditions[$key] = $value;
        }

        $entity = $instance->first(['conditions' => $conditions]);

        if ($entity) {
            foreach ($values as $key => $value) {
                $entity[$key] = $value;
            }
            $entity->save();
            return $entity;
        }

        // Attempt INSERT; retry as UPDATE on duplicate key (race condition)
        try {
            $newEntity = new static();
            foreach (array_merge($attributes, $values) as $key => $value) {
                $newEntity[$key] = $value;
            }
            $newEntity->save();
            return $newEntity;
        } catch (\Throwable $e) {
            // Check for duplicate key error (MySQL 1062, PostgreSQL 23505)
            $msg = strtolower($e->getMessage());
            $code = (string) $e->getCode();
            if (str_contains($msg, 'duplicate') || $code === '23000' || $code === '23505') {
                // Race condition: another process inserted first. Retry as UPDATE.
                $entity = $instance->first(['conditions' => $conditions]);
                if ($entity) {
                    foreach ($values as $key => $value) {
                        $entity[$key] = $value;
                    }
                    $entity->save();
                    return $entity;
                }
            }
            throw $e;
        }
    }

    /**
     * Register creating event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function creating(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }
        if (!isset(self::$eventHooks[$class]['creating'])) {
            self::$eventHooks[$class]['creating'] = [];
        }
        self::$eventHooks[$class]['creating'][] = $callback;
    }

    /**
     * Register created event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function created(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }
        if (!isset(self::$eventHooks[$class]['created'])) {
            self::$eventHooks[$class]['created'] = [];
        }
        self::$eventHooks[$class]['created'][] = $callback;
    }

    /**
     * Register updating event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function updating(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }
        if (!isset(self::$eventHooks[$class]['updating'])) {
            self::$eventHooks[$class]['updating'] = [];
        }
        self::$eventHooks[$class]['updating'][] = $callback;
    }

    /**
     * Register updated event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function updated(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }
        if (!isset(self::$eventHooks[$class]['updated'])) {
            self::$eventHooks[$class]['updated'] = [];
        }
        self::$eventHooks[$class]['updated'][] = $callback;
    }

    /**
     * Register saving event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function saving(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }
        if (!isset(self::$eventHooks[$class]['saving'])) {
            self::$eventHooks[$class]['saving'] = [];
        }
        self::$eventHooks[$class]['saving'][] = $callback;
    }

    /**
     * Register saved event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function saved(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }
        if (!isset(self::$eventHooks[$class]['saved'])) {
            self::$eventHooks[$class]['saved'] = [];
        }
        self::$eventHooks[$class]['saved'][] = $callback;
    }

    /**
     * Register deleting event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function deleting(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }
        if (!isset(self::$eventHooks[$class]['deleting'])) {
            self::$eventHooks[$class]['deleting'] = [];
        }
        self::$eventHooks[$class]['deleting'][] = $callback;
    }

    /**
     * Register deleted event hook
     * 
     * @param callable $callback
     * 
     * @return void
     */
    public static function deleted(callable $callback): void
    {
        $class = static::class;
        if (!isset(self::$eventHooks[$class])) {
            self::$eventHooks[$class] = [];
        }

        if (!isset(self::$eventHooks[$class]['deleted'])) {
            self::$eventHooks[$class]['deleted'] = [];
        }

        self::$eventHooks[$class]['deleted'][] = $callback;
    }

    /**
     * Atomically increment a column value.
     *
     * Uses `SET column = column + ?` instead of fetching and re-setting,
     * making it safe under concurrent access without explicit locking.
     *
     * @param string $column Column property name
     * @param int    $amount Amount to add (negative to subtract)
     *
     * @return bool True on success
     */
    public function increment(string $column, int $amount = 1): bool
    {
        $metadata = $this->getMetadata();
        if (!isset($metadata['columns'][$column])) {
            throw new Exception("Column {$column} not found");
        }

        $columnName = $metadata['columns'][$column]['name'];
        $pkName = $metadata['primaryKey'];
        $pkColumn = $metadata['columns'][$pkName]['name'];
        $tableName = $metadata['table'];

        // Atomic increment — safe under concurrent writes
        $sql = "UPDATE `{$tableName}` SET `{$columnName}` = `{$columnName}` + ? WHERE `{$pkColumn}` = ?";
        $stmt = self::$db->prepare($sql);
        $result = $stmt->execute([$amount, $this[$pkName]]);

        if ($result) {
            // Update local value (best-effort; may drift under concurrency)
            $this->offsetSet($column, (int) ($this[$column] ?? 0) + $amount);
        }

        return $result;
    }

    /**
     * Decrement column value
     * 
     * @param string $column
     * @param int $amount
     * 
     * @return bool
     */
    public function decrement(string $column, int $amount = 1): bool
    {
        return $this->increment($column, -$amount);
    }

    /**
     * Refresh entity from database
     * 
     * @return $this|null
     */
    public function refresh(): ActiveRecord|null
    {
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];

        if (empty($this[$pkName])) {
            return null;
        }

        $fresh = $this->find($this[$pkName]);
        if ($fresh) {
            foreach ($metadata['columns'] as $property => $columnInfo) {
                $this[$property] = $fresh[$property] ?? null;
            }
            $this->syncOriginal();
            $this->dirtyFields = [];
        }

        return $this;
    }

    /**
     * Create a shallow clone of this entity without the primary key.
     *
     * Returns a new, unsaved entity instance with all attribute values copied
     * from this entity except the primary key and any additional keys listed
     * in $except. Useful for duplicating records.
     *
     * @param list<string> $except Additional property names to exclude from the clone
     *
     * @return static New unsaved entity instance
     * @phpstan-param list<string> $except
     */
    public function replicate(array $except = []): object
    {
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $except[] = $pkName;

        $class = static::class;
        $clone = new $class();

        foreach ($metadata['columns'] as $property => $columnInfo) {
            if (!in_array($property, $except)) {
                $clone[$property] = $this[$property] ?? null;
            }
        }

        return $clone;
    }

    /**
     * Convert entity to an associative array representation.
     *
     * Iterates over all mapped columns, excludes any fields listed in
     * the $hidden property or passed via the $hidden parameter, and appends
     * any computed accessor attributes defined in the $appends property.
     *
     * @param list<string> $hidden Additional field names to exclude from output
     *
     * @return array<string, mixed> Associative array keyed by property names
     * @phpstan-param list<string> $hidden
     * @phpstan-return array<string, mixed>
     */
    public function toArray(array $hidden = []): array
    {
        $metadata = $this->getMetadata();
        $result = [];
        $hidden = array_merge($hidden, $this->hidden);
        $storedData = $this->getArrayCopy();

        foreach ($metadata['columns'] as $property => $columnInfo) {
            if (!in_array($property, $hidden)) {
                $result[$property] = $storedData[$property] ?? null;
            }
        }

        foreach ($this->appends as $append) {
            $accessor = 'get' . str_replace('_', '', ucwords($append, '_')) . 'Attribute';
            if (method_exists($this, $accessor)) {
                $result[$append] = $this->$accessor();
            }
        }

        return $result;
    }

    /**
     * Serialize entity to a JSON string.
     *
     * Delegates to toArray() and encodes the result. By default uses
     * JSON_UNESCAPED_UNICODE to preserve multibyte characters.
     *
     * @param list<string> $hidden Additional field names to exclude
     * @param int           $flags  json_encode() option bitmask
     *
     * @return string JSON-encoded entity representation
     * @phpstan-param list<string> $hidden
     * @phpstan-param int-mask-of<JSON_*> $flags
     */
    public function toJson(array $hidden = [], int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return json_encode($this->toArray($hidden), $flags);
    }

    /**
     * Mass-assign attributes from an associative array.
     *
     * Respects the fillable whitelist and guarded blacklist. Only keys that
     * correspond to mapped columns and pass mass-assignment protection are
     * set on the entity. Values are stored via offsetSet(), which also
     * triggers dirty field tracking for persisted entities.
     *
     * @param array<string, mixed> $data Property name => value pairs
     *
     * @return static This entity instance for method chaining
     * @phpstan-param array<string, mixed> $data
     */
    public function fill(array $data): static
    {
        $metadata = $this->getMetadata();

        foreach ($data as $key => $value) {
            if (isset($metadata['columns'][$key])) {
                if (!empty($this->fillable)) {
                    if (!in_array($key, $this->fillable)) {
                        continue;
                    }
                } else {
                    if (!empty($this->guarded) && ($this->guarded[0] === '*' || in_array($key, $this->guarded))) {
                        continue;
                    }
                }
                $this[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Mass-assign attributes ignoring fillable/guarded mass-assignment protection.
     *
     * Use with caution — this bypasses all mass-assignment guards. Intended
     * for internal framework use, seeders, or administrative operations.
     *
     * @param array<string, mixed> $data Property name => value pairs
     *
     * @return static This entity instance for method chaining
     * @phpstan-param array<string, mixed> $data
     */
    public function forceFill(array $data): static
    {
        $metadata = $this->getMetadata();
        foreach ($data as $key => $value) {
            if (isset($metadata['columns'][$key])) {
                $this[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Get all fields that have been modified since the last sync.
     *
     * Returns an associative array of property names and their new values.
     * Only populated when the entity has exists=true (i.e., was loaded
     * from the database or has been saved at least once).
     *
     * @return array<string, mixed> Property name => modified value pairs
     * @phpstan-return array<string, mixed>
     */
    public function getDirty(): array
    {
        return $this->dirtyFields ?? [];
    }

    /**
     * Determine whether the entity (or a specific attribute) has been modified.
     *
     * When $attribute is null, checks if any field has been modified.
     * When $attribute is specified, checks only that specific field.
     *
     * @param string|null $attribute Optional specific attribute name to check
     *
     * @return bool True if the entity (or attribute) has unsaved changes
     */
    public function isDirty(?string $attribute = null): bool
    {
        if ($attribute === null) {
            return !empty($this->dirtyFields);
        }

        return array_key_exists($attribute, $this->dirtyFields);
    }

    /**
     * Execute a raw SQL SELECT query and return associative array results.
     *
     * Use this for complex queries that cannot be expressed through the
     * query builder. Parameters are bound via PDO prepared statements
     * to prevent SQL injection.
     *
     * @param string       $sql    Raw SQL query string with ? placeholders
     * @param list<mixed>  $params Positional parameter values
     *
     * @return list<array<string, mixed>> Array of associative result rows
     * @phpstan-param list<mixed> $params
     * @phpstan-return list<array<string, mixed>>
     * @throws Exception When database connection not set
     */
    public static function rawQuery(string $sql, array $params = []): array
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a raw SQL statement (INSERT, UPDATE, DELETE) and return affected row count.
     *
     * Parameters are bound via PDO prepared statements to prevent SQL injection.
     *
     * @param string       $sql    Raw SQL statement with ? placeholders
     * @param list<mixed>  $params Positional parameter values
     *
     * @return int Number of affected rows
     * @phpstan-param list<mixed> $params
     * @throws Exception When database connection not set
     */
    public static function rawExecute(string $sql, array $params = []): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Get database connection
     * 
     * @return ConnectionPool|null
     */
    public static function getConnection(): ?ConnectionPool
    {
        return self::$db;
    }

    /**
     * Configure the connection pool for high-traffic scenarios.
     *
     * Provides a single entry point to tune all pool-level settings:
     * circuit breaker cooldown, retry policy, statement cache size,
     * health checks, and ActiveRecord-level batch/retry configuration.
     *
     * @param array{
     *   circuit_breaker_cooldown?: int,
     *   health_check_enabled?: bool,
     *   max_retries?: int,
     *   retry_base_delay_us?: int,
     *   stmt_cache_limit?: int,
     *   batch_chunk_size?: int,
     *   transaction_retries?: int,
     *   transaction_retry_delay_us?: int,
     *   query_cache_limit?: int,
     *   query_log_limit?: int
     * } $config
     *
     * @return void
     */
    public static function configurePool(array $config): void
    {
        if (self::$db) {
            if (isset($config['circuit_breaker_cooldown'])) {
                self::$db->setCircuitBreakerCooldown($config['circuit_breaker_cooldown']);
            }
            if (isset($config['health_check_enabled'])) {
                self::$db->setHealthCheckEnabled($config['health_check_enabled']);
            }
            if (isset($config['max_retries'])) {
                self::$db->setRetryPolicy(
                    $config['max_retries'],
                    $config['retry_base_delay_us'] ?? 50_000
                );
            }
            if (isset($config['stmt_cache_limit'])) {
                self::$db->setStmtCacheLimit($config['stmt_cache_limit']);
            }
        }

        if (isset($config['batch_chunk_size'])) {
            static::$batchChunkSize = $config['batch_chunk_size'];
        }
        if (isset($config['transaction_retries'])) {
            static::$transactionRetries = $config['transaction_retries'];
        }
        if (isset($config['transaction_retry_delay_us'])) {
            static::$transactionRetryDelayUs = $config['transaction_retry_delay_us'];
        }
        if (isset($config['query_cache_limit'])) {
            QueryResultCache::configureLimit($config['query_cache_limit']);
        }
        if (isset($config['query_log_limit'])) {
            QueryLog::configureLimit($config['query_log_limit']);
        }
    }

    /**
     * Apply a pre-tuned high-traffic configuration profile.
     *
     * Configures all pool and ActiveRecord settings for optimal performance
     * under sustained high load: larger caches, more retries, bigger batches,
     * and circuit breaker protection.
     *
     * @return void
     */
    public static function configureHighTraffic(): void
    {
        self::configurePool([
            'circuit_breaker_cooldown' => 15,
            'health_check_enabled' => false,  // Rely on circuit breaker
            'max_retries' => 5,
            'retry_base_delay_us' => 25_000,
            'stmt_cache_limit' => 1000,
            'batch_chunk_size' => 1000,
            'transaction_retries' => 5,
            'transaction_retry_delay_us' => 25_000,
            'query_cache_limit' => 5000,
            'query_log_limit' => 10000,
        ]);
    }

    /**
     * Aggregate functions - Sum
     * 
     * @param string $column
     * @param array $conditions
     * 
     * @return float
     */
    public function sum(string $column, array $conditions = []): float
    {
        return (float) $this->aggregate('SUM', $column, $conditions);
    }

    /**
     * Aggregate functions - Avg
     * 
     * @param string $column
     * @param array $conditions
     * 
     * @return float
     */
    public function avg(string $column, array $conditions = []): float
    {
        return (float) $this->aggregate('AVG', $column, $conditions);
    }

    /**
     * Aggregate functions - Min
     * 
     * @param string $column
     * @param array $conditions
     * 
     * @return mixed
     */
    public function min(string $column, array $conditions = []): mixed
    {
        return $this->aggregate('MIN', $column, $conditions);
    }

    /**
     * Aggregate functions - Max
     * 
     * @param string $column
     * @param array $conditions
     * 
     * @return mixed
     */
    public function max(string $column, array $conditions = []): mixed
    {
        return $this->aggregate('MAX', $column, $conditions);
    }

    /**
     * Generic aggregate function
     * 
     * @param string $function
     * @param string $column
     * @param array $conditions
     * 
     * @return mixed
     */
    private function aggregate(string $function, string $column, array $conditions = []): mixed
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        if (!isset($metadata['columns'][$column])) {
            throw new Exception("Column {$column} not found");
        }

        $columnName = $metadata['columns'][$column]['name'];
        $sql = "SELECT {$function}(`{$columnName}`) as result FROM `{$tableName}`";

        $modified = $this->modifyQueryByOptions($sql, ['conditions' => $conditions]);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['result'] ?? null;
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param string $column
     * @param string|null $key
     * @return array
     */
    public function pluck(string $column, ?string $key = null): array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        if (!isset($metadata['columns'][$column])) {
            throw new Exception("Column {$column} not found");
        }

        $columnName = $metadata['columns'][$column]['name'];
        $keyColumn = ($key !== null && isset($metadata['columns'][$key])) ? $metadata['columns'][$key]['name'] : null;

        $select = $keyColumn ? "`{$keyColumn}`, `{$columnName}`" : "`{$columnName}`";
        $sql = "SELECT {$select} FROM `{$tableName}`";

        $stmt = self::$db->prepare($sql);
        $stmt->execute();

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($keyColumn) {
                $result[$row[$keyColumn]] = $row[$columnName];
            } else {
                $result[] = $row[$columnName];
            }
        }

        return $result;
    }

    /**
     * Process query results in fixed-size chunks to limit memory usage.
     *
     * Fetches $count records at a time via paginate(), passes each chunk
     * to the callback, and continues until all records are processed or
     * the callback returns false.
     *
     * @param int      $count    Number of records per chunk
     * @param callable $callback fn(list<static> $items, int $page): bool|void — return false to stop
     * @param array    $options  Additional query options
     *
     * @return bool False if the callback stopped iteration early, true if all chunks processed
     * @phpstan-param positive-int $count
     * @phpstan-param callable(list<static>, int): (bool|void) $callback
     * @phpstan-param QueryOptions $options
     */
    public function chunk(int $count, callable $callback, array $options = []): bool
    {
        $page = 1;
        do {
            $results = $this->paginate($count, $page, $options);
            $items = $results['data'];
            if (empty($items)) {
                break;
            }

            if ($callback($items, $page) === false) {
                return false;
            }

            $page++;
        } while ($results['has_more']);

        return true;
    }

    /**
     * Iterate over each record individually
     * 
     * @param callable $callback Function receiving single entity, return false to stop
     * @param int $chunkSize Records per chunk
     * @param array $options Query options
     * 
     * @return bool False if stopped early, true if completed
     */
    public function each(callable $callback, int $chunkSize = 1000, array $options = []): bool
    {
        return $this->chunk($chunkSize, function ($items) use ($callback) {
            foreach ($items as $item) {
                if ($callback($item) === false) {
                    return false;
                }
            }

            return true;
        }, $options);
    }

    /**
     * Simple pagination without total count
     * 
     * @param int $page Current page number (1-indexed)
     * @param int $perPage Items per page
     * @param array $options Query options
     * 
     * @return array{items: array, per_page: int, current_page: int, has_more: bool}
     */
    public function simplePaginate(int $page = 1, int $perPage = 15, array $options = []): array
    {
        $options['limit'] = $perPage + 1;
        $options['offset'] = ($page - 1) * $perPage;
        $items = $this->findAll($options);
        $hasMore = count($items) > $perPage;
        if ($hasMore) {
            array_pop($items);
        }

        return ['items' => $items, 'per_page' => $perPage, 'current_page' => $page, 'has_more' => $hasMore];
    }

    /**
     * Update the updated_at timestamp
     * 
     * @return bool True if updated, false if timestamps disabled
     */
    public function touch(): bool
    {
        if (!$this->timestamps || !isset($this->getMetadata()['columns'][$this->updatedAtColumn])) {
            return false;
        }

        $this[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        return $this->save() !== null;
    }

    /**
     * Permanently delete entity (bypasses soft delete)
     * 
     * @return bool True if deleted
     */
    public function forceDelete(): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];

        if (!$this->offsetExists($pkName) || empty($this[$pkName])) {
            throw new Exception("Cannot delete entity without primary key");
        }

        $tableName = $metadata['table'];
        $pkColumn = $metadata['columns'][$pkName]['name'];

        $sql = "DELETE FROM `{$tableName}` WHERE `{$pkColumn}` = ?";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$this[$pkName]]);

        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            $this->exists = false;
        }
        return $deleted;
    }

    /**
     * Determine whether this entity has been soft-deleted.
     *
     * Checks if the soft delete column is configured and contains a non-null
     * timestamp value, indicating the record was previously soft-deleted.
     *
     * @return bool True if the entity is currently in a soft-deleted state
     */
    public function trashed(): bool
    {
        return $this->softDeleteColumn
            && $this->offsetExists($this->softDeleteColumn)
            && $this[$this->softDeleteColumn] !== null;
    }

    /**
     * Find single record by field value
     * 
     * @param string $field Column name
     * @param mixed $value Value to match
     * @param string $operator Comparison operator
     * 
     * @return object|null Found entity or null
     */
    public function findOneBy(string $field, mixed $value, string $operator = "="): ?object
    {
        $result = $this->findBy($field, $value, $operator);
        return is_array($result) ? ($result[0] ?? null) : $result;
    }

    /**
     * Find entity or create new instance (not saved)
     * 
     * @param array $attributes Search conditions
     * @param array $values Additional values for new instance
     * 
     * @return object Found or new entity
     */
    public function firstOrNew(array $attributes, array $values = []): object
    {
        $entity = $this->first(['conditions' => $attributes]);
        if ($entity) {
            return $entity;
        }

        $newEntity = new static();
        foreach (array_merge($attributes, $values) as $k => $v) {
            $newEntity[$k] = $v;
        }
        return $newEntity;
    }

    /**
     * Sync original data from current values
     * 
     * @return static This instance
     */
    public function syncOriginal(): static
    {
        $metadata = $this->getMetadata();
        $storedData = $this->getArrayCopy();
        $this->originalData = [];
        foreach ($metadata['columns'] as $property => $columnInfo) {
            if (array_key_exists($property, $storedData)) {
                $this->originalData[$property] = $storedData[$property];
            }
        }

        return $this;
    }

    /**
     * Get original value(s) before changes
     * 
     * @param string|null $key Specific key or null for all
     * 
     * @return mixed Original value or array of all
     */
    public function getOriginal(?string $key = null): mixed
    {
        return $key === null ? $this->originalData : ($this->originalData[$key] ?? null);
    }

    /**
     * Get all changed fields since last sync
     * 
     * @return array<string, mixed> Changed fields and values
     */
    public function getChanges(): array
    {
        return $this->dirtyFields;
    }

    /**
     * Check if entity or attribute is unchanged
     * 
     * @param string|null $attribute Specific attribute or null for any
     * 
     * @return bool True if clean
     */
    public function isClean(?string $attribute = null): bool
    {
        return !$this->isDirty($attribute);
    }

    /**
     * Check if entity or attribute was changed
     * 
     * @param string|null $attribute Specific attribute or null for any
     * 
     * @return bool True if changed
     */
    public function wasChanged(?string $attribute = null): bool
    {
        return $this->isDirty($attribute);
    }

    /**
     * Get primary key column name
     * 
     * @return string|null Primary key name
     */
    public function getPrimaryKey(): ?string
    {
        return $this->getMetadata()['primaryKey'] ?? null;
    }

    /**
     * Get primary key value
     * 
     * @return mixed Primary key value or null
     */
    public function getPrimaryKeyValue(): mixed
    {
        $pkName = $this->getPrimaryKey();
        return $pkName ? ($this[$pkName] ?? null) : null;
    }

    /**
     * Get table name
     * 
     * @return string Table name
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set table name
     * 
     * @param string $table New table name
     * 
     * @return static This instance
     */
    public function setTable(string $table): static
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Get fillable attributes
     * 
     * @return string[] Fillable attribute names
     */
    public function getFillable(): array
    {
        return $this->fillable;
    }

    /**
     * Get guarded attributes
     * 
     * @return string[] Guarded attribute names
     */
    public function getGuarded(): array
    {
        return $this->guarded;
    }

    /**
     * Get hidden attributes
     * 
     * @return string[] Hidden attribute names
     */
    public function getHidden(): array
    {
        return $this->hidden;
    }

    /**
     * Set hidden attributes
     * 
     * @param string[] $hidden Attributes to hide
     * 
     * @return static This instance
     */
    public function setHidden(array $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Make attributes visible
     * 
     * @param string[] $attributes Attributes to show
     * 
     * @return static This instance
     */
    public function makeVisible(array $attributes): static
    {
        $this->hidden = array_diff($this->hidden, $attributes);
        return $this;
    }

    /**
     * Make attributes hidden
     * 
     * @param string[] $attributes Attributes to hide
     * 
     * @return static This instance
     */
    public function makeHidden(array $attributes): static
    {
        $this->hidden = array_merge($this->hidden, $attributes);
        return $this;
    }

    /**
     * Check if entity is new (not saved)
     * 
     * @return bool True if not in database
     */
    public function isNew(): bool
    {
        return !$this->exists;
    }

    /**
     * Check if entity was recently created
     * 
     * @return bool True if recently created
     */
    public function wasRecentlyCreated(): bool
    {
        return $this->exists && empty($this->originalData);
    }

    /**
     * Static method call handler
     * 
     * @param string $name Method name
     * @param array $arguments Arguments
     * 
     * @return mixed Method result
     */
    public static function __callStatic(string $name, array $arguments): mixed
    {
        return (new static())->__call($name, $arguments);
    }

    /**
     * Clear event hooks
     * 
     * @param string|null $event Event name or null for all
     * 
     * @return void
     */
    public static function clearEventHooks(?string $event = null): void
    {
        $class = static::class;
        if ($event === null) {
            unset(self::$eventHooks[$class]);
        } else {
            unset(self::$eventHooks[$class][$event]);
        }
    }

    /**
     * Truncate table
     * 
     * @return int Affected rows
     * @throws Exception When database not connected
     */
    public static function truncate(): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $instance = new static();
        $tableName = $instance->getMetadata()['table'];
        $stmt = self::$db->prepare("TRUNCATE TABLE `{$tableName}`");
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Append a structured condition record to the WHERE list.
     *
     * Records are appended rather than keyed by column so that several conditions may constrain the
     * same column, which a column-keyed map cannot express.
     *
     * @param string            $type     One of the CONDITION_TYPE_* constants
     * @param array<string,mixed> $parts   Record payload: column, operator, value, nested, boolean
     *
     * @return static Fluent interface
     */
    private function addConditionRecord(string $type, array $parts): static
    {
        $this->activeQuery->addCondition([
            self::CONDITION_MARKER => true,
            'type' => $type,
            'boolean' => strtoupper((string) ($parts['boolean'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
            'column' => $parts['column'] ?? null,
            'operator' => $parts['operator'] ?? null,
            'value' => $parts['value'] ?? null,
            'nested' => $parts['nested'] ?? [],
            'negate' => (bool) ($parts['negate'] ?? false),
        ]);

        return $this;
    }

    /**
     * Run a callback against this builder and return only the conditions it registered.
     *
     * The current condition list is swapped out for the duration of the callback so the nested
     * conditions can be collected without a second builder instance, then restored whatever the
     * callback does.
     *
     * @param Closure $callback Receives this builder and registers conditions on it
     *
     * @return list<array<string,mixed>> The conditions registered by the callback
     */
    private function captureConditionGroup(Closure $callback): array
    {
        $outer = $this->activeQuery->conditions();
        $this->activeQuery->setConditions([]);

        try {
            $callback($this);
            $group = $this->activeQuery->conditions();
        } finally {
            $this->activeQuery->setConditions($outer);
        }

        return $group;
    }

    /**
     * Resolve the two- and three-argument comparison forms into an explicit operator and value.
     *
     * The argument count is used rather than a null check on the value, because `where('a', '>',
     * null)` is a three-argument call whose value happens to be null and must not silently collapse
     * into `a = '>'`.
     *
     * @param int    $argumentCount Number of arguments the caller actually passed
     * @param mixed  $operator      Second argument
     * @param mixed  $value         Third argument
     *
     * @return array{0: string, 1: mixed}
     *
     * @throws InvalidArgumentException When a null value is compared with an ordering operator.
     */
    private function resolveComparison(int $argumentCount, mixed $operator, mixed $value): array
    {
        if ($argumentCount < 3) {
            $value = $operator;
            $operator = '=';
        }

        $operator = is_string($operator) ? trim($operator) : '=';

        if ($value === null) {
            $normalized = strtoupper($operator);

            if ($normalized === '=' || $normalized === 'IS') {
                return [self::CONDITION_TYPE_NULL, null];
            }

            if ($normalized === '!=' || $normalized === '<>' || $normalized === 'IS NOT') {
                return [self::CONDITION_TYPE_NOT_NULL, null];
            }

            throw new InvalidArgumentException(
                sprintf('Operator "%s" cannot be used with a null value; use whereNull or whereNotNull.', $operator)
            );
        }

        return [$operator, $value];
    }

    /**
     * Add a WHERE condition to the query builder.
     *
     * Supports two-argument shorthand: where('col', 'val') defaults to '='.
     * Three-argument form: where('col', '>', 100).
     * A closure opens a parenthesised group: where(fn ($q) => $q->where(...)->orWhere(...)).
     *
     * @param string|Closure $column   Column/property name, or a group callback
     * @param mixed          $operator Comparison operator, or the value in two-argument form
     * @param mixed          $value    Comparison value
     *
     * @return static Fluent interface
     */
    protected function where(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            return $this->addConditionRecord(self::CONDITION_TYPE_GROUP, [
                'nested' => $this->captureConditionGroup($column),
            ]);
        }

        [$resolvedOperator, $resolvedValue] = $this->resolveComparison(func_num_args(), $operator, $value);

        if ($resolvedOperator === self::CONDITION_TYPE_NULL || $resolvedOperator === self::CONDITION_TYPE_NOT_NULL) {
            return $this->addConditionRecord($resolvedOperator, ['column' => $column]);
        }

        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, [
            'column' => $column,
            'operator' => $resolvedOperator,
            'value' => $resolvedValue,
        ]);
    }

    /**
     * Negate a condition or a group of conditions.
     *
     * whereNot('role', 'admin') renders NOT (`role` = ?), which — unlike `role` != ? — also excludes
     * rows where the column is NULL, because NULL != 'admin' is unknown rather than true.
     *
     * @param string|Closure $column   Column/property name, or a group callback
     * @param mixed          $operator Comparison operator, or the value in two-argument form
     * @param mixed          $value    Comparison value
     *
     * @return static Fluent interface
     */
    protected function whereNot(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->addNegatedGroup($column, $operator, $value, func_num_args(), 'AND');
    }

    /**
     * Negate a condition or a group of conditions, joined with OR.
     *
     * @param string|Closure $column   Column/property name, or a group callback
     * @param mixed          $operator Comparison operator, or the value in two-argument form
     * @param mixed          $value    Comparison value
     *
     * @return static Fluent interface
     */
    protected function orWhereNot(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->addNegatedGroup($column, $operator, $value, func_num_args(), 'OR');
    }

    /**
     * Build the negated group shared by whereNot() and orWhereNot().
     *
     * The caller's argument count is forwarded rather than re-derived, so the two- and
     * three-argument comparison forms keep the meaning they had at the original call site.
     *
     * @param string|Closure $column
     * @param mixed          $operator
     * @param mixed          $value
     * @param int            $argumentCount Arguments passed to the public entry point
     * @param string         $boolean       AND or OR
     *
     * @return static Fluent interface
     */
    private function addNegatedGroup(
        string|Closure $column,
        mixed $operator,
        mixed $value,
        int $argumentCount,
        string $boolean
    ): static {
        $callback = $column instanceof Closure
            ? $column
            : static function (self $query) use ($column, $operator, $value, $argumentCount): void {
                if ($argumentCount < 3) {
                    $query->where($column, $operator);
                    return;
                }

                $query->where($column, $operator, $value);
            };

        return $this->addConditionRecord(self::CONDITION_TYPE_GROUP, [
            'nested' => $this->captureConditionGroup($callback),
            'negate' => true,
            'boolean' => $boolean,
        ]);
    }

    /**
     * Match when ANY of the given columns satisfies the comparison.
     *
     * Renders a parenthesised OR group, so it composes safely with surrounding AND conditions.
     *
     * @param list<string> $columns  Column/property names
     * @param string       $operator Comparison operator applied to every column
     * @param mixed        $value    Comparison value applied to every column
     *
     * @return static Fluent interface
     *
     * @throws InvalidArgumentException When no column is given.
     */
    protected function whereAny(array $columns, string $operator, mixed $value): static
    {
        return $this->addColumnSetGroup($columns, $operator, $value, 'OR', false);
    }

    /**
     * Match when ANY of the given columns satisfies the comparison, joined to the preceding
     * conditions with OR.
     *
     * @param list<string> $columns  Column/property names
     * @param string       $operator Comparison operator applied to every column
     * @param mixed        $value    Comparison value applied to every column
     *
     * @return static Fluent interface
     *
     * @throws InvalidArgumentException When no column is given.
     */
    protected function orWhereAny(array $columns, string $operator, mixed $value): static
    {
        $this->addColumnSetGroup($columns, $operator, $value, 'OR', false);

        $this->activeQuery->markLastConditionAsOr();

        return $this;
    }

    /**
     * Match when ALL of the given columns satisfy the comparison.
     *
     * @param list<string> $columns  Column/property names
     * @param string       $operator Comparison operator applied to every column
     * @param mixed        $value    Comparison value applied to every column
     *
     * @return static Fluent interface
     *
     * @throws InvalidArgumentException When no column is given.
     */
    protected function whereAll(array $columns, string $operator, mixed $value): static
    {
        return $this->addColumnSetGroup($columns, $operator, $value, 'AND', false);
    }

    /**
     * Match when NONE of the given columns satisfies the comparison.
     *
     * @param list<string> $columns  Column/property names
     * @param string       $operator Comparison operator applied to every column
     * @param mixed        $value    Comparison value applied to every column
     *
     * @return static Fluent interface
     *
     * @throws InvalidArgumentException When no column is given.
     */
    protected function whereNone(array $columns, string $operator, mixed $value): static
    {
        return $this->addColumnSetGroup($columns, $operator, $value, 'OR', true);
    }

    /**
     * Apply one comparison across a set of columns inside a single parenthesised group.
     *
     * The column array is re-indexed rather than assumed to be a list, because callers reach these
     * methods through __call and may pass a keyed array; the first-element check that decides
     * where the group starts depends on the keys being sequential.
     *
     * @param array<array-key,string> $columns
     * @param string                  $operator
     * @param mixed                   $value
     * @param string                  $joiner AND or OR, used between the columns
     * @param bool                    $negate Wrap the group in NOT
     *
     * @return static Fluent interface
     *
     * @throws InvalidArgumentException When no column is given.
     */
    private function addColumnSetGroup(
        array $columns,
        string $operator,
        mixed $value,
        string $joiner,
        bool $negate
    ): static {
        $columns = array_values($columns);

        if ($columns === []) {
            throw new InvalidArgumentException('At least one column is required.');
        }

        return $this->addConditionRecord(self::CONDITION_TYPE_GROUP, [
            'negate' => $negate,
            'nested' => $this->captureConditionGroup(
                static function (self $query) use ($columns, $operator, $value, $joiner): void {
                    foreach ($columns as $index => $column) {
                        if ($index === 0 || $joiner === 'AND') {
                            $query->where($column, $operator, $value);
                            continue;
                        }

                        $query->orWhere($column, $operator, $value);
                    }
                }
            ),
        ]);
    }

    /**
     * Constrain the query to the primary key.
     *
     * Accepts a single key or a list of keys, dispatching to an equality or an IN comparison.
     *
     * @param mixed $key Primary key value, or a list of them
     *
     * @return static Fluent interface
     */
    protected function whereKey(mixed $key): static
    {
        if (is_array($key)) {
            return $this->addConditionRecord(self::CONDITION_TYPE_IN, [
                'column' => $this->getPrimaryKey(),
                'value' => array_values($key),
            ]);
        }

        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, [
            'column' => $this->getPrimaryKey(),
            'operator' => '=',
            'value' => $key,
        ]);
    }

    /**
     * Exclude the given primary key or keys from the query.
     *
     * @param mixed $key Primary key value, or a list of them
     *
     * @return static Fluent interface
     */
    protected function whereKeyNot(mixed $key): static
    {
        if (is_array($key)) {
            return $this->addConditionRecord(self::CONDITION_TYPE_NOT_IN, [
                'column' => $this->getPrimaryKey(),
                'value' => array_values($key),
            ]);
        }

        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, [
            'column' => $this->getPrimaryKey(),
            'operator' => '!=',
            'value' => $key,
        ]);
    }

    /**
     * Match a search term against any of the given columns with LIKE.
     *
     * The term is wrapped in wildcards here, so callers pass the bare term. Wildcards inside the
     * term are escaped, otherwise a user-supplied `%` would widen the search to everything.
     *
     * @param list<string> $columns Column/property names
     * @param string       $search  Bare search term
     *
     * @return static Fluent interface
     *
     * @throws InvalidArgumentException When no column is given.
     */
    protected function whereLikeAny(array $columns, string $search): static
    {
        return $this->whereAny($columns, 'LIKE', '%' . $this->escapeLikeWildcards($search) . '%');
    }

    /**
     * Escape the LIKE metacharacters in a search term.
     *
     * @param string $term Raw term supplied by a caller
     *
     * @return string Term safe to embed between wildcards
     */
    private function escapeLikeWildcards(string $term): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term);
    }

    /**
     * Add a WHERE IN (...) clause to the query.
     *
     * Generates a parameterized IN condition with one placeholder per value.
     *
     * @param string       $column Column/property name
     * @param list<mixed>  $values Array of values to match against
     *
     * @return static Fluent interface
     * @phpstan-param non-empty-string $column
     * @phpstan-param list<mixed> $values
     */
    protected function whereIn(string $column, array $values): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_IN, ['column' => $column, 'value' => $values]);
    }

    /**
     * Add a WHERE column IS NULL clause to the query.
     *
     * @param string $column Column/property name to check for NULL
     *
     * @return static Fluent interface
     * @phpstan-param non-empty-string $column
     */
    protected function whereNull(string $column): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_NULL, ['column' => $column]);
    }

    /**
     * Add a WHERE column IS NOT NULL clause to the query.
     *
     * @param string $column Column/property name to check for non-NULL
     *
     * @return static Fluent interface
     * @phpstan-param non-empty-string $column
     */
    protected function whereNotNull(string $column): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_NOT_NULL, ['column' => $column]);
    }

    /**
     * Add an ORDER BY clause to the query.
     *
     * @param string $column    Column/property name to sort by
     * @param string $direction Sort direction: 'ASC' or 'DESC' (default 'ASC')
     *
     * @return static Fluent interface
     * @phpstan-param non-empty-string $column
     * @phpstan-param 'ASC'|'DESC'|'asc'|'desc' $direction
     */
    protected function orderBy(string $column, string $direction = 'ASC'): static
    {
        // The column is stored already quoted because the render site interpolates it verbatim.
        $this->activeQuery->orderBy(SqlIdentifier::quote($column), SqlIdentifier::direction($direction));
        return $this;
    }

    /**
     * Set the maximum number of rows to return (and optionally skip).
     *
     * @param int      $limit  Maximum number of rows
     * @param int|null $offset Number of rows to skip (optional)
     *
     * @return static Fluent interface
     * @phpstan-param positive-int $limit
     * @phpstan-param non-negative-int|null $offset
     */
    protected function limit(int $limit, ?int $offset = null): static
    {
        $this->activeQuery->setLimit($limit);
        if ($offset !== null) {
            $this->activeQuery->setOffset($offset);
        }
        return $this;
    }

    /**
     * Set the number of rows to skip before returning results.
     *
     * @param int $offset Number of rows to skip (0-based)
     *
     * @return static Fluent interface
     * @phpstan-param non-negative-int $offset
     */
    protected function offset(int $offset): static
    {
        $this->activeQuery->setOffset($offset);
        return $this;
    }

    /**
     * Retrieve all records from the entity's table.
     *
     * Alias for get() without any prior query builder conditions.
     * Soft-deleted records are excluded by default when soft delete is enabled.
     *
     * @return list<static> Array of hydrated entity instances
     * @phpstan-return list<static>
     */
    protected function all(): array
    {
        return $this->get();
    }

    /**
     * Execute the current query builder state and return matching entities.
     *
     * Builds query options from all chained conditions (where, orderBy, limit,
     * joins, etc.), applies soft-delete scope, executes the query, resets the
     * builder, and returns hydrated entity instances.
     *
     * @return list<static> Array of hydrated entity instances
     * @phpstan-return list<static>
     */
    protected function get(): array
    {
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);
        $results = $this->findAll($options);
        $this->resetQuery();
        return $results;
    }

    /**
     * Reset all query builder state to default values.
     *
     * Called automatically after get(), getCached(), deleteWhere(),
     * updateWhere2(), and other terminal query builder methods to
     * prevent state leakage between successive queries.
     *
     * @return void
     */
    private function resetQuery(): void
    {
        $this->activeQuery->reset();
        $this->softDeleteScope = null;
        $this->withCountRelations = [];
    }

    /**
     * Convert the current query builder state into an options array.
     *
     * Consolidates all chained builder calls (where, orderBy, limit, joins,
     * groupBy, having, select, distinct, lock, etc.) into a single
     * associative array consumed by modifyQueryByOptions().
     *
     * @return array{conditions: array, order: string|null, arrange: string|null, limit: int|null, offset: int|null}
     * @phpstan-return QueryOptions
     */
    private function buildQueryOptions(): array
    {
        return $this->activeQuery->toOptions();
    }

    /**
     * Apply soft delete scope (e.g., hiding trashed records) to query options.
     *
     * @param array{conditions: array} $options
     * @return void
     */
    private function applySoftDeleteScope(array &$options): void
    {
        if (!$this->softDeleteColumn) {
            return;
        }
        if ($this->softDeleteScope === 'withTrashed') {
            return;
        }

        $metadata = $this->getMetadata();
        if (!isset($metadata['columns'][$this->softDeleteColumn])) {
            return;
        }

        $col = $this->softDeleteColumn;
        if ($this->softDeleteScope === 'onlyTrashed') {
            $options['conditions'] = $options['conditions'] ?? [];
            $options['conditions'][$col] = ['value' => null, 'operator' => 'IS NOT NULL'];
        } else {
            $options['conditions'] = $options['conditions'] ?? [];
            $options['conditions'][$col] = ['value' => null, 'operator' => 'IS NULL'];
        }
    }

    /**
     * Include soft deleted records in the query results.
     *
     * @return static
     */
    protected function withTrashed(): static
    {
        $this->softDeleteScope = 'withTrashed';
        return $this;
    }

    /**
     * Only include soft deleted records in the query results.
     *
     * @return static
     */
    protected function onlyTrashed(): static
    {
        $this->softDeleteScope = 'onlyTrashed';
        return $this;
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param mixed $id
     * @param array $options
     * @return object
     * @throws \Exception If no model is found
     */
    protected function findOrFail(mixed $id, array $options = []): object
    {
        $result = $this->find($id, $options);
        if ($result === null) {
            throw new Exception("Entity not found");
        }

        return $result;
    }

    /**
     * Delete multiple records by primary key
     * 
     * @param int|string|array ...$ids Primary key values
     * 
     * @return int Number of deleted records
     * @throws Exception When database not connected
     */
    public static function destroy(int|string|array ...$ids): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $ids = is_array($ids[0] ?? null) ? $ids[0] : $ids;
        if (empty($ids)) {
            return 0;
        }

        $instance = new static();
        $metadata = $instance->getMetadata();
        $tableName = $metadata['table'];
        $pkColumn = $metadata['columns'][$metadata['primaryKey']]['name'];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "DELETE FROM `{$tableName}` WHERE `{$pkColumn}` IN ({$placeholders})";
        $stmt = self::$db->prepare($sql);
        $stmt->execute($ids);
        return $stmt->rowCount();
    }

    /**
     * Add an OR where clause to the query.
     *
     * A closure opens a parenthesised group joined with OR.
     *
     * @param string|Closure $column   Column/property name, or a group callback
     * @param mixed          $operator Comparison operator, or the value in two-argument form
     * @param mixed          $value    Comparison value
     *
     * @return static Fluent interface
     */
    protected function orWhere(string|Closure $column, mixed $operator = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            return $this->addConditionRecord(self::CONDITION_TYPE_GROUP, [
                'nested' => $this->captureConditionGroup($column),
                'boolean' => 'OR',
            ]);
        }

        [$resolvedOperator, $resolvedValue] = $this->resolveComparison(func_num_args(), $operator, $value);

        if ($resolvedOperator === self::CONDITION_TYPE_NULL || $resolvedOperator === self::CONDITION_TYPE_NOT_NULL) {
            return $this->addConditionRecord($resolvedOperator, ['column' => $column, 'boolean' => 'OR']);
        }

        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, [
            'column' => $column,
            'operator' => $resolvedOperator,
            'value' => $resolvedValue,
            'boolean' => 'OR',
        ]);
    }

    /**
     * Add an OR where-in clause to the query.
     *
     * @param string $column
     * @param array $values
     * @return static
     */
    protected function orWhereIn(string $column, array $values): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_IN, ['column' => $column, 'value' => $values, 'boolean' => 'OR']);
    }

    /**
     * Add an OR where-null clause to the query.
     *
     * @param string $column
     * @return static
     */
    protected function orWhereNull(string $column): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_NULL, ['column' => $column, 'boolean' => 'OR']);
    }

    /**
     * Add an OR where-not-null clause to the query.
     *
     * @param string $column
     * @return static
     */
    protected function orWhereNotNull(string $column): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_NOT_NULL, ['column' => $column, 'boolean' => 'OR']);
    }

    /**
     * Add a where-not-in clause to the query.
     *
     * @param string $column
     * @param array $values
     * @return static
     */
    protected function whereNotIn(string $column, array $values): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_NOT_IN, ['column' => $column, 'value' => $values]);
    }

    /**
     * Add a where-between clause to the query.
     *
     * @param string $column
     * @param mixed $min
     * @param mixed $max
     * @return static
     */
    protected function whereBetween(string $column, mixed $min, mixed $max): static
    {
        return $this->whereRaw(SqlIdentifier::quote($column) . " BETWEEN ? AND ?", [$min, $max]);
    }

    /**
     * Add a where-not-between clause to the query.
     *
     * @param string $column
     * @param mixed $min
     * @param mixed $max
     * @return static
     */
    protected function whereNotBetween(string $column, mixed $min, mixed $max): static
    {
        return $this->whereRaw(SqlIdentifier::quote($column) . " NOT BETWEEN ? AND ?", [$min, $max]);
    }

    /**
     * Add a where LIKE clause to the query.
     *
     * @param string $column
     * @param string $pattern
     * @return static
     */
    protected function whereLike(string $column, string $pattern): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, ['column' => $column, 'operator' => 'LIKE', 'value' => $pattern]);
    }

    /**
     * Add a where NOT LIKE clause to the query.
     *
     * @param string $column
     * @param string $pattern
     * @return static
     */
    protected function whereNotLike(string $column, string $pattern): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, ['column' => $column, 'operator' => 'NOT LIKE', 'value' => $pattern]);
    }

    /**
     * Add a raw where expression to the query.
     *
     * @param string $expression
     * @param array $bindings
     * @param string $boolean
     * @return static
     */
    protected function whereRaw(string $expression, array $bindings = [], string $boolean = 'AND'): static
    {
        $this->activeQuery->addWhereRaw($expression, $bindings, $boolean);
        return $this;
    }

    /**
     * Add an OR raw where expression to the query.
     *
     * @param string $expression
     * @param array $bindings
     * @return static
     */
    protected function orWhereRaw(string $expression, array $bindings = []): static
    {
        return $this->whereRaw($expression, $bindings, 'OR');
    }

    /**
     * Add a column-to-column comparison where clause.
     *
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return static
     */
    protected function whereColumn(string $first, string $operator, string $second): static
    {
        $quotedFirst = SqlIdentifier::quote($first);
        $safeOperator = SqlIdentifier::operator($operator);
        $quotedSecond = SqlIdentifier::quote($second);

        return $this->whereRaw("{$quotedFirst} {$safeOperator} {$quotedSecond}");
    }

    /**
     * Add a where clause comparing only the date part of a datetime column.
     *
     * @param string $column
     * @param string $operator
     * @param string $date
     * @return static
     */
    protected function whereDate(string $column, string $operator, string $date): static
    {
        return $this->whereDatePart('DATE', $column, $operator, $date);
    }

    /**
     * Add a where clause comparing the year part of a datetime column.
     *
     * @param string $column
     * @param string $operator
     * @param int $year
     * @return static
     */
    protected function whereYear(string $column, string $operator, int $year): static
    {
        return $this->whereDatePart('YEAR', $column, $operator, $year);
    }

    /**
     * Add a where clause comparing the month part of a datetime column.
     *
     * @param string $column
     * @param string $operator
     * @param int $month
     * @return static
     */
    protected function whereMonth(string $column, string $operator, int $month): static
    {
        return $this->whereDatePart('MONTH', $column, $operator, $month);
    }

    /**
     * Add a where clause comparing the day part of a datetime column.
     *
     * @param string $column
     * @param string $operator
     * @param int $day
     * @return static
     */
    protected function whereDay(string $column, string $operator, int $day): static
    {
        return $this->whereDatePart('DAY', $column, $operator, $day);
    }

    /**
     * Add a where clause comparing the time part of a datetime column.
     *
     * @param string $column
     * @param string $operator
     * @param string $time
     * @return static
     */
    protected function whereTime(string $column, string $operator, string $time): static
    {
        return $this->whereDatePart('TIME', $column, $operator, $time);
    }

    /**
     * Build a WHERE clause that applies a date/time extraction function to a column.
     *
     * The function name is supplied by the calling builder method and is never caller input; the
     * column and operator are, so both are validated before the expression is assembled.
     *
     * @param string $function MySQL extraction function: DATE, YEAR, MONTH, DAY or TIME.
     * @param string $column   Column to extract from.
     * @param string $operator Comparison operator.
     * @param mixed  $value    Bound comparison value.
     *
     * @return static Fluent interface
     * @throws InvalidArgumentException When the column or operator is not accepted.
     */
    private function whereDatePart(string $function, string $column, string $operator, mixed $value): static
    {
        $quotedColumn = SqlIdentifier::quote($column);
        $safeOperator = SqlIdentifier::operator($operator);

        return $this->whereRaw("{$function}({$quotedColumn}) {$safeOperator} ?", [$value]);
    }

    /**
     * Add one or more GROUP BY columns to the query.
     *
     * @param string ...$columns
     * @return static
     */
    protected function groupBy(string ...$columns): static
    {
        foreach ($columns as $col) {
            $this->activeQuery->addGroupBy(SqlIdentifier::quote($col));
        }
        return $this;
    }

    /**
     * Add a raw GROUP BY expression to the query.
     *
     * @param string $expression
     * @return static
     */
    protected function groupByRaw(string $expression): static
    {
        $this->activeQuery->addGroupBy($expression);
        return $this;
    }

    /**
     * Add a HAVING clause to the query.
     *
     * @param string $column
     * @param string $operator
     * @param mixed $value
     * @return static
     */
    protected function having(string $column, string $operator, mixed $value): static
    {
        // Validated here so a bad identifier is reported at the call rather than at execution
        // time. The values are stored unquoted; the render site quotes them.
        SqlIdentifier::quote($column);
        SqlIdentifier::operator($operator);

        $this->activeQuery->addHaving(['column' => $column, 'operator' => $operator, 'value' => $value]);
        return $this;
    }

    /**
     * Add a raw HAVING expression to the query.
     *
     * @param string $expression
     * @param array $bindings
     * @return static
     */
    protected function havingRaw(string $expression, array $bindings = []): static
    {
        $this->activeQuery->addHaving(['raw' => $expression, 'bindings' => $bindings]);
        return $this;
    }

    /**
     * Restrict an aggregate to an inclusive range.
     *
     * @param string $column Aggregated column/property name
     * @param mixed  $min    Lower bound, inclusive
     * @param mixed  $max    Upper bound, inclusive
     *
     * @return static Fluent interface
     */
    protected function havingBetween(string $column, mixed $min, mixed $max): static
    {
        $quotedColumn = SqlIdentifier::quote($column);

        $this->activeQuery->addHaving([
            'raw' => $quotedColumn . ' BETWEEN ? AND ?',
            'bindings' => [$min, $max],
        ]);

        return $this;
    }

    /**
     * Add a descending ORDER BY clause to the query.
     *
     * @param string $column
     * @return static
     */
    protected function orderByDesc(string $column): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Add a raw ORDER BY expression to the query.
     *
     * @param string $expression
     * @return static
     */
    protected function orderByRaw(string $expression): static
    {
        $this->activeQuery->addOrderByRaw($expression);
        return $this;
    }

    /**
     * Discard every ordering collected so far, optionally installing a replacement.
     *
     * Ordering is otherwise additive, so a caller that inherits a scope or a shared query cannot
     * override the sort without this. Called with no arguments the query is left unordered.
     *
     * @param string|null $column    Replacement column/property name, or null to leave unordered
     * @param string      $direction Replacement sort direction
     *
     * @return static Fluent interface
     */
    protected function reorder(?string $column = null, string $direction = 'ASC'): static
    {
        $this->activeQuery->clearOrdering();

        if ($column === null) {
            return $this;
        }

        return $this->orderBy($column, $direction);
    }

    /**
     * Order by the given column descending (latest first).
     *
     * @param string $column
     * @return static
     */
    protected function latest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Order by the given column ascending (oldest first).
     *
     * @param string $column
     * @return static
     */
    protected function oldest(string $column = 'created_at'): static
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Alias for limit().
     *
     * @param int $value
     * @return static
     */
    protected function take(int $value): static
    {
        return $this->limit($value);
    }

    /**
     * Alias for offset().
     *
     * @param int $value
     * @return static
     */
    protected function skip(int $value): static
    {
        return $this->offset($value);
    }

    /**
     * Set the SELECT columns for the query.
     *
     * @param string ...$columns  Raw column expressions
     * @return static
     */
    protected function select(string ...$columns): static
    {
        $this->activeQuery->setSelect($columns);
        return $this;
    }

    /**
     * Add a raw SELECT expression to the query.
     *
     * @param string $expression
     * @return static
     */
    protected function selectRaw(string $expression): static
    {
        $this->activeQuery->addSelect($expression);
        return $this;
    }

    /**
     * Force the query to return only distinct results.
     *
     * @return static
     */
    protected function distinct(): static
    {
        $this->activeQuery->markDistinct();
        return $this;
    }

    /**
     * Add an INNER JOIN clause to the query.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return static
     */
    protected function join(string $table, string $first, string $operator, string $second): static
    {
        $this->activeQuery->addJoin($this->buildJoinEntry('INNER', $table, $first, $operator, $second));
        return $this;
    }

    /**
     * Validate the parts of a JOIN clause and return an entry that is safe to interpolate.
     *
     * Every component of a join is structure rather than data: the join type, the table, both
     * operands of the ON condition and the comparison operator are all written into the statement
     * verbatim. They are validated once here, at the point the caller supplies them, so the render
     * sites can interpolate the stored entry without re-checking it — and so a bad identifier is
     * reported at the join() call rather than at execution time.
     *
     * @param string $type     Join type keyword; must be one of the supported joins.
     * @param string $table    Table (or alias) to join.
     * @param string $first    Left operand of the ON condition.
     * @param string $operator Comparison operator.
     * @param string $second   Right operand of the ON condition.
     *
     * @return array{type: string, table: string, first: string, operator: string, second: string}
     * @phpstan-return JoinEntry
     * @throws InvalidArgumentException When any component is not a valid identifier/operator/type.
     */
    private function buildJoinEntry(string $type, string $table, string $first, string $operator, string $second): array
    {
        $safeType = strtoupper(trim($type));

        if (!in_array($safeType, ['INNER', 'LEFT', 'RIGHT', 'CROSS', 'LEFT OUTER', 'RIGHT OUTER', 'FULL OUTER'], true)) {
            throw new InvalidArgumentException("The join type `{$type}` is not supported.");
        }

        return [
            'type' => $safeType,
            'table' => SqlIdentifier::quote($table),
            'first' => SqlIdentifier::quote($first),
            'operator' => SqlIdentifier::operator($operator),
            'second' => SqlIdentifier::quote($second),
        ];
    }

    /**
     * Add a LEFT JOIN clause to the query.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return static
     */
    protected function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->activeQuery->addJoin($this->buildJoinEntry('LEFT', $table, $first, $operator, $second));
        return $this;
    }

    /**
     * Add a RIGHT JOIN clause to the query.
     *
     * @param string $table
     * @param string $first
     * @param string $operator
     * @param string $second
     * @return static
     */
    protected function rightJoin(string $table, string $first, string $operator, string $second): static
    {
        $this->activeQuery->addJoin($this->buildJoinEntry('RIGHT', $table, $first, $operator, $second));
        return $this;
    }

    /**
     * Lock selected rows for update (pessimistic write lock).
     *
     * @return static
     */
    protected function lockForUpdate(): static
    {
        $this->activeQuery->setLock('FOR UPDATE');
        return $this;
    }

    /**
     * Lock selected rows with a shared lock (pessimistic read lock).
     *
     * @return static
     */
    protected function sharedLock(): static
    {
        $this->activeQuery->setLock('LOCK IN SHARE MODE');
        return $this;
    }

    /**
     * Add a Common Table Expression (CTE / WITH clause) to the query.
     *
     * CTEs allow defining named subqueries that can be referenced in the
     * main SELECT. Pass recursive=true to generate a WITH RECURSIVE clause,
     * which is required for self-referential (hierarchical) queries.
     *
     * @param string $name      CTE name used to reference it in the main query
     * @param string $sql       The SELECT statement that defines the CTE
     * @param array  $bindings  Bound parameters for the CTE's statement
     * @param bool   $recursive Whether to use WITH RECURSIVE (default false)
     *
     * @return static Fluent interface
     */
    protected function withCte(string $name, string $sql, array $bindings = [], bool $recursive = false): static
    {
        // Validated here so a bad name is reported at the call; buildCtePrefix() does the quoting.
        SqlIdentifier::quote($name);

        $this->activeQuery->addCte($name, $sql, $bindings, $recursive);
        return $this;
    }

    /**
     * Use a subquery as the FROM clause instead of a table name.
     *
     * Replaces the primary table with a derived table. Useful for wrapping
     * complex queries, applying UNION results, or using window functions as
     * the source for outer queries.
     *
     * @param string $sql      The subquery SQL string (without surrounding parentheses)
     * @param array  $bindings Bound parameter values for the subquery
     * @param string $alias    Alias name for the derived table
     *
     * @return static Fluent interface
     */
    protected function fromSubquery(string $sql, array $bindings, string $alias): static
    {
        // Validated here so a bad alias is reported at the call; the render sites do the quoting.
        SqlIdentifier::quote($alias);

        $this->activeQuery->setFromSubquery($sql, $bindings, $alias);
        return $this;
    }

    /**
     * Add a WHERE clause that compares a column against a subquery result.
     *
     * Supports standard comparison operators as well as EXISTS / NOT EXISTS.
     * When operator is EXISTS or NOT EXISTS, the $column argument is ignored
     * and only the subquery's existence is checked.
     *
     * Examples:
     *   ->whereSubquery('id', 'IN', 'SELECT user_id FROM banned_users')
     *   ->whereSubquery('', 'EXISTS', 'SELECT 1 FROM flags WHERE flags.post_id = posts.id')
     *
     * @param string $column   Property/column to compare (ignored for EXISTS)
     * @param string $operator Comparison operator: =, IN, NOT IN, EXISTS, NOT EXISTS, etc.
     * @param string $sql      The inner subquery SQL string
     * @param array  $bindings Bound parameter values for the subquery
     * @param string $boolean  How to combine with previous clauses: 'AND' or 'OR'
     *
     * @return static Fluent interface
     */
    protected function whereSubquery(string $column, string $operator, string $sql, array $bindings = [], string $boolean = 'AND'): static
    {
        // EXISTS / NOT EXISTS ignore the column entirely and are handled by the render site, so
        // they bypass the operator whitelist; every other form is validated up front.
        if (!in_array(strtoupper(trim($operator)), ['EXISTS', 'NOT EXISTS'], true)) {
            SqlIdentifier::quote($column);
            SqlIdentifier::operator($operator);
        }

        $this->activeQuery->addWhereSubquery($column, $operator, $sql, $bindings, $boolean);
        return $this;
    }

    /**
     * Build the SQL WITH [RECURSIVE] prefix from a list of CTE definitions.
     *
     * Concatenates all CTE entries into a single prefix string suitable for
     * prepending to a SELECT query. If any CTE is marked as recursive, the
     * WITH RECURSIVE keyword is used for the entire WITH clause.
     *
     * @param array $ctes  Array of CTE entries: [name, sql, bindings, recursive]
     *
     * @return array{0: string, 1: array} [prefix SQL string, flattened bindings]
     */
    private function buildCtePrefix(array $ctes): array
    {
        $parts = [];
        $bindings = [];
        $hasRecursive = false;

        foreach ($ctes as $cte) {
            // The CTE body is trusted input and stays raw; its name is a caller-supplied identifier.
            $parts[] = SqlIdentifier::quote($cte['name']) . " AS ({$cte['sql']})";
            foreach ($cte['bindings'] as $b) {
                $bindings[] = $b;
            }
            if ($cte['recursive']) {
                $hasRecursive = true;
            }
        }

        $keyword = $hasRecursive ? 'WITH RECURSIVE ' : 'WITH ';
        return [$keyword . implode(', ', $parts) . ' ', $bindings];
    }

    /**
     * Apply the given callback when the condition is truthy.
     *
     * @param bool|\Closure $condition
     * @param callable $callback
     * @param callable|null $default
     * @return static
     */
    protected function when(mixed $condition, callable $callback, ?callable $default = null): static
    {
        $value = $condition instanceof \Closure ? $condition($this) : $condition;
        if ($value) {
            $callback($this, $value);
        } elseif ($default !== null) {
            $default($this, $value);
        }
        return $this;
    }

    /**
     * Apply the given callback when the condition is falsy.
     *
     * @param bool|\Closure $condition
     * @param callable $callback
     * @param callable|null $default
     * @return static
     */
    protected function unless(mixed $condition, callable $callback, ?callable $default = null): static
    {
        return $this->when(
            !($condition instanceof \Closure ? $condition($this) : $condition),
            $callback,
            $default
        );
    }

    /**
     * Call the given callback with the current instance and return $this (tap pattern).
     *
     * @param callable $callback
     * @return static
     */
    protected function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    /**
     * Get the SQL string that would be executed by the current query builder state.
     *
     * @return string
     */
    protected function toSql(): string
    {
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $distinct = !empty($options['distinct']) ? 'DISTINCT ' : '';
        $selectRaw = !empty($options['select']) ? implode(', ', $options['select']) : "`{$tableName}`.*";
        $joinSql = '';
        if (!empty($options['joins'])) {
            foreach ($options['joins'] as $j) {
                // Every component was validated and quoted by buildJoinEntry() / crossJoin().
                $joinSql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        $sql = "SELECT {$distinct}{$selectRaw} FROM `{$tableName}`{$joinSql}";
        $modified = $this->modifyQueryByOptions($sql, $options);

        $this->resetQuery();
        return $modified['query'];
    }

    /**
     * Get the value of the given column from the first matching row.
     *
     * @param string $column  Entity property name
     * @return mixed
     * @throws Exception When column not found in metadata
     */
    protected function value(string $column): mixed
    {
        $this->ensureConnection();
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);
        $options['limit'] = 1;

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        if (!isset($metadata['columns'][$column])) {
            throw new Exception("Column {$column} not found in metadata");
        }

        $colName = $metadata['columns'][$column]['name'];
        $joinSql = '';
        if (!empty($options['joins'])) {
            foreach ($options['joins'] as $j) {
                // Every component was validated and quoted by buildJoinEntry() / crossJoin().
                $joinSql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        $sql = "SELECT `{$colName}` FROM `{$tableName}`{$joinSql}";
        $modified = $this->modifyQueryByOptions($sql, $options);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->resetQuery();

        return $row[$colName] ?? null;
    }

    /**
     * Execute the query expecting exactly one result; throw if zero or more than one.
     *
     * @return object
     * @throws Exception When result count is not exactly 1
     */
    protected function sole(): object
    {
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);
        $results = $this->findAll($options);
        $this->resetQuery();

        if (count($results) === 0) {
            throw new Exception("No records found");
        }
        if (count($results) > 1) {
            throw new Exception("Multiple records found, expected exactly one");
        }

        return $results[0];
    }

    /**
     * Determine if no records exist for the given conditions.
     *
     * @param array $conditions
     * @return bool
     */
    public function doesntExist(array $conditions = []): bool
    {
        return !$this->exists($conditions);
    }

    /**
     * Count records grouped by the given column.
     * Returns associative array of column_value => count.
     *
     * @param string $column  Entity property name
     * @param array $conditions
     * @return array<string|int, int>
     */
    public function countBy(string $column, array $conditions = []): array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        if (!isset($metadata['columns'][$column])) {
            throw new Exception("Column {$column} not found");
        }

        $colName = $metadata['columns'][$column]['name'];
        $sql = "SELECT `{$colName}`, COUNT(*) as _cnt FROM `{$tableName}`";
        $modified = $this->modifyQueryByOptions($sql, ['conditions' => $conditions]);
        $modified['query'] .= " GROUP BY `{$colName}`";

        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[$row[$colName]] = (int) $row['_cnt'];
        }

        return $result;
    }

    /**
     * Insert rows or update on duplicate key (MySQL ON DUPLICATE KEY UPDATE).
     *
     * @param array $values    Single row or array of rows (associative: property => value)
     * @param array $uniqueBy  Property names that form the unique constraint
     * @param array $update    Properties to update on conflict (empty = all non-unique columns)
     * @return int Affected rows
     */
    public static function upsert(array $values, array $uniqueBy, array $update = []): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }
        if (empty($values)) {
            return 0;
        }

        $instance = new static();
        $metadata = $instance->getMetadata();
        $tableName = $metadata['table'];
        $columns = $metadata['columns'];

        if (!isset($values[0])) {
            $values = [$values];
        }

        $colMap = [];
        foreach ($values[0] as $prop => $v) {
            if (isset($columns[$prop])) {
                $colMap[$prop] = $columns[$prop]['name'];
            }
        }

        if (empty($colMap)) {
            return 0;
        }

        $colSql = implode(', ', array_map(fn($c) => "`{$c}`", array_values($colMap)));
        $rowPh = '(' . implode(', ', array_fill(0, count($colMap), '?')) . ')';
        $allPh = implode(', ', array_fill(0, count($values), $rowPh));
        $sql = "INSERT INTO `{$tableName}` ({$colSql}) VALUES {$allPh}";

        $updateProps = empty($update)
            ? array_filter(array_keys($colMap), fn($p) => !in_array($p, $uniqueBy))
            : $update;

        $updateParts = [];
        foreach ($updateProps as $prop) {
            if (isset($colMap[$prop])) {
                $c = $colMap[$prop];
                $updateParts[] = "`{$c}` = VALUES(`{$c}`)";
            }
        }

        if (!empty($updateParts)) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateParts);
        }

        $params = [];
        foreach ($values as $row) {
            foreach (array_keys($colMap) as $prop) {
                $params[] = $row[$prop] ?? null;
            }
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Insert a single row and return the auto-increment primary key.
     *
     * @param array $values  Associative array of property => value
     * @return int|string
     * @throws Exception When no valid columns found
     */
    public static function insertGetId(array $values): int|string
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $instance = new static();
        $metadata = $instance->getMetadata();
        $tableName = $metadata['table'];
        $columns = $metadata['columns'];
        $pkName = $metadata['primaryKey'];

        $colNames = [];
        $params = [];
        foreach ($values as $prop => $v) {
            if (isset($columns[$prop]) && $prop !== $pkName) {
                $colNames[] = "`{$columns[$prop]['name']}`";
                $params[] = $v;
            }
        }

        if (empty($colNames)) {
            throw new Exception("No valid columns to insert");
        }

        $ph = implode(', ', array_fill(0, count($colNames), '?'));
        $sql = "INSERT INTO `{$tableName}` (" . implode(', ', $colNames) . ") VALUES ({$ph})";
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        return self::$db->lastInsertId();
    }

    /**
     * Update rows matching the given conditions without instantiating entities.
     *
     * @param array $conditions  property => value
     * @param array $values      property => value
     * @return int Affected rows
     */
    public static function updateWhere(array $conditions, array $values): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $instance = new static();
        $metadata = $instance->getMetadata();
        $tableName = $metadata['table'];
        $columns = $metadata['columns'];

        $setParts = [];
        $params = [];
        foreach ($values as $prop => $v) {
            if (isset($columns[$prop])) {
                $setParts[] = "`{$columns[$prop]['name']}` = ?";
                $params[] = $v;
            }
        }

        if (empty($setParts)) {
            return 0;
        }

        $whereParts = [];
        foreach ($conditions as $prop => $v) {
            if (isset($columns[$prop])) {
                $colName = $columns[$prop]['name'];
                if (is_array($v)) {
                    $ph = implode(', ', array_fill(0, count($v), '?'));
                    $whereParts[] = "`{$colName}` IN ({$ph})";
                    $params = array_merge($params, $v);
                } else {
                    $whereParts[] = "`{$colName}` IN (?)";
                    $params[] = $v;
                }
            }
        }

        $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts);
        if (!empty($whereParts)) {
            $sql .= ' WHERE ' . implode(' AND ', $whereParts);
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        $affected = $stmt->rowCount();
        if ($affected === 0) {
            $check = self::$db->query("SELECT changes()");
            $affected = (int) $check->fetchColumn();
        }
        return $affected;
    }

    /**
     * Stream all matching records via a Generator, one row at a time.
     * Avoids loading the entire result set into memory.
     *
     * @param array $options
     * @return \Generator
     */
    public function cursor(array $options = []): \Generator
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];
        $calledClass = get_class($this);

        $sql = "SELECT * FROM `{$tableName}`";
        $modified = $this->modifyQueryByOptions($sql, $options);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);

        while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $instance = new $calledClass();
            foreach ($metadata['columns'] as $property => $columnInfo) {
                $colName = $columnInfo['name'];
                if (array_key_exists($colName, $data)) {
                    $instance->offsetSet($property, $data[$colName]);
                }
            }
            $instance->exists = true;
            $instance->syncOriginal();
            yield $instance;
        }
    }

    /**
     * Process results in chunks keyed by primary key value.
     * Stable under concurrent inserts compared to offset-based chunk().
     *
     * @param int $count
     * @param callable $callback  Receives (array $items, mixed $lastId); return false to stop
     * @param string|null $column  PK property override
     * @return bool  False if stopped early
     */
    public function chunkById(int $count, callable $callback, ?string $column = null): bool
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $pkName = $column ?? $metadata['primaryKey'];

        if (!isset($metadata['columns'][$pkName])) {
            throw new Exception("Column {$pkName} not found in metadata");
        }

        $pkColName = $metadata['columns'][$pkName]['name'];
        $lastId = null;

        do {
            $options = ['order' => $pkColName, 'arrange' => 'ASC', 'limit' => $count];

            if ($lastId !== null) {
                $options['conditions'][$pkColName] = ['value' => $lastId, 'operator' => '>'];
            }

            $items = $this->findAll($options);
            if (empty($items)) {
                break;
            }

            $lastItem = end($items);
            $lastId = $lastItem[$pkName] ?? null;

            if ($callback($items, $lastId) === false) {
                return false;
            }
        } while (count($items) === $count);

        return true;
    }

    /**
     * Get the list of column names from the underlying database table.
     *
     * @return string[]
     */
    public function getColumnListing(): array
    {
        $this->ensureConnection();
        $tableName = $this->getMetadata()['table'];
        $stmt = self::$db->prepare("SHOW COLUMNS FROM `{$tableName}`");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
    }

    /**
     * Return a fresh instance loaded from the database without modifying the current instance.
     *
     * @return static|null
     */
    public function fresh(): ?static
    {
        $pkName = $this->getPrimaryKey();
        if (!$pkName || empty($this[$pkName])) {
            return null;
        }
        return $this->find($this[$pkName]);
    }

    /**
     * Validation rules for each property (property => rule string or array).
     * Supported rules: required, min:n, max:n, minLength:n, maxLength:n,
     * numeric, integer, email, url, regex:/pattern/, in:a,b,c, notIn:a,b,
     * unique:table.column, confirmed (checks {field}_confirmation).
     *
     * @var array<string, string|array>
     */
    protected array $rules = [];

    /**
     * Custom error messages overriding default validation messages.
     * Keys follow the format "field.rule" (e.g. "email.required").
     *
     * @var array<string, string>
     */
    protected array $messages = [];

    /**
     * Validation errors populated after a failed validate() call.
     *
     * @var array<string, string[]>
     */
    private array $validationErrors = [];

    /**
     * Validate the current entity against $this->rules.
     *
     * Returns true when all rules pass.  On failure the errors are accessible
     * via getErrors() / getFirstError() and this method returns false.
     *
     * @return bool True if valid
     */
    public function validate(): bool
    {
        $validator = new RecordValidator($this->rules, $this->messages, $this->uniqueValueChecker());

        $valid = $validator->validate($this->getArrayCopy());
        $this->validationErrors = $validator->errors();

        return $valid;
    }

    /**
     * The lookup the `unique` rule needs: whether a value is already present in
     * the entity's own table, or in the table.column the rule names.
     *
     * It lives here rather than in RecordValidator because it is the only rule
     * that needs a connection and the entity's metadata, and neither belongs to
     * a rule engine. A model with no connection reports nothing taken, which is
     * what the rule has always done.
     *
     * @return Closure fn(string $field, mixed $value, ?string $ruleParam): bool
     */
    private function uniqueValueChecker(): Closure
    {
        return function (string $field, mixed $value, ?string $ruleParam): bool {
            if (!self::$db) {
                return false;
            }

            [$tbl, $col] = array_pad(explode('.', $ruleParam ?? ''), 2, null);

            if ($tbl === null) {
                $meta = $this->getMetadata();
                $tbl = $meta['table'];
                $col = $meta['columns'][$field]['name'] ?? $field;
            }

            $stmt = self::$db->prepare("SELECT COUNT(*) FROM `{$tbl}` WHERE `{$col}` = ?");
            $stmt->execute([$value]);

            return (int) $stmt->fetchColumn() > 0;
        };
    }
    /**
     * Get all validation errors indexed by field name.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Get the first error message for a specific field, or overall first error.
     *
     * @param string|null $field Specific field or null for the very first error
     *
     * @return string|null Error message or null when clean
     */
    public function getFirstError(?string $field = null): ?string
    {
        if ($field !== null) {
            return $this->validationErrors[$field][0] ?? null;
        }

        foreach ($this->validationErrors as $errors) {
            return $errors[0] ?? null;
        }

        return null;
    }

    /**
     * Validate and throw an exception if invalid.
     *
     * @return static This instance
     * @throws Exception When validation fails, message contains JSON-encoded errors
     */
    public function validateOrFail(): static
    {
        if (!$this->validate()) {
            throw new Exception('Validation failed: ' . json_encode($this->validationErrors));
        }
        return $this;
    }

    /**
     * Named local scopes registered at runtime.
     *
     * @var array<string, callable>
     */
    private array $localScopes = [];

    /**
     * Global scopes applied to all queries for this class.
     *
     * @var array<string, array<string, callable>>
     */
    private static array $globalScopes = [];

    /**
     * Register a named local query scope.
     *
     * Usage: $this->addScope('active', fn($q) => $q->where('status', 'active'));
     *        $this->scope('active')->get();
     *
     * @param string   $name     Scope name
     * @param callable $callback Receives $this (ActiveRecord) for chaining
     *
     * @return static
     */
    public function addScope(string $name, callable $callback): static
    {
        $this->localScopes[$name] = $callback;
        return $this;
    }

    /**
     * Apply a registered named scope to the current query builder.
     *
     * @param string $name Scope name
     * @param mixed  ...$args Extra arguments forwarded to the scope callback
     *
     * @return static
     * @throws Exception When scope not found
     */
    public function scope(string $name, mixed ...$args): static
    {
        // First check local scopes registered via addScope()
        if (isset($this->localScopes[$name])) {
            ($this->localScopes[$name])($this, ...$args);
            return $this;
        }

        // Then look for a scopeName() method on the subclass
        $method = 'scope' . ucfirst($name);
        if (method_exists($this, $method)) {
            $this->$method(...$args);
            return $this;
        }

        throw new Exception("Scope '{$name}' not found.");
    }

    /**
     * Register a global scope that is automatically applied to every query.
     *
     * @param string   $name     Unique scope identifier (use to remove later)
     * @param callable $callback Receives ActiveRecord instance
     *
     * @return void
     */
    public static function addGlobalScope(string $name, callable $callback): void
    {
        $class = static::class;
        self::$globalScopes[$class][$name] = $callback;
    }

    /**
     * Remove a previously registered global scope.
     *
     * @param string $name Scope identifier
     *
     * @return void
     */
    public static function removeGlobalScope(string $name): void
    {
        $class = static::class;
        unset(self::$globalScopes[$class][$name]);
    }

    /**
     * Apply all registered global scopes to the current query.
     *
     * Called automatically inside get() / first() / count() etc.
     *
     * @return static
     */
    protected function applyGlobalScopes(): static
    {
        $class = get_class($this);
        foreach (self::$globalScopes[$class] ?? [] as $callback) {
            $callback($this);
        }
        return $this;
    }

    /**
     * Relationship count aliases to load alongside the entity.
     *
     * @var array<string, string>  relation => alias
     */
    private array $withCountRelations = [];

    /**
     * Load the count of one or more related entities as virtual attributes.
     *
     * After calling ->withCount(['comments', 'tags'])->get(), each entity
     * will have "comments_count" and "tags_count" attributes set.
     *
     * @param array $relations Relation names to count
     *
     * @return static
     */
    public function withCount(array $relations): static
    {
        foreach ($relations as $relation) {
            $this->withCountRelations[$relation] = $relation . '_count';
        }
        return $this;
    }

    /**
     * Populate {relation}_count attributes on an entity after loading.
     *
     * @param object $entity     Target entity
     * @param array  $relations  Relation names
     *
     * @return void
     */
    private function applyWithCount(object $entity, array $relations): void
    {
        $metadata = $this->getMetadata();

        foreach ($relations as $relation => $alias) {
            if (!isset($metadata['relationships'][$relation])) {
                continue;
            }

            $rel = $metadata['relationships'][$relation];
            $type = $rel['type'];
            $pkName = $metadata['primaryKey'];
            $pkValue = $entity[$pkName] ?? null;

            if ($pkValue === null) {
                $entity[$alias] = 0;
                continue;
            }

            if ($type === 'oneToMany' || $type === 'manyToMany') {
                $targetClass = $rel['targetEntity'];
                $targetInstance = new $targetClass();
                $targetMeta = self::$reflectionCache[get_class($targetInstance)];
                $targetTable = $targetMeta['table'];

                if ($type === 'oneToMany') {
                    $mappedBy = $rel['mappedBy'];
                    $fkCol = $targetMeta['columns'][$mappedBy]['name']
                        ?? ($targetMeta['relationships'][$mappedBy]['joinColumn']['name'] ?? null);
                    if ($fkCol) {
                        $stmt = self::$db->prepare("SELECT COUNT(*) FROM `{$targetTable}` WHERE `{$fkCol}` = ?");
                        $stmt->execute([$pkValue]);
                        $entity[$alias] = (int) $stmt->fetchColumn();
                    } else {
                        $entity[$alias] = 0;
                    }
                } else {
                    // manyToMany
                    $joinTable = $rel['joinTable']['name'] ?? null;
                    $sourceCol = $rel['joinTable']['joinColumn']['name'] ?? null;
                    if ($joinTable && $sourceCol) {
                        $stmt = self::$db->prepare("SELECT COUNT(*) FROM `{$joinTable}` WHERE `{$sourceCol}` = ?");
                        $stmt->execute([$pkValue]);
                        $entity[$alias] = (int) $stmt->fetchColumn();
                    } else {
                        $entity[$alias] = 0;
                    }
                }
            } else {
                $entity[$alias] = 0;
            }
        }
    }

    /**
     * Find multiple entities by an array of primary key values.
     *
     * @param array $ids   Primary key values to retrieve
     * @param array $options Additional query options
     *
     * @return array Entities indexed by primary key value
     */
    public function findMany(array $ids, array $options = []): array
    {
        if (empty($ids)) {
            return [];
        }

        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkCol = $metadata['columns'][$pkName]['name'];

        $options['conditions'][$pkCol] = ['value' => $ids, 'operator' => 'IN'];
        return $this->findAll($options);
    }

    /**
     * Create multiple entities and persist them in a single batch INSERT.
     *
     * Each element of $rows is an associative array of property => value.
     * Unlike batchInsert(), this method also returns the new entity instances.
     *
     * @param array $rows   Rows to insert
     *
     * @return array Created entity instances (without auto-increment IDs)
     */
    public static function createMany(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $instances = [];
        foreach ($rows as $attrs) {
            $instance = new static();
            foreach ($attrs as $k => $v) {
                $instance[$k] = $v;
            }
            $instances[] = $instance;
        }

        static::batchInsert($instances);
        return $instances;
    }

    /**
     * Save the entity without firing any lifecycle events.
     *
     * @param array $debug Optional debug options forwarded to save()
     *
     * @return static
     */
    public function saveQuietly(array $debug = []): static
    {
        return $this->save($debug);
    }

    /**
     * Delete the entity without firing any lifecycle events.
     *
     * @return bool True if deleted
     */
    public function deleteQuietly(): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        if ($this->softDeleteColumn) {
            // Bypass event hooks – write the timestamp directly
            $metadata = $this->getMetadata();
            $pkName = $metadata['primaryKey'];
            if (empty($this[$pkName])) {
                throw new Exception("Cannot soft delete entity without primary key");
            }
            $this[$this->softDeleteColumn] = date('Y-m-d H:i:s');
            return $this->save() !== null;
        }

        return $this->delete();
    }

    /**
     * Execute a callback with automatic timestamp management temporarily disabled.
     *
     * @param callable $callback  Receives $this; its save() calls won't touch timestamps
     *
     * @return mixed Return value of $callback
     */
    public function withoutTimestamps(callable $callback): mixed
    {
        $original = $this->timestamps;
        $this->timestamps = false;
        try {
            return $callback($this);
        } finally {
            $this->timestamps = $original;
        }
    }

    /**
     * Manually set a relationship value on this entity.
     *
     * Useful for injecting pre-loaded or stub relation data without a DB call.
     *
     * @param string $relation  Relation property name
     * @param mixed  $value     Entity, array of entities, or null
     *
     * @return static
     */
    public function setRelation(string $relation, mixed $value): static
    {
        $this->offsetSet($relation, $value);
        return $this;
    }

    /**
     * Get a currently loaded relationship value without triggering lazy loading.
     *
     * Returns null if the relation has not been loaded yet.
     *
     * @param string $relation  Relation property name
     *
     * @return mixed Loaded relation value or null
     */
    public function getRelation(string $relation): mixed
    {
        return $this->offsetExists($relation) ? $this->offsetGet($relation) : null;
    }

    /**
     * Lazy-load one or more relationships only if they are not already loaded.
     *
     * @param array $relations  Relation property names
     *
     * @return static
     */
    public function loadMissing(array $relations): static
    {
        $metadata = $this->getMetadata();

        foreach ($relations as $relation) {
            if ($this->offsetExists($relation)) {
                continue; // Already loaded – skip
            }

            if (isset($metadata['relationships'][$relation])) {
                (new RelationLoader($this))->load($relation, $metadata['relationships'][$relation]);
            }
        }

        return $this;
    }

    /**
     * Create a new unsaved instance of this model with given attributes.
     *
     * Unlike create(), this method does NOT persist to the database.
     *
     * @param array $attributes  Property => value pairs
     *
     * @return static New unsaved instance
     */
    public static function newInstance(array $attributes = []): static
    {
        $instance = new static();
        foreach ($attributes as $k => $v) {
            $instance[$k] = $v;
        }
        return $instance;
    }

    /**
     * Deep-clone the current entity.
     *
     * All column values are copied; the primary key and exists flag are reset
     * so that calling save() on the clone creates a new database row.
     *
     * @param array $overrides  Property => value overrides applied after cloning
     *
     * @return static New unsaved clone
     */
    public function cloneInstance(array $overrides = []): static
    {
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $clone = new static();

        foreach ($metadata['columns'] as $property => $columnInfo) {
            if ($property !== $pkName) {
                $clone[$property] = $this[$property] ?? null;
            }
        }

        foreach ($overrides as $k => $v) {
            $clone[$k] = $v;
        }

        // clone starts as a new record
        $clone->exists = false;

        return $clone;
    }

    /**
     * Execute a SUM aggregate using the current query builder state.
     *
     * @param string $column  Property name to aggregate
     *
     * @return float
     */
    protected function querySum(string $column): float
    {
        return (float) $this->runQueryAggregate('SUM', $column);
    }

    /**
     * Execute an AVG aggregate using the current query builder state.
     *
     * @param string $column  Property name to aggregate
     *
     * @return float
     */
    protected function queryAvg(string $column): float
    {
        return (float) $this->runQueryAggregate('AVG', $column);
    }

    /**
     * Execute a MIN aggregate using the current query builder state.
     *
     * @param string $column  Property name to aggregate
     *
     * @return mixed
     */
    protected function queryMin(string $column): mixed
    {
        return $this->runQueryAggregate('MIN', $column);
    }

    /**
     * Execute a MAX aggregate using the current query builder state.
     *
     * @param string $column  Property name to aggregate
     *
     * @return mixed
     */
    protected function queryMax(string $column): mixed
    {
        return $this->runQueryAggregate('MAX', $column);
    }

    /**
     * Internal helper: run an aggregate function with the current query builder options.
     *
     * @param string $function  SQL aggregate name (SUM, AVG, MIN, MAX)
     * @param string $column    Property name to aggregate
     *
     * @return mixed Raw aggregate result
     */
    private function runQueryAggregate(string $function, string $column): mixed
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        if (!isset($metadata['columns'][$column])) {
            throw new Exception("Column {$column} not found in metadata");
        }

        $colName = $metadata['columns'][$column]['name'];
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $sql = "SELECT {$function}(`{$colName}`) as result FROM `{$tableName}`";
        $modified = $this->modifyQueryByOptions($sql, $options);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);
        $this->resetQuery();

        return $stmt->fetchColumn();
    }

    /**
     * Return the SQL for the current query builder state with bindings interpolated.
     *
     * Intended for debugging only – values are escaped but this should NOT
     * be used to build real queries.
     *
     * @return string Human-readable SQL with values inlined
     */
    public function toSqlWithBindings(): string
    {
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $distinct = !empty($options['distinct']) ? 'DISTINCT ' : '';
        $selectRaw = !empty($options['select']) ? implode(', ', $options['select']) : "`{$tableName}`.*";
        $joinSql = '';
        if (!empty($options['joins'])) {
            foreach ($options['joins'] as $j) {
                // Every component was validated and quoted by buildJoinEntry() / crossJoin().
                $joinSql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        $prefixBindings = [];

        if (!empty($options['ctes'])) {
            [$ctePrefix, $cteBindings] = $this->buildCtePrefix($options['ctes']);
            $prefixBindings = array_merge($prefixBindings, $cteBindings);
        } else {
            $ctePrefix = '';
        }

        if (!empty($options['from_subquery'])) {
            $fsq = $options['from_subquery'];
            $prefixBindings = array_merge($prefixBindings, $fsq['bindings']);
            $fsqAlias = SqlIdentifier::quote($fsq['alias']);
            $sql = "{$ctePrefix}SELECT {$distinct}{$selectRaw} FROM ({$fsq['sql']}) AS {$fsqAlias}{$joinSql}";
        } else {
            $sql = "{$ctePrefix}SELECT {$distinct}{$selectRaw} FROM `{$tableName}`{$joinSql}";
        }

        $modified = $this->modifyQueryByOptions($sql, $options);
        $this->resetQuery();

        $query = $modified['query'];
        $bindings = array_merge($prefixBindings, $modified['parameters']);

        // Interpolate bindings for readability
        foreach ($bindings as $binding) {
            $escaped = is_null($binding) ? 'NULL' : ("'" . addslashes((string) $binding) . "'");
            $query = preg_replace('/\?/', $escaped, $query, 1);
        }

        return $query;
    }

    /**
     * Dump the entity's column data and optionally die.
     *
     * @param bool $die  When true, calls exit() after dumping (dd-style)
     *
     * @return static
     */
    public function dump(bool $die = false): static
    {
        $data = $this->toArray();
        // Use var_dump for raw output so it works in both CLI and web contexts
        var_dump($data);
        if ($die) {
            exit(1);
        }
        return $this;
    }

    /**
     * Dump the entity and immediately terminate the script.
     *
     * @return void
     */
    public function dumpAndDie(): void
    {
        $this->dump(true);
    }

    /**
     * Convert an array of entities to a key-value map.
     *
     * @param array  $entities  Collection of ActiveRecord instances
     * @param string $keyField  Entity property to use as the map key
     * @param string|null $valueField  Optional property to use as value; null returns the entity
     *
     * @return array<mixed, mixed>
     */
    public static function keyBy(array $entities, string $keyField, ?string $valueField = null): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $key = $entity[$keyField] ?? null;
            if ($key !== null) {
                $result[$key] = $valueField !== null ? ($entity[$valueField] ?? null) : $entity;
            }
        }
        return $result;
    }

    /**
     * Group an array of entities by a given field value.
     *
     * @param array  $entities   Collection of ActiveRecord instances
     * @param string $field      Property to group by
     *
     * @return array<mixed, array>  Groups indexed by field value
     */
    public static function groupByField(array $entities, string $field): array
    {
        $result = [];
        foreach ($entities as $entity) {
            $key = $entity[$field] ?? null;
            $result[$key][] = $entity;
        }
        return $result;
    }

    /**
     * Return only distinct values of a given field from a collection.
     *
     * @param array  $entities  Collection of ActiveRecord instances
     * @param string $field     Property to extract
     *
     * @return array Unique values
     */
    public static function distinctValues(array $entities, string $field): array
    {
        return array_unique(array_map(fn($e) => $e[$field] ?? null, $entities));
    }

    /**
     * Filter a collection of entities using a callback.
     *
     * @param array    $entities  Collection of ActiveRecord instances
     * @param callable $callback  fn(entity): bool
     *
     * @return array Filtered entities (re-indexed)
     */
    public static function filter(array $entities, callable $callback): array
    {
        return array_values(array_filter($entities, $callback));
    }

    /**
     * Sort a collection of entities by a given field.
     *
     * @param array  $entities   Collection of ActiveRecord instances
     * @param string $field      Property to sort by
     * @param string $direction  'ASC' or 'DESC'
     *
     * @return array Sorted entities
     */
    public static function sortBy(array $entities, string $field, string $direction = 'ASC'): array
    {
        usort($entities, function ($a, $b) use ($field, $direction) {
            $av = $a[$field] ?? null;
            $bv = $b[$field] ?? null;
            return $direction === 'DESC' ? ($bv <=> $av) : ($av <=> $bv);
        });
        return $entities;
    }

    /**
     * Wrap a collection of entities in an ArrayObject for fluent access.
     *
     * @param array $entities  Collection of ActiveRecord instances
     *
     * @return ArrayObject
     */
    public static function toCollection(array $entities): ArrayObject
    {
        return new ArrayObject($entities, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * Get all registered event hooks for the current (or given) class.
     *
     * @param string|null $class  Fully-qualified class name or null for current
     *
     * @return array<string, callable[]>
     */
    public static function getEventHooks(?string $class = null): array
    {
        $class = $class ?? static::class;
        return self::$eventHooks[$class] ?? [];
    }

    /**
     * Return the full column metadata registered for this entity.
     *
     * Useful for introspection and tooling.
     *
     * @return array<string, array{name: string, type: string, nullable: bool}>
     */
    public function getColumnMetadata(): array
    {
        return $this->getMetadata()['columns'] ?? [];
    }

    /**
     * Return all registered relationship metadata for this entity.
     *
     * @return array<string, array>
     */
    public function getRelationshipMetadata(): array
    {
        return $this->getMetadata()['relationships'] ?? [];
    }

    /**
     * Check whether a given relationship has already been loaded (eager or lazy).
     *
     * @param string $relation  Relation property name
     *
     * @return bool True if the relation data is present on this instance
     */
    public function relationLoaded(string $relation): bool
    {
        return $this->offsetExists($relation);
    }

    /**
     * Return the entity as a plain stdClass object.
     *
     * @param array $hidden  Additional fields to exclude
     *
     * @return \stdClass
     */
    public function toObject(array $hidden = []): \stdClass
    {
        return (object) $this->toArray($hidden);
    }

    /**
     * Check if two ActiveRecord instances represent the same database record.
     *
     * Two entities are considered the same when they share the same class,
     * the same primary key column, and the same primary key value.
     *
     * @param object $other  Entity to compare against
     *
     * @return bool True if the same record
     */
    public function is(object $other): bool
    {
        if (get_class($this) !== get_class($other)) {
            return false;
        }

        $pkName = $this->getPrimaryKey();
        if ($pkName === null) {
            return false;
        }

        return $this[$pkName] !== null && (string) $this[$pkName] === (string) $other[$pkName];
    }

    /**
     * Check if the entity does NOT represent the same record as another.
     *
     * @param object $other  Entity to compare against
     *
     * @return bool True if different records
     */
    public function isNot(object $other): bool
    {
        return !$this->is($other);
    }

    /**
     * Return human-readable string representation (class + primary key value).
     *
     * @return string
     */
    public function __toString(): string
    {
        $pkName = $this->getPrimaryKey();
        $pkValue = $pkName ? ($this[$pkName] ?? 'null') : 'null';
        return get_class($this) . '#' . $pkValue;
    }

    /**
     * Enable query-level caching for the next SELECT execution.
     *
     * Usage: $user->cache(60)->where('is_active', 1)->get();
     *
     * @param int $seconds  Time-to-live in seconds
     *
     * @return static
     */
    public function cache(int $seconds): static
    {
        $this->queryCacheTtl = $seconds;
        return $this;
    }

    /**
     * Retrieve a cached result by cache key, or null if expired/missing.
     * Moves accessed entries to the end to implement LRU ordering.
     *
     * @param string $key  Cache key (typically an SQL hash)
     *
     * @return mixed|null  Cached value or null
     */
    private static function getCachedKey(string $key): mixed
    {
        return QueryResultCache::get($key);
    }

    /**
     * Store a value in the query cache.
     * Evicts the oldest 20% of entries when the cache limit is reached.
     *
     * @param string $key      Cache key
     * @param mixed  $data     Data to cache
     * @param int    $ttl      Time-to-live in seconds (0 = forever)
     *
     * @return void
     */
    private static function setCache(string $key, mixed $data, int $ttl): void
    {
        QueryResultCache::put($key, $data, $ttl);
    }

    /**
     * Flush all entries from the in-memory query cache.
     *
     * @return void
     */
    public static function flushCache(): void
    {
        QueryResultCache::flush();
    }

    /**
     * Set the maximum number of query cache entries.
     *
     * @param int $limit  Max cache entries (default 1000)
     *
     * @return void
     */
    public static function setQueryCacheLimit(int $limit): void
    {
        QueryResultCache::setLimit($limit);
    }

    /**
     * Execute current query with cache support and return results.
     *
     * If a cached version exists and has not expired, it is returned
     * without hitting the database.
     *
     * @return array  Entity instances
     */
    public function getCached(): array
    {
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $cacheKey = md5(json_encode($options) . get_class($this));
        $ttl = $this->queryCacheTtl;
        $this->queryCacheTtl = 0;

        if ($ttl > 0) {
            $cached = self::getCachedKey($cacheKey);
            if ($cached !== null) {
                $this->resetQuery();
                return $cached;
            }
        }

        $results = $this->findAll($options);
        $this->resetQuery();

        if ($ttl > 0) {
            self::setCache($cacheKey, $results, $ttl);
        }

        return $results;
    }

    /**
     * Enable query logging. All subsequent queries will be recorded.
     *
     * @return void
     */
    public static function enableQueryLog(): void
    {
        QueryLog::enable();
    }

    /**
     * Disable query logging.
     *
     * @return void
     */
    public static function disableQueryLog(): void
    {
        QueryLog::disable();
    }

    /**
     * Retrieve and flush the query log.
     *
     * @return array<int, array{sql: string, bindings: array, time_ms: float}>
     */
    public static function getQueryLog(): array
    {
        return QueryLog::flush();
    }

    /**
     * Record a query to the internal log.
     * Drops oldest entries when the log exceeds its limit to prevent
     * unbounded memory growth in long-running workers/daemons.
     *
     * @param string $sql      SQL statement
     * @param array  $bindings Bound parameters
     * @param float  $timeMs   Execution time in milliseconds
     *
     * @return void
     */
    protected static function logQuery(string $sql, array $bindings, float $timeMs): void
    {
        QueryLog::record($sql, $bindings, $timeMs);
    }

    /**
     * Attributes that should be encrypted when stored and decrypted when read.
     *
     * @var string[]
     */
    protected array $encrypted = [];

    /**
     * Set the global encryption key used for encrypted attribute handling.
     *
     * @param string $key  Encryption key (should be 32 bytes for AES-256)
     *
     * @return void
     */
    public static function setEncryptionKey(string $key): void
    {
        AttributeEncrypter::setKey($key);
    }

    /**
     * Encrypt a plain-text value using AES-256-CBC.
     *
     * @param string $value  Plain text
     *
     * @return string  Base64-encoded ciphertext with IV prepended
     * @throws Exception When encryption key not set
     */
    protected static function encryptValue(string $value): string
    {
        return AttributeEncrypter::encrypt($value);
    }

    /**
     * Decrypt a previously encrypted value.
     *
     * @param string $payload  Base64-encoded ciphertext with IV
     *
     * @return string  Decrypted plain text
     * @throws Exception When encryption key not set or decryption fails
     */
    protected static function decryptValue(string $payload): string
    {
        return AttributeEncrypter::decrypt($payload);
    }

    /**
     * Check if a given attribute is in the encrypted list.
     *
     * @param string $attribute  Attribute name
     *
     * @return bool  True if the attribute should be encrypted/decrypted
     */
    public function isEncrypted(string $attribute): bool
    {
        return in_array($attribute, $this->encrypted);
    }

    /**
     * Optimistic locking version column name (null = disabled).
     * When enabled, UPDATE queries include a WHERE version = ? guard
     * and auto-increment the version on save, preventing lost updates
     * under concurrent writes.
     *
     * @var string|null
     */
    protected ?string $versionColumn = null;

    /**
     * Save with optimistic locking.
     *
     * Compares the in-memory version number against the database value.
     * If they differ, a concurrent modification has occurred and an
     * exception is thrown instead of overwriting the other change.
     *
     * @return static
     * @throws Exception When a concurrent modification is detected
     */
    public function saveWithLock(): static
    {
        if (!$this->versionColumn) {
            return $this->save();
        }

        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];

        if (!$this->exists || empty($this[$pkName])) {
            $this[$this->versionColumn] = 1;
            return $this->save();
        }

        $currentVersion = (int) ($this[$this->versionColumn] ?? 0);
        $newVersion = $currentVersion + 1;

        $tableName = $metadata['table'];
        $pkColumn = $metadata['columns'][$pkName]['name'];
        $versionCol = $metadata['columns'][$this->versionColumn]['name'];

        $storedData = $this->getArrayCopy();
        $data = [];
        foreach ($metadata['columns'] as $property => $columnInfo) {
            if ($property === $pkName || $property === $this->versionColumn) {
                continue;
            }
            if (array_key_exists($property, $storedData)) {
                $data[$columnInfo['name']] = $this->prepareValueForDatabase($storedData[$property], $columnInfo['type']);
            }
        }

        $setParts = [];
        $params = [];
        foreach ($data as $col => $val) {
            $setParts[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $setParts[] = "`{$versionCol}` = ?";
        $params[] = $newVersion;

        // WHERE pk = ? AND version = currentVersion
        $params[] = $this[$pkName];
        $params[] = $currentVersion;

        $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts)
            . " WHERE `{$pkColumn}` = ? AND `{$versionCol}` = ?";

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            throw new Exception("Optimistic lock failure: the record was modified by another process.");
        }

        $this[$this->versionColumn] = $newVersion;
        $this->syncOriginal();
        $this->dirtyFields = [];

        return $this;
    }

    /**
     * Compute the difference between this entity and another of the same class.
     *
     * Returns an associative array of property names where the values differ,
     * each containing 'from' (this) and 'to' (other) values.
     *
     * @param object $other  Another entity to compare against
     *
     * @return array<string, array{from: mixed, to: mixed}>
     */
    public function diff(object $other): array
    {
        $metadata = $this->getMetadata();
        $changes = [];

        foreach ($metadata['columns'] as $property => $columnInfo) {
            $thisVal = $this[$property] ?? null;
            $otherVal = $other[$property] ?? null;

            if ($thisVal !== $otherVal) {
                $changes[$property] = ['from' => $thisVal, 'to' => $otherVal];
            }
        }

        return $changes;
    }

    /**
     * Apply an array of callbacks (pipes) sequentially to the current query.
     *
     * Each callback receives $this and may add where clauses, ordering, etc.
     *
     * @param callable[] $pipes  Array of fn(ActiveRecord): void
     *
     * @return static
     */
    public function pipeline(array $pipes): static
    {
        foreach ($pipes as $pipe) {
            $pipe($this);
        }

        return $this;
    }

    /**
     * Cursor-based pagination using a keyset approach.
     *
     * More efficient than offset-based pagination for large data sets.
     * Returns items after the given cursor value ordered by $column ASC.
     *
     * @param int         $perPage  Number of items per page
     * @param mixed|null  $cursor   Last seen primary key value (null for first page)
     * @param string|null $column   Column to paginate by (default: primary key)
     *
     * @return array{data: array, next_cursor: mixed|null, has_more: bool}
     */
    public function cursorPaginate(int $perPage = 15, mixed $cursor = null, ?string $column = null): array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $col = $column ?? $metadata['primaryKey'];

        if (!isset($metadata['columns'][$col])) {
            throw new Exception("Column {$col} not found in metadata");
        }

        $colName = $metadata['columns'][$col]['name'];

        $options = [
            'order' => $colName,
            'arrange' => 'ASC',
            'limit' => $perPage + 1,
        ];

        if ($cursor !== null) {
            $options['conditions'][$colName] = ['value' => $cursor, 'operator' => '>'];
        }

        $items = $this->findAll($options);
        $hasMore = count($items) > $perPage;

        if ($hasMore) {
            array_pop($items);
        }

        $nextCursor = !empty($items) ? end($items)[$col] ?? null : null;

        return [
            'data' => $items,
            'next_cursor' => $hasMore ? $nextCursor : null,
            'has_more' => $hasMore,
        ];
    }

    /**
     * Add a WHERE clause checking that a JSON column contains a value.
     *
     * Translates to: JSON_CONTAINS(`column`, '"value"')
     *
     * @param string $column  Column name
     * @param mixed  $value   Value to search for in the JSON
     *
     * @return static
     */
    protected function whereJsonContains(string $column, mixed $value): static
    {
        $jsonValue = json_encode($value);
        return $this->whereRaw('JSON_CONTAINS(' . SqlIdentifier::quote($column) . ', ?)', [$jsonValue]);
    }

    /**
     * Add a WHERE clause extracting a JSON path value.
     *
     * Translates to: JSON_EXTRACT(`column`, '$.path') operator value
     *
     * @param string $column    Column name
     * @param string $path      JSON path (e.g., '$.address.city')
     * @param string $operator  Comparison operator
     * @param mixed  $value     Value to compare
     *
     * @return static
     */
    protected function whereJsonPath(string $column, string $path, string $operator, mixed $value): static
    {
        $quotedColumn = SqlIdentifier::quote($column);
        $safeOperator = SqlIdentifier::operator($operator);

        return $this->whereRaw("JSON_EXTRACT({$quotedColumn}, ?) {$safeOperator} ?", [$path, $value]);
    }

    /**
     * Add a WHERE clause for MySQL full-text MATCH … AGAINST search.
     *
     * @param array  $columns  Column names to search
     * @param string $query    Search query string
     * @param string $mode     Search mode: 'natural', 'boolean', or 'expansion'
     *
     * @return static
     */
    protected function whereFullText(array $columns, string $query, string $mode = 'natural'): static
    {
        $cols = SqlIdentifier::quoteList($columns);

        $modeClause = match ($mode) {
            'boolean' => ' IN BOOLEAN MODE',
            'expansion' => ' WITH QUERY EXPANSION',
            default => ' IN NATURAL LANGUAGE MODE',
        };

        return $this->whereRaw("MATCH({$cols}) AGAINST(? {$modeClause})", [$query]);
    }

    /**
     * Add a WHERE EXISTS (subquery) clause.
     *
     * @param string $subquery  Raw SQL subquery
     * @param array  $bindings  Bindings for the subquery
     *
     * @return static
     */
    protected function whereExists(string $subquery, array $bindings = []): static
    {
        return $this->whereRaw("EXISTS ({$subquery})", $bindings);
    }

    /**
     * Add a WHERE NOT EXISTS (subquery) clause.
     *
     * @param string $subquery  Raw SQL subquery
     * @param array  $bindings  Bindings for the subquery
     *
     * @return static
     */
    protected function whereNotExists(string $subquery, array $bindings = []): static
    {
        return $this->whereRaw("NOT EXISTS ({$subquery})", $bindings);
    }

    /**
     * Add a WHERE column IN (subquery) clause.
     *
     * @param string $column    Column name
     * @param string $subquery  Raw SQL subquery returning single column
     * @param array  $bindings  Bindings for the subquery
     *
     * @return static
     */
    protected function whereInSubquery(string $column, string $subquery, array $bindings = []): static
    {
        // The subquery is a trusted-input expression and stays raw; the column is validated.
        return $this->whereRaw(SqlIdentifier::quote($column) . " IN ({$subquery})", $bindings);
    }

    /**
     * Delete all records matching the current query builder state.
     *
     * @return int  Number of deleted rows
     */
    public function deleteWhere(): int
    {
        $this->ensureConnection();
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $sql = "DELETE FROM `{$tableName}`";
        $modified = $this->modifyQueryByOptions($sql, $options);
        $this->resetQuery();

        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);

        return $stmt->rowCount();
    }

    /**
     * Update all records matching the current query builder state.
     *
     * @param array $values  Property => value pairs to set
     *
     * @return int  Number of affected rows
     */
    public function updateWhere2(array $values): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $setParts = [];
        $setParams = [];
        foreach ($values as $prop => $val) {
            if (isset($metadata['columns'][$prop])) {
                $setParts[] = "`{$metadata['columns'][$prop]['name']}` = ?";
                $setParams[] = $val;
            }
        }

        if (empty($setParts)) {
            return 0;
        }

        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts);
        $modified = $this->modifyQueryByOptions($sql, $options);
        $this->resetQuery();

        $params = array_merge($setParams, $modified['parameters']);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Apply a callback to each entity in a collection and return the mapped results.
     *
     * @param array    $entities  Array of ActiveRecord instances
     * @param callable $callback  fn(entity): mixed
     *
     * @return array  Mapped values
     */
    public static function map(array $entities, callable $callback): array
    {
        return array_map($callback, $entities);
    }

    /**
     * Reduce a collection of entities to a single value.
     *
     * @param array    $entities  Array of ActiveRecord instances
     * @param callable $callback  fn(carry, entity): mixed
     * @param mixed    $initial   Initial carry value
     *
     * @return mixed  Final reduced value
     */
    public static function reduce(array $entities, callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($entities, $callback, $initial);
    }

    /**
     * Partition a collection into two arrays using a truth test.
     *
     * The first array contains entities for which the callback returns true,
     * the second contains the rest.
     *
     * @param array    $entities  Array of ActiveRecord instances
     * @param callable $callback  fn(entity): bool
     *
     * @return array{0: array, 1: array}  [passing, failing]
     */
    public static function partition(array $entities, callable $callback): array
    {
        $pass = [];
        $fail = [];

        foreach ($entities as $entity) {
            if ($callback($entity)) {
                $pass[] = $entity;
            } else {
                $fail[] = $entity;
            }
        }

        return [$pass, $fail];
    }

    /**
     * Extract a single column value from each entity in a collection.
     *
     * @param array  $entities  Array of ActiveRecord instances
     * @param string $column    Property name to extract
     *
     * @return array  Array of extracted values
     */
    public static function column(array $entities, string $column): array
    {
        return array_map(fn($e) => $e[$column] ?? null, $entities);
    }

    /**
     * Return the first entity matching a callback, or null if none match.
     *
     * @param array    $entities  Array of ActiveRecord instances
     * @param callable $callback  fn(entity): bool
     *
     * @return object|null  First matching entity or null
     */
    public static function firstWhere(array $entities, callable $callback): ?object
    {
        foreach ($entities as $entity) {
            if ($callback($entity)) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * Determine if every entity in a collection passes a truth test.
     *
     * @param array    $entities  Array of ActiveRecord instances
     * @param callable $callback  fn(entity): bool
     *
     * @return bool  True if all pass
     */
    public static function every(array $entities, callable $callback): bool
    {
        foreach ($entities as $entity) {
            if (!$callback($entity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any entity in a collection passes a truth test.
     *
     * @param array    $entities  Array of ActiveRecord instances
     * @param callable $callback  fn(entity): bool
     *
     * @return bool  True if at least one passes
     */
    public static function some(array $entities, callable $callback): bool
    {
        foreach ($entities as $entity) {
            if ($callback($entity)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return an array with only the specified attributes.
     *
     * @param array $keys  Property names to include
     *
     * @return array  Filtered associative array
     */
    public function only(array $keys): array
    {
        $data = $this->toArray();
        return array_intersect_key($data, array_flip($keys));
    }

    /**
     * Return an array with all attributes except the specified ones.
     *
     * @param array $keys  Property names to exclude
     *
     * @return array  Filtered associative array
     */
    public function except(array $keys): array
    {
        $data = $this->toArray();
        return array_diff_key($data, array_flip($keys));
    }

    /**
     * Set an attribute only if the value is not null.
     *
     * @param string $key    Attribute name
     * @param mixed  $value  Value to set (skipped if null)
     *
     * @return static
     */
    public function setIfNotNull(string $key, mixed $value): static
    {
        if ($value !== null) {
            $this[$key] = $value;
        }

        return $this;
    }

    /**
     * Set an attribute only if it is currently empty (null or unset).
     *
     * @param string $key    Attribute name
     * @param mixed  $value  Value to set
     *
     * @return static
     */
    public function setDefault(string $key, mixed $value): static
    {
        if (!isset($this[$key]) || $this[$key] === null) {
            $this[$key] = $value;
        }

        return $this;
    }

    /**
     * Set multiple ORDER BY clauses at once.
     *
     * @param array $orders  Array of [column => direction] pairs
     *
     * @return static
     */
    protected function orderByMultiple(array $orders): static
    {
        $expressions = [];
        foreach ($orders as $column => $direction) {
            $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $expressions[] = SqlIdentifier::quote((string) $column) . " {$dir}";
        }

        foreach ($expressions as $expr) {
            $this->activeQuery->addOrderByRaw($expr);
        }

        return $this;
    }

    /**
     * Order results randomly.
     *
     * @return static
     */
    protected function inRandomOrder(): static
    {
        $this->activeQuery->addOrderByRaw('RAND()');
        return $this;
    }

    /**
     * Add a CROSS JOIN clause to the query.
     *
     * @param string $table  Table to cross join
     *
     * @return static
     */
    protected function crossJoin(string $table): static
    {
        // A cross join has no real ON condition; the 1 = 1 operands are literals written by this
        // method, not caller input, so they stay unquoted. Only the table is caller-controlled.
        $this->activeQuery->addJoin([
            'type' => 'CROSS',
            'table' => SqlIdentifier::quote($table),
            'first' => '1',
            'operator' => '=',
            'second' => '1',
        ]);

        return $this;
    }

    /**
     * Add a HAVING COUNT(*) condition.
     *
     * @param string $operator  Comparison operator
     * @param int    $value     Count threshold
     *
     * @return static
     */
    protected function havingCount(string $operator, int $value): static
    {
        $safeOperator = SqlIdentifier::operator($operator);

        return $this->havingRaw("COUNT(*) {$safeOperator} ?", [$value]);
    }

    /**
     * Transform attribute values using set{PropertyName}Attribute mutator methods.
     *
     * If a method like setUsernameAttribute() exists, it will be called when
     * the 'username' property is assigned. This method is invoked from __set.
     *
     * @param string $key    Attribute name
     * @param mixed  $value  Incoming value
     *
     * @return mixed  Transformed value
     */
    protected function mutateAttribute(string $key, mixed $value): mixed
    {
        $mutator = 'set' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';

        if (method_exists($this, $mutator)) {
            return $this->$mutator($value);
        }

        return $value;
    }

    /**
     * Serialize the entity to a JSON-decodable array suitable for API responses.
     *
     * Recursively serializes loaded relationships — both annotation-defined
     * and manually injected via setRelation().
     *
     * @return array  Serializable array
     */
    public function jsonSerialize(): array
    {
        $data = $this->toArray();
        $metadata = $this->getMetadata();
        $storedData = $this->getArrayCopy();

        // Collect all known relationship keys from metadata
        $relationKeys = array_keys($metadata['relationships'] ?? []);

        // Also include any array-of-objects or single-object values that were
        // manually set (e.g., via setRelation) but are not in column metadata
        $columnKeys = array_keys($metadata['columns'] ?? []);
        foreach ($storedData as $key => $value) {
            if (!in_array($key, $columnKeys) && !in_array($key, $relationKeys)) {
                $relationKeys[] = $key;
            }
        }

        foreach ($relationKeys as $relation) {
            if ($this->offsetExists($relation)) {
                $loaded = $this->offsetGet($relation);

                if (is_array($loaded)) {
                    $data[$relation] = array_map(
                        fn($e) => ($e instanceof self) ? $e->jsonSerialize() : $e,
                        $loaded
                    );
                } elseif ($loaded instanceof self) {
                    $data[$relation] = $loaded->jsonSerialize();
                }
            }
        }

        return $data;
    }

    /**
     * Check which of the given primary key values exist in the database.
     *
     * @param array $ids  Primary key values to check
     *
     * @return array  Subset of $ids that exist
     */
    public function existingIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkCol = $metadata['columns'][$pkName]['name'];
        $tableName = $metadata['table'];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT `{$pkCol}` FROM `{$tableName}` WHERE `{$pkCol}` IN ({$placeholders})";
        $stmt = self::$db->prepare($sql);
        $stmt->execute(array_values($ids));

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row[$pkCol];
        }

        return $result;
    }

    /**
     * Execute a callback scoped to the current entity, then return the result.
     *
     * Unlike tap() which returns $this, pipe() returns the callback's value.
     *
     * @param callable $callback  fn(ActiveRecord): mixed
     *
     * @return mixed  Callback return value
     */
    public function pipe(callable $callback): mixed
    {
        return $callback($this);
    }

    /**
     * Insert or update a single row and return the entity.
     *
     * Wraps the existing upsert() but also builds and returns an entity instance
     * populated with the merged attributes.
     *
     * @param array $attributes  Unique identifier fields + values
     * @param array $values      Fields to update on conflict
     *
     * @return static  Entity instance (not guaranteed to have auto-ID on update)
     */
    public static function upsertAndReturn(array $attributes, array $values = []): static
    {
        $merged = array_merge($attributes, $values);
        static::upsert([$merged], array_keys($attributes), array_keys($values));

        // Attempt to retrieve the row by the unique attributes
        $instance = new static();
        $entity = $instance->first(['conditions' => $attributes]);

        return $entity ?? static::newInstance($merged);
    }

    /**
     * Return total number of records in the table.
     *
     * @return int
     */
    public static function totalCount(): int
    {
        return (new static())->count();
    }

    /**
     * Process all matching rows in chunks and collect mapped results.
     *
     * @param int      $chunkSize  Number of rows per chunk
     * @param callable $callback   fn(entity): mixed — maps each entity to a value
     * @param array    $options    Query options
     *
     * @return array  Collected mapped values from all chunks
     */
    public function chunkMap(int $chunkSize, callable $callback, array $options = []): array
    {
        $results = [];

        $this->chunk($chunkSize, function ($items) use ($callback, &$results) {
            foreach ($items as $item) {
                $results[] = $callback($item);
            }
        }, $options);

        return $results;
    }

    /**
     * Execute a raw SELECT query and hydrate results as entity instances.
     *
     * @param string $sql     SQL query
     * @param array  $params  Bound parameters
     *
     * @return array  Array of hydrated entity instances
     */
    public static function rawHydrate(string $sql, array $params = []): array
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        $instance = new static();
        $metadata = self::$reflectionCache[get_class($instance)] ?? null;

        if (!$metadata) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $entities = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new static();

            foreach ($metadata['columns'] as $property => $columnInfo) {
                $colName = $columnInfo['name'];
                if (array_key_exists($colName, $row)) {
                    $entity->offsetSet($property, $row[$colName]);
                }
            }

            $entity->exists = true;
            $entity->syncOriginal();
            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * Invoke the boot sequence for the current class if it has not been booted yet.
     *
     * Each concrete subclass may override the static boot() method to register
     * global scopes, event hooks, or other one-time initialization logic.
     *
     * @return void
     */
    private function bootIfNotBooted(): void
    {
        $class = get_class($this);

        if (!isset(self::$booted[$class])) {
            self::$booted[$class] = true;
            static::boot();
            static::bootTraits();
        }
    }

    /**
     * Bootstrap the model.
     *
     * Override in subclasses to register global scopes, event listeners,
     * or other one-time setup logic. Called exactly once per class.
     *
     * @return void
     */
    protected static function boot(): void
    {
        // Intentionally empty — subclasses override as needed
    }

    /**
     * Boot all trait-style initializers following the boot{TraitName} convention.
     *
     * Scans the class for used traits and invokes boot{TraitShortName}
     * methods if they exist. This allows mix-in style initialization.
     *
     * @return void
     */
    protected static function bootTraits(): void
    {
        $class = static::class;
        $traits = class_uses($class) ?: [];

        // Also collect traits from parent classes
        $parent = get_parent_class($class);
        while ($parent) {
            $traits = array_merge($traits, class_uses($parent) ?: []);
            $parent = get_parent_class($parent);
        }

        foreach ($traits as $trait) {
            $method = 'boot' . (new ReflectionClass($trait))->getShortName();
            if (method_exists($class, $method)) {
                forward_static_call([$class, $method]);
            }
        }
    }

    /**
     * Clear the booted state for the current class (useful in tests).
     *
     * @return void
     */
    public static function clearBootedState(): void
    {
        unset(self::$booted[static::class]);
    }

    /**
     * Register a restoring event hook.
     *
     * Fired before a soft-deleted entity is restored. Return false
     * from the callback to cancel the restore operation.
     *
     * @param callable $callback  fn(static): bool|void
     *
     * @return void
     */
    public static function restoring(callable $callback): void
    {
        static::on('restoring', $callback);
    }

    /**
     * Register a restored event hook.
     *
     * Fired after a soft-deleted entity has been successfully restored.
     *
     * @param callable $callback  fn(static): void
     *
     * @return void
     */
    public static function restored(callable $callback): void
    {
        static::on('restored', $callback);
    }

    /**
     * Restore a soft-deleted entity with lifecycle event hooks.
     *
     * Fires 'restoring' before and 'restored' after the restore operation.
     * If any 'restoring' callback returns false, the restore is aborted.
     *
     * @return bool True if the entity was successfully restored
     * @throws Exception When soft delete is not enabled
     */
    public function restoreWithEvents(): bool
    {
        if (!$this->softDeleteColumn) {
            throw new Exception("Soft delete not enabled");
        }

        if (!$this->trigger('restoring')) {
            return false;
        }

        $result = $this->restore();

        if ($result) {
            $this->trigger('restored');
        }

        return $result;
    }

    /**
     * Load a has-many-through relationship.
     *
     * Traverses an intermediate model to reach the final related model.
     * Example: Country -> User -> Post  (a country has many posts through users)
     *
     * @param string $property          Property name to store results on
     * @param string $finalClass        Fully qualified class name of the final target entity
     * @param string $intermediateClass Fully qualified class name of the intermediate entity
     * @param string $firstKey          Foreign key on the intermediate table referencing this entity
     * @param string $secondKey         Foreign key on the final table referencing the intermediate entity
     * @param string|null $localKey     Local primary key on this entity (null = auto-detect)
     * @param string|null $secondLocalKey Local primary key on the intermediate entity (null = auto-detect)
     *
     * @return static
     */
    public function hasManyThrough(string $property, string $finalClass, string $intermediateClass, string $firstKey, string $secondKey, ?string $localKey = null, ?string $secondLocalKey = null): static
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();

        $localKey = $localKey ?? $metadata['primaryKey'];
        $localValue = $this[$localKey] ?? null;

        if ($localValue === null) {
            $this[$property] = [];
            return $this;
        }

        $intermediateInstance = new $intermediateClass();
        $intermediateMeta = self::$reflectionCache[get_class($intermediateInstance)];
        $intermediateTable = $intermediateMeta['table'];
        $secondLocalKey = $secondLocalKey ?? $intermediateMeta['primaryKey'];
        $secondLocalCol = $intermediateMeta['columns'][$secondLocalKey]['name'];

        $finalInstance = new $finalClass();
        $finalMeta = self::$reflectionCache[get_class($finalInstance)];
        $finalTable = $finalMeta['table'];

        $sql = "SELECT `{$finalTable}`.* 
                FROM `{$finalTable}` 
                INNER JOIN `{$intermediateTable}` ON `{$intermediateTable}`.`{$secondLocalCol}` = `{$finalTable}`.`{$secondKey}` 
                WHERE `{$intermediateTable}`.`{$firstKey}` = ?";

        $stmt = self::$db->prepare($sql);
        $stmt->execute([$localValue]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new $finalClass();
            foreach ($finalMeta['columns'] as $prop => $colInfo) {
                $colName = $colInfo['name'];
                if (array_key_exists($colName, $row)) {
                    $entity->offsetSet($prop, $row[$colName]);
                }
            }
            $entity->exists = true;
            $entity->syncOriginal();
            $results[] = $entity;
        }

        $this[$property] = $results;
        return $this;
    }

    /**
     * Load a has-one-through relationship.
     *
     * Traverses an intermediate model to reach a single final related model.
     *
     * @param string $property          Property name to store the result on
     * @param string $finalClass        Fully qualified class name of the final target entity
     * @param string $intermediateClass Fully qualified class name of the intermediate entity
     * @param string $firstKey          Foreign key on the intermediate table referencing this entity
     * @param string $secondKey         Foreign key on the final table referencing the intermediate entity
     * @param string|null $localKey     Local primary key on this entity (null = auto-detect)
     * @param string|null $secondLocalKey Local primary key on the intermediate entity (null = auto-detect)
     *
     * @return static
     */
    public function hasOneThrough(string $property, string $finalClass, string $intermediateClass, string $firstKey, string $secondKey, ?string $localKey = null, ?string $secondLocalKey = null): static
    {
        $this->hasManyThrough($property, $finalClass, $intermediateClass, $firstKey, $secondKey, $localKey, $secondLocalKey);

        // Reduce collection to single result
        $loaded = $this[$property] ?? [];
        $this[$property] = is_array($loaded) && !empty($loaded) ? $loaded[0] : null;

        return $this;
    }

    /**
     * Load a polymorphic one-to-many relationship (morphMany).
     *
     * The related table must have a "{morphName}_type" and "{morphName}_id" column pair.
     *
     * @param string $property    Property name to store the results on
     * @param string $related     Fully qualified class name of the related entity
     * @param string $morphName   Base name for the polymorphic columns (e.g., 'commentable')
     * @param string|null $typeColumn  Override for the type column name
     * @param string|null $idColumn    Override for the id column name
     *
     * @return static
     */
    public function morphMany(string $property, string $related, string $morphName, ?string $typeColumn = null, ?string $idColumn = null): static
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this[$pkName] ?? null;

        if ($pkValue === null) {
            $this[$property] = [];
            return $this;
        }

        $typeCol = $typeColumn ?? $morphName . '_type';
        $idCol = $idColumn ?? $morphName . '_id';
        $morphType = get_class($this);

        $relatedInstance = new $related();
        $relatedMeta = self::$reflectionCache[get_class($relatedInstance)];
        $relatedTable = $relatedMeta['table'];

        $sql = "SELECT * FROM `{$relatedTable}` WHERE `{$typeCol}` = ? AND `{$idCol}` = ?";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$morphType, $pkValue]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new $related();
            foreach ($relatedMeta['columns'] as $prop => $colInfo) {
                if (array_key_exists($colInfo['name'], $row)) {
                    $entity->offsetSet($prop, $row[$colInfo['name']]);
                }
            }
            $entity->exists = true;
            $entity->syncOriginal();
            $results[] = $entity;
        }

        $this[$property] = $results;
        return $this;
    }

    /**
     * Load a polymorphic one-to-one relationship (morphOne).
     *
     * @param string $property    Property name to store the result on
     * @param string $related     Fully qualified class name of the related entity
     * @param string $morphName   Base name for the polymorphic columns
     * @param string|null $typeColumn Override for the type column name
     * @param string|null $idColumn   Override for the id column name
     *
     * @return static
     */
    public function morphOne(string $property, string $related, string $morphName, ?string $typeColumn = null, ?string $idColumn = null): static
    {
        $this->morphMany($property, $related, $morphName, $typeColumn, $idColumn);
        $loaded = $this[$property] ?? [];
        $this[$property] = is_array($loaded) && !empty($loaded) ? $loaded[0] : null;
        return $this;
    }

    /**
     * Load the parent of a polymorphic relationship (morphTo).
     *
     * Reads the type and id columns from this entity to determine
     * the parent class and primary key, then loads the parent instance.
     *
     * @param string $property    Property name to store the parent on
     * @param string|null $typeColumn Override for the type column name (default: {property}_type)
     * @param string|null $idColumn   Override for the id column name (default: {property}_id)
     *
     * @return static
     */
    public function morphTo(string $property, ?string $typeColumn = null, ?string $idColumn = null): static
    {
        $this->ensureConnection();

        $typeCol = $typeColumn ?? $property . '_type';
        $idCol = $idColumn ?? $property . '_id';

        $parentClass = $this[$typeCol] ?? null;
        $parentId = $this[$idCol] ?? null;

        if (!$parentClass || !$parentId || !class_exists($parentClass)) {
            $this[$property] = null;
            return $this;
        }

        $parentInstance = new $parentClass();
        $parentMeta = self::$reflectionCache[get_class($parentInstance)] ?? null;

        if (!$parentMeta) {
            $this[$property] = null;
            return $this;
        }

        $parentTable = $parentMeta['table'];
        $parentPk = $parentMeta['columns'][$parentMeta['primaryKey']]['name'];

        $sql = "SELECT * FROM `{$parentTable}` WHERE `{$parentPk}` = ? LIMIT 1";
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$parentId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $parent = new $parentClass();
            foreach ($parentMeta['columns'] as $prop => $colInfo) {
                if (array_key_exists($colInfo['name'], $row)) {
                    $parent->offsetSet($prop, $row[$colInfo['name']]);
                }
            }
            $parent->exists = true;
            $parent->syncOriginal();
            $this[$property] = $parent;
        } else {
            $this[$property] = null;
        }

        return $this;
    }

    /**
     * Load a many-to-many polymorphic relationship (morphToMany).
     *
     * Uses a pivot table with type and id columns for both sides.
     *
     * @param string $property       Property name to store results on
     * @param string $related        Fully qualified class of the related entity
     * @param string $pivotTable     Name of the pivot table
     * @param string $foreignPivotKey Column on pivot referencing this entity
     * @param string $relatedPivotKey Column on pivot referencing the related entity
     * @param string $morphType      Type discriminator stored in pivot (defaults to current class)
     * @param string $typeColumn     Column name for the morph type in pivot
     *
     * @return static
     */
    public function morphToMany(string $property, string $related, string $pivotTable, string $foreignPivotKey, string $relatedPivotKey, ?string $morphType = null, string $typeColumn = 'taggable_type'): static
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkValue = $this[$pkName] ?? null;

        if ($pkValue === null) {
            $this[$property] = [];
            return $this;
        }

        $morphType = $morphType ?? get_class($this);

        $relatedInstance = new $related();
        $relatedMeta = self::$reflectionCache[get_class($relatedInstance)];
        $relatedTable = $relatedMeta['table'];
        $relatedPk = $relatedMeta['columns'][$relatedMeta['primaryKey']]['name'];

        $sql = "SELECT `{$relatedTable}`.* 
                FROM `{$relatedTable}` 
                INNER JOIN `{$pivotTable}` ON `{$pivotTable}`.`{$relatedPivotKey}` = `{$relatedTable}`.`{$relatedPk}` 
                WHERE `{$pivotTable}`.`{$foreignPivotKey}` = ? AND `{$pivotTable}`.`{$typeColumn}` = ?";

        $stmt = self::$db->prepare($sql);
        $stmt->execute([$pkValue, $morphType]);

        $results = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $entity = new $related();
            foreach ($relatedMeta['columns'] as $prop => $colInfo) {
                if (array_key_exists($colInfo['name'], $row)) {
                    $entity->offsetSet($prop, $row[$colInfo['name']]);
                }
            }
            $entity->exists = true;
            $entity->syncOriginal();
            $results[] = $entity;
        }

        $this[$property] = $results;
        return $this;
    }

    /**
     * Attach related models to a many-to-many relationship via the pivot table.
     *
     * @param string     $relation     Relationship property name (must be manyToMany)
     * @param int|string|array $ids    Single ID or array of IDs to attach
     * @param array      $pivotData    Additional columns to set on each pivot row
     *
     * @return int Number of rows inserted
     * @throws Exception When the relationship is not found or not manyToMany
     */
    public function attach(string $relation, int|string|array $ids, array $pivotData = []): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();

        if (!isset($metadata['relationships'][$relation]) || $metadata['relationships'][$relation]['type'] !== 'manyToMany') {
            throw new Exception("Relationship '{$relation}' is not a manyToMany relationship");
        }

        $relData = $metadata['relationships'][$relation];
        $pkName = $metadata['primaryKey'];
        $pkValue = $this[$pkName];

        [$joinTable, $sourceCol, $targetCol] = $this->resolvePivotInfo($relData);

        $ids = is_array($ids) ? $ids : [$ids];
        if (empty($ids)) {
            return 0;
        }

        $extraCols = array_keys($pivotData);
        $colsSql = "`{$sourceCol}`, `{$targetCol}`" . (!empty($extraCols) ? ', `' . implode('`, `', $extraCols) . '`' : '');
        $ph = '?, ?' . str_repeat(', ?', count($extraCols));
        $rowPh = "({$ph})";
        $allPh = implode(', ', array_fill(0, count($ids), $rowPh));

        $sql = "INSERT INTO `{$joinTable}` ({$colsSql}) VALUES {$allPh}";
        $params = [];
        foreach ($ids as $id) {
            $params[] = $pkValue;
            $params[] = $id;
            foreach ($pivotData as $v) {
                $params[] = $v;
            }
        }

        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Detach related models from a many-to-many relationship via the pivot table.
     *
     * @param string          $relation  Relationship property name (must be manyToMany)
     * @param int|string|array|null $ids IDs to detach, or null to detach all
     *
     * @return int Number of rows deleted
     * @throws Exception When the relationship is not found or not manyToMany
     */
    public function detach(string $relation, int|string|array|null $ids = null): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();

        if (!isset($metadata['relationships'][$relation]) || $metadata['relationships'][$relation]['type'] !== 'manyToMany') {
            throw new Exception("Relationship '{$relation}' is not a manyToMany relationship");
        }

        $relData = $metadata['relationships'][$relation];
        $pkValue = $this[$metadata['primaryKey']];

        [$joinTable, $sourceCol, $targetCol] = $this->resolvePivotInfo($relData);

        if ($ids === null) {
            $sql = "DELETE FROM `{$joinTable}` WHERE `{$sourceCol}` = ?";
            $stmt = self::$db->prepare($sql);
            $stmt->execute([$pkValue]);
        } else {
            $ids = is_array($ids) ? $ids : [$ids];
            $ph = implode(', ', array_fill(0, count($ids), '?'));
            $sql = "DELETE FROM `{$joinTable}` WHERE `{$sourceCol}` = ? AND `{$targetCol}` IN ({$ph})";
            $stmt = self::$db->prepare($sql);
            $stmt->execute(array_merge([$pkValue], $ids));
        }

        return $stmt->rowCount();
    }

    /**
     * Synchronize a many-to-many relationship.
     *
     * Attaches missing IDs and detaches IDs not present in the given list.
     *
     * @param string $relation  Relationship property name (must be manyToMany)
     * @param array  $ids       Desired set of related IDs
     *
     * @return array{attached: array, detached: array} IDs that were attached and detached
     */
    public function syncRelation(string $relation, array $ids): array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $relData = $metadata['relationships'][$relation];
        $pkValue = $this[$metadata['primaryKey']];

        [$joinTable, $sourceCol, $targetCol] = $this->resolvePivotInfo($relData);

        // Fetch current IDs
        $stmt = self::$db->prepare("SELECT `{$targetCol}` FROM `{$joinTable}` WHERE `{$sourceCol}` = ?");
        $stmt->execute([$pkValue]);
        $currentIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), $targetCol);

        $toAttach = array_diff($ids, $currentIds);
        $toDetach = array_diff($currentIds, $ids);

        if (!empty($toDetach)) {
            $this->detach($relation, array_values($toDetach));
        }
        if (!empty($toAttach)) {
            $this->attach($relation, array_values($toAttach));
        }

        return ['attached' => array_values($toAttach), 'detached' => array_values($toDetach)];
    }

    /**
     * Toggle the attachment of related IDs in a many-to-many relationship.
     *
     * IDs that are currently attached will be detached, and vice versa.
     *
     * @param string $relation  Relationship property name
     * @param array  $ids       IDs to toggle
     *
     * @return array{attached: array, detached: array}
     */
    public function toggle(string $relation, array $ids): array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $relData = $metadata['relationships'][$relation];
        $pkValue = $this[$metadata['primaryKey']];

        [$joinTable, $sourceCol, $targetCol] = $this->resolvePivotInfo($relData);

        $stmt = self::$db->prepare("SELECT `{$targetCol}` FROM `{$joinTable}` WHERE `{$sourceCol}` = ?");
        $stmt->execute([$pkValue]);
        $currentIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), $targetCol);

        $toAttach = array_diff($ids, $currentIds);
        $toDetach = array_intersect($ids, $currentIds);

        if (!empty($toDetach)) {
            $this->detach($relation, array_values($toDetach));
        }
        if (!empty($toAttach)) {
            $this->attach($relation, array_values($toAttach));
        }

        return ['attached' => array_values($toAttach), 'detached' => array_values($toDetach)];
    }

    /**
     * Update pivot table data for a specific related ID.
     *
     * @param string     $relation   Relationship property name
     * @param int|string $id         Related ID whose pivot row to update
     * @param array      $attributes Columns to update on the pivot row
     *
     * @return int Number of affected rows
     */
    public function updatePivot(string $relation, int|string $id, array $attributes): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $relData = $metadata['relationships'][$relation];
        $pkValue = $this[$metadata['primaryKey']];

        [$joinTable, $sourceCol, $targetCol] = $this->resolvePivotInfo($relData);

        $setParts = [];
        $params = [];
        foreach ($attributes as $col => $val) {
            $setParts[] = "`{$col}` = ?";
            $params[] = $val;
        }

        $params[] = $pkValue;
        $params[] = $id;

        $sql = "UPDATE `{$joinTable}` SET " . implode(', ', $setParts) . " WHERE `{$sourceCol}` = ? AND `{$targetCol}` = ?";
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Resolve pivot table name and column names from relationship metadata.
     *
     * @param array $relData  Relationship metadata
     *
     * @return array{0: string, 1: string, 2: string} [joinTable, sourceColumn, targetColumn]
     */
    private function resolvePivotInfo(array $relData): array
    {
        if (isset($relData['joinTable'])) {
            return [
                $relData['joinTable']['name'],
                $relData['joinTable']['joinColumn']['name'],
                $relData['joinTable']['inverseJoinColumn']['name'],
            ];
        }

        // Infer from class names
        $thisClass = ReflectionHandler::getClassShortName($this);
        $targetInstance = new ($relData['targetEntity'])();
        $targetClass = ReflectionHandler::getClassShortName($targetInstance);

        $tables = [$thisClass, $targetClass];
        sort($tables);

        return [
            strtolower(implode('_', $tables)),
            strtolower($thisClass) . '_id',
            strtolower($targetClass) . '_id',
        ];
    }

    /**
     * Add a WHERE EXISTS condition based on a relationship.
     *
     * Filters the current query to only include entities that have at least
     * one matching related record. Supports oneToMany relationships.
     *
     * @param string        $relation  Relationship property name
     * @param callable|null $callback  Optional callback to add conditions to the subquery
     *
     * @return static
     */
    protected function whereHas(string $relation, ?callable $callback = null): static
    {
        $metadata = $this->getMetadata();
        if (!isset($metadata['relationships'][$relation])) {
            throw new Exception("Relationship '{$relation}' not found");
        }

        $rel = $metadata['relationships'][$relation];
        [$subSql, $bindings] = $this->buildRelationExistsSubquery($rel, $metadata, $callback);

        return $this->whereRaw("EXISTS ({$subSql})", $bindings);
    }

    /**
     * Add a WHERE NOT EXISTS condition based on a relationship.
     *
     * Filters the current query to only include entities that have zero
     * matching related records.
     *
     * @param string        $relation  Relationship property name
     * @param callable|null $callback  Optional callback to add conditions to the subquery
     *
     * @return static
     */
    protected function whereDoesntHave(string $relation, ?callable $callback = null): static
    {
        $metadata = $this->getMetadata();
        if (!isset($metadata['relationships'][$relation])) {
            throw new Exception("Relationship '{$relation}' not found");
        }

        $rel = $metadata['relationships'][$relation];
        [$subSql, $bindings] = $this->buildRelationExistsSubquery($rel, $metadata, $callback);

        return $this->whereRaw("NOT EXISTS ({$subSql})", $bindings);
    }

    /**
     * Filter entities that have a minimum count of related records.
     *
     * @param string $relation  Relationship property name
     * @param string $operator  Comparison operator (>=, >, =, etc.)
     * @param int    $count     Minimum count threshold
     *
     * @return static
     */
    protected function has(string $relation, string $operator = '>=', int $count = 1): static
    {
        $metadata = $this->getMetadata();
        if (!isset($metadata['relationships'][$relation])) {
            throw new Exception("Relationship '{$relation}' not found");
        }

        $rel = $metadata['relationships'][$relation];
        [$subSql, $bindings] = $this->buildRelationCountSubquery($rel, $metadata);

        $safeOperator = SqlIdentifier::operator($operator);

        return $this->whereRaw("({$subSql}) {$safeOperator} ?", array_merge($bindings, [$count]));
    }

    /**
     * Build an EXISTS subquery for a relationship.
     *
     * @param array         $rel       Relationship metadata
     * @param array         $metadata  Parent entity metadata
     * @param callable|null $callback  Optional condition callback
     *
     * @return array{0: string, 1: array} [SQL, bindings]
     */
    private function buildRelationExistsSubquery(array $rel, array $metadata, ?callable $callback): array
    {
        $pkCol = $metadata['columns'][$metadata['primaryKey']]['name'];
        $tableName = $metadata['table'];
        $bindings = [];

        if ($rel['type'] === 'oneToMany') {
            $targetInstance = new ($rel['targetEntity'])();
            $targetMeta = self::$reflectionCache[get_class($targetInstance)];
            $targetTable = $targetMeta['table'];
            $mappedBy = $rel['mappedBy'];

            $fkCol = $targetMeta['columns'][$mappedBy]['name']
                ?? ($targetMeta['relationships'][$mappedBy]['joinColumn']['name'] ?? null);

            if (!$fkCol) {
                throw new Exception("Cannot resolve foreign key for relationship");
            }

            $subSql = "SELECT 1 FROM `{$targetTable}` WHERE `{$targetTable}`.`{$fkCol}` = `{$tableName}`.`{$pkCol}`";

            if ($callback) {
                $sub = new ($rel['targetEntity'])();
                $callback($sub);
                $subOptions = $sub->buildQueryOptions();
                if (!empty($subOptions['conditions'])) {
                    foreach ($subOptions['conditions'] as $col => $val) {
                        $quotedCol = SqlIdentifier::quote((string) $col);

                        if (is_array($val)) {
                            $safeOperator = SqlIdentifier::operator($val['operator']);
                            $subSql .= " AND {$quotedCol} {$safeOperator} ?";
                            $bindings[] = $val['value'];
                        } else {
                            $subSql .= " AND {$quotedCol} = ?";
                            $bindings[] = $val;
                        }
                    }
                }
            }
        } else {
            throw new Exception("whereHas/whereDoesntHave currently supports oneToMany relationships");
        }

        return [$subSql, $bindings];
    }

    /**
     * Build a COUNT subquery for a relationship.
     *
     * @param array $rel      Relationship metadata
     * @param array $metadata Parent entity metadata
     *
     * @return array{0: string, 1: array} [SQL, bindings]
     */
    private function buildRelationCountSubquery(array $rel, array $metadata): array
    {
        $pkCol = $metadata['columns'][$metadata['primaryKey']]['name'];
        $tableName = $metadata['table'];

        if ($rel['type'] === 'oneToMany') {
            $targetInstance = new ($rel['targetEntity'])();
            $targetMeta = self::$reflectionCache[get_class($targetInstance)];
            $targetTable = $targetMeta['table'];
            $mappedBy = $rel['mappedBy'];

            $fkCol = $targetMeta['columns'][$mappedBy]['name']
                ?? ($targetMeta['relationships'][$mappedBy]['joinColumn']['name'] ?? null);

            $subSql = "SELECT COUNT(*) FROM `{$targetTable}` WHERE `{$targetTable}`.`{$fkCol}` = `{$tableName}`.`{$pkCol}`";
            return [$subSql, []];
        }

        throw new Exception("has() currently supports oneToMany relationships");
    }

    /**
     * Load an aggregate value from a relationship as a virtual attribute.
     *
     * After calling withAggregate('orders', 'SUM', 'total', 'orders_total'),
     * the entity will have an 'orders_total' attribute with the aggregate value.
     *
     * @param string $relation   Relationship property name
     * @param string $function   SQL aggregate function (SUM, AVG, MIN, MAX, COUNT)
     * @param string $column     Column name in the related table to aggregate
     * @param string|null $alias Attribute name to store the result (default: {relation}_{function})
     *
     * @return static
     */
    public function withAggregate(string $relation, string $function, string $column, ?string $alias = null): static
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $alias = $alias ?? $relation . '_' . strtolower($function);

        if (!isset($metadata['relationships'][$relation])) {
            $this[$alias] = null;
            return $this;
        }

        $rel = $metadata['relationships'][$relation];
        $pkName = $metadata['primaryKey'];
        $pkValue = $this[$pkName] ?? null;

        if ($pkValue === null) {
            $this[$alias] = null;
            return $this;
        }

        if ($rel['type'] === 'oneToMany') {
            $targetInstance = new ($rel['targetEntity'])();
            $targetMeta = self::$reflectionCache[get_class($targetInstance)];
            $targetTable = $targetMeta['table'];
            $mappedBy = $rel['mappedBy'];

            $fkCol = $targetMeta['columns'][$mappedBy]['name']
                ?? ($targetMeta['relationships'][$mappedBy]['joinColumn']['name'] ?? null);

            if (!$fkCol) {
                $this[$alias] = null;
                return $this;
            }

            $targetCol = $targetMeta['columns'][$column]['name'] ?? $column;
            $sql = "SELECT {$function}(`{$targetCol}`) FROM `{$targetTable}` WHERE `{$fkCol}` = ?";
            $stmt = self::$db->prepare($sql);
            $stmt->execute([$pkValue]);
            $this[$alias] = $stmt->fetchColumn();
        } else {
            $this[$alias] = null;
        }

        return $this;
    }

    /**
     * Load a SUM aggregate from a relationship.
     *
     * @param string $relation  Relationship property name
     * @param string $column    Column to sum
     *
     * @return static
     */
    public function withSum(string $relation, string $column): static
    {
        return $this->withAggregate($relation, 'SUM', $column, $relation . '_sum_' . $column);
    }

    /**
     * Load an AVG aggregate from a relationship.
     *
     * @param string $relation  Relationship property name
     * @param string $column    Column to average
     *
     * @return static
     */
    public function withAvg(string $relation, string $column): static
    {
        return $this->withAggregate($relation, 'AVG', $column, $relation . '_avg_' . $column);
    }

    /**
     * Load a MIN aggregate from a relationship.
     *
     * @param string $relation  Relationship property name
     * @param string $column    Column to find minimum
     *
     * @return static
     */
    public function withMin(string $relation, string $column): static
    {
        return $this->withAggregate($relation, 'MIN', $column, $relation . '_min_' . $column);
    }

    /**
     * Load a MAX aggregate from a relationship.
     *
     * @param string $relation  Relationship property name
     * @param string $column    Column to find maximum
     *
     * @return static
     */
    public function withMax(string $relation, string $column): static
    {
        return $this->withAggregate($relation, 'MAX', $column, $relation . '_max_' . $column);
    }

    /**
     * Lazily iterate over matching rows in chunks using a Generator.
     *
     * Unlike chunk(), this method yields individual entities and does not
     * require a callback. Memory-efficient for processing large result sets.
     *
     * @param int    $chunkSize  Number of rows per internal fetch
     * @param array  $options    Additional query options
     *
     * @return \Generator<int, static>
     */
    public function lazy(int $chunkSize = 1000, array $options = []): \Generator
    {
        $page = 1;
        do {
            $results = $this->paginate($chunkSize, $page, $options);
            $items = $results['data'];

            foreach ($items as $item) {
                yield $item;
            }

            $page++;
        } while ($results['has_more']);
    }

    /**
     * TODO
     * Lazily iterate using cursor-based (ID-keyed) chunking.
     *
     * More stable than offset-based lazy() under concurrent writes.
     *
     * @param int         $chunkSize  Number of rows per internal fetch
     * @param string|null $column     Column to paginate by (default: primary key)
     *
     * @return \Generator<int, static>
     */
    public function lazyById(int $chunkSize = 1000, ?string $column = null): \Generator
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $pkName = $column ?? $metadata['primaryKey'];
        $pkColName = $metadata['columns'][$pkName]['name'];
        $lastId = null;

        do {
            $options = ['order' => $pkColName, 'arrange' => 'ASC', 'limit' => $chunkSize];
            if ($lastId !== null) {
                $options['conditions'][$pkColName] = ['value' => $lastId, 'operator' => '>'];
            }

            $items = $this->findAll($options);
            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                yield $item;
            }

            $lastItem = end($items);
            $lastId = $lastItem[$pkName] ?? null;
        } while (count($items) === $chunkSize);
    }

    /**
     * Check whether the entity's table exists in the database.
     *
     * @return bool True if the table exists
     */
    public function hasTable(): bool
    {
        $this->ensureConnection();
        $tableName = $this->getMetadata()['table'];

        try {
            $stmt = self::$db->prepare("SELECT 1 FROM `{$tableName}` LIMIT 1");
            $stmt->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check whether a column exists in the entity's database table.
     *
     * @param string $column  Column name to check
     *
     * @return bool True if the column exists
     */
    public function hasColumn(string $column): bool
    {
        $this->ensureConnection();
        $tableName = $this->getMetadata()['table'];

        try {
            $stmt = self::$db->prepare("SELECT `{$column}` FROM `{$tableName}` LIMIT 0");
            $stmt->execute();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Check if the entity has a specific column defined in its metadata.
     *
     * This checks the annotation-based metadata, not the actual database schema.
     *
     * @param string $property  Property name to check
     *
     * @return bool True if the property is mapped to a column
     */
    public function hasColumnInMetadata(string $property): bool
    {
        $metadata = $this->getMetadata();
        return isset($metadata['columns'][$property]);
    }

    /**
     * Remove a loaded relationship from the entity.
     *
     * @param string $relation  Relation property name
     *
     * @return static
     */
    public function unsetRelation(string $relation): static
    {
        if ($this->offsetExists($relation)) {
            $this->offsetUnset($relation);
        }
        return $this;
    }

    /**
     * Get all currently loaded relationships as an associative array.
     *
     * Returns only properties that represent relationships (as defined in
     * metadata) and have been loaded onto the entity.
     *
     * @return array<string, mixed> Relation name => loaded value
     */
    public function getRelations(): array
    {
        $metadata = $this->getMetadata();
        $storedData = $this->getArrayCopy();
        $columnKeys = array_keys($metadata['columns'] ?? []);
        $relations = [];

        foreach ($storedData as $key => $value) {
            if (!in_array($key, $columnKeys)) {
                $relations[$key] = $value;
            }
        }

        return $relations;
    }

    /**
     * Set a default value for a relationship when it is empty/null.
     *
     * If the relationship has not been loaded or is null, the default
     * value (or a new empty instance) is returned instead.
     *
     * @param string $relation   Relation property name
     * @param mixed  $default    Default value or callback returning default
     *
     * @return static
     */
    public function withDefault(string $relation, mixed $default = null): static
    {
        if (!$this->offsetExists($relation) || $this->offsetGet($relation) === null) {
            if (is_callable($default)) {
                $this[$relation] = $default($this);
            } elseif ($default !== null) {
                $this[$relation] = $default;
            } else {
                // Create an empty instance of the target entity
                $metadata = $this->getMetadata();
                if (isset($metadata['relationships'][$relation])) {
                    $targetClass = $metadata['relationships'][$relation]['targetEntity'];
                    $this[$relation] = new $targetClass();
                }
            }
        }

        return $this;
    }

    /**
     * Save the entity and all loaded relationships recursively.
     *
     * Each loaded relationship value that is an ActiveRecord instance
     * (or an array of instances) will also be saved.
     *
     * @return bool True if all saves succeeded
     */
    public function push(): bool
    {
        $this->save();

        $relations = $this->getRelations();

        foreach ($relations as $relation => $value) {
            if ($value instanceof self) {
                $value->push();
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if ($item instanceof self) {
                        $item->push();
                    }
                }
            }
        }

        return true;
    }

    /**
     * Get all raw stored attribute values without casting or accessor processing.
     *
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * Get only column-mapped attribute values (no relationships or virtual attributes).
     *
     * @return array<string, mixed>
     */
    public function attributesToArray(): array
    {
        $metadata = $this->getMetadata();
        $storedData = $this->getArrayCopy();
        $result = [];

        foreach ($metadata['columns'] as $property => $columnInfo) {
            if (array_key_exists($property, $storedData)) {
                $result[$property] = $storedData[$property];
            }
        }

        return $result;
    }

    /**
     * Check if the given attribute has a cast type defined.
     *
     * @param string $key        Attribute name
     * @param string|null $type  Optional: check if cast matches this specific type
     *
     * @return bool True if a cast is defined (and optionally matches $type)
     */
    public function hasCast(string $key, ?string $type = null): bool
    {
        if (!isset($this->casts[$key])) {
            return false;
        }

        return $type === null || $this->casts[$key] === $type;
    }

    /**
     * Add or override cast definitions at runtime.
     *
     * @param array<string, string> $casts  Attribute => cast type pairs to merge
     *
     * @return static
     */
    public function mergeCasts(array $casts): static
    {
        $this->casts = array_merge($this->casts, $casts);
        return $this;
    }

    /**
     * Get the current cast definitions.
     *
     * @return array<string, string>
     */
    public function getCasts(): array
    {
        return $this->casts;
    }

    /**
     * Get the fully qualified primary key column name (table.column).
     *
     * Useful for JOIN conditions and subqueries where column name
     * ambiguity must be avoided.
     *
     * @return string Fully qualified column name (e.g., "users.id")
     */
    public function getQualifiedKeyName(): string
    {
        $metadata = $this->getMetadata();
        $pkName = $metadata['primaryKey'];
        $pkCol = $metadata['columns'][$pkName]['name'];

        return $metadata['table'] . '.' . $pkCol;
    }

    /**
     * Get a fully qualified column name for any mapped property.
     *
     * @param string $property  Entity property name
     *
     * @return string Fully qualified column name (e.g., "users.email")
     * @throws Exception When the property is not mapped
     */
    public function qualifyColumn(string $property): string
    {
        $metadata = $this->getMetadata();

        if (!isset($metadata['columns'][$property])) {
            throw new Exception("Property '{$property}' is not mapped to a column");
        }

        return $metadata['table'] . '.' . $metadata['columns'][$property]['name'];
    }

    /**
     * Create an entity instance from an associative array.
     *
     * Unlike create(), this does NOT persist to the database.
     * Unlike newInstance(), this method marks the entity as existing
     * and syncs original data (useful for hydrating from external sources).
     *
     * @param array  $attributes  Property => value pairs
     * @param bool   $persisted   Whether to mark the entity as already existing in DB
     *
     * @return static
     */
    public static function fromArray(array $attributes, bool $persisted = false): static
    {
        $instance = new static();
        foreach ($attributes as $k => $v) {
            $instance->offsetSet($k, $v);
        }

        if ($persisted) {
            $instance->exists = true;
            $instance->syncOriginal();
        }

        return $instance;
    }

    /**
     * Create an entity instance from a JSON string.
     *
     * @param string $json       JSON-encoded entity data
     * @param bool   $persisted  Whether to mark the entity as existing
     *
     * @return static
     * @throws Exception When JSON is invalid
     */
    public static function fromJson(string $json, bool $persisted = false): static
    {
        $data = json_decode($json, true);
        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }

        return static::fromArray($data, $persisted);
    }

    /**
     * Serialize the entity to a Base64-encoded JSON string.
     *
     * @return string Base64-encoded JSON
     */
    public function toBase64(): string
    {
        return base64_encode($this->toJson());
    }

    /**
     * Create an entity instance from a Base64-encoded JSON string.
     *
     * @param string $encoded    Base64-encoded JSON
     * @param bool   $persisted  Whether to mark the entity as existing
     *
     * @return static
     */
    public static function fromBase64(string $encoded, bool $persisted = false): static
    {
        $json = base64_decode($encoded);
        return static::fromJson($json, $persisted);
    }

    /**
     * Whether to use DateTimeImmutable instead of mutable DateTime for timestamp attributes.
     *
     * @var bool
     */
    protected bool $useImmutableDates = false;

    /**
     * Get the created_at value as a DateTime or DateTimeImmutable instance.
     *
     * @return \DateTimeInterface|null
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->asDateTime($this[$this->createdAtColumn] ?? null);
    }

    /**
     * Get the updated_at value as a DateTime or DateTimeImmutable instance.
     *
     * @return \DateTimeInterface|null
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->asDateTime($this[$this->updatedAtColumn] ?? null);
    }

    /**
     * Convert a value to a DateTimeInterface instance.
     *
     * Respects the $useImmutableDates flag to determine whether to return
     * a mutable DateTime or immutable DateTimeImmutable.
     *
     * @param mixed $value  Raw timestamp value (string, int, DateTime, or null)
     *
     * @return \DateTimeInterface|null
     */
    protected function asDateTime(mixed $value): ?\DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeImmutable) {
            return $this->useImmutableDates ? $value : \DateTime::createFromInterface($value);
        }

        if ($value instanceof \DateTime) {
            return $this->useImmutableDates ? \DateTimeImmutable::createFromMutable($value) : $value;
        }

        if (is_numeric($value)) {
            $dt = $this->useImmutableDates
                ? (new \DateTimeImmutable())->setTimestamp((int) $value)
                : (new \DateTime())->setTimestamp((int) $value);
            return $dt;
        }

        return $this->useImmutableDates
            ? new \DateTimeImmutable($value)
            : new \DateTime($value);
    }

    /**
     * Get the current freshly generated timestamp string.
     *
     * @return string Formatted timestamp (Y-m-d H:i:s)
     */
    public function freshTimestampString(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Add a UNION to the current query.
     *
     * @param string $sql       SQL query to union
     * @param array  $bindings  Bound parameters for the union query
     * @param bool   $all       Whether to use UNION ALL (default: false for UNION DISTINCT)
     *
     * @return static
     */
    protected function union(string $sql, array $bindings = [], bool $all = false): static
    {
        $this->activeQuery->addUnion($sql, $bindings, $all);
        return $this;
    }

    /**
     * Add a UNION ALL to the current query.
     *
     * @param string $sql       SQL query to union
     * @param array  $bindings  Bound parameters
     *
     * @return static
     */
    protected function unionAll(string $sql, array $bindings = []): static
    {
        return $this->union($sql, $bindings, true);
    }

    /**
     * Add a subquery as a SELECT column expression.
     *
     * Allows adding computed columns via subqueries, e.g.,
     * ->addSubSelect('latest_login', 'SELECT MAX(created_at) FROM logins WHERE user_id = users.id')
     *
     * @param string $alias     Column alias for the subquery result
     * @param string $subquery  Raw SQL subquery
     *
     * @return static
     */
    protected function addSubSelect(string $alias, string $subquery): static
    {
        // The subquery is trusted input and stays raw; the alias is a caller-supplied identifier.
        $this->activeQuery->addSelect("({$subquery}) AS " . SqlIdentifier::quote($alias));
        return $this;
    }

    /**
     * Insert a row, silently ignoring duplicate key violations.
     *
     * Uses INSERT IGNORE syntax (MySQL). Returns true if the row was
     * actually inserted, false if it was ignored due to a duplicate.
     *
     * @param array $attributes  Property => value pairs to insert
     *
     * @return bool True if the row was inserted
     */
    public static function insertOrIgnore(array $attributes): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $instance = new static();
        $metadata = $instance->getMetadata();
        $tableName = $metadata['table'];
        $columns = $metadata['columns'];

        $colNames = [];
        $params = [];
        foreach ($attributes as $prop => $val) {
            if (isset($columns[$prop])) {
                $colNames[] = "`{$columns[$prop]['name']}`";
                $params[] = $val;
            }
        }

        if (empty($colNames)) {
            return false;
        }

        $ph = implode(', ', array_fill(0, count($colNames), '?'));
        $sql = "INSERT IGNORE INTO `{$tableName}` (" . implode(', ', $colNames) . ") VALUES ({$ph})";
        $stmt = self::$db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    /**
     * Execute a COUNT using the current query builder state and return the count.
     *
     * @return int Number of matching records
     */
    protected function queryCount(): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        // Strip irrelevant options for count
        unset($options['limit'], $options['offset'], $options['order'], $options['arrange'], $options['order_raw'], $options['select']);

        $joinSql = '';
        if (!empty($options['joins'])) {
            foreach ($options['joins'] as $j) {
                // Every component was validated and quoted by buildJoinEntry() / crossJoin().
                $joinSql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        $sql = "SELECT COUNT(*) as _cnt FROM `{$tableName}`{$joinSql}";
        $modified = $this->modifyQueryByOptions($sql, $options);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($modified['parameters']);
        $this->resetQuery();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($row['_cnt'] ?? 0);
    }

    /**
     * Check if any records match the current query builder state.
     *
     * @return bool True if at least one record matches
     */
    protected function queryExists(): bool
    {
        return $this->queryCount() > 0;
    }

    /**
     * Check if no records match the current query builder state.
     *
     * @return bool True if zero records match
     */
    protected function queryDoesntExist(): bool
    {
        return $this->queryCount() === 0;
    }

    /**
     * Get the first matching record, or execute a callback if none found.
     *
     * @param callable $callback  Invoked when no record found; its return value is returned
     * @param array    $options   Query options
     *
     * @return mixed Found entity or callback result
     */
    protected function firstOr(callable $callback, array $options = []): mixed
    {
        $result = $this->first($options);
        return $result ?? $callback();
    }

    /**
     * Find by primary key, or execute a callback if not found.
     *
     * @param mixed    $id        Primary key value
     * @param callable $callback  Invoked when not found
     * @param array    $options   Additional query options
     *
     * @return mixed Found entity or callback result
     */
    protected function findOr(mixed $id, callable $callback, array $options = []): mixed
    {
        $result = $this->find($id, $options);
        return $result ?? $callback();
    }

    /**
     * Apply soft delete scope to the current query builder before executing get().
     *
     * Overridden version of get() that integrates global scopes.
     *
     * @return list<static>
     */
    protected function getWithScopes(): array
    {
        $this->applyGlobalScopes();
        return $this->get();
    }

    /**
     * Increment a column for all records matching the current query builder state.
     *
     * @param string $column  Column to increment
     * @param int    $amount  Amount to add (can be negative for decrement)
     * @param array  $extra   Additional columns to update
     *
     * @return int Number of affected rows
     */
    protected function incrementWhere(string $column, int $amount = 1, array $extra = []): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        if (!isset($metadata['columns'][$column])) {
            throw new Exception("Column {$column} not found in metadata");
        }

        $colName = $metadata['columns'][$column]['name'];
        $setParts = ["`{$colName}` = `{$colName}` + ?"];
        $params = [$amount];

        foreach ($extra as $prop => $val) {
            if (isset($metadata['columns'][$prop])) {
                $setParts[] = "`{$metadata['columns'][$prop]['name']}` = ?";
                $params[] = $val;
            }
        }

        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts);
        $modified = $this->modifyQueryByOptions($sql, $options);
        $this->resetQuery();

        $allParams = array_merge($params, $modified['parameters']);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($allParams);

        return $stmt->rowCount();
    }

    /**
     * Decrement a column for all records matching the current query builder state.
     *
     * @param string $column  Column to decrement
     * @param int    $amount  Amount to subtract
     * @param array  $extra   Additional columns to update
     *
     * @return int Number of affected rows
     */
    protected function decrementWhere(string $column, int $amount = 1, array $extra = []): int
    {
        return $this->incrementWhere($column, -$amount, $extra);
    }

    /**
     * Add an OR WHERE BETWEEN clause to the query.
     *
     * @param string $column  Column name
     * @param mixed  $min     Lower bound
     * @param mixed  $max     Upper bound
     *
     * @return static
     */
    protected function orWhereBetween(string $column, mixed $min, mixed $max): static
    {
        return $this->whereRaw(SqlIdentifier::quote($column) . " BETWEEN ? AND ?", [$min, $max], 'OR');
    }

    /**
     * Add an OR WHERE LIKE clause to the query.
     *
     * @param string $column   Column name
     * @param string $pattern  LIKE pattern
     *
     * @return static
     */
    protected function orWhereLike(string $column, string $pattern): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, ['column' => $column, 'operator' => 'LIKE', 'value' => $pattern, 'boolean' => 'OR']);
    }

    /**
     * Add an OR WHERE NOT LIKE clause to the query.
     *
     * @param string $column  Column/property name
     * @param string $pattern LIKE pattern
     *
     * @return static Fluent interface
     */
    protected function orWhereNotLike(string $column, string $pattern): static
    {
        return $this->addConditionRecord(self::CONDITION_TYPE_BASIC, [
            'column' => $column,
            'operator' => 'NOT LIKE',
            'value' => $pattern,
            'boolean' => 'OR',
        ]);
    }

    /**
     * Compare the number of elements in a JSON array or object stored in a column.
     *
     * @param string $column   Column/property name holding a JSON document
     * @param string $operator Comparison operator applied to the length
     * @param int    $length   Length to compare against
     *
     * @return static Fluent interface
     */
    protected function whereJsonLength(string $column, string $operator, int $length): static
    {
        $expression = sprintf(
            'JSON_LENGTH(%s) %s ?',
            SqlIdentifier::quote($column),
            SqlIdentifier::operator($operator)
        );

        return $this->whereRaw($expression, [$length]);
    }

    /**
     * Get the number of column attributes defined on this entity.
     *
     * @return int
     */
    public function getColumnCount(): int
    {
        return count($this->getMetadata()['columns'] ?? []);
    }

    /**
     * Get an array of all mapped property names.
     *
     * @return string[]
     */
    public function getColumnNames(): array
    {
        return array_keys($this->getMetadata()['columns'] ?? []);
    }

    /**
     * Get an array of all mapped database column names.
     *
     * @return string[]
     */
    public function getDatabaseColumnNames(): array
    {
        return array_map(fn($c) => $c['name'], $this->getMetadata()['columns'] ?? []);
    }

    /**
     * Determine if the entity's table uses auto-incrementing primary keys.
     *
     * Override in subclasses for UUID or other non-auto-increment strategies.
     *
     * @var bool
     */
    protected bool $incrementing = true;

    /**
     * Check whether the primary key is auto-incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return $this->incrementing;
    }

    /**
     * Set whether the primary key is auto-incrementing.
     *
     * @param bool $value
     *
     * @return static
     */
    public function setIncrementing(bool $value): static
    {
        $this->incrementing = $value;
        return $this;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return $this->keyType;
    }

    /**
     * Set the primary key type.
     *
     * @param string $type
     *
     * @return static
     */
    public function setKeyType(string $type): static
    {
        $this->keyType = $type;
        return $this;
    }

    /**
     * Get the default foreign key name for this model.
     *
     * Converts the class short name to snake_case and appends "_id".
     * E.g., UserProfile -> user_profile_id
     *
     * @return string
     */
    public function getForeignKey(): string
    {
        $className = ReflectionHandler::getClassShortName($this);
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className)) . '_id';
    }

    /**
     * Retrieve a new entity by the given conditions or throw an exception.
     *
     * @param array $attributes  Conditions to search by
     *
     * @return static Found entity
     * @throws Exception When no entity matches
     */
    public static function firstOrFail(array $attributes): static
    {
        $instance = new static();
        $entity = $instance->first(['conditions' => $attributes]);

        if (!$entity) {
            throw new Exception("No entity found matching the given conditions");
        }

        return $entity;
    }

    /**
     * Run a callback within a database savepoint.
     *
     * If the callback throws, only changes within the savepoint are
     * rolled back — not the entire outer transaction.
     *
     * @template TReturn
     * @param string           $name     Savepoint name
     * @param callable(): TReturn $callback Business logic
     *
     * @return TReturn
     * @throws \Throwable
     */
    public static function savepoint(string $name, callable $callback): mixed
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        self::$db->exec("SAVEPOINT `{$name}`");
        try {
            $result = $callback();
            self::$db->exec("RELEASE SAVEPOINT `{$name}`");
            return $result;
        } catch (\Throwable $e) {
            self::$db->exec("ROLLBACK TO SAVEPOINT `{$name}`");
            throw $e;
        }
    }

    /**
     * Execute a callback for each entity matching the conditions.
     *
     * Combination of cursor() and array_map() for memory-efficient
     * transformations over large datasets.
     *
     * @param callable $callback  fn(static): mixed
     * @param array    $options   Query options
     *
     * @return array Mapped values
     */
    public function cursorMap(callable $callback, array $options = []): array
    {
        $results = [];
        foreach ($this->cursor($options) as $entity) {
            $results[] = $callback($entity);
        }
        return $results;
    }

    /**
     * Check if this model has the given global scope registered.
     *
     * @param string $name  Scope identifier
     *
     * @return bool
     */
    public static function hasGlobalScope(string $name): bool
    {
        $class = static::class;
        return isset(self::$globalScopes[$class][$name]);
    }

    /**
     * Remove all global scopes from the current class.
     *
     * @return void
     */
    public static function clearGlobalScopes(): void
    {
        unset(self::$globalScopes[static::class]);
    }

    /**
     * Get the per-page default for pagination.
     *
     * Override in subclasses to change the default.
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return 15;
    }

    /**
     * Set the number of entities returned per page by default.
     *
     * @param int $perPage
     *
     * @return static
     */
    public function setPerPage(int $perPage): static
    {
        $this->perPage = $perPage;
        return $this;
    }

    /**
     * Check whether a database transaction is currently active.
     *
     * @return bool
     */
    public static function inTransaction(): bool
    {
        if (!self::$db) {
            return false;
        }

        return self::$db->inTransaction();
    }

    /**
     * Return the current transaction nesting depth.
     *
     * @return int 0 when no transaction is active
     */
    public static function transactionLevel(): int
    {
        if (!self::$db) {
            return 0;
        }

        return self::$db->transactionLevel();
    }

    /**
     * Register a callback to run after the outermost transaction commits.
     * If no transaction is active the callback fires immediately.
     * Callbacks are discarded on rollback.
     *
     * @param callable $callback
     * @return void
     * @throws Exception When database connection not set
     */
    public static function afterCommit(callable $callback): void
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        self::$db->afterCommit($callback);
    }

    /**
     * Acquire a MySQL advisory lock (GET_LOCK).
     *
     * Advisory locks are cooperative application-level locks. They do not
     * block DML but are useful for mutual exclusion (cron guards, unique
     * job processing, distributed coordination).
     *
     * @param string $name     Lock name (max 64 characters)
     * @param int    $timeout  Seconds to wait for the lock (0 = no wait)
     *
     * @return bool True if the lock was acquired
     * @throws Exception When database connection not set
     */
    public static function getLock(string $name, int $timeout = 0): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->getLock($name, $timeout);
    }

    /**
     * Release a MySQL advisory lock (RELEASE_LOCK).
     *
     * @param string $name Lock name
     *
     * @return bool True if the lock was released
     * @throws Exception When database connection not set
     */
    public static function releaseLock(string $name): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->releaseLock($name);
    }

    /**
     * Check whether a MySQL advisory lock is free (IS_FREE_LOCK).
     *
     * @param string $name Lock name
     *
     * @return bool True if the lock is not held by any session
     * @throws Exception When database connection not set
     */
    public static function isFreeLock(string $name): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->isFreeLock($name);
    }

    /**
     * Check whether the current session holds a MySQL advisory lock.
     *
     * @param string $name Lock name
     *
     * @return bool True if the current connection holds this lock
     * @throws Exception When database connection not set
     */
    public static function isUsedLock(string $name): bool
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->isUsedLock($name);
    }

    /**
     * Release all advisory locks held by the current connection.
     *
     * @return int Number of locks released
     * @throws Exception When database connection not set
     */
    public static function releaseAllLocks(): int
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->releaseAllLocks();
    }

    /**
     * Return the list of advisory lock names currently held.
     *
     * @return string[]
     * @throws Exception When database connection not set
     */
    public static function getHeldLocks(): array
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->getHeldLocks();
    }

    /**
     * Execute a callback while holding an advisory lock.
     *
     * Acquires the named lock, runs the callback, and always releases the
     * lock — even if the callback throws. Returns the callback's return value.
     *
     * @template TReturn
     * @param string          $name     Lock name
     * @param callable(): TReturn $callback Business logic
     * @param int             $timeout  Seconds to wait for the lock
     *
     * @return TReturn
     * @throws Exception When the lock cannot be acquired or DB not connected
     */
    public static function withLock(string $name, callable $callback, int $timeout = 10): mixed
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        if (!self::$db->getLock($name, $timeout)) {
            throw new Exception("Failed to acquire advisory lock '{$name}' within {$timeout}s");
        }

        try {
            return $callback();
        } finally {
            self::$db->releaseLock($name);
        }
    }

    /**
     * Apply FOR UPDATE SKIP LOCKED to the query (MySQL 8.0+ / PostgreSQL 9.5+).
     *
     * Skips rows that are already locked by another transaction. Useful for
     * job-queue patterns where multiple workers claim work concurrently.
     *
     * @return static
     */
    protected function lockForUpdateSkipLocked(): static
    {
        $this->activeQuery->setLock('FOR UPDATE SKIP LOCKED');
        return $this;
    }

    /**
     * Apply FOR UPDATE NOWAIT to the query (MySQL 8.0+ / PostgreSQL 9.5+).
     *
     * Immediately raises an error if any selected row is locked, instead
     * of waiting for the lock to be released.
     *
     * @return static
     */
    protected function lockForUpdateNoWait(): static
    {
        $this->activeQuery->setLock('FOR UPDATE NOWAIT');
        return $this;
    }

    /**
     * Apply LOCK IN SHARE MODE SKIP LOCKED to the query.
     *
     * @return static
     */
    protected function sharedLockSkipLocked(): static
    {
        $this->activeQuery->setLock('FOR SHARE SKIP LOCKED');
        return $this;
    }

    /**
     * Apply FOR SHARE NOWAIT to the query.
     *
     * @return static
     */
    protected function sharedLockNoWait(): static
    {
        $this->activeQuery->setLock('FOR SHARE NOWAIT');
        return $this;
    }

    /**
     * Get the EXPLAIN output for the current query builder state.
     *
     * @param bool $analyze Whether to use EXPLAIN ANALYZE (MySQL 8.0.18+)
     *
     * @return array Rows from EXPLAIN output
     */
    protected function explain(bool $analyze = false): array
    {
        $this->ensureConnection();
        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $distinct = !empty($options['distinct']) ? 'DISTINCT ' : '';
        $selectRaw = !empty($options['select']) ? implode(', ', $options['select']) : "`{$tableName}`.*";
        $joinSql = '';
        if (!empty($options['joins'])) {
            foreach ($options['joins'] as $j) {
                // Every component was validated and quoted by buildJoinEntry() / crossJoin().
                $joinSql .= " {$j['type']} JOIN {$j['table']} ON {$j['first']} {$j['operator']} {$j['second']}";
            }
        }

        $sql = "SELECT {$distinct}{$selectRaw} FROM `{$tableName}`{$joinSql}";
        $modified = $this->modifyQueryByOptions($sql, $options);
        $this->resetQuery();

        $prefix = $analyze ? 'EXPLAIN ANALYZE ' : 'EXPLAIN ';
        $stmt = self::$db->prepare($prefix . $modified['query']);
        $stmt->execute($modified['parameters']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute a raw SQL statement without preparing it.
     *
     * Useful for SET commands, LOCK TABLES, or DDL statements that
     * cannot or should not be prepared.
     *
     * @param string $sql Raw SQL
     *
     * @return int|false Number of affected rows
     * @throws Exception When database connection not set
     */
    public static function unprepared(string $sql): int|false
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        return self::$db->unprepared($sql);
    }

    /**
     * Acquire table-level locks (LOCK TABLES).
     *
     * @param array<string, string> $tables Table name => lock type ('READ'|'WRITE')
     *
     * @return void
     * @throws Exception When database connection not set
     */
    public static function lockTables(array $tables): void
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        self::$db->lockTables($tables);
    }

    /**
     * Release all table-level locks (UNLOCK TABLES).
     *
     * @return void
     * @throws Exception When database connection not set
     */
    public static function unlockTables(): void
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        self::$db->unlockTables();
    }

    /**
     * Get the SHOW CREATE TABLE output for this entity's table.
     *
     * @return string|null The CREATE TABLE statement, or null on failure
     * @throws Exception When database connection not set
     */
    public function showCreateTable(): ?string
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $stmt = self::$db->query("SHOW CREATE TABLE `{$tableName}`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['Create Table'] ?? null;
    }

    /**
     * Return index information for this entity's table (SHOW INDEX).
     *
     * @return array List of index rows
     * @throws Exception When database connection not set
     */
    public function showIndexes(): array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $stmt = self::$db->query("SHOW INDEX FROM `{$tableName}`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Return table status information (SHOW TABLE STATUS).
     *
     * @return array|null Table status row or null if not found
     * @throws Exception When database connection not set
     */
    public function tableStatus(): ?array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $stmt = self::$db->prepare("SHOW TABLE STATUS LIKE ?");
        $stmt->execute([$tableName]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /**
     * Return all column information for this entity's table (SHOW COLUMNS).
     *
     * @return array List of column description rows
     * @throws Exception When database connection not set
     */
    public function showColumns(): array
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];

        $stmt = self::$db->query("SHOW FULL COLUMNS FROM `{$tableName}`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Set a MySQL session variable.
     *
     * @param string $name  Variable name (without @@)
     * @param mixed  $value Variable value
     *
     * @return void
     * @throws Exception When database connection not set
     */
    public static function setSessionVariable(string $name, mixed $value): void
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $stmt = self::$db->prepare("SET SESSION `{$name}` = ?");
        $stmt->execute([$value]);
    }

    /**
     * Get a MySQL session or global variable.
     *
     * @param string $name   Variable name (without @@)
     * @param string $scope  'SESSION' or 'GLOBAL'
     *
     * @return mixed Variable value
     * @throws Exception When database connection not set
     */
    public static function getVariable(string $name, string $scope = 'SESSION'): mixed
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $scope = strtoupper($scope) === 'GLOBAL' ? 'GLOBAL' : 'SESSION';
        $stmt = self::$db->query("SELECT @@{$scope}.`{$name}`");
        return $stmt->fetchColumn();
    }

    /**
     * Set the transaction isolation level for the current session.
     *
     * @param string $level One of: READ UNCOMMITTED, READ COMMITTED,
     *                      REPEATABLE READ, SERIALIZABLE
     *
     * @return void
     * @throws Exception When database connection not set or invalid level
     */
    public static function setIsolationLevel(string $level): void
    {
        $allowed = [
            'READ UNCOMMITTED',
            'READ COMMITTED',
            'REPEATABLE READ',
            'SERIALIZABLE',
        ];

        $level = strtoupper($level);
        if (!in_array($level, $allowed, true)) {
            throw new Exception("Invalid isolation level: {$level}");
        }

        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        self::$db->unprepared("SET SESSION TRANSACTION ISOLATION LEVEL {$level}");
    }

    /**
     * Execute a callback within a specific isolation level, then restore the original.
     *
     * @template TReturn
     * @param string          $level    Isolation level
     * @param callable(): TReturn $callback Business logic
     *
     * @return TReturn
     * @throws \Throwable
     */
    public static function withIsolationLevel(string $level, callable $callback): mixed
    {
        if (!self::$db) {
            throw new Exception("Database connection not set");
        }

        $original = self::getVariable('transaction_isolation');
        self::setIsolationLevel($level);

        try {
            $result = $callback();
            return $result;
        } finally {
            if ($original) {
                $restored = str_replace('-', ' ', strtoupper((string) $original));
                self::setIsolationLevel($restored);
            }
        }
    }

    /**
     * Return the raw PDO write connection for advanced use.
     *
     * @return PDO|null
     */
    public static function getPdo(): ?PDO
    {
        if (!self::$db) {
            return null;
        }

        return self::$db->forceWriteConnection();
    }

    /**
     * Execute a chunked DELETE to avoid long-running locks on large tables.
     *
     * Deletes matching rows in batches of $chunkSize within individual
     * transactions. Returns the total number of deleted rows.
     *
     * @param array $conditions  property => value conditions
     * @param int   $chunkSize   Rows per chunk
     *
     * @return int Total deleted rows
     * @throws Exception When database connection not set
     */
    public function chunkedDelete(array $conditions, int $chunkSize = 1000): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];
        $pkName = $metadata['primaryKey'];
        $pkCol = $metadata['columns'][$pkName]['name'];

        $whereParts = [];
        $whereParams = [];
        foreach ($conditions as $property => $value) {
            if (isset($metadata['columns'][$property])) {
                $colName = $metadata['columns'][$property]['name'];
                if (is_array($value)) {
                    $ph = implode(',', array_fill(0, count($value), '?'));
                    $whereParts[] = "`{$colName}` IN ({$ph})";
                    $whereParams = array_merge($whereParams, $value);
                } else {
                    $whereParts[] = "`{$colName}` = ?";
                    $whereParams[] = $value;
                }
            }
        }

        $whereClause = !empty($whereParts) ? ' WHERE ' . implode(' AND ', $whereParts) : '';
        $totalDeleted = 0;

        do {
            $selectSql = "SELECT `{$pkCol}` FROM `{$tableName}`{$whereClause} LIMIT {$chunkSize}";
            $stmt = self::$db->prepare($selectSql);
            $stmt->execute($whereParams);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($ids)) {
                break;
            }

            $ph = implode(',', array_fill(0, count($ids), '?'));
            $deleteSql = "DELETE FROM `{$tableName}` WHERE `{$pkCol}` IN ({$ph})";

            self::$db->beginTransaction();
            try {
                $delStmt = self::$db->prepare($deleteSql);
                $delStmt->execute($ids);
                $totalDeleted += $delStmt->rowCount();
                self::$db->commit();
            } catch (\Throwable $e) {
                self::$db->rollBack();
                throw $e;
            }
        } while (count($ids) === $chunkSize);

        return $totalDeleted;
    }

    /**
     * Execute a chunked UPDATE to avoid long-running locks on large tables.
     *
     * Updates matching rows in batches of $chunkSize within individual
     * transactions. Returns the total number of affected rows.
     *
     * @param array $conditions  property => value conditions
     * @param array $values      property => value updates
     * @param int   $chunkSize   Rows per chunk
     *
     * @return int Total affected rows
     * @throws Exception When database connection not set
     */
    public function chunkedUpdate(array $conditions, array $values, int $chunkSize = 1000): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];
        $pkName = $metadata['primaryKey'];
        $pkCol = $metadata['columns'][$pkName]['name'];

        $whereParts = [];
        $whereParams = [];
        foreach ($conditions as $property => $value) {
            if (isset($metadata['columns'][$property])) {
                $colName = $metadata['columns'][$property]['name'];
                if (is_array($value)) {
                    $ph = implode(',', array_fill(0, count($value), '?'));
                    $whereParts[] = "`{$colName}` IN ({$ph})";
                    $whereParams = array_merge($whereParams, $value);
                } else {
                    $whereParts[] = "`{$colName}` = ?";
                    $whereParams[] = $value;
                }
            }
        }

        $setParts = [];
        $setParams = [];
        foreach ($values as $property => $value) {
            if (isset($metadata['columns'][$property])) {
                $colName = $metadata['columns'][$property]['name'];
                $setParts[] = "`{$colName}` = ?";
                $setParams[] = $value;
            }
        }

        if (empty($setParts)) {
            return 0;
        }

        $whereClause = !empty($whereParts) ? ' WHERE ' . implode(' AND ', $whereParts) : '';
        $setClause = implode(', ', $setParts);
        $totalAffected = 0;

        do {
            $selectSql = "SELECT `{$pkCol}` FROM `{$tableName}`{$whereClause} LIMIT {$chunkSize}";
            $stmt = self::$db->prepare($selectSql);
            $stmt->execute($whereParams);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($ids)) {
                break;
            }

            $ph = implode(',', array_fill(0, count($ids), '?'));
            $updateSql = "UPDATE `{$tableName}` SET {$setClause} WHERE `{$pkCol}` IN ({$ph})";

            self::$db->beginTransaction();
            try {
                $updStmt = self::$db->prepare($updateSql);
                $updStmt->execute(array_merge($setParams, $ids));
                $totalAffected += $updStmt->rowCount();
                self::$db->commit();
            } catch (\Throwable $e) {
                self::$db->rollBack();
                throw $e;
            }
        } while (count($ids) === $chunkSize);

        return $totalAffected;
    }

    /**
     * Clone the current entity for INSERT, removing the primary key and resetting state.
     * Shorthand for replicate() that also handles timestamps.
     *
     * @param array $overrides  Property => value overrides
     *
     * @return static New unsaved entity
     */
    public function replicateForInsert(array $overrides = []): static
    {
        $clone = $this->cloneInstance($overrides);

        if ($this->timestamps && isset($this->getMetadata()['columns'][$this->createdAtColumn])) {
            $clone[$this->createdAtColumn] = date('Y-m-d H:i:s');
            $clone[$this->updatedAtColumn] = date('Y-m-d H:i:s');
        }

        return $clone;
    }

    /**
     * Insert a row and return the full hydrated entity (fetch-after-insert).
     *
     * Unlike create(), this fetches the row back from the database after
     * insertion so that DB defaults, triggers, and generated columns are
     * reflected in the returned instance.
     *
     * @param array $attributes Property => value
     *
     * @return static
     * @throws Exception When database connection not set
     */
    public static function createAndFetch(array $attributes): static
    {
        $instance = static::create($attributes);
        $pkName = $instance->getPrimaryKey();
        $pkValue = $instance[$pkName] ?? null;

        if ($pkValue !== null) {
            $fresh = $instance->find($pkValue);
            if ($fresh) {
                return $fresh;
            }
        }

        return $instance;
    }

    /**
     * Execute an UPDATE statement using the current query builder and return affected row count.
     * Resets the query builder after execution.
     *
     * @param array $values Property => value pairs to update
     *
     * @return int Affected rows
     * @throws Exception When database connection not set
     */
    protected function updateByQuery(array $values): int
    {
        $this->ensureConnection();
        $metadata = $this->getMetadata();
        $tableName = $metadata['table'];
        $columns = $metadata['columns'];

        $setParts = [];
        $setParams = [];
        foreach ($values as $property => $value) {
            if (isset($columns[$property])) {
                $colName = $columns[$property]['name'];
                $setParts[] = "`{$colName}` = ?";
                $setParams[] = $this->prepareValueForDatabase($value, $columns[$property]['type']);
            }
        }

        if (empty($setParts)) {
            $this->resetQuery();
            return 0;
        }

        $options = $this->buildQueryOptions();
        $this->applySoftDeleteScope($options);

        $sql = "UPDATE `{$tableName}` SET " . implode(', ', $setParts);
        $modified = $this->modifyQueryByOptions($sql, $options);
        $this->resetQuery();

        $allParams = array_merge($setParams, $modified['parameters']);
        $stmt = self::$db->prepare($modified['query']);
        $stmt->execute($allParams);

        return $stmt->rowCount();
    }

    #endregion
}
