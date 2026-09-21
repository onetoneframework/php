<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Debug;

use Clover\Classes\Debug\ProfilerTraceFlowNormalizer;
use PHPUnit\Framework\TestCase;

final class ProfilerTraceFlowNormalizerTest extends TestCase
{
	public function testNormalizeSkipsNonObjectsAndPreservesOriginalTraceIndexAsStep(): void
	{
		$normalizer = new ProfilerTraceFlowNormalizer();
		$trace = $this->trace(text: 'Service::run()', file: '/app/Service.php', line: 12);

		$flow = $normalizer->normalize(['ignored', null, $trace]);

		$this->assertSame([
			[
				'step' => 2,
				'call' => 'Service::run()',
				'location' => '/app/Service.php:12',
			],
		], $flow);
	}

	public function testNormalizeUsesFileWithoutLineWhenTraceHasNoLine(): void
	{
		$trace = $this->trace(text: 'run()', file: '/app/file.php');

		$flow = (new ProfilerTraceFlowNormalizer())->normalize([$trace]);

		$this->assertSame('/app/file.php', $flow[0]['location']);
	}

	public function testNormalizeFallsBackToClassWhenFileIsUnavailable(): void
	{
		$trace = $this->trace(text: 'run()', class: 'App\\Worker');

		$flow = (new ProfilerTraceFlowNormalizer())->normalize([$trace]);

		$this->assertSame('App\\Worker', $flow[0]['location']);
	}

	public function testNormalizeUsesUnknownFallbacksForObjectsWithoutTraceMethods(): void
	{
		$flow = (new ProfilerTraceFlowNormalizer())->normalize([new \stdClass()]);

		$this->assertSame([
			[
				'step' => 0,
				'call' => '(unknown call)',
				'location' => '(unknown location)',
			],
		], $flow);
	}

	public function testNormalizeUsesFallbacksWhenOptionalTraceValuesAreReportedAbsent(): void
	{
		$trace = $this->trace();

		$flow = (new ProfilerTraceFlowNormalizer())->normalize([$trace]);

		$this->assertSame('(unknown call)', $flow[0]['call']);
		$this->assertSame('(unknown location)', $flow[0]['location']);
	}

	public function testNormalizeLimitsReturnedFlowToMaximumSteps(): void
	{
		$traces = [
			$this->trace(text: 'one()'),
			$this->trace(text: 'two()'),
			$this->trace(text: 'three()'),
		];

		$flow = (new ProfilerTraceFlowNormalizer())->normalize($traces, 2);

		$this->assertCount(2, $flow);
		$this->assertSame(['one()', 'two()'], array_column($flow, 'call'));
	}

	private function trace(
		?string $text = null,
		?string $file = null,
		?int $line = null,
		?string $class = null
	): object {
		return new class($text, $file, $line, $class) {
			public function __construct(
				private readonly ?string $text,
				private readonly ?string $file,
				private readonly ?int $line,
				private readonly ?string $class
			) {
			}

			public function hasText(): bool
			{
				return $this->text !== null;
			}

			public function getText(): ?string
			{
				return $this->text;
			}

			public function hasFile(): bool
			{
				return $this->file !== null;
			}

			public function getFile(): ?string
			{
				return $this->file;
			}

			public function hasLine(): bool
			{
				return $this->line !== null;
			}

			public function getLine(): ?int
			{
				return $this->line;
			}

			public function hasClass(): bool
			{
				return $this->class !== null;
			}

			public function getClass(): ?string
			{
				return $this->class;
			}
		};
	}
}
