<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Array;

use Clover\Classes\ArraySummarizer;
use PHPUnit\Framework\TestCase;

final class ArraySummarizerTest extends TestCase
{
	public function testSequentialValuesSummarizeAndCompileBackToMatchingRegex(): void
	{
		$summarizer = new ArraySummarizer();
		$values = ['item1', 'item2', 'item3'];

		$summary = $summarizer->summarizeArray($values);
		$regex = $summarizer->summaryToRegex($summary);

		$this->assertSame('[item][1-3]', $summary);
		foreach ($values as $value) {
			$this->assertSame(1, preg_match($regex, $value));
		}
		$this->assertSame(0, preg_match($regex, 'other1'));
	}

	public function testMixedScalarValuesAreIncludedAndObjectsAndNullAreIgnored(): void
	{
		$summarizer = new ArraySummarizer();

		$summary = $summarizer->summarizeArray([true, false, 2, null, new \stdClass()]);
		$regex = $summarizer->summaryToRegex($summary);

		foreach (['1', '0', '2'] as $value) {
			$this->assertSame(1, preg_match($regex, $value));
		}
	}

	public function testEmptySummaryProducesNoRegex(): void
	{
		$summarizer = new ArraySummarizer();

		$this->assertSame('', $summarizer->summarizeArray([]));
		$this->assertSame('', $summarizer->summaryToRegex('   '));
	}
}
