<?php

declare(strict_types=1);

namespace Clover\Classes\NLP\Components;

/**
 * KoreanComponent — Korean (한국어) language NLP spec.
 *
 * Features:
 *  - Eojeol-based tokenization (space-delimited morpheme groups)
 *  - Jamo decomposition (자모 분리: 가 → ㄱ + ㅏ)
 *  - Sentence boundary detection (다. 요. 까. 냐. 아. 어. 등)
 *  - Korean-specific stop words
 *  - Suffix/postposition stripping (조사 제거)
 *  - Script detection: Hangul / Latin / Digit / Hanja / Whitespace / Punct
 *  - Basic POS tagging using particle/suffix patterns
 *  - NFC normalization
 */
class KoreanComponent extends LanguageComponent
{
    // Unicode ranges
    private const HANGUL_SYLLABLE_START = 0xAC00;
    private const HANGUL_SYLLABLE_END = 0xD7A3;
    private const HANGUL_JAMO_START = 0x1100;
    private const HANGUL_JAMO_END = 0x11FF;
    private const HANGUL_COMPAT_START = 0x3130;
    private const HANGUL_COMPAT_END = 0x318F;
    private const HANJA_START = 0x4E00;
    private const HANJA_END = 0x9FFF;

    // Jamo tables for decomposition
    private const CHOSEONG = [
        'ㄱ',
        'ㄲ',
        'ㄴ',
        'ㄷ',
        'ㄸ',
        'ㄹ',
        'ㅁ',
        'ㅂ',
        'ㅃ',
        'ㅅ',
        'ㅆ',
        'ㅇ',
        'ㅈ',
        'ㅉ',
        'ㅊ',
        'ㅋ',
        'ㅌ',
        'ㅍ',
        'ㅎ',
    ];
    private const JUNGSEONG = [
        'ㅏ',
        'ㅐ',
        'ㅑ',
        'ㅒ',
        'ㅓ',
        'ㅔ',
        'ㅕ',
        'ㅖ',
        'ㅗ',
        'ㅘ',
        'ㅙ',
        'ㅚ',
        'ㅛ',
        'ㅜ',
        'ㅝ',
        'ㅞ',
        'ㅟ',
        'ㅠ',
        'ㅡ',
        'ㅢ',
        'ㅣ',
    ];
    private const JONGSEONG = [
        '',
        'ㄱ',
        'ㄲ',
        'ㄳ',
        'ㄴ',
        'ㄵ',
        'ㄶ',
        'ㄷ',
        'ㄹ',
        'ㄺ',
        'ㄻ',
        'ㄼ',
        'ㄽ',
        'ㄾ',
        'ㄿ',
        'ㅀ',
        'ㅁ',
        'ㅂ',
        'ㅄ',
        'ㅅ',
        'ㅆ',
        'ㅇ',
        'ㅈ',
        'ㅊ',
        'ㅋ',
        'ㅌ',
        'ㅍ',
        'ㅎ',
    ];

    // -------------------------------------------------------------------------
    // Identity
    // -------------------------------------------------------------------------

    public function name(): string
    {
        return 'Korean';
    }
    public function isoCode(): string
    {
        return 'ko';
    }

    // -------------------------------------------------------------------------
    // Tokenization (eojeol = space-delimited unit)
    // -------------------------------------------------------------------------

    public function tokenize(string $text): array
    {
        // Split on whitespace
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        $tokens = [];

        foreach ($parts as $part) {
            if ($part === '')
                continue;

            // Strip trailing punctuation (마침표, 쉼표, 느낌표, 물음표…)
            $clean = preg_replace('/[.,!?;:。、！？…·]+$/', '', $part) ?? $part;
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
        // Korean sentence endings: 다, 요, 까, 냐, 아, 어, 죠, 네, 군, 구나, 지 + sentence-ending punctuation
        $pattern = '/(?<=[다요까냐아어죠네군지][.!?。！？])\s+/u';
        $parts = preg_split($pattern, $text) ?: [$text];

        // Also split on explicit punctuation sequences
        $result = [];
        foreach ($parts as $part) {
            $sub = preg_split('/(?<=[.!?。！？…])\s+(?=[가-힣A-Z"\'])/u', $part) ?: [$part];
            foreach ($sub as $s) {
                $trimmed = trim($s);
                if ($trimmed !== '')
                    $result[] = $trimmed;
            }
        }

        return $result ?: [trim($text)];
    }

    // -------------------------------------------------------------------------
    // Normalization
    // -------------------------------------------------------------------------

    public function normalize(string $text): string
    {
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::NFC) ?: $text;
        }
        // Korean does not have case; keep as-is but collapse whitespace
        return preg_replace('/\s+/', ' ', trim($text)) ?? $text;
    }

    // -------------------------------------------------------------------------
    // Stop words (조사, 접속사, 의존명사 등)
    // -------------------------------------------------------------------------

    public function stopWords(): array
    {
        return [
            // 조사 (particles)
            '이',
            '가',
            '은',
            '는',
            '을',
            '를',
            '의',
            '와',
            '과',
            '에',
            '에서',
            '에게',
            '에게서',
            '로',
            '으로',
            '와',
            '과',
            '도',
            '만',
            '까지',
            '부터',
            '한테',
            '한테서',
            '께',
            '께서',
            '이라',
            '라',
            '이며',
            '며',
            '이나',
            '나',
            '이든',
            '든',
            '이랑',
            '랑',
            // 접속사 (conjunctions)
            '그리고',
            '그러나',
            '그래서',
            '하지만',
            '또한',
            '또는',
            '혹은',
            '그런데',
            '그러므로',
            '따라서',
            '즉',
            '다만',
            '단',
            // 의존명사 / 보조용언
            '것',
            '수',
            '때',
            '곳',
            '중',
            '등',
            '및',
            '관련',
            '대한',
            '위한',
            '통한',
            '의한',
            // 기타 (misc)
            '이다',
            '있다',
            '없다',
            '하다',
            '되다',
            '않다',
            '못하다',
        ];
    }

    public function removeStopWords(array $tokens): array
    {
        $stops = array_flip($this->stopWords());
        return array_values(
            array_filter($tokens, fn(string $t) => !isset($stops[$t]))
        );
    }

    // -------------------------------------------------------------------------
    // Stemming: strip common postpositions / verb endings (조사/어미 제거)
    // -------------------------------------------------------------------------

    public function stem(array $tokens): array
    {
        return array_map([$this, 'stemToken'], $tokens);
    }

    private function stemToken(string $token): string
    {
        // Order matters: longest suffix first
        $suffixes = [
            // Verb endings (어미)
            '습니다',
            '입니다',
            '었습니다',
            '겠습니다',
            '았습니다',
            'ㄹ게요',
            '을게요',
            '겠어요',
            '어요',
            '아요',
            '이에요',
            '예요',
            '습니까',
            '었습니까',
            '었어',
            '았어',
            '겠어',
            '었다',
            '았다',
            '는다',
            'ㄴ다',
            '는데',
            '은데',
            '겠다',
            '어서',
            '아서',
            '으면',
            '면',
            '지만',
            '지요',
            '죠',
            '네요',
            '군요',
            // Noun particles (조사)
            '에서',
            '에게',
            '한테',
            '한테서',
            '까지',
            '부터',
            '으로',
            '로',
            '이라',
            '이며',
            '이나',
            '이든',
            '이랑',
            '이가',
            '이는',
            '이를',
            '이다',
            // Short particles
            '에',
            '가',
            '는',
            '은',
            '를',
            '을',
            '의',
            '도',
            '만',
            '와',
            '과',
            '나',
            '랑',
            '며',
            '든',
        ];

        foreach ($suffixes as $suffix) {
            if (mb_strlen($token) < mb_strlen($suffix)) {
                continue;
            }
            if (mb_substr($token, -mb_strlen($suffix)) === $suffix) {
                $stemLength = mb_strlen($token) - mb_strlen($suffix);
                if ($stemLength <= 0) {
                    $fallbackStemLength = mb_strlen($suffix) > 2 ? mb_strlen($suffix) - 2 : mb_strlen($suffix) - 1;
                    return mb_substr($token, 0, max(1, $fallbackStemLength));
                }

                return mb_substr($token, 0, $stemLength);
            }
        }

        if (mb_strlen($token) > 2 && mb_substr($token, -2) === '니다') {
            return mb_substr($token, 0, mb_strlen($token) - 2);
        }

        return $token;
    }

    // -------------------------------------------------------------------------
    // Script detection
    // -------------------------------------------------------------------------

    public function detectScript(string $text): array
    {
        $counts = [
            'Hangul' => 0,
            'Latin' => 0,
            'Digit' => 0,
            'Hanja' => 0,
            'Whitespace' => 0,
            'Punctuation' => 0,
            'Other' => 0,
        ];

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($chars as $ch) {
            $ord = mb_ord($ch, 'UTF-8');

            if ($this->isHangul($ord)) {
                $counts['Hangul']++;
            } elseif ($ord >= self::HANJA_START && $ord <= self::HANJA_END) {
                $counts['Hanja']++;
            } elseif (($ord >= 0x0041 && $ord <= 0x007A) || ($ord >= 0x00C0 && $ord <= 0x024F)) {
                $counts['Latin']++;
            } elseif ($ord >= 0x0030 && $ord <= 0x0039) {
                $counts['Digit']++;
            } elseif (ctype_space($ch)) {
                $counts['Whitespace']++;
            } elseif ($ord < 0x0080 && ctype_punct($ch)) {
                $counts['Punctuation']++;
            } else {
                $counts['Other']++;
            }
        }

        return array_filter($counts);
    }

    // -------------------------------------------------------------------------
    // Jamo decomposition (자모 분리)  — extra Korean-specific feature
    // -------------------------------------------------------------------------

    /**
     * Decompose a Hangul syllable string into individual jamo.
     * 가 → ㄱ + ㅏ   |   닭 → ㄷ + ㅏ + ㄹ + ㄱ
     *
     * @return string[]
     */
    public function decomposeJamo(string $text): array
    {
        $result = [];
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($chars as $ch) {
            $ord = mb_ord($ch, 'UTF-8');

            if ($ord >= self::HANGUL_SYLLABLE_START && $ord <= self::HANGUL_SYLLABLE_END) {
                $code = $ord - self::HANGUL_SYLLABLE_START;
                $cho = intdiv($code, 21 * 28);
                $jung = intdiv($code % (21 * 28), 28);
                $jong = $code % 28;

                $result[] = self::CHOSEONG[$cho];
                $result[] = self::JUNGSEONG[$jung];
                if ($jong !== 0) {
                    $result[] = self::JONGSEONG[$jong];
                }
            } else {
                $result[] = $ch;
            }
        }

        return $result;
    }

    /**
     * Decompose all tokens into jamo sequences.
     * Useful for phonetic similarity / fuzzy matching.
     *
     * @param  string[] $tokens
     * @return array<string, string>  token => jamo_string
     */
    public function tokensToJamo(array $tokens): array
    {
        $result = [];
        foreach ($tokens as $token) {
            $result[$token] = implode('', $this->decomposeJamo($token));
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // POS tagging (rule-based, Korean morphological patterns)
    // -------------------------------------------------------------------------

    public function posTag(array $tokens): array
    {
        $result = [];

        $nounSuffixes = ['자', '사', '가', '인', '장', '소', '실', '부', '원', '국', '관', '처', '청'];
        $verbSuffixes = ['하다', '되다', '시키다', '받다', '주다', '오다', '가다', '보다', '있다', '없다'];
        $adjSuffixes = ['하다', '스럽다', '롭다', '답다', '적이다', '같다'];
        $particles = ['이', '가', '은', '는', '을', '를', '의', '에', '와', '과', '도', '만', '까지', '부터'];

        foreach ($tokens as $token) {
            if (in_array($token, $particles)) {
                $tag = 'PART';       // 조사 (particle)
                goto done;
            }

            // Number detection (Arabic or Korean numerals)
            if (preg_match('/^[\d\s]+$/', $token) || preg_match('/^[일이삼사오육칠팔구십백천만억]+$/u', $token)) {
                $tag = 'NUM';
                goto done;
            }

            // Verb / adjective: ends with 다 and matches known suffix
            $tag = 'NOUN'; // default
            foreach ($verbSuffixes as $s) {
                if (mb_substr($token, -mb_strlen($s)) === $s) {
                    $tag = 'VERB';
                    goto done;
                }
            }
            foreach ($adjSuffixes as $s) {
                if (mb_substr($token, -mb_strlen($s)) === $s) {
                    $tag = 'ADJ';
                    goto done;
                }
            }

            // Noun: ends in common noun-final characters
            $last = mb_substr($token, -1);
            if (in_array($last, $nounSuffixes)) {
                $tag = 'NOUN';
                goto done;
            }

            // Latin inside Korean text → foreign word
            if (preg_match('/[A-Za-z]/', $token)) {
                $tag = 'FOREIGN';
            }

            done:
            $result[$token] = $tag;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function isHangul(int $ord): bool
    {
        return ($ord >= self::HANGUL_SYLLABLE_START && $ord <= self::HANGUL_SYLLABLE_END)
            || ($ord >= self::HANGUL_JAMO_START && $ord <= self::HANGUL_JAMO_END)
            || ($ord >= self::HANGUL_COMPAT_START && $ord <= self::HANGUL_COMPAT_END);
    }
}
