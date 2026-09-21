<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Text;

use Clover\Classes\Word\WordBranchSentenceBuilder;
use PHPUnit\Framework\TestCase;

final class WordBranchSentenceBuilderTest extends TestCase
{
	public function testBranchSelectionBuildsSentenceAndReturnsNextChoices(): void
	{
		$builder = new WordBranchSentenceBuilder([
			'__start__' => ['Hello'],
			'Hello' => ['world!', 'there'],
			'there' => ['friend.'],
		]);

		$this->assertSame(['Hello'], $builder->start());
		$this->assertSame(['world!', 'there'], $builder->choose('Hello'));
		$this->assertSame(['friend.'], $builder->choose('there'));
		$this->assertSame([], $builder->choose('friend.'));
		$this->assertSame('Hello there friend.', $builder->getSentence());
	}

	public function testSentinelIsRejectedWithoutChangingSentence(): void
	{
		$builder = new WordBranchSentenceBuilder(['__start__' => ['Hello']]);

		$this->assertSame([], $builder->choose('<[/OBJECT/]>'));
		$this->assertSame('', $builder->getSentence());
	}

	public function testMissingStartBranchReturnsEmptyArray(): void
	{
		$builder = new WordBranchSentenceBuilder([]);

		$this->assertSame([], $builder->start());
	}
}
