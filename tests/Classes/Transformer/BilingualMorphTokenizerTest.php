<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Transformer;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Transformer\BilingualMorphTokenizer;
use Clover\Classes\Transformer\CharacterTokenizer;
use PHPUnit\Framework\TestCase;

final class BilingualMorphTokenizerTest extends TestCase
{
	private CharacterTokenizer $tokenizer;

	protected function setUp(): void
	{
		$this->tokenizer = new CharacterTokenizer();
	}

	public function testTokenizer($model = null): void
	{
		$tokenizer = new BilingualMorphTokenizer();
		$sample = '그 상품을 온라인에서 구매했습니다';

		$ids = $tokenizer->encode($sample);
		$this->assertNotEmpty($ids);
		$this->assertLessThanOrEqual($tokenizer->getVocabSize(), max($ids) + 1);
		$this->assertSame($sample, $tokenizer->decode($ids));

		$firstVocabularySize = $tokenizer->getVocabSize();
		$sample = '[JP]alpha[KO]beta';

		$ids = $tokenizer->encode($sample);
		$this->assertNotEmpty($ids);
		$this->assertLessThanOrEqual($tokenizer->getVocabSize(), max($ids) + 1);
		$this->assertSame($sample, $tokenizer->decode($ids));
		$this->assertGreaterThanOrEqual($firstVocabularySize, $tokenizer->getVocabSize());
	}
}
