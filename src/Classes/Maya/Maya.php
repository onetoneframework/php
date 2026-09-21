<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

/**
 * Maya class for fluent string matching and manipulation.
 * Allows chaining of operations to test or process a string.
 */
class Maya
{
    /**
     * The original text being processed.
     * @var string
     */
    private string $originalText;

    /**
     * The current index/position in the originalText for the next operation.
     * @var int
     */
    private int $currentIndex = 0;

    /**
     * Flag indicating if any operation in the chain has failed.
     * @var bool
     */
    private bool $matchFailed = false;

    /**
     * A prefix to be added to the pattern for the immediately next matching operation.
     * (Primarily for use with startsWith, endsWith, contains, advanceAfter)
     * @var string|null
     */
    private ?string $prefixForNextOperation = null;

    /**
     * Debug mode flag. If true, methods may output debugging information.
     * @var bool
     */
    private bool $debugMode = false;

    /**
     * Constructor for Maya.
     * Initializes the instance with the text to be processed and debug mode.
     *
     * @param string $text The text to operate on.
     * @param bool $debug Optional. Whether to enable debug mode. Defaults to false.
     */
    public function __construct(string $text, bool $debug = false)
    {
        $this->originalText = $text;
        $this->debugMode = $debug;
        $this->logDebug("Maya instance created. Text length: " . strlen($this->originalText));
    }

    /**
     * Static factory method to create a new Maya instance.
     * Provides a fluent way to start a chain of operations.
     *
     * @param string $text The text to operate on.
     * @param bool $debug Optional. Whether to enable debug mode. Defaults to false.
     * @return self Returns a new instance of Maya.
     */
    public static function on(string $text, bool $debug = false): self
    {
        return new self($text, $debug);
    }

    /**
     * Splits a rule string by '||' into an array of patterns (for older methods).
     *
     * @param string $rule The rule string, potentially containing '||' as OR separator.
     * @return string[] An array of patterns.
     */
    private function splitRule(string $rule): array
    {
        return explode('||', $rule);
    }

    /**
     * Logs a message if debug mode is enabled.
     *
     * @param string $message The message to log.
     */
    private function logDebug(string $message): void
    {
        if ($this->debugMode) {
            echo "DEBUG: " . $message . PHP_EOL;
        }
    }

    // --- HIGH-LEVEL STRING OPERATIONS (from previous version) ---
    // These methods (startsWith, endsWith, contains, advanceAfter, addPrefixToNext)
    // remain as they provide useful, simpler string operations.
    // They are not typically part of the new regex-like construction flow but can be used
    // to position the currentIndex before starting a complex pattern match.

    /**
     * Checks if the remaining text (from the current index) starts with any of the given patterns.
     * If a match is found, advances the current index past the matched pattern.
     * Supports '||' in patterns for OR logic.
     *
     * @param string $patterns The pattern(s) to match at the start. Use '||' for alternatives.
     * @return self Returns the current instance for method chaining.
     */
    public function startsWith(string $patterns): self
    {
        if ($this->matchFailed) {
            return $this;
        }
        $this->logDebug("startsWith: Called with patterns '{$patterns}'. Current index: {$this->currentIndex}. Prefix: '{$this->prefixForNextOperation}'");
        $rules = $this->splitRule($patterns);
        $operationSuccess = false;
        $remainingText = substr($this->originalText, $this->currentIndex);
        if ($remainingText === false) {
            $remainingText = '';
        }

        foreach ($rules as $rule) {
            $effectivePattern = ($this->prefixForNextOperation ?? '') . $rule;
            if ($effectivePattern === '' && !($rule === '' && $this->prefixForNextOperation === null))
                continue;
            if ($effectivePattern === '') { // Legit empty pattern
                $operationSuccess = true;
                $this->logDebug("startsWith: Matched empty pattern. Index remains {$this->currentIndex}.");
                break;
            }
            if (strpos($remainingText, $effectivePattern) === 0) {
                $this->currentIndex += strlen($effectivePattern);
                $operationSuccess = true;
                $this->logDebug("startsWith: Matched '{$effectivePattern}'. New index: {$this->currentIndex}.");
                break;
            }
        }
        if (!$operationSuccess) {
            $this->matchFailed = true;
            $this->logDebug("startsWith: No match found for '{$patterns}'. Match failed.");
        }
        $this->prefixForNextOperation = null;
        return $this;
    }

    /**
     * Checks if the remaining text (from the current index) ends with any of the given patterns.
     * This method does not change the current index.
     * Supports '||' in patterns for OR logic.
     *
     * @param string $patterns The pattern(s) to match at the end. Use '||' for alternatives.
     * @return self Returns the current instance for method chaining.
     */
    public function endsWith(string $patterns): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        $this->logDebug("endsWith: Called with patterns '{$patterns}'. Current index: {$this->currentIndex}. Prefix: '{$this->prefixForNextOperation}'");
        $rules = $this->splitRule($patterns);
        $operationSuccess = false;
        $remainingText = substr($this->originalText, $this->currentIndex);
        if ($remainingText === false) {
            $remainingText = '';
        }

        foreach ($rules as $rule) {
            $effectivePattern = ($this->prefixForNextOperation ?? '') . $rule;
            if ($effectivePattern === '' && !($rule === '' && $this->prefixForNextOperation === null)) {
                continue;
            }

            if ($effectivePattern === '') {
                $operationSuccess = true;
                $this->logDebug("endsWith: Matched empty pattern.");
                break;
            }

            if (\strlen($remainingText) >= \strlen($effectivePattern) && substr($remainingText, -strlen($effectivePattern)) === $effectivePattern) {
                $operationSuccess = true;
                $this->logDebug("endsWith: Matched '{$effectivePattern}'. Index remains {$this->currentIndex}.");
                break;
            }
        }
        if (!$operationSuccess) {
            $this->matchFailed = true;
            $this->logDebug("endsWith: No match found for '{$patterns}'. Match failed.");
        }
        $this->prefixForNextOperation = null;
        return $this;
    }

    /**
     * Searches for any of the given patterns in the remaining text (from the current index).
     * If a match is found, moves the current index to the start of the matched pattern.
     * Supports '||' in patterns for OR logic.
     *
     * @param string $patterns The pattern(s) to search for. Use '||' for alternatives.
     * @return self Returns the current instance for method chaining.
     */
    public function contains(string $patterns): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        $this->logDebug("contains: Called with patterns '{$patterns}'. Current index: {$this->currentIndex}. Prefix: '{$this->prefixForNextOperation}'");
        $rules = $this->splitRule($patterns);
        $operationSuccess = false;
        $earliestMatchPos = -1;
        $textToSearch = substr($this->originalText, $this->currentIndex);
        if ($textToSearch === false) {
            $textToSearch = '';
        }

        foreach ($rules as $rule) {
            $effectivePattern = ($this->prefixForNextOperation ?? '') . $rule;
            if ($effectivePattern === '' && !($rule === '' && $this->prefixForNextOperation === null)) {
                continue;
            }

            if ($effectivePattern === '') { // Empty pattern matches at current position if no other match is found earlier
                if ($earliestMatchPos === -1 || 0 < $earliestMatchPos) {
                    $earliestMatchPos = 0;
                }

                // No break, an empty pattern is lowest priority unless it's the only one.
                continue;
            }
            $position = strpos($textToSearch, $effectivePattern);
            if ($position !== false) {
                if ($earliestMatchPos === -1 || $position < $earliestMatchPos) {
                    $earliestMatchPos = $position;
                    $this->logDebug("contains: Found potential match '{$effectivePattern}' at relative position {$position}.");
                }
            }
        }
        if ($earliestMatchPos !== -1) {
            $this->currentIndex += $earliestMatchPos;
            $operationSuccess = true;
            $this->logDebug("contains: Matched. New index: {$this->currentIndex} (start of found pattern).");
        } else {
            $this->matchFailed = true;
            $this->logDebug("contains: No match found for '{$patterns}'. Match failed.");
        }
        $this->prefixForNextOperation = null;
        return $this;
    }

    /**
     * Searches for any of the given patterns in the remaining text (from the current index).
     * If a match is found, moves the current index to the position immediately after the matched pattern.
     * Supports '||' in patterns for OR logic.
     *
     * @param string $patterns The pattern(s) to search for and advance past. Use '||' for alternatives.
     * @return self Returns the current instance for method chaining.
     */
    public function advanceAfter(string $patterns): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        $this->logDebug("advanceAfter: Called with patterns '{$patterns}'. Current index: {$this->currentIndex}. Prefix: '{$this->prefixForNextOperation}'");
        $rules = $this->splitRule($patterns);
        $operationSuccess = false;
        $earliestMatchPos = -1;
        $lengthOfMatchedPattern = 0;
        $textToSearch = substr($this->originalText, $this->currentIndex);
        if ($textToSearch === false) {
            $textToSearch = '';
        }

        foreach ($rules as $rule) {
            $effectivePattern = ($this->prefixForNextOperation ?? '') . $rule;
            if ($effectivePattern === '' && !($rule === '' && $this->prefixForNextOperation === null)) {
                continue;
            }

            if ($effectivePattern === '') {
                if ($earliestMatchPos === -1 || 0 < $earliestMatchPos) {
                    $earliestMatchPos = 0;
                    $lengthOfMatchedPattern = 0;
                }
                continue;
            }
            $position = strpos($textToSearch, $effectivePattern);
            if ($position !== false) {
                if ($earliestMatchPos === -1 || $position < $earliestMatchPos) {
                    $earliestMatchPos = $position;
                    $lengthOfMatchedPattern = strlen($effectivePattern);
                } else if ($position === $earliestMatchPos && strlen($effectivePattern) > $lengthOfMatchedPattern) {
                    $lengthOfMatchedPattern = strlen($effectivePattern); // Prefer longer match at same spot
                }
            }
        }
        if ($earliestMatchPos !== -1) {
            $this->currentIndex += $earliestMatchPos + $lengthOfMatchedPattern;
            $operationSuccess = true;
            $this->logDebug("advanceAfter: Matched. New index: {$this->currentIndex} (after found pattern).");
        } else {
            $this->matchFailed = true;
            $this->logDebug("advanceAfter: No match found for '{$patterns}'. Match failed.");
        }
        $this->prefixForNextOperation = null;
        return $this;
    }

    /**
     * Sets a prefix that will be prepended to the pattern(s) of the next high-level matching operation
     * (startsWith, endsWith, contains, advanceAfter).
     *
     * @param string $prefix The prefix string.
     * @return self Returns the current instance for method chaining.
     */
    public function addPrefixToNext(string $prefix): self
    {
        if ($this->matchFailed) {
            return $this; // Allow if chain already failed? Or no-op? Let's allow.
        }

        $this->prefixForNextOperation = $prefix;
        $this->logDebug("addPrefixToNext: Prefix '{$prefix}' set for the next operation.");
        return $this;
    }


    // --- NEW PRIMITIVE AND COMPOUND MATCHING METHODS ---

    /**
     * Helper method to try a sub-matching logic.
     * Manages currentIndex rollback for the sub-logic if it fails.
     * The sub-logic itself is responsible for setting $this->matchFailed if its pattern cannot be met.
     * This method then checks that flag to determine success of the sub-logic.
     *
     * @param callable $logic A callable that takes this Maya instance and performs matching operations.
     * @return bool True if the sub-logic succeeded (did not set $this->matchFailed), false otherwise.
     */
    protected function trySubMatch(callable $logic): bool
    {
        // If the main chain has already failed, sub-matches cannot succeed.
        // This check is important for compound matchers like oneOrMore to stop early.
        if ($this->matchFailed) {
            return false;
        }

        $initialIndex = $this->currentIndex;

        // Store the chain's current overall failure status.
        // This is crucial because the $logic callable might itself contain
        // optional parts or alternatives that locally succeed even if parts of them fail.
        $chainFailureStatusBeforeSubLogicAttempt = $this->matchFailed;

        // Temporarily give the sub-logic a "clean slate" regarding $this->matchFailed
        // for *its own isolated execution*. If the sub-logic fails, it will set $this->matchFailed.
        $this->matchFailed = false;

        $logic($this); // Execute the sub-logic

        if ($this->matchFailed) { // The sub-logic ($logic) indicated a failure for its part.
            $this->currentIndex = $initialIndex; // Backtrack cursor changes made by the failed sub-logic.
            $this->matchFailed = $chainFailureStatusBeforeSubLogicAttempt; // Restore original chain failure state.
            return false; // Sub-logic failed
        }

        // Sub-logic succeeded. Restore the original chain failure state.
        // (It should typically be false if we reached this point and sub-logic also succeeded).
        $this->matchFailed = $chainFailureStatusBeforeSubLogicAttempt;
        return true; // Sub-logic succeeded
    }

    /**
     * Tries to match a literal string at the current position.
     * Advances the current index if successful.
     *
     * @param string $str The string to match.
     * @return self
     */
    public function literal(string $str): self
    {
        // Do not proceed if chain already failed.
        if ($this->matchFailed) {
            return $this;
        }

        // Matching an empty string always succeeds and consumes nothing.
        if ($str === '') {
            $this->logDebug("Matched empty literal string at index {$this->currentIndex}.");
            return $this;
        }

        $len = \strlen($str);
        // Check if there's enough text remaining
        if ($this->currentIndex + $len > strlen($this->originalText)) {
            $this->logDebug("Failed to match literal '{$str}': not enough characters remaining.");
            $this->matchFailed = true;
            return $this;
        }

        if (substr($this->originalText, $this->currentIndex, $len) === $str) {
            $this->logDebug("Matched literal '{$str}' at index {$this->currentIndex}.");
            $this->currentIndex += $len;
        } else {
            $this->logDebug("Failed to match literal '{$str}' at index {$this->currentIndex}. Attempted on: '" . substr($this->originalText, $this->currentIndex, $len) . "'");
            $this->matchFailed = true;
        }
        return $this;
    }

    /**
     * Matches any single character.
     * @return self
     */
    public function anyChar(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if ($this->currentIndex < strlen($this->originalText)) {
            // Determine length of the next UTF-8 character to advance correctly
            $firstByte = ord($this->originalText[$this->currentIndex]);
            $len = 0;
            if ($firstByte < 0x80) {
                $len = 1;
            } elseif (($firstByte & 0xE0) == 0xC0) {
                $len = 2;
            } elseif (($firstByte & 0xF0) == 0xE0) {
                $len = 3;
            } elseif (($firstByte & 0xF8) == 0xF0) {
                $len = 4;
            }

            if ($len > 0 && ($this->currentIndex + $len <= strlen($this->originalText))) {
                $char = substr($this->originalText, $this->currentIndex, $len);
                $this->logDebug("Matched anyChar: '{$char}' at index {$this->currentIndex}.");
                $this->currentIndex += $len;
            } else {
                $this->logDebug("Failed to match anyChar: incomplete UTF-8 sequence at index {$this->currentIndex}.");
                $this->matchFailed = true;
            }
        } else {
            $this->logDebug("Failed to match anyChar: at end of string.");
            $this->matchFailed = true;
        }
        return $this;
    }


    /**
     * Tries to match a single character satisfying a condition.
     * Internal helper for specific char matchers.
     *
     * @param callable $condition Callback (string $char): bool
     * @param string $debugCharType Description for logging
     * @return bool True if matched, false otherwise. This method itself does NOT set $this->matchFailed.
     */
    private function matchCharIf(callable $condition, string $debugCharType): bool
    {
        if ($this->currentIndex >= strlen($this->originalText)) {
            $this->logDebug("Attempted to match {$debugCharType} at end of string (matchCharIf).");
            return false;
        }
        // This assumes single-byte characters for conditions like ctype_*.
        // For Unicode properties, use matchUnicodeCharIf.
        $char = $this->originalText[$this->currentIndex];
        if ($condition($char)) {
            $this->logDebug("Matched {$debugCharType}: '{$char}' at index {$this->currentIndex}.");
            $this->currentIndex++;
            return true;
        }
        $this->logDebug("Failed to match {$debugCharType} at index {$this->currentIndex} with char '{$char}'.");
        return false;
    }

    /**
     * Tries to match a single Unicode character satisfying a PCRE pattern (e.g., a character class).
     * Internal helper.
     *
     * @param string $pcreCharClassPattern PCRE pattern for a single char (e.g., '\p{L}', '[\x{AC00}-\x{D7A3}]').
     * @param string $debugCharType Description for logging.
     * @return bool True if matched, false otherwise. This method itself does NOT set $this->matchFailed.
     */
    private function matchUnicodeCharIf(string $pcreCharClassPattern, string $debugCharType): bool
    {
        if ($this->currentIndex >= strlen($this->originalText)) {
            $this->logDebug("Attempted to match {$debugCharType} at end of string (matchUnicodeCharIf).");
            return false;
        }

        $firstByte = ord($this->originalText[$this->currentIndex]);
        $len = 0;
        if ($firstByte < 0x80) {
            $len = 1;
        } elseif (($firstByte & 0xE0) == 0xC0) {
            $len = 2;
        } elseif (($firstByte & 0xF0) == 0xE0) {
            $len = 3;
        } elseif (($firstByte & 0xF8) == 0xF0) {
            $len = 4;
        }

        if ($len === 0 || ($this->currentIndex + $len > strlen($this->originalText))) {
            $this->logDebug("Invalid or incomplete UTF-8 sequence for {$debugCharType} at index {$this->currentIndex}.");
            return false;
        }
        $char = substr($this->originalText, $this->currentIndex, $len);

        if (preg_match('/^' . $pcreCharClassPattern . '$/u', $char)) {
            $this->logDebug("Matched Unicode {$debugCharType}: '{$char}' at index {$this->currentIndex}.");
            $this->currentIndex += $len;
            return true;
        }
        $this->logDebug("Failed to match Unicode {$debugCharType} ('{$pcreCharClassPattern}') at index {$this->currentIndex} with char '{$char}'.");
        return false;
    }

    /** Matches a single digit '0'-'9'. @return self */
    public function isDigit(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (!$this->matchCharIf(fn($c) => ctype_digit($c), 'digit')) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /** Matches a single alphabetic character [a-zA-Z]. @return self */
    public function isAlpha(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (!$this->matchCharIf(fn($c) => ctype_alpha($c), 'alpha')) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /** Matches a single alphanumeric character [a-zA-Z0-9]. @return self */
    public function isAlphanum(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (!$this->matchCharIf(fn($c) => ctype_alnum($c), 'alphanum')) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /** Matches a single whitespace character. @return self */
    public function isWhitespace(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (!$this->matchCharIf(fn($c) => ctype_space($c), 'whitespace')) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /** Matches a single hexadecimal digit [0-9a-fA-F]. @return self */
    public function isHexDigit(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (!$this->matchCharIf(fn($c) => ctype_xdigit($c), 'hex digit')) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /**
     * Matches a single character from the provided set (ASCII/single-byte oriented).
     * @param string $charSet A string containing all allowed characters.
     * @return self
     */
    public function isCharInSet(string $charSet): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (empty($charSet)) { // Matching in an empty set always fails.
            $this->logDebug("Failed to match char in empty set.");
            $this->matchFailed = true;
            return $this;
        }

        if (!$this->matchCharIf(fn($c) => strpos($charSet, $c) !== false, "char in set '{$charSet}'")) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /** Matches a single Korean character. @return self */
    public function isKoreanChar(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (!$this->matchUnicodeCharIf('[\x{AC00}-\x{D7A3}]', 'Korean char')) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /** Matches a single Japanese character (Hiragana, Katakana, Kanji, iteration marks). @return self */
    public function isJapaneseChar(): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        if (!$this->matchUnicodeCharIf('[ぁ-んァ-ヶー一-龠]', 'Japanese char')) {
            $this->matchFailed = true;
        }

        return $this;
    }

    /** Matches a single Hiragana character. @return self */
    public function isHiraganaChar(): self
    {
        if ($this->matchFailed)
            return $this;
        if (!$this->matchUnicodeCharIf('[ぁ-ん]', 'Hiragana char'))
            $this->matchFailed = true;
        return $this;
    }

    /** Matches a single Katakana character. @return self */
    public function isKatakanaChar(): self
    {
        if ($this->matchFailed)
            return $this;
        if (!$this->matchUnicodeCharIf('[ァ-ヶー]', 'Katakana char'))
            $this->matchFailed = true;
        return $this;
    }

    /** Matches a single Kanji character (common CJK Unified Ideographs). @return self */
    public function isKanjiChar(): self
    {
        if ($this->matchFailed)
            return $this;
        if (!$this->matchUnicodeCharIf('[一-龠]', 'Kanji char'))
            $this->matchFailed = true;
        return $this;
    }

    /** Matches a single English alphabet OR Korean character. @return self */
    public function isEnglishOrKoreanChar(): self
    {
        if ($this->matchFailed)
            return $this;

        $initialIndex = $this->currentIndex; // For potential backtrack if first attempt fails
        // Try English Alpha first (common case, potentially faster for ASCII)
        if ($this->matchCharIf(fn($c) => ctype_alpha($c), 'English char for EnglishOrKorean')) {
            // Succeeded
        } // Else, try Korean
        else if ($this->matchUnicodeCharIf('[\x{AC00}-\x{D7A3}]', 'Korean char for EnglishOrKorean')) {
            // Succeeded
        } // Both failed
        else {
            $this->currentIndex = $initialIndex; // Ensure index is where it started before this combined check
            $this->logDebug("Failed to match EnglishOrKoreanChar at index {$initialIndex}.");
            $this->matchFailed = true;
        }
        return $this;
    }

    /**
     * Matches the provided logic one or more times.
     *
     * @param callable $matcherLogic Logic to apply (takes Maya instance: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function oneOrMore(callable $matcherLogic): self
    {
        if ($this->matchFailed)
            return $this;
        $this->logDebug("Attempting oneOrMore...");

        $count = 0;
        $initialIndexForOneOrMore = $this->currentIndex;

        while (true) {
            $indexBeforeThisIteration = $this->currentIndex;
            // trySubMatch will call $matcherLogic. If $matcherLogic fails, trySubMatch will set $this->matchFailed to false (its original state)
            // and return false. If $matcherLogic succeeds, trySubMatch returns true.
            if ($this->trySubMatch($matcherLogic)) {
                // Sub-match succeeded for this iteration
                if ($this->currentIndex === $indexBeforeThisIteration) { // Matched an empty sequence
                    if ($count === 0) { // First iteration cannot match empty for oneOrMore
                        $this->logDebug("oneOrMore: First iteration matched an empty sequence. This is not allowed. Failing oneOrMore.");
                        $count = 0; // Explicitly ensure count is 0 to trigger failure
                    } else {
                        $this->logDebug("oneOrMore: Iteration " . ($count + 1) . " successfully matched an empty sequence after non-empty matches. Assuming completion.");
                    }
                    break; // Avoid infinite loop whether first or subsequent empty match
                }
                $count++;
                $this->logDebug("oneOrMore: iteration {$count} succeeded.");
            } else {
                // trySubMatch indicated failure of the sub-logic for this iteration
                $this->logDebug("oneOrMore: Sub-logic failed for iteration " . ($count + 1) . ". Breaking loop.");
                break;
            }
        }

        if ($count === 0) {
            $this->logDebug("oneOrMore: Failed, did not match even once.");
            $this->matchFailed = true;
            $this->currentIndex = $initialIndexForOneOrMore; // Ensure full backtrack for the oneOrMore failure
        } else {
            $this->logDebug("oneOrMore: Succeeded with {$count} matches.");
            // $this->matchFailed remains as it was before this oneOrMore call (ideally false).
        }
        return $this;
    }

    /**
     * Matches the provided logic zero or more times. This operation itself always "succeeds"
     * in terms of chain continuation, meaning it won't set $this->matchFailed to true.
     *
     * @param callable $matcherLogic Logic to apply (takes Maya instance: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function zeroOrMore(callable $matcherLogic): self
    {
        if ($this->matchFailed)
            return $this; // If chain already failed, this is a no-op
        $this->logDebug("Attempting zeroOrMore...");
        $count = 0;
        while (true) {
            // If the chain has failed due to a previous (non-optional) part *within* an iteration of zeroOrMore, stop.
            // This check is now effectively handled by trySubMatch's initial check.
            if ($this->matchFailed)
                break;

            $indexBeforeThisIteration = $this->currentIndex;
            if ($this->trySubMatch($matcherLogic)) {
                if ($this->currentIndex === $indexBeforeThisIteration) {
                    $this->logDebug("zeroOrMore: iteration " . ($count + 1) . " successfully matched an empty sequence. Assuming completion to avoid infinite loop.");
                    break;
                }
                $count++;
                $this->logDebug("zeroOrMore: iteration {$count} succeeded.");
            } else {
                $this->logDebug("zeroOrMore: Sub-logic failed for iteration " . ($count + 1) . ". Breaking loop.");
                break;
            }
        }
        $this->logDebug("zeroOrMore: Completed with {$count} matches. This operation itself does not fail the chain.");
        // $this->matchFailed is NOT set to true by zeroOrMore itself. It preserves the chain's prior failure state.
        return $this;
    }

    /**
     * Optionally matches the provided logic. This operation itself always "succeeds"
     * in terms of chain continuation.
     *
     * @param callable $matcherLogic Logic to apply (takes Maya instance: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function optional(callable $matcherLogic): self
    {
        if ($this->matchFailed)
            return $this; // If chain already failed, this is a no-op
        $this->logDebug("Attempting optional...");

        // trySubMatch attempts the logic. If $matcherLogic fails, trySubMatch will backtrack currentIndex
        // and restore $this->matchFailed to what it was before the trySubMatch call.
        // The success/failure of $matcherLogic doesn't cause `optional` itself to fail the chain.
        $this->trySubMatch($matcherLogic);

        $this->logDebug("Optional: attempt completed. Chain failure status preserved.");
        return $this;
    }

    /**
     * Matches the provided logic exactly N times.
     *
     * @param int $n Number of times to match. Must be >= 0.
     * @param callable $matcherLogic Logic to apply (takes Maya instance: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function times(int $n, callable $matcherLogic): self
    {
        if ($this->matchFailed)
            return $this;
        if ($n < 0) {
            $this->logDebug("times: Called with n < 0 ({$n}). Invalid argument. Failing.");
            $this->matchFailed = true; // Or throw InvalidArgumentException
            return $this;
        }
        if ($n === 0) {
            $this->logDebug("times: Called with n = 0. Succeeding vacuously (matches nothing).");
            return $this;
        }

        $this->logDebug("Attempting times({$n})...");
        $initialIndexForTimes = $this->currentIndex;
        $count = 0;
        for ($i = 0; $i < $n; $i++) {
            $indexBeforeThisIteration = $this->currentIndex;
            if ($this->trySubMatch($matcherLogic)) {
                // For `times`, an empty match is problematic as it doesn't represent a distinct "time".
                if ($this->currentIndex === $indexBeforeThisIteration) {
                    $this->logDebug("times: Iteration " . ($i + 1) . " matched an empty sequence. Failing 'times({$n})'.");
                    $this->matchFailed = true; // Matching empty `n` times is ambiguous/problematic for distinct counts.
                    $this->currentIndex = $initialIndexForTimes;
                    return $this;
                }
                $count++;
            } else {
                // Failed to match one of the required times
                $this->logDebug("times({$n}): Failed on iteration " . ($i + 1) . ". Matched only {$count} times.");
                $this->matchFailed = true;
                $this->currentIndex = $initialIndexForTimes; // Rollback
                return $this;
            }
        }

        // If loop completes, means $count === $n
        $this->logDebug("times({$n}): Succeeded.");
        return $this;
    }

    /**
     * Matches the provided logic between min and max times (inclusive).
     * Greedily matches as many as possible up to max.
     *
     * @param int $min Minimum number of times (>=0).
     * @param int $max Maximum number of times (>=min).
     * @param callable $matcherLogic Logic to apply (takes Maya instance: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function timesBetween(int $min, int $max, callable $matcherLogic): self
    {
        if ($this->matchFailed)
            return $this;
        if ($min < 0 || $max < $min) {
            $this->logDebug("timesBetween: Invalid min/max ({$min},{$max}). Failing.");
            $this->matchFailed = true; // Or throw InvalidArgumentException
            return $this;
        }
        if ($max === 0) { // implies min is also 0
            $this->logDebug("timesBetween: Called with max = 0 (so min=0). Succeeding vacuously.");
            return $this;
        }

        $this->logDebug("Attempting timesBetween({$min}, {$max})...");
        $initialIndexForTimesBetween = $this->currentIndex;
        $count = 0;

        for ($i = 0; $i < $max; $i++) {
            $indexBeforeThisIteration = $this->currentIndex;
            if ($this->trySubMatch($matcherLogic)) {
                if ($this->currentIndex === $indexBeforeThisIteration) { // Matched empty
                    $this->logDebug("timesBetween: iteration " . ($i + 1) . " matched empty. Breaking additional matches.");
                    break; // Stop matching more if it's empty, to avoid issues if min isn't met by non-empty.
                }
                $count++;
            } else {
                break; // Stop trying if one fails
            }
        }

        if ($count >= $min) {
            $this->logDebug("timesBetween({$min},{$max}): Succeeded with {$count} matches.");
            // Current $this->currentIndex is correct as it stands after the successful matches.
        } else {
            $this->logDebug("timesBetween({$min},{$max}): Failed. Matched only {$count} times (min was {$min}). Rolling back.");
            $this->matchFailed = true;
            $this->currentIndex = $initialIndexForTimesBetween; // Rollback
        }
        return $this;
    }

    /**
     * Tries a list of matchers in order. Succeeds if any one of them succeeds.
     * Backtracks between attempts. The first successful match is taken.
     *
     * @param callable[] $matcherLogics Array of callables (each: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function eitherTry(array $matcherLogics): self
    {
        if ($this->matchFailed)
            return $this;
        if (empty($matcherLogics)) {
            $this->logDebug("eitherTry: No matchers provided. Failing.");
            $this->matchFailed = true;
            return $this;
        }

        $this->logDebug("Attempting eitherTry with " . count($matcherLogics) . " options...");
        // $initialIndexForEither = $this->currentIndex; // trySubMatch handles its own rollback
        // $initialMatchFailedState = $this->matchFailed; // trySubMatch also preserves this for the chain

        foreach ($matcherLogics as $i => $logic) {
            $this->logDebug("eitherTry: Trying option " . ($i + 1));
            if ($this->trySubMatch($logic)) {
                $this->logDebug("eitherTry: Option " . ($i + 1) . " succeeded.");
                // First success, exit. trySubMatch ensures $this->matchFailed is correct for the chain.
                return $this;
            }
            // If trySubMatch returned false, it has already reset its own currentIndex modification
            // and restored $this->matchFailed to what it was before its attempt.
            // So the next option in the loop starts from the correct state.
            $this->logDebug("eitherTry: Option " . ($i + 1) . " failed.");
        }

        $this->logDebug("eitherTry: All options failed.");
        $this->matchFailed = true; // All attempts failed, so eitherTry itself fails the chain.
        // $this->currentIndex remains where it was before the eitherTry call because all sub-matches backtracked.
        return $this;
    }

    /**
     * Checks if the provided logic consumes the entire remaining text from the current position.
     *
     * @param callable $matcherLogic Logic to apply (takes Maya instance: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function theRestSatisfies(callable $matcherLogic): self
    {
        if ($this->matchFailed) {
            return $this;
        }

        $this->logDebug("Attempting theRestSatisfies...");

        $initialIndexBeforeLogic = $this->currentIndex;
        // $initialFailedState = $this->matchFailed; // Store this to see if $matcherLogic itself failed

        // Execute the provided logic. It will update $this->currentIndex and potentially $this->matchFailed.
        $matcherLogic($this);

        if (!$this->matchFailed) { // Core logic itself succeeded without setting the failure flag
            if ($this->currentIndex === strlen($this->originalText)) {
                $this->logDebug("theRestSatisfies: Logic succeeded and consumed entire remaining text.");
            } else {
                $this->logDebug("theRestSatisfies: Logic succeeded but did NOT consume entire remaining text. Current: {$this->currentIndex}, End: " . strlen($this->originalText) . ". Failing.");
                $this->matchFailed = true; // Mark failure
                // Rollback cursor to where it was before $matcherLogic, as it didn't satisfy "theRest".
                // $this->currentIndex = $initialIndexBeforeLogic; // This might be too aggressive if partial success of $matcherLogic was intended to be kept before this check.
                // For "theRestSatisfies", if it's not the rest, the whole operation fails.
            }
        } else {
            $this->logDebug("theRestSatisfies: Core logic itself reported a failure.");
            // $this->matchFailed is already true. currentIndex might have moved, but the operation failed.
            // No need to roll back $currentIndex here as the failure is from $matcherLogic.
            // If $matcherLogic had complex internal backtracking, it should have handled it.
        }
        return $this;
    }

    /**
     * Resets the matcher and checks if the provided logic consumes the entire original text.
     *
     * @param callable $matcherLogic Logic to apply (takes Maya instance: `fn(Maya $m) => $m->...`).
     * @return self
     */
    public function matchEntireString(callable $matcherLogic): self
    {
        $this->logDebug("Attempting matchEntireString...");
        $this->reset(); // Start fresh from the beginning of the original text
        return $this->theRestSatisfies($matcherLogic);
    }

    // --- UTILITY METHODS ---

    /**
     * Resets the matching state of the Maya instance.
     * The current index is reset to 0, matchFailed is set to false,
     * and any pending prefix is cleared. The original text remains.
     * Useful for reusing the instance for a new sequence of operations on the same text.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function reset(): self
    {
        $this->currentIndex = 0;
        $this->matchFailed = false;
        $this->prefixForNextOperation = null;
        $this->logDebug("reset: State reset. Current index: 0. Match status: OK.");
        return $this;
    }

    /**
     * Checks if all chained operations up to this point were successful.
     *
     * @return bool True if all operations succeeded, false otherwise.
     */
    public function isSuccess(): bool
    {
        $status = !$this->matchFailed;
        $this->logDebug("isSuccess: Called. Result: " . ($status ? 'true' : 'false'));
        return $status;
    }

    /**
     * Gets the current index (position) in the original text.
     * This indicates how much of the text has been processed or matched.
     *
     * @return int The current zero-based index.
     */
    public function getCurrentIndex(): int
    {
        return $this->currentIndex;
    }

    /**
     * Gets the portion of the original text from the beginning up to the current index.
     * This can be seen as the "processed" part of the text.
     *
     * @return string The text from index 0 to the current index.
     */
    public function getProcessedText(): string
    {
        return substr($this->originalText, 0, $this->currentIndex);
    }

    /**
     * Gets the remaining portion of the original text from the current index to the end.
     *
     * @return string The text from the current index onwards.
     */
    public function getRemainingText(): string
    {
        $remaining = substr($this->originalText, $this->currentIndex);
        return $remaining === false ? '' : $remaining;
    }
}