<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Annotation\Query;
use PHPUnit\Framework\TestCase;

class AnnotationQueryTest extends TestCase
{
    public function testQueryConstructor(): void
    {
        $query = new Query('page');
        $this->assertSame('page', $query->value);
    }
}
