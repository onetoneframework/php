<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Debug;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Debug\ProfilerHttpUrlPreview;
use PHPUnit\Framework\TestCase;

class ProfilerHttpUrlPreviewTest extends TestCase
{
	public function testForTimelineTrimsWhitespace(): void
	{
		$this->assertSame('https://example.com/a', ProfilerHttpUrlPreview::forTimeline("  https://example.com/a  "));
	}

	public function testForTimelineRespectsMaxLength(): void
	{
		$long = str_repeat('a', ProfilerHttpUrlPreview::DEFAULT_MAX_LENGTH + 40);
		$out = ProfilerHttpUrlPreview::forTimeline($long);
		$this->assertSame(ProfilerHttpUrlPreview::DEFAULT_MAX_LENGTH, strlen($out));
		$this->assertStringEndsWith('...', $out);
	}
}
