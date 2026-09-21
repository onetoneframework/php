<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Data\StringObject;
use PHPUnit\Framework\TestCase;

class JSONHandlerTest extends TestCase
{
    public function testDecode(): void
    {
        $json = '{"a":1,"b":2}';
        $result = JSONHandler::decodeToArray($json);
        $this->assertInstanceOf(ArrayObject::class, $result);
        $data = $result->getRawData();
        $this->assertEquals(1, $data['a']);
        $this->assertEquals(2, $data['b']);
    }

    public function testDecodeToArray(): void
    {
        $json = '[1,2,3]';
        $result = JSONHandler::decodeToArray($json);
        $this->assertInstanceOf(ArrayObject::class, $result);
        $data = $result->getRawData();
        $this->assertIsArray($data);
    }

    public function testEncode(): void
    {
        $data = ['name' => 'test', 'value' => 42];
        $encoded = JSONHandler::encode($data);
        $this->assertIsString($encoded);
        $decoded = json_decode($encoded, true);
        $this->assertEquals('test', $decoded['name']);
        $this->assertEquals(42, $decoded['value']);
    }

	public function testEncodeWithArrayObject(): void
	{
        $data = new ArrayObject(['x' => 1]);
        $encoded = JSONHandler::encode($data);
        $this->assertIsString($encoded);
		$this->assertStringContainsString('"x"', $encoded);
	}

	public function testEncodeRecoversAfterPreviousFailure(): void
	{
		$recursive = [];
		$recursive['self'] = &$recursive;

		$this->assertFalse(JSONHandler::encode($recursive, JSON_UNESCAPED_UNICODE));
		$this->assertSame('{"healthy":true}', JSONHandler::encode(['healthy' => true], JSON_UNESCAPED_UNICODE));
	}

    public function testDecodeWithStringObject(): void
    {
        $json = new StringObject('{"key":"value"}');
        $result = JSONHandler::decodeToArray($json);
        $this->assertInstanceOf(ArrayObject::class, $result);
        $data = $result->getRawData();
        $this->assertEquals('value', $data['key']);
    }
}
