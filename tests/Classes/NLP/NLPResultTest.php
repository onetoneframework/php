<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\NLP;

use Clover\Classes\NLP\Result\NLPResult;
use PHPUnit\Framework\TestCase;

final class NLPResultTest extends TestCase
{
	public function testMetricsStateMetadataAndPipelineAccessors(): void
	{
		$result = $this->createResult();

		$this->assertSame(4, $result->tokenCount());
		$this->assertSame(2, $result->sentenceCount());
		$this->assertSame(2, $result->pipelineCount());
		$this->assertSame(2.0, $result->avgTokensPerSentence());
		$this->assertSame(0.75, $result->typeTokenRatio());
		$this->assertSame(['cat' => 2, 'dog' => 1], $result->topTokens(2));
		$this->assertSame([], $result->topTokens(0));
		$this->assertSame(['cat' => 'NOUN'], $result->posTags());
		$this->assertSame([['cat', 'cat']], $result->ngrams());
		$this->assertSame(['Latin' => 13], $result->scripts());
		$this->assertSame(42, $result->get('custom'));
		$this->assertSame('fallback', $result->get('missing', 'fallback'));
		$this->assertTrue($result->hasState('frequency'));
		$this->assertTrue($result->hasMetadata('language_code'));
		$this->assertSame('en', $result->languageCode());
		$this->assertSame('ExampleComponent', $result->componentClass());
		$this->assertTrue($result->hasPipelineStep('frequency'));
		$this->assertFalse($result->hasPipelineStep('sentiment'));
	}

	public function testSummaryAndJsonSerializationRemainConsistent(): void
	{
		$result = $this->createResult();
		$summary = $result->summary();

		$this->assertSame('English', $summary['language']);
		$this->assertSame('en', $summary['language_code']);
		$this->assertTrue($summary['has_frequency']);
		$this->assertTrue($summary['has_pos_tags']);
		$this->assertTrue($summary['has_scripts']);
		$this->assertSame($result->toArray(), $result->jsonSerialize());
		$this->assertSame($result->toArray(), json_decode($result->toJson(), true));
		$this->assertSame($result->toJson(), (string) $result);
	}

	public function testEmptyResultHasZeroMetricsAndTypedFallbacks(): void
	{
		$result = new NLPResult('', '', [], [], [
			'frequency' => 'invalid',
			'pos_tags' => 123,
		], [], 'Unknown', [
			'language_code' => 123,
			'component_class' => false,
			'step_details' => 'invalid',
		]);

		$this->assertSame(0.0, $result->avgTokensPerSentence());
		$this->assertSame(0.0, $result->typeTokenRatio());
		$this->assertSame([], $result->frequency());
		$this->assertSame([], $result->posTags());
		$this->assertSame([], $result->stepDetails());
		$this->assertSame('', $result->languageCode());
		$this->assertNull($result->componentClass());
	}

	private function createResult(): NLPResult
	{
		return new NLPResult(
			'Cat cat dog. Bird.',
			'cat cat dog. bird.',
			['cat', 'cat', 'dog', 'bird'],
			['cat cat dog.', 'bird.'],
			[
				'frequency' => ['cat' => 2, 'dog' => 1, 'bird' => 1],
				'pos_tags' => ['cat' => 'NOUN'],
				'ngrams' => [['cat', 'cat']],
				'scripts' => ['Latin' => 13],
				'custom' => 42,
			],
			['tokenize', 'frequency'],
			'English',
			[
				'language_code' => 'en',
				'component_class' => 'ExampleComponent',
				'step_details' => [['index' => 1, 'step' => 'frequency']],
			]
		);
	}
}
