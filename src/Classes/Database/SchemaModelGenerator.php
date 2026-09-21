<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

use Clover\Classes\Database\Driver\PHPDataObject;
use Exception;
use RuntimeException;
use InvalidArgumentException;

/**
 * SchemaModelGenerator
 *
 * Reads database schema via PHPDataObject::getSchema() and generates
 * concrete ActiveRecord model classes with typed properties and
 * real findBy{Column}() methods, so IDE autocompletion works fully
 * without relying on __call magic.
 * 
 * @package Clover\Classes\Database
 */
class SchemaModelGenerator
{
	/** @var PHPDataObject */
	private PHPDataObject $pdo;

	/** @var string Namespace for generated model classes. */
	private string $namespace = 'App\\Models';

	/** @var string Filesystem directory to write generated files into. */
	private string $outputDirectory = '';

	/** @var string The fully qualified class name of the base ActiveRecord. */
	private string $baseClass = 'Clover\\Classes\\Database\\ActiveRecord';

	/** @var array<string, string> Table name overrides: table_name => ClassName. */
	private array $classNameOverrides = [];

	/** @var string[] Tables to skip during generation. */
	private array $excludedTables = [];

	/** @var string[] If non-empty, only these tables are generated. */
	private array $includedTables = [];

	/** @var bool Whether to generate findBy methods. */
	private bool $generateFinders = true;

	/** @var bool Whether to generate a static create() factory. */
	private bool $generateFactory = true;

	/** @var bool Whether to generate scope methods (scopeActive, etc.). */
	private bool $generateScopes = false;

	/** @var bool Whether to overwrite existing files. */
	private bool $overwrite = true;

	/** @var string Indentation string (4 spaces by default). */
	private string $indent = '    ';

	/** @var string[] Columns that should appear in $hidden by default. */
	private array $defaultHiddenColumns = ['password', 'password_hash', 'secret', 'token', 'api_key'];

	/** @var string|null Soft delete column name to detect (e.g. 'deleted_at'). */
	private ?string $softDeleteColumn = 'deleted_at';

	/** @var bool Whether to add timestamp properties when created_at/updated_at exist. */
	private bool $detectTimestamps = true;

	/**
	 * SQL type to PHP type mapping.
	 *
	 * @var array<string, string>
	 */
	private static array $typeMap = [
		// Integers
		'tinyint' => 'int',
		'smallint' => 'int',
		'mediumint' => 'int',
		'int' => 'int',
		'integer' => 'int',
		'bigint' => 'int',
		'serial' => 'int',

		// Floats
		'float' => 'float',
		'double' => 'float',
		'decimal' => 'float',
		'numeric' => 'float',
		'real' => 'float',

		// Strings
		'char' => 'string',
		'varchar' => 'string',
		'tinytext' => 'string',
		'text' => 'string',
		'mediumtext' => 'string',
		'longtext' => 'string',
		'enum' => 'string',
		'set' => 'string',
		'uuid' => 'string',

		// Binary
		'binary' => 'string',
		'varbinary' => 'string',
		'blob' => 'string',
		'tinyblob' => 'string',
		'mediumblob' => 'string',
		'longblob' => 'string',

		// Date/Time
		'date' => 'string',
		'datetime' => 'string',
		'timestamp' => 'string',
		'time' => 'string',
		'year' => 'int',

		// Other
		'json' => 'array',
		'boolean' => 'bool',
		'bool' => 'bool',
		'bit' => 'int',
	];

	/**
	 * SQL type to ActiveRecord $casts mapping.
	 *
	 * @var array<string, string>
	 */
	private static array $castsMap = [
		'tinyint' => 'integer',
		'smallint' => 'integer',
		'mediumint' => 'integer',
		'int' => 'integer',
		'integer' => 'integer',
		'bigint' => 'integer',
		'float' => 'float',
		'double' => 'float',
		'decimal' => 'float',
		'numeric' => 'float',
		'boolean' => 'boolean',
		'bool' => 'boolean',
		'json' => 'json',
		'date' => 'date',
		'datetime' => 'datetime',
		'timestamp' => 'timestamp',
	];

	/**
	 * @param PHPDataObject $pdo A connected PHPDataObject instance.
	 */
	public function __construct(PHPDataObject $pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * Set the PHP namespace for generated classes.
	 *
	 * @param string $namespace
	 * @return self
	 */
	public function setNamespace(string $namespace): self
	{
		$this->namespace = rtrim($namespace, '\\');
		return $this;
	}

	/**
	 * Set the filesystem directory for output files.
	 *
	 * @param string $directory
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function setOutputDirectory(string $directory): self
	{
		$this->outputDirectory = rtrim($directory, '/\\');
		return $this;
	}

	/**
	 * Set the base class that generated models extend.
	 *
	 * @param string $fqcn Fully qualified class name.
	 * @return self
	 */
	public function setBaseClass(string $fqcn): self
	{
		$this->baseClass = $fqcn;
		return $this;
	}

	/**
	 * Override the generated class name for a specific table.
	 *
	 * @param string $tableName
	 * @param string $className
	 * @return self
	 */
	public function setClassNameOverride(string $tableName, string $className): self
	{
		$this->classNameOverrides[$tableName] = $className;
		return $this;
	}

	/**
	 * Set multiple class name overrides at once.
	 *
	 * @param array<string, string> $overrides table_name => ClassName
	 * @return self
	 */
	public function setClassNameOverrides(array $overrides): self
	{
		$this->classNameOverrides = $overrides;
		return $this;
	}

	/**
	 * Exclude specific tables from generation.
	 *
	 * @param string[] $tables
	 * @return self
	 */
	public function setExcludedTables(array $tables): self
	{
		$this->excludedTables = $tables;
		return $this;
	}

	/**
	 * Only generate models for these tables (empty = all).
	 *
	 * @param string[] $tables
	 * @return self
	 */
	public function setIncludedTables(array $tables): self
	{
		$this->includedTables = $tables;
		return $this;
	}

	/**
	 * Control whether findBy/findOneBy methods are generated.
	 *
	 * @param bool $enabled
	 * @return self
	 */
	public function setGenerateFinders(bool $enabled): self
	{
		$this->generateFinders = $enabled;
		return $this;
	}

	/**
	 * Control whether a static create() factory method is generated.
	 *
	 * @param bool $enabled
	 * @return self
	 */
	public function setGenerateFactory(bool $enabled): self
	{
		$this->generateFactory = $enabled;
		return $this;
	}

	/**
	 * Control whether to overwrite existing model files.
	 *
	 * @param bool $overwrite
	 * @return self
	 */
	public function setOverwrite(bool $overwrite): self
	{
		$this->overwrite = $overwrite;
		return $this;
	}

	/**
	 * Set the soft delete column name to detect (null to disable).
	 *
	 * @param string|null $column
	 * @return self
	 */
	public function setSoftDeleteColumn(?string $column): self
	{
		$this->softDeleteColumn = $column;
		return $this;
	}

	/**
	 * Generate model files for all matching tables.
	 *
	 * Reads the schema from the database, then writes one PHP file
	 * per table into the output directory.
	 *
	 * @param string|null $database Database name override (null = use PDO's current database).
	 * @return array<string, string> Map of table name => generated file path.
	 * @throws RuntimeException If the output directory cannot be created.
	 * @throws Exception On schema read failure.
	 */
	public function generate(?string $database = null): array
	{
		if ($this->outputDirectory === '') {
			throw new RuntimeException('Output directory not set. Call setOutputDirectory() first.');
		}

		if (!is_dir($this->outputDirectory)) {
			if (!mkdir($this->outputDirectory, 0755, true) && !is_dir($this->outputDirectory)) {
				throw new RuntimeException(sprintf('Cannot create output directory: %s', $this->outputDirectory));
			}
		}

		$schema = $this->pdo->getSchema($database);
		$generated = [];

		foreach ($schema as $tableName => $columns) {
			if ($this->shouldSkipTable($tableName)) {
				continue;
			}

			$className = $this->resolveClassName($tableName);
			$code = $this->generateClassCode($tableName, $className, $columns);
			$filePath = $this->outputDirectory . DIRECTORY_SEPARATOR . $className . '.php';

			if (!$this->overwrite && file_exists($filePath)) {
				continue;
			}

			file_put_contents($filePath, $code);
			$generated[$tableName] = $filePath;
		}

		return $generated;
	}

	/**
	 * Generate model code for a single table without writing to disk.
	 *
	 * Useful for previewing or piping into other tools.
	 *
	 * @param string $tableName
	 * @param array $columns Column metadata from getSchema().
	 * @return string The full PHP source code.
	 */
	public function generateForTable(string $tableName, array $columns): string
	{
		$className = $this->resolveClassName($tableName);
		return $this->generateClassCode($tableName, $className, $columns);
	}

	/**
	 * Determine whether a table should be skipped.
	 *
	 * @param string $tableName
	 * @return bool
	 */
	private function shouldSkipTable(string $tableName): bool
	{
		if (in_array($tableName, $this->excludedTables, true)) {
			return true;
		}

		if (!empty($this->includedTables) && !in_array($tableName, $this->includedTables, true)) {
			return true;
		}

		return false;
	}

	/**
	 * Convert a table name to a PascalCase class name.
	 *
	 * Applies overrides first, then converts snake_case to PascalCase
	 * and strips trailing 's' for naive singularization.
	 *
	 * @param string $tableName
	 * @return string
	 */
	private function resolveClassName(string $tableName): string
	{
		if (isset($this->classNameOverrides[$tableName])) {
			return $this->classNameOverrides[$tableName];
		}

		$singular = $this->singularize($tableName);
		return $this->snakeToPascal($singular);
	}

	/**
	 * Generate the full PHP source for a single model class.
	 *
	 * @param string $tableName
	 * @param string $className
	 * @param array $columns Column metadata from information_schema.
	 * @return string
	 */
	private function generateClassCode(string $tableName, string $className, array $columns): string
	{
		$i = $this->indent;
		$primaryKey = null;
		$fillableColumns = [];
		$hiddenColumns = [];
		$casts = [];
		$properties = [];
		$hasSoftDelete = false;
		$hasCreatedAt = false;
		$hasUpdatedAt = false;

		foreach ($columns as $col) {
			$colName = $col['COLUMN_NAME'];
			$dataType = strtolower($col['DATA_TYPE']);
			$colType = $col['COLUMN_TYPE'] ?? $dataType;
			$nullable = ($col['IS_NULLABLE'] ?? 'YES') === 'YES';
			$colKey = $col['COLUMN_KEY'] ?? '';
			$extra = $col['EXTRA'] ?? '';
			$colDefault = $col['COLUMN_DEFAULT'] ?? null;

			$phpType = $this->mapSqlTypeToPhp($dataType);
			$propName = $this->snakeToCamel($colName);

			$isPrimary = ($colKey === 'PRI');

			if ($isPrimary) {
				$primaryKey = [
					'property' => $propName,
					'column' => $colName,
					'type' => $phpType,
					'autoIncrement' => str_contains(strtolower($extra), 'auto_increment'),
				];
			}

			if ($colName === $this->softDeleteColumn) {
				$hasSoftDelete = true;
			}
			if ($colName === 'created_at') {
				$hasCreatedAt = true;
			}
			if ($colName === 'updated_at') {
				$hasUpdatedAt = true;
			}

			if (!$isPrimary && !str_contains(strtolower($extra), 'auto_increment')) {
				$fillableColumns[] = $propName;
			}

			if (in_array($colName, $this->defaultHiddenColumns, true)) {
				$hiddenColumns[] = $propName;
			}

			$castType = $this->mapSqlTypeToCast($dataType, $colType);
			if ($castType !== null) {
				$casts[$propName] = $castType;
			}

			$properties[] = [
				'name' => $propName,
				'column' => $colName,
				'phpType' => $phpType,
				'sqlType' => $dataType,
				'colType' => $colType,
				'nullable' => $nullable,
				'isPrimary' => $isPrimary,
				'default' => $colDefault,
				'extra' => $extra,
				'maxLength' => $col['CHARACTER_MAXIMUM_LENGTH'] ?? null,
				'precision' => $col['NUMERIC_PRECISION'] ?? null,
				'scale' => $col['NUMERIC_SCALE'] ?? null,
			];
		}

		$lines = [];
		$lines[] = '<?php';
		$lines[] = '';
		$lines[] = 'declare(strict_types=1);';
		$lines[] = '';
		$lines[] = '/**';
		$lines[] = ' * Auto-generated model for table `' . $tableName . '`';
		$lines[] = ' *';
		$lines[] = ' * Generated by SchemaModelGenerator on ' . date('Y-m-d H:i:s');
		$lines[] = ' * DO NOT EDIT — re-run the generator to update.';
		$lines[] = ' */';
		$lines[] = '';
		$lines[] = 'namespace ' . $this->namespace . ';';
		$lines[] = '';
		$lines[] = 'use ' . $this->baseClass . ';';
		$lines[] = '';

		// Class-level PHPDoc
		$lines[] = '/**';
		$lines[] = ' * @Table(name="' . $tableName . '")';
		$lines[] = ' *';

		foreach ($properties as $prop) {
			$typeHint = $prop['nullable'] ? $prop['phpType'] . '|null' : $prop['phpType'];
			$lines[] = ' * @property ' . $typeHint . ' $' . $prop['name'];
		}

		$lines[] = ' */';
		$lines[] = 'class ' . $className . ' extends ActiveRecord';
		$lines[] = '{';

		// $table
		$lines[] = $i . '/** @var string */';
		$lines[] = $i . 'protected ?string $table = \'' . $tableName . '\';';
		$lines[] = '';

		// $fillable
		$lines[] = $i . '/** @var string[] */';
		$lines[] = $i . 'protected array $fillable = [';
		foreach ($fillableColumns as $fc) {
			$lines[] = $i . $i . '\'' . $fc . '\',';
		}
		$lines[] = $i . '];';
		$lines[] = '';

		// $guarded
		$lines[] = $i . '/** @var string[] */';
		$lines[] = $i . 'protected array $guarded = [];';
		$lines[] = '';

		// $hidden
		if (!empty($hiddenColumns)) {
			$lines[] = $i . '/** @var string[] */';
			$lines[] = $i . 'protected array $hidden = [';
			foreach ($hiddenColumns as $hc) {
				$lines[] = $i . $i . '\'' . $hc . '\',';
			}
			$lines[] = $i . '];';
			$lines[] = '';
		}

		// $casts
		if (!empty($casts)) {
			$lines[] = $i . '/** @var array<string, string> */';
			$lines[] = $i . 'protected array $casts = [';
			foreach ($casts as $prop => $cast) {
				$lines[] = $i . $i . '\'' . $prop . '\' => \'' . $cast . '\',';
			}
			$lines[] = $i . '];';
			$lines[] = '';
		}

		// Timestamps
		if ($this->detectTimestamps && $hasCreatedAt && $hasUpdatedAt) {
			$lines[] = $i . '/** @var bool */';
			$lines[] = $i . 'protected bool $timestamps = true;';
			$lines[] = '';
		}

		// Soft delete
		if ($hasSoftDelete && $this->softDeleteColumn !== null) {
			$lines[] = $i . '/** @var string|null */';
			$lines[] = $i . 'protected ?string $softDeleteColumn = \'' . $this->softDeleteColumn . '\';';
			$lines[] = '';
		}

		// --- Property declarations with @Column annotations ---
		foreach ($properties as $prop) {
			$typeHint = $prop['nullable'] ? '?' . $prop['phpType'] : $prop['phpType'];
			$nullableStr = $prop['nullable'] ? 'true' : 'false';

			$lines[] = $i . '/**';

			if ($prop['isPrimary']) {
				$lines[] = $i . ' * @Id';
			}

			$lines[] = $i . ' * @Column(name="' . $prop['column'] . '", type="' . $prop['sqlType'] . '", nullable=' . $nullableStr . ')';
			$lines[] = $i . ' */';

			$defaultValue = $this->resolveDefaultValue($prop);
			$lines[] = $i . 'protected ' . $typeHint . ' $' . $prop['name'] . $defaultValue . ';';
			$lines[] = '';
		}

		// --- Static create() factory ---
		if ($this->generateFactory) {
			$lines = array_merge($lines, $this->generateCreateFactory($className, $properties, $primaryKey, $i));
		}

		// --- findBy{Column} methods ---
		if ($this->generateFinders) {
			foreach ($properties as $prop) {
				if ($prop['isPrimary']) {
					continue;
				}

				$lines = array_merge($lines, $this->generateFindByMethod($prop, $i));
				$lines = array_merge($lines, $this->generateFindOneByMethod($prop, $i));
			}
		}

		// --- Getters and setters ---
		foreach ($properties as $prop) {
			$lines = array_merge($lines, $this->generateGetter($prop, $i));
			if (!$prop['isPrimary']) {
				$lines = array_merge($lines, $this->generateSetter($prop, $className, $i));
			}
		}

		$lines[] = '}';
		$lines[] = '';

		return implode("\n", $lines);
	}

	/**
	 * Generate a findBy{Column} method that returns an array of entities.
	 *
	 * @param array $prop Column property metadata.
	 * @param string $i Indentation.
	 * @return string[]
	 */
	private function generateFindByMethod(array $prop, string $i): array
	{
		$methodName = 'findBy' . $this->snakeToPascal($prop['name']);
		$paramType = $prop['phpType'];

		return [
			$i . '/**',
			$i . ' * Find all records where `' . $prop['column'] . '` matches the given value.',
			$i . ' *',
			$i . ' * @param ' . $paramType . ' $value',
			$i . ' * @param string $operator',
			$i . ' * @return static[]|static|null',
			$i . ' */',
			$i . 'public function ' . $methodName . '(' . $paramType . ' $value, string $operator = \'=\'): array|object|null',
			$i . '{',
			$i . $i . 'return $this->findBy(\'' . $prop['name'] . '\', $value, $operator);',
			$i . '}',
			'',
		];
	}

	/**
	 * Generate a findOneBy{Column} method that returns a single entity or null.
	 *
	 * @param array $prop
	 * @param string $i
	 * @return string[]
	 */
	private function generateFindOneByMethod(array $prop, string $i): array
	{
		$methodName = 'findOneBy' . $this->snakeToPascal($prop['name']);
		$paramType = $prop['phpType'];

		return [
			$i . '/**',
			$i . ' * Find a single record where `' . $prop['column'] . '` matches the given value.',
			$i . ' *',
			$i . ' * @param ' . $paramType . ' $value',
			$i . ' * @param string $operator',
			$i . ' * @return static|null',
			$i . ' */',
			$i . 'public function ' . $methodName . '(' . $paramType . ' $value, string $operator = \'=\'): ?object',
			$i . '{',
			$i . $i . 'return $this->findOneBy(\'' . $prop['name'] . '\', $value, $operator);',
			$i . '}',
			'',
		];
	}

	/**
	 * Generate a static create() factory method.
	 *
	 * @param string $className
	 * @param array $properties
	 * @param array|null $primaryKey
	 * @param string $i
	 * @return string[]
	 */
	private function generateCreateFactory(string $className, array $properties, ?array $primaryKey, string $i): array
	{
		$params = [];
		$assignments = [];

		foreach ($properties as $prop) {
			if ($prop['isPrimary'] && ($primaryKey['autoIncrement'] ?? false)) {
				continue;
			}

			if (in_array($prop['column'], ['created_at', 'updated_at', $this->softDeleteColumn], true)) {
				continue;
			}

			$typeHint = $prop['nullable'] ? '?' . $prop['phpType'] : $prop['phpType'];
			$default = $prop['nullable'] ? ' = null' : '';
			$params[] = $i . $i . $typeHint . ' $' . $prop['name'] . $default . ',';
			$assignments[] = $i . $i . '$entity->' . $prop['name'] . ' = $' . $prop['name'] . ';';
		}

		$lines = [];
		$lines[] = $i . '/**';
		$lines[] = $i . ' * Create a new ' . $className . ' instance and save it to the database.';
		$lines[] = $i . ' *';
		$lines[] = $i . ' * @return static';
		$lines[] = $i . ' */';
		$lines[] = $i . 'public static function create(';

		foreach ($params as $param) {
			$lines[] = $param;
		}

		$lines[] = $i . '): static {';
		$lines[] = $i . $i . '$entity = new static();';

		foreach ($assignments as $assignment) {
			$lines[] = $assignment;
		}

		$lines[] = $i . $i . '$entity->save();';
		$lines[] = $i . $i . 'return $entity;';
		$lines[] = $i . '}';
		$lines[] = '';

		return $lines;
	}

	/**
	 * Generate a typed getter for a property.
	 *
	 * @param array $prop
	 * @param string $i
	 * @return string[]
	 */
	private function generateGetter(array $prop, string $i): array
	{
		$methodName = 'get' . $this->snakeToPascal($prop['name']);
		$returnType = $prop['nullable'] ? '?' . $prop['phpType'] : $prop['phpType'];

		return [
			$i . '/**',
			$i . ' * @return ' . ($prop['nullable'] ? $prop['phpType'] . '|null' : $prop['phpType']),
			$i . ' */',
			$i . 'public function ' . $methodName . '(): ' . $returnType,
			$i . '{',
			$i . $i . 'return $this->' . $prop['name'] . ';',
			$i . '}',
			'',
		];
	}

	/**
	 * Generate a typed fluent setter for a property.
	 *
	 * @param array $prop
	 * @param string $className
	 * @param string $i
	 * @return string[]
	 */
	private function generateSetter(array $prop, string $className, string $i): array
	{
		$methodName = 'set' . $this->snakeToPascal($prop['name']);
		$paramType = $prop['nullable'] ? '?' . $prop['phpType'] : $prop['phpType'];

		return [
			$i . '/**',
			$i . ' * @param ' . ($prop['nullable'] ? $prop['phpType'] . '|null' : $prop['phpType']) . ' $value',
			$i . ' * @return static',
			$i . ' */',
			$i . 'public function ' . $methodName . '(' . $paramType . ' $value): static',
			$i . '{',
			$i . $i . '$this->' . $prop['name'] . ' = $value;',
			$i . $i . 'return $this;',
			$i . '}',
			'',
		];
	}

	// -------------------------------------------------------------------------
	// Type mapping
	// -------------------------------------------------------------------------

	/**
	 * Map an SQL data type to a PHP type string.
	 *
	 * @param string $sqlType
	 * @return string
	 */
	private function mapSqlTypeToPhp(string $sqlType): string
	{
		$normalized = strtolower(trim($sqlType));

		// Handle tinyint(1) as bool
		if ($normalized === 'tinyint') {
			return 'int';
		}

		return self::$typeMap[$normalized] ?? 'string';
	}

	/**
	 * Map an SQL type to an ActiveRecord $casts value.
	 *
	 * @param string $dataType SQL DATA_TYPE.
	 * @param string $colType SQL COLUMN_TYPE (e.g. "tinyint(1)").
	 * @return string|null Cast type string or null if no cast needed.
	 */
	private function mapSqlTypeToCast(string $dataType, string $colType): ?string
	{
		$normalized = strtolower($dataType);

		// tinyint(1) -> boolean
		if ($normalized === 'tinyint' && preg_match('/tinyint\(1\)/i', $colType)) {
			return 'boolean';
		}

		return self::$castsMap[$normalized] ?? null;
	}

	/**
	 * Resolve a default value expression for a property declaration.
	 *
	 * @param array $prop
	 * @return string e.g. " = null" or " = 0" or "".
	 */
	private function resolveDefaultValue(array $prop): string
	{
		if ($prop['nullable']) {
			return ' = null';
		}

		if ($prop['default'] !== null) {
			return match ($prop['phpType']) {
				'int' => ' = ' . (int) $prop['default'],
				'float' => ' = ' . (float) $prop['default'],
				'bool' => ' = ' . ($prop['default'] ? 'true' : 'false'),
				'array' => ' = []',
				default => '',
			};
		}

		// Non-nullable without default: no initializer (constructor handles it)
		return '';
	}

	// -------------------------------------------------------------------------
	// String helpers
	// -------------------------------------------------------------------------

	/**
	 * Convert snake_case to PascalCase.
	 *
	 * @param string $input
	 * @return string
	 */
	private function snakeToPascal(string $input): string
	{
		return str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $input)));
	}

	/**
	 * Convert snake_case to camelCase.
	 *
	 * @param string $input
	 * @return string
	 */
	private function snakeToCamel(string $input): string
	{
		return lcfirst($this->snakeToPascal($input));
	}

	/**
	 * Naive singularization: strip trailing 's'.
	 *
	 * Handles common patterns: -ies => -y, -ses => -s, -s => (remove).
	 *
	 * @param string $word
	 * @return string
	 */
	private function singularize(string $word): string
	{
		if (str_ends_with($word, 'ies')) {
			return substr($word, 0, -3) . 'y';
		}

		if (str_ends_with($word, 'sses')) {
			return substr($word, 0, -2);
		}

		if (str_ends_with($word, 'ses')) {
			return substr($word, 0, -2);
		}

		if (str_ends_with($word, 'ves')) {
			return substr($word, 0, -3) . 'f';
		}

		if (str_ends_with($word, 's') && !str_ends_with($word, 'ss') && !str_ends_with($word, 'us')) {
			return substr($word, 0, -1);
		}

		return $word;
	}
}
