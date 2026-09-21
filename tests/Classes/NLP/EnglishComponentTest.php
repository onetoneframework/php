<?php

declare(strict_types=1);

namespace Clover\Tests\NLP;

use Clover\Classes\NLP\Components\EnglishComponent;
use Clover\Classes\NLP\NLP;
use PHPUnit\Framework\TestCase;

final class EnglishComponentTest extends TestCase
{
    private EnglishComponent $en;

    protected function setUp(): void
    {
        $this->en = new EnglishComponent();
    }

    // ─── Identity ─────────────────────────────────────────────────────────────

    public function testIdentity(): void
    {
        $this->assertSame('English', $this->en->name());
        $this->assertSame('en', $this->en->isoCode());
    }

    // ─── Tokenization ─────────────────────────────────────────────────────────

    public function testBasicTokenization(): void
    {
        $tokens = $this->en->tokenize('Hello world');
        $this->assertSame(['Hello', 'world'], $tokens);
    }

    public function testTokenizationStripsTrailingPunctuation(): void
    {
        $tokens = $this->en->tokenize('Hello, world! How are you?');
        $this->assertContains('Hello', $tokens);
        $this->assertContains('world', $tokens);
        $this->assertNotContains('Hello,', $tokens);
        $this->assertNotContains('world!', $tokens);
    }

    /** @dataProvider contractionProvider */
    public function testContractionAbsorbsSuffix(string $input, array $expected): void
    {
        $tokens = $this->en->tokenize($input);
        $this->assertSame($expected, $tokens);
    }

    public static function contractionProvider(): array
    {
        return [
            "possessive 's"   => ["Alan's research",   ['Alan', 'research']],
            "is contraction"  => ["It's fast",          ['It', 'fast']],
            "negative 't"     => ["don't stop",         ['don', 'stop']],
            "have 've"        => ["they've arrived",    ['they', 'arrived']],
            "will 'll"        => ["she'll go",          ['she', 'go']],
            "had 'd"          => ["he'd left",          ['he', 'left']],
            "am 'm"           => ["I'm ready",          ['I', 'ready']],
            "are 're"         => ["we're done",         ['we', 'done']],
        ];
    }

    public function testNoJunkApostropheTokens(): void
    {
        // Bug regression: "'s", "'t", "'re" must NOT appear as tokens
        $tokens = $this->en->tokenize("It's used in search engines, chatbots, and translation systems.");
        foreach ($tokens as $t) {
            $this->assertStringStartsNotWith("'", $t, "Stray contraction suffix token: '{$t}'");
        }
    }

    // ─── Stemmer ──────────────────────────────────────────────────────────────

    /** @dataProvider stemmerProvider */
    public function testStemmer(string $input, string $expected): void
    {
        $result = $this->en->stem([$input]);
        $this->assertSame($expected, $result[0], "stem('{$input}') should be '{$expected}', got '{$result[0]}'");
    }

    public static function stemmerProvider(): array
    {
        return [
            // Regression cases (previously broken)
            'used → use'          => ['used',       'use'],
            'processing → process'=> ['processing', 'process'],
            'Turing → turing'     => ['Turing',     'turing'],    // proper noun: no chopping
            'enables → enable'     => ['enables',    'enable'],     // -s + -e strip
            'computers → computer'=> ['computers',  'computer'],
            // Standard Porter-lite
            'caresses → caress'     => ['caresses',   'caress'],
            'ponies → poni'       => ['ponies',      'poni'],
            'cats → cat'          => ['cats',        'cat'],
            'running → run'       => ['running',     'run'],
            'matting → matt'       => ['matting',     'matt'],
            'happiness → happi'   => ['happiness',   'happi'],
            'goodness → good'     => ['goodness',    'good'],
            'hopeful → hope'      => ['hopeful',     'hope'],
            // Must NOT over-stem short bases
            'go → go'             => ['go',          'go'],
            'be → be'             => ['be',          'be'],
        ];
    }

    public function testStemmerNeverProduces2CharBaseFromLongWord(): void
    {
        // Regression: "used" was producing "us" (2 chars)
        $result = $this->en->stem(['used']);
        $this->assertGreaterThanOrEqual(3, mb_strlen($result[0]),
            "stem('used') must be ≥ 3 chars, got '{$result[0]}'");
    }

    public function testStemmerProcessDoesNotLoseS(): void
    {
        // Regression: "processing" was producing "proces" (double-s strip bug)
        $result = $this->en->stem(['processing']);
        $this->assertSame('process', $result[0]);
    }

    // ─── Stop words ───────────────────────────────────────────────────────────

    public function testRemoveStopWords(): void
    {
        $tokens   = ['the', 'quick', 'brown', 'fox', 'and', 'a', 'dog'];
        $filtered = $this->en->removeStopWords($tokens);
        $this->assertNotContains('the', $filtered);
        $this->assertNotContains('and', $filtered);
        $this->assertNotContains('a', $filtered);
        $this->assertContains('quick', $filtered);
        $this->assertContains('fox', $filtered);
    }

    public function testRemoveStopWordsCaseInsensitive(): void
    {
        $tokens   = ['The', 'AND', 'A', 'Fox'];
        $filtered = $this->en->removeStopWords($tokens);
        $this->assertNotContains('The', $filtered);
        $this->assertNotContains('AND', $filtered);
        $this->assertContains('Fox', $filtered);
    }

    public function testStopWordsListIsNonEmpty(): void
    {
        $this->assertGreaterThan(50, count($this->en->stopWords()));
    }

    // ─── Sentence splitting ───────────────────────────────────────────────────

    public function testSentenceSplitBasic(): void
    {
        $text      = 'Hello world. How are you? I am fine!';
        $sentences = $this->en->splitSentences($text);
        $this->assertCount(3, $sentences);
    }

    public function testSentenceSplitProtectsAbbreviations(): void
    {
        $text      = 'Dr. Alan Turing was born in 1912. He pioneered computer science.';
        $sentences = $this->en->splitSentences($text);
        // "Dr." must NOT create a false sentence boundary
        $this->assertCount(2, $sentences);
        $this->assertStringContainsString('Dr', $sentences[0]);
        $this->assertStringContainsString('Alan Turing', $sentences[0]);
    }

    // ─── Script detection ─────────────────────────────────────────────────────

    public function testDetectScriptEnglish(): void
    {
        $scripts = $this->en->detectScript('Hello World 123!');
        $this->assertArrayHasKey('Latin', $scripts);
        $this->assertArrayHasKey('Digit', $scripts);
        $this->assertGreaterThan(0, $scripts['Latin']);
    }

    // ─── POS tagging ──────────────────────────────────────────────────────────

    public function testPosTagBasic(): void
    {
        $tags = $this->en->posTag(['the', 'quick', 'running', 'quickly', 'London']);
        $this->assertSame('DET',   $tags['the']);
        $this->assertSame('NOUN',   $tags['quick']);
        $this->assertSame('VERB',  $tags['running']);
        $this->assertSame('ADV',   $tags['quickly']);
        $this->assertSame('PROPN', $tags['London']);
    }

    // ─── N-grams ──────────────────────────────────────────────────────────────

    public function testBigrams(): void
    {
        $bigrams = $this->en->ngrams(['a', 'b', 'c', 'd'], 2);
        $this->assertCount(3, $bigrams);
        $this->assertSame(['a', 'b'], $bigrams[0]);
        $this->assertSame(['c', 'd'], $bigrams[2]);
    }

    public function testTrigrams(): void
    {
        $trigrams = $this->en->ngrams(['a', 'b', 'c', 'd'], 3);
        $this->assertCount(2, $trigrams);
        $this->assertSame(['a', 'b', 'c'], $trigrams[0]);
    }

    // ─── Full pipeline ────────────────────────────────────────────────────────

    public function testFullPipelineNoBugTokens(): void
    {
        $text = "Natural language processing enables computers to understand human text. "
              . "It's used in search engines, chatbots, and translation systems. "
              . "Dr. Alan Turing pioneered theoretical computer science.";

        $result = NLP::of($text)
            ->use(new EnglishComponent())
            ->normalize()
            ->tokenize()
            ->removeStopWords()
            ->stem()
            ->analyze();

        $tokens = $result->tokens();

        // Regression: none of these bad tokens should appear
        $this->assertNotContains("'s",  $tokens, "Stray contraction suffix must not appear");
        $this->assertNotContains("'t",  $tokens, "Stray contraction suffix must not appear");
        $this->assertNotContains("'re", $tokens, "Stray contraction suffix must not appear");
        $this->assertNotContains('us',  $tokens, "Over-stemmed 'used'→'us' must not appear");

        // Process must not lose its final 's'
        foreach ($tokens as $t) {
            $this->assertNotSame('proces', $t, "'process' must not stem to 'proces'");
        }

        // Proper nouns must keep reasonable length (Turing must not become 'tur')
        foreach ($tokens as $t) {
            if ($t === 'tur') {
                $this->fail("'Turing' over-stemmed to 'tur'");
            }
        }
    }

    public function testAnalyzeReturnsCorrectLanguage(): void
    {
        $result = NLP::of('hello world')
            ->use(new EnglishComponent())
            ->tokenize()
            ->analyze();

        $this->assertSame('English', $result->language);
    }

    public function testFrequencyCountsCorrectly(): void
    {
        $result = NLP::of('cat cat dog cat dog bird')
            ->use(new EnglishComponent())
            ->tokenize()
            ->frequency()
            ->analyze();

        $freq = $result->frequency();
        $this->assertSame(3, $freq['cat']);
        $this->assertSame(2, $freq['dog']);
        $this->assertSame(1, $freq['bird']);
    }
}
