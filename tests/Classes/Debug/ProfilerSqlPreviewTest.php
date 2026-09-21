<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Debug;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Debug\ProfilerSqlPreview;
use PHPUnit\Framework\TestCase;

class ProfilerSqlPreviewTest extends TestCase
{
	public function testForTimelineCollapsesWhitespace(): void
	{
		$sql = "SELECT  *\nFROM   t\nWHERE  id = 1";
		$this->assertSame('SELECT * FROM t WHERE id = 1', ProfilerSqlPreview::forTimeline($sql));
	}

	public function testForTimelineRespectsMaxLength(): void
	{
		$long = str_repeat('a', ProfilerSqlPreview::DEFAULT_MAX_LENGTH + 50);
		$out = ProfilerSqlPreview::forTimeline($long);
		$this->assertSame(ProfilerSqlPreview::DEFAULT_MAX_LENGTH, strlen($out));
		$this->assertStringEndsWith('...', $out);
	}
}
