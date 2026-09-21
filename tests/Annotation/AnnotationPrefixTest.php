<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Annotation\Prefix;
use PHPUnit\Framework\TestCase;

class AnnotationPrefixTest extends TestCase
{
    public function testPrefixConstructor(): void
    {
        $prefix = new Prefix('/api/v1');
        $this->assertSame('/api/v1', $prefix->value);
    }
}
