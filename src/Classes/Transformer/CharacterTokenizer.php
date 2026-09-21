<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Transformer;

/**
 * Character-level Tokenizer
 * * Features:
 * - Korean: Decomposes into Jamo (ㄱ, ㅏ, ㄴ)
 * - English: Character-level (a, b, c)
 * - Can represent OOV words (Out-of-Vocabulary)
 */
class CharacterTokenizer
{
    private array $char_to_idx = [];
    private array $idx_to_char = [];
    private int $vocab_size;

    private static string $START = '<START>';
    private static string $END = '<END>';
    private static string $PAD = '<PAD>';
    private static string $UNK = '<UNK>';
    private static string $SPACE = '<SP>';

    // Hangul Jamo components
    private static array $CHOSUNG = ['ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];
    private static array $JUNGSUNG = ['ㅏ', 'ㅐ', 'ㅑ', 'ㅒ', 'ㅓ', 'ㅔ', 'ㅕ', 'ㅖ', 'ㅗ', 'ㅘ', 'ㅙ', 'ㅚ', 'ㅛ', 'ㅜ', 'ㅝ', 'ㅞ', 'ㅟ', 'ㅠ', 'ㅡ', 'ㅢ', 'ㅣ'];
    private static array $JONGSUNG = ['', 'ㄱ', 'ㄲ', 'ㄳ', 'ㄴ', 'ㄵ', 'ㄶ', 'ㄷ', 'ㄹ', 'ㄺ', 'ㄻ', 'ㄼ', 'ㄽ', 'ㄾ', 'ㄿ', 'ㅀ', 'ㅁ', 'ㅂ', 'ㅄ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];

    public function __construct()
    {
        $this->buildVocabulary();
    }

    public function getStartId(): int
    {
        return $this->char_to_idx[self::$START];
    }
    
    public function getPadId(): int
    {
        return $this->char_to_idx[self::$PAD];
    }

    public function getEndId(): int
    {
        return $this->char_to_idx[self::$END];
    }

    private function buildVocabulary(): void
    {
        $vocab = [self::$START, self::$END, self::$PAD, self::$UNK, self::$SPACE];
        $vocab = array_merge($vocab, self::$CHOSUNG, self::$JUNGSUNG, self::$JONGSUNG);

        // English
        for ($i = ord('a'); $i <= ord('z'); $i++) {
            $vocab[] = chr($i);
        }
        for ($i = ord('A'); $i <= ord('Z'); $i++) {
            $vocab[] = chr($i);
        }

        // Digits
        for ($i = 0; $i <= 9; $i++) {
            $vocab[] = (string) $i;
        }

        // Punctuation
        $vocab = array_merge($vocab, str_split("!?.:,;-_()[]{}\"'/\\|@#\$%&*+=<>~`"));

        // Add Japanese Kana range
        for ($code = 0x3041; $code <= 0x3096; $code++) {
            $vocab[] = mb_chr($code); // Hiragana
        }
        for ($code = 0x30A1; $code <= 0x30FA; $code++) {
            $vocab[] = mb_chr($code); // Katakana
        }

        $vocab = array_values(array_unique($vocab));
        $this->vocab_size = count($vocab);

        foreach ($vocab as $i => $char) {
            $this->char_to_idx[$char] = $i;
            $this->idx_to_char[$i] = $char;
        }
    }

    private function decomposeHangul(string $char): array
    {
        $code = mb_ord($char);
        if ($code < 0xAC00 || $code > 0xD7A3) {
            return [$char];
        }

        $base = $code - 0xAC00;
        $cho_idx = intdiv($base, 21 * 28);
        $jung_idx = intdiv($base % (21 * 28), 28);
        $jong_idx = $base % 28;

        $result = [self::$CHOSUNG[$cho_idx], self::$JUNGSUNG[$jung_idx]];
        if ($jong_idx > 0) {
            $result[] = self::$JONGSUNG[$jong_idx];
        }

        return $result;
    }

    private function composeHangul(string $cho, string $jung, string $jong = ''): string
    {
        $cho_idx = array_search($cho, self::$CHOSUNG, true);
        $jung_idx = array_search($jung, self::$JUNGSUNG, true);
        $jong_idx = array_search($jong, self::$JONGSUNG, true);

        if ($cho_idx === false || $jung_idx === false || $jong_idx === false) {
            return $cho . $jung . $jong;
        }

        $code = 0xAC00 + ($cho_idx * 21 * 28) + ($jung_idx * 28) + $jong_idx;
        return mb_chr($code);
    }

    public function encode(string $text): array
    {
        $tokens = [];
        $len = mb_strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($text, $i, 1);

            if ($char === ' ') {
                $tokens[] = self::$SPACE;
            } elseif (preg_match('/[가-힣]/u', $char)) {
                $tokens = array_merge($tokens, $this->decomposeHangul($char));
            } else {
                $tokens[] = $char;
            }
        }

        $unk = $this->char_to_idx[self::$UNK];
        return array_map(fn($t) => $this->char_to_idx[$t] ?? $unk, $tokens);
    }

    /**
     * Decode: Combines Jamo back into Hangul syllables
     */
    public function decode(array $ids): string
    {
        $chars = [];
        $i = 0;

        while ($i < count($ids)) {
            $char = $this->idx_to_char[$ids[$i]] ?? self::$UNK;
            
            // Special tokens
            if (in_array($char, [self::$START, self::$END, self::$PAD], true)) {
                $i++;
                continue;
            }

            if ($char === self::$SPACE) {
                $chars[] = ' ';
                $i++;
                continue;
            }

            // Hangul composition
            if (in_array($char, self::$CHOSUNG, true) && $i + 1 < count($ids)) {
                $cho = $char;
                $next = $this->idx_to_char[$ids[$i + 1]] ?? '';

                if (in_array($next, self::$JUNGSUNG, true)) {
                    $jung = $next;
                    $jong = '';
                    $i += 2;
                    
                    // CORE LOGIC: Jongseong (final consonant) check.
                    if ($i < count($ids)) {
                        $next2 = $this->idx_to_char[$ids[$i]] ?? '';

                        if ($next2 !== '' && in_array($next2, self::$JONGSUNG, true)) {
                            $is_jong = true;
                            if ($i + 1 < count($ids)) {
                                $next3 = $this->idx_to_char[$ids[$i + 1]] ?? '';
                                // If next3 is a Jungsung, then next2 must be a Chosung
                                if (in_array($next3, self::$JUNGSUNG, true)) {
                                    $is_jong = false;
                                }
                            }

                            if ($is_jong) {
                                $jong = $next2;
                                $i++;
                            }
                        }
                    }

                    $chars[] = $this->composeHangul($cho, $jung, $jong);
                    continue;
                }
            }
            
            // Regular character
            $chars[] = $char;
            $i++;
        }

        return implode('', $chars);
    }

    public function getVocabSize(): int
    {
        return $this->vocab_size;
    }
}