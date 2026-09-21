<?php

declare(strict_types=1);

namespace Clover\Tests\NLP;

use Clover\Classes\NLP\Components\KoreanComponent;
use Clover\Classes\NLP\NLP;
use PHPUnit\Framework\TestCase;

final class KoreanComponentTest extends TestCase
{
    private KoreanComponent $ko;

    protected function setUp(): void
    {
        $this->ko = new KoreanComponent();
    }

    // ─── Identity ─────────────────────────────────────────────────────────────

    public function testIdentity(): void
    {
        $this->assertSame('Korean', $this->ko->name());
        $this->assertSame('ko', $this->ko->isoCode());
    }

    // ─── Tokenization ─────────────────────────────────────────────────────────

    public function testBasicEojeolTokenization(): void
    {
        $tokens = $this->ko->tokenize('자연어 처리는 좋습니다');
        $this->assertSame(['자연어', '처리는', '좋습니다'], $tokens);
    }

    public function testTokenizationStripsTrailingPunctuation(): void
    {
        $tokens = $this->ko->tokenize('안녕하세요. 반갑습니다!');
        $this->assertContains('안녕하세요', $tokens);
        $this->assertContains('반갑습니다', $tokens);
        $this->assertNotContains('안녕하세요.', $tokens);
    }

    public function testEmptyStringReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->ko->tokenize(''));
        $this->assertSame([], $this->ko->tokenize('   '));
    }

    // ─── Sentence splitting ───────────────────────────────────────────────────

    public function testSentenceSplitOnPunctuation(): void
    {
        $text      = '자연어 처리는 중요합니다. 인공지능이 발전하고 있습니다.';
        $sentences = $this->ko->splitSentences($text);
        $this->assertCount(2, $sentences);
    }

    public function testSentenceSplitSingleSentence(): void
    {
        $text      = '안녕하세요';
        $sentences = $this->ko->splitSentences($text);
        $this->assertCount(1, $sentences);
        $this->assertSame('안녕하세요', $sentences[0]);
    }

    // ─── Stop words ───────────────────────────────────────────────────────────

    public function testRemoveKoreanParticles(): void
    {
        $tokens   = ['자연어', '처리', '는', '중요', '합니다'];
        $filtered = $this->ko->removeStopWords($tokens);
        $this->assertNotContains('는', $filtered);
        $this->assertContains('자연어', $filtered);
    }

    public function testStopWordsIncludeCommonParticles(): void
    {
        $stops = $this->ko->stopWords();
        foreach (['이', '가', '은', '는', '을', '를', '의', '에서', '그리고', '하지만'] as $p) {
            $this->assertContains($p, $stops, "Stop word '{$p}' missing");
        }
    }

    // ─── Stemming ─────────────────────────────────────────────────────────────

    /** @dataProvider koreanStemProvider */
    public function testStemStripsParticlesAndEndings(string $input, string $expected): void
    {
        $result = $this->ko->stem([$input]);
        $this->assertSame($expected, $result[0],
            "ko->stem('{$input}') expected '{$expected}', got '{$result[0]}'");
    }

    public static function koreanStemProvider(): array
    {
        return [
            '처리는 → 처리'   => ['처리는',   '처리'],
            '합니다 → 합'     => ['합니다',   '합'],
            '있습니다 → 있'   => ['있습니다', '있'],
            '연구를 → 연구'   => ['연구를',   '연구'],
            '기술에서 → 기술' => ['기술에서', '기술'],
            // Tokens shorter than suffix: must not corrupt
            '의 → 의'         => ['의',       '의'],
        ];
    }

    public function testStemDoesNotCorruptShortTokens(): void
    {
        $result = $this->ko->stem(['나', '는']);
        // Short tokens should return unchanged (not empty string)
        $this->assertNotSame('', $result[0]);
        $this->assertNotSame('', $result[1]);
    }

    // ─── Jamo decomposition ───────────────────────────────────────────────────

    public function testJamoDecompositionSimpleSyllable(): void
    {
        // 가 = ㄱ + ㅏ (no jongseong)
        $jamo = $this->ko->decomposeJamo('가');
        $this->assertSame(['ㄱ', 'ㅏ'], $jamo);
    }

    public function testJamoDecompositionWithJongseong(): void
    {
        // 닭 = ㄷ + ㅏ + ㄺ
        $jamo = $this->ko->decomposeJamo('닭');
        $this->assertSame(['ㄷ', 'ㅏ', 'ㄺ'], $jamo);
    }

    public function testJamoDecompositionMultiSyllable(): void
    {
        // 한국 = ㅎ+ㅏ+ㄴ + ㄱ+ㅜ+ㄱ
        $jamo = $this->ko->decomposeJamo('한국');
        $this->assertCount(6, $jamo);
        $this->assertSame('ㅎ', $jamo[0]);
    }

    public function testJamoDecompositionPassthroughNonHangul(): void
    {
        $jamo = $this->ko->decomposeJamo('A1');
        $this->assertSame(['A', '1'], $jamo);
    }

    public function testTokensToJamo(): void
    {
        $map = $this->ko->tokensToJamo(['가나', '다라']);
        $this->assertArrayHasKey('가나', $map);
        $this->assertIsString($map['가나']);
        $this->assertStringContainsString('ㄱ', $map['가나']);
    }

    // ─── Script detection ─────────────────────────────────────────────────────

    public function testDetectHangulScript(): void
    {
        $scripts = $this->ko->detectScript('한국어 NLP');
        $this->assertArrayHasKey('Hangul', $scripts);
        $this->assertArrayHasKey('Latin', $scripts);
        $this->assertGreaterThan(0, $scripts['Hangul']);
    }

    public function testDetectHanjaScript(): void
    {
        $scripts = $this->ko->detectScript('大韓民國');
        $this->assertArrayHasKey('Hanja', $scripts);
    }

    // ─── POS tagging ──────────────────────────────────────────────────────────

    public function testPosTagParticle(): void
    {
        $tags = $this->ko->posTag(['이', '가', '는']);
        $this->assertSame('PART', $tags['이']);
        $this->assertSame('PART', $tags['가']);
    }

    public function testPosTagNumber(): void
    {
        $tags = $this->ko->posTag(['123', '일이삼']);
        $this->assertSame('NUM', $tags['123']);
        $this->assertSame('NUM', $tags['일이삼']);
    }

    // ─── Full pipeline ────────────────────────────────────────────────────────

    public function testFullPipeline(): void
    {
        $text = '자연어 처리는 컴퓨터가 인간의 언어를 이해하는 기술입니다.';

        $result = NLP::of($text)
            ->use(new KoreanComponent())
            ->normalize()
            ->tokenize()
            ->removeStopWords()
            ->stem()
            ->frequency()
            ->analyze();

        $this->assertSame('Korean', $result->language);
        $this->assertGreaterThan(0, $result->tokenCount());
        $this->assertIsArray($result->frequency());
    }

    public function testNGrams(): void
    {
        $result = NLP::of('자연어 처리 기술')
            ->use(new KoreanComponent())
            ->tokenize()
            ->ngrams(2)
            ->analyze();

        $grams = $result->ngrams();
        $this->assertNotEmpty($grams);
        $this->assertCount(2, $grams[0]); // bigrams
    }
}
