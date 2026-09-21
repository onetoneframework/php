<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Pagination;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Pagination\Dynamic;
use Clover\Classes\Pagination\Statics;

class PaginationTest extends TestCase
{
	private array $queryParameters = [];

	protected function setUp(): void
	{
		$this->queryParameters = $_GET;
	}

	protected function tearDown(): void
	{
		$_GET = $this->queryParameters;
	}

	public function testDynamic(): void
	{
        $dynamic = new Dynamic(1, 10, 157, 5);
        $this->assertEquals(true, $dynamic->hasNextPage());
		$this->assertEquals(16, $dynamic->getLastPage());
		$this->assertEquals(10, $dynamic->getItemCount());
        $dynamic->setCurrentPage($dynamic->getLastPage());
        $this->assertEquals(false, $dynamic->hasNextPage());
        $dynamic->setCurrentPage(12);
	}
	
    public function testStatic(): void
	{
        $static = new Statics(1, 157, 10, 5);
        $this->assertEquals(true, $static->hasNextPage());
		$this->assertEquals(16, $static->getLastPage());
        $static->setCurrentPage($static->getLastPage());
        $this->assertEquals(false, $static->hasNextPage());
        $static->setCurrentPage(12);
			$this->assertEquals(1, $static->getPageStart());
		}

	public function testDynamicNavigationAndSerialization(): void
	{
		$dynamic = new Dynamic(3, 10, 157, 5);

		$this->assertSame(2, $dynamic->getPreviousPage());
		$this->assertSame(4, $dynamic->getNextPage());
		$this->assertTrue($dynamic->hasPreviousPage());
		$this->assertTrue($dynamic->canGoNext());
		$this->assertTrue($dynamic->isActivePage(3));
		$this->assertSame(20, $dynamic->getOffset());
		$this->assertSame(10, $dynamic->getLimit());
		$this->assertSame(157, $dynamic->getDocumentCount());
		$this->assertSame(5, $dynamic->getListCount());
		$this->assertSame([
			'current_page' => 3,
			'per_page' => 10,
			'total_items' => 157,
			'total_pages' => 16,
			'has_previous' => true,
			'has_next' => true,
			'offset' => 20,
			'limit' => 10,
		], $dynamic->toArray());
	}

	public function testDynamicFromRequestUsesCustomPageParameter(): void
	{
		$_GET = ['cursor' => '4'];

		$dynamic = Dynamic::fromRequest(95, 10, 7, 'cursor');

		$this->assertSame(4, $dynamic->getActivePage());
		$this->assertSame(30, $dynamic->getOffset());
		$this->assertSame(10, $dynamic->getLastPage());
	}

	public function testStaticNavigationAndSerializationForNonExactDocumentCount(): void
	{
		$static = new Statics(6, 157, 10, 5);

		$this->assertTrue($static->needFirstPage());
		$this->assertTrue($static->needLastPage());
		$this->assertSame(5, $static->getPreviousPage());
		$this->assertSame(7, $static->getNextPage());
		$this->assertTrue($static->hasPreviousPage());
		$this->assertTrue($static->canGoNext());
		$this->assertTrue($static->isCurrentPage(6));
		$this->assertSame(50, $static->getOffset());
		$this->assertSame(10, $static->getLimit());
		$this->assertSame(157, $static->getDocumentCount());
		$this->assertSame(10, $static->getPageCount());
		$this->assertSame(5, $static->getListCount());
		$this->assertSame(5, $static->getFirstPage());
		$this->assertSame([
			'current_page' => 6,
			'per_page' => 10,
			'total_items' => 157,
			'total_pages' => 16,
			'first_page' => 5,
			'has_previous' => true,
			'has_next' => true,
			'offset' => 50,
			'limit' => 10,
		], $static->toArray());
	}
}
