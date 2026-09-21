<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Transformer;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Transformer\CharacterTokenizer;
use PHPUnit\Framework\TestCase;

final class CharacterTokenizerTest extends TestCase
{
    private CharacterTokenizer $tokenizer;

    protected function setUp(): void
    {
        $this->tokenizer = new CharacterTokenizer();
    }

    public function testKoreanEncodingDecoding(): void
    {
        $text = "가나다";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertSame($text, $decoded);
        $this->assertSame([5, 24, 7, 24, 8, 24], $encoded);
    }

    public function testEnglishEncodingDecoding(): void
    {
        $text = "Hello World";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertSame($text, $decoded);
        $this->assertSame([90, 61, 68, 68, 71, 4, 105, 71, 74, 68, 60], $encoded);
    }

    public function testJapaneseEncodingDecoding(): void
    {
        $text = "こんにちは";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertSame($text, $decoded);
    }

    public function testMixedMultilingual(): void
    {
        $text = "Hello 안녕 こんにちは";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertSame($text, $decoded);
    }

    public function testUnknownCharacters(): void
    {
        $text = "♞♛♜";
        $encoded = $this->tokenizer->encode($text);
        $this->assertContainsOnly('int', $encoded);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertIsString($decoded);
    }

    public function testSpecialTokensExist(): void
    {
        $this->assertIsInt($this->tokenizer->getStartId());
        $this->assertIsInt($this->tokenizer->getEndId());
        $this->assertGreaterThan(0, $this->tokenizer->getVocabSize());
    }
    
        public function testVocabularySizeIsStable()
    {
        $size = $this->tokenizer->getVocabSize();
        $this->assertGreaterThan(100, $size, "Vocabulary size should be large enough");
        $this->assertLessThan(1000, $size, "Vocabulary size should be bounded");
    }

    public function testStartAndEndTokenIdsExist()
    {
        $startId = $this->tokenizer->getStartId();
        $endId = $this->tokenizer->getEndId();

        $this->assertIsInt($startId);
        $this->assertIsInt($endId);
        $this->assertNotEquals($startId, $endId);
    }

    public function testHangulDecomposition()
    {
        // 가 = ㄱ + ㅏ
        $encoded = $this->tokenizer->encode("가");
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertEquals("가", $decoded);
    }

    public function testHangulWithFinalConsonant()
    {
        // 각 = ㄱ + ㅏ + ㄱ
        $encoded = $this->tokenizer->encode("각");
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertEquals("각", $decoded);
    }

    public function testMultipleHangulSyllables()
    {
        $text = "한글";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertEquals($text, $decoded);
    }

    public function testEnglishCharacters()
    {
        $text = "abcXYZ";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertEquals($text, $decoded);
    }

    public function testMixedKoreanEnglish()
    {
        $text = "가a나b";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertEquals($text, $decoded);
    }

    public function testSpaceHandling()
    {
        $text = "한 글";
        $encoded = $this->tokenizer->encode($text);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertEquals($text, $decoded);
    }

    public function testUnknownCharacterFallback()
    {
        $text = "🚀";
        $encoded = $this->tokenizer->encode($text);
        $unkId = $this->tokenizer->getVocabSize() - 1; // fallback to last index if unknown

        foreach ($encoded as $id) {
            $this->assertIsInt($id);
        }

        $decoded = $this->tokenizer->decode($encoded);
        $this->assertNotEquals($text, $decoded); // unknown character should not match
    }

    public function testDecodeIgnoresSpecialTokens()
    {
        $startId = $this->tokenizer->getStartId();
        $endId = $this->tokenizer->getEndId();
        $encoded = array_merge([$startId], $this->tokenizer->encode("가"), [$endId]);
        $decoded = $this->tokenizer->decode($encoded);
        $this->assertEquals("가", $decoded);
    }

}
