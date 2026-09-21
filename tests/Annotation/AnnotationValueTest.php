<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Annotation\Value;
use PHPUnit\Framework\TestCase;

class AnnotationValueTest extends TestCase
{
    public function testValueConstructor(): void
    {
        $value = new Value('config.key');
        $this->assertSame('config.key', $value->value);
    }
}
