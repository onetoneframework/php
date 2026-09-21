<?php

declare(strict_types=1);

namespace Clover\Tests;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;

class DefaultsLanguageTest extends TestCase
{
    public function testLanguageDefaultsIsArray(): void
    {
        $data = require __DIR__ . '/../src/Defaults/Language.php';
        $this->assertIsArray($data);
    }

    public function testLanguageContainsKoreanLead(): void
    {
        $data = require __DIR__ . '/../src/Defaults/Language.php';
        $this->assertArrayHasKey('KOREAN', $data);
        $this->assertArrayHasKey('LEAD', $data['KOREAN']);
        $this->assertIsArray($data['KOREAN']['LEAD']);
    }

    public function testLanguageContainsRomanNumber(): void
    {
        $data = require __DIR__ . '/../src/Defaults/Language.php';
        $this->assertArrayHasKey('ROMAN', $data);
        $this->assertArrayHasKey('NUMBER', $data['ROMAN']);
        $this->assertSame(1, $data['ROMAN']['NUMBER']['Ⅰ']);
    }
}
