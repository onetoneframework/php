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
use RuntimeException;
use function array_map;
use function explode;
use function is_array;

/**
 * Class WithQuery
 *
 * This class allows building SQL queries with Common Table Expressions (CTEs)
 *
 * CTE names and their column lists are validated by {@see SqlIdentifier} when the CTE is declared
 * and quoted when it is rendered; the raw name is kept alongside so it can be handed to
 * {@see SelectQuery::with()}, which quotes it itself. The CTE bodies are emitted untouched.
 */
class WithQuery
{
    private $connection;
    private $ctes = [];
    private $mainQuery;

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
     * Add a CTE
     * 
     * @param string $name
     * @param string $query
     * @param string|array|null $columns
     * 
     * @return self
     */
    public function with(string $name, string $query, string|array|null $columns = null): self
    {
        // Validated now, quoted at render: the raw name has to survive so prepareWithQuery() can
        // hand it to SelectQuery::with(), which quotes it itself.
        SqlIdentifier::quote($name);

        $this->ctes[] = [
            'name' => $name,
            'columns' => $this->normalizeCteColumns($columns),
            'query' => $query
        ];

        return $this;
    }

    /**
     * Validate a CTE column list and return it as a list of unquoted names.
     *
     * @param string|array|null $columns Either a list, a comma separated string, or nothing.
     *
     * @return array
     *
     * @throws \InvalidArgumentException When any column is not a valid identifier.
     */
    private function normalizeCteColumns(string|array|null $columns): array
    {
        if (empty($columns)) {
            return [];
        }

        if (!is_array($columns)) {
            $columns = array_map('trim', explode(',', $columns));
        }

        // Quoting here is thrown away; it is the validation that matters, so that a bad name is
        // reported by the call that introduced it rather than at render time.
        SqlIdentifier::quoteList($columns);

        return $columns;
    }

    /**
     * Render one CTE's name and column list.
     *
     * @param array $cte
     *
     * @return string
     */
    private function compileCteName(array $cte): string
    {
        $name = SqlIdentifier::quote($cte['name']);

        if (!empty($cte['columns'])) {
            $name .= ' (' . SqlIdentifier::quoteList($cte['columns']) . ')';
        }

        return $name;
    }

    /**
     * Add a recursive CTE
     * 
     * @param string $name
     * @param string $query
     * @param string|array|null $columns
     * 
     * @return self
     */
    public function withRecursive(string $name, string $query, string|array|null $columns = null): self
    {
        SqlIdentifier::quote($name);

        $this->ctes[] = [
            'name' => $name,
            'columns' => $this->normalizeCteColumns($columns),
            'query' => $query,
            'recursive' => true
        ];

        return $this;
    }

    /**
     * Prepare the main query by adding the WITH clauses
     */
    private function prepareWithQuery(): void
    {
        if (!$this->mainQuery) {
            throw new RuntimeException('Main query object is not initialized');
        }

        if (empty($this->ctes)) {
            return;
        }

        foreach ($this->ctes as $cte) {
            $this->mainQuery->with($cte['name'], $cte['query'], $cte['columns'] ?? []);
        }
    }

    /**
     * Start a SELECT query as the main query
     * 
     * @param string|array $columns
     * 
     * @return SelectQuery
     */
    public function select(string|array $columns = '*'): SelectQuery
    {
        $this->mainQuery = new SelectQuery($this->connection);
        $this->prepareWithQuery();

        return $this->mainQuery->select($columns);
    }

    /**
     * Set an existing SELECT query as the main query
     * 
     * @param SelectQuery $selectQuery
     * 
     * @return self
     */
    public function asSelect(SelectQuery $selectQuery): self
    {
        $this->mainQuery = $selectQuery;
        $this->prepareWithQuery();

        
        return $this;
    }

    /**
     * Build and get the SQL query string
     * 
     * @return string
     */
    public function toSql(): string
    {
        if (empty($this->ctes)) {
            throw new \Exception('No CTE defined');
        }

        $sql = [];

        $with = 'WITH';
        if ($this->hasRecursive()) {
            $with .= ' RECURSIVE';
        }

        $cteStrings = [];
        foreach ($this->ctes as $cte) {
            $cteString = $this->compileCteName($cte) . ' AS (' . $cte['query'] . ')';
            $cteStrings[] = $cteString;
        }

        $sql[] = $with . ' ' . implode(', ', $cteStrings);

        if ($this->mainQuery) {
            $sql[] = $this->mainQuery->toSql();
        }

        return implode(' ', $sql);
    }

    /**
     * Check if any CTE is recursive
     * 
     * @return bool
     */
    private function hasRecursive(): bool
    {
        foreach ($this->ctes as $cte) {
            if (isset($cte['recursive']) && $cte['recursive']) {
                return true;
            }
        }
        return false;
    }

    /**
     * Execute the built query
     * 
     * @return mixed
     * 
     * @throws \Exception
     */
    public function execute(): mixed
    {
        if ($this->connection) {
            $sql = $this->toSql();
            return $this->connection->query($sql);
        }
        
        throw new \Exception('No database connection available');
    }

    /**
     * Fetch all results from the executed query
     * 
     * @return array
     * 
     * @throws \Exception
     */
    public function get(): array
    {
        $result = $this->execute();
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Fetch the first result from the executed query
     * 
     * @return mixed
     * 
     * @throws \Exception
     */
    public function first(): mixed
    {
        $result = $this->execute();
        return $result ? $result->fetch() : null;
    }
}
