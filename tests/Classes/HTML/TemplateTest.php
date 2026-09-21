<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\HTML;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\HTML\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testTemplate(): void
	{
		$template = new Template();
		$template->useStyle('latte');

		$html = $template->renderString('
<ul n:if="count($menu) > 1" class="menu">
	<li n:foreach="$menu as $item">
		<a n:tag-if="$item->href" href="{$item->href}">
			{$item->caption}
		</a>
	</li>
</ul>
', [
			'menu' => [
				(object) ['href' => '/home', 'caption' => 'Home'],
				(object) ['href' => '/', 'caption' => 'About'],
				(object) ['href' => '/contact', 'caption' => 'Contact'],
			]
		]);

		$this->assertEquals('<ul class="menu"><li><a href="/home">Home</a></li><li><a href="/">About</a></li><li><a href="/contact">Contact</a></li></ul>', $html->removeNewLines());
	}
}
