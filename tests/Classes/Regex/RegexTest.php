<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Regex;

use PHPUnit\Framework\TestCase;
use Clover\Classes\Regex\Builder;
use Clover\Classes\Regex\Executor;
use Clover\Classes\Regex\Expression;
use Clover\Classes\Regex\ArrayResult;
use Clover\Classes\Regex\StringResult;

class RegexTest extends TestCase
{
	public function testExecutorMatch(): void
	{
		$result = Executor::match('/\d+/', 'abc123def');
		
		$this->assertIsArray($result);
		$this->assertTrue($result['Boolean'] !== false);
		$this->assertEquals('/\d+/', $result['Pattern']);
		$this->assertEquals('abc123def', $result['Subject']);
		$this->assertEquals('123', $result['Matches'][0]);
	}

	public function testExecutorMatchAll(): void
	{
		$result = Executor::matchAll('/\d+/', 'abc123def456');
		
		$this->assertIsArray($result);
		$this->assertEquals(2, $result['Boolean']);
		$this->assertCount(2, $result['Matches'][0]);
		$this->assertEquals('123', $result['Matches'][0][0]);
		$this->assertEquals('456', $result['Matches'][0][1]);
	}

	public function testExecutorNoMatch(): void
	{
		$result = Executor::match('/\d+/', 'abcdef');
		
		$this->assertFalse($result['Boolean'] !== false && $result['Boolean'] !== 0);
		$this->assertEmpty($result['Matches']);
	}

	public function testArrayResultHasResult(): void
	{
		$result = new ArrayResult([
			'Boolean' => true,
			'Pattern' => '/test/',
			'Subject' => 'test string',
			'Matches' => ['test']
		]);
		
		$this->assertTrue($result->hasResult());
	}

	public function testArrayResultGetByIndex(): void
	{
		$result = new ArrayResult([
			'Boolean' => true,
			'Pattern' => '/(\w+)@(\w+)/',
			'Subject' => 'user@domain',
			'Matches' => ['user@domain', 'user', 'domain']
		]);
		
		$this->assertEquals('user@domain', $result->getByIndex(0));
		$this->assertEquals('user', $result->getByIndex(1));
		$this->assertEquals('domain', $result->getByIndex(2));
		$this->assertEquals('', $result->getByIndex(99));
	}

	public function testArrayResultGetters(): void
	{
		$result = new ArrayResult([
			'Boolean' => true,
			'Pattern' => '/test/',
			'Subject' => 'test subject',
			'Matches' => ['test']
		]);
		
		$this->assertEquals('/test/', $result->getPattern());
		$this->assertEquals('test subject', $result->getSubject());
		$this->assertEquals(['test'], $result->getMatches());
		$this->assertEquals(['test'], $result->getResults());
	}

	public function testArrayResultEmptyConstruct(): void
	{
		$result = new ArrayResult();
		
		$this->assertFalse($result->hasResult());
		$this->assertEquals('', $result->getPattern());
		$this->assertEquals('', $result->getSubject());
		$this->assertEquals([], $result->getMatches());
	}

	public function testStringResultGet(): void
	{
		$result = new StringResult([
			'Boolean' => true,
			'Pattern' => '/\d+/',
			'Subject' => 'abc123',
			'Matches' => ['123']
		]);
		
		$this->assertEquals('123', $result->get());
		$this->assertTrue($result->hasResult());
	}

	public function testStringResultGetGroup(): void
	{
		$result = new StringResult([
			'Boolean' => true,
			'Pattern' => '/(\w+)@(\w+)/',
			'Subject' => 'user@domain',
			'Matches' => ['user@domain', 'user', 'domain']
		]);
		
		$this->assertEquals('user@domain', $result->get());
		$this->assertEquals('user', $result->getGroup(1));
		$this->assertEquals('domain', $result->getGroup(2));
	}

	public function testStringResultEmptyConstruct(): void
	{
		$result = new StringResult();
		
		$this->assertFalse($result->hasResult());
		$this->assertEquals('', $result->get());
		$this->assertEquals('', $result->getGroup(0));
	}

	public function testBuilderBasicPattern(): void
	{
		$builder = Builder::create()
			->startOfLine()
			->digits()
			->endOfLine();
		
		$this->assertEquals('/^\d+$/', $builder->build());
	}

	public function testBuilderLiteral(): void
	{
		$builder = Builder::create()
			->literal('test.com');
		
		$this->assertEquals('/test\.com/', $builder->build());
	}

	public function testBuilderCharSet(): void
	{
		$builder = Builder::create()
			->charSet('a-zA-Z')
			->oneOrMore();
		
		$this->assertEquals('/[a-zA-Z]+/', $builder->build());
	}

	public function testBuilderNegatedCharSet(): void
	{
		$builder = Builder::create()
			->negatedCharSet('0-9')
			->oneOrMore();
		
		$this->assertEquals('/[^0-9]+/', $builder->build());
	}

	public function testBuilderGroup(): void
	{
		$builder = Builder::create()
			->group('\d+')
			->literal('-')
			->group('\d+');
		
		$this->assertEquals('/(\d+)\-(\d+)/', $builder->build());
	}

	public function testBuilderNamedGroup(): void
	{
		$builder = Builder::create()
			->namedGroup('year', '\d{4}')
			->literal('-')
			->namedGroup('month', '\d{2}');
		
		$this->assertEquals('/(?P<year>\d{4})\-(?P<month>\d{2})/', $builder->build());
	}

	public function testBuilderNonCapturingGroup(): void
	{
		$builder = Builder::create()
			->nonCapturingGroup('https?')
			->literal('://');
		
		$this->assertEquals('/(?:https?)\:\/\//', $builder->build());
	}

	public function testBuilderLookahead(): void
	{
		$builder = Builder::create()
			->words()
			->positiveLookahead('@');
		
		$this->assertEquals('/\w+(?=@)/', $builder->build());
	}

	public function testBuilderNegativeLookahead(): void
	{
		$builder = Builder::create()
			->words()
			->negativeLookahead('@');
		
		$this->assertEquals('/\w+(?!@)/', $builder->build());
	}

	public function testBuilderLookbehind(): void
	{
		$builder = Builder::create()
			->positiveLookbehind('\$')
			->digits();
		
		$this->assertEquals('/(?<=\$)\d+/', $builder->build());
	}

	public function testBuilderNegativeLookbehind(): void
	{
		$builder = Builder::create()
			->negativeLookbehind('\$')
			->digits();
		
		$this->assertEquals('/(?<!\$)\d+/', $builder->build());
	}

	public function testBuilderQuantifiers(): void
	{
		$builder = Builder::create()
			->digit()
			->times(3);
		
		$this->assertEquals('/\d{3}/', $builder->build());
		
		$builder = Builder::create()
			->digit()
			->between(2, 4);
		
		$this->assertEquals('/\d{2,4}/', $builder->build());
		
		$builder = Builder::create()
			->digit()
			->atLeast(2);
		
		$this->assertEquals('/\d{2,}/', $builder->build());
	}

	public function testBuilderOptional(): void
	{
		$builder = Builder::create()
			->literal('https')
			->optional();
		
		$this->assertEquals('/https?/', $builder->build());
	}

	public function testBuilderOr(): void
	{
		$builder = Builder::create()
			->groupStart()
			->literal('cat')
			->or()
			->literal('dog')
			->groupEnd();
		
		$this->assertEquals('/(cat|dog)/', $builder->build());
	}

	public function testBuilderModifiers(): void
	{
		$builder = Builder::create()
			->words()
			->caseInsensitive()
			->multiline();
		
		$this->assertEquals('/\w+/im', $builder->build());
	}

	public function testBuilderDotAll(): void
	{
		$builder = Builder::create()
			->anyChar()
			->oneOrMore()
			->dotAll();
		
		$this->assertEquals('/.+/s', $builder->build());
	}

	public function testBuilderUnicode(): void
	{
		$builder = Builder::create()
			->words()
			->unicode();
		
		$this->assertEquals('/\w+/u', $builder->build());
	}

	public function testBuilderExtended(): void
	{
		$builder = Builder::create()
			->digits()
			->extended();
		
		$this->assertEquals('/\d+/x', $builder->build());
	}

	public function testBuilderBackreference(): void
	{
		$builder = Builder::create()
			->group('\w+')
			->whitespace()
			->backreference(1);
		
		$this->assertEquals('/(\w+)\s\1/', $builder->build());
	}

	public function testBuilderNamedBackreference(): void
	{
		$builder = Builder::create()
			->namedGroup('word', '\w+')
			->whitespace()
			->namedBackreference('word');
		
		$this->assertEquals('/(?P<word>\w+)\s(?P=word)/', $builder->build());
	}

	public function testBuilderTest(): void
	{
		$builder = Builder::create()
			->startOfLine()
			->digits()
			->endOfLine();
		
		$this->assertTrue($builder->test('123'));
		$this->assertFalse($builder->test('abc'));
		$this->assertFalse($builder->test('123abc'));
	}

	public function testBuilderMatch(): void
	{
		$builder = Builder::create()
			->group('\d+');
		
		$result = $builder->match('abc123def');
		
		$this->assertTrue($result->hasResult());
		$this->assertEquals('123', $result->getByIndex(0));
	}

	public function testBuilderMatchAll(): void
	{
		$builder = Builder::create()
			->digits();
		
		$result = $builder->matchAll('a1b2c3');
		
		$this->assertTrue($result->hasResult());
		$this->assertCount(3, $result->getByIndex(0));
	}

	public function testBuilderReplace(): void
	{
		$builder = Builder::create()
			->digits();
		
		$result = $builder->replace('X', 'a1b2c3');
		
		$this->assertEquals('aXbXcX', $result);
	}

	public function testBuilderSplit(): void
	{
		$builder = Builder::create()
			->literal(',')
			->whitespaces()
			->optional();
		
		$result = $builder->split('a, b, c, d');
		
		$this->assertEquals(['a', 'b', 'c', 'd'], $result);
	}

	public function testBuilderToString(): void
	{
		$builder = Builder::create()
			->digits()
			->caseInsensitive();
		
		$this->assertEquals('/\d+/i', (string)$builder);
	}

	public function testBuilderCustomDelimiter(): void
	{
		$builder = Builder::create()
			->setDelimiter('#')
			->literal('/path/to/file');
		
		$this->assertEquals("#/path/to/file#", $builder->build());
	}

	public function testBuilderWordBoundary(): void
	{
		$builder = Builder::create()
			->wordBoundary()
			->literal('test')
			->wordBoundary();
		
		$this->assertTrue($builder->test('this is a test here'));
		$this->assertFalse($builder->test('testing'));
	}

	public function testBuilderWhitespacePatterns(): void
	{
		$builder = Builder::create()
			->whitespaces();
		
		$this->assertTrue($builder->test("  \t\n"));
		
		$builder = Builder::create()
			->nonWhitespace()
			->oneOrMore();
		
		$this->assertTrue($builder->test('abc'));
		$this->assertFalse($builder->test('   '));
	}

	public function testBuilderSpecialCharacters(): void
	{
		$builder = Builder::create()
			->tab()
			->newline()
			->carriageReturn();
		
		$this->assertEquals('/\t\n\r/', $builder->build());
	}

	public function testBuilderLazyQuantifier(): void
	{
		$builder = Builder::create()
			->anyChar()
			->oneOrMore()
			->lazy();
		
		$this->assertEquals('/.+?/', $builder->build());
	}

	public function testBuilderRaw(): void
	{
		$builder = Builder::create()
			->raw('(?:[a-z]+)')
			->raw('(?:\d+)');
		
		$this->assertEquals('/(?:[a-z]+)(?:\d+)/', $builder->build());
	}

	public function testBuilderChaining(): void
	{
		$emailPattern = Builder::create()
			->startOfLine()
			->charSet('a-zA-Z0-9._%+-')
			->oneOrMore()
			->literal('@')
			->charSet('a-zA-Z0-9.-')
			->oneOrMore()
			->literal('.')
			->charSet('a-zA-Z')
			->between(2, 6)
			->endOfLine()
			->caseInsensitive();
		
		$this->assertTrue($emailPattern->test('test@example.com'));
		$this->assertTrue($emailPattern->test('USER@DOMAIN.ORG'));
		$this->assertFalse($emailPattern->test('invalid'));
		$this->assertFalse($emailPattern->test('@nodomain.com'));
	}

	public function testExpressionDigits(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('\d', $expr->digits());
		$this->assertEquals('\D', $expr->nonDigits());
	}

	public function testExpressionWhitespace(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('\s', $expr->whiteSpaceCharacters());
		$this->assertEquals('\S', $expr->nonWhiteSpaceCharacters());
	}

	public function testExpressionWord(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('\w', $expr->alphanumericCharacters());
		$this->assertEquals('\W', $expr->nonAlphanumericCharacters());
		$this->assertEquals('\b', $expr->wordBoundary());
	}

	public function testExpressionLookaround(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('?=', $expr->positiveLookahead());
		$this->assertEquals('?!', $expr->negativeLookahead());
		$this->assertEquals('?<=', $expr->positiveLookbehind());
		$this->assertEquals('?<!', $expr->negativeLookbehind());
	}

	public function testExpressionGroups(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('(?:test)', $expr->noneCapturingGroup('test'));
		$this->assertEquals('(?P<name>pattern)', $expr->namedCapturingGroup('name', 'pattern'));
		$this->assertEquals('(?>atomic)', $expr->atomicGroup('atomic'));
	}

	public function testExpressionQuantifiers(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('test*', $expr->zeroOrMoreQuantifier('test'));
		$this->assertEquals('test?', $expr->zeroOrOneQuantifier('test'));
		$this->assertEquals('+', $expr->oneOrMoreQuantifier());
	}

	public function testExpressionSpecialChars(): void
	{
		$expr = new Expression();
		
		$this->assertEquals("\t", $expr->horizontalTab());
		$this->assertEquals("\n", $expr->lineFeed());
		$this->assertEquals("\r", $expr->carriageReturn());
		$this->assertEquals("\v", $expr->verticalTab());
		$this->assertEquals("\f", $expr->formFeed());
	}

	public function testExpressionBlockTag(): void
	{
		$expr = new Expression();
		
		$pattern = '/' . $expr->blockTag('div') . '/';
		$this->assertEquals(1, preg_match($pattern, '<div>content</div>'));
	}

	public function testExpressionNegativeSet(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('[^abc]', $expr->negativeSet('abc'));
	}

	public function testExpressionCondition(): void
	{
		$expr = new Expression();
		
		$result = $expr->condition('pattern', 'then', 'else');
		$this->assertStringContainsString('?=', $result);
		$this->assertStringContainsString('then', $result);
		$this->assertStringContainsString('else', $result);
	}

	public function testExpressionModes(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('(?i)', $expr->caseInsensitive());
		$this->assertEquals('(?s)', $expr->dotAll());
		$this->assertEquals('(?x)', $expr->turnOnFreeSpacingMode());
	}

	public function testExpressionSubroutines(): void
	{
		$expr = new Expression();
		
		$this->assertEquals('(?R)', $expr->recursion());
		$this->assertEquals('(?-1)', $expr->relativeSubroutineCall());
	}

	public function testExpressionEmailValidation(): void
	{
		$expr = new Expression();
		
		$pattern = '/' . $expr->isValidEmail() . '/';
		$this->assertEquals(1, preg_match($pattern, 'test@example.com'));
		$this->assertEquals(0, preg_match($pattern, 'invalid-email'));
	}

	public function testExpressionNumberFormat(): void
	{
		$expr = new Expression();
		$pattern = '/' . $expr->numberFormat() . '/';
		
		$formatted = preg_replace($pattern, ',', '1234567');
		$this->assertEquals('1,234,567', $formatted);
	}

	public function testBuilderStaticCreate(): void
	{
		$builder = Builder::create('\d+');
		
		$this->assertEquals('/\d+/', $builder->build());
	}

	public function testBuilderGetPattern(): void
	{
		$builder = Builder::create()
			->startOfLine()
			->digits();
		
		$this->assertEquals('^\d+', $builder->getPattern());
	}

	public function testArrayResultGetSingleton(): void
	{
		$original = new ArrayResult();
		$singleton = $original->getSingleton([
			'Boolean' => true,
			'Pattern' => '/test/',
			'Subject' => 'test',
			'Matches' => ['test']
		]);
		
		$this->assertInstanceOf(ArrayResult::class, $singleton);
		$this->assertTrue($singleton->hasResult());
	}

	public function testStringResultGetSingleton(): void
	{
		$original = new StringResult();
		$singleton = $original->getSingleton([
			'Boolean' => true,
			'Pattern' => '/test/',
			'Subject' => 'test',
			'Matches' => ['test']
		]);
		
		$this->assertInstanceOf(StringResult::class, $singleton);
		$this->assertTrue($singleton->hasResult());
	}
}