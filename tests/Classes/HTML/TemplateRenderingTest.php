<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\HTML;

use Clover\Classes\HTML\Template;
use PHPUnit\Framework\TestCase;

final class TemplateRenderingTest extends TestCase
{
	public function testRenderStringEscapesNormalEchoAndLeavesRawEchoUntouched(): void
	{
		$template = new Template();
		$html = $template->renderString('{{ $value }}|{!! $value !!}', ['value' => '<b>bold</b>']);

		$this->assertSame('&lt;b&gt;bold&lt;/b&gt;|<b>bold</b>', (string) $html);
	}

	public function testSharedAssignedAndCallTimeDataMergeWithExpectedPrecedence(): void
	{
		$template = new Template();
		$template->share('value', 'shared')->assign('value', 'assigned');

		$this->assertSame('assigned', (string) $template->renderString('{{ $value }}'));
		$this->assertSame('call', (string) $template->renderString('{{ $value }}', ['value' => 'call']));

		$template->clear();
		$this->assertSame('shared', (string) $template->renderString('{{ $value }}'));
	}

	public function testBuiltInAndCustomFiltersAreApplied(): void
	{
		$template = new Template();
		$template->filter('wrap', static fn($value, $left = '[', $right = ']') => $left . $value . $right);

		$this->assertSame('ONETONE', (string) $template->renderString('{{ $name|upper }}', ['name' => 'onetone']));
		$this->assertSame('[core]', (string) $template->renderString('{{ $name|wrap }}', ['name' => 'core']));
	}

	public function testAutoEscapeCanBeDisabledOrCustomized(): void
	{
		$template = new Template();
		$template->setAutoEscape(false);
		$this->assertSame('<em>x</em>', (string) $template->renderString('{{ $value }}', ['value' => '<em>x</em>']));

		$template = new Template();
		$template->setEscapeHandler(static fn($value) => '[' . $value . ']');
		$this->assertSame('[hello]', (string) $template->renderString('{{ $value }}', ['value' => 'hello']));
	}
}
