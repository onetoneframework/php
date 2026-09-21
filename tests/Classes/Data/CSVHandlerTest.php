<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\CSVHandler;
use Clover\Classes\Data\StringObject;
use PHPUnit\Framework\TestCase;

final class CSVHandlerTest extends TestCase
{
    public function testDecodeAndEncodeRoundTrip(): void
    {
        $csv = "id,name\n1,Alice\n2,Bob";
        $decoded = CSVHandler::decode($csv)->getRawData();

        $this->assertSame('id', $decoded[0][0]);
        $this->assertSame('Alice', $decoded[1][1]);

        $encoded = CSVHandler::encode($decoded);
        $this->assertIsString($encoded);
        $this->assertStringContainsString('id,name', $encoded);
        $this->assertStringContainsString('2,Bob', $encoded);
    }

    public function testDecodeAcceptsStringObject(): void
    {
        $csv = new StringObject("k,v\na,b");
        $decoded = CSVHandler::decode($csv)->getRawData();

        $this->assertSame('k', $decoded[0][0]);
        $this->assertSame('b', $decoded[1][1]);
    }

    public function testIsCsvDetectsValidAndInvalidFormats(): void
    {
        $valid = "id,name\n1,Alice\n2,Bob";
        $invalidColumnCount = "id,name\n1\n2,Bob";
        $invalidSingleColumn = "only\none";

        $this->assertTrue(CSVHandler::isCsv($valid));
        $this->assertFalse(CSVHandler::isCsv($invalidColumnCount));
        $this->assertFalse(CSVHandler::isCsv($invalidSingleColumn));
    }
}
