<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\HTTP;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;

class HttpResponseStatusCodesTest extends TestCase
{
    public function testDefaultsContainCommonStatusCodes(): void
    {
        $codes = require __DIR__ . '/../../../src/Defaults/HttpResponseStatusCodes.php';
        $this->assertIsArray($codes);

        $this->assertArrayHasKey(200, $codes);
        $this->assertSame('OK', $codes[200]);

        $this->assertArrayHasKey(404, $codes);
        $this->assertSame('Not Found', $codes[404]);

        $this->assertArrayHasKey(500, $codes);
        $this->assertSame('Internal Server Error', $codes[500]);
    }

    public function testStatusCodesValuesAreStrings(): void
    {
        $codes = require __DIR__ . '/../../../src/Defaults/HttpResponseStatusCodes.php';
        foreach ($codes as $code => $message) {
            $this->assertIsInt($code);
            $this->assertIsString($message);
        }
    }
}
