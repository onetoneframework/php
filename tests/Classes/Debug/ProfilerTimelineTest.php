<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Debug;

use Clover\Classes\Debug\ProfilerTimeline;
use PHPUnit\Framework\TestCase;

final class ProfilerTimelineTest extends TestCase
{
	public function testNewTimelineStartsEmpty(): void
	{
		$this->assertSame([], (new ProfilerTimeline())->getTimelineFlow());
	}

	public function testEmptyStartTokenAndUnknownFinishTokenAreIgnored(): void
	{
		$timeline = new ProfilerTimeline();

		$timeline->startSpanToken('', 'ignored()');
		$timeline->finishSpanToken('missing');

		$this->assertSame([], $timeline->getTimelineFlow());
	}

	public function testFinishedSpanRecordsCallLocationAndRuntimeMetrics(): void
	{
		$timeline = new ProfilerTimeline();
		$timeline->startSpanToken('span-1', 'Repository::find()', '/app/Repository.php:42');
		$timeline->finishSpanToken('span-1');

		$flow = $timeline->getTimelineFlow();

		$this->assertCount(1, $flow);
		$this->assertSame(0, $flow[0]['step']);
		$this->assertSame('Repository::find()', $flow[0]['call']);
		$this->assertSame('/app/Repository.php:42', $flow[0]['location']);
		$this->assertGreaterThanOrEqual(0.0, $flow[0]['durationMs']);
		$this->assertIsInt($flow[0]['memoryUsageBytes']);
		$this->assertIsInt($flow[0]['memoryPeakBytes']);
		$this->assertIsInt($flow[0]['memoryDeltaBytes']);
		$this->assertGreaterThanOrEqual($flow[0]['memoryUsageBytes'], $flow[0]['memoryPeakBytes']);
	}

	public function testSpansAreOrderedByFinishTimeRatherThanStartTime(): void
	{
		$timeline = new ProfilerTimeline();
		$timeline->startSpanToken('first', 'first()');
		$timeline->startSpanToken('second', 'second()');

		$timeline->finishSpanToken('second');
		$timeline->finishSpanToken('first');

		$flow = $timeline->getTimelineFlow();

		$this->assertSame([0, 1], array_column($flow, 'step'));
		$this->assertSame(['second()', 'first()'], array_column($flow, 'call'));
	}

	public function testFinishingSameTokenTwiceOnlyRecordsItOnce(): void
	{
		$timeline = new ProfilerTimeline();
		$timeline->startSpanToken('only', 'onlyOnce()');

		$timeline->finishSpanToken('only');
		$timeline->finishSpanToken('only');

		$this->assertCount(1, $timeline->getTimelineFlow());
	}

	public function testStartingSameTokenAgainReplacesTheActiveSpanMetadata(): void
	{
		$timeline = new ProfilerTimeline();
		$timeline->startSpanToken('same', 'old()', 'old.php:1');
		$timeline->startSpanToken('same', 'new()', 'new.php:2');

		$timeline->finishSpanToken('same');

		$flow = $timeline->getTimelineFlow();
		$this->assertSame('new()', $flow[0]['call']);
		$this->assertSame('new.php:2', $flow[0]['location']);
	}

	public function testResetClearsFinishedAndActiveSpans(): void
	{
		$timeline = new ProfilerTimeline();
		$timeline->startSpanToken('finished', 'finished()');
		$timeline->finishSpanToken('finished');
		$timeline->startSpanToken('active', 'active()');

		$timeline->reset();
		$timeline->finishSpanToken('active');

		$this->assertSame([], $timeline->getTimelineFlow());
	}
}
