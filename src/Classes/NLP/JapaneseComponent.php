<?php

declare(strict_types=1);

namespace Clover\Classes\NLP\Components;

/**
 * JapaneseComponent — Japanese (日本語) language NLP spec.
 *
 * Features:
 *  - Character-class boundary tokenization (no spaces in Japanese)
 *  - Script detection: Hiragana / Katakana / Kanji / Romaji / Digit / Punct / Symbol
 *  - Sentence boundary detection (。！？…)
 *  - Japanese stop words (particles / auxiliaries)
 *  - Suffix stripping (verb/adjective ending normalization)
 *  - N-gram override (character-level fallback for unknown segments)
 *  - Katakana → Hiragana conversion (読み仮名正規化)
 *  - Hiragana → Romaji transliteration table (basic Hepburn)
 *  - POS tagging via suffix / particle pattern matching
 *  - NFC unicode normalization + fullwidth→halfwidth conversion
 */
class JapaneseComponent extends LanguageComponent
{
    // Unicode ranges
    private const HIRAGANA_START = 0x3041;
    private const HIRAGANA_END = 0x3096;
    private const KATAKANA_START = 0x30A0;
    private const KATAKANA_END = 0x30FF;
    private const KANJI_START = 0x4E00;
    private const KANJI_END = 0x9FFF;
    private const KANJI_EXT_START = 0x3400;
    private const KANJI_EXT_END = 0x4DBF;
    private const HALFKANA_START = 0xFF65;
    private const HALFKANA_END = 0xFF9F;

    // -------------------------------------------------------------------------
    // Identity
    // -------------------------------------------------------------------------

    public function name(): string
    {
        return 'Japanese';
    }
    public function isoCode(): string
    {
        return 'ja';
    }

    // -------------------------------------------------------------------------
    // Tokenization
    // -------------------------------------------------------------------------

    /**
     * Tokenize Japanese text by grouping consecutive same-script characters.
     * Boundary: Hiragana | Katakana | Kanji | Latin | Digit | Punctuation
     *
     * Pure PHP (no MeCab / kuromoji). Good for script-level segmentation.
     */
    public function tokenize(string $text): array
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if (empty($chars))
            return [];

        $tokens = [];
        $current = '';
        $curType = $this->scriptOf($chars[0]);

        foreach ($chars as $ch) {
            $type = $this->scriptOf($ch);

            if ($type === 'Whitespace') {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
                $curType = '';
                continue;
            }

            if ($type === 'Punctuation' || $type === 'Symbol') {
                if ($current !== '') {
                    $tokens[] = $current;
                    $current = '';
                }
                $tokens[] = $ch;
                $curType = '';
                continue;
            }

            if ($type === $curType) {
                $current .= $ch;
            } else {
                if ($current !== '')
                    $tokens[] = $current;
                $current = $ch;
                $curType = $type;
            }
        }

        if ($current !== '')
            $tokens[] = $current;

        return array_values(array_filter($tokens, fn(string $t) => trim($t) !== ''));
    }

    // -------------------------------------------------------------------------
    // Sentence splitting
    // -------------------------------------------------------------------------

    public function splitSentences(string $text): array
    {
        // Japanese sentence terminators: 。！？… and their fullwidth variants
        $parts = preg_split('/(?<=[。！？…\!?\.])\s*/u', $text) ?: [$text];

        return array_values(array_filter(
            array_map('trim', $parts),
            fn(string $s) => $s !== ''
        ));
    }

    // -------------------------------------------------------------------------
    // Normalization
    // -------------------------------------------------------------------------

    public function normalize(string $text): string
    {
        if (class_exists('Normalizer')) {
            $text = \Normalizer::normalize($text, \Normalizer::NFC) ?: $text;
        }

        // Fullwidth ASCII → halfwidth (Ａ→A, １→1, …)
        $text = $this->fullwidthToHalfwidth($text);
        $text = mb_strtolower($text, 'UTF-8');

        return preg_replace('/\s+/', ' ', trim($text)) ?? $text;
    }

    // -------------------------------------------------------------------------
    // Stop words (助詞・助動詞・接続詞など)
    // -------------------------------------------------------------------------

    public function stopWords(): array
    {
        return [
            // 助詞 (particles)
            'は',
            'が',
            'を',
            'に',
            'へ',
            'と',
            'から',
            'まで',
            'で',
            'の',
            'も',
            'か',
            'や',
            'ね',
            'よ',
            'な',
            'し',
            'て',
            'ば',
            'ながら',
            'けれど',
            'けれども',
            'けど',
            'が',
            'のに',
            'ので',
            'から',
            'より',
            'ほど',
            'だけ',
            'しか',
            'さえ',
            'でも',
            'こそ',
            // 助動詞 (auxiliary verbs)
            'です',
            'ます',
            'だ',
            'た',
            'ない',
            'ぬ',
            'れる',
            'られる',
            'せる',
            'させる',
            'たい',
            'そう',
            'ようだ',
            'らしい',
            'まい',
            // 接続詞 (conjunctions)
            'そして',
            'それから',
            'しかし',
            'でも',
            'ところが',
            'また',
            'さらに',
            'なお',
            'つまり',
            'すなわち',
            'だから',
            'それで',
            'ゆえに',
            'もしくは',
            'あるいは',
            // 代名詞 (pronouns)
            'これ',
            'それ',
            'あれ',
            'どれ',
            'ここ',
            'そこ',
            'あそこ',
            'こちら',
            'そちら',
            'あちら',
            'わたし',
            'ぼく',
            'あなた',
            // 副詞・その他
            'とても',
            'すごく',
            'かなり',
            'ちょっと',
            'もう',
            'まだ',
            'もっと',
            'ずっと',
            'ほとんど',
            'たくさん',
            'あまり',
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
    // Stemming (verb / adjective ending normalization)
    // -------------------------------------------------------------------------

    public function stem(array $tokens): array
    {
        return array_map([$this, 'stemToken'], $tokens);
    }

    private function stemToken(string $token): string
    {
        // Order: longest suffix first
        $endings = [
            // Polite verb forms → dictionary form guess
            'ています',
            'ました',
            'ません',
            'ませんでした',
            'ましょう',
            'ないでください',
            'てください',
            'ている',
            'ている',
            'ていた',
            'てある',
            'てみる',
            'てしまう',
            'ます',
            'った',
            'った',
            'して',
            'んで',
            // Adjective forms (い形容詞)
            'くない',
            'くて',
            'かった',
            'くなかった',
            'ければ',
            'かろう',
            // な形容詞
            'ではない',
            'でない',
            'だった',
            'でした',
            // Common verb endings
            'させられる',
            'させる',
            'られる',
            'れる',
            'ない',
            'たい',
        ];

        foreach ($endings as $ending) {
            $len = mb_strlen($ending);
            if (mb_strlen($token) >= $len && mb_substr($token, -$len) === $ending) {
                $stemLength = mb_strlen($token) - $len;
                if ($stemLength <= 0) {
                    $fallbackStemLength = $len > 2 ? $len - 2 : $len - 1;
                    return mb_substr($token, 0, max(1, $fallbackStemLength));
                }

                return mb_substr($token, 0, $stemLength);
            }
        }

        return $token;
    }

    // -------------------------------------------------------------------------
    // N-gram override: character-level for Kanji/Hiragana blocks
    // -------------------------------------------------------------------------

    public function ngrams(array $tokens, int $n = 2): array
    {
        $result = [];

        foreach ($tokens as $token) {
            $scriptType = $this->scriptOf(mb_substr($token, 0, 1));

            // For Kanji / Hiragana / Katakana: character n-grams
            if (in_array($scriptType, ['Kanji', 'Hiragana', 'Katakana'])) {
                $chars = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $limit = count($chars) - $n + 1;
                for ($i = 0; $i < $limit; $i++) {
                    $result[] = array_slice($chars, $i, $n);
                }
            }
        }

        // Also produce token-level n-grams for the whole token list
        $tokenNgrams = parent::ngrams($tokens, $n);

        return array_merge($result, $tokenNgrams);
    }

    // -------------------------------------------------------------------------
    // Script detection
    // -------------------------------------------------------------------------

    public function detectScript(string $text): array
    {
        $counts = [
            'Hiragana' => 0,
            'Katakana' => 0,
            'Kanji' => 0,
            'Latin' => 0,
            'Digit' => 0,
            'Whitespace' => 0,
            'Punctuation' => 0,
            'Symbol' => 0,
            'Other' => 0,
        ];

        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($chars as $ch) {
            $counts[$this->scriptOf($ch)]++;
        }

        return array_filter($counts);
    }

    // -------------------------------------------------------------------------
    // Katakana → Hiragana conversion (カタカナ → ひらがな)
    // -------------------------------------------------------------------------

    /**
     * Convert a string: all Katakana characters → equivalent Hiragana.
     * ア → あ, カ → か, etc.
     */
    public function katakanaToHiragana(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = '';

        foreach ($chars as $ch) {
            $ord = mb_ord($ch, 'UTF-8');
            // Standard Katakana range: 0x30A1–0x30F6 → 0x3041–0x3096
            if ($ord >= 0x30A1 && $ord <= 0x30F6) {
                $result .= mb_chr($ord - 0x0060, 'UTF-8');
            } else {
                $result .= $ch;
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Hiragana → Romaji (Hepburn transliteration, basic)
    // -------------------------------------------------------------------------

    /**
     * Transliterate Hiragana characters to Hepburn Romaji.
     * Does not handle double consonants (っ) or long vowels (ー) perfectly
     * at a morphological level — character-level conversion only.
     */
    public function hiraganaToRomaji(string $text): string
    {
        $map = [
            'あ' => 'a',
            'い' => 'i',
            'う' => 'u',
            'え' => 'e',
            'お' => 'o',
            'か' => 'ka',
            'き' => 'ki',
            'く' => 'ku',
            'け' => 'ke',
            'こ' => 'ko',
            'さ' => 'sa',
            'し' => 'shi',
            'す' => 'su',
            'せ' => 'se',
            'そ' => 'so',
            'た' => 'ta',
            'ち' => 'chi',
            'つ' => 'tsu',
            'て' => 'te',
            'と' => 'to',
            'な' => 'na',
            'に' => 'ni',
            'ぬ' => 'nu',
            'ね' => 'ne',
            'の' => 'no',
            'は' => 'ha',
            'ひ' => 'hi',
            'ふ' => 'fu',
            'へ' => 'he',
            'ほ' => 'ho',
            'ま' => 'ma',
            'み' => 'mi',
            'む' => 'mu',
            'め' => 'me',
            'も' => 'mo',
            'や' => 'ya',
            'ゆ' => 'yu',
            'よ' => 'yo',
            'ら' => 'ra',
            'り' => 'ri',
            'る' => 'ru',
            'れ' => 're',
            'ろ' => 'ro',
            'わ' => 'wa',
            'ゐ' => 'wi',
            'ゑ' => 'we',
            'を' => 'wo',
            'ん' => 'n',
            // Voiced
            'が' => 'ga',
            'ぎ' => 'gi',
            'ぐ' => 'gu',
            'げ' => 'ge',
            'ご' => 'go',
            'ざ' => 'za',
            'じ' => 'ji',
            'ず' => 'zu',
            'ぜ' => 'ze',
            'ぞ' => 'zo',
            'だ' => 'da',
            'ぢ' => 'ji',
            'づ' => 'zu',
            'で' => 'de',
            'ど' => 'do',
            'ば' => 'ba',
            'び' => 'bi',
            'ぶ' => 'bu',
            'べ' => 'be',
            'ぼ' => 'bo',
            'ぱ' => 'pa',
            'ぴ' => 'pi',
            'ぷ' => 'pu',
            'ぺ' => 'pe',
            'ぽ' => 'po',
            // Digraphs (small kana)
            'きゃ' => 'kya',
            'きゅ' => 'kyu',
            'きょ' => 'kyo',
            'しゃ' => 'sha',
            'しゅ' => 'shu',
            'しょ' => 'sho',
            'ちゃ' => 'cha',
            'ちゅ' => 'chu',
            'ちょ' => 'cho',
            'にゃ' => 'nya',
            'にゅ' => 'nyu',
            'にょ' => 'nyo',
            'ひゃ' => 'hya',
            'ひゅ' => 'hyu',
            'ひょ' => 'hyo',
            'みゃ' => 'mya',
            'みゅ' => 'myu',
            'みょ' => 'myo',
            'りゃ' => 'rya',
            'りゅ' => 'ryu',
            'りょ' => 'ryo',
            'ぎゃ' => 'gya',
            'ぎゅ' => 'gyu',
            'ぎょ' => 'gyo',
            'じゃ' => 'ja',
            'じゅ' => 'ju',
            'じょ' => 'jo',
            'びゃ' => 'bya',
            'びゅ' => 'byu',
            'びょ' => 'byo',
            'ぴゃ' => 'pya',
            'ぴゅ' => 'pyu',
            'ぴょ' => 'pyo',
            // Special
            'っ' => '(t)',
            'ー' => '-',
            'ゃ' => 'ya',
            'ゅ' => 'yu',
            'ょ' => 'yo',
            'ぁ' => 'a',
            'ぃ' => 'i',
            'ぅ' => 'u',
            'ぇ' => 'e',
            'ぉ' => 'o',
        ];

        // Process digraphs first (two-character combos)
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = '';
        $i = 0;
        while ($i < count($chars)) {
            $pair = ($i + 1 < count($chars)) ? $chars[$i] . $chars[$i + 1] : '';
            if ($pair !== '' && isset($map[$pair])) {
                $result .= $map[$pair];
                $i += 2;
            } elseif (isset($map[$chars[$i]])) {
                $result .= $map[$chars[$i]];
                $i++;
            } else {
                $result .= $chars[$i];
                $i++;
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // POS tagging (rule-based, Japanese patterns)
    // -------------------------------------------------------------------------

    public function posTag(array $tokens): array
    {
        $particles = array_flip(['は', 'が', 'を', 'に', 'へ', 'と', 'から', 'まで', 'で', 'の', 'も', 'か', 'や', 'より', 'ほど', 'だけ']);
        $auxiliaries = array_flip(['です', 'ます', 'だ', 'た', 'ない', 'れる', 'られる', 'せる', 'させる']);
        $conjunctions = array_flip(['そして', 'しかし', 'でも', 'また', 'さらに', 'それから', 'だから', 'つまり', 'なぜなら']);

        $result = [];

        foreach ($tokens as $token) {
            $script = $this->scriptOf(mb_substr($token, 0, 1));

            if (isset($particles[$token])) {
                $tag = 'PART';
            } elseif (isset($auxiliaries[$token])) {
                $tag = 'AUX';
            } elseif (isset($conjunctions[$token])) {
                $tag = 'CONJ';
            } elseif ($script === 'Digit' || preg_match('/^\d+$/', $token)) {
                $tag = 'NUM';
            } elseif ($script === 'Latin') {
                $tag = 'FOREIGN';
            } elseif (mb_strlen($token) === 1 && $script === 'Punctuation') {
                $tag = 'PUNCT';
            } elseif ($script === 'Katakana') {
                $tag = 'NOUN';   // Katakana words are typically nouns / loanwords
            } elseif ($script === 'Kanji') {
                // Kanji-only: likely noun or verb stem
                $tag = mb_strlen($token) >= 2 ? 'NOUN' : 'VERB_STEM';
            } elseif ($script === 'Hiragana') {
                // Hiragana-only segments: often particles, auxiliaries, or adverbs
                $last = mb_substr($token, -1);
                if (in_array($last, ['て', 'で', 'ば', 'が', 'し'])) {
                    $tag = 'VERB';
                } elseif (in_array($last, ['い', 'な'])) {
                    $tag = 'ADJ';
                } else {
                    $tag = 'ADV';
                }
            } else {
                $tag = 'NOUN';
            }

            $result[$token] = $tag;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    private function scriptOf(string $ch): string
    {
        if ($ch === '')
            return 'Other';
        $ord = mb_ord($ch, 'UTF-8');

        if ($ord >= self::HIRAGANA_START && $ord <= self::HIRAGANA_END)
            return 'Hiragana';
        if ($ord >= self::KATAKANA_START && $ord <= self::KATAKANA_END)
            return 'Katakana';
        if ($ord >= self::HALFKANA_START && $ord <= self::HALFKANA_END)
            return 'Katakana';
        if ($ord >= self::KANJI_START && $ord <= self::KANJI_END)
            return 'Kanji';
        if ($ord >= self::KANJI_EXT_START && $ord <= self::KANJI_EXT_END)
            return 'Kanji';
        if (($ord >= 0x0041 && $ord <= 0x005A) || ($ord >= 0x0061 && $ord <= 0x007A))
            return 'Latin';
        if ($ord >= 0xFF01 && $ord <= 0xFF5E)
            return 'Latin'; // Fullwidth Latin
        if ($ord >= 0x0030 && $ord <= 0x0039)
            return 'Digit';
        if ($ord >= 0xFF10 && $ord <= 0xFF19)
            return 'Digit'; // Fullwidth digits
        if (ctype_space($ch))
            return 'Whitespace';

        // Japanese punctuation
        if (
            in_array($ord, [
                0x3001,
                0x3002,
                0xFF01,
                0xFF0C,
                0xFF0E,
                0xFF1F,
                0x300C,
                0x300D,
                0x300E,
                0x300F,
                0x3008,
                0x3009,
                0xFF08,
                0xFF09,
                0x2019,
                0x201D,
                0x30FB,
                0x21,
                0x2C,
                0x2E,
                0x3F,
                0x28,
                0x29
            ])
        ) {
            return 'Punctuation';
        }

        if ($ord >= 0x3000 && $ord <= 0x303F)
            return 'Symbol';
        if ($ord >= 0xFF00 && $ord <= 0xFFEF)
            return 'Symbol';

        return 'Other';
    }

    private function fullwidthToHalfwidth(string $text): string
    {
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = '';
        foreach ($chars as $ch) {
            $ord = mb_ord($ch, 'UTF-8');
            // Fullwidth ASCII range: 0xFF01–0xFF5E → 0x0021–0x007E
            if ($ord >= 0xFF01 && $ord <= 0xFF5E) {
                $result .= mb_chr($ord - 0xFEE0, 'UTF-8');
            } elseif ($ord === 0x3000) {
                $result .= ' '; // Ideographic space → regular space
            } else {
                $result .= $ch;
            }
        }
        return $result;
    }
}
