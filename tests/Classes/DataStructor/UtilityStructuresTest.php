<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\DataStructor;

use Clover\Classes\DataStructor\KnowledgeBase;
use Clover\Classes\DataStructor\LinkedList;
use Clover\Classes\DataStructor\Queue;
use Clover\Classes\DataStructor\SimpleStack;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UtilityStructuresTest extends TestCase
{
	public function testLinkedListExposesStableIndexNavigation(): void
	{
		$list = new LinkedList();

		$this->assertFalse($list->first());
		$this->assertFalse($list->end());

		$list->add('alpha');
		$list->add('beta');

		$this->assertSame(0, $list->first());
		$this->assertSame(1, $list->next(0));
		$this->assertFalse($list->next(1));
		$this->assertSame(1, $list->end());
		$this->assertSame('alpha', $list->get(0));
		$this->assertSame('beta', $list->item(1));
		$this->assertNull($list->get(99));
	}

	public function testSimpleStackIsLifoAndReturnsNullWhenEmpty(): void
	{
		$stack = new SimpleStack();
		$stack->push('first');
		$stack->push('second');

		$this->assertSame('second', $stack->pop());
		$this->assertSame('first', $stack->pop());
		$this->assertNull($stack->pop());
	}

	public function testKnowledgeBaseUsesCaseInsensitiveLabelsAndKeepsKnownReference(): void
	{
		$knowledge = new KnowledgeBase(1, 'item');
		$value = 'initial';
		$knowledge->know($value, 'Status');

		$this->assertTrue($knowledge->exists('status'));
		$this->assertSame('initial', $knowledge->getValue('STATUS'));

		$value = 'changed';
		$this->assertSame('changed', $knowledge->getValue('status'));

		$knowledge->put('updated', 'status');
		$this->assertSame('updated', $knowledge->getValue('Status'));
		$this->assertSame('updated', $value);
		$this->assertNull($knowledge->getValue('missing'));
	}

	public function testQueueDequeueFromEmptyQueueThrowsRuntimeException(): void
	{
		$queue = new Queue();

		$this->expectException(RuntimeException::class);
		$queue->dequeue();
	}
}
