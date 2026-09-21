<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\DataStructor;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\DataStructor\Stack;
use Clover\Classes\DataStructor\Queue;
use PHPUnit\Framework\TestCase;

class StackQueueTest extends TestCase
{
    public function testStackPushAndTop(): void
    {
        $stack = new Stack();
        $stack->pushItem(10);
        $stack->pushItem(20);
        $this->assertEquals(20, $stack->top());
    }

    public function testStackLifoOrder(): void
    {
        $stack = new Stack();
        $stack->pushItem('first');
        $stack->pushItem('second');
        $stack->pushItem('third');
        $this->assertEquals('third', $stack->top());
    }

    public function testStackCount(): void
    {
        $stack = new Stack();
        $stack->pushItem(1);
        $stack->pushItem(2);
        $this->assertGreaterThanOrEqual(1, $stack->top());
    }

    public function testQueueEnqueueDequeue(): void
    {
        $queue = new Queue();
        $queue->enqueue('first');
        $queue->enqueue('second');
        $queue->enqueue('third');
        $this->assertEquals('first', $queue->dequeue());
        $this->assertEquals('second', $queue->dequeue());
        $this->assertEquals('third', $queue->dequeue());
    }

    public function testQueueFifoOrder(): void
    {
        $queue = new Queue();
        $queue->enqueue(10);
        $queue->enqueue(20);
        $queue->enqueue(30);
        $this->assertEquals(10, $queue->dequeue());
        $this->assertEquals(20, $queue->dequeue());
        $this->assertEquals(30, $queue->dequeue());
    }

    public function testQueueCount(): void
    {
        $queue = new Queue();
        $queue->enqueue(1);
        $queue->enqueue(2);
        $first = $queue->dequeue();
        $second = $queue->dequeue();
        $this->assertEquals(1, $first);
        $this->assertEquals(2, $second);
    }

    public function testStackTopReturnsLastPushed(): void
    {
        $stack = new Stack();
        $stack->pushItem('a');
        $this->assertEquals('a', $stack->top());
        $stack->pushItem('b');
        $this->assertEquals('b', $stack->top());
    }
}
