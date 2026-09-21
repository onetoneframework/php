<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

/**
 * The query an ActiveRecord instance is building up: conditions, ordering,
 * limits, joins, grouping, selection, locking, CTEs, subqueries and unions.
 *
 * This is deliberately *not* `Clover\Classes\Database\QueryBuilder`, which is a
 * separate standalone builder with its own SelectQuery/InsertQuery family. This
 * one exists only to hold the eighteen `query*` fields ActiveRecord used to
 * carry itself, so that the chain a model accumulates lives in one object with
 * one reset point rather than as scattered state on a 11,000-line class.
 *
 * Every method here is a move of code that was inline in ActiveRecord; the
 * shape of `toOptions()` is exactly the array modifyQueryByOptions() has always
 * consumed.
 *
 * @package Clover\Classes\Database
 *
 * @phpstan-type ConditionEntry array<string, mixed>
 * @phpstan-type WhereRawEntry array{expression: string, bindings: list<mixed>, boolean: string}
 * @phpstan-type HavingEntry array{column?: string, operator?: string, value?: mixed, raw?: string, bindings?: list<mixed>}
 * @phpstan-type JoinEntry array{type: string, table: string, first: string, operator: string, second: string}
 */
class ActiveRecordQuery
{
	#region properties

	/**
	 * Conditions to be combined with AND, unless an entry carries its own boolean.
	 *
	 * @var list<ConditionEntry>
	 */
	private array $conditions = [];

	/**
	 * Conditions to be combined with OR.
	 *
	 * @var array<string, mixed>
	 */
	private array $orConditions = [];

	/**
	 * Raw WHERE fragments with their bindings. Trusted input only.
	 *
	 * @var list<WhereRawEntry>
	 */
	private array $whereRaw = [];

	/** @var string|null $order Quoted column to sort by. */
	private ?string $order = null;

	/** @var string|null $arrange Sort direction, already whitelisted. */
	private ?string $arrange = null;

	/**
	 * Raw ORDER BY fragments. Trusted input only.
	 *
	 * @var list<string>
	 */
	private array $orderByRaw = [];

	/** @var int|null $limit Row limit, or null for no limit. */
	private ?int $limit = null;

	/** @var int|null $offset Row offset, or null for none. */
	private ?int $offset = null;

	/**
	 * Quoted columns to group by.
	 *
	 * @var list<string>
	 */
	private array $groupBy = [];

	/**
	 * HAVING entries, either column/operator/value or a raw fragment.
	 *
	 * @var list<HavingEntry>
	 */
	private array $having = [];

	/**
	 * JOIN entries.
	 *
	 * @var list<JoinEntry>
	 */
	private array $joins = [];

	/**
	 * Columns or expressions to select.
	 *
	 * @var list<string>
	 */
	private array $select = [];

	/** @var bool $distinct Whether the select is DISTINCT. */
	private bool $distinct = false;

	/** @var string|null $lock Row-locking clause, already whitelisted. */
	private ?string $lock = null;

	/**
	 * Common table expressions.
	 *
	 * @var list<array{name: string, sql: string, bindings: list<mixed>, recursive: bool}>
	 */
	private array $ctes = [];

	/**
	 * Subquery to select FROM instead of the table.
	 *
	 * @var array{sql: string, bindings: list<mixed>, alias: string}|null
	 */
	private ?array $fromSubquery = null;

	/**
	 * WHERE clauses whose right-hand side is a subquery.
	 *
	 * @var list<array{column: string, operator: string, sql: string, bindings: list<mixed>, boolean: string}>
	 */
	private array $whereSubqueries = [];

	/**
	 * Queries to UNION with this one.
	 *
	 * @var list<array{sql: string, bindings: list<mixed>, all: bool}>
	 */
	private array $unions = [];

	#endregion

	#region conditions

	/**
	 * Append a condition.
	 *
	 * @param array<string, mixed> $condition The condition entry.
	 *
	 * @return void
	 */
	public function addCondition(array $condition): void
	{
		$this->conditions[] = $condition;
	}

	/**
	 * Whether any condition has been added.
	 *
	 * @return bool
	 */
	public function hasConditions(): bool
	{
		return $this->conditions !== [];
	}

	/**
	 * The conditions accumulated so far.
	 *
	 * @return list<ConditionEntry>
	 */
	public function conditions(): array
	{
		return $this->conditions;
	}

	/**
	 * Replace the conditions wholesale.
	 *
	 * Used when capturing a nested group: the outer set is taken aside, the
	 * group is collected on a cleared list, and the outer set is put back.
	 *
	 * @param list<ConditionEntry> $conditions The conditions to install.
	 *
	 * @return void
	 */
	public function setConditions(array $conditions): void
	{
		$this->conditions = $conditions;
	}

	/**
	 * Combine the most recently added condition with OR rather than AND.
	 *
	 * @return void
	 */
	public function markLastConditionAsOr(): void
	{
		$lastIndex = array_key_last($this->conditions);

		if ($lastIndex === null) {
			return;
		}

		$this->conditions[$lastIndex]['boolean'] = 'OR';
	}

	/**
	 * Append a raw WHERE fragment. Trusted input only.
	 *
	 * @param string     $expression The SQL fragment.
	 * @param list<mixed> $bindings  Values bound to its placeholders.
	 * @param string     $boolean    How to combine it, AND or OR.
	 *
	 * @return void
	 */
	public function addWhereRaw(string $expression, array $bindings, string $boolean): void
	{
		$this->whereRaw[] = ['expression' => $expression, 'bindings' => $bindings, 'boolean' => $boolean];
	}

	/**
	 * Append a WHERE clause whose right-hand side is a subquery.
	 *
	 * @param string      $column   The column being compared.
	 * @param string      $operator The comparison operator.
	 * @param string      $sql      The subquery.
	 * @param list<mixed> $bindings Values bound to its placeholders.
	 * @param string      $boolean  How to combine it, AND or OR.
	 *
	 * @return void
	 */
	public function addWhereSubquery(string $column, string $operator, string $sql, array $bindings, string $boolean): void
	{
		$this->whereSubqueries[] = [
			'column' => $column,
			'operator' => $operator,
			'sql' => $sql,
			'bindings' => $bindings,
			'boolean' => $boolean,
		];
	}

	#endregion

	#region ordering, limiting and grouping

	/**
	 * Sort by a column.
	 *
	 * @param string|null $column    Quoted column name.
	 * @param string|null $direction Whitelisted direction.
	 *
	 * @return void
	 */
	public function orderBy(?string $column, ?string $direction): void
	{
		$this->order = $column;
		$this->arrange = $direction;
	}

	/**
	 * Forget every ordering instruction, raw ones included.
	 *
	 * @return void
	 */
	public function clearOrdering(): void
	{
		$this->order = null;
		$this->arrange = null;
		$this->orderByRaw = [];
	}

	/**
	 * Append a raw ORDER BY fragment. Trusted input only.
	 *
	 * @param string $expression The SQL fragment.
	 *
	 * @return void
	 */
	public function addOrderByRaw(string $expression): void
	{
		$this->orderByRaw[] = $expression;
	}

	/**
	 * Limit the number of rows.
	 *
	 * @param int|null $limit The limit, or null for none.
	 *
	 * @return void
	 */
	public function setLimit(?int $limit): void
	{
		$this->limit = $limit;
	}

	/**
	 * Skip a number of rows.
	 *
	 * @param int|null $offset The offset, or null for none.
	 *
	 * @return void
	 */
	public function setOffset(?int $offset): void
	{
		$this->offset = $offset;
	}

	/**
	 * Append a GROUP BY expression.
	 *
	 * @param string $expression Quoted column or raw fragment.
	 *
	 * @return void
	 */
	public function addGroupBy(string $expression): void
	{
		$this->groupBy[] = $expression;
	}

	/**
	 * Append a HAVING entry.
	 *
	 * @param array<string, mixed> $entry Either column/operator/value or a raw fragment.
	 *
	 * @return void
	 */
	public function addHaving(array $entry): void
	{
		$this->having[] = $entry;
	}

	#endregion

	#region selection, joins and sources

	/**
	 * Replace the selected columns.
	 *
	 * Reindexed on the way in: select() forwards whatever the caller handed it,
	 * which is not necessarily a list, and the consumer joins it positionally.
	 *
	 * @param array<array-key, string> $columns Quoted column names.
	 *
	 * @return void
	 */
	public function setSelect(array $columns): void
	{
		$this->select = array_values($columns);
	}

	/**
	 * Append one selected column or expression.
	 *
	 * @param string $expression Quoted column or raw fragment.
	 *
	 * @return void
	 */
	public function addSelect(string $expression): void
	{
		$this->select[] = $expression;
	}

	/**
	 * Make the select DISTINCT.
	 *
	 * @return void
	 */
	public function markDistinct(): void
	{
		$this->distinct = true;
	}

	/**
	 * Append a JOIN entry.
	 *
	 * @param array<string, mixed> $entry The join entry.
	 *
	 * @return void
	 */
	public function addJoin(array $entry): void
	{
		$this->joins[] = $entry;
	}

	/**
	 * Set the row-locking clause.
	 *
	 * @param string $lock The clause, from the caller's closed set.
	 *
	 * @return void
	 */
	public function setLock(string $lock): void
	{
		$this->lock = $lock;
	}

	/**
	 * Append a common table expression.
	 *
	 * @param string      $name      The CTE name.
	 * @param string      $sql       The CTE body.
	 * @param list<mixed> $bindings  Values bound to its placeholders.
	 * @param bool        $recursive Whether the CTE is recursive.
	 *
	 * @return void
	 */
	public function addCte(string $name, string $sql, array $bindings, bool $recursive): void
	{
		$this->ctes[] = ['name' => $name, 'sql' => $sql, 'bindings' => $bindings, 'recursive' => $recursive];
	}

	/**
	 * Select from a subquery instead of the table.
	 *
	 * @param string      $sql      The subquery.
	 * @param list<mixed> $bindings Values bound to its placeholders.
	 * @param string      $alias    The alias the subquery is given.
	 *
	 * @return void
	 */
	public function setFromSubquery(string $sql, array $bindings, string $alias): void
	{
		$this->fromSubquery = ['sql' => $sql, 'bindings' => $bindings, 'alias' => $alias];
	}

	/**
	 * Append a query to UNION with this one.
	 *
	 * @param string      $sql      The other query.
	 * @param list<mixed> $bindings Values bound to its placeholders.
	 * @param bool        $all      Whether this is a UNION ALL.
	 *
	 * @return void
	 */
	public function addUnion(string $sql, array $bindings, bool $all): void
	{
		$this->unions[] = ['sql' => $sql, 'bindings' => $bindings, 'all' => $all];
	}

	/**
	 * The queries to UNION with this one.
	 *
	 * @return list<array{sql: string, bindings: list<mixed>, all: bool}>
	 */
	public function unions(): array
	{
		return $this->unions;
	}

	#endregion

	#region lifecycle

	/**
	 * Forget everything accumulated so far.
	 *
	 * @return void
	 */
	public function reset(): void
	{
		$this->conditions = [];
		$this->orConditions = [];
		$this->whereRaw = [];
		$this->order = null;
		$this->arrange = null;
		$this->orderByRaw = [];
		$this->limit = null;
		$this->offset = null;
		$this->groupBy = [];
		$this->having = [];
		$this->joins = [];
		$this->select = [];
		$this->distinct = false;
		$this->lock = null;
		$this->ctes = [];
		$this->fromSubquery = null;
		$this->whereSubqueries = [];
		$this->unions = [];
	}

	/**
	 * The accumulated query as the options array modifyQueryByOptions() consumes.
	 *
	 * Only the parts that were actually set appear, which is what lets the
	 * consumer tell "no limit" from "limit 0".
	 *
	 * @return array<string, mixed>
	 */
	public function toOptions(): array
	{
		$options = [];

		if (!empty($this->conditions)) {
			$options['conditions'] = $this->conditions;
		}

		if (!empty($this->orConditions)) {
			$options['or_conditions'] = $this->orConditions;
		}

		if (!empty($this->whereRaw)) {
			$options['where_raw'] = $this->whereRaw;
		}

		if ($this->order !== null) {
			$options['order'] = $this->order;

			if ($this->arrange !== null) {
				$options['arrange'] = $this->arrange;
			}
		}

		if (!empty($this->orderByRaw)) {
			$options['order_raw'] = $this->orderByRaw;
		}

		if ($this->limit !== null) {
			$options['limit'] = $this->limit;
		}

		if ($this->offset !== null) {
			$options['offset'] = $this->offset;
		}

		if (!empty($this->groupBy)) {
			$options['group_by'] = $this->groupBy;
		}

		if (!empty($this->having)) {
			$options['having'] = $this->having;
		}

		if (!empty($this->joins)) {
			$options['joins'] = $this->joins;
		}

		if (!empty($this->select)) {
			$options['select'] = $this->select;
		}

		if ($this->distinct) {
			$options['distinct'] = true;
		}

		if ($this->lock !== null) {
			$options['lock'] = $this->lock;
		}

		if (!empty($this->ctes)) {
			$options['ctes'] = $this->ctes;
		}

		if ($this->fromSubquery !== null) {
			$options['from_subquery'] = $this->fromSubquery;
		}

		if (!empty($this->whereSubqueries)) {
			$options['where_subqueries'] = $this->whereSubqueries;
		}

		return $options;
	}

	#endregion
}
