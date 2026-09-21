<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\QueryBuilder;

use Clover\Classes\Database\SqlIdentifier;
use Exception;
use InvalidArgumentException;
use function in_array;
use function is_string;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * CREATE TABLE Query Builder
 *
 * Table, column, index, key and referenced names go through {@see SqlIdentifier}. The parts of a
 * DDL statement that are not identifiers — the column type, the storage engine, the character set,
 * the collation and the referential actions — are not quotable, so each is matched against a
 * pattern or a whitelist here instead. Column defaults and comments are still inlined as literals.
 */
class CreateQuery
{
    private $connection;
    private $table;
    private $columns = [];
    private $primaryKey = [];
    private $indexes = [];
    private $foreignKeys = [];
    private $engine = 'InnoDB';
    private $charset = 'utf8mb4';
    private $collation = 'utf8mb4_unicode_ci';
    private $temporary = false;
    private $ifNotExists = false;

    /**
     * Constructor
     * 
     * @param mixed $connection
     */
    public function __construct($connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Set the table name
     * 
     * @param string $table
     * 
     * @return self
     */
    public function createTable(string $table): self
    {
        $this->table = SqlIdentifier::quote($table);
        return $this;
    }

    /**
     * Add TEMPORARY clause
     * 
     * @return self
     */
    public function temporary(): self
    {
        $this->temporary = true;
        return $this;
    }

    /**
     * Add IF NOT EXISTS clause
     * 
     * @return self
     */
    public function ifNotExists(): self
    {
        $this->ifNotExists = true;
        return $this;
    }

    /**
     * Add a column definition
     * 
     * @param string $name
     * @param string $type
     * @param array $options
     * 
     * @return self
     */
    public function column(string $name, string $type, array $options = []): self
    {
        $name = SqlIdentifier::quote($name);
        $type = $this->validateColumnType($type);

        $column = "{$name} {$type}";

        // NULL/NOT NULL
        if (isset($options['nullable']) && !$options['nullable']) {
            $column .= ' NOT NULL';
        } elseif (isset($options['null']) && !$options['null']) {
            $column .= ' NOT NULL';
        }

        if (isset($options['default'])) {
            $default = $options['default'];
            if (is_string($default)) {
                $column .= " DEFAULT '{$default}'";
            } else {
                $column .= " DEFAULT {$default}";
            }
        }

        // AUTO_INCREMENT
        if (isset($options['auto_increment']) && $options['auto_increment']) {
            $column .= ' AUTO_INCREMENT';
        }

        // UNIQUE
        if (isset($options['unique']) && $options['unique']) {
            $column .= ' UNIQUE';
        }

        // COMMENT
        if (isset($options['comment'])) {
            $column .= " COMMENT '{$options['comment']}'";
        }

        $this->columns[] = $column;
        return $this;
    }

    /**
     * Check a column type against the shapes this builder is willing to emit.
     *
     * A type is not an identifier, so it cannot be quoted; instead it must match a base name with
     * an optional length or precision and an optional set of trailing attributes — `INT(11)`,
     * `VARCHAR(255)`, `DECIMAL(8, 2)`, `BIGINT UNSIGNED`, `TEXT`. Types that carry a value list,
     * such as `ENUM('a','b')`, are rejected: their quoting rules are the ones this builder has no
     * safe way to reproduce.
     *
     * @param string $type
     *
     * @return string The type as given, once it is known to be well formed.
     *
     * @throws InvalidArgumentException When the type does not match that shape.
     */
    private function validateColumnType(string $type): string
    {
        $candidate = trim($type);
        $pattern = '/^[A-Za-z][A-Za-z0-9_]*(\s*\(\s*\d+\s*(,\s*\d+\s*)?\))?(\s+(UNSIGNED|SIGNED|ZEROFILL|BINARY))*$/Di';

        if (preg_match($pattern, $candidate) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The column type `%s` is not supported.',
                $type
            ));
        }

        return $candidate;
    }

    /**
     * Check a bare SQL keyword-style setting: engine, character set or collation.
     *
     * @param string $value
     * @param string $label Used in the failure message.
     *
     * @return string
     *
     * @throws InvalidArgumentException When the value is not a bare name.
     */
    private function validateBareName(string $value, string $label): string
    {
        $candidate = trim($value);

        if (preg_match('/^[A-Za-z0-9_]+$/D', $candidate) !== 1) {
            throw new InvalidArgumentException(sprintf(
                'The %s `%s` is not a valid name.',
                $label,
                $value
            ));
        }

        return $candidate;
    }

    /**
     * Add INTEGER column
     * 
     * @param string $name
     * @param int|null $length
     * @param array $options
     * 
     * @return self
     */
    public function integer(string $name, int|null $length = null, array $options = []): self
    {
        $type = 'INT';
        if ($length) {
            $type .= "({$length})";
        }
        return $this->column($name, $type, $options);
    }

    /**
     * Add BIGINTEGER column
     * 
     * @param string $name
     * @param int|null $length
     * @param array $options
     * 
     * @return self
     */
    public function bigInteger(string $name, int|null $length = null, array $options = []): self
    {
        $type = 'BIGINT';
        if ($length) {
            $type .= "({$length})";
        }
        return $this->column($name, $type, $options);
    }

    /**
     * Add STRING/VARCHAR column
     * 
     * @param string $name
     * @param int $length
     * @param array $options
     * 
     * @return self
     */
    public function string(string $name, int $length = 255, array $options = []): self
    {
        $type = "VARCHAR({$length})";
        return $this->column($name, $type, $options);
    }

    /**
     * Add TEXT column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function text(string $name, array $options = []): self
    {
        return $this->column($name, 'TEXT', $options);
    }

    /**
     * Add LONGTEXT column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function longText(string $name, array $options = []): self
    {
        return $this->column($name, 'LONGTEXT', $options);
    }

    /**
     * Add BOOLEAN column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function boolean(string $name, array $options = []): self
    {
        return $this->column($name, 'BOOLEAN', $options);
    }

    /**
     * Add DECIMAL column
     * 
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @param array $options
     * 
     * @return self
     */
    public function decimal(string $name, int $precision = 8, int $scale = 2, array $options = []): self
    {
        $type = "DECIMAL({$precision}, {$scale})";
        return $this->column($name, $type, $options);
    }

    /**
     * Add FLOAT column
     * 
     * @param string $name
     * @param int $precision
     * @param int $scale
     * @param array $options
     * 
     * @return self
     */
    public function float(string $name, int $precision = 8, int $scale = 2, array $options = []): self
    {
        $type = "FLOAT({$precision}, {$scale})";
        return $this->column($name, $type, $options);
    }

    /**
     * Add DATE column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function date(string $name, array $options = []): self
    {
        return $this->column($name, 'DATE', $options);
    }

    /**
     * Add DATETIME column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function dateTime(string $name, array $options = []): self
    {
        return $this->column($name, 'DATETIME', $options);
    }

    /**
     * Add TIMESTAMP column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function timestamp(string $name, array $options = []): self
    {
        return $this->column($name, 'TIMESTAMP', $options);
    }

    /**
     * Add JSON column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function json(string $name, array $options = []): self
    {
        return $this->column($name, 'JSON', $options);
    }

    /**
     * Add BLOB column
     * 
     * @param string $name
     * @param array $options
     * 
     * @return self
     */
    public function blob(string $name, array $options = []): self
    {
        return $this->column($name, 'BLOB', $options);
    }

    /**
     * Set PRIMARY KEY
     * 
     * @param array|string $columns
     * 
     * @return self
     */
    public function primaryKey(array|string $columns): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $this->primaryKey = [];

        foreach ($columns as $column) {
            $this->primaryKey[] = SqlIdentifier::quote((string) $column);
        }

        return $this;
    }

    /**
     * Add INDEX
     * 
     * @param string $name
     * @param array|string $columns
     * @param bool $unique
     * 
     * @return self
     */
    public function index(string $name, array|string $columns, bool $unique = false): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $indexType = $unique ? 'UNIQUE INDEX' : 'INDEX';
        $name = SqlIdentifier::quote($name);
        $this->indexes[] = "{$indexType} {$name} (" . SqlIdentifier::quoteList($columns) . ")";
        return $this;
    }

    /**
     * Add UNIQUE INDEX
     * 
     * @param string $name
     * @param array|string $columns
     * 
     * @return self
     */
    public function uniqueIndex(string $name, array|string $columns): self
    {
        return $this->index($name, $columns, true);
    }

    /**
     * Add FOREIGN KEY constraint
     *
     * `$references` is either a table name or the usual `table(column)` spelling; both halves are
     * validated and quoted. The referential actions are matched against the SQL whitelist.
     *
     * @param string $name
     * @param string $column
     * @param string $references
     * @param string|null $onDelete
     * @param string|null $onUpdate
     *
     * @return self
     *
     * @throws InvalidArgumentException When a name, the reference or an action is not accepted.
     */
    public function foreignKey(string $name, string $column, string $references, string|null $onDelete = null, string|null $onUpdate = null): self
    {
        $name = SqlIdentifier::quote($name);
        $column = SqlIdentifier::quote($column);
        $references = $this->compileReference($references);

        $fk = "FOREIGN KEY {$name} ({$column}) REFERENCES {$references}";

        if ($onDelete) {
            $fk .= ' ON DELETE ' . $this->normalizeReferentialAction($onDelete);
        }

        if ($onUpdate) {
            $fk .= ' ON UPDATE ' . $this->normalizeReferentialAction($onUpdate);
        }

        $this->foreignKeys[] = $fk;
        return $this;
    }

    /**
     * Quote the target of a REFERENCES clause.
     *
     * @param string $references Either `table` or `table(column)`.
     *
     * @return string
     *
     * @throws InvalidArgumentException When the target is neither of those shapes.
     */
    private function compileReference(string $references): string
    {
        $candidate = trim($references);
        $matches = [];

        if (preg_match('/^(.+?)\s*\(\s*(.+?)\s*\)$/D', $candidate, $matches) === 1) {
            return SqlIdentifier::quote($matches[1]) . ' (' . SqlIdentifier::quote($matches[2]) . ')';
        }

        return SqlIdentifier::quote($candidate);
    }

    /**
     * Validate an ON DELETE / ON UPDATE action.
     *
     * @param string $action
     *
     * @return string The canonical upper-case action.
     *
     * @throws InvalidArgumentException When the action is not one SQL defines.
     */
    private function normalizeReferentialAction(string $action): string
    {
        $candidate = strtoupper(trim($action));
        $candidate = (string) preg_replace('/\s+/', ' ', $candidate);

        if (!in_array($candidate, ['RESTRICT', 'CASCADE', 'SET NULL', 'NO ACTION', 'SET DEFAULT'], true)) {
            throw new InvalidArgumentException(sprintf(
                'The referential action `%s` is not supported.',
                $action
            ));
        }

        return $candidate;
    }

    /**
     * Set storage engine for the table
     * 
     * @param string $engine
     * 
     * @return self
     */
    public function engine(string $engine): self
    {
        $this->engine = $this->validateBareName($engine, 'storage engine');
        return $this;
    }

    /**
     * Set character set for the table
     * 
     * @param string $charset
     * 
     * @return self
     */
    public function charset(string $charset): self
    {
        $this->charset = $this->validateBareName($charset, 'character set');
        return $this;
    }

    /**
     * Set collation for the table
     * 
     * @param string $collation
     * 
     * @return self
     */
    public function collation(string $collation): self
    {
        $this->collation = $this->validateBareName($collation, 'collation');
        return $this;
    }

    /**
     * Build the CREATE TABLE SQL query
     * 
     * @return string
     */
    public function toSql(): string
    {
        $sql = [];

        // CREATE TABLE
        $create = 'CREATE';
        if ($this->temporary) {
            $create .= ' TEMPORARY';
        }
        $create .= ' TABLE';
        if ($this->ifNotExists) {
            $create .= ' IF NOT EXISTS';
        }
        $create .= ' ' . $this->table;
        $sql[] = $create;

        
        $definitions = $this->columns;

        // PRIMARY KEY
        if (!empty($this->primaryKey)) {
            $definitions[] = 'PRIMARY KEY (' . implode(', ', $this->primaryKey) . ')';
        }

        // INDEXES
        $definitions = array_merge($definitions, $this->indexes);

        // FOREIGN KEYS
        $definitions = array_merge($definitions, $this->foreignKeys);

        $sql[] = '(' . implode(', ', $definitions) . ')';

        // ENGINE
        $sql[] = "ENGINE={$this->engine}";

        // CHARSET
        $sql[] = "DEFAULT CHARSET={$this->charset}";

        // COLLATION
        $sql[] = "COLLATE={$this->collation}";

        return implode(' ', $sql);
    }

    /**
     * Execute the CREATE TABLE query
     * 
     * @return mixed
     * 
     * @throws Exception
     */
    public function execute(): mixed
    {
        if ($this->connection) {
            $sql = $this->toSql();
            return $this->connection->query($sql);
        }

        throw new Exception('No database connection available');
    }
}
