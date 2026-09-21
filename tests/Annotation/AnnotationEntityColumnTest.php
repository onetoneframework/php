<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Annotation\Entity\Column;
use Clover\Annotation\Entity\ID;
use PHPUnit\Framework\TestCase;

class AnnotationEntityColumnTest extends TestCase
{
    public function testColumnConstructor(): void
    {
        $column = new Column('username', 'varchar(255)', false);
        $this->assertSame('username', $column->name);
        $this->assertSame('varchar(255)', $column->type);
        $this->assertFalse($column->nullable);
    }

    public function testColumnConstructorNullableDefault(): void
    {
        $column = new Column('email', 'string');
        $this->assertSame('email', $column->name);
        $this->assertSame('string', $column->type);
        $this->assertTrue($column->nullable);
    }

    public function testIdConstructor(): void
    {
        $id = new ID();
        $this->assertInstanceOf(ID::class, $id);
    }
}
