<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database;

use Clover\Classes\Database\ActiveRecordQuery;
use PHPUnit\Framework\TestCase;

/**
 * Characterisation tests for the eighteen query fields ActiveRecord used to
 * carry itself.
 *
 * The extraction moved the fields and rewrote buildQueryOptions() into a
 * delegation, so what has to be pinned is the mapping: which builder call lands
 * under which option key, which keys are omitted when nothing was set, and what
 * reset() forgets. modifyQueryByOptions() reads this array by key, so a field
 * that silently changed key would produce a quietly different query rather than
 * an error.
 */
final class ActiveRecordQueryTest extends TestCase
{
	private ActiveRecordQuery $query;

	protected function setUp(): void
	{
		parent::setUp();
		$this->query = new ActiveRecordQuery();
	}

	public function testAFreshQueryProducesNoOptions(): void
	{
		$this->assertSame([], $this->query->toOptions());
		$this->assertFalse($this->query->hasConditions());
	}

	#region conditions

	public function testConditionsLandUnderConditions(): void
	{
		$this->query->addCondition(['column' => 'id', 'operator' => '=', 'value' => 7]);

		$this->assertTrue($this->query->hasConditions());
		$this->assertSame(
			[['column' => 'id', 'operator' => '=', 'value' => 7]],
			$this->query->toOptions()['conditions']
		);
	}

	public function testConditionsKeepTheirOrder(): void
	{
		$this->query->addCondition(['column' => 'a']);
		$this->query->addCondition(['column' => 'b']);

		$this->assertSame([['column' => 'a'], ['column' => 'b']], $this->query->conditions());
	}

	public function testTheConditionListCanBeTakenAsideAndPutBack(): void
	{
		$this->query->addCondition(['column' => 'outer']);
		$outer = $this->query->conditions();

		$this->query->setConditions([]);
		$this->query->addCondition(['column' => 'inner']);
		$group = $this->query->conditions();

		$this->query->setConditions($outer);

		$this->assertSame([['column' => 'inner']], $group);
		$this->assertSame([['column' => 'outer']], $this->query->conditions());
	}

	public function testTheLastConditionCanBeFlippedToOr(): void
	{
		$this->query->addCondition(['column' => 'a', 'boolean' => 'AND']);
		$this->query->addCondition(['column' => 'b', 'boolean' => 'AND']);
		$this->query->markLastConditionAsOr();

		$conditions = $this->query->conditions();

		$this->assertSame('AND', $conditions[0]['boolean']);
		$this->assertSame('OR', $conditions[1]['boolean']);
	}

	public function testFlippingTheLastConditionOfAnEmptyListIsHarmless(): void
	{
		$this->query->markLastConditionAsOr();

		$this->assertSame([], $this->query->conditions());
	}

	public function testRawWhereFragmentsLandUnderWhereRaw(): void
	{
		$this->query->addWhereRaw('price > ?', [100], 'AND');

		$this->assertSame(
			[['expression' => 'price > ?', 'bindings' => [100], 'boolean' => 'AND']],
			$this->query->toOptions()['where_raw']
		);
	}

	public function testSubqueryConditionsLandUnderWhereSubqueries(): void
	{
		$this->query->addWhereSubquery('id', 'IN', 'SELECT id FROM t', [1], 'AND');

		$this->assertSame(
			[[
				'column' => 'id',
				'operator' => 'IN',
				'sql' => 'SELECT id FROM t',
				'bindings' => [1],
				'boolean' => 'AND',
			]],
			$this->query->toOptions()['where_subqueries']
		);
	}

	#endregion

	#region ordering, limiting and grouping

	public function testOrderingLandsUnderOrderAndArrange(): void
	{
		$this->query->orderBy('`created_at`', 'DESC');

		$options = $this->query->toOptions();

		$this->assertSame('`created_at`', $options['order']);
		$this->assertSame('DESC', $options['arrange']);
	}

	public function testAnOrderWithoutADirectionOmitsArrange(): void
	{
		$this->query->orderBy('`created_at`', null);

		$options = $this->query->toOptions();

		$this->assertSame('`created_at`', $options['order']);
		$this->assertArrayNotHasKey('arrange', $options);
	}

	public function testClearingOrderingForgetsTheRawFragmentsToo(): void
	{
		$this->query->orderBy('`a`', 'ASC');
		$this->query->addOrderByRaw('FIELD(id, 1, 2)');

		$this->query->clearOrdering();
		$options = $this->query->toOptions();

		$this->assertArrayNotHasKey('order', $options);
		$this->assertArrayNotHasKey('arrange', $options);
		$this->assertArrayNotHasKey('order_raw', $options);
	}

	public function testRawOrderingLandsUnderOrderRaw(): void
	{
		$this->query->addOrderByRaw('RAND()');

		$this->assertSame(['RAND()'], $this->query->toOptions()['order_raw']);
	}

	public function testLimitAndOffsetLandUnderTheirOwnKeys(): void
	{
		$this->query->setLimit(10);
		$this->query->setOffset(20);

		$options = $this->query->toOptions();

		$this->assertSame(10, $options['limit']);
		$this->assertSame(20, $options['offset']);
	}

	public function testAZeroLimitIsStillReported(): void
	{
		$this->query->setLimit(0);

		$this->assertArrayHasKey('limit', $this->query->toOptions());
		$this->assertSame(0, $this->query->toOptions()['limit']);
	}

	public function testAnUnsetLimitIsOmittedRatherThanZero(): void
	{
		$this->assertArrayNotHasKey('limit', $this->query->toOptions());
		$this->assertArrayNotHasKey('offset', $this->query->toOptions());
	}

	public function testGroupingLandsUnderGroupBy(): void
	{
		$this->query->addGroupBy('`role`');
		$this->query->addGroupBy('`team`');

		$this->assertSame(['`role`', '`team`'], $this->query->toOptions()['group_by']);
	}

	public function testHavingLandsUnderHaving(): void
	{
		$this->query->addHaving(['column' => '`total`', 'operator' => '>', 'value' => 5]);
		$this->query->addHaving(['raw' => 'COUNT(*) > ?', 'bindings' => [3]]);

		$this->assertSame(
			[
				['column' => '`total`', 'operator' => '>', 'value' => 5],
				['raw' => 'COUNT(*) > ?', 'bindings' => [3]],
			],
			$this->query->toOptions()['having']
		);
	}

	#endregion

	#region selection, joins and sources

	public function testSelectReplacesWhileAddSelectAppends(): void
	{
		$this->query->setSelect(['`a`', '`b`']);
		$this->query->addSelect('COUNT(*) AS `n`');

		$this->assertSame(['`a`', '`b`', 'COUNT(*) AS `n`'], $this->query->toOptions()['select']);
	}

	public function testSelectReindexesTheColumnsItIsGiven(): void
	{
		$this->query->setSelect([3 => '`a`', 7 => '`b`']);

		$this->assertSame(['`a`', '`b`'], $this->query->toOptions()['select']);
	}

	public function testDistinctLandsUnderDistinct(): void
	{
		$this->assertArrayNotHasKey('distinct', $this->query->toOptions());

		$this->query->markDistinct();

		$this->assertTrue($this->query->toOptions()['distinct']);
	}

	public function testJoinsLandUnderJoins(): void
	{
		$entry = ['type' => 'INNER', 'table' => '`t`', 'first' => '`a`.`id`', 'operator' => '=', 'second' => '`t`.`a_id`'];
		$this->query->addJoin($entry);

		$this->assertSame([$entry], $this->query->toOptions()['joins']);
	}

	public function testTheLockClauseLandsUnderLock(): void
	{
		$this->query->setLock('FOR UPDATE');

		$this->assertSame('FOR UPDATE', $this->query->toOptions()['lock']);
	}

	public function testTheLastLockClauseWins(): void
	{
		$this->query->setLock('FOR UPDATE');
		$this->query->setLock('FOR UPDATE NOWAIT');

		$this->assertSame('FOR UPDATE NOWAIT', $this->query->toOptions()['lock']);
	}

	public function testCommonTableExpressionsLandUnderCtes(): void
	{
		$this->query->addCte('recent', 'SELECT * FROM t', [1], true);

		$this->assertSame(
			[['name' => 'recent', 'sql' => 'SELECT * FROM t', 'bindings' => [1], 'recursive' => true]],
			$this->query->toOptions()['ctes']
		);
	}

	public function testASubquerySourceLandsUnderFromSubquery(): void
	{
		$this->query->setFromSubquery('SELECT * FROM t', [2], 'sub');

		$this->assertSame(
			['sql' => 'SELECT * FROM t', 'bindings' => [2], 'alias' => 'sub'],
			$this->query->toOptions()['from_subquery']
		);
	}

	/**
	 * union() and unionAll() collect their queries, but buildQueryOptions() has
	 * never exported them - modifyQueryByOptions() has no 'unions' key to read.
	 * Recorded rather than endorsed: the extraction kept the omission exactly as
	 * it was rather than quietly starting to emit a key nothing consumes.
	 */
	public function testUnionsAreCollectedButNotExported(): void
	{
		$this->query->addUnion('SELECT 1', [], false);

		$this->assertSame([['sql' => 'SELECT 1', 'bindings' => [], 'all' => false]], $this->query->unions());
		$this->assertArrayNotHasKey('unions', $this->query->toOptions());
	}

	#endregion

	#region reset

	public function testResetForgetsEverything(): void
	{
		$this->query->addCondition(['column' => 'a']);
		$this->query->addWhereRaw('x = ?', [1], 'AND');
		$this->query->addWhereSubquery('id', 'IN', 'SELECT 1', [], 'AND');
		$this->query->orderBy('`a`', 'ASC');
		$this->query->addOrderByRaw('RAND()');
		$this->query->setLimit(5);
		$this->query->setOffset(10);
		$this->query->addGroupBy('`g`');
		$this->query->addHaving(['raw' => '1=1', 'bindings' => []]);
		$this->query->addJoin(['type' => 'INNER']);
		$this->query->setSelect(['`a`']);
		$this->query->markDistinct();
		$this->query->setLock('FOR UPDATE');
		$this->query->addCte('c', 'SELECT 1', [], false);
		$this->query->setFromSubquery('SELECT 1', [], 'sub');
		$this->query->addUnion('SELECT 1', [], true);

		$this->assertNotSame([], $this->query->toOptions());

		$this->query->reset();

		$this->assertSame([], $this->query->toOptions(), 'Every option key is gone.');
		$this->assertSame([], $this->query->conditions());
		$this->assertSame([], $this->query->unions(), 'Unions are reset even though they are never exported.');
		$this->assertFalse($this->query->hasConditions());
	}

	public function testResetIsIdempotent(): void
	{
		$this->query->reset();
		$this->query->reset();

		$this->assertSame([], $this->query->toOptions());
	}

	#endregion
}
