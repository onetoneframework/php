<?php

declare(strict_types=1);

namespace Clover\Tests\Trait\Json;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Trait\Json\JSONError;
use PHPUnit\Framework\TestCase;

final class JsonErrorTraitTestStub
{
    use JSONError;
}

class JsonErrorTraitTest extends TestCase
{
    public function testGetLastErrorAfterValidDecode(): void
    {
        json_decode('{}');
        $stub = new JsonErrorTraitTestStub();
        $this->assertSame(JSON_ERROR_NONE, $stub->getLastError());
    }

    public function testHasErrorAfterValidDecode(): void
    {
        json_decode('{"a":1}');
        $stub = new JsonErrorTraitTestStub();
        $this->assertFalse($stub->hasError());
    }

    public function testGetLastErrorMessageReturnsString(): void
    {
        $stub = new JsonErrorTraitTestStub();
        $this->assertIsString($stub->getLastErrorMessage());
    }

    public function testGetMessageReturnsString(): void
    {
        json_decode('{}');
        $stub = new JsonErrorTraitTestStub();
        $this->assertIsString($stub->getMessage());
    }

    public function testHasSyntaxError(): void
    {
        json_decode('{ invalid }');
        $stub = new JsonErrorTraitTestStub();
        $this->assertSame(JSON_ERROR_SYNTAX === $stub->getLastError(), $stub->hasSyntaxError());
    }
}
