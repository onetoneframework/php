<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Data\Object;

use Clover\Classes\Data\StringObject;
use Clover\Classes\Data\ArrayObject;
use PHPUnit\Framework\TestCase;

class StringObjectTest extends TestCase
{
    public function testConstructor(): void
    {
        $str = new StringObject('hello');
        $this->assertEquals('hello', (string) $str);
    }

    public function testTimeFormat(): void
    {
        $str = new StringObject('3661'); // 1h 1m 1s
        $formatted = $str->timeFormat();
        $this->assertEquals('1 Hour 1 Minute 1 Second', (string) $formatted);
    }

    public function testToArray(): void
    {
        $str = new StringObject('abc');
        $arr = $str->toArray();
        $this->assertInstanceOf(ArrayObject::class, $arr);
        $this->assertEquals(['a', 'b', 'c'], $arr->getRawData());
    }

    public function testEllipsis(): void
    {
        $str = new StringObject('hello world');
        $truncated = $str->ellipsis(5);
        $this->assertEquals('hello...', (string) $truncated);
        
        $notTruncated = $str->ellipsis(100);
        $this->assertEquals('hello world', (string) $notTruncated);
    }

    public function testNumberFormat(): void
    {
        $str = new StringObject('1234567890');
        $formatted = $str->nubmerFormat('n3-n3-n4');
        $this->assertEquals('123-456-7890', (string) $formatted);
    }

    public function testToRomanDigits(): void
    {
        $str = new StringObject('2023');
        $roman = $str->toRomanDigits();
        $this->assertEquals('MMXXIII', (string) $roman);
    }

    public function testToInteger(): void
    {
        $str = new StringObject('123');
        $this->assertEquals(123, $str->toInteger());
    }

    public function testCaseChecks(): void
    {
        $lower = new StringObject('abc');
        $upper = new StringObject('ABC');
        $mixed = new StringObject('Abc');

        $this->assertTrue($lower->isLowercase());
        $this->assertFalse($lower->isUppercase());

        $this->assertTrue($upper->isUppercase());
        $this->assertFalse($upper->isLowercase());

        $this->assertTrue($mixed->hasLowercase());
        $this->assertTrue($mixed->hasUppercase());
    }

    public function testWhitespaceChecks(): void
    {
        $str = new StringObject('hello world');
        $this->assertTrue($str->hasWhitespace());
        
        $str2 = new StringObject('helloworld');
        $this->assertFalse($str2->hasWhitespace());
    }

    public function testNgrams(): void
    {
        $str = new StringObject('abcde');
        $ngrams = $str->ngrams(2);
        $this->assertEquals(['ab', 'bc', 'cd', 'de'], $ngrams);
    }

    public function testSlugify(): void
    {
        $str = new StringObject('Hello World!');
        $slug = $str->slugify();
        $this->assertEquals('hello-world', (string) $slug);
    }

    public function testCharTypeChecks(): void
    {
        $ascii = new StringObject('abc');
        $this->assertTrue($ascii->isAscii());

        $alnum = new StringObject('abc123');
        $this->assertTrue($alnum->isAlphanumeric());

        $hex = new StringObject('ABC012');
        $this->assertTrue($hex->isHexadecimal());
    }

    public function testMatchFromPairs(): void
    {
        $str = new StringObject('apple');
        $value = $str->matchFromPairs('unknown', 'apple', 'red', 'banana', 'yellow');
        $this->assertEquals('red', $value);

        $str2 = new StringObject('grape');
        $value2 = $str2->matchFromPairs('unknown', 'apple', 'red');
        $this->assertEquals('unknown', $value2);
    }

    public function testEquals(): void
    {
        $str = new StringObject('test');
        $this->assertTrue($str->equals('test'));
        $this->assertFalse($str->equals('other'));
    }

    public function testIsEmail(): void
    {
        $email = new StringObject('test@example.com');
        $this->assertTrue($email->isEmail());

        $notEmail = new StringObject('invalid-email');
        $this->assertFalse($notEmail->isEmail());
    }

    public function testLastIndexOf(): void
    {
        $str = new StringObject('hello world hello');
        $this->assertEquals(12, $str->lastIndexOf('hello'));
    }

    public function testIndexOf(): void
    {
        $str = new StringObject('hello world');
        $this->assertEquals(0, $str->indexOf('hello'));
        $this->assertEquals(6, $str->indexOf('world'));
    }

    public function testTrimStart(): void
    {
        $str = new StringObject('  hello');
        $trimmed = $str->trimStart();
        $this->assertEquals('hello', (string) $trimmed);
    }

    public function testTrimEnd(): void
    {
        $str = new StringObject('hello  ');
        $trimmed = $str->trimEnd();
        $this->assertEquals('hello', (string) $trimmed);
    }

    public function testTrim(): void
    {
        $str = new StringObject('  hello  ');
        $trimmed = $str->trim();
        $this->assertEquals('hello', (string) $trimmed);
    }

    public function testReplace(): void
    {
        $str = new StringObject('hello world');
        $replaced = $str->replace('world', 'php');
        $this->assertEquals('hello php', (string) $replaced);
    }

    public function testReverse(): void
    {
        $str = new StringObject('abc');
        $reversed = $str->reverse();
        $this->assertEquals('cba', (string) $reversed);
    }

    public function testReplaceBlanks(): void
    {
        $str = new StringObject('hello   world');
        $replaced = $str->replaceBlanks('-');
        $this->assertEquals('hello-world', (string) $replaced);
    }

    public function testRemoveBlanks(): void
    {
        $str = new StringObject('hello world');
        $removed = $str->removeBlanks();
        $this->assertEquals('helloworld', (string) $removed);
    }

    public function testSplit(): void
    {
        $str = new StringObject('a,b,c');
        $split = $str->split(',');
        $this->assertInstanceOf(ArrayObject::class, $split);
        $this->assertEquals(['a', 'b', 'c'], $split->getRawData());
    }

    public function testReverseWords(): void
    {
        $str = new StringObject('hello world');
        $reversed = $str->reverseWords();
        $this->assertEquals('world hello', (string) $reversed);
    }

    public function testStartsWith(): void
    {
        $str = new StringObject('hello world');
        $this->assertTrue((bool)$str->startsWith('hello'));
        $this->assertFalse((bool)$str->startsWith('world'));
    }

    public function testEndsWith(): void
    {
        $str = new StringObject('hello world');
        $this->assertTrue((bool)$str->endsWith('world'));
        $this->assertFalse((bool)$str->endsWith('hello'));
    }
}
