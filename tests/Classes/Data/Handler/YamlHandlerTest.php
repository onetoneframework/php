<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Data\Handler;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\YamlHandler;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for YamlHandler.
 *
 * Each test is self-contained: YAML is supplied as an array of lines
 * directly to the constructor.
 */
class YamlHandlerTest extends TestCase
{
    // ------------------------------------------------------------------ //
    //  Helper
    // ------------------------------------------------------------------ //

    /**
     * Convert a heredoc / multi-line string into the line array that
     * YamlHandler expects.
     */
    private function lines(string $yaml): array
    {
        return explode("\n", $yaml);
    }

    private function parse(string $yaml): array
    {
        return (new YamlHandler($this->lines($yaml)))->getData();
    }

    // ================================================================== //
    //  1. Scalar types
    // ================================================================== //

    public function testStringValue(): void
    {
        $data = $this->parse("name: Alice");
        $this->assertSame('Alice', $data['name']);
    }

    public function testIntegerValue(): void
    {
        $data = $this->parse("count: 42");
        $this->assertSame(42, $data['count']);
        $this->assertIsInt($data['count']);
    }

    public function testNegativeIntegerValue(): void
    {
        $data = $this->parse("temp: -7");
        $this->assertSame(-7, $data['temp']);
    }

    public function testFloatValue(): void
    {
        $data = $this->parse("ratio: 3.14");
        $this->assertEqualsWithDelta(3.14, $data['ratio'], 1e-9);
        $this->assertIsFloat($data['ratio']);
    }

    public function testScientificNotationFloat(): void
    {
        $data = $this->parse("speed: 1.5e3");
        $this->assertEqualsWithDelta(1500.0, $data['speed'], 1e-6);
        $this->assertIsFloat($data['speed']);
    }

    public function testBooleanTrue(): void
    {
        foreach (['true', 'True', 'TRUE', 'yes', 'Yes', 'on', 'On'] as $val) {
            $data = $this->parse("flag: $val");
            $this->assertTrue($data['flag'], "Expected true for '$val'");
        }
    }

    public function testBooleanFalse(): void
    {
        foreach (['false', 'False', 'FALSE', 'no', 'No', 'off', 'Off'] as $val) {
            $data = $this->parse("flag: $val");
            $this->assertFalse($data['flag'], "Expected false for '$val'");
        }
    }

    public function testNullKeyword(): void
    {
        $data = $this->parse("ptr: null");
        $this->assertNull($data['ptr']);
    }

    public function testNullTilde(): void
    {
        $data = $this->parse("ptr: ~");
        $this->assertNull($data['ptr']);
    }

    public function testFloatInfinity(): void
    {
        $data = $this->parse("big: .inf");
        $this->assertInfinite($data['big']);
        $this->assertGreaterThan(0, $data['big']);
    }

    public function testFloatNegativeInfinity(): void
    {
        $data = $this->parse("big: -.inf");
        $this->assertInfinite($data['big']);
        $this->assertLessThan(0, $data['big']);
    }

    public function testFloatNaN(): void
    {
        $data = $this->parse("x: .nan");
        $this->assertNan($data['x']);
    }

    // ================================================================== //
    //  2. Quoted strings
    // ================================================================== //

    public function testSingleQuotedString(): void
    {
        $data = $this->parse("msg: 'hello world'");
        $this->assertSame('hello world', $data['msg']);
    }

    public function testSingleQuotedEscapedApostrophe(): void
    {
        $data = $this->parse("msg: 'it''s fine'");
        $this->assertSame("it's fine", $data['msg']);
    }

    public function testDoubleQuotedString(): void
    {
        $data = $this->parse('msg: "hello world"');
        $this->assertSame('hello world', $data['msg']);
    }

    public function testDoubleQuotedEscapeNewline(): void
    {
        $data = $this->parse('msg: "line1\nline2"');
        $this->assertSame("line1\nline2", $data['msg']);
    }

    public function testDoubleQuotedEscapeTab(): void
    {
        $data = $this->parse('msg: "col1\tcol2"');
        $this->assertSame("col1\tcol2", $data['msg']);
    }

    public function testDoubleQuotedUnicodeEscape(): void
    {
        $data = $this->parse('symbol: "\u0041"');   // U+0041 = A
        $this->assertSame('A', $data['symbol']);
    }

    public function testSingleQuotedPreservesTrue(): void
    {
        // Quoted 'true' must remain the string "true", not bool true
        $data = $this->parse("flag: 'true'");
        $this->assertSame('true', $data['flag']);
    }

    public function testDoubleQuotedPreservesInteger(): void
    {
        $data = $this->parse('num: "42"');
        $this->assertSame('42', $data['num']);
    }

    // ================================================================== //
    //  3. Comments
    // ================================================================== //

    public function testFullLineCommentIsIgnored(): void
    {
        $yaml = <<<'YAML'
# This is a comment
name: Bob
YAML;
        $data = $this->parse($yaml);
        $this->assertSame('Bob', $data['name']);
        $this->assertArrayNotHasKey('#', $data);
    }

    public function testInlineCommentIsStripped(): void
    {
        $data = $this->parse("port: 8080 # default HTTP");
        $this->assertSame(8080, $data['port']);
    }

    public function testHashInsideQuotedStringIsPreserved(): void
    {
        $data = $this->parse("color: '#FF0000'");
        $this->assertSame('#FF0000', $data['color']);
    }

    // ================================================================== //
    //  4. Nested mappings
    // ================================================================== //

    public function testNestedMapping(): void
    {
        $yaml = <<<'YAML'
server:
  host: localhost
  port: 3306
YAML;
        $data = $this->parse($yaml);

        $this->assertIsArray($data['server']);
        $this->assertSame('localhost', $data['server']['host']);
        $this->assertSame(3306, $data['server']['port']);
    }

    public function testDeeplyNestedMapping(): void
    {
        $yaml = <<<'YAML'
a:
  b:
    c: deep
YAML;
        $data = $this->parse($yaml);
        $this->assertSame('deep', $data['a']['b']['c']);
    }

    public function testSiblingKeysAfterNested(): void
    {
        $yaml = <<<'YAML'
db:
  host: 127.0.0.1
env: production
YAML;
        $data = $this->parse($yaml);
        $this->assertSame('127.0.0.1', $data['db']['host']);
        $this->assertSame('production', $data['env']);
    }

    // ================================================================== //
    //  5. Sequences
    // ================================================================== //

    public function testSimpleSequence(): void
    {
        $yaml = <<<'YAML'
fruits:
  - apple
  - banana
  - cherry
YAML;
        $data = $this->parse($yaml);
        $this->assertSame(['apple', 'banana', 'cherry'], $data['fruits']);
    }

    public function testSequenceOfIntegers(): void
    {
        $yaml = <<<'YAML'
ids:
  - 1
  - 2
  - 3
YAML;
        $data = $this->parse($yaml);
        $this->assertSame([1, 2, 3], $data['ids']);
    }

    public function testSequenceOfMixedTypes(): void
    {
        $yaml = <<<'YAML'
values:
  - hello
  - 42
  - true
  - ~
YAML;
        $data = $this->parse($yaml);
        $this->assertSame('hello', $data['values'][0]);
        $this->assertSame(42, $data['values'][1]);
        $this->assertTrue($data['values'][2]);
        $this->assertNull($data['values'][3]);
    }

    public function testSequenceOfMappings(): void
    {
        $yaml = <<<'YAML'
users:
  - name: Alice
    age: 30
  - name: Bob
    age: 25
YAML;
        $data = $this->parse($yaml);

        $this->assertCount(2, $data['users']);
        $this->assertSame('Alice', $data['users'][0]['name']);
        $this->assertSame(30, $data['users'][0]['age']);
        $this->assertSame('Bob', $data['users'][1]['name']);
        $this->assertSame(25, $data['users'][1]['age']);
    }

    // ================================================================== //
    //  6. Inline collections
    // ================================================================== //

    public function testInlineSequence(): void
    {
        $data = $this->parse("colors: [red, green, blue]");
        $this->assertSame(['red', 'green', 'blue'], $data['colors']);
    }

    public function testInlineSequenceWithIntegers(): void
    {
        $data = $this->parse("nums: [1, 2, 3]");
        $this->assertSame([1, 2, 3], $data['nums']);
    }

    public function testInlineMapping(): void
    {
        $data = $this->parse("point: {x: 10, y: 20}");
        $this->assertSame(['x' => 10, 'y' => 20], $data['point']);
    }

    public function testNestedInlineCollections(): void
    {
        $data = $this->parse("matrix: [[1, 2], [3, 4]]");
        $this->assertSame([[1, 2], [3, 4]], $data['matrix']);
    }

    public function testInlineSequenceWithBooleans(): void
    {
        $data = $this->parse("flags: [true, false, true]");
        $this->assertSame([true, false, true], $data['flags']);
    }

    public function testInlineSequenceWithCommaInQuotedString(): void
    {
        $data = $this->parse("tags: ['hello, world', foo]");
        $this->assertSame(['hello, world', 'foo'], $data['tags']);
    }

    // ================================================================== //
    //  7. Block scalars
    // ================================================================== //

    public function testLiteralBlockScalar(): void
    {
        $yaml = <<<'YAML'
text: |
  line one
  line two
YAML;
        $data = $this->parse($yaml);
        $this->assertSame("line one\nline two\n", $data['text']);
    }

    public function testLiteralBlockScalarStrip(): void
    {
        $yaml = <<<'YAML'
text: |-
  line one
  line two
YAML;
        $data = $this->parse($yaml);
        $this->assertSame("line one\nline two", $data['text']);
    }

    public function testFoldedBlockScalar(): void
    {
        $yaml = <<<'YAML'
text: >
  hello world
  this is folded
YAML;
        $data = $this->parse($yaml);
        // Single newlines become spaces
        $this->assertStringContainsString('hello world this is folded', $data['text']);
    }

    // ================================================================== //
    //  8. Anchors and aliases
    // ================================================================== //

    public function testAnchorAndAlias(): void
    {
        $yaml = <<<'YAML'
default: &def 42
actual: *def
YAML;
        $data = $this->parse($yaml);
        $this->assertSame(42, $data['default']);
        $this->assertSame(42, $data['actual']);
    }

    public function testAnchorWithString(): void
    {
        $yaml = <<<'YAML'
base: &b localhost
replica: *b
YAML;
        $data = $this->parse($yaml);
        $this->assertSame('localhost', $data['base']);
        $this->assertSame('localhost', $data['replica']);
    }

    // ================================================================== //
    //  9. Document markers
    // ================================================================== //

    public function testDocumentStartMarkerIsIgnored(): void
    {
        $yaml = <<<'YAML'
---
key: value
YAML;
        $data = $this->parse($yaml);
        $this->assertSame('value', $data['key']);
    }

    public function testDocumentEndMarkerIsIgnored(): void
    {
        $yaml = <<<'YAML'
key: value
...
YAML;
        $data = $this->parse($yaml);
        $this->assertSame('value', $data['key']);
    }

    // ================================================================== //
    //  10. Numeric edge cases
    // ================================================================== //

    public function testHexadecimalInteger(): void
    {
        $data = $this->parse("val: 0xFF");
        $this->assertSame(255, $data['val']);
    }

    public function testOctalInteger(): void
    {
        $data = $this->parse("val: 0o17");
        $this->assertSame(15, $data['val']);
    }

    public function testBinaryInteger(): void
    {
        $data = $this->parse("val: 0b1010");
        $this->assertSame(10, $data['val']);
    }

    // ================================================================== //
    //  11. Empty / edge cases
    // ================================================================== //

    public function testEmptyDocument(): void
    {
        $data = $this->parse("");
        $this->assertSame([], $data);
    }

    public function testOnlyComments(): void
    {
        $yaml = <<<'YAML'
# nothing here
# really nothing
YAML;
        $data = $this->parse($yaml);
        $this->assertSame([], $data);
    }

    public function testEmptyValue(): void
    {
        $yaml = <<<'YAML'
empty:
next: value
YAML;
        $data = $this->parse($yaml);
        $this->assertIsArray($data['empty']);
        $this->assertSame('value', $data['next']);
    }

    public function testGetDataReturnsSameResult(): void
    {
        $handler = new YamlHandler($this->lines("key: 123"));
        $this->assertSame($handler->getData(), $handler->getData());
    }

    // ================================================================== //
    //  12. Complex / real-world fixture
    // ================================================================== //

    public function testComplexDocument(): void
    {
        $yaml = <<<'YAML'
# Application configuration
app:
  name: MyApp
  version: 2.1
  debug: false

database:
  host: 127.0.0.1
  port: 5432
  credentials:
    user: admin
    password: 's3cr3t'

features:
  - logging
  - monitoring
  - tracing

limits:
  max_connections: 100
  timeout: 30.5

tags: [web, api, v2]

metadata:
  owner: &owner ops-team
  reviewer: *owner
YAML;
        $data = $this->parse($yaml);

        $this->assertSame('MyApp', $data['app']['name']);
        $this->assertEqualsWithDelta(2.1, $data['app']['version'], 1e-9);
        $this->assertFalse($data['app']['debug']);

        $this->assertSame('127.0.0.1', $data['database']['host']);
        $this->assertSame(5432, $data['database']['port']);
        $this->assertSame('admin', $data['database']['credentials']['user']);
        $this->assertSame('s3cr3t', $data['database']['credentials']['password']);

        $this->assertSame(['logging', 'monitoring', 'tracing'], $data['features']);

        $this->assertSame(100, $data['limits']['max_connections']);
        $this->assertEqualsWithDelta(30.5, $data['limits']['timeout'], 1e-9);

        $this->assertSame(['web', 'api', 'v2'], $data['tags']);

        $this->assertSame('ops-team', $data['metadata']['owner']);
        $this->assertSame('ops-team', $data['metadata']['reviewer']);
    }
}
