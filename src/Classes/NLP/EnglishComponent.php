<?php

declare(strict_types=1);

namespace Clover\Classes\NLP\Components;

use function strlen;
use function in_array;

/**
 * EnglishComponent — English language NLP spec.
 *
 * Features:
 *  - Whitespace + punctuation tokenization
 *  - Sentence boundary detection (. ! ? …)
 *  - Porter-lite stemmer (suffix stripping)
 *  - Comprehensive stop-word list
 *  - Script detection (Latin / Digit / Punct / Other)
 *  - Rule-based POS tagging (Noun / Verb / Adjective / Adverb / Det / Prep)
 *  - Unicode NFC normalization + ASCII lowercasing
 */
class EnglishComponent extends LanguageComponent
{
    // -------------------------------------------------------------------------
    // Identity
    // -------------------------------------------------------------------------

    public function name(): string
    {
        return 'English';
    }
    public function isoCode(): string
    {
        return 'en';
    }

    // -------------------------------------------------------------------------
    // Tokenization
    // -------------------------------------------------------------------------

    public function tokenize(string $text): array
    {
        // Normalize smart quotes / dashes
        $text = strtr($text, [
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{2013}" => '-',
            "\u{2014}" => '-'
        ]);

        // Split on whitespace and strip leading/trailing punctuation per token
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            if ($part === '')
                continue;

            // ── Contraction handling ────────────────────────────────────────
            // Absorb the suffix, emit ONLY the base word.
            //   "It's"    → "It"     (don't keep "'s" as a stray token)
            //   "don't"   → "don"
            //   "they've" → "they"
            //   "Alan's"  → "Alan"
            if (preg_match("/^(.+?)'(t|s|re|ve|ll|d|m)([.,!?;:]*)$/i", $part, $m)) {
                $base = preg_replace('/^[^\w]+|[^\w]+$/', '', $m[1]) ?? $m[1];
                if ($base !== '') {
                    $tokens[] = $base;
                }
                continue;
            }

            // Strip surrounding punctuation from regular words
            $clean = preg_replace('/^[^\w]+|[^\w]+$/', '', $part) ?? $part;
            if ($clean !== '') {
                $tokens[] = $clean;
            }
        }

        return array_values(array_filter($tokens));
    }

    // -------------------------------------------------------------------------
    // Sentence splitting
    // -------------------------------------------------------------------------

    public function splitSentences(string $text): array
    {
        // Protect common abbreviations
        $abbrevs = [
            'Mr',
            'Mrs',
            'Ms',
            'Dr',
            'Prof',
            'Sr',
            'Jr',
            'vs',
            'etc',
            'i.e',
            'e.g',
            'viz',
            'Fig',
            'vol'
        ];

        $protected = $text;
        foreach ($abbrevs as $ab) {
            $protected = str_replace($ab . '.', $ab . '##DOT##', $protected);
        }

        // Split on sentence-ending punctuation followed by whitespace + uppercase
        $split = preg_split('/(?<=[.!?…])\s+(?=[A-Z"\'])/', $protected) ?: [$protected];

        return array_map(
            fn(string $s) => trim(str_replace('##DOT##', '.', $s)),
            array_filter($split, fn(string $s) => $s !== '')
        );
    }

    // -------------------------------------------------------------------------
    // Normalization
    // -------------------------------------------------------------------------

    public function normalize(string $text): string
    {
        // NFC unicode normalization
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::NFC) ?: $text;
        }
        return mb_strtolower($text, 'UTF-8');
    }

    // -------------------------------------------------------------------------
    // Stop words
    // -------------------------------------------------------------------------

    public function stopWords(): array
    {
        return [
            'a',
            'about',
            'above',
            'after',
            'again',
            'against',
            'all',
            'am',
            'an',
            'and',
            'any',
            'are',
            "aren't",
            'as',
            'at',
            'be',
            'because',
            'been',
            'before',
            'being',
            'below',
            'between',
            'both',
            'but',
            'by',
            "can't",
            'cannot',
            'could',
            "couldn't",
            'did',
            "didn't",
            'do',
            'does',
            "doesn't",
            'doing',
            "don't",
            'down',
            'during',
            'each',
            'few',
            'for',
            'from',
            'further',
            'get',
            'got',
            'had',
            "hadn't",
            'has',
            "hasn't",
            'have',
            "haven't",
            'having',
            'he',
            "he'd",
            "he'll",
            "he's",
            'her',
            'here',
            "here's",
            'hers',
            'herself',
            'him',
            'himself',
            'his',
            'how',
            "how's",
            'i',
            "i'd",
            "i'll",
            "i'm",
            "i've",
            'if',
            'in',
            'into',
            'is',
            "isn't",
            'it',
            "it's",
            'its',
            'itself',
            "let's",
            'me',
            'more',
            'most',
            "mustn't",
            'my',
            'myself',
            'no',
            'nor',
            'not',
            'of',
            'off',
            'on',
            'once',
            'only',
            'or',
            'other',
            'ought',
            'our',
            'ours',
            'ourselves',
            'out',
            'over',
            'own',
            's',
            'same',
            "shan't",
            'she',
            "she'd",
            "she'll",
            "she's",
            'should',
            "shouldn't",
            'so',
            'some',
            'such',
            't',
            'than',
            'that',
            "that's",
            'the',
            'their',
            'theirs',
            'them',
            'themselves',
            'then',
            'there',
            "there's",
            'these',
            'they',
            "they'd",
            "they'll",
            "they're",
            "they've",
            'this',
            'those',
            'through',
            'to',
            'too',
            'under',
            'until',
            'up',
            'very',
            'was',
            "wasn't",
            'we',
            "we'd",
            "we'll",
            "we're",
            "we've",
            'were',
            "weren't",
            'what',
            "what's",
            'when',
            "when's",
            'where',
            "where's",
            'which',
            'while',
            'who',
            "who's",
            'whom',
            'why',
            "why's",
            'will',
            'with',
            "won't",
            'would',
            "wouldn't",
            'you',
            "you'd",
            "you'll",
            "you're",
            "you've",
            'your',
            'yours',
            'yourself',
            'yourselves',
        ];
    }

    public function removeStopWords(array $tokens): array
    {
        $stops = array_flip($this->stopWords());
        return array_values(
            array_filter($tokens, fn(string $t) => !isset($stops[mb_strtolower($t, 'UTF-8')]))
        );
    }

    // -------------------------------------------------------------------------
    // Stemming (Porter-lite suffix stripping)
    // -------------------------------------------------------------------------

    public function stem(array $tokens): array
    {
        return array_map([$this, 'stemWord'], $tokens);
    }

    private function stemWord(string $word): string
    {
        $w = mb_strtolower($word, 'UTF-8');

        if (mb_strlen($w) <= 2) {
            return $w;
        }

        // ── Step 1a: plural / third-person-singular -s ────────────────────
        if (str_ends_with($w, 'sses')) {
            $w = substr($w, 0, -2);
        }   // caresses → caress
        elseif (str_ends_with($w, 'ies')) {
            $w = substr($w, 0, -2);
        }   // ponies   → poni
        elseif (str_ends_with($w, 's') && !str_ends_with($w, 'ss')) {
            $w = substr($w, 0, -1);
        }   // cats     → cat

        // ── Step 1b: -ed / -ing ───────────────────────────────────────────
        if (str_ends_with($w, 'eed')) {
            if (mb_strlen($w) > 4) {
                $w = substr($w, 0, -1);                 // agreed → agre
            }
        } elseif (preg_match('/[aeiou].*(ed|ing)$/i', $w, $m)) {
            $suffix = $m[1];
            $base = substr($w, 0, -strlen($suffix));
            $baseLen = mb_strlen($base);

            // FIX ①: minimum base length — e.g. "used"→"us"(2), "turing"→"tur"(3-skip)
            // Require at least 3 chars AND the result must be a plausible root.
            // We use 4 as the safe floor so single-consonant bases like "tur" are skipped.
            if ($suffix === 'ed' && $baseLen === 2 && preg_match('/^[aeiou][^aeiou]$/i', $base)) {
                $w = $base . 'e';
            } elseif ($suffix === 'ing' && $baseLen <= 3 && !preg_match('/([^aeiou])\1$/i', $base)) {
                $w = $w;
            } elseif ($baseLen < 4) {
                // Only allow 3-char bases for a whitelist of clean cases (go, see, etc.)
                // Otherwise restore the un-trimmed form.
                $w = $baseLen >= 3 && preg_match('/[aeiou]/', $base) ? $base : $w;
            } elseif (str_ends_with($base, 'at') || str_ends_with($base, 'bl') || str_ends_with($base, 'iz')) {
                $w = $base . 'e';                                            // hopping → hope-ish
            } elseif (preg_match('/([^aeiou])\1$/i', $base)) {
                // FIX ②: skip "ss" — "process" must NOT become "proces"
                //         also require the stripped result is ≥ 4 chars
                $doubled = strtolower(substr($base, -1));
                $stripped = substr($base, 0, -1);
                if ($doubled !== 's' && (mb_strlen($stripped) >= 4 || ($doubled === 'n' && mb_strlen($stripped) >= 3))) {
                    $w = $stripped;                                          // matting → mat
                } else {
                    $w = $base;                                              // process → process ✓
                }
            } else {
                $w = $base;
            }
        }

        // ── Step 1c: -y → -i (only when stem ≥ 4 chars) ─────────────────
        if (str_ends_with($w, 'y') && mb_strlen($w) >= 4 && !preg_match('/[aeiou]$/', substr($w, 0, -1))) {
            $w = substr($w, 0, -1) . 'i';                                   // happy → happi
        }

        // ── Step 2: derivational suffixes ─────────────────────────────────
        // Ordered longest-first; guard minimum remaining stem length.
        $step2 = [
            'ational' => 'ate',    // relational → relate
            'tional' => 'tion',   // conditional → condition
            'enci' => 'ence',   // valenci → valence
            'anci' => 'ance',   // hesitanci → hesitance
            'izer' => 'ize',    // digitizer → digitize
            'iser' => 'ise',
            'alism' => 'al',     // conformalism → conformal
            'ation' => 'ate',    // predication → predicate
            'ator' => 'ate',    // operator → operate
            'ness' => '',       // goodness → good
            'ful' => '',       // hopeful → hope
            'ous' => '',       // glamorous → glamor
            'ive' => '',       // effective → effect
            'ize' => '',       // digitize → digit
        ];
        foreach ($step2 as $suffix => $replacement) {
            $sLen = strlen($suffix);
            if (str_ends_with($w, $suffix) && mb_strlen($w) > $sLen + 3) {
                $w = substr($w, 0, -$sLen) . $replacement;
                break;
            }
        }

        return $w;
    }

    // -------------------------------------------------------------------------
    // Script detection
    // -------------------------------------------------------------------------

    public function detectScript(string $text): array
    {
        $counts = [
            'Latin' => 0,
            'Digit' => 0,
            'Whitespace' => 0,
            'Punctuation' => 0,
            'Other' => 0
        ];

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($chars as $ch) {
            $ord = mb_ord($ch, 'UTF-8');
            if (($ord >= 0x0041 && $ord <= 0x007A) || ($ord >= 0x00C0 && $ord <= 0x024F)) {
                $counts['Latin']++;
            } elseif ($ord >= 0x0030 && $ord <= 0x0039) {
                $counts['Digit']++;
            } elseif (ctype_space($ch)) {
                $counts['Whitespace']++;
            } elseif (ctype_punct($ch) || in_array($ord, [0x2019, 0x2018, 0x201C, 0x201D])) {
                $counts['Punctuation']++;
            } else {
                $counts['Other']++;
            }
        }

        return array_filter($counts);
    }

    // -------------------------------------------------------------------------
    // POS Tagging (rule-based)
    // -------------------------------------------------------------------------

    public function posTag(array $tokens): array
    {
        $determiners = ['the', 'a', 'an', 'this', 'that', 'these', 'those', 'my', 'your', 'his', 'her', 'its', 'our', 'their'];
        $prepositions = ['in', 'on', 'at', 'to', 'for', 'with', 'by', 'from', 'of', 'about', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'between', 'under'];
        $conjunctions = ['and', 'or', 'but', 'so', 'yet', 'for', 'nor', 'although', 'because', 'since', 'unless', 'while'];
        $modalVerbs = ['can', 'could', 'will', 'would', 'shall', 'should', 'may', 'might', 'must', 'ought'];

        $result = [];
        foreach ($tokens as $token) {
            $lower = mb_strtolower($token, 'UTF-8');

            if (in_array($lower, $determiners)) {
                $tag = 'DET';
            } elseif (in_array($lower, $prepositions)) {
                $tag = 'PREP';
            } elseif (in_array($lower, $conjunctions)) {
                $tag = 'CONJ';
            } elseif (in_array($lower, $modalVerbs)) {
                $tag = 'MODAL';
            } elseif (preg_match('/^-?\d+(\.\d+)?$/', $token)) {
                $tag = 'NUM';
            } elseif (preg_match('/ly$/i', $token)) {
                $tag = 'ADV';
            } elseif (preg_match('/(ful|ous|ive|able|ible|al|ent|ant|ic|ish|ed)$/i', $token)) {
                $tag = 'ADJ';
            } elseif (preg_match('/(ing|ize|ise|ify|ate|en)$/i', $token)) {
                $tag = 'VERB';
            } elseif (preg_match('/(tion|sion|ment|ness|ity|ance|ence|er|or|ist|ism|ship)$/i', $token)) {
                $tag = 'NOUN';
            } elseif (preg_match('/^[A-Z]/', $token)) {
                $tag = 'PROPN';   // Proper noun (capitalized)
            } else {
                $tag = 'NOUN';    // Default fallback
            }

            $result[$token] = $tag;
        }

        return $result;
    }
}
