<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Debug\ControlFlow;

use Clover\Classes\Debug\ControlFlow\TokenCursor;
use PHPUnit\Framework\TestCase;

final class TokenCursorTest extends TestCase
{
	public function testInitialStateExposesTheOpeningPhpToken(): void
	{
		$cursor = new TokenCursor('<?php echo 1;');

		$this->assertTrue($cursor->valid());
		$this->assertSame(T_OPEN_TAG, $cursor->id());
		$this->assertSame('<?php ', $cursor->text());
	}

	public function testAdvanceReturnsTheSameCursorAndMovesToTheNextToken(): void
	{
		$cursor = new TokenCursor('<?php echo 1;');

		$this->assertSame($cursor, $cursor->advance());
		$this->assertSame(T_ECHO, $cursor->id());
		$this->assertSame('echo', $cursor->text());
	}

	public function testSkipConsumesWhitespaceCommentsAndDocComments(): void
	{
		$cursor = $this->cursorAtCode("  /* regular */\n/** doc */\n\t\$value");

		$this->assertSame($cursor, $cursor->skip());
		$this->assertSame(T_VARIABLE, $cursor->id());
		$this->assertSame('$value', $cursor->text());
	}

	public function testConsumeSkipsIgnorableTokensAndConsumesMatchingCharacter(): void
	{
		$cursor = $this->cursorAtCode(" /* lead */ (");

		$this->assertSame($cursor, $cursor->consume('('));
		$this->assertFalse($cursor->valid());
	}

	public function testConsumeLeavesNonMatchingTokenInPlace(): void
	{
		$cursor = $this->cursorAtCode('$value');

		$cursor->consume('(');

		$this->assertSame(T_VARIABLE, $cursor->id());
		$this->assertSame('$value', $cursor->text());
	}

	public function testCollectInnerHandlesNestedBalancedDelimitersAndNormalizesWhitespace(): void
	{
		$cursor = $this->cursorAtCode('(foo( 1,  2 ) + bar) tail');

		$this->assertSame('foo( 1, 2 ) + bar', $cursor->collectInner('(', ')'));
		$cursor->skip();
		$this->assertSame(T_STRING, $cursor->id());
		$this->assertSame('tail', $cursor->text());
	}

	public function testCollectInnerReturnsEmptyWithoutAdvancingWhenOpeningDelimiterIsMissing(): void
	{
		$cursor = $this->cursorAtCode('$value + 1');

		$this->assertSame('', $cursor->collectInner('(', ')'));
		$this->assertSame('$value', $cursor->text());
	}

	public function testCollectInnerReturnsCollectedContentWhenInputEndsBeforeClosingDelimiter(): void
	{
		$cursor = $this->cursorAtCode('(foo + bar');

		$this->assertSame('foo + bar', $cursor->collectInner('(', ')'));
		$this->assertFalse($cursor->valid());
	}

	public function testCollectStatementSkipsWhitespaceAndCommentsAndConsumesTerminatingSemicolon(): void
	{
		$cursor = $this->cursorAtCode("\$value /* comment */ = foo(1, [2, 3]); nextCall()");

		$this->assertSame(
			['$value', '=', 'foo', '(', '1', ',', '[', '2', ',', '3', ']', ')'],
			$cursor->collectStatement()
		);
		$cursor->skip();
		$this->assertSame(T_STRING, $cursor->id());
		$this->assertSame('nextCall', $cursor->text());
	}

	public function testCollectStatementKeepsSemicolonsInsideNestedBraces(): void
	{
		$cursor = $this->cursorAtCode('{ first(); second(); }; after');

		$this->assertSame(
			['{', 'first', '(', ')', ';', 'second', '(', ')', ';', '}'],
			$cursor->collectStatement()
		);
		$cursor->skip();
		$this->assertSame('after', $cursor->text());
	}

	public function testCollectStatementStopsBeforeAnUnmatchedClosingDelimiter(): void
	{
		$cursor = $this->cursorAtCode('$value + 1) trailing');

		$this->assertSame(['$value', '+', '1'], $cursor->collectStatement());
		$this->assertSame(')', $cursor->text());
	}

	private function cursorAtCode(string $code): TokenCursor
	{
		$cursor = new TokenCursor('<?php ' . $code);
		$cursor->advance();

		return $cursor;
	}
}
