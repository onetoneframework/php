<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Annotation\Middleware;
use PHPUnit\Framework\TestCase;

class AnnotationMiddlewareTest extends TestCase
{
    public function testMiddlewareConstructor(): void
    {
        $middleware = new Middleware('AuthMiddleware');
        $this->assertSame('AuthMiddleware', $middleware->value);
    }
}
