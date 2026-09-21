<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Annotation\NotFound;
use PHPUnit\Framework\TestCase;

class AnnotationNotFoundTest extends TestCase
{
    public function testNotFoundConstructor(): void
    {
        $notFound = new NotFound('CustomNotFoundHandler');
        $this->assertSame('CustomNotFoundHandler', $notFound->value);
    }
}
