<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

use Clover\Classes\Database\QueryBuilder\{SelectQuery, InsertQuery, UpdateQuery, DeleteQuery, CreateQuery, WithQuery};

/**
 * Class QueryBuilder
 *
 * A class for building database queries.
 */
class QueryBuilder
{
    /** @var mixed  */
    private $connection;

    /** @var string  */
    private string $queryType;

    /** @var SelectQuery| InsertQuery| UpdateQuery| DeleteQuery| CreateQuery| WithQuery $query */
    private mixed $query;

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
     * Create a select query object
     * 
     * @param array|string $columns
     * 
     * @return SelectQuery
     */
    public function select(array|string $columns = '*'): SelectQuery
    {
        $this->queryType = 'SELECT';
        $this->query = new SelectQuery($this->connection);
        return $this->query->select($columns);
    }

    /**
     * Create a insert query object
     * 
     * @param string $table
     * 
     * @return InsertQuery
     */
    public function insert(string $table): InsertQuery
    {
        $this->queryType = 'INSERT';
        $this->query = new InsertQuery($this->connection);
        return $this->query->insert($table);
    }

    /**
     * Create a update query object
     * 
     * @param string $table
     * 
     * @return UpdateQuery
     */
    public function update(string $table): UpdateQuery
    {
        $this->queryType = 'UPDATE';
        $this->query = new UpdateQuery($this->connection);
        return $this->query->update($table);
    }

    /**
     * Create a delete query object
     * 
     * @param string $table
     * 
     * @return DeleteQuery
     */
    public function delete(?string $table = null): DeleteQuery
    {
        $this->queryType = 'DELETE';
        $this->query = new DeleteQuery($this->connection);
        return $this->query->delete($table);
    }

    /**
     * Create a create query object
     * 
     * @param string $table
     * 
     * @return CreateQuery
     */
    public function createTable(string $table): CreateQuery
    {
        $this->queryType = 'CREATE';
        $this->query = new CreateQuery($this->connection);
        return $this->query->createTable($table);
    }

    /**
     * Create a with cte query object
     * 
     * @param string $name
     * @param string $query
     * 
     * @return WithQuery
     */
    public function with(string $name, string $query): WithQuery
    {
        $this->queryType = 'WITH';
        $this->query = new WithQuery($this->connection);
        return $this->query->with($name, $query);
    }

    /**
     * Get a currently query
     * 
     * @return CreateQuery|DeleteQuery|InsertQuery|SelectQuery|UpdateQuery|WithQuery
     */
    public function getQuery(): CreateQuery|DeleteQuery|InsertQuery|SelectQuery|UpdateQuery|WithQuery
    {
        return $this->query;
    }

    /**
     * Execute queries
     * 
     * @return mixed
     * 
     * @throws \Exception
     */
    public function execute(): mixed
    {
        if ($this->query && $this->connection) {
            return $this->query->execute();
        }

        throw new \Exception('No query or connection available');
    }

    /**
     * Get a query string
     * 
     * @return string
     */
    public function toSql(): string
    {
        if ($this->query) {
            return $this->query->toSql();
        }

        return '';
    }
}
