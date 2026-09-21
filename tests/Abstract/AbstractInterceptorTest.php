<?php

declare(strict_types=1);

namespace Clover\Tests\Abstract;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Abstract\Interceptor;
use PHPUnit\Framework\TestCase;

/**
 * Concrete Interceptor for testing.
 */
final class InterceptorTestStub extends Interceptor
{
    public array $preCalls = [];
    public array $postCalls = [];

    public function preHandle(object &$handler, string &$method, array &$args): void
    {
        $this->preCalls[] = ['handler' => get_class($handler), 'method' => $method, 'args' => $args];
    }

    public function postHandle(object &$handler, string &$method, array &$args, mixed &$result): void
    {
        $this->postCalls[] = ['handler' => get_class($handler), 'method' => $method, 'result' => $result];
    }
}

class AbstractInterceptorTest extends TestCase
{
    public function testPreHandleAndPostHandleAreInvoked(): void
    {
        $interceptor = new InterceptorTestStub();
        $handler = new \stdClass();
        $method = 'testMethod';
        $args = [1, 2];
        $result = 'result';

        $interceptor->preHandle($handler, $method, $args);
        $this->assertCount(1, $interceptor->preCalls);
        $this->assertSame('stdClass', $interceptor->preCalls[0]['handler']);
        $this->assertSame('testMethod', $interceptor->preCalls[0]['method']);
        $this->assertSame([1, 2], $interceptor->preCalls[0]['args']);

        $interceptor->postHandle($handler, $method, $args, $result);
        $this->assertCount(1, $interceptor->postCalls);
        $this->assertSame('result', $interceptor->postCalls[0]['result']);
    }

    public function testPreHandleCanReceiveArgumentsByReference(): void
    {
        $interceptor = new InterceptorTestStub();
        $handler = new \stdClass();
        $method = 'm';
        $args = ['a'];
        $interceptor->preHandle($handler, $method, $args);
        $this->assertSame(['a'], $args);
    }
}
