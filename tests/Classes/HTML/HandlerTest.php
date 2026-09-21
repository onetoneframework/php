<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\HTML;

use Clover\Classes\HTML\Handler;
use PHPUnit\Framework\TestCase;

final class HandlerTest extends TestCase
{
	public function testInputTagBuildersRenderSelectionAndTextareaValues(): void
	{
		$this->assertSame(
			'<select name="size"><option value="small">small</option><option selected value="large">large</option></select>',
			Handler::getInputTag('option', 'size', 'large', ['small', 'large'])
		);
		$this->assertSame(
			'<textarea rows="4" cols="50" name="message" value="hello">hello</textarea>',
			Handler::getInputTag('textarea', 'message', 'hello')
		);
	}

	public function testGenerateElementSupportsStringBooleanAndValuedAttributes(): void
	{
		$this->assertSame('<div class="box" hidden>content</div>', Handler::generateElement('div', 'content', [
			'class' => 'box',
			'hidden' => true,
		]));
		$this->assertSame('<span data-id="7">x</span>', Handler::generateElement('span', 'x', 'data-id="7"'));
		$this->assertSame('<audio src="voice.wav" controls>', Handler::generateAudioTag('voice.wav', ['controls' => true]));
	}

	public function testParameterAndEntityHelpersHandleSimpleValues(): void
	{
		$this->assertSame("'alpha','beta'", Handler::generateParameter(['first' => 'alpha', 'second' => 'beta']));
		$this->assertSame('Tom & Jerry', Handler::decodeEntity('Tom &amp; Jerry'));
		$this->assertNull(Handler::decodeEntity(null));
	}
}
