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
use Clover\Classes\Data\CardNumberObject;
use Clover\Classes\Data\DataTransferObject;
use Clover\Classes\Data\EmailObject;
use Clover\Classes\Data\HTMLObject;
use Clover\Classes\Data\StringObject;
use PHPUnit\Framework\TestCase;

class DataTransfer extends DataTransferObject
{
    private $store;
}

class StringObjectTest extends TestCase
{

    public function setUp(): void
    {
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', sprintf("%s/../../../root", __DIR__));
        }
    }

    public function testCardNumber()
    {
        $string = new CardNumberObject('4539148803436467');
        $this->assertTrue($string->isVisa());
        $this->assertTrue($string->isValidate());
    }

    public function testDataTransfer()
    {
        $dataTransfer = new DataTransfer();
        $dataTransfer->setStore('storedText');
        $store = $dataTransfer->getStore();
        $this->assertEquals('storedText', $store);
        
        $store = DataTransfer::from(['store' => 'storedStaticText']);
        $store = $store->getStore();
        $this->assertEquals('storedStaticText', $store);
    }

    public function testHtml()
    {
        $string = new HTMLObject('<a>https://www.google.com</a>');
        $this->assertEquals('<a><a href="https://www.google.com">https://www.google.com</a></a>', $string->autolink());
    }

    public function testEmail()
    {
        $domain = new EmailObject('clover@naver.com');
        $this->assertTrue($domain->isKnownDomain());
        $this->assertEquals("naver.com", $domain->getDomain());

        $domain = new EmailObject('clover@gmail.com');
        $this->assertEquals("gmail.com", $domain->getDomain());
        $this->assertTrue($domain->isKnownDomain());
    }

    public function testString(): void
    {
        $string = new StringObject('This is text for testing');

        $this->assertEquals('This is text for testing!!', $string->append('!!'));
        $this->assertEquals('Notice: This is text for testing!!', $string->prepend('Notice: '));
        $this->assertEquals('**Notice: This is text for testing!!**', $string->appendBoth('**'));
        $this->assertEquals('Intel', $string->set('Intel Computer')->cut(5));
        $this->assertEquals('CloverFramework', $string->set('Clover Framework')->camelize());
        $this->assertEquals('clover-framework', $string->set('Clover Framework')->slugify());
        $this->assertEquals('Clover framework', $string->set('clover framework')->capitalizeFirstLetter());
        $this->assertNotEquals('abcdefghijklmnopqrstuvwxynz', $string->set('abcdefghijklmnopqrstuvwxynz')->shuffle());
        $this->assertEquals('Clover Framework', $string->set('  Clover Framework  ')->trim());
        $this->assertEquals('  Clover Framework', $string->set('  Clover Framework')->trimEnd()->getRawData());
        $this->assertEquals('CLOVER FRAMEWORK', $string->set('Clover Framework')->toUpperCase()->getRawData());
        $this->assertEquals('clover framework', $string->set('Clover Framework')->toLowerCase()->getRawData());
        $this->assertEquals('krowemarF revolC', $string->set('Clover Framework')->reverse()->getRawData());
        $this->assertEquals('AAAAAAAAAA', $string->set('A')->repeat(10)->getRawData());
        $this->assertEquals(15, $string->set('Clover Framework')->similar('Closer Framework'));
        $this->assertEquals('C*o*e* *r*m*w*r*', $string->set('Clover Framework')->replaceEven('*')->getRawData());
        $this->assertEquals('*l*v*r*F*a*e*o*k', $string->set('Clover Framework')->replaceOdd('*')->getRawData());
        $this->assertEquals('Clov********work', $string->set('Clover Framework')->replaceCenter('*******')->getRawData());
        $this->assertEquals('Cl*******Framework', $string->set('Clover Framework')->replaceSubstr('*******', 2, 5)->getRawData());
        $this->assertEquals('Clover Framework Unit Test', $string->set('Clover Framework')->concat(' Unit Test')->getRawData());
        $this->assertEquals(2, $string->set('Clover Framework')->wordCount());
        $this->assertEquals('Clover FrameworkWord', $string->set('Clover Framework')->ensureEndsWith('Word')->getRawData());
        $this->assertEquals('Clover Framework Word', $string->set('Clover Framework Word')->ensureEndsWith('Word')->getRawData());
        $this->assertEquals('TestClover Framework', $string->set('Clover Framework')->ensureStartsWith('Test')->getRawData());
        $this->assertEquals('Test Clover Framework', $string->set('Test Clover Framework')->ensureStartsWith('Test')->getRawData());

        $expected = [
            ["index" => 0, "start" => 1, "pattern" => "ATG"],
            ["index" => 2, "start" => 6, "pattern" => "TAC"],
            ["index" => 4, "start" => 13, "pattern" => "ACT"],
            ["index" => 1, "start" => 16, "pattern" => "GCT"],
            ["index" => 1, "start" => 32, "pattern" => "GCT"],
            ["index" => 0, "start" => 35, "pattern" => "ATG"],
            ["index" => 4, "start" => 38, "pattern" => "ACT"],
            ["index" => 2, "start" => 48, "pattern" => "TAC"],
        ];
        $this->assertEquals($expected, $string->set('ATGCGTACCTGAACTGCTTAGCGGATCCTAGGCTATGACTGGAATCGTAC')->ahoCorasick(["ATG", "GCT", "TAC", "CGA", "ACT"])->getRawData());

        $string = new StringObject('630907-2458061');
        $this->assertTrue($string->isValidResidentRegistrationNumberInKorea());

        $string = new StringObject('680204-1531257');
        $this->assertTrue($string->isValidResidentRegistrationNumberInKorea());

        $string = new StringObject('041215-1116529');
        $this->assertTrue($string->isValidResidentRegistrationNumberInKorea());

        $string = new StringObject('041215-1116529');
        $this->assertTrue($string->isValidResidentRegistrationNumberInKorea());

        $string = new StringObject('250619-1373084');
        $this->assertTrue($string->isValidResidentRegistrationNumberInKorea());

        $string = new StringObject('241016-1343636');
        $this->assertTrue($string->isValidResidentRegistrationNumberInKorea());
    }

    public function testArray(): void
    {
        $array = new ArrayObject(['a', 'b', 'c', 'd', 'e']);
        $this->assertEquals(5, $array->count());
        $this->assertEquals('abcde', $array->join(''));
        $this->assertEquals('e', $array->last());
        $this->assertEquals('a', $array->first());
        $this->assertEquals('abcdef', $array->add('f')->join(''));

        $array = new ArrayObject(['a' => ['a' => '1', 'b' => '2', 'c' => ['3']], 'b' => ['a' => '1', 'b' => '2']]);
        $this->assertEquals(2, $array->getMaxDepth());
    }
}
