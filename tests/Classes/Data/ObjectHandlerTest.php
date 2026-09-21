<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data;

use Clover\Classes\Data\ObjectHandler;
use PHPUnit\Framework\TestCase;

final class ObjectHandlerTest extends TestCase
{
    public function testObjectToArrayConvertsNestedObjectsRecursively(): void
    {
        $input = (object) [
            'name' => 'root',
            'child' => (object) ['value' => 10]
        ];

        $result = ObjectHandler::objectToArray($input);

        $this->assertIsArray($result);
        $this->assertSame('root', $result['name']);
        $this->assertIsArray($result['child']);
        $this->assertSame(10, $result['child']['value']);
    }

    public function testArrayToObjectUnmanglesPropertyNames(): void
    {
        $mangled = [
            "\0Foo\0privateName" => 'a',
            "\0*\0protectedName" => 'b',
            'publicName' => 'c',
        ];

        $result = ObjectHandler::arrayToObject($mangled);

        $this->assertSame(
            ['privateName' => 'a', 'protectedName' => 'b', 'publicName' => 'c'],
            $result
        );
    }
}
