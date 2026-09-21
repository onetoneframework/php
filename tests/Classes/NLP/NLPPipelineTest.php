<?php

declare(strict_types=1);

namespace Clover\Tests\NLP;

use Clover\Classes\NLP\Components\EnglishComponent;
use Clover\Classes\NLP\NLP;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class NLPPipelineTest extends TestCase
{
	public function testFrequencyAfterStopWordRemovalHandlesEmptyTokenSet(): void
	{
		$result = NLP::of('the and a')
			->use(new EnglishComponent())
			->normalize()
			->tokenize()
			->removeStopWords()
			->frequency()
			->analyze();

		$this->assertSame([], $result->tokens());
		$this->assertSame([], $result->frequency());
	}

	public function testAnalyzeIncludesStructuredMetadata(): void
	{
		$result = NLP::of('Hello world. Hello again.')
			->use(new EnglishComponent())
			->normalize()
			->tokenize()
			->sentences()
			->frequency()
			->detectScript()
			->analyze();

		$this->assertSame('English', $result->language);
		$this->assertSame('en', $result->languageCode());
		$this->assertTrue($result->hasMetadata('component'));
		$this->assertTrue($result->hasMetadata('step_details'));
		$this->assertTrue($result->hasPipelineStep('tokenize'));
		$this->assertSame(6, $result->pipelineCount());
		$this->assertNotEmpty($result->stepDetails());
	}

	public function testResetPreservesComponentAndClearsDerivedState(): void
	{
		$pipeline = NLP::of('hello world')
			->use(new EnglishComponent())
			->tokenize()
			->frequency();

		$pipeline->reset();

		$result = $pipeline
			->tokenize()
			->analyze();

		$this->assertSame('English', $result->language);
		$this->assertSame(['hello', 'world'], $result->tokens());
		$this->assertSame([], $result->frequency());
		$this->assertSame(['tokenize'], $result->pipelineSteps());
	}

	public function testReplaceTextClearsPreviousRuntimeState(): void
	{
		$pipeline = NLP::of('cat cat dog')
			->use(new EnglishComponent())
			->tokenize()
			->frequency();

		$pipeline->replaceText('bird fish');

		$result = $pipeline
			->tokenize()
			->analyze();

		$this->assertSame(['bird', 'fish'], $result->tokens());
		$this->assertSame([], $result->frequency());
		$this->assertSame('bird fish', $result->raw);
		$this->assertSame('bird fish', $result->processed);
	}

	public function testLanguageComponentInfoExposesSupportedFeatures(): void
	{
		$componentInfo = (new EnglishComponent())->info();

		$this->assertSame('English', $componentInfo['name']);
		$this->assertSame('en', $componentInfo['iso']);
		$this->assertContains('tokenize', $componentInfo['features']);
		$this->assertContains('pos_tag', $componentInfo['features']);
	}

	public function testResultRequireThrowsForMissingState(): void
	{
		$this->expectException(OutOfBoundsException::class);

		NLP::of('hello world')
			->use(new EnglishComponent())
			->tokenize()
			->analyze()
			->require('frequency');
	}
}
