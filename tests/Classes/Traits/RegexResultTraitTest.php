<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Traits;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Traits\Regex\Result;
use PHPUnit\Framework\TestCase;

final class RegexResultTraitTestStub
{
    use Result;
}

class RegexResultTraitTest extends TestCase
{
    public function testClassUsesResultTrait(): void
    {
        $this->assertContains(Result::class, class_uses(RegexResultTraitTestStub::class));
    }

    public function testInstanceCanBeCreated(): void
    {
        $stub = new RegexResultTraitTestStub();
        $this->assertInstanceOf(RegexResultTraitTestStub::class, $stub);
    }
}
