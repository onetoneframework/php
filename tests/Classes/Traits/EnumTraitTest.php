<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Traits;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Trait\EnumTrait;
use PHPUnit\Framework\TestCase;

final class EnumTraitTestStub
{
    use EnumTrait;
}

class EnumTraitTest extends TestCase
{
    public function testClassUsesEnumTrait(): void
    {
        $this->assertContains(EnumTrait::class, class_uses(EnumTraitTestStub::class));
    }

    public function testInstanceCanBeCreated(): void
    {
        $stub = new EnumTraitTestStub();
        $this->assertInstanceOf(EnumTraitTestStub::class, $stub);
    }
}
