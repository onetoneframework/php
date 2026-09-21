<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Transformer;

class BilingualMorphTokenizer
{
    private array $char_to_idx = [];
    private array $idx_to_char = [];
    private int $vocab_size = 0;

    private static string $START = '<START>';
    private static string $END = '<END>';
    private static string $PAD = '<PAD>';
    private static string $UNK = '<UNK>';
    private static string $SPACE = '<SP>';

    private static array $CHOSUNG = ['ㄱ', 'ㄲ', 'ㄴ', 'ㄷ', 'ㄸ', 'ㄹ', 'ㅁ', 'ㅂ', 'ㅃ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅉ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ'];
    private static array $JUNGSUNG = ['ㅏ', 'ㅐ', 'ㅑ', 'ㅒ', 'ㅓ', 'ㅔ', 'ㅕ', 'ㅖ', 'ㅗ', 'ㅘ', 'ㅙ', 'ㅚ', 'ㅛ', 'ㅜ', 'ㅝ', 'ㅞ', 'ㅟ', 'ㅠ', 'ㅡ', 'ㅢ', 'ㅣ'];
    private static array $JONGSUNG = ['ㄱ', 'ㄲ', 'ㄳ', 'ㄴ', 'ㄵ', 'ㄶ', 'ㄷ', 'ㄹ', 'ㄺ', 'ㄻ', 'ㄼ', 'ㄽ', 'ㄾ', 'ㄿ', 'ㅀ', 'ㅁ', 'ㅂ', 'ㅄ', 'ㅅ', 'ㅆ', 'ㅇ', 'ㅈ', 'ㅊ', 'ㅋ', 'ㅌ', 'ㅍ', 'ㅎ']; // remove empty string entries

    private static array $KOR_PARTICLES = [
        '은',
        '는',
        '이',
        '가',
        '을',
        '를',
        '에',
        '에서',
        '에게',
        '께',
        '까지',
        '부터',
        '로',
        '으로',
        '와',
        '과',
        '랑',
        '이나',
        '나',
        '도',
        '만',
        '뿐'
    ];
    private static array $KOR_ENDINGS = [
        '겠습니다',
        '겠습니다.',
        '습니다',
        '습니다.',
        '었습니다',
        '었습니다.',
        '습니다?',
        '요',
        '요.',
        '요?',
        '이다',
        '입니다',
        '이었다',
        '였다',
        '했다',
        '합니다'
    ];

    private string $analyzer = 'auto'; // 'auto'|'mecab'|'rule'
    private string $mecabPath = 'mecab';

    private bool $frozen = false;

    public function __construct(string $mecabPath = 'mecab', string $analyzer = 'auto')
    {
        $this->mecabPath = $mecabPath;
        $this->analyzer = $analyzer;
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
    public function getVocabSize(): int
    {
        return $this->vocab_size;
    }
    public function idToToken(int $id): string
    {
        return $this->idx_to_char[$id] ?? self::$UNK;
    }
    public function tokenToId(string $token): int
    {
        return $this->char_to_idx[$token] ?? ($this->char_to_idx[self::$UNK] ?? 0);
    }
    public function setAnalyzer(string $analyzer, string $mecabPath = 'mecab'): void
    {
        $this->analyzer = $analyzer;
        $this->mecabPath = $mecabPath;
    }
    public function freezeVocabulary(): void
    {
        $this->frozen = true;
    }
    public function isFrozen(): bool
    {
        return $this->frozen;
    }

    private function buildVocabulary(): void
    {
        // base tokens
        $vocab = [self::$START, self::$END, self::$PAD, self::$UNK, self::$SPACE, '[JP]', '[KO]'];

        // Add common Kanij used in translation test dynamically to avoid OOV expansion later
        $vocab = array_merge($vocab, ['こ', 'ん', 'に', 'ち', 'は', 'あ', 'り', 'が', 'と', 'う', 'ご', 'ざ', 'い', 'ま', 'す', 'さ', 'よ', 'な', 'ら', 'は', 'い', 'い', 'え']);

        // Hangul Jamo
        $vocab = array_merge($vocab, self::$CHOSUNG, self::$JUNGSUNG, self::$JONGSUNG);

        // English lower/upper
        for ($i = ord('a'); $i <= ord('z'); $i++)
            $vocab[] = chr($i);
        for ($i = ord('A'); $i <= ord('Z'); $i++)
            $vocab[] = chr($i);

        // digits
        for ($i = 0; $i <= 9; $i++)
            $vocab[] = (string) $i;

        // punctuation
        $vocab = array_merge($vocab, str_split("!?.:,;-_()[]{}\"'/\\|@#\$%&*+=<>~`"));

        // Japanese Hiragana + Katakana ranges
        for ($code = 0x3041; $code <= 0x3096; $code++)
            $vocab[] = mb_chr($code);
        for ($code = 0x30A1; $code <= 0x30FA; $code++)
            $vocab[] = mb_chr($code);

        // unique
        $vocab = array_values(array_unique($vocab));
        $this->vocab_size = count($vocab);
        foreach ($vocab as $i => $char) {
            $this->char_to_idx[$char] = $i;
            $this->idx_to_char[$i] = $char;
        }
    }

    private function analyzeWithMeCab(string $text): array
    {
        $cmd = escapeshellcmd($this->mecabPath);
        $desc = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $proc = @proc_open($cmd, $desc, $pipes);
        if (!is_resource($proc)) {
            return [];
        }

        fwrite($pipes[0], $text);
        fclose($pipes[0]);
        $raw = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $status = proc_close($proc);
        if ($raw === '' && $status !== 0) {
            return [];
        }

        $tokens = [];
        $lines = preg_split('/\r\n|\n|\r/', trim($raw));
        foreach ($lines as $line) {
            if ($line === '' || $line === 'EOS') {
                continue;
            }
            $parts = explode("\t", $line, 2);
            $surface = $parts[0] ?? '';
            if ($surface === '') {
                continue;
            }

            if (preg_match('/^\s+$/u', $surface)) {
                $tokens[] = self::$SPACE;
            } else {
                $tokens[] = $surface;
            }
        }

        return $tokens;
    }

    private function tokenizeKoreanByRule(string $text): array
    {
        $out = [];
        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $seg) {
            if ($seg === '')
                continue;
            if (preg_match('/^\s+$/u', $seg)) {
                $out[] = self::$SPACE;
                continue;
            }

            $w = $seg;
            $particles = self::$KOR_PARTICLES;
            usort($particles, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
            $matched = false;
            foreach ($particles as $p) {
                if (mb_strlen($p) > 0 && mb_substr($w, -mb_strlen($p)) === $p) {
                    $stem = mb_substr($w, 0, mb_strlen($w) - mb_strlen($p));
                    if ($stem !== '')
                        $out[] = $stem;
                    $out[] = $p;
                    $matched = true;
                    break;
                }
            }
            if ($matched)
                continue;

            $endings = self::$KOR_ENDINGS;
            usort($endings, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
            $matchedE = false;
            foreach ($endings as $e) {
                if (mb_strlen($e) > 0 && mb_substr($w, -mb_strlen($e)) === $e) {
                    $stem = mb_substr($w, 0, mb_strlen($w) - mb_strlen($e));
                    if ($stem !== '')
                        $out[] = $stem;
                    $out[] = $e;
                    $matchedE = true;
                    break;
                }
            }
            if ($matchedE)
                continue;

            $out[] = $w;
        }
        return $out;
    }

    private function tokenizeJapaneseByRule(string $text): array
    {
        $tokens = [];
        $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $seg) {
            if ($seg === '')
                continue;
            if (preg_match('/^\s+$/u', $seg)) {
                $tokens[] = self::$SPACE;
                continue;
            }

            $len = mb_strlen($seg);
            $cur = '';
            $curType = null;
            for ($i = 0; $i < $len; $i++) {
                $ch = mb_substr($seg, $i, 1);
                if (preg_match('/[\p{Hiragana}]/u', $ch))
                    $type = 'H';
                elseif (preg_match('/[\p{Katakana}]/u', $ch))
                    $type = 'K';
                elseif (preg_match('/[\p{Han}]/u', $ch))
                    $type = 'C';
                elseif (preg_match('/[A-Za-z0-9]/u', $ch))
                    $type = 'A';
                else
                    $type = 'P';

                if ($type === 'P') {
                    if ($cur !== '') {
                        $tokens[] = $cur;
                        $cur = '';
                        $curType = null;
                    }
                    $tokens[] = $ch;
                    continue;
                }
                if ($curType === null || $curType === $type) {
                    $cur .= $ch;
                    $curType = $type;
                } else {
                    $tokens[] = $cur;
                    $cur = $ch;
                    $curType = $type;
                }
            }
            if ($cur !== '') {
                $tokens[] = $cur;
            }
        }
        return $tokens;
    }

    private function tokenizeText(string $text): array
    {
        if ($this->analyzer === 'mecab' || $this->analyzer === 'auto') {
            $mecabTokens = $this->analyzeWithMeCab($text);
            if (!empty($mecabTokens)) {
                return $mecabTokens;
            }
        }

        $out = [];
        if (preg_match('/[가-힣]/u', $text)) {
            $out = array_merge($out, $this->tokenizeKoreanByRule($text));
        } elseif (preg_match('/[\p{Hiragana}\p{Katakana}\p{Han}]/u', $text)) {
            $out = array_merge($out, $this->tokenizeJapaneseByRule($text));
        } else {
            $parts = preg_split('/(\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
            foreach ($parts as $seg) {
                if ($seg === '') {
                    continue;
                }
                if (preg_match('/^\s+$/u', $seg)) {
                    $out[] = self::$SPACE;
                    continue;
                }
                $sub = preg_split('/(\p{P})/u', $seg, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                foreach ($sub as $s) {
                    $out[] = $s;
                }
            }
        }

        return $out;
    }

    private function tokenizeAuto(string $text): array
    {
        // Extract bracketed tags like [JP] and [KO] first to avoid them being mangled by language rules
        $parts = preg_split('/(\[[A-Z]+\])/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        $final_out = [];
        foreach ($parts as $part) {
            if (preg_match('/^\[[A-Z]+\]$/u', $part)) {
                $final_out[] = $part;
            } else {
                $final_out = array_merge($final_out, $this->tokenizeText($part));
            }
        }
        
        return $final_out;
    }

    private function addTokenIfAllowed(string $t): int
    {
        $unk = $this->char_to_idx[self::$UNK];
        if ($t === '') {
            return $unk;
        }
        if ($t === self::$SPACE) {
            return $this->char_to_idx[self::$SPACE];
        }

        if ($this->frozen) {
            return $this->char_to_idx[$t] ?? $unk;
        }
        if (isset($this->char_to_idx[$t])) {
            return $this->char_to_idx[$t];
        }

        $idx = $this->vocab_size;
        $this->char_to_idx[$t] = $idx;
        $this->idx_to_char[$idx] = $t;
        $this->vocab_size++;
        return $idx;
    }

    // encode: string -> id[]
    public function encode(string $text): array
    {
        $tokens = $this->tokenizeAuto($text);
        $ids = [];
        foreach ($tokens as $t) {
            if ($t === self::$SPACE) {
                $ids[] = $this->char_to_idx[self::$SPACE];
            } else {
                $ids[] = $this->addTokenIfAllowed($t);
            }
        }
        return $ids;
    }

    public function decode(array $ids): string
    {
        $pieces = [];
        foreach ($ids as $id) {
            $tok = $this->idx_to_char[$id] ?? self::$UNK;
            if (in_array($tok, [self::$START, self::$END, self::$PAD], true))
                continue;
            if ($tok === self::$SPACE) {
                $pieces[] = ' ';
                continue;
            }
            $pieces[] = $tok;
        }
        return implode('', $pieces);
    }

    public function clampToModelVocab(array $ids, int $modelVocab): array
    {
        $unk = $this->char_to_idx[self::$UNK] ?? 0;
        return array_map(fn($id) => ($id >= $modelVocab ? $unk : $id), $ids);
    }

    public function idsToOneHotFixed(array $ids, int $modelVocab): array
    {
        $M = [];
        foreach ($ids as $id) {
            $row = array_fill(0, $modelVocab, 0);
            if ($id >= 0 && $id < $modelVocab) {
                $row[$id] = 1;
            } else {
                $unk = $this->char_to_idx[self::$UNK] ?? 0;
                $row[$unk] = 1;
            }
            $M[] = $row;
        }
        return $M; // shape: (seq_len x modelVocab)
    }

    public function decodeToText(array $ids): string
    {
        $pieces = [];
        foreach ($ids as $id) {
            $tok = $this->idx_to_char[$id] ?? self::$UNK;

            if (in_array($tok, [self::$START, self::$END, self::$PAD], true)) {
                continue;
            }

            if ($tok === self::$SPACE) {
                $pieces[] = ' ';
                continue;
            }

            $pieces[] = $tok;
        }

        return $this->reassembleHangul(implode('', $pieces));
    }

    private function reassembleHangul(string $text): string
    {
        $result = '';
        $len = mb_strlen($text);
        $i = 0;

        while ($i < $len) {
            $char = mb_substr($text, $i, 1);

            $choIdx = array_search($char, self::$CHOSUNG);
            if ($choIdx !== false && $i + 1 < $len) {
                $next = mb_substr($text, $i + 1, 1);
                $jungIdx = array_search($next, self::$JUNGSUNG);

                if ($jungIdx !== false) {
                    $jongIdx = -1;

                    if ($i + 2 < $len) {
                        $third = mb_substr($text, $i + 2, 1);
                        $jongIdxTemp = array_search($third, self::$JONGSUNG);
                        if ($jongIdxTemp !== false) {
                            $jongIdx = $jongIdxTemp;
                        }
                    }

                    $syllable = 0xAC00 + ($choIdx * 21 * 28) + ($jungIdx * 28) + ($jongIdx + 1);
                    $result .= mb_chr($syllable);

                    $i += ($jongIdx >= 0) ? 3 : 2;
                    continue;
                }
            }

            $result .= $char;
            $i++;
        }

        return $result;
    }
}