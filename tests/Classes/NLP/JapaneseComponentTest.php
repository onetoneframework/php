<?php

declare(strict_types=1);

namespace Clover\Tests\NLP;

use Clover\Classes\NLP\Components\JapaneseComponent;
use Clover\Classes\NLP\NLP;
use PHPUnit\Framework\TestCase;

final class JapaneseComponentTest extends TestCase
{
    private JapaneseComponent $ja;

    protected function setUp(): void
    {
        $this->ja = new JapaneseComponent();
    }

    // ─── Identity ─────────────────────────────────────────────────────────────

    public function testIdentity(): void
    {
        $this->assertSame('Japanese', $this->ja->name());
        $this->assertSame('ja', $this->ja->isoCode());
    }

    // ─── Tokenization ─────────────────────────────────────────────────────────

    public function testTokenizationByScriptBoundary(): void
    {
        // Kanji | Hiragana | Katakana: each block is its own token
        $tokens = $this->ja->tokenize('自然言語処理は面白い');
        $this->assertNotEmpty($tokens);
        // 'は' is hiragana — should be separate from surrounding kanji
        $this->assertContains('は', $tokens);
    }

    public function testTokenizationSplitsOnSentencePunctuation(): void
    {
        $tokens = $this->ja->tokenize('これはテストです。');
        // '。' should be its own token
        $this->assertContains('。', $tokens);
    }

    public function testTokenizationHandlesMixedScript(): void
    {
        $tokens = $this->ja->tokenize('AIの技術');
        $this->assertContains('AI', $tokens);
        $this->assertContains('の', $tokens);
        $this->assertContains('技術', $tokens);
    }

    public function testTokenizationEmptyString(): void
    {
        $this->assertSame([], $this->ja->tokenize(''));
    }

    // ─── Sentence splitting ───────────────────────────────────────────────────

    public function testSentenceSplitOnKuoten(): void
    {
        $text      = '自然言語処理は重要です。AIの研究が進んでいます。';
        $sentences = $this->ja->splitSentences($text);
        $this->assertCount(2, $sentences);
    }

    public function testSentenceSplitOnExclamation(): void
    {
        $text      = 'すごい！本当にすごい！';
        $sentences = $this->ja->splitSentences($text);
        $this->assertCount(2, $sentences);
    }

    public function testSentenceSplitSingleSentence(): void
    {
        $text      = '日本語です';
        $sentences = $this->ja->splitSentences($text);
        $this->assertCount(1, $sentences);
    }

    // ─── Normalization ────────────────────────────────────────────────────────

    public function testFullwidthToHalfwidth(): void
    {
        $normal = $this->ja->normalize('ＡＢＣ１２３');
        $this->assertSame('abc123', $normal);
    }

    public function testIdeographicSpaceNormalized(): void
    {
        $normal = $this->ja->normalize("こんにちは\u{3000}世界");
        $this->assertStringContainsString(' ', $normal); // ideographic space → regular space
    }

    // ─── Stop words ───────────────────────────────────────────────────────────

    public function testRemoveJapaneseParticles(): void
    {
        $tokens   = ['自然', 'は', '言語', 'を', '処理'];
        $filtered = $this->ja->removeStopWords($tokens);
        $this->assertNotContains('は', $filtered);
        $this->assertNotContains('を', $filtered);
        $this->assertContains('自然', $filtered);
        $this->assertContains('処理', $filtered);
    }

    public function testStopWordsIncludeCommonParticles(): void
    {
        $stops = $this->ja->stopWords();
        foreach (['は', 'が', 'を', 'に', 'の', 'です', 'ます', 'そして'] as $p) {
            $this->assertContains($p, $stops, "Stop word '{$p}' missing");
        }
    }

    // ─── Stemming ─────────────────────────────────────────────────────────────

    /** @dataProvider japaneseStemProvider */
    public function testStemStripsVerbEndings(string $input, string $expected): void
    {
        $result = $this->ja->stem([$input]);
        $this->assertSame($expected, $result[0],
            "ja->stem('{$input}') expected '{$expected}', got '{$result[0]}'");
    }

    public static function japaneseStemProvider(): array
    {
        return [
            'います → い'           => ['います',   'い'],
            'ています → てい'       => ['ています', 'てい'],
            'ました → ま'           => ['ました',   'ま'],
            'たい → た'             => ['たい',     'た'],
            'ない → な'             => ['ない',     'な'],
            // Short tokens: must pass through unchanged
            'は → は'               => ['は',       'は'],
        ];
    }

    // ─── Script detection ─────────────────────────────────────────────────────

    public function testDetectHiragana(): void
    {
        $scripts = $this->ja->detectScript('これはてすとです');
        $this->assertArrayHasKey('Hiragana', $scripts);
        $this->assertGreaterThan(0, $scripts['Hiragana']);
    }

    public function testDetectKatakana(): void
    {
        $scripts = $this->ja->detectScript('コンピューター');
        $this->assertArrayHasKey('Katakana', $scripts);
        $this->assertGreaterThan(0, $scripts['Katakana']);
    }

    public function testDetectKanji(): void
    {
        $scripts = $this->ja->detectScript('自然言語処理');
        $this->assertArrayHasKey('Kanji', $scripts);
        $this->assertGreaterThan(0, $scripts['Kanji']);
    }

    public function testDetectMixedScripts(): void
    {
        $scripts = $this->ja->detectScript('AIの技術はすごいです');
        $this->assertArrayHasKey('Kanji',    $scripts);
        $this->assertArrayHasKey('Hiragana', $scripts);
        $this->assertArrayHasKey('Latin',    $scripts);
    }

    // ─── Katakana → Hiragana ──────────────────────────────────────────────────

    /** @dataProvider katakanaToHiraganaProvider */
    public function testKatakanaToHiragana(string $kata, string $expected): void
    {
        $this->assertSame($expected, $this->ja->katakanaToHiragana($kata));
    }

    public static function katakanaToHiraganaProvider(): array
    {
        return [
            'ア → あ' => ['ア', 'あ'],
            'カ → か' => ['カ', 'か'],
            'コンピュータ → こんぴゅーた' => ['コンピュータ', 'こんぴゅーた'],
            'Non-kata unchanged' => ['ABC', 'ABC'],
            'Mixed' => ['テストtest', 'てすとtest'],
        ];
    }

    // ─── Hiragana → Romaji ────────────────────────────────────────────────────

    /** @dataProvider hiraganaToRomajiProvider */
    public function testHiraganaToRomaji(string $hira, string $expected): void
    {
        $this->assertSame($expected, $this->ja->hiraganaToRomaji($hira));
    }

    public static function hiraganaToRomajiProvider(): array
    {
        return [
            'あ → a'                    => ['あ', 'a'],
            'か → ka'                   => ['か', 'ka'],
            'し → shi'                  => ['し', 'shi'],
            'つ → tsu'                  => ['つ', 'tsu'],
            'ありがとう → arigatou'     => ['ありがとう', 'arigatou'],
            'にほんご → nihongo'        => ['にほんご', 'nihongo'],
            'Non-hira passthrough'      => ['ABC', 'ABC'],
        ];
    }

    // ─── POS tagging ──────────────────────────────────────────────────────────

    public function testPosTagParticle(): void
    {
        $tags = $this->ja->posTag(['は', 'が', 'を']);
        $this->assertSame('PART', $tags['は']);
        $this->assertSame('PART', $tags['が']);
        $this->assertSame('PART', $tags['を']);
    }

    public function testPosTagAuxiliary(): void
    {
        $tags = $this->ja->posTag(['です', 'ます', 'ない']);
        $this->assertSame('AUX', $tags['です']);
        $this->assertSame('AUX', $tags['ます']);
    }

    public function testPosTagForeignLatin(): void
    {
        $tags = $this->ja->posTag(['AI', 'NLP', 'PHP']);
        $this->assertSame('FOREIGN', $tags['AI']);
    }

    public function testPosTagKatakanaIsNoun(): void
    {
        $tags = $this->ja->posTag(['コンピューター', 'チャットボット']);
        $this->assertSame('NOUN', $tags['コンピューター']);
    }

    // ─── N-grams (character-level for CJK) ───────────────────────────────────

    public function testCharacterLevelNgramsForKanji(): void
    {
        $grams = $this->ja->ngrams(['自然言語'], 2);
        // Should produce character bigrams for the kanji block
        $this->assertNotEmpty($grams);
        // Each gram is an array of 2 chars
        $charGrams = array_filter($grams, fn($g) => count($g) === 2 && mb_strlen($g[0]) === 1);
        $this->assertNotEmpty($charGrams);
    }

    // ─── Full pipeline ────────────────────────────────────────────────────────

    public function testFullPipeline(): void
    {
        $text = '自然言語処理は、コンピューターが人間の言語を理解するための技術です。';

        $result = NLP::of($text)
            ->use(new JapaneseComponent())
            ->normalize()
            ->tokenize()
            ->removeStopWords()
            ->stem()
            ->frequency()
            ->posTag()
            ->sentences()
            ->detectScript()
            ->analyze();

        $this->assertSame('Japanese', $result->language);
        $this->assertGreaterThan(0, $result->tokenCount());
        $this->assertGreaterThan(0, count($result->scripts()));
        $this->assertIsArray($result->posTags());
    }
}
