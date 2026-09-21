<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Tests\Classes\Debug;

use Attribute;
use Clover\Classes\Debug\TraceArgumentObject;
use Clover\Classes\Debug\TraceObject;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use PHPUnit\Framework\TestCase;
use ReflectionNamedType;

/**
 * TraceObject turns one `debug_backtrace()` frame into the rendered form the
 * error handler prints. The three frame shapes it has to cope with are a
 * function-only frame, a class+method frame, and a frame carrying a file and a
 * line; each takes a different branch of the constructor.
 */
class TraceObjectTest extends TestCase
{
	/**
	 * Line endings built from their codepoints rather than written inline.
	 *
	 * A literal CR byte in this source is one `git add` away from being
	 * normalised to LF by the repository's `* text=auto eol=lf` rule, which
	 * silently turns the CRLF fixture into an LF one and takes the assertion
	 * with it. That is exactly how this file was committed once already.
	 */
	private const CARRIAGE_RETURN = "\x0D";

	private const LINE_FEED = "\x0A";

	private const CARRIAGE_RETURN_LINE_FEED = self::CARRIAGE_RETURN . self::LINE_FEED;

	/** @var string[] Temporary source files written by sourceFixture(). */
	private array $fixtures = [];

	/**
	 * The file a method frame points at. It must not be this test file: the
	 * rendered code block embeds the source verbatim, so asserting against a
	 * marker that also appears in the assertion itself always passes.
	 */
	private string $frameFile = '';

	protected function setUp(): void
	{
		parent::setUp();
		$this->frameFile = $this->sourceFixture(self::CARRIAGE_RETURN_LINE_FEED);
	}

	protected function tearDown(): void
	{
		foreach ($this->fixtures as $path) {
			if (is_file($path)) {
				unlink($path);
			}
		}

		$this->fixtures = [];
		parent::tearDown();
	}

	/**
	 * A frame for a plain function call, with no class and no file.
	 *
	 * @return array<string, mixed>
	 */
	private function functionFrame(): array
	{
		return ['function' => 'array_map', 'args' => [null, []]];
	}

	/**
	 * A frame for a method call on the fixture below.
	 *
	 * @return array<string, mixed>
	 */
	private function methodFrame(string $function = 'annotatedMethod'): array
	{
		return [
			'function' => $function,
			'class' => TraceObjectFixture::class,
			'type' => '->',
			'file' => $this->frameFile,
			'line' => 3,
			'args' => ['first', 2],
		];
	}

	public function testIsInstantiable(): void
	{
		$this->assertInstanceOf(TraceObject::class, new TraceObject($this->functionFrame()));
	}

	public function testAnEmptyFrameProducesAnObjectWithNothingSet(): void
	{
		$trace = new TraceObject([]);

		$this->assertFalse($trace->hasFunction());
		$this->assertFalse($trace->hasClass());
		$this->assertFalse($trace->hasFile());
		$this->assertFalse($trace->hasLine());
		$this->assertNull($trace->getIDELink());
	}

	public function testGetIDELink(): void
	{
		$withFile = new TraceObject($this->methodFrame());
		$withoutFile = new TraceObject($this->functionFrame());

		$this->assertIsString($withFile->getIDELink());
		$this->assertStringContainsString('vscode', strtolower((string) $withFile->getIDELink()));
		$this->assertNull($withoutFile->getIDELink());
	}

	public function testHasArguments(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasArguments());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasArguments());
	}

	public function testGetArguments(): void
	{
		$arguments = (new TraceObject($this->methodFrame()))->getArguments();

		$this->assertIsArray($arguments);
		$this->assertContainsOnlyInstancesOf(TraceArgumentObject::class, $arguments);
		$this->assertCount(2, $arguments, 'annotatedMethod() declares two parameters.');
	}

	public function testArgumentsAreEmptyForAFunctionOnlyFrame(): void
	{
		$this->assertSame([], (new TraceObject($this->functionFrame()))->getArguments());
	}

	public function testHasReturnType(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasReturnType());
		$this->assertFalse((new TraceObject($this->methodFrame('untypedMethod')))->hasReturnType());
	}

	public function testGetReturnType(): void
	{
		$returnType = (new TraceObject($this->methodFrame()))->getReturnType();

		$this->assertInstanceOf(ReflectionNamedType::class, $returnType);
		$this->assertSame('string', $returnType->getName());
	}

	public function testSetReturnType(): void
	{
		$trace = new TraceObject($this->functionFrame());
		$declared = (new \ReflectionMethod(TraceObjectFixture::class, 'annotatedMethod'))->getReturnType();

		$this->assertFalse($trace->hasReturnType());
		$trace->setReturnType($declared);

		$this->assertTrue($trace->hasReturnType());
		$this->assertSame($declared, $trace->getReturnType());

		$trace->setReturnType(null);
		$this->assertFalse($trace->hasReturnType());
	}

	public function testHasFile(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasFile());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasFile());
	}

	public function testGetFile(): void
	{
		$this->assertSame($this->frameFile, (new TraceObject($this->methodFrame()))->getFile());
		$this->assertNull((new TraceObject($this->functionFrame()))->getFile());
	}

	public function testHasShortName(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasShortName());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasShortName());
	}

	public function testGetShortName(): void
	{
		$this->assertSame('TraceObjectFixture', (new TraceObject($this->methodFrame()))->getShortName());
	}

	public function testSetShortName(): void
	{
		$trace = new TraceObject($this->functionFrame());
		$trace->setShortName('Renamed');

		$this->assertTrue($trace->hasShortName());
		$this->assertSame('Renamed', $trace->getShortName());
	}

	public function testHasType(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasType());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasType());
	}

	public function testGetType(): void
	{
		$this->assertSame('->', (new TraceObject($this->methodFrame()))->getType());
		$this->assertNull((new TraceObject($this->functionFrame()))->getType());
	}

	public function testHasLine(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasLine());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasLine());
	}

	public function testGetLine(): void
	{
		$this->assertSame(3, (new TraceObject($this->methodFrame()))->getLine());
		$this->assertNull((new TraceObject($this->functionFrame()))->getLine());
	}

	public function testHasText(): void
	{
		$this->assertTrue((new TraceObject($this->functionFrame()))->hasText());
		$this->assertFalse((new TraceObject([]))->hasText());
	}

	public function testSetText(): void
	{
		$trace = new TraceObject([]);
		$trace->setText('custom()');

		$this->assertTrue($trace->hasText());
		$this->assertSame('custom()', $trace->getText());
	}

	public function testGetText(): void
	{
		$this->assertSame('array_map()', (new TraceObject($this->functionFrame()))->getText());
	}

	public function testTheTextOfAMethodFrameCarriesModifierClassAndReturnType(): void
	{
		$text = (string) (new TraceObject($this->methodFrame()))->getText();

		$this->assertStringContainsString('public', $text);
		$this->assertStringContainsString('TraceObjectFixture->annotatedMethod', $text);
		$this->assertStringContainsString('string', $text);
	}

	public function testHasClass(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasClass());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasClass());
	}

	public function testGetClass(): void
	{
		$this->assertSame(TraceObjectFixture::class, (new TraceObject($this->methodFrame()))->getClass());
		$this->assertNull((new TraceObject($this->functionFrame()))->getClass());
	}

	public function testHasCode(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasCode());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasCode());
	}

	/**
	 * getCode() was declared `string` while the property it returns is nullable,
	 * so a frame with no file raised a TypeError rather than reporting no code.
	 */
	public function testGetCodeIsNullForAFrameWithNoFile(): void
	{
		$this->assertNull((new TraceObject($this->functionFrame()))->getCode());
		$this->assertNull((new TraceObject([]))->getCode());
	}

	public function testGetCode(): void
	{
		$code = (new TraceObject($this->methodFrame()))->getCode();

		$this->assertStringContainsString('<span class="code-line-number">3</span>', $code);
		$this->assertStringContainsString('data-highlight="true"', $code, 'Line 3 is the frame line.');
		$this->assertStringContainsString('$beta = 2;', $code);
	}

	public function testHasComment(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasComment());
		$this->assertFalse((new TraceObject($this->methodFrame('undocumentedMethod')))->hasComment());
	}

	public function testGetComment(): void
	{
		$comments = (new TraceObject($this->methodFrame()))->getComment();

		$this->assertIsArray($comments);
		$this->assertContains('The fixture method the trace tests reflect over.', $comments);
	}

	public function testGetCommentTag(): void
	{
		$tag = (new TraceObject($this->methodFrame()))->getCommentTag();

		$this->assertStringContainsString('The fixture method the trace tests reflect over.', $tag);
	}

	/**
	 * getCommentTag() used to hand a null property to join(), which then read the
	 * separator as the array and raised a TypeError.
	 */
	public function testGetCommentTagIsEmptyWhenThereAreNoComments(): void
	{
		$this->assertSame('', (new TraceObject($this->functionFrame()))->getCommentTag());
		$this->assertSame('', (new TraceObject($this->methodFrame('undocumentedMethod')))->getCommentTag());
	}

	public function testHasAnnotation(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasAnnotation());
		$this->assertFalse((new TraceObject($this->methodFrame('undocumentedMethod')))->hasAnnotation());
	}

	public function testGetAnnotation(): void
	{
		$annotation = (string) (new TraceObject($this->methodFrame()))->getAnnotation();

		$this->assertStringContainsString(TraceObjectMarker::class, $annotation);
		$this->assertStringContainsString("'marked'", $annotation);
	}

	public function testSetFunction(): void
	{
		$trace = new TraceObject([]);

		$this->assertFalse($trace->hasFunction());
		$trace->setFunction('handle');
		$this->assertSame('handle', $trace->getFunction());

		$trace->setFunction(null);
		$this->assertFalse($trace->hasFunction());
	}

	public function testHasFunction(): void
	{
		$this->assertTrue((new TraceObject($this->functionFrame()))->hasFunction());
		$this->assertFalse((new TraceObject([]))->hasFunction());
	}

	public function testGetFunction(): void
	{
		$this->assertSame('array_map', (new TraceObject($this->functionFrame()))->getFunction());
	}

	public function testHasDeclaringClass(): void
	{
		$this->assertTrue((new TraceObject($this->methodFrame()))->hasDeclaringClass());
		$this->assertFalse((new TraceObject($this->functionFrame()))->hasDeclaringClass());
	}

	public function testGetDeclaringClassIsNullWhenTheFrameHasNoClass(): void
	{
		$this->assertNull((new TraceObject($this->functionFrame()))->getDeclaringClass());
	}

	/**
	 * getDeclaringClass() is declared `?string` but used to return the
	 * ReflectionClass it stores, so it raised a TypeError on every class+method
	 * frame - which is every frame parseClass() touches.
	 */
	public function testGetDeclaringClassReturnsTheClassNameNotTheReflection(): void
	{
		$declaringClass = (new TraceObject($this->methodFrame()))->getDeclaringClass();

		$this->assertIsString($declaringClass);
		$this->assertSame(TraceObjectFixture::class, $declaringClass);
	}

	public function testSetDeclaringClass(): void
	{
		$trace = new TraceObject($this->functionFrame());

		$this->assertFalse($trace->hasDeclaringClass());
		$trace->setDeclaringClass(new \ReflectionClass(TraceObjectFixture::class));
		$this->assertTrue($trace->hasDeclaringClass());
	}

	/**
	 * Write a throwaway source file with the given line ending and return its path.
	 */
	private function sourceFixture(string $lineEnding): string
	{
		$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'trace_source_' . uniqid() . '.php';
		file_put_contents($path, implode($lineEnding, ['<?php', '$alpha = 1;', '$beta = 2;', '$gamma = 3;']));
		$this->fixtures[] = $path;

		return $path;
	}

	public function testRenderCodeBlock(): void
	{
		$rendered = TraceObject::renderCodeBlock($this->sourceFixture(self::CARRIAGE_RETURN_LINE_FEED), 3, false);

		$this->assertIsString($rendered);
		$this->assertStringContainsString('<span class="code-line-number">3</span>', $rendered);
		$this->assertStringContainsString('data-highlight="true"', $rendered);
		$this->assertSame(4, substr_count($rendered, '<div'), 'One div per source line.');
	}

	public function testRenderCodeBlockReturnsNullForAMissingFile(): void
	{
		$this->assertNull(TraceObject::renderCodeBlock(__DIR__ . '/no-such-file.php'));
	}

	public function testRenderCodeBlockWithoutAHighlightLineMarksNothing(): void
	{
		$rendered = (string) TraceObject::renderCodeBlock($this->sourceFixture(self::CARRIAGE_RETURN_LINE_FEED), null, false);

		$this->assertStringNotContainsString('data-highlight="true"', $rendered);
	}

	/**
	 * renderCodeBlock() splits on "\r\n" and "<br>" only, so a file with LF
	 * endings collapses into a single numbered line. The working tree is LF
	 * (.gitattributes: `* text=auto eol=lf`), which means every rendered stack
	 * frame in this repository is one line long. Pinned here so the day it is
	 * fixed, this test says so.
	 */
	public function testRenderCodeBlockDoesNotSplitLineFeedOnlyFiles(): void
	{
		$rendered = (string) TraceObject::renderCodeBlock($this->sourceFixture(self::LINE_FEED), 3, false);

		$this->assertSame(1, substr_count($rendered, '<div'));
		$this->assertStringNotContainsString('<span class="code-line-number">3</span>', $rendered);
	}

	public function testParseComments(): void
	{
		$comment = "/**\n * First line.\n *\n * Second line.\n * @param string \$value\n * @return void\n */";

		$this->assertSame(
			['First line.', 'Second line.'],
			array_values(TraceObject::parseComments($comment)),
			'Annotation lines and blank lines are dropped; the summary survives.'
		);
	}

	public function testTrimComment(): void
	{
		$this->assertSame('First line.', TraceObject::trimComment('   * First line.   '));
		$this->assertSame('', TraceObject::trimComment(' */ '));
		$this->assertSame('@param string $value', TraceObject::trimComment(' * @param string $value'));
	}

	public function testArgumentToString(): void
	{
		$trace = new TraceObject([]);

		// Each inner array is flattened with the same ", " the outer join uses, so a
		// two-element type/name pair arrives as two comma-separated tokens rather than
		// as "type $name". That is what the renderer prints today.
		$this->assertSame('string, $a, int, $b', $trace->argumentToString([['string', '$a'], ['int', '$b']]));
		$this->assertSame('null', $trace->argumentToString(['']), 'An empty argument renders as the literal null.');
		$this->assertSame('', $trace->argumentToString([]));
	}

	public function testParseTracePreservesFunctionOnlyFrames(): void
	{
		$objects = ReflectionHandler::parseTrace([$this->functionFrame()]);

		$this->assertCount(1, $objects);
		$this->assertSame('array_map', $objects[0]->getFunction());
	}

	public function testParseTraceDiscardsEmptyFrames(): void
	{
		$objects = ReflectionHandler::parseTrace([[], $this->functionFrame(), []]);

		$this->assertCount(1, $objects);
	}

	public function testParseTraceOfAnEmptyBacktraceIsEmpty(): void
	{
		$this->assertSame([], ReflectionHandler::parseTrace([]));
	}
}

#[Attribute(Attribute::TARGET_METHOD)]
final class TraceObjectMarker
{
	public function __construct(public readonly string $label)
	{
	}
}

class TraceObjectFixture
{
	/**
	 * The fixture method the trace tests reflect over.
	 *
	 * @param string $first  First parameter.
	 * @param int    $second Second parameter.
	 *
	 * @return string
	 */
	#[TraceObjectMarker('marked')]
	public function annotatedMethod(string $first, int $second): string
	{
		return $first . $second;
	}

	public function undocumentedMethod(string $only): void
	{
	}

	public function untypedMethod($value)
	{
		return $value;
	}
}
