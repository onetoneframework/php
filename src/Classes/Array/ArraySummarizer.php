<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use function is_string;
use function count;
use function sprintf;
use function intval;
use function ord;
use function mb_strlen;
use function mb_substr;
use function is_array;
use function is_bool;
use function is_object;
use function is_float;
use function floatval;
use function strlen;

/**
 * Class ArraySummarizer
 *
 * Provides functionality to summarize arrays of strings containing alphanumeric patterns.
 */
class ArraySummarizer extends BaseClass
{
    /**
     * Summarizes an array of strings containing alphanumeric patterns.
     * Recursively processes nested arrays and extracts values from associative arrays.
     *
     * @param array $arr The input array of strings to summarize.
     * 
     * @return string A summarized representation of the input array.
     */
    public function summarizeArray(array $arr): string
    {
        $stringItems = [];
        $nestedResults = [];

        // Separate strings and nested arrays
        foreach ($arr as $item) {
            if (is_array($item)) {
                $nested = $this->summarizeArray($item);
                if ($nested !== '') {
                    $nestedResults[] = '[' . $nested . ']';
                }
                continue;
            }

            if (is_string($item)) {
                $stringItems[] = $item;
            } else if ($item !== null && !is_object($item)) {
                // Convert scalar values (int, float, bool) to strings
                if (is_bool($item)) {
                    $stringItems[] = $item ? '1' : '0';
                } else {
                    $stringItems[] = (string) $item;
                }
            }
        }

        if (empty($stringItems)) {
            return implode(' ', $nestedResults);
        }

        // Group items by pattern
        $grouped = $this->groupByPattern($stringItems);
        $summary = [];

        foreach ($grouped as $group) {
            $summaryText = $this->summarizeGroup($group);
            if ($summaryText !== '') {
                $summary[] = $summaryText;
            }
        }

        // Merge with nested results
        $summary = array_merge($summary, $nestedResults);
        return implode(' ', $summary);
    }

    /**
     * Converts a summarization string produced by summarizeArray back into a regular expression.
     * The resulting regular expression can match any of the unsummarized values forming the array.
     *
     * @param string $summary The summarized string.
     * @return string A valid PCRE regular expression matching the underlying strings.
     */
    public function summaryToRegex(string $summary): string
    {
        if (empty(trim($summary))) {
            return '';
        }

        $pattern = $this->summaryToRegexPattern($summary);
        return '/^(?:' . $pattern . ')$/u';
    }

    /**
     * Internal method to parse the summary string and compile it into a regex pattern without boundaries.
     *
     * @param string $summary The summarized string.
     * @return string Formatted regex pattern.
     */
    private function summaryToRegexPattern(string $summary): string
    {
        $length = mb_strlen($summary);
        $buffer = '';
        $depth = 0;

        $groups = [];
        $currentGroup = '';

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($summary, $i, 1);
            if ($char === '[') {
                $depth++;
                $buffer .= $char;
            } elseif ($char === ']') {
                $depth--;
                $buffer .= $char;
                if ($depth === 0) {
                    $currentGroup .= $this->parseSummaryToken($buffer);
                    $buffer = '';
                }
            } elseif ($char === ' ' && $depth === 0) {
                if ($currentGroup !== '') {
                    $groups[] = $currentGroup;
                    $currentGroup = '';
                }
            } else {
                if ($depth > 0) {
                    $buffer .= $char;
                } else {
                    $currentGroup .= preg_quote($char, '/');
                }
            }
        }

        if ($currentGroup !== '') {
            $groups[] = $currentGroup;
        }

        if (empty($groups)) {
            return '';
        }

        return implode('|', $groups);
    }

    /**
     * Parses an individual token mapping brackets to regex equivalents.
     *
     * @param string $token Token string wrapped in brackets
     * @return string Regex string representation of the token
     */
    private function parseSummaryToken(string $token): string
    {
        if (mb_substr($token, 0, 1) === '[' && mb_substr($token, -1) === ']') {
            $inner = mb_substr($token, 1, -1);

            // Nested arrays: [[...]]
            if (mb_substr($inner, 0, 1) === '[' && mb_substr($inner, -1) === ']') {
                return $this->summaryToRegexPattern($inner);
            }

            // Alternations: a||b||c
            if (mb_strpos($inner, '||') !== false) {
                $parts = explode('||', $inner);
                $escaped = array_map(function ($p) {
                    return preg_quote($p, '/');
                }, $parts);

                return '(?:' . implode('|', $escaped) . ')';
            }

            // Multiple comma-separated definitions
            $subTokens = [];
            $subBuf = '';
            $parenDepth = 0;
            $len = mb_strlen($inner);

            for ($j = 0; $j < $len; $j++) {
                $ch = mb_substr($inner, $j, 1);
                if ($ch === '(') {
                    $parenDepth++;
                } elseif ($ch === ')') {
                    $parenDepth--;
                }

                if ($ch === ',' && $parenDepth === 0) {
                    $subTokens[] = $this->parseSummarySubToken($subBuf);
                    $subBuf = '';
                } else {
                    $subBuf .= $ch;
                }
            }

            if ($subBuf !== '') {
                $subTokens[] = $this->parseSummarySubToken($subBuf);
            }

            if (count($subTokens) > 1) {
                return '(?:' . implode('|', $subTokens) . ')';
            } else {
                return $subTokens[0];
            }
        }

        return preg_quote($token, '/');
    }

    /**
     * Parses the sub-token specifics (ranges, steps, differences) to regex logic.
     *
     * @param string $inner Formatted specific string to identify.
     * @return string Extrapolated regex format representation of sub token.
     */
    private function parseSummarySubToken(string $inner): string
    {
        // Decimal range: 1.5-3.0(step:0.5)
        if (preg_match('/^([0-9\.]+)-([0-9\.]+)\(step:([0-9\.]+)\)$/u', $inner, $matches)) {
            return '[0-9\.]+';
        }

        // Int range: 001-003 or 1-3 or 5-1
        if (preg_match('/^(\d+)-(\d+)$/u', $inner, $matches)) {
            $len1 = strlen($matches[1]);
            $len2 = strlen($matches[2]);
            if ($len1 === $len2 && $matches[1][0] === '0') {
                return '\d{' . $len1 . '}';
            }
            return '\d+';
        }

        // Float range without step: 1.0-3.0
        if (preg_match('/^([0-9\.]+)-([0-9\.]+)$/u', $inner, $matches)) {
            return '[0-9\.]+';
        }

        // Arithmetic sequence: (10 + 0n ~ 100)
        if (preg_match('/^\([0-9\.]+ \+ [0-9\.]+n ~ [0-9\.]+\)$/u', $inner)) {
            return '\d+';
        }

        // Letter range: a-c
        if (preg_match('/^([a-zA-Z])-([a-zA-Z])$/u', $inner, $matches)) {
            return '[' . $matches[1] . '-' . $matches[2] . ']';
        }

        return preg_quote($inner, '/');
    }

    /**
     * Group items by their pattern structure.
     *
     * @param array $items Array of string items to group
     * @return array Grouped items
     */
    private function groupByPattern(array $items): array
    {
        if (empty($items)) {
            return [];
        }

        $groups = [];

        foreach ($items as $item) {
            $pattern = $this->getItemPattern($item);
            if (!isset($groups[$pattern])) {
                $groups[$pattern] = [];
            }
            $groups[$pattern][] = $item;
        }

        return array_values($groups);
    }

    /**
     * Get the pattern signature of an item.
     * Identifies the structure: non-digit, digit, non-digit, digit patterns.
     *
     * @param string $item
     * @return string Pattern signature
     */
    private function getItemPattern(string $item): string
    {
        $len = mb_strlen($item);
        $parts = [];
        $currentType = null; // 'letter' or 'digit'

        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($item, $i, 1);
            $isDigit = preg_match('/\d/u', $char);

            if ($isDigit) {
                if ($currentType !== 'digit') {
                    if ($currentType !== null) {
                        $parts[] = 'x'; // letter section
                    }
                    $currentType = 'digit';
                }
            } else {
                if ($currentType !== 'letter') {
                    if ($currentType !== null) {
                        $parts[] = 'd'; // digit section
                    }
                    $currentType = 'letter';
                }
            }
        }

        // Close the last section
        if ($currentType === 'digit') {
            $parts[] = 'd';
        } else {
            $parts[] = 'x';
        }

        return implode('', $parts);
    }

    /**
     * Summarize a group of similar items.
     *
     * @param array $group Group of items to summarize
     * @return string Summarized representation
     */
    private function summarizeGroup(array $group): string
    {
        if (empty($group)) {
            return '';
        }

        if (count($group) === 1) {
            return '[' . $group[0] . ']';
        }

        $isAllIdentical = true;
        foreach ($group as $item) {
            if ($item !== $group[0]) {
                $isAllIdentical = false;
                break;
            }
        }
        if ($isAllIdentical) {
            return '[' . $group[0] . ']';
        }

        // Find common prefix and suffix
        $commonPrefix = $this->findCommonPrefix($group);
        $commonSuffix = $this->findCommonSuffix($group);

        // Adjust common prefix to prevent splitting numbers
        while ($commonPrefix !== '' && preg_match('/\d/u', mb_substr($commonPrefix, -1))) {
            $commonPrefix = mb_substr($commonPrefix, 0, -1);
        }

        // Extract variable parts
        $variableParts = [];
        $prefixLen = mb_strlen($commonPrefix);
        $suffixLen = mb_strlen($commonSuffix);

        foreach ($group as $item) {
            $itemLen = mb_strlen($item);
            $var = ($suffixLen > 0)
                ? mb_substr($item, $prefixLen, $itemLen - $prefixLen - $suffixLen)
                : mb_substr($item, $prefixLen);

            if ($var !== '') {
                $variableParts[] = $var;
            }
        }

        // Summarize variable parts
        $varSummary = $this->summarizeVariableParts($variableParts);

        // Build result
        $result = '';
        if ($commonPrefix !== '') {
            $result .= '[' . $commonPrefix . ']';
        }
        if ($varSummary !== '') {
            $result .= $varSummary;
        }
        if ($commonSuffix !== '') {
            $result .= '[' . $commonSuffix . ']';
        }

        return $result ?: '[' . $group[0] . ']';
    }

    /**
     * Find common prefix of strings.
     *
     * @param array $items
     * @return string
     */
    private function findCommonPrefix(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $prefix = '';
        $minLen = min(array_map('mb_strlen', $items));

        for ($i = 0; $i < $minLen; $i++) {
            $char = mb_substr($items[0], $i, 1);
            $match = true;
            foreach ($items as $item) {
                if (mb_substr($item, $i, 1) !== $char) {
                    $match = false;
                    break;
                }
            }
            if (!$match) {
                break;
            }
            $prefix .= $char;
        }

        return $prefix;
    }

    /**
     * Find common suffix of strings.
     *
     * @param array $items
     * @return string
     */
    private function findCommonSuffix(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $suffix = '';
        $minLen = min(array_map('mb_strlen', $items));

        for ($i = 1; $i <= $minLen; $i++) {
            $char = mb_substr($items[0], -$i, 1);
            $match = true;
            foreach ($items as $item) {
                if (mb_substr($item, -$i, 1) !== $char) {
                    $match = false;
                    break;
                }
            }
            if (!$match) {
                break;
            }
            $suffix = $char . $suffix;
        }

        return $suffix;
    }

    /**
     * Summarize variable parts (letters or numbers).
     *
     * @param array $variableParts
     * @return string
     */
    private function summarizeVariableParts(array $variableParts): string
    {
        if (empty($variableParts)) {
            return '';
        }

        // Check if all parts are single letters
        $allSingleLetters = true;
        $letters = [];
        foreach ($variableParts as $part) {
            if (mb_strlen($part) !== 1 || !preg_match('/[a-zA-Z]/u', $part)) {
                $allSingleLetters = false;
                break;
            }
            $letters[] = $part;
        }

        if ($allSingleLetters) {
            return '[' . $this->summarizeLetters($letters) . ']';
        }

        // Check if all parts are single digits
        $allSingleDigits = true;
        $digitsStr = [];
        foreach ($variableParts as $part) {
            if (mb_strlen($part) !== 1 || !preg_match('/\d/', $part)) {
                $allSingleDigits = false;
                break;
            }
            $digitsStr[] = $part;
        }

        if ($allSingleDigits) {
            return '[' . $this->summarizeNumericStrings($digitsStr) . ']';
        }

        // Check if all parts are numeric (including multi-digit numbers)
        $allNumeric = true;
        $numbersStr = [];
        foreach ($variableParts as $part) {
            if (!preg_match('/^\d+(?:\.\d+)?$/u', $part)) {
                $allNumeric = false;
                break;
            }
            $numbersStr[] = $part;
        }

        if ($allNumeric) {
            return '[' . $this->summarizeNumericStrings($numbersStr) . ']';
        }

        // Try to detect compound patterns (prefix_suffix)
        $compoundResult = $this->summarizeCompoundVariableParts($variableParts);
        if ($compoundResult !== '') {
            return $compoundResult;
        }

        // Otherwise, just collect them
        return '[' . implode(',', array_unique($variableParts)) . ']';
    }

    /**
     * Summarize compound variable parts (e.g., app_1, app_2, db_1, db_2)
     * Detects when parts have prefix-suffix structure and can be grouped.
     *
     * @param array $variableParts
     * @return string Empty string if not a compound pattern, otherwise the summary
     */
    private function summarizeCompoundVariableParts(array $variableParts): string
    {
        if (count($variableParts) < 2) {
            return '';
        }

        // Try to decompose each variable part into prefix and suffix
        $decomposed = [];
        foreach ($variableParts as $part) {
            // Find the boundary between letters and digits (or vice versa)
            $len = mb_strlen($part);
            $lastLetterPos = -1;

            for ($i = 0; $i < $len; $i++) {
                $char = mb_substr($part, $i, 1);
                if (!preg_match('/^\d+$/u', $char)) {
                    // This is a letter or non-digit
                    $lastLetterPos = $i;
                }
            }

            // If we found letters, check if they're followed by digits
            if ($lastLetterPos >= 0 && $lastLetterPos < $len - 1) {
                $prefix = mb_substr($part, 0, $lastLetterPos + 1);
                $suffix = mb_substr($part, $lastLetterPos + 1);

                // Check if suffix is numeric
                if (preg_match('/^\d+(?:\.\d+)?$/u', $suffix)) {
                    $decomposed[] = [
                        'original' => $part,
                        'prefix' => $prefix,
                        'suffix_str' => $suffix
                    ];
                    continue;
                }
            }

            // Could not decompose this part into prefix + numeric suffix
            return '';
        }

        // All parts decomposed successfully - check if they can be compressed
        if (count($decomposed) !== count($variableParts)) {
            return '';
        }

        // Extract unique prefixes
        $prefixes = array_unique(array_map(fn($item) => $item['prefix'], $decomposed));
        sort($prefixes);

        // If we have multiple different prefixes and consistent suffixes
        if (count($prefixes) > 1) {
            $uniqueSuffixStrs = array_values(array_unique(array_map(fn($item) => $item['suffix_str'], $decomposed)));
            $suffixSummary = $this->summarizeNumericStrings($uniqueSuffixStrs);
            if (strpos($suffixSummary, ',') === false) {
                $prefixPart = '[' . implode('||', $prefixes) . ']';
                return $prefixPart . '[' . $suffixSummary . ']';
            }
        }

        return '';
    }

    /**
     * Summarize single letters into ranges.
     *
     * @param array $letters
     * @return string
     */
    private function summarizeLetters(array $letters): string
    {
        $letters = array_unique($letters);
        sort($letters);

        $ranges = [];
        $start = $letters[0];
        $prev = $start;

        for ($i = 1; $i < count($letters); $i++) {
            $currentOrd = ord($letters[$i]);
            $prevOrd = ord($prev);

            if ($currentOrd === $prevOrd + 1) {
                // Consecutive
                $prev = $letters[$i];
            } else {
                // Gap found
                if ($start === $prev) {
                    $ranges[] = $start;
                } else {
                    $ranges[] = $start . '-' . $prev;
                }
                $start = $letters[$i];
                $prev = $letters[$i];
            }
        }

        // Add the last range
        if ($start === $prev) {
            $ranges[] = $start;
        } else {
            $ranges[] = $start . '-' . $prev;
        }

        return implode(',', $ranges);
    }

    /**
     * Summarizes a list of numeric strings into ranges or patterns.
     *
     * @param array $numericStrings The list of numeric strings to summarize.
     * 
     * @return string A summarized representation of the numbers (without outer brackets).
     */
    private function summarizeNumericStrings(array $numericStrings): string
    {
        if (empty($numericStrings)) {
            return '';
        }

        // Determine if they are in strictly ascending or descending order
        $values = [];
        foreach ($numericStrings as $str) {
            $values[] = strpos((string) $str, '.') !== false ? floatval($str) : intval($str);
        }

        $isAscending = true;
        $isDescending = true;
        $count = count($values);
        if ($count > 1) {
            for ($i = 1; $i < $count; $i++) {
                if ($values[$i] <= $values[$i - 1]) {
                    $isAscending = false;
                }
                if ($values[$i] >= $values[$i - 1]) {
                    $isDescending = false;
                }
            }
        }

        $orderedStrings = $numericStrings;
        $orderedValues = $values;

        // If not strictly ordered, we sort them internally to find ranges
        if (!$isAscending && !$isDescending) {
            array_multisort($values, SORT_ASC, $numericStrings);
            $orderedStrings = $numericStrings;
            $orderedValues = $values;
        }

        $ranges = [];
        $startStr = $orderedStrings[0];
        $startVal = $orderedValues[0];
        $prevStr = $startStr;
        $prevVal = $startVal;
        $diff = null;

        for ($i = 1; $i < count($orderedValues); $i++) {
            $currentStr = $orderedStrings[$i];
            $currentVal = $orderedValues[$i];
            $currentDiff = $currentVal - $prevVal;

            if (is_float($currentDiff) || is_float($diff)) {
                $currentDiff = round((float) $currentDiff, 10);
            }

            if ($diff === null) {
                $diff = $currentDiff;
            } else if (is_float($diff)) {
                $diff = round((float) $diff, 10);
            }

            if ($currentDiff !== $diff) {
                if ($startStr === $prevStr) {
                    $ranges[] = $startStr;
                } elseif (abs((float) $diff) == 1) {
                    $ranges[] = $startStr . '-' . $prevStr;
                } else {
                    if (is_float($diff) || is_float($startVal)) {
                        $ranges[] = sprintf("%s-%s(step:%g)", $startStr, $prevStr, abs((float) $diff));
                    } else {
                        $ranges[] = sprintf("(%g + %gn ~ %g)", abs((float) $diff), $startVal, $prevVal);
                    }
                }
                $startStr = $currentStr;
                $startVal = $currentVal;
                $diff = null;
            }
            $prevStr = $currentStr;
            $prevVal = $currentVal;
        }

        if ($startStr === $prevStr) {
            $ranges[] = $startStr;
        } elseif (abs((float) $diff) == 1) {
            $ranges[] = $startStr . '-' . $prevStr;
        } else {
            if (is_float($diff) || is_float($startVal)) {
                $ranges[] = sprintf("%s-%s(step:%g)", $startStr, $prevStr, abs((float) $diff));
            } else {
                $ranges[] = sprintf("(%g + %gn ~ %g)", abs((float) $diff), $startVal, $prevVal);
            }
        }
        return implode(',', $ranges);
    }
}
