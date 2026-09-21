<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

#region use

use AllowDynamicProperties;
use Clover\Classes\Data\{ArrayObject, BaseObject, Multibyte, StringHandler};
use Clover\Classes\Math\AhoCorasick;
use Clover\Classes\OperationSystem;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Classes\System\Output;
use Clover\Enumeration\RegularRegex;
use Clover\Exception\Argument\ArgumentEmptyException;
use InvalidArgumentException;
use RuntimeException;
use Exception;
use function array_slice;
use function chr;
use function count;
use function in_array;
use function intval;
use function is_array;
use function is_null;
use function is_object;
use function is_string;
use function ord;
use function sprintf;
use function strlen;

#endregion

/**
 * Class StringObject
 *
 * A class that encapsulates string data and provides various string manipulation methods.
 */
#[AllowDynamicProperties]
class StringObject extends BaseObject
{
    #region Properties

    /**
     * The raw string data.
     *
     * @var string
     */
    protected $rawData = '';

    /**
     * The character encoding of the string.
     *
     * @var string
     */
    private $encoding = "";

    #endregion

    #region Function

    /**
     * Constructor for StringObject.
     *
     * @param string $data The data to be stored as a string.
     * @param string $encoding The character encoding of the string. Default is 'UTF-8'.
     *
     * @throws InvalidArgumentException If the provided data is an array.
     */
    public function __construct(string $data, string $encoding = 'UTF-8')
    {
        if (is_array($data)) {
            throw new InvalidArgumentException('Passed value cannot be an array');
        }

        $this->encoding = $encoding;

        parent::__construct($data);
    }

    public static function isMultibyteExtensionLoaded(): bool
    {
        return extension_loaded('mbstring');
    }

    /**
     * Recreates a StringObject instance from an array representation.
     *
     * @param array $an_array The array containing the properties of the StringObject.
     *
     * @return StringObject A new instance of StringObject.
     */
    public static function __set_state(array $an_array): StringObject
    {
        return new self($an_array['rawData'], $an_array['encoding']);
    }

    /**
     * Formats a time duration given in seconds into a human-readable string.
     *
     * @return StringObject A new StringObject representing the formatted time duration.
     */
    public function timeFormat(): StringObject
    {
        $time = (int) $this->cloneRawData();

        if ($time <= 0) {
            return new self("0 Seconds");
        }

        $format = "";

        $years = intdiv($time, 31536000); // 365 days
        if ($years > 0) {
            $format .= "{$years} " . ($years === 1 ? "Year" : "Years") . " ";
            $time %= 31536000;
        }

        $weeks = intdiv($time, 604800); // 7 days
        if ($weeks > 0) {
            $format .= "{$weeks} " . ($weeks === 1 ? "Week" : "Weeks") . " ";
            $time %= 604800;
        }

        $days = intdiv($time, 86400); // 24 hours
        if ($days > 0) {
            $format .= "{$days} " . ($days === 1 ? "Day" : "Days") . " ";
            $time %= 86400;
        }

        $hours = intdiv($time, 3600);
        if ($hours > 0) {
            $format .= "{$hours} " . ($hours === 1 ? "Hour" : "Hours") . " ";
            $time %= 3600;
        }

        $minutes = intdiv($time, 60);
        if ($minutes > 0) {
            $format .= "{$minutes} " . ($minutes === 1 ? "Minute" : "Minutes") . " ";
            $time %= 60;
        }

        if ($time > 0) {
            $format .= "{$time} " . ($time === 1 ? "Second" : "Seconds") . " ";
        }

        return new self(trim($format));
    }

    /**
     * Converts the string into an array of its characters.
     *
     * @return ArrayObject An ArrayObject containing each character of the string as an element.
     */
    public function toArray(): ArrayObject
    {
        $array = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);

        return new ArrayObject($array);
    }

    /**
     * Magic method to convert the StringObject to a string.
     *
     * @return string The string representation of the StringObject.
     */
    public function __toString(): string
    {
        return (string) $this->rawData;
    }

    /**
     * Truncates the string to a specified length and appends an ellipsis if necessary.
     *
     * @param int $length The maximum length of the string before truncation.
     * @param string $end The string to append at the end if truncation occurs. Default is '...'.
     *
     * @return StringObject A new StringObject with the truncated string.
     */
    public function ellipsis(int $length = 100, string $end = '...'): string|StringObject
    {
        $value = (string) $this->__toString();

        if (mb_strlen($value) <= $length) {
            return $value;
        }

        $value = rtrim(mb_substr($value, 0, $length, 'UTF-8')) . $end;
        return new self($value);
    }

    /**
     * Formats a number string based on a specified pattern.
     *
     * @param string $pattern The pattern to format the number (e.g., "n3-n2-n4").
     *
     * @return StringObject The current StringObject with the formatted number.
     */
    public function nubmerFormat(string $pattern): StringObject
    {
        $number = preg_replace('/\D/', '', $this->getRawData());
        preg_match_all('/n(\d+)/', $pattern, $matches);
        $segments = $matches[1];
        $delimiters = preg_split('/n\d+/', $pattern, -1, PREG_SPLIT_NO_EMPTY);

        $offset = 0;
        $resultParts = [];

        foreach ($segments as $index => $length) {
            $chunk = substr($number, $offset, (int) $length);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $resultParts[] = $chunk;
            $offset += $length;
        }

        $result = $resultParts[0] ?? '';
        for ($i = 1; $i < count($resultParts); $i++) {
            $delimiter = $delimiters[$i - 1] ?? '';
            $result .= $delimiter . $resultParts[$i];
        }

        $this->setRawData($result);

        return $this;
    }

    /**
     * Converts the string's digits to Roman numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Roman numerals.
     */
    public function toRomanDigits(): StringObject
    {
        $num = (int) $this->cloneRawData();

        if ($num <= 0) {
            return new self('');
        }

        $map = [
            '(M)' => 1000000,
            '(CM)' => 900000,
            '(D)' => 500000,
            '(CD)' => 400000,
            '(C)' => 100000,
            '(XC)' => 90000,
            '(L)' => 50000,
            '(XL)' => 40000,
            '(X)' => 10000,
            '(IX)' => 9000,
            '(V)' => 5000,
            '(IV)' => 4000,
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        $result = '';
        foreach ($map as $roman => $value) {
            while ($num >= $value) {
                $result .= $roman;
                $num -= $value;
            }
        }

        return new self($result);
    }

    /**
     * Converts the string's digits to United statement numeral system.
     *
     * @return StringObject A new StringObject with digits converted to United statement.
     */
    public function unitedStatesDigits(): StringObject
    {
        $num = (float) $this->cloneRawData();

        if ($num === 0.0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $units = [
            [1000000000000, 'T'],
            [1000000000, 'B'],
            [1000000, 'M'],
            [1000, 'K'],
        ];

        foreach ($units as [$value, $unit]) {
            if ($num >= $value) {
                $divided = $num / $value;
                $formatted = rtrim(rtrim(number_format($divided, 2), '0'), '.');
                $result = "{$formatted}{$unit}";
                return new self($isNegative ? "-{$result}" : $result);
            }
        }

        $result = (string) $num;
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Persian numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Persian.
     */
    public function toPersianDigits(): StringObject
    {
        $digits = (string) $this->cloneRawData();

        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return new self(strtr($digits, array_combine(range('0', '9'), $persianDigits)));
    }

    /**
     * Converts the string's digits to Thai numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Thai.
     */
    public function thaiDigits(): StringObject
    {
        $num = (int) $this->cloneRawData();

        if ($num === 0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $units = [
            [1000000000000, 'ล้านล้าน'],
            [1000000000, 'พันล้าน'],
            [1000000, 'ล้าน'],
            [100000, 'แสน'],
            [10000, 'หมื่น'],
            [1000, 'พัน'],
            [100, 'ร้อย'],
            [10, 'สิบ'],
            [1, ''],
        ];

        $parts = [];
        foreach ($units as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        $result = implode(' ', $parts);
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Chinese numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Chinese.
     */
    public function chineseDigits(): StringObject
    {
        $num = (int) $this->cloneRawData();

        if ($num === 0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $largeUnits = [
            [1000000000000, '兆'],
            [100000000, '亿'],
            [10000, '万'],
        ];

        $smallUnits = [
            [1000, '千'],
            [100, '百'],
            [10, '十'],
            [1, ''],
        ];

        $parts = [];

        foreach ($largeUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        foreach ($smallUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        $result = implode('', $parts);
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Russian numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Russian.
     */
    public function russianDigits(): StringObject
    {
        $num = (float) $this->cloneRawData();

        if ($num === 0.0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $units = [
            [1000000000000, 'трлн'],
            [1000000000, 'млрд'],
            [1000000, 'млн'],
            [1000, 'тыс.'],
        ];

        foreach ($units as [$value, $unit]) {
            if ($num >= $value) {
                $divided = $num / $value;
                $formatted = rtrim(rtrim(number_format($divided, 2), '0'), '.');
                $result = "{$formatted} {$unit}";
                return new self($isNegative ? "-{$result}" : $result);
            }
        }

        $result = (string) (int) $num;
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Australian numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Australian.
     */
    public function australianDigits(): StringObject
    {
        $num = (float) $this->cloneRawData();

        if ($num === 0.0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $units = [
            [1000000000000, 'tn'],
            [1000000000, 'bn'],
            [1000000, 'm'],
            [1000, 'k'],
        ];

        foreach ($units as [$value, $unit]) {
            if ($num >= $value) {
                $divided = $num / $value;
                $formatted = rtrim(rtrim(number_format($divided, 2), '0'), '.');
                $result = "\${$formatted}{$unit}";
                return new self($isNegative ? "-{$result}" : $result);
            }
        }

        $result = '$' . number_format((int) $num);
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Korean numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Korean.
     */
    public function koreanDigits(): StringObject
    {
        $num = (int) $this->cloneRawData();

        if ($num === 0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $largeUnits = [
            [1000000000000, '조'],
            [100000000, '억'],
            [10000, '만'],
        ];

        $smallUnits = [
            [1000, '천'],
            [100, '백'],
            [10, '십'],
            [1, ''],
        ];

        $parts = [];

        foreach ($largeUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        foreach ($smallUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        $result = implode(' ', $parts);
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Japanese numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Japanese.
     */
    public function japaneseDigits(): StringObject
    {
        $num = (int) $this->cloneRawData();

        if ($num === 0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $largeUnits = [
            [1000000000000, '兆'],
            [100000000, '億'],
            [10000, '万'],
        ];

        $smallUnits = [
            [1000, '千'],
            [100, '百'],
            [10, '十'],
            [1, ''],
        ];

        $parts = [];

        foreach ($largeUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        foreach ($smallUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        $result = implode('', $parts);
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Taiwan numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Taiwan.
     */
    public function taiwanDigits(): StringObject
    {
        $num = (int) $this->cloneRawData();

        if ($num === 0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $largeUnits = [
            [1000000000000, '兆'],
            [100000000, '億'],
            [10000, '萬'],
        ];

        $smallUnits = [
            [1000, '千'],
            [100, '百'],
            [10, '十'],
            [1, ''],
        ];

        $parts = [];

        foreach ($largeUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        foreach ($smallUnits as [$value, $unit]) {
            if ($num >= $value) {
                $parts[] = intdiv($num, $value) . $unit;
                $num %= $value;
            }
        }

        $result = implode('', $parts);
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to United kingdom numeral system.
     *
     * @return StringObject A new StringObject with digits converted to United kingdom.
     */
    public function unitedKingdomDigits(): StringObject
    {
        $num = (float) $this->cloneRawData();

        if ($num === 0.0) {
            return new self('0');
        }

        $isNegative = $num < 0;
        $num = abs($num);

        $units = [
            [1000000000000, 'tn'],
            [1000000000, 'bn'],
            [1000000, 'm'],
            [1000, 'k'],
        ];

        foreach ($units as [$value, $unit]) {
            if ($num >= $value) {
                $divided = $num / $value;
                $formatted = rtrim(rtrim(number_format($divided, 2), '0'), '.');
                $result = "£{$formatted}{$unit}";
                return new self($isNegative ? "-{$result}" : $result);
            }
        }

        $result = '£' . number_format((int) $num);
        return new self($isNegative ? "-{$result}" : $result);
    }

    /**
     * Converts the string's digits to Arabic-Indic numeral system.
     *
     * @return StringObject A new StringObject with digits converted to Arabic-Indic.
     */
    public function toArabicIndicDigits(): StringObject
    {
        $digits = (string) $this->cloneRawData();

        $arabicIndicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        return new self(strtr($digits, array_combine(range('0', '9'), $arabicIndicDigits)));
    }

    /**
     * Perform a regular expression match Searches subject for a match to the regular expression given in pattern.
     * 
     * @param string $pattern
     * @return bool
     */
    protected function matchesPattern(string $pattern): bool
    {
        return (bool) preg_match(sprintf("/%s/u", $pattern), $this->getRawData());
    }

    /**
     * Converts the string to an integer.
     *
     * @return int The integer representation of the string.
     */
    public function toInteger(): int
    {
        return intval($this->__toString());
    }

    /**
     * Checks if the string contains any lowercase letters.
     *
     * @return bool True if the string contains lowercase letters, false otherwise.
     */
    public function hasLowercase(): bool
    {
        return self::matchesPattern('.*[[:lower:]]');
    }

    /**
     * Checks if the string consists solely of lowercase letters.
     *
     * @return bool True if the string is lowercase, false otherwise.
     */
    public function isLowercase(): bool
    {
        return self::matchesPattern('^[[:lower:]]*$');
    }

    /**
     * Checks if the string contains any uppercase letters.
     *
     * @return bool True if the string contains uppercase letters, false otherwise.
     */
    public function hasUppercase(): bool
    {
        return self::matchesPattern('.*[[:upper:]]');
    }

    /**
     * Checks if the string consists solely of uppercase letters.
     *
     * @return bool True if the string is uppercase, false otherwise.
     */
    public function isUppercase(): bool
    {
        return self::matchesPattern('^[[:upper:]]*$');
    }

    /**
     * Checks if the string contains any whitespace characters.
     *
     * @return bool True if the string contains whitespace, false otherwise.
     */
    public function hasWhitespace(): bool
    {
        return self::matchesPattern('.*[[:space:]]');
    }

    /**
     * Generates n-grams from the string.
     *
     * @param int $n The length of each n-gram.
     *
     * @return array An array of n-grams.
     */
    public function ngrams(int $n): array
    {
        $value = $this->getRawData();
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $ngrams = [];
        for ($i = 0; $i <= count($chars) - $n; $i++) {
            $ngrams[] = implode('', array_slice($chars, $i, $n));
        }

        return $ngrams;
    }

    /**
     * Reverses a multibyte string.
     *
     * @param string $str The string to be reversed.
     *
     * @return string The reversed string.
     */
    private static function mb_strrev(string $str): string
    {
        $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        return implode('', array_reverse($chars));
    }

    /**
     * Converts the string into a URL-friendly "slug".
     *
     * @return StringObject A new StringObject representing the slugified version of the string.
     */
    public function slugify(): self
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $this->cloneRawData());
        $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', $slug);
        $slug = strtolower(trim($slug, '-'));

        return new self($slug);
    }

    /**
     * Checks if the string consists solely of ASCII characters.
     *
     * @return bool True if the string is ASCII, false otherwise.
     */
    public function isAscii(): bool
    {
        return $this->matchesPattern('[\x09\x0A\x0D\x20-\x7E]');
    }

    /**
     * Checks if the string consists solely of alphanumeric characters.
     *
     * @return bool True if the string is alphanumeric, false otherwise.
     */
    public function isAlphanumeric(): bool
    {
        return self::matchesPattern('^[[:alnum:]]*$');
    }

    /**
     * Checks if the string consists solely of hexadecimal characters.
     *
     * @return bool True if the string is hexadecimal, false otherwise.
     */
    public function isHexadecimal(): bool
    {
        return self::matchesPattern('^[[:xdigit:]]*$');
    }

    /**
     * Checks if the string consists solely of punctuation characters.
     *
     * @return bool True if the string is punctuation, false otherwise.
     */
    public function isPunctuation(): bool
    {
        return self::matchesPattern('^[[:punct:]]*$');
    }

    /**
     * Matches the string against key-value pairs and returns the corresponding value.
     *
     * @param mixed $default The default value to return if no match is found.
     * @param mixed ...$arguments Key-value pairs to match against the string.
     *
     * @throws Exception If the number of arguments is not even.
     *
     * @return mixed The matched value or the default value.
     */
    public function matchFromPairs(mixed $default, mixed ...$arguments): mixed
    {
        if (count($arguments) % 2 !== 0) {
            throw new Exception("Arguments should be in key-value pairs");
        }

        $data = $this->getRawData();

        for ($i = 0; $i < count($arguments); $i += 2) {
            $key = $arguments[$i];
            $value = $arguments[$i + 1];

            if ($key == $data) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Compares the string with another value for equality.
     *
     * @param mixed $value The value to compare with.
     *
     * @return bool True if the strings are equal, false otherwise.
     */
    public function equals(mixed $value): bool
    {
        return $this->getRawData() === $value;
    }

    /**
     * Validates if the string is in email format.
     *
     * @return bool True if the string is a valid email, false otherwise.
     */
    public function isEmail(): bool
    {
        return (bool) preg_match(RegularRegex::EMAIL, $this->getRawData());
    }

    /**
     * Returns the string in hexadecimal format.
     *
     * @return StringObject
     */
    public function getHexDecimal(): self
    {
        preg_match(RegularRegex::HEX, $this->getRawData(), $matches);

        if (isset($matches)) {
            $this->rawData = $matches[0];
        }

        return $this;
    }

    /**
     * Detects and returns the encoding of the string.
     *
     * @return StringObject
     */
    public function getEncoding(): self
    {
        $this->rawData = Multibyte::detectCharacterEncoding($this->rawData);

        return $this;
    }

    /**
     * Find the position of the last occurrence of a substring in a string
     *
     * @param string $needle The substring to search for.
     * @param int $offset The position in the string to start searching.
     * @param bool $ignoreCase Whether to ignore case sensitivity in the search.
     *
     * @return int|bool The position of the last occurrence of the substring, or false if not found.
     */
    public function lastIndexOf(string $needle, int $offset = 0, bool $ignoreCase = true): int|bool
    {
        $data = $this->getRawData();

        if ($ignoreCase) {
            $data = strripos($data, $needle, $offset);
        } else {
            $data = strrpos($data, $needle, $offset);
        }

        return $data;
    }

    /**
     * Finds the position of the first occurrence of a substring in the string.
     *
     * @param string $needle The substring to search for.
     * @param int $offset The position to start searching from.
     * @param bool $ignoreCase Whether to ignore case sensitivity.
     *
     * @return bool|int
     */
    public function indexOf(string $needle, int $offset = 0, bool $ignoreCase = true): bool|int
    {
        $data = $this->getRawData();

        if ($ignoreCase) {
            $data = stripos($data, $needle, $offset);
        } else {
            $data = strpos($data, $needle, $offset);
        }

        return $data;
    }

    /**
     * Removes specified characters from the beginning of the string.
     *
     * @param string|null $characters The set of characters to remove.
     *
     * @return StringObject
     */
    public function trimStart(string|null $characters = " \n\r\t\v\x00"): self
    {
        $this->rawData = ltrim($this->getRawData(), $characters);

        return $this;
    }

    /**
     * Replace all occurrences of the search string with the replacement string
     *
     * @param array|string $search The value(s) to replace.
     * @param array|string $replace The replacement value(s).
     * @param bool $ignoreCase Whether to ignore case sensitivity in the replacement.
     *
     * @return StringObject The current object with the modified string.
     */
    public function replace(array|string $search, array|string $replace, bool $ignoreCase = true): self
    {
        $data = $this->getRawData();

        if ($ignoreCase) {
            $data = str_ireplace($search, $replace, $data);
        } else {
            $data = str_replace($search, $replace, $data);
        }

        $this->setRawData($data);

        return $this;
    }

    /**
     * Replaces a portion of the string with a specified replacement string, based on the given start position and length.
     *
     * @param int $start The starting position for the replacement.
     * @param int $length The number of characters to replace.
     * @param string $replacement The string to replace the specified portion with.
     *
     * @return StringObject The current object with the modified string.
     */
    public function replaceByLength(int $start, int $length, string $replacement): self
    {
        $data = $this->getRawData();
        $data = substr($data, 0, $start) . str_repeat($replacement, $length) . substr($data, $start + $length);
        $this->setRawData($data);

        return $this;
    }

    /**
     * Removes a portion of the string based on the given start position and length.
     *
     * @param int $start The starting position for the removal.
     * @param int $length The number of characters to remove.
     *
     * @return StringObject The current object with the modified string.
     */
    public function removeByLength(int $start, int $length): self
    {
        $data = $this->getRawData();
        $data = substr($data, 0, $start) . substr($data, $start + $length);
        $this->setRawData($data);

        return $this;
    }

    /**
     * Inserts a string at a specified position within the current string.
     *
     * @param int $position The position at which to insert the string.
     * @param string $value The string to be inserted.
     *
     * @return StringObject The current object with the modified string.
     */
    public function insertAt(int $position, string $value): self
    {
        $data = $this->getRawData();
        $data = substr($data, 0, $position) . $value . substr($data, $position);
        $this->setRawData($data);

        return $this;
    }

    /**
     * Overwrites a portion of the string with a specified value, starting from a given position.
     *
     * @param int $start The starting position for the overwrite.
     * @param string $value The string to overwrite with.
     *
     * @return StringObject The current object with the modified string.
     */
    public function overwriteAt(int $start, string $value): self
    {
        $data = $this->getRawData();
        $data = substr($data, 0, $start) . $value . substr($data, $start + strlen($value));
        $this->setRawData($data);

        return $this;
    }

    /**
     * Reverse a string
     *
     * @return StringObject The current object with the reversed string.
     */
    public function reverse(): self
    {
        $this->rawData = strrev($this->rawData);

        return $this;
    }

    /**
     * Strip whitespace (or other characters) from the end of a string
     * 
     * @param string|null $characters
     * 
     * @return StringObject
     */
    public function trimEnd(string|null $characters = " \n\r\t\v\x00"): self
    {
        $this->rawData = rtrim($this->getRawData(), $characters);

        return $this;
    }

    /**
     * Strip whitespace (or other characters) from the beginning and end of a string
     * 
     * @param string|null $characters
     * 
     * @return StringObject
     */
    public function trim(string $characters = " \n\r\t\v\0"): self
    {
        $this->rawData = trim($this->getRawData(), $characters);

        return $this;
    }

    /**
     * Replace all blanks (whitespace characters) in the string with a specified replacement.
     *
     * @param string $replacement The string to replace blanks with. Default is an empty string.
     *
     * @return StringObject The current object with the modified string.
     */
    public function replaceBlanks(string $replacement = ''): static
    {
        $this->rawData = preg_replace('/\s+/', $replacement, $this->getRawData());

        return $this;
    }

    /**
     * Replace all line breaks in the string with a specified replacement.
     *
     * @param string $replacement The string to replace line breaks with. Default is an empty string.
     *
     * @return StringObject The current object with the modified string.
     */
    public function replaceBr(string $replacement = ''): static
    {
        $this->rawData = preg_replace('/[\r]/', $replacement, $this->getRawData());

        return $this;
    }

    /**
     * Removes all newline characters from the string.
     *
     * @return StringObject The current object with the modified string.
     */
    public function removeNewLines(): static
    {
        $this->rawData = str_replace(["\n", "\r", "\t"], "", $this->getRawData());

        return $this;
    }

    /**
     * Removes all blanks (whitespace characters) from the string.
     *
     * @return StringObject The current object with the modified string.
     */
    public function removeBlanks(): StringObject
    {
        return $this->replaceBlanks();
    }

    /**
     * Splits the string into an array using a specified delimiter.
     *
     * @param string $separator The delimiter to split the string by.
     *
     * @throws ArgumentEmptyException
     *
     * @return ArrayObject
     */
    public function split(string $separator): ArrayObject
    {
        if (empty($separator)) {
            ReflectionHandler::throwEmptyParameterError(self::class, __FUNCTION__, get_defined_vars());
        }

        $array = explode($separator, $this->rawData);

        return new ArrayObject($array);
    }

    /**
     * Splits the string into an array using a regular expression.
     *
     * @param string $regex The regular expression to split the string by. Default is "/\r\n|\n|\r/".
     * @param int $limit The maximum number of splits. Default is -1 (no limit).
     * @param int $flags Flags to modify the behavior of preg_split. Default is 0.
     *
     * @return ArrayObject
     */
    public function pregSplit(string $regex = "/\r\n|\n|\r/", int $limit = -1, int $flags = 0): ArrayObject
    {
        $data = $this->getRawData();

        $array = preg_split($regex, $data, $limit, $flags);

        return new ArrayObject($array);
    }

    /**
     * Japanese character checks
     * @return bool|int
     */
    public function isHiragana(): bool|int
    {
        return $this->match(RegularRegex::HIRAGANA);
    }

    /**
     * Japanese character checks
     * @return bool|int
     */
    public function isKatakana(): bool|int
    {
        return $this->match(RegularRegex::KATAKANA);
    }

    /**
     * Japanese character checks
     * @return bool|int
     */
    public function isJapanese(): bool|int
    {
        return $this->match(RegularRegex::JAPANESE);
    }

    /**
     * Japanese character checks
     * @return bool|int
     */
    public function isKanji(): bool|int
    {
        return $this->match(RegularRegex::KANJI);
    }

    /**
     * Korean character checks
     * @return bool|int
     */
    public function isKorean(): bool|int
    {
        return $this->match(RegularRegex::KOREAN);
    }

    /**
     * Reverses the order of words in the string.
     *
     * @return StringObject A new StringObject with the words in reverse order.
     */
    public function reverseWords(): StringObject
    {
        $value = $this->getRawData();
        $words = preg_split('/\s+/', trim($value));
        $words = array_reverse($words);
        return new self(implode(" ", $words));
    }

    /**
     * Splits the string into lines.
     *
     * @param bool $keepEnds Whether to keep the line endings in the resulting array. Default is false.
     *
     * @return ArrayObject An ArrayObject containing the lines of the string.
     */
    public function splitLines(bool $keepEnds = false): ArrayObject
    {
        if ($keepEnds) {
            return $this->pregSplit('/(\r\n|\r|\n)/', -1, PREG_SPLIT_DELIM_CAPTURE);
        }

        return $this->pregSplit("/\r\n|\n|\r/");
    }

    /**
     * Determines if the string starts with the given substring.
     *
     * @param string $needle The substring to check for.
     *
     * @return int|false Returns 1 if the string starts with the substring, 0 otherwise.
     */
    public function startsWith(string $needle): bool
    {
        if ('' === $needle) {
            return true;
        }

        $rawData = $this->getRawData();
        if (function_exists('str_starts_with')) {
            return str_starts_with($rawData, $needle);
        }

        if (self::isMultibyteExtensionLoaded()) {
            return mb_strpos($rawData, $needle, 0, $this->encoding) === 0;
        }

        return strpos($rawData, $needle) === 0;
    }

    /**
     * Determines if the string ends with the given substring.
     *
     * @param string $needle The substring to check for.
     *
     * @return int|false Returns 1 if the string ends with the substring, 0 otherwise.
     */
    public function endsWith(string $needle): bool
    {
        if ('' === $needle) {
            return true;
        }

        $rawData = $this->getRawData();
        if (function_exists('str_ends_with')) {
            return str_ends_with($rawData, $needle);
        }

        if (self::isMultibyteExtensionLoaded()) {
            return mb_substr($rawData, -mb_strlen($needle, $this->encoding), $this->encoding) === $needle;
        }

        return substr($rawData, -strlen($needle)) === $needle;
    }

    /** 
     * Ensures that the string ends with the specified substring. If it doesn't, the substring is appended to the string.
     *
     * @param string $needle The substring to ensure at the end of the string.
     *
     * @return StringObject The current object with the modified string.
     */
    public function ensureEndsWith(string $needle): static
    {
        $subject = $this->getRawData();

        if (!$this->endsWith($needle)) {
            $subject = sprintf("%s%s", $subject, $needle);
        }

        $this->setRawData($subject);

        return $this;
    }

    /** 
     * Ensures that the string starts with the specified substring. If it doesn't, the substring is prepended to the string.
     *
     * @param string $needle The substring to ensure at the start of the string.
     *
     * @return StringObject The current object with the modified string.
     */
    public function ensureStartsWith(string $needle): static
    {
        $subject = $this->getRawData();

        if (!$this->startsWith($needle)) {
            $subject = sprintf("%s%s", $needle, $subject);
        }

        $this->setRawData($subject);

        return $this;
    }

    /** 
     * Replaces the last occurrence of a search string with a replacement string.
     *
     * @param string $search The string to search for.
     * @param string $replace The string to replace the last occurrence with.
     *
     * @return StringObject The current object with the modified string.
     */
    public function replaceLast(string $search, string $replace): static
    {
        $subject = $this->getRawData();
        $position = strrpos($subject, $search);

        if ($position !== false) {
            $subject = substr_replace($subject, $replace, $position, strlen($search));
        }

        $this->setRawData($subject);

        return $this;
    }

    /**
     * Finds the shortest word in the string.
     *
     * @return string|null The shortest word, or null if no words are found.
     */
    public function shortestWord(): ?string
    {
        $value = $this->getRawData();
        $words = preg_split('/\W+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return null;
        }
        usort($words, fn($a, $b) => mb_strlen($a) <=> mb_strlen($b));
        return $words[0];
    }

    /**
     * Finds the longest word in the string.
     *
     * @return string|null The longest word, or null if no words are found.
     */
    public function longestWord(): ?string
    {
        $value = $this->getRawData();
        $words = preg_split('/\W+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return null;
        }
        usort($words, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        return $words[0];
    }

    /**
     * Splits the string into sentences.
     *
     * @return array An array of sentences.
     */
    public function splitSentences(): array
    {
        $value = $this->getRawData();
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return $sentences;
    }

    /**
     * Calculates the average word length in the string.
     *
     * @return float The average length of the words.
     */
    public function averageWordLength(): float
    {
        $value = $this->getRawData();
        $words = preg_split('/\W+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($words)) {
            return 0.0;
        }
        $total = array_sum(array_map('mb_strlen', $words));
        return $total / count($words);
    }

    /**
     * Counts the number of unique words in the string.
     *
     * @return int The count of unique words.
     */
    public function uniqueWordCount(): int
    {
        $value = mb_strtolower($this->getRawData());
        $words = preg_split('/\W+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        return count(array_unique($words));
    }

    /**
     * Calculates the frequency of each character in the string.
     *
     * @return array An associative array with characters as keys and their frequencies as values.
     */
    public function charFrequency(): array
    {
        $value = mb_strtolower($this->getRawData());
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $freq = [];
        foreach ($chars as $c) {
            $freq[$c] = ($freq[$c] ?? 0) + 1;
        }
        arsort($freq);
        return $freq;
    }

    /**
     * Finds the most common word in the string.
     *
     * @return string|null The most common word, or null if no words are found.
     */
    public function mostCommonWord(): ?string
    {
        $freq = $this->wordFrequency();
        return empty($freq) ? null : array_key_first($freq);
    }

    /**
     * Calculates the frequency of each word in the string.
     *
     * @return array An associative array with words as keys and their frequencies as values.
     */
    public function wordFrequency(): array
    {
        $value = mb_strtolower($this->getRawData());
        $words = preg_split('/\W+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $freq = [];
        foreach ($words as $w) {
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }
        arsort($freq);
        return $freq;
    }

    /**
     * Counts the number of words in the string.
     *
     * @param int $format The format of the return value. 0 for count, 1 for array of words, 2 for associative array.
     * @param string|null $characters Additional characters to consider as part of a word.
     *
     * @return ArrayObject|int The word count or an ArrayObject of words based on the format.
     */
    public function wordCount(int $format = 0, string|null $characters = null): ArrayObject|int
    {
        if (function_exists('str_word_count')) {
            $count = str_word_count($this->getRawData(), $format, $characters);

            if (is_array($count)) {
                return new ArrayObject($count);
            }

            return $count;
        }

        $value = $this->getRawData();
        $words = preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * Return part of a string
     *
     * @param int $start The starting position.
     * @param int|null $length The length of the substring. Defaults to the remainder of the string.
     *
     * @return StringObject The current object with the modified string.
     */
    public function substring(int $start, int|null $length = null): self
    {
        if (self::isMultibyteExtensionLoaded()) {
            $data = mb_substr($this->getRawData(), $start, $length, $this->encoding);
        } else {
            $data = StringHandler::substring($this->getRawData(), $start, $length);
        }

        return new self($data);
    }

    /**
     * Count the number of substring occurrences
     * 
     * @param string $needle
     * @param int $offset
     * 
     * @return int
     */
    public function substringCount(string $needle, int $offset = 0, ?int $length = null): int
    {
        if (self::isMultibyteExtensionLoaded()) {
            $data = mb_substr_count($this->getRawData(), $needle, $this->encoding);
        } else {
            $data = substr_count($this->getRawData(), $needle, $offset, $length);
        }

        return $data;
    }

    /**
     * Binary safe comparison of two strings from an offset, up to length characters
     * 
     * @param string $needle
     * @param int $offset
     * 
     * @return int
     */
    public function substringCompare(string $needle, int $offset): int
    {
        return substr_compare($this->getRawData(), $needle, $offset);
    }

    /**
     * Determine whether a variable is considered to be empty
     * 
     * @return bool
     */
    public function isEmpty(): bool
    {
        $boolean = StringHandler::isEmpty($this->rawData);

        return $boolean;
    }

    /**
     * Converts the string to a Base64 URL format.
     * 
     * @return StringObject
     */
    public function toBase64URL(): static
    {
        $this->rawData = sprintf('data:image:gif;base64,%s', $this->encodeToBase64()->getRawData());

        return $this;
    }

    /**
     * Encodes the string to Base64 format.
     * 
     * @return StringObject
     */
    public function encodeToBase64(): static
    {
        $this->rawData = base64_encode($this->rawData);

        return $this;
    }

    /**
     * Finds whether a variable is null
     * 
     * @return bool
     */
    public function isNull(): bool
    {
        return StringHandler::isNull($this->rawData);
    }

    /**
     * Determine whether a variable is null or an empty string
     * 
     * @return bool
     */
    public function isNullOrEmpty(): bool
    {
        return StringHandler::isNull($this->rawData) || $this->trim()->equals('');
    }


    /**
     * Determine if a string contains a given substring
     * 
     * @param string $needle
     * 
     * @return StringObject
     */
    public function contains(string $needle): bool
    {
        return StringHandler::contains($this->getRawData(), $needle);
    }

    /**
     * Check if the string is a palindrome
     * 
     * @return bool
     */
    public function isPalindrome(): bool
    {
        $value = mb_strtolower(preg_replace('/\W/u', '', $this->getRawData()));
        return $value === $this->mb_strrev($value);
    }

    /**
     * Rotates the characters in the string by a specified number of positions.
     *
     * @param int $n The number of positions to rotate the string. Positive values rotate to the left, negative values rotate to the right.
     * @param bool $multiline Whether to apply the rotation to each line separately. Default is false.
     *
     * @return StringObject|ArrayObject A new StringObject with the rotated string, or an ArrayObject of rotated lines if multiline is true.
     */
    public function rotate(int $n = 0, bool $multiline = false): StringObject
    {
        $value = $this->getRawData();

        function alignment($value, $n): string|StringObject
        {
            $len = mb_strlen($value);
            if ($len === 0) {
                return new StringObject($value);
            }

            $n = $n % $len;
            if ($n < 0) {
                $n += $len;
            }

            return mb_substr($value, $n) . mb_substr($value, 0, $n);
        }

        if (!$multiline) {
            return new self(alignment($value, $n));
        }

        $lines = $this->splitLines();
        foreach ($lines as $key => $line) {
            if (is_string($line)) {
                $lines[$key] = alignment($line, $n);
            }
        }

        return $lines->join(PHP_EOL);
    }

    /**
     * Checks if the string's value is present in the given array.
     *
     * @param array $haystack The array to search in.
     *
     * @return bool True if the string's value is found in the array, false otherwise.
     */
    public function inArray(array $haystack = []): bool
    {
        return in_array($this->getRawData(), $haystack);
    }

    /**
     * Collapses consecutive whitespace characters into a single space and trims leading/trailing whitespace.
     *
     * @return StringObject A new StringObject with collapsed whitespace.
     */
    public function collapseWhitespace(): StringObject
    {
        $value = $this->getRawData();
        $collapsed = preg_replace('/\s+/u', ' ', trim($value));
        return new self($collapsed ?? $value);
    }

    /**
     * Limits the string to a specified number of sentences, appending an ellipsis if necessary.
     *
     * @param int $count The maximum number of sentences to retain.
     * @param string $ellipsis The string to append if truncation occurs. Default is '...'.
     *
     * @return StringObject A new StringObject with the limited sentences.
     */
    public function limitBySentences(int $count, string $ellipsis = '...'): StringObject
    {
        $value = $this->getRawData();
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if (!$sentences || count($sentences) <= $count) {
            return new self($value);
        }

        $cut = implode(' ', array_slice($sentences, 0, $count));
        return new self($cut . $ellipsis);
    }

    /**
     * Calculates the Hamming distance between the string and another string.
     *
     * @param string $other The other string to compare with.
     *
     * @return int|null The Hamming distance, or null if the strings are of different lengths.
     */
    public function hammingDistance(string $other): ?int
    {
        $a = $this->getRawData();
        $b = $other;
        if (mb_strlen($a) !== mb_strlen($b)) {
            return null;
        }

        $ac = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY);
        $bc = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY);
        $dist = 0;
        for ($i = 0; $i < count($ac); $i++) {
            if ($ac[$i] !== $bc[$i]) {
                $dist++;
            }
        }

        return $dist;
    }

    /**
     * Generates the Soundex code for the string.
     *
     * @return StringObject A new StringObject containing the Soundex code.
     */
    public function soundexCode(): StringObject
    {
        return new self(soundex($this->getRawData()));
    }

    /**
     * Normalizes the string to a specified Unicode normalization form.
     *
     * @param string $form The normalization form ('NFC', 'NFD', 'NFKC', 'NFKD'). Default is 'NFC'.
     *
     * @return StringObject A new StringObject with the normalized string.
     */
    public function normalizeUnicode(string $form = 'NFC'): StringObject
    {
        $v = $this->getRawData();

        if (class_exists('\Normalizer')) {
            $map = [
                'NFC' => \Normalizer::FORM_C,
                'NFD' => \Normalizer::FORM_D,
                'NFKC' => \Normalizer::FORM_KC,
                'NFKD' => \Normalizer::FORM_KD,
            ];
            $target = $map[$form] ?? \Normalizer::FORM_C;
            $norm = \Normalizer::normalize($v, $target);
            return new self($norm !== false ? $norm : $v);
        }

        return new self($v);
    }

    /**
     * Finds the longest common substring between the string and another string.
     *
     * @param string $other The other string to compare with.
     *
     * @return string The longest common substring.
     */
    public function longestCommonSubstring(string $other): string
    {
        $a = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);
        $b = preg_split('//u', $other, -1, PREG_SPLIT_NO_EMPTY);
        $m = count($a);
        $n = count($b);
        if ($m === 0 || $n === 0) {
            return '';
        }

        $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        $maxLen = 0;
        $endPos = 0;
        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $dp[$i][$j] = $dp[$i - 1][$j - 1] + 1;
                    if ($dp[$i][$j] > $maxLen) {
                        $maxLen = $dp[$i][$j];
                        $endPos = $i;
                    }
                } else {
                    $dp[$i][$j] = 0;
                }
            }
        }

        return $maxLen > 0 ? implode('', array_slice($a, $endPos - $maxLen, $maxLen)) : '';
    }

    /**
     * Finds the common prefix between the string and another string.
     *
     * @param string $other The other string to compare with.
     *
     * @return string The common prefix.
     */
    public function commonPrefixWith(string $other): string
    {
        $a = $this->getRawData();
        $len = min(mb_strlen($a), mb_strlen($other));
        $i = 0;
        while ($i < $len && mb_substr($a, $i, 1) === mb_substr($other, $i, 1)) {
            $i++;
        }

        return mb_substr($a, 0, $i);
    }

    /**
     * Finds the common suffix between the string and another string.
     *
     * @param string $other The other string to compare with.
     *
     * @return string The common suffix.
     */
    public function commonSuffixWith(string $other): string
    {
        $a = $this->getRawData();
        $lenA = mb_strlen($a);
        $lenB = mb_strlen($other);
        $i = 0;
        while (
            $i < $lenA && $i < $lenB &&
            mb_substr($a, $lenA - $i - 1, 1) === mb_substr($other, $lenB - $i - 1, 1)
        ) {
            $i++;
        }

        return $i === 0 ? '' : mb_substr($a, $lenA - $i, $i);
    }

    /**
     * Calculates the Dice coefficient between the string and another string.
     *
     * @param string $other The other string to compare with.
     * @param int $n The n-gram size. Default is 2.
     *
     * @return float The Dice coefficient.
     */
    public function diceCoefficient(string $other, int $n = 2): float
    {
        $a = $this->getRawData();
        $ngrams = function (string $s) use ($n): array {
            $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
            $gs = [];
            for ($i = 0; $i <= count($chars) - $n; $i++) {
                $gs[] = implode('', array_slice($chars, $i, $n));
            }

            return $gs;
        };

        $A = $ngrams($a);
        $B = $ngrams($other);
        if (empty($A) && empty($B)) {
            return 1.0;
        }

        if (empty($A) || empty($B)) {
            return 0.0;
        }

        $setA = array_count_values($A);
        $setB = array_count_values($B);
        $intersection = 0;
        foreach ($setA as $g => $ca) {
            if (isset($setB[$g])) {
                $intersection += min($ca, $setB[$g]);
            }
        }

        return (2.0 * $intersection) / (array_sum($setA) + array_sum($setB));
    }

    /**
     * Removes the specified prefix from the string if it exists.
     *
     * @param string $prefix The prefix to remove.
     *
     * @return StringObject A new StringObject with the prefix removed if it was present.
     */
    public function removePrefix(string $prefix): StringObject
    {
        $value = $this->getRawData();

        if ($prefix !== '' && strncmp($value, $prefix, strlen($prefix)) === 0) {
            return new self(substr($value, strlen($prefix)));
        }

        return new self($value);
    }

    /**
     * Strips ANSI escape codes from the string.
     *
     * @return StringObject A new StringObject with ANSI codes removed.
     */
    public function stripAnsi(): StringObject
    {
        $value = $this->getRawData();
        $clean = preg_replace('/\x1B\[[0-?]*[ -/]*[@-~]/', '', $value);
        return new self($clean ?? $value);
    }

    /**
     * Extracts all date strings from the string in various formats.
     *
     * @return array An array of extracted date strings.
     */
    public function extractDates(): array
    {
        $value = $this->getRawData();
        $patterns = [
            '/\b\d{4}-\d{2}-\d{2}\b/u',      // 2025-09-25
            '/\b\d{2}\/\d{2}\/\d{4}\b/u',    // 25/09/2025
            '/\b\d{2}-\d{2}-\d{4}\b/u',      // 25-09-2025
        ];

        $out = [];
        foreach ($patterns as $p) {
            preg_match_all($p, $value, $m);

            if (!empty($m[0])) {
                $out = array_merge($out, $m[0]);
            }
        }

        return array_values(array_unique($out));
    }

    /**
     * Removes the specified suffix from the string if it exists.
     *
     * @param string $suffix The suffix to remove.
     *
     * @return StringObject A new StringObject with the suffix removed if it was present.
     */
    public function removeSuffix(string $suffix): StringObject
    {
        $value = $this->getRawData();
        if ($suffix !== '' && substr($value, -strlen($suffix)) === $suffix) {
            return new self(substr($value, 0, -strlen($suffix)));
        }

        return new self($value);
    }

    /**
     * Ensures that the string starts with the specified prefix.
     *
     * @param string $prefix The prefix to ensure.
     *
     * @return StringObject A new StringObject that starts with the specified prefix.
     */
    public function ensurePrefix(string $prefix): StringObject
    {
        $value = $this->getRawData();
        return new self(strncmp($value, $prefix, strlen($prefix)) === 0 ? $value : $prefix . $value);
    }

    /**
     * Ensures that the string ends with the specified suffix.
     *
     * @param string $suffix The suffix to ensure.
     *
     * @return StringObject A new StringObject that ends with the specified suffix.
     */
    public function ensureSuffix(string $suffix): StringObject
    {
        $value = $this->getRawData();
        return new self(substr($value, -strlen($suffix)) === $suffix ? $value : $value . $suffix);
    }

    /**
     * Replaces the nth occurrence of a pattern in the string with a specified replacement.
     *
     * @param string $pattern The regex pattern to search for.
     * @param string $replacement The string to replace the nth occurrence with.
     * @param int $n The occurrence number to replace (1-based index).
     *
     * @return StringObject A new StringObject with the nth occurrence replaced.
     */
    public function replaceNth(string $pattern, string $replacement, int $n = 1): StringObject
    {
        $value = $this->getRawData();
        $count = 0;
        $replaced = preg_replace_callback($pattern, function ($m) use ($replacement, &$count, $n) {
            $count++;
            return ($count === $n) ? $replacement : $m[0];
        }, $value);

        return new self($replaced ?? $value);
    }

    /**
     * Finds all matches of a pattern in the string.
     *
     * @param string $pattern The regex pattern to search for.
     *
     * @return array An array of matched substrings.
     */
    public function findAll(string $pattern): array
    {
        $v = $this->getRawData();
        preg_match_all($pattern, $v, $matches, PREG_SET_ORDER);
        $out = [];
        foreach ($matches as $m) {
            $out[] = count($m) > 1 ? $m[1] : $m[0];
        }

        return $out;
    }

    /**
     * Replaces all occurrences of a pattern in the string with a specified replacement.
     *
     * @param string $pattern The regex pattern to search for.
     * @param string $replacement The string to replace matches with.
     *
     * @return StringObject A new StringObject with all occurrences replaced.
     */
    public function replaceAll(string $pattern, string $replacement): StringObject
    {
        $v = $this->getRawData();
        $replaced = preg_replace($pattern, $replacement, $v);
        return new self($replaced ?? $v);
    }

    /**
     * Splits the string into three parts based on the last occurrence of a separator.
     *
     * @param string $sep The separator to split the string by.
     *
     * @return array An array containing the part before the separator, the separator itself, and the part after the separator.
     */
    public function rpartition(string $sep): array
    {
        $value = $this->getRawData();
        $pos = strrpos($value, $sep);
        if ($pos === false) {
            return [$value, '', ''];
        }

        return [
            substr($value, 0, $pos),
            $sep,
            substr($value, $pos + strlen($sep)),
        ];
    }

    /**
     * Limits the string to a specified number of words, appending an ellipsis if necessary.
     *
     * @param int $count The maximum number of words to retain.
     * @param string $ellipsis The string to append if truncation occurs. Default is '...'.
     *
     * @return StringObject A new StringObject with the limited words.
     */
    public function limitByWords(int $count = 1, string $ellipsis = '...'): StringObject
    {
        $value = $this->getRawData();
        $words = preg_split('/\W+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if (!$words || count($words) <= $count) {
            return new self($value);
        }

        $cut = implode(' ', array_slice($words, 0, $count));
        return new self($cut . $ellipsis);
    }

    /**
     * Extracts a range of lines from the string.
     *
     * @param int $start The starting line index (0-based).
     * @param int $end The ending line index (exclusive).
     *
     * @return StringObject A new StringObject containing the specified range of lines.
     */
    public function lineRange(int $start, int $end): StringObject
    {
        $lines = $this->splitLines()->toPHPObject();
        $slice = array_slice($lines, $start, max(0, $end - $start));
        return new self(implode("\n", $slice));
    }

    /**
     * Removes common leading whitespace from each line in the string.
     *
     * @return StringObject A new StringObject with dedented lines.
     */
    public function dedent(): StringObject
    {
        $value = $this->getRawData();
        $lines = preg_split('/\R/u', $value);
        if (!$lines) {
            return new self($value);
        }

        $min = null;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (preg_match('/^( +|\t+)/', $line, $m)) {
                $len = strlen($m[0]);
                $min = is_null($min) ? $len : min($min, $len);
            } else {
                $min = 0;
                break;
            }
        }

        if (is_null($min) || $min === 0) {
            return new self($value);
        }

        $dedented = preg_replace('/^(?: {' . $min . '}|\t{1,' . $min . '})/m', '', $value);
        return new self($dedented ?? $value);
    }

    /**
     * Indents each line in the string by a specified number of characters.
     *
     * @param int $count The number of characters to indent each line. Default is 4.
     * @param string $char The character to use for indentation. Default is a space.
     *
     * @return StringObject A new StringObject with indented lines.
     */
    public function indent(int $count = 4, string $char = ' '): StringObject
    {
        $value = $this->getRawData();
        $prefix = str_repeat($char, $count);
        $indented = preg_replace('/^/m', $prefix, $value);
        return new self($indented ?? $value);
    }

    /**
     * Converts the string to title case.
     *
     * @return StringObject A new StringObject with the string in title case.
     */
    public function toTitleCase(): StringObject
    {
        $value = $this->getRawData();
        return new self(mb_convert_case($value, MB_CASE_TITLE, 'UTF-8'));
    }

    /**
     * Normalize \r\n and \r to \n.
     *
     * @return StringObject
     */
    public function normalizeLineEndings(): StringObject
    {
        $value = $this->getRawData();
        return new self(str_replace(["\r\n", "\r"], "\n", $value));
    }

    /**
     * Strips accents from the string.
     *
     * @return StringObject A new StringObject with accents removed.
     */
    public function stripAccents(): StringObject
    {
        $value = $this->getRawData();
        if (class_exists('\Transliterator')) {
            $tr = \Transliterator::create('NFD; [:Nonspacing Mark:] Remove; NFC');

            if ($tr) {
                return new self($tr->transliterate($value));
            }
        }

        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return new self($normalized ?: $value);
    }

    /**
     * Converts the string to kebab-case.
     *
     * @return StringObject A new StringObject with the string in kebab-case.
     */
    public function toKebabCase(): StringObject
    {
        $value = $this->getRawData();
        $kebab = strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '-', $value));
        $kebab = trim($kebab, '-');
        return new self($kebab);
    }

    /**
     * Converts the string to snake_case.
     *
     * @return StringObject A new StringObject with the string in snake_case.
     */
    public function toSnakeCase(): StringObject
    {
        $value = $this->getRawData();
        $snake = strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '_', $value));
        $snake = trim($snake, '_');
        return new self($snake);
    }

    /**
     * Converts the string to camelCase.
     *
     * @return StringObject A new StringObject with the string in camelCase.
     */
    public function toCamelCase(): StringObject
    {
        $value = $this->getRawData();
        $parts = preg_split('/[^a-zA-Z0-9]+/', strtolower($value));
        $camel = array_shift($parts);
        $camel .= implode('', array_map('ucfirst', $parts));
        return new self($camel);
    }


    /**
     * Camelize the string
     * 
     * @return StringObject
     */
    public function camelize(): self
    {
        $this->rawData = StringHandler::camelize($this->rawData);

        return $this;
    }

    /**
     * Set raw data
     * 
     * @param string $text
     * 
     * @return StringObject
     */
    public function set(string $text): self
    {
        $this->rawData = $text;

        return $this;
    }

    /**
     * Clear raw data
     * 
     * @return StringObject
     */
    public function clear(): self
    {
        $this->rawData = '';

        return $this;
    }

    /**
     * Print raw data
     * 
     * @return void
     */
    public function print(): void
    {
        Output::print($this->rawData);
    }

    /**
     * Replaces all both side strings
     * 
     * @param string $string The string to replace.
     * 
     * @return StringObject
     */
    public function replaceBoth(string $string): self
    {
        $length = strlen($string);

        $this->rawData = preg_replace("/^\w{{$length}}(.*)\w{{$length}}/i", "$string\$1$string", $this->rawData);

        return $this;
    }

    /**
     * Replaces the center string
     * 
     * @param string $string The string to replace.
     * 
     * @return StringObject
     */
    public function replaceCenter(string $string): self
    {
        $data = $this->getRawData();

        $length = ceil(strlen($data) / 2) - ceil(strlen($string) / 2);
        $tail = strlen($data) - (($length * 2) + strlen($string));
        $append = $tail > 0 ? str_repeat($string[0], (int) $tail) : "";
        $string .= $append;

        $data = preg_replace("/^(\w{{$length}}).*(\w{{$length}})/i", "\$1$string\$2", $data);

        $this->setRawData($data);

        return $this;
    }

    /**
     * Appends strings to both sides
     * 
     * @param string $string The string to append both sides.
     * 
     * @return StringObject
     */
    public function appendBoth(string $string): self
    {
        $this->setRawData($string . $this->getRawData() . $string);

        return $this;
    }

    /**
     * Prepends a string
     * 
     * @param string $string The string to prepend.
     * 
     * @return StringObject
     */
    public function prepend(string $string): static
    {
        $data = $this->getRawData();

        $this->setRawData($string . $data);

        return $this;
    }

    /**
     * Appends a string
     * 
     * @param string $string The string to append.
     * 
     * @return StringObject
     */
    public function append(string $string): self
    {
        $data = $this->getRawData();

        $this->setRawData($data . $string);

        return $this;
    }

    /**
     * Appends strings to every word
     * 
     * @param string $string The string to append.
     * 
     * @return StringObject
     */
    public function appendWord(string $string): self
    {
        $data = preg_replace("/(?!\s)(?!\s\b.{0,1}\b)/i", $string, $this->getRawData());

        $this->setRawData($data);

        return $this;
    }

    /**
     * Appends strings inside every word
     * 
     * @param string $string The string to append.
     * 
     * @return StringObject
     */
    public function appendInner(string $string): self
    {
        $data = preg_replace("/(?:\s)(\b.{0,1}\b)(?!\s)/i", "{$string}\$1", $this->getRawData());

        $this->setRawData($data);

        return $this;
    }

    /**
     * Make a string's first character uppercase
     * 
     * @return StringObject
     */
    public function capitalizeFirstLetter(): self
    {
        $data = ucfirst($this->getRawData());

        return new self($data);
    }

    /**
     * Tokenize string
     * 
     * @param bool $toArray
     * 
     * @return self|bool|array
     */
    public function tokenize(bool $toArray = false): self|bool|array
    {
        if (function_exists('strtok') && !$toArray) {
            $data = strtok($this->getRawData());

            $this->setRawData($data);

            if (!$this->rawData) {
                return false;
            }

            return $this;
        }

        $value = mb_strtolower($this->getRawData());
        return preg_split('/\W+/u', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Perform a regular expression match
     * 
     * @param string $pattern
     * @param &$matches
     * 
     * @return int|false
     */
    public function match(string $pattern, &$matches = null): int|false
    {
        $data = preg_match($pattern, $this->getRawData(), $matches);

        return $data;
    }

    /**
     * Perform a global regular expression match
     * 
     * @param string $string
     * @param &$matches
     * 
     * @return int|false
     */
    public function matchAll(string $string, &$matches = null): int|false
    {
        $data = preg_match_all($string, $this->getRawData(), $matches, PREG_SET_ORDER);

        return $data;
    }

    /**
     * Replaces all even position strings
     * 
     * @param string $replace The string to replace.
     * 
     * @return StringObject
     */
    public function replaceEven(string $replace): self
    {
        $this->rawData = preg_replace_callback('/(.)(.)/u', function ($matches) use ($replace) {
            return $matches[1] . $replace;
        }, $this->rawData);

        return $this;
    }

    /**
     * Replaces all odd position strings
     * 
     * @param string $replace The string to replace.
     * 
     * @return StringObject
     */
    public function replaceOdd(string $replace): self
    {
        $this->rawData = preg_replace('/.(.)?/', "{$replace}\$1", $this->rawData);

        if ($error = preg_last_error()) {
            if (function_exists('preg_last_error_msg')) {
                throw new RuntimeException(preg_last_error_msg());
            } else {
                throw new RuntimeException((string) $error);
            }
        }

        return $this;
    }

    /**
     * Cut the string
     * 
     * @param int $length
     * 
     * @return StringObject
     */
    public function cut(int $length = 1): self
    {
        $this->rawData = substr($this->getRawData(), 0, $length);

        return $this;
    }

    /**
     * Get unique tokens
     * 
     * @return array
     */
    public function uniqueTokens(): array
    {
        return array_values(array_unique($this->tokenize(true)));
    }

    /**
     * Wraps a string to a given number of characters
     * 
     * @param int $width
     * @param string $break = "\n"
     * 
     * @return StringObject
     */
    public function wrap(int $width, string $break = "\n"): StringObject
    {
        $value = $this->getRawData();

        return new self(wordwrap($value, $width, $break, true));
    }

    /**
     * Truncates a string to a specified length
     * 
     * @param int $length
     * @param string $ellipsis = "..."
     * 
     * @return StringObject
     */
    public function truncate(int $length, string $ellipsis = "..."): StringObject
    {
        $value = $this->getRawData();
        if (mb_strlen($value) <= $length) {
            return new self($value);
        }
        return new self(mb_substr($value, 0, $length - mb_strlen($ellipsis)) . $ellipsis);
    }

    /**
     * Get truncated string with specified width
     * 
     * @param int $limit
     * @param string $trimMarker = ''
     * 
     * @return StringObject
     */
    public function limit(int $limit, string $trimMarker = ''): self
    {
        $this->rawData = mb_strimwidth($this->getRawData(), 0, $limit, $trimMarker);

        return $this;
    }

    /**
     * Locale based string comparison
     * 
     * @param string $string
     * 
     * @return int
     */
    public function localeBasedComparison(string $string): int
    {
        return strcoll($this->getRawData(), $string);
    }

    /**
     * Make a string's first character lowercase
     * 
     * @return StringObject
     */
    public function lowercaseFirstLetter(): self
    {
        $this->rawData = lcfirst($this->rawData);

        return $this;
    }

    /**
     * Make a string uppercase
     * 
     * @return StringObject
     */
    public function toUpperCase(): self
    {
        $this->rawData = StringHandler::toUpperCase($this->rawData);

        return $this;
    }

    /**
     * Remove a byte-order mark
     * 
     * @return StringObject
     */
    public function removeByteOrderMark(): self
    {
        $this->rawData = StringHandler::removeByteOrderMark($this->rawData);

        return $this;
    }

    /**
     * Remove a substring
     * 
     * @param string $string
     * @param bool $ignoreCase
     * 
     * @return StringObject
     */
    public function remove(string $string, bool $ignoreCase = true): static
    {
        return $this->replace($string, '', $ignoreCase);
    }

    /**
     * Make a string lowercase
     * 
     * @return StringObject
     */
    public function toLowerCase(): self
    {
        $this->rawData = StringHandler::toLowerCase($this->rawData);

        return $this;
    }

    /**
     * Convert a string to under_score format
     * 
     * @return StringObject
     */
    public function toUnderScore(): self
    {
        $this->rawData = StringHandler::toUnderScore($this->rawData);

        return $this;
    }

    /**
     * Calculate the similarity between two strings
     * 
     * @param string $compare
     * 
     * @return int
     */
    public function similar(string $compare): int
    {
        return similar_text($this->getRawData(), $compare);
    }

    /**
     * Quote meta characters
     * 
     * @return StringObject
     */
    public function quotemeta(): self
    {
        $this->rawData = quotemeta($this->rawData);

        return $this;
    }

    /**
     * Calculates the metaphone key of string.
     * 
     * @param int|null $max_phonemes
     * 
     * @return StringObject
     */
    public function metaphone(int|null $max_phonemes = 0): self
    {
        $this->rawData = metaphone($this->getRawData(), $max_phonemes);

        return $this;
    }

    /**
     * Calculate Levenshtein distance between two strings
     * 
     * @param string $compare
     * @param int|null $insertion_cost
     * @param int|null $replacement_cost
     * @param int|null $deletion_cost
     * 
     * @return int
     */
    public function levenshteinDistance(string $compare, int|null $insertion_cost = 1, int|null $replacement_cost = 1, int|null $deletion_cost = 1): int
    {
        return levenshtein($this->getRawData(), $compare, $insertion_cost, $replacement_cost, $deletion_cost);
    }

    /**
     * Get character at specified index
     * 
     * @param int $index
     * 
     * @return string
     */
    public function getCharacter(int $index): mixed
    {
        return $this->getRawData()[$index];
    }

    /**
     * Increment string
     * 
     * @return bool|StringObject
     * 
     * @throws Exception
     */
    public function decrement(): bool|self
    {
        if (OperationSystem::comparePHPVersion('8.3.0', '<')) {
            return false;
        }

        if (!function_exists('str_decrement')) {
            throw new Exception('function str_decrement is not exists');
        }

        $this->rawData = \str_decrement($this->rawData);

        return $this;
    }

    /**
     * Approximate string matching
     * 
     * @param string $pattern
     * @param int $k
     * 
     * @return ArrayObject
     */
    public function approximateMatching(string $pattern, int $k): ArrayObject
    {
        $matched = [];
        $text = $this->cloneRawData();
        $n = strlen($text);
        $m = strlen($pattern);

        if ($m > $n) {
            return new ArrayObject([]);
        }

        for ($i = 0; $i <= $n - $m; $i++) {
            $mismatch = 0;
            for ($j = 0; $j < $m; $j++) {
                if ($text[$i + $j] !== $pattern[$j]) {
                    $mismatch++;
                }
            }

            if ($mismatch <= $k) {
                $offset = $i + 1;
                $length = $m - $mismatch;

                $matched[] = [
                    'offset' => $offset,
                    'text' => substr($text, $offset, $length)
                ];
            }
        }

        return new ArrayObject($matched);
    }

    /**
     * Wagner-Fischer algorithm for edit distance
     * 
     * @param string $s2
     * 
     * @return StringObject
     */
    public function wagnerFischer(string $s2): StringObject
    {
        $s1 = $this->cloneRawData();
        $m = strlen($s1);
        $n = strlen($s2);

        $dp = [];
        for ($i = 0; $i <= $m; $i++) {
            $dp[$i] = [];
            $dp[$i][0] = $i;
        }

        for ($j = 0; $j <= $n; $j++) {
            $dp[0][$j] = $j;
        }

        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                $cost = ($s1[$i - 1] === $s2[$j - 1]) ? 0 : 1;

                $dp[$i][$j] = min(
                    $dp[$i][$j - 1] + 1,
                    $dp[$i - 1][$j] + 1,
                    $dp[$i - 1][$j - 1] + $cost
                );
            }
        }

        return new self($dp[$m][$n]);
    }

    /**
     * Calculate the bigram probability of a sentence based on a given corpus
     * 
     * @param string $corpusText
     * @param string $sentenceText
     * 
     * @return float|int
     */
    public static function calculateSentenceBigramProbability(string $corpusText, string $sentenceText): float|int
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $corpusText, -1, PREG_SPLIT_NO_EMPTY);

        $unigramCounts = [];
        $bigramCounts = [];

        foreach ($sentences as $sentence) {
            $cleanedSentence = strtolower(preg_replace('/[^a-zA-Z가-힣\s]/u', '', $sentence));
            $words = preg_split('/\s+/', $cleanedSentence, -1, PREG_SPLIT_NO_EMPTY);
            $words = array_merge(['<S>'], $words, ['<E>']);

            $prevWord = null;
            foreach ($words as $currentWord) {
                $unigramCounts[$currentWord] = ($unigramCounts[$currentWord] ?? 0) + 1;

                if ($prevWord !== null) {
                    if (!isset($bigramCounts[$prevWord])) {
                        $bigramCounts[$prevWord] = [];
                    }

                    $bigramCounts[$prevWord][$currentWord] = ($bigramCounts[$prevWord][$currentWord] ?? 0) + 1;
                }
                $prevWord = $currentWord;
            }
        }

        $cleanedSentence = strtolower(preg_replace('/[^a-zA-Z가-힣\s]/u', '', $sentenceText));
        $words = preg_split('/\s+/', $cleanedSentence, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_merge(['<S>'], $words, ['<E>']);

        $sentenceProbability = 1.0;

        for ($i = 1; $i < count($words); $i++) {
            $prevWord = $words[$i - 1];
            $currentWord = $words[$i];

            $bigramCount = $bigramCounts[$prevWord][$currentWord] ?? 0;
            $prevWordCount = $unigramCounts[$prevWord] ?? 0;

            if ($prevWordCount > 0) {
                $bigramProbability = $bigramCount / $prevWordCount;
            } else {
                $bigramProbability = 0.0;
            }

            $sentenceProbability *= $bigramProbability;

            if ($sentenceProbability === 0.0) {
                break;
            }
        }

        return $sentenceProbability;
    }

    /**
     * Aho-Corasick algorithm for multiple pattern matching
     * 
     * @param array $patterns
     * 
     * @return ArrayObject
     */
    public function ahoCorasick(array $patterns = []): ArrayObject
    {
        $data = $this->cloneRawData();

        $ahoCorasick = new AhoCorasick($patterns);
        $matches = $ahoCorasick->search($data);

        return new ArrayObject($matches);
    }

    /**
     * Dynamic Programming Approximate Matching
     * 
     * @param string $P
     * @param int $k
     * 
     * @return array
     */
    public function dpApproximateMatching(string $P, int $k): array
    {
        $T = $this->cloneRawData();
        $n = strlen($T);
        $m = strlen($P);

        $matched = [];

        if ($m > $n) {
            return $matched;
        }

        $dp = [];
        for ($i = 0; $i <= $m; $i++) {
            $dp[$i] = [];
            $dp[$i][0] = $i;
        }

        for ($j = 0; $j <= $n; $j++) {
            $dp[0][$j] = 0;
        }

        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                $cost = ($P[$i - 1] === $T[$j - 1]) ? 0 : 1;

                $dp[$i][$j] = min(
                    $dp[$i][$j - 1] + 1,
                    $dp[$i - 1][$j] + 1,
                    $dp[$i - 1][$j - 1] + $cost
                );
            }
        }

        $dp = [];
        $prev_dp = [];

        for ($i = 0; $i <= $m; $i++) {
            $prev_dp[$i] = $i;
        }

        for ($j = 1; $j <= $n; $j++) {
            $dp[0] = 0;

            for ($i = 1; $i <= $m; $i++) {
                $cost = ($P[$i - 1] === $T[$j - 1]) ? 0 : 1;

                $dp[$i] = min(
                    $prev_dp[$i] + 1,
                    $dp[$i - 1] + 1,
                    $prev_dp[$i - 1] + $cost
                );
            }

            if ($dp[$m] <= $k) {
                $offset = $j - $m + 1;
                $length = $m - $dp[$m];

                $matched[] = [
                    'offset' => $offset,
                    'text' => substr($T, $offset, $length)
                ];
            }

            $prev_dp = $dp;
        }

        return $matched;
    }

    /**
     * Smith-Waterman algorithm for local sequence alignment
     * 
     * @param string $seq2
     * @param int $matchScore
     * @param int $mismatchScore
     * @param int $gapPenalty
     * 
     * @return mixed
     */
    public function smithWaterman(string $seq2, int $matchScore = 2, int $mismatchScore = -1, int $gapPenalty = -1): mixed
    {
        $seq1 = $this->cloneRawData();
        $m = strlen($seq1);
        $n = strlen($seq2);

        $dp = [];
        $maxScore = 0;

        for ($i = 0; $i <= $m; $i++) {
            $dp[$i] = [];
            $dp[$i][0] = 0;
        }

        for ($j = 0; $j <= $n; $j++) {
            $dp[0][$j] = 0;
        }

        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                $score = ($seq1[$i - 1] === $seq2[$j - 1]) ? $matchScore : $mismatchScore;

                $dp[$i][$j] = max(
                    0,
                    $dp[$i][$j - 1] + $gapPenalty,
                    $dp[$i - 1][$j] + $gapPenalty,
                    $dp[$i - 1][$j - 1] + $score
                );

                if ($dp[$i][$j] > $maxScore) {
                    $maxScore = $dp[$i][$j];
                }
            }
        }

        return $maxScore;
    }

    /**
     * Needleman-Wunsch algorithm for global sequence alignment
     * 
     * @param string $seq2
     * @param int $matchScore
     * @param int $mismatchScore
     * @param int $gapPenalty
     * 
     * @return StringObject
     */
    public function needlemanWunsch(string $seq2, int $matchScore = 1, int $mismatchScore = -1, int $gapPenalty = -1): StringObject
    {
        $seq1 = $this->cloneRawData();
        $m = strlen($seq1);
        $n = strlen($seq2);

        $dp = [];
        for ($i = 0; $i <= $m; $i++) {
            $dp[$i] = [];
            $dp[$i][0] = $i * $gapPenalty;
        }
        for ($j = 0; $j <= $n; $j++) {
            $dp[0][$j] = $j * $gapPenalty;
        }

        for ($i = 1; $i <= $m; $i++) {
            for ($j = 1; $j <= $n; $j++) {
                $score = ($seq1[$i - 1] === $seq2[$j - 1]) ? $matchScore : $mismatchScore;

                $dp[$i][$j] = max(
                    $dp[$i][$j - 1] + $gapPenalty,
                    $dp[$i - 1][$j] + $gapPenalty,
                    $dp[$i - 1][$j - 1] + $score
                );
            }
        }

        return new self($dp[$m][$n]);
    }

    /**
     * Increment string
     * 
     * @return bool|StringObject
     * 
     * @throws Exception
     */
    public function increment(): bool|self
    {
        if (OperationSystem::comparePHPVersion('8.3.0', '<')) {
            return false;
        }

        if (!function_exists('str_increment')) {
            throw new Exception('function str_increment is not exists');
        }

        $this->rawData = str_increment($this->rawData);

        return $this;
    }

    /**
     * Randomly shuffles a string
     * 
     * @return StringObject
     */
    public function shuffle(): self
    {
        $this->rawData = str_shuffle($this->rawData);

        return $this;
    }

    /**
     * Repeat a string
     * 
     * @param int $times
     * 
     * @return StringObject
     */
    public function repeat(int $times = 0): self
    {
        $this->rawData = str_repeat($this->getRawData(), $times);

        return $this;
    }

    /**
     * Format a string
     * 
     * @param string $format
     * @param mixed ...$values
     * 
     * @return StringObject
     */
    public static function format(string $format, mixed ...$values): StringObject
    {
        $data = sprintf($format, $values);

        return new self($data);
    }

    /**
     * Check if string length is odd
     * 
     * @return bool
     */
    public function isOddLength(): bool
    {
        $length = $this->length();

        if ($length % 2 === 1) {
            return true;
        }

        return false;
    }

    /**
     * Pad a string to the center with another string
     * 
     * @param int $length
     * @param string $char
     * 
     * @return mixed
     */
    public function padCenter(int $length, string $char = " "): mixed
    {
        $value = $this->getRawData();
        $totalPad = $length - strlen($value);
        if ($totalPad <= 0) {
            return $value;
        }

        $left = intdiv($totalPad, 2);
        $right = $totalPad - $left;
        return new self(str_repeat($char, $left) . $value . str_repeat($char, $right));
    }

    /**
     * Slice a string
     * 
     * @param int $start
     * @param int|null $end
     * @param int $step
     * 
     * @return StringObject
     */
    public function slice(int $start, ?int $end = null, int $step = 1): StringObject
    {
        $value = $this->getRawData();
        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $end = $end ?? count($chars);
        $result = [];

        for ($i = $start; $i < $end; $i += $step) {
            $result[] = $chars[$i];
        }

        return new self(implode("", $result));
    }

    /**
     * Pad a string to a certain length with another string
     * 
     * @param int $length
     * @param string $padString
     * @param int $padType
     * 
     * @return StringObject
     */
    public function pad(int $length, string $padString = ' ', int $padType = STR_PAD_RIGHT): static
    {
        $this->rawData = str_pad($this->getRawData(), $length, $padString, $padType);

        return $this;
    }

    /**
     * Replace a substring
     * 
     * @param array|string $replacement
     * @param array|int $start
     * @param array|int|null $length
     * 
     * @return StringObject
     */
    public function replaceSubstr(array|string $replacement, array|int $start, array|int|null $length = null): static
    {
        if ($length === null) {
            $length = $this->length();
        }

        $this->rawData = substr_replace($this->getRawData(), $replacement, $start, $length);

        return $this;
    }

    /**
     * Convert an object to string
     * 
     * @param mixed $object
     * 
     * @return string
     * 
     * @throws InvalidArgumentException
     */
    protected static function stringify(mixed $object): string
    {
        if (is_object($object) && ReflectionHandler::isMethodExists($object, '__toString')) {
            return $object->__toString();
        }

        if (is_string($object)) {
            return $object;
        }

        throw new InvalidArgumentException('Parameter is not stringable');
    }

    /**
     * Get string length
     * 
     * @return int
     */
    public function length(): int
    {
        return StringHandler::length($this->rawData);
    }

    /**
     * Remove null bytes
     * 
     * @return StringObject
     */
    public function removeNullByte(): self
    {
        $this->rawData = StringHandler::removeNullByte($this->rawData);

        return $this;
    }

    /**
     * Concatenates the string
     * 
     * @param string $string
     * 
     * @return StringObject
     */
    public function concat(string $string): self
    {
        $this->rawData = sprintf("%s%s", $this->getRawData(), $string);

        return $this;
    }

    /**
     * Remove double spaces
     * 
     * @return StringObject
     */
    public function removeDoubleSpace(): static
    {
        $this->rawData = preg_replace('/ {2,}/', ' ', $this->getRawData());

        return $this;
    }

    /**
     * Validate resident registration number in Korea
     * 
     * @return bool
     */
    public function isValidResidentRegistrationNumberInKorea(): bool
    {
        $data = $this->getRawData();
        if (strlen($data) == !12) {
            return false;
        }

        if (preg_match("/[0-9]{2}(0[1-9]|1[012])(0[1-9]|1[0-9]|2[0-9]|3[01])-?[012349][0-9]{5}[0-9]/i", $data) <= 0) {
            return false;
        }

        if (!preg_match('/^[0-9]{6}-?[0-9]{7}$/', $data)) {
            return false;
        }

        $data = str_replace('-', '', $data);

        $year = (int) substr($data, 0, 2);
        $month = (int) substr($data, 2, length: 2);
        $day = (int) substr($data, 4, 2);

        $validation_code = (int) ($data[12]);
        $gender = (int) substr($data, 6, 1);
        $province = substr($data, 7, 2);

        if (!in_array($gender, [1, 2, 3, 4, 5, 6, 7, 8])) {
            return false;
        }

        if ($year <= 0 || $month <= 0 || $month > 12 || $day <= 0 || $day > 31) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $carry = ($i % 8) + 2;
            $sum += $data[$i] * $carry;
        }

        $checksum = (11 - ($sum % 11)) % 10;
        return $checksum === $validation_code;
    }

    /**
     * Validate corporation number in Korea
     * 
     * @return bool
     */
    public function isValidCorporationNumberInKorea(): bool
    {
        $data = $this->getRawData();

        if (!preg_match('/^[0-9]{6}-?[0-9]{7}$/', $data)) {
            return false;
        }

        $data = str_replace('-', '', $data);

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $carry = ($i % 2) + 1;
            $sum += $data[$i] * $carry;
        }
        $checksum = (10 - ($sum % 10)) % 10;

        if ($data[6] !== '0') {
            return false;
        }

        return $checksum === (int) ($data[12]);
    }

    /**
     * Remove UTF-8 BOM
     * 
     * @return StringObject
     */
    public function removeZeroWidthSpace(): self
    {
        $this->rawData = StringHandler::removeZeroWidthSpace($this->rawData);

        return $this;
    }

    /**
     * Partition the string
     * 
     * @param string $sep
     * 
     * @return ArrayObject
     */
    public function partition(string $sep): ArrayObject
    {
        $value = $this->rawData;
        $pos = strpos($value, $sep);
        if ($pos === false) {
            return new ArrayObject([$value, "", ""]);
        }

        return new ArrayObject([
            substr($value, 0, $pos),
            $sep,
            substr($value, $pos + strlen($sep))
        ]);
    }

    /**
     * Remove UTF-8 BOM
     * 
     * @return StringObject
     */
    public function removeUtf8Bom(): self
    {
        $this->rawData = StringHandler::removeUtf8Bom($this->rawData);

        return $this;
    }

    /**
     * Pluralize the string
     *
     * @return StringObject The pluralized StringObject.
     */
    public function pluralize(): self
    {
        $item = $this->getRawData();

        $lastChar = strtolower($item[strlen($item) - 1]);
        if ($lastChar === 's') {
            return new self($item . 'es');
        }

        if ($lastChar === 'y') {
            return new self(substr($item, 0, -1) . 'ies');
        }

        return new self($item . 's');
    }

    /**
     * Get sentence length statistics
     *
     * @return array{min: mixed, max: mixed, avg: mixed} An associative array with 'min', 'max', and 'avg' sentence lengths.
     */
    public function sentenceLengthStats(): array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($this->getRawData()), -1, PREG_SPLIT_NO_EMPTY);
        $lengths = array_map('mb_strlen', $sentences);

        if (empty($lengths)) {
            return ['min' => 0, 'max' => 0, 'avg' => 0];
        }

        return [
            'min' => min($lengths),
            'max' => max($lengths),
            'avg' => array_sum($lengths) / count($lengths)
        ];
    }

    /**
     * Extract hashtags from the string
     *
     * @return array An array of extracted hashtags without the '#' symbol.
     */
    public function extractMentions(): array
    {
        $value = $this->getRawData();
        preg_match_all('/@(\w+)/u', $value, $matches);
        return $matches[1];
    }

    /**
     * Extract hashtags from the string
     *
     * @return array An array of extracted hashtags without the '#' symbol.
     */
    public function extractHashtags(): array
    {
        $value = $this->getRawData();
        preg_match_all('/#(\w+)/u', $value, $matches);
        return $matches[1];
    }

    /**
     * Get word length distribution
     *
     * @return array An associative array where keys are word lengths and values are their frequencies.
     */
    public function wordLengthDistribution(): array
    {
        $words = $this->tokenize();
        $dist = [];

        foreach ($words as $w) {
            $len = mb_strlen($w);
            $dist[$len] = ($dist[$len] ?? 0) + 1;
        }

        ksort($dist);
        return $dist;
    }

    /**
     * Convert string to alternating case
     *
     * @param bool $startUpper Whether to start with uppercase (true) or lowercase (false).
     * 
     * @return StringObject The modified StringObject.
     */
    public function alternatingCase(bool $startUpper = true): StringObject
    {
        $chars = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        $toggle = $startUpper;

        foreach ($chars as $ch) {
            if (preg_match('/[A-Za-z]/u', $ch)) {
                $out[] = $toggle ? mb_strtoupper($ch) : mb_strtolower($ch);
                $toggle = !$toggle;
            } else {
                $out[] = $ch;
            }
        }

        return new self(implode('', $out));
    }

    /**
     * Perform typoglycemia shuffle on the string
     *
     * @return StringObject The shuffled StringObject.
     */
    public function typoglycemiaShuffle(): StringObject
    {
        $v = $this->getRawData();
        $words = preg_split('/(\b)/u', $v, -1, PREG_SPLIT_DELIM_CAPTURE);

        $shuffled = array_map(function ($w) {
            if (preg_match('/^\w{4,}$/u', $w)) {
                $chars = preg_split('//u', $w, -1, PREG_SPLIT_NO_EMPTY);
                $first = array_shift($chars);
                $last = array_pop($chars);
                if (!empty($chars)) {
                    shuffle($chars);
                }

                return $first . implode('', $chars) . $last;
            }

            return $w;
        }, $words);

        return new self(implode('', $shuffled));
    }

    /**
     * Create a mirrored string
     *
     * @param string $divider The divider string between original and mirrored parts.
     * @param bool $includeDivider Whether to include the divider in the output.
     * 
     * @return StringObject The mirrored StringObject.
     */
    public function mirror(string $divider = '|', bool $includeDivider = true): StringObject
    {
        $v = $this->getRawData();
        $rev = $this->mb_strrev($v);

        return new self($includeDivider ? ($v . $divider . $rev) : ($v . $rev));
    }

    /**
     * Interleave two strings with specified step sizes
     *
     * @param string $other The other string to interleave with.
     * @param int $stepA Number of characters to take from the original string at each step.
     * @param int $stepB Number of characters to take from the other string at each step.
     * 
     * @return StringObject The interleaved StringObject.
     */
    public function interleave(string $other, int $stepA = 1, int $stepB = 1): StringObject
    {
        $a = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);
        $b = preg_split('//u', $other, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        $i = $j = 0;

        while ($i < count($a) || $j < count($b)) {
            for ($k = 0; $k < $stepA && $i < count($a); $k++) {
                $out[] = $a[$i++];
            }

            for ($k = 0; $k < $stepB && $j < count($b); $k++) {
                $out[] = $b[$j++];
            }
        }

        return new self(implode('', $out));
    }

    /**
     * ROT-N cipher encryption/decryption
     *
     * @param int $n The rotation amount (default is 13).
     * 
     * @return StringObject The modified StringObject.
     */
    public function rotN(int $n = 13): StringObject
    {
        $n = $n % 26;

        $map = function ($ch) use ($n) {
            $o = ord($ch);
            if ($o >= 65 && $o <= 90) {
                return chr(65 + (($o - 65 + $n) % 26));
            }

            if ($o >= 97 && $o <= 122) {
                return chr(97 + (($o - 97 + $n) % 26));
            }

            return $ch;
        };

        $bytes = str_split($this->getRawData());
        return new self(implode('', array_map($map, $bytes)));
    }

    /**
     * Vigenère cipher encryption/decryption
     *
     * @param string $key The cipher key.
     * @param bool $decrypt Whether to decrypt (true) or encrypt (false).
     * @return StringObject The modified StringObject.
     */
    public function vigenere(string $key, bool $decrypt = false): StringObject
    {
        if ($key === '') {
            return new self($this->getRawData());
        }

        $key = preg_replace('/[^A-Za-z]/', '', $key);
        $kv = array_map(fn($c) => (ord(strtoupper($c)) - 65), str_split($key));
        $out = [];
        $ki = 0;

        foreach (str_split($this->getRawData()) as $ch) {
            $o = ord($ch);
            if ($o >= 65 && $o <= 90) {
                $shift = $kv[$ki++ % count($kv)];
                $new = $decrypt ? ($o - 65 - $shift + 26) % 26 : ($o - 65 + $shift) % 26;
                $out[] = chr(65 + $new);
            } elseif ($o >= 97 && $o <= 122) {
                $shift = $kv[$ki++ % count($kv)];
                $new = $decrypt ? ($o - 97 - $shift + 26) % 26 : ($o - 97 + $shift) % 26;
                $out[] = chr(97 + $new);
            } else {
                $out[] = $ch;
            }
        }

        return new self(implode('', $out));
    }

    /**
     * Expand tabs to spaces
     *
     * @param int $size Number of spaces per tab.
     * 
     * @return StringObject The modified StringObject with expanded tabs.
     */
    public function expandTabs(int $size = 4): StringObject
    {
        $v = $this->getRawData();
        $lines = preg_split('/\R/u', $v);
        $out = [];

        foreach ($lines as $line) {
            $expanded = '';
            $col = 0;

            foreach (str_split($line) as $ch) {
                if ($ch === "\t") {
                    $spaces = $size - ($col % $size);
                    $expanded .= str_repeat(' ', $spaces);
                    $col += $spaces;
                } else {
                    $expanded .= $ch;
                    $col++;
                }
            }

            $out[] = $expanded;
        }

        return new self(implode("\n", $out));
    }

    /**
     * Justify text to a specified width
     *
     * @param int $width Desired line width.
     * 
     * @return StringObject The justified StringObject.
     */
    public function justify(int $width): StringObject
    {
        $words = preg_split('/\s+/u', trim($this->getRawData()), -1, PREG_SPLIT_NO_EMPTY);
        $lines = [];
        $line = [];
        $len = 0;

        foreach ($words as $w) {
            $wl = mb_strlen($w);
            if ($len + $wl + count($line) > $width) {
                $lines[] = self::justifyLine($line, $width);
                $line = [$w];
                $len = $wl;
            } else {
                $line[] = $w;
                $len += $wl;
            }
        }

        if (!empty($line)) {
            $lines[] = implode(' ', $line);
        }

        return new self(implode("\n", $lines));
    }

    /**
     * Justify a single line
     *
     * @param array $words Words in the line.
     * @param int $width Desired line width.
     * 
     * @return string Justified line.
     */
    private static function justifyLine(array $words, int $width): string
    {
        if (count($words) === 1) {
            return $words[0];
        }

        $textLen = array_sum(array_map('mb_strlen', $words));
        $spaces = $width - $textLen;
        $gaps = count($words) - 1;
        $base = intdiv($spaces, $gaps);
        $extra = $spaces % $gaps;
        $out = '';

        foreach ($words as $i => $w) {
            $out .= $w;
            if ($i < $gaps) {
                $out .= str_repeat(' ', $base + ($i < $extra ? 1 : 0));
            }
        }

        return $out;
    }

    /**
     * Frame the string with a border
     *
     * @param int $padding Number of spaces padding around the text.
     * @param string $char Character to use for the border.
     * 
     * @return StringObject The framed StringObject.
     */
    public function frame(int $padding = 1, string $char = '*'): StringObject
    {
        $lines = preg_split('/\R/u', $this->getRawData());
        $max = 0;

        foreach ($lines as $l) {
            $max = max($max, mb_strlen($l));
        }

        $w = $max + 2 * $padding + 2;
        $top = str_repeat($char, $w);
        $body = array_map(function ($l) use ($padding, $char, $max) {
            $padRight = $max - mb_strlen($l);
            return $char . str_repeat(' ', $padding) . $l . str_repeat(' ', $padRight + $padding) . $char;
        }, $lines);

        return new self(implode("\n", array_merge([$top], $body, [$top])));
    }

    /**
     * Apply Run-Length Encoding (RLE) to the string.
     *
     * @return StringObject The RLE encoded StringObject.
     */
    public function runLengthEncode(): StringObject
    {
        $chars = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($chars)) {
            return new self('');
        }

        $out = '';
        $prev = $chars[0];
        $count = 1;

        for ($i = 1; $i < count($chars); $i++) {
            if ($chars[$i] === $prev) {
                $count++;
            } else {
                $out .= $prev . ($count > 1 ? $count : '');
                $prev = $chars[$i];
                $count = 1;
            }
        }
        $out .= $prev . ($count > 1 ? $count : '');

        return new self($out);
    }

    /**
     * Collapse consecutive repeated characters into a single character.
     *
     * @param int $min Minimum number of repeats to collapse.
     * @return StringObject The modified StringObject with collapsed repeats.
     */
    public function collapseRepeats(int $min = 2): StringObject
    {
        $v = $this->getRawData();
        $collapsed = preg_replace('/(.)\1{' . ($min - 1) . ',}/u', '$1', $v);

        return new self($collapsed ?? $v);
    }

    /**
     * Apply Rail Fence Cipher encoding to the string.
     *
     * @param int $rows Number of rails.
     * @return StringObject The encoded StringObject.
     */
    public function railFenceEncode(int $rows = 3): StringObject
    {
        $v = $this->getRawData();
        if ($rows <= 1 || $v === '') {
            return new self($v);
        }

        $rails = array_fill(0, $rows, '');
        $r = 0;
        $dir = 1;

        foreach (preg_split('//u', $v, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            $rails[$r] .= $ch;
            if ($r === 0) {
                $dir = 1;
            } elseif ($r === $rows - 1) {
                $dir = -1;
            }

            $r += $dir;
        }

        return new self(implode('', $rails));
    }

    /**
     * Apply Zalgo text effect to the string.
     *
     * @param int $up Number of diacritical marks to add above each character.
     * @param int $mid Number of diacritical marks to add in the middle of each character.
     * @param int $down Number of diacritical marks to add below each character.
     * 
     * @return StringObject The modified StringObject with Zalgo effect.
     */
    public function zalgo(int $up = 3, int $mid = 1, int $down = 3): StringObject
    {
        $v = $this->getRawData();
        $upMarks = ['̍', '̎', '̄', '̅', '̿', '̑', '̆', '̐', '͒', '͗', '͑', '̇', '̈', '̊', '͂', '̓', '̈', '͊', '͋', '͌', '̃', '̂', '̌', '͆', '͑', '̓', '͘', '̚'];
        $midMarks = ['̕', '̛', '̀', '́', '͘', '̡', '̢', '̧', '̨', '̴', '̵', '̶', '͜', '͝', '͞', '͟', '͠', '͢', '̱', '̲', '̳', '̹', '̺', '̻', '̼', '͔', '͕', '͖', '͙', '͚'];
        $downMarks = ['̖', '̗', '̘', '̙', '̜', '̝', '̞', '̟', '̠', '̤', '̥', '̦', '̩', '̪', '̫', '̬', '̭', '̮', '̯', '̰', '̱', '̲', '̳', '̹', '̺', '̻', '̼', 'ͅ', '͇', '͈', '͉', '͍'];
        $rand = function ($arr, $n) {
            $out = '';
            for ($i = 0; $i < $n; $i++) {
                $out .= $arr[array_rand($arr)];
            }

            return $out;
        };

        $chars = preg_split('//u', $v, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';

        foreach ($chars as $ch) {
            if (preg_match('/\s/u', $ch)) {
                $out .= $ch;
                continue;
            }

            $out .= $ch . $rand($upMarks, max(0, $up)) . $rand($midMarks, max(0, $mid)) . $rand($downMarks, max(0, $down));
        }

        return new self($out);
    }

    /**
     * Calculate the Shannon entropy of the string.
     *
     * @return float The Shannon entropy value.
     */
    public function entropy(): float
    {
        $v = $this->getRawData();
        if ($v === '') {
            return 0.0;
        }

        $chars = preg_split('//u', $v, -1, PREG_SPLIT_NO_EMPTY);
        $n = count($chars);
        $freq = array_count_values($chars);
        $H = 0.0;

        foreach ($freq as $c => $count) {
            $p = $count / $n;
            $H += -$p * (log($p) / log(2));
        }

        return $H;
    }

    /**
     * Apply Spongebob mocking case to the string.
     *
     * @return StringObject The modified StringObject with mocking case.
     */
    public function mockingSpongebob(): StringObject
    {
        $s = $this->getRawData();
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';
        $toggle = false;

        foreach ($chars as $ch) {
            if (preg_match('/\p{L}/u', $ch)) {
                $out .= $toggle ? mb_strtoupper($ch) : mb_strtolower($ch);
                $toggle = !$toggle;
            } else {
                $out .= $ch;
            }
        }

        return new self($out);
    }

    /**
     * Append a Japanese-style ending to the string.
     *
     * @param string $style The style of ending to append ('casual', 'polite', 'baka').
     * 
     * @return StringObject The modified StringObject with the Japanese ending.
     */
    public function japaneseEndingify(string $style = 'casual'): StringObject
    {
        $s = trim($this->getRawData());
        if ($s === '') {
            return new self($s);
        }

        $endings = [
            'casual' => ['だよね', 'じゃん', 'ってば'],
            'polite' => ['ですね', 'でしょう', 'かな'],
            'baka' => ['だぞ', 'だよ', 'だもん'],
        ];
        $candidates = $endings[$style] ?? $endings['casual'];
        $ending = $candidates[array_rand($candidates)];

        if (preg_match('/[。！？!?]$/u', $s)) {
            return new self($s . ' ' . $ending);
        }

        return new self($s . '。' . $ending);
    }

    /**
     * Apply Japanese moeize effect to the string.
     *
     * @param int $repeatChance The chance (in percentage) to repeat each character (default is 30).
     * 
     * @return StringObject The modified StringObject with moeize effect.
     */
    public function japaneseMoeize(int $repeatChance = 30): StringObject
    {
        $s = $this->getRawData();
        if ($s === '') {
            return new self($s);
        }

        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';

        foreach ($chars as $ch) {
            $out .= $ch;
            if (rand(1, 100) <= $repeatChance) {
                $out .= str_repeat($ch, rand(1, 3));
            }

            if (rand(1, 100) <= 10) {
                $out .= '〜';
            }
        }

        $tails = ['にゃ', '〜', 'だよ', 'よぉ'];
        $out .= ' ' . $tails[array_rand($tails)];
        return new self($out);
    }

    /**
     * Apply Roh-style meme tone to the string.
     *
     * @param int $maxWords Maximum number of words in the core sentence (default is 18).
     * @param string $stamp The stamp text to append (default is '노짱' (Nojjang)).
     * @param string $sep The separator between the core sentence and the stamp (default is ' — ').
     * 
     * @return StringObject The modified StringObject with Roh-style meme tone.
     */
    public function rohMemeTone(int $maxWords = 18, string $stamp = '노짱', string $sep = ' — '): StringObject
    {
        $s = trim($this->getRawData());
        if ($s === '') {
            return new self($s);
        }

        $sentences = preg_split('/(?<=[.!?。！？])\s+/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $core = trim($sentences[0] ?? $s);
        $words = preg_split('/\s+/u', $core, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > $maxWords) {
            $core = implode(' ', array_slice($words, 0, $maxWords)) . '...';
        }

        $questionStamps = ['그래서 뭐 했노?', '부끄러운 줄 알아야지', '직무 유기 아입니까?', '안될거 머있노?', '미국한테 매달려 가지고, 바짓가랑이 매달려 가지고 응디, 미국 응딩이 뒤에서 숨어가지고 형님, 형님, 형님 빽만 믿겠다'];
        $q = $questionStamps[array_rand($questionStamps)];
        return new self($core . ' ' . $q . $sep . $stamp);
    }

    /**
     * Apply Roh-style rhetorical chain to the string.
     *
     * @param int $take Number of unique longest tokens to use (default is 3).
     * 
     * @return StringObject The modified StringObject with Roh-style rhetorical chain.
     */
    public function rohRhetoricalChain(int $take = 3): StringObject
    {
        $s = $this->getRawData();
        if (trim($s) === '') {
            return new self($s);
        }

        $tokens = preg_split('/[,\.\s。！？!?\-–—]+/u', preg_replace('/[^\p{L}\p{N}\s\-]/u', ' ', $s), -1, PREG_SPLIT_NO_EMPTY);
        usort($tokens, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        $pick = array_slice(array_values(array_unique($tokens)), 0, max(1, $take));
        if (empty($pick)) {
            return new self($s);
        }

        $chain = array_map(fn($t) => $t . '는 뭐 했노?', $pick);
        return new self(implode(' ', $chain));
    }

    /**
     * Apply Japanese "kusa" style suffix to the string.
     *
     * @param int $k The level of "kusa" to apply (1 to 6, default is 2).
     * @param bool $addKaomoji Whether to add a kaomoji at the end (default is true).
     * 
     * @return StringObject The modified StringObject with "kusa" suffix.
     */
    public function japanKusaSuffix(int $k = 2, bool $addKaomoji = true): StringObject
    {
        $s = rtrim($this->getRawData());
        if ($s === '') {
            return new self($s);
        }

        $k = max(1, min(6, $k));
        $suffix = str_repeat('w', $k) . ' ' . str_repeat('草', $k);
        $kaomoji = ['(＾ω＾)', '(・∀・)', '（＾＿＾）', '(⌒_⌒)'];
        $ending = $s . ' ' . $suffix;

        if ($addKaomoji) {
            $ending .= ' ' . $kaomoji[array_rand($kaomoji)];
        }

        return new self($ending);
    }

    /**
     * Apply Roh-style emphatic list to the string.
     *
     * @param array|null $adds Additional phrases to choose from (default phrases used if null).
     * @param int $repeat Number of phrases to append (default is 2).
     * 
     * @return StringObject The modified StringObject with Roh-style emphasis.
     */
    public function rohListEmphasize(?array $adds = null, int $repeat = 2): StringObject
    {
        $s = trim($this->getRawData());
        if ($s === '') {
            return new self($s);
        }

        $defaults = ['경제도 잘하고', '문화도 잘하고', '영화도 잘하고', '기술도 잘하고'];
        $adds = $adds ?? $defaults;
        $choose = array_slice($adds, 0, $repeat);
        $suffix = implode('， ', $choose) . '。';
        return new self($s . ' ' . $suffix);
    }

    /**
     * Apply Roh-inspired stamp to the string.
     *
     * @param string $stamp The stamp text to append (default is '노짱' (Nojjang)).
     * @param string $sep The separator between the core sentence and the stamp (default is ' — ').
     * 
     * @return StringObject The modified StringObject with the Roh-inspired stamp.
     */
    public function rohInspiredStamp(string $stamp = '노짱', string $sep = ' — '): StringObject
    {
        $s = trim($this->getRawData());
        if ($s === '') {
            return new self($s);
        }

        $sentences = preg_split('/(?<=[.!?。！？])\s+/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $core = $sentences[0] ?? $s;
        $core = rtrim($core, ' .。！？!?') . '。';
        return new self($core . $sep . $stamp);
    }

    /**
     * Apply Japanese "kusa" style suffix to the string.
     *
     * @param bool $addKaomoji Whether to add a kaomoji at the end (default is true).
     * @param int $kusaLevel The level of "kusa" to apply (1 to 5, default is 1).
     * 
     * @return StringObject The modified StringObject with "kusa" suffix.
     */
    public function japaneseKusaify(bool $addKaomoji = true, int $kusaLevel = 1): StringObject
    {
        $s = $this->getRawData();
        if (trim($s) === '') {
            return new self($s);
        }

        $out = $s;
        $k = max(1, min(5, $kusaLevel));
        $suffix = str_repeat('w', $k) . ' ' . str_repeat('草', $k);
        $out = rtrim($out) . ' ' . $suffix;

        if ($addKaomoji) {
            $kao = ['(＾ω＾)', '(・∀・)', '（＾＿＾）', '(⌒_⌒)'];
            $out .= ' ' . $kao[array_rand($kao)];
        }

        return new self($out);
    }

    /**
     * Convert ASCII characters to fullwidth characters for a vaporwave effect.
     *
     * @return StringObject The modified StringObject with fullwidth characters.
     */
    public function fullwidthVaporwave(): StringObject
    {
        $s = $this->getRawData();
        $out = '';
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($chars as $ch) {
            $code = mb_ord($ch);
            if ($code >= 33 && $code <= 126) {
                $out .= mb_chr(0xFF00 + $code - 0x20);
            } elseif ($ch === ' ') {
                $out .= '　';
            } else {
                $out .= $ch;
            }
        }

        return new self($out);
    }

    /**
     * Deep fry the string by applying random capitalization, character duplication, and adding emojis.
     *
     * @param int $intensity The intensity of the deep fry effect (default is 2).
     * 
     * @return StringObject The modified StringObject after deep frying.
     */
    public function deepFry(int $intensity = 2): StringObject
    {
        $s = $this->getRawData();
        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';

        foreach ($chars as $ch) {
            if (preg_match('/\p{L}/u', $ch)) {
                $out .= (rand(0, 1) ? mb_strtoupper($ch) : mb_strtolower($ch));
                if (rand(0, 10) < $intensity) {
                    $out .= str_repeat($ch, rand(0, $intensity));
                }
            } else {
                $out .= $ch;
            }
        }

        $spice = [' 😂', ' 🔥', ' 💯', ' 😩', ' 🤡', ' 👌'];
        for ($i = 0; $i < $intensity; $i++) {
            $out .= $spice[array_rand($spice)];
        }

        return new self($out);
    }

    /**
     * Simulate Thanos snap by removing half of the words or characters.
     *
     * @param bool $preserveWords If true, remove whole words; if false, remove individual characters.
     * 
     * @return StringObject The modified StringObject after the Thanos snap effect.
     */
    public function thanosSnap(bool $preserveWords = true): StringObject
    {
        $s = $this->getRawData();
        if ($s === '') {
            return new self($s);
        }

        if ($preserveWords) {
            $words = preg_split('/(\s+)/u', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
            $wordIndexes = [];
            for ($i = 0; $i < count($words); $i += 2) {
                $wordIndexes[] = $i;
            }

            shuffle($wordIndexes);
            $toRemove = array_slice($wordIndexes, 0, intdiv(count($wordIndexes), 2));
            $removeSet = array_flip($toRemove);
            $out = '';

            for ($i = 0; $i < count($words); $i++) {
                $isWordPosition = ($i % 2 === 0);
                if ($isWordPosition && isset($removeSet[$i])) {
                    continue;
                }

                $out .= $words[$i];
            }

            return new self($out);
        } else {
            $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
            $keep = array_rand(array_flip(range(0, count($chars) - 1)), max(1, intdiv(count($chars), 2)));
            $keepSet = array_flip((array) $keep);
            $out = '';

            foreach ($chars as $i => $c) {
                if (isset($keepSet[$i])) {
                    $out .= $c;
                }
            }

            return new self($out);
        }
    }

    /**
     * Simulate a translation glitch by shuffling words and adding random duplications and parentheses.
     *
     * @return StringObject The modified StringObject with a translation glitch effect.
     */
    public function mockTranslationGlitch(): StringObject
    {
        $s = $this->getRawData();
        $words = preg_split('/(\s+)/u', $s, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wordBlocks = [];
        for ($i = 0; $i < count($words); $i += 2) {
            $wordBlocks[] = $words[$i];
        }

        shuffle($wordBlocks);
        $out = '';
        $wbIdx = 0;
        for ($i = 0; $i < count($words); $i++) {
            if ($i % 2 === 0) {
                $w = $wordBlocks[$wbIdx++] ?? '';
                if (rand(0, 5) === 0) {
                    $w .= ' ' . $w;
                }

                if (rand(0, 6) === 0) {
                    $w = '(' . $w . ')';
                }
                $out .= $w;
            } else {
                $out .= $words[$i];
            }
        }

        return new self($out);
    }

    /**
     * Convert the string into Doge Speak by inserting random doge adjectives.
     *
     * @return StringObject The modified StringObject with doge speak.
     */
    public function dogeSpeak(): StringObject
    {
        $s = trim($this->getRawData());
        if ($s === '') {
            return new self($s);
        }

        $words = preg_split('/\s+/u', $s);
        $adjs = ['so', 'such', 'very', 'much', 'wow', 'amaze', 'many', 'much'];
        $outParts = [];

        foreach ($words as $w) {
            $outParts[] = $w;
            if (rand(0, 3) === 0) {
                $outParts[] = $adjs[array_rand($adjs)];
            }
        }

        if (rand(0, 1) === 1) {
            $outParts[] = 'wow';
        }

        return new self(implode(' ', $outParts));
    }

    /**
     * Uwuify the string by replacing certain characters and adding cute suffixes.
     *
     * @return StringObject The modified StringObject with uwuified text.
     */
    public function uwuify(): StringObject
    {
        $s = $this->getRawData();
        $map = [
            '/r|l/i' => 'w',
            '/n([aeiou])/i' => 'ny$1',
            '/ove/i' => 'uv',
        ];
        $out = $s;

        foreach ($map as $pat => $rep) {
            $out = preg_replace($pat, $rep, $out);
        }

        $tails = [' uwu', ' >w<', ' owo', ' (✿◠‿◠)'];
        if (trim($out) !== '') {
            $out .= $tails[array_rand($tails)];
        }

        return new self($out);
    }

    /**
     * Check if the string is an isogram (no repeating letters).
     *
     * @return bool True if the string is an isogram, false otherwise.
     */
    public function isIsogram(): bool
    {
        $v = mb_strtolower(preg_replace('/[\s-]/u', '', $this->getRawData()));
        $chars = preg_split('//u', $v, -1, PREG_SPLIT_NO_EMPTY);
        $seen = [];

        foreach ($chars as $c) {
            if (isset($seen[$c])) {
                return false;
            }

            $seen[$c] = true;
        }

        return true;
    }

    /**
     * Check if the string is a pangram (contains every letter of the alphabet at least once).
     *
     * @param string $alphabet The set of characters to check against (default: English alphabet).
     * @return bool True if the string is a pangram, false otherwise.
     */
    public function isPangram(string $alphabet = 'abcdefghijklmnopqrstuvwxyz'): bool
    {
        $v = mb_strtolower($this->getRawData());

        foreach (preg_split('//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            if (mb_strpos($v, $ch) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Delete every N-th character from the string.
     *
     * @param int $n The interval of characters to delete (e.g., 2 deletes every 2nd character).
     * 
     * @return StringObject The modified StringObject with every N-th character removed.
     */
    public function deleteEveryNth(int $n): StringObject
    {
        if ($n <= 0) {
            return new self($this->getRawData());
        }

        $chars = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        foreach ($chars as $idx => $ch) {
            if ((($idx + 1) % $n) !== 0) {
                $out[] = $ch;
            }
        }

        return new self(implode('', $out));
    }

    /**
     * Extract all numbers (integers and floats) from the string.
     *
     * @return array An array of extracted numbers.
     */
    public function extractAllNumbers(): array
    {
        $value = $this->getRawData();
        preg_match_all('/\d+(\.\d+)?/', $value, $matches);
        return array_map(fn($n) => is_numeric($n) ? $n + 0 : $n, $matches[0]);
    }

    /**
     * Remove non-breaking spaces from the string.
     *
     * @return self The modified StringObject with non-breaking spaces removed.
     */
    public function removeNonBreakingSpace(): self
    {
        $this->rawData = StringHandler::removeNonBreakingSpace($this->rawData);

        return $this;
    }

    /**
     * Emphasize specified words in the string by surrounding them with a marker.
     *
     * @param array $words List of words to emphasize.
     * @param string $marker Marker to surround the emphasized words (default: '***').
     * 
     * @return StringObject The modified StringObject with emphasized words.
     */
    public function emphasizeWords(array $words, string $marker = '***'): StringObject
    {
        $v = $this->getRawData();
        if (trim($v) === '') {
            return new self($v);
        }

        foreach ($words as $w) {
            $escaped = preg_quote($w, '/');
            $v = preg_replace_callback("/\b($escaped)\b/iu", function ($m) use ($marker) {
                return $marker . $m[1] . $marker;
            }, $v);
        }

        return new self($v);
    }

    /**
     * Stutterify the string by randomly repeating characters.
     *
     * @param float $prob Probability of stuttering each character (0.0 to 1.0).
     * @param int $maxRepeat Maximum number of times to repeat a character.
     * 
     * @return StringObject The modified StringObject with stuttering effect.
     */
    public function stutterify(float $prob = 0.35, int $maxRepeat = 2): StringObject
    {
        $v = $this->getRawData();
        $chars = preg_split('//u', $v, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';

        foreach ($chars as $ch) {
            $out .= $ch;

            if (preg_match('/\p{L}/u', $ch) && mt_rand() / mt_getrandmax() < $prob) {
                $out .= str_repeat($ch, mt_rand(1, $maxRepeat));
            }
        }

        return new self($out);
    }

    /**
     * Gradually ramp up capitalization of words in the string.
     *
     * @param int $steps Number of steps to reach full capitalization.
     * 
     * @return StringObject The modified StringObject with ramped-up capitalization.
     */
    public function rampUpCaps(int $steps = 3): StringObject
    {
        $v = trim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        $words = preg_split('/(\s+)/u', $v, -1, PREG_SPLIT_DELIM_CAPTURE);
        $letters = 0;
        foreach ($words as $i => $w) {
            if (trim($w) === '') {
                continue;
            }

            $ratio = min(1.0, ($letters / max(1, mb_strlen($v))) * $steps);
            $letters += mb_strlen($w);
            $up = (mt_rand() / mt_getrandmax()) < $ratio;
            $words[$i] = $up ? mb_strtoupper($w) : $w;
        }

        return new self(implode('', $words));
    }

    /**
     * Create a cascading echo effect by progressively shortening the string.
     *
     * @param int $repeat Number of repetitions in the cascade.
     * @param float $decay Decay factor for the length of each repetition.
     * @param string $sep Separator to use between repetitions.
     * 
     * @return StringObject The modified StringObject with cascade echo effect.
     */
    public function cascadeEcho(int $repeat = 3, float $decay = 0.6, string $sep = ' — '): StringObject
    {
        $v = trim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        $parts = [];
        $cur = $v;
        for ($i = 0; $i < $repeat; $i++) {
            $parts[] = $cur;
            $len = max(1, (int) (mb_strlen($cur) * $decay));
            $cur = mb_substr($cur, 0, $len);
        }

        return new self(implode($sep, $parts));
    }

    /**
     * Insert a pause in the string after a specified number of words.
     *
     * @param int $breakAfter Number of words after which to insert the pause.
     * @param string $delimiter The delimiter to use for the pause.
     * 
     * @return StringObject The modified StringObject with the pause inserted.
     */
    public function punchlineDelay(int $breakAfter = 6, string $delimiter = "\n...\n"): StringObject
    {
        $v = trim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        $words = preg_split('/\s+/u', $v, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words) <= $breakAfter) {
            return new self($v);
        }

        $lead = implode(' ', \array_slice($words, 0, $breakAfter));
        $tail = implode(' ', \array_slice($words, $breakAfter));
        return new self($lead . $delimiter . $tail);
    }

    /**
     * Escalate the ending punctuation of the string.
     *
     * @param string $punct The punctuation character to use for escalation.
     * @param int $max The maximum number of punctuation characters.
     * 
     * @return StringObject The modified StringObject with escalated punctuation.
     */
    public function escalatePunctuation(string $punct = '!', int $max = 5): StringObject
    {
        $v = rtrim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        return new self(preg_replace_callback('/([.!?])\s*$/u', function ($m) use ($punct, $max) {
            return str_repeat($punct, min(mb_strlen($m[1]) + 1, $max));
        }, $v) ?: $v . str_repeat($punct, 1));
    }

    /**
     * Split the string into chunks of words separated by a ripple effect.
     *
     * @param int $chunk Number of words per chunk.
     * @param string $sep Separator to use between chunks.
     * 
     * @return StringObject The modified StringObject with ripple split.
     */
    public function rippleSplit(int $chunk = 8, string $sep = ' ∙ '): StringObject
    {
        $v = trim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        $words = preg_split('/\s+/u', $v, -1, PREG_SPLIT_NO_EMPTY);
        $out = [];
        for ($i = 0; $i < count($words); $i += $chunk) {
            $out[] = implode(' ', array_slice($words, $i, $chunk));
        }

        return new self(implode($sep, $out));
    }

    /**
     * Create a visual stamp around the string.
     *
     * @param string $label The label to display at the top.
     * @param string $frameChar The character to use for framing.
     * 
     * @return StringObject The modified StringObject with visual stamp.
     */
    public function visualStamp(string $label = 'Important', string $frameChar = '■'): StringObject
    {
        $v = trim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        $top = $frameChar . ' ' . $label . ' ' . $frameChar;
        return new self($top . "\n" . $v . "\n" . str_repeat($frameChar, mb_strlen($top)));
    }

    /**
     * Create a progressively repeated version of the string.
     *
     * @param int $max Maximum number of repetitions.
     * @param string $sep Separator between repetitions.
     * 
     * @return StringObject The modified StringObject with progressive repeats.
     */
    public function progressiveRepeat(int $max = 4, string $sep = ' '): StringObject
    {
        $v = trim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        $out = $v;
        for ($i = 2; $i <= $max; $i++) {
            $out .= $sep . str_repeat($v, $i);
        }

        return new self($out);
    }

    /**
     * Sprinkle modifiers randomly after words in the string.
     *
     * @param array $mods List of modifiers to sprinkle.
     * @param float $prob Probability of sprinkling a modifier after each word.
     * 
     * @return StringObject The modified StringObject with sprinkled modifiers.
     */
    public function sprinkleModifiers(array $mods = ['정말', '진짜', '확실히'], float $prob = 0.25): StringObject
    {
        $v = $this->getRawData();
        $words = preg_split('/(\s+)/u', $v, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($words as $i => $w) {
            if (trim($w) === '') {
                continue;
            }

            if (mt_rand() / mt_getrandmax() < $prob) {
                $words[$i] = $w . ' ' . $mods[array_rand($mods)];
            }
        }

        return new self(implode('', $words));
    }

    /**
     * Create an animated ellipsis effect by appending dots.
     *
     * @param int $dots Number of dots to append.
     * @param bool $spaced Whether to add a space before the dots.
     * 
     * @return StringObject The modified StringObject with animated ellipsis.
     */
    public function animatedEllipsis(int $dots = 3, bool $spaced = true): StringObject
    {
        $v = rtrim($this->getRawData());
        if ($v === '') {
            return new self($v);
        }

        $dotsStr = str_repeat('.', max(1, $dots));
        return new self($v . ($spaced ? ' ' : '') . $dotsStr);
    }

    /**
     * Create a heavily distorted "glitched" version of the string.
     *
     * @param int $intensity Level of distortion (1-5).
     * @param float $noiseRate Rate of noise insertion (0.0-1.0).
     * 
     * @return StringObject The glitched StringObject.
     */
    public function grossGlitch(int $intensity = 3, float $noiseRate = 0.15): StringObject
    {
        $s = $this->getRawData();
        if ($s === '') {
            return new self($s);
        }

        $combining = [
            "\u{0300}",
            "\u{0301}",
            "\u{0302}",
            "\u{0308}",
            "\u{0327}",
            "\u{0328}",
            "\u{0336}",
            "\u{0335}",
            "\u{0315}",
            "\u{031B}",
            "\u{034F}",
            "\u{035C}"
        ];
        $weird = ['卍', '彡', '※', '¤', '▒', '▓', '☠', '☢', '⚡', '〆', '҉', '҂'];

        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';
        foreach ($chars as $ch) {
            // randomly change case / duplicate / swap visually similar chars
            if (preg_match('/\p{L}/u', $ch)) {
                if (mt_rand() / mt_getrandmax() < 0.5) {
                    $ch = (mt_rand(0, 1) ? mb_strtoupper($ch) : mb_strtolower($ch));
                }

                if (mt_rand() / mt_getrandmax() < 0.25 * $intensity) {
                    $ch = str_repeat($ch, mt_rand(1, min(3, $intensity)));
                }
            }
            $out .= $ch;

            // insert combining marks heavily
            if (mt_rand() / mt_getrandmax() < ($noiseRate * $intensity)) {
                $count = mt_rand(1, max(1, $intensity));
                for ($i = 0; $i < $count; $i++) {
                    $out .= $combining[array_rand($combining)];
                }
            }

            // sprinkle weird glyphs occasionally
            if (mt_rand() / mt_getrandmax() < (0.06 * $intensity)) {
                $out .= $weird[array_rand($weird)];
            }
        }

        // randomly reverse chunks to break flow
        if (mt_rand(0, 1) === 1) {
            $chunks = preg_split('/(\s+)/u', $out, -1, PREG_SPLIT_DELIM_CAPTURE);
            $chunks = array_map(fn($c) => (strlen($c) > 8 && mt_rand() / mt_getrandmax() < 0.4) ? $this->mb_strrev($c) : $c, $chunks);
            $out = implode('', $chunks);
        }

        return new self($out);
    }

    /**
     * Create a corrupted version of the string by applying random mutations.
     *
     * @param int|null $seed Optional seed for random number generator.
     * @param int $mutations Number of random mutations to apply.
     * 
     * @return StringObject The corrupted StringObject.
     */
    public function corruptify(?int $seed = null, int $mutations = 30): StringObject
    {
        if ($seed !== null) {
            mt_srand($seed);
        }

        $s = $this->getRawData();
        if ($s === '') {
            return new self($s);
        }

        $bytes = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $pool = [
            "\u{FFFD}",
            "\u{2063}",
            "\u{2060}",
            "\u{200B}",
            "\u{200C}", // invisible / replacement
            "□",
            "▩",
            "▧",
            "╳",
            "╬",
            "✖",
            "✦",
            "✧",
            "✶"
        ];

        for ($i = 0; $i < $mutations; $i++) {
            $pos = mt_rand(0, max(0, count($bytes) - 1));
            $mode = mt_rand(0, 4);
            switch ($mode) {
                case 0: // replace with weird char
                    $bytes[$pos] = $pool[array_rand($pool)];
                    break;
                case 1: // duplicate neighbor
                    array_splice($bytes, $pos, 0, [$bytes[$pos]]);
                    break;
                case 2: // inject invisible control
                    array_splice($bytes, $pos, 0, [$pool[array_rand($pool)]]);
                    break;
                case 3: // swap with nearby
                    $j = max(0, min(count($bytes) - 1, $pos + mt_rand(-3, 3)));
                    $tmp = $bytes[$pos];
                    $bytes[$pos] = $bytes[$j];
                    $bytes[$j] = $tmp;
                    break;
                case 4: // inject combining mark
                    $bytes[$pos] .= "\u{0338}";
                    break;
            }
        }

        // cluster-encode some spans to hex-ish fragments
        if (mt_rand(0, 1) === 1) {
            $i = mt_rand(0, max(0, count($bytes) - 2));
            $span = array_slice($bytes, $i, mt_rand(2, min(6, count($bytes) - $i)));
            $hex = implode('', array_map(fn($c) => bin2hex(mb_substr($c, 0, 1)), $span));
            array_splice($bytes, $i, count($span), ["⟦" . $hex . "⟧"]);
        }

        return new self(implode('', $bytes));
    }

    /**
     * Create a squished and stretched version of the string.
     *
     * Vowels are compressed while consonants are stretched, with occasional mid-dot separators.
     * Vertical glitch lines may also be applied randomly.
     *
     * @param int $min Minimum stretch factor for consonants.
     * @param int $max Maximum stretch factor for consonants.
     * 
     * @return StringObject The squished and stretched StringObject.
     */
    public function squishStretch(int $min = 1, int $max = 6): StringObject
    {
        $s = $this->getRawData();
        if ($s === '') {
            return new self($s);
        }

        $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $out = '';

        foreach ($chars as $ch) {
            // compress vowels, stretch consonants arbitrarily, add mid-dot separators
            if (preg_match('/[aeiouAEIOUㅏ-ㅣ가-힣]/u', $ch)) {
                if (mt_rand(0, 1) === 0) {
                    $out .= $ch;
                } else {
                    $out .= str_repeat($ch, mt_rand($min, max(1, $min)));
                }
            } else {
                $out .= str_repeat($ch, mt_rand($min, $max));
            }

            if (mt_rand(0, 10) < 2) {
                $out .= '·';
            }
        }

        // apply vertical glitch lines
        if (mt_rand(0, 2) === 1) {
            $lines = explode("\n", $out);
            foreach ($lines as &$L) {
                $insertPos = mt_rand(0, mb_strlen($L));
                $L = mb_substr($L, 0, $insertPos) . '┃' . mb_substr($L, $insertPos);
            }

            $out = implode("\n", $lines);
        }

        return new self($out);
    }

    /**
     * Create an "absolute abomination" version of the string by combining multiple distortion effects.
     *
     * @param bool $mirror Whether to append a mirrored version of the output.
     * @param int $gore The intensity of the distortion effects.
     * 
     * @return StringObject The distorted StringObject.
     */
    public function absoluteAbomination(bool $mirror = true, int $gore = 4): StringObject
    {

        $s = $this->getRawData();
        if ($s === '') {
            return new self($s);
        }

        $z = $this->grossGlitch($gore, 0.3)->getRawData();
        $c = $this->corruptify(null, 15)->getRawData();
        $ss = $this->squishStretch(1, max(3, $gore))->getRawData();

        // interleave three versions
        $a = preg_split('//u', $z, -1, PREG_SPLIT_NO_EMPTY);
        $b = preg_split('//u', $c, -1, PREG_SPLIT_NO_EMPTY);
        $d = preg_split('//u', $ss, -1, PREG_SPLIT_NO_EMPTY);

        $max = max(count($a), count($b), count($d));
        $out = '';

        for ($i = 0; $i < $max; $i++) {
            if (isset($a[$i])) {
                $out .= $a[$i];
            }

            if (isset($b[$i]) && mt_rand(0, 1) === 1) {
                $out .= $b[$i];
            }

            if (isset($d[$i]) && mt_rand(0, 2) === 0) {
                $out .= $d[$i];
            }

            if (mt_rand(0, 10) < $gore) {
                $out .= ['☠', '⚡', '✖', '҉', '卍'][array_rand([0, 1, 2, 3, 4])];
            }
        }

        if ($mirror) {
            $out .= "\n—\n" . $this->mb_strrev($out);
        }

        // add blocky frame occasionally
        if (mt_rand(0, 3) === 1) {
            $out = "▛" . str_repeat('▀', 20) . "▜\n" . $out . "\n▙" . str_repeat('▄', 20) . "▟";
        }

        return new self($out);
    }

    /**
     * Convert certain characters to Yaminjeongeum style.
     *
     * @return StringObject The converted StringObject.
     */
    public function toYaminjeongeum(): StringObject
    {
        $s = $this->getRawData();
        if ($s === '') {
            return new self($s);
        }

        $default = [
            '유' => '윾',
            '면' => '댼',
            '빙' => '넹',
            '관' => '판',
            '광' => '팡',
            '명' => '띵',
            '대' => '머',
            '티' => '日',
            '멍' => '댕',
            '귀' => '커',
            '폭' => '눞',
            '풍' => '옾',
            '눈' => '곡',
            '물' => '롬',
            '왕' => '앟',
            '근' => 'ㄹ',
            '비' => '네',
            '빔' => '넴',
            '빕' => '넵',
            '백' => '뿌',
            '격' => '꾹',
            '피' => '끠',
            '버' => '또',
            '장' => '튽',
            '김' => '숲',
            '누' => 'ϟ',
            '식' => '싀',
            '파' => '과',
            '끠' => '괴',
            '포' => '쪼',
            '먹' => '댁',
            '뉘' => '부',
            '인' => '외',
            '스' => '△',
            '외' => '요',
            '홋' => '훗',
            '위' => '읶',
            '익' => '의',
            '든' => 'ㅌ',
            '국' => 'ϡ',
            '녀' => '티',
            '임' => '읜',
            '3' => 'Ǝ',
            '1' => 'ㅣ',
            '2' => 'Z',
            '4' => '나',
            '영' => '몀',
            '몀' => '영',
            '나' => '4',
            '수' => '↑',
            '니' => '⊔ ',
            '도' => 'E',
            '드' => '⊆',
            '데' => 'θㅣ',
            '난' => 'ㅂ-',
            '이' => '9',
        ];
        $map = $map ?? $default;

        if (function_exists('mb_str_split')) {
            $chars = mb_str_split($s);
        } else {
            $chars = preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY);
        }

        foreach ($chars as &$ch) {
            if (isset($map[$ch])) {
                $ch = $map[$ch];
            }
        }
        $out = implode('', $chars);


        return new self($out);
    }

    /**
     * Compute the MD5 hash of the string.
     *
     * @param bool $binary If true, returns raw binary output (16 bytes). Default is false (32-char hex).
     *
     * @return StringObject A new StringObject containing the MD5 hash.
     */
    public function toMd5(bool $binary = false): StringObject
    {
        return new self(md5($this->getRawData(), $binary));
    }

    /**
     * Compute the SHA-1 hash of the string.
     *
     * @param bool $binary If true, returns raw binary output. Default is false (40-char hex).
     *
     * @return StringObject A new StringObject containing the SHA-1 hash.
     */
    public function toSha1(bool $binary = false): StringObject
    {
        return new self(sha1($this->getRawData(), $binary));
    }

    /**
     * Compute the SHA-256 hash of the string.
     *
     * @param bool $binary If true, returns raw binary output. Default is false (64-char hex).
     *
     * @return StringObject A new StringObject containing the SHA-256 hash.
     */
    public function toSha256(bool $binary = false): StringObject
    {
        return new self(hash('sha256', $this->getRawData(), $binary));
    }

    /**
     * Compute a hash using any supported algorithm.
     *
     * @param string $algo The hashing algorithm (e.g., 'sha512', 'crc32', 'xxh3').
     *
     * @return StringObject A new StringObject containing the hash.
     */
    public function hash(string $algo = 'sha256'): StringObject
    {
        return new self(hash($algo, $this->getRawData()));
    }

    /**
     * Compute an HMAC (Hash-based Message Authentication Code) for the string.
     *
     * @param string $key The secret key used for HMAC computation.
     * @param string $algo The hashing algorithm. Default is 'sha256'.
     *
     * @return StringObject A new StringObject containing the HMAC hex digest.
     */
    public function hmac(string $key, string $algo = 'sha256'): StringObject
    {
        return new self(hash_hmac($algo, $this->getRawData(), $key));
    }

    /**
     * Compute the CRC32 checksum of the string.
     *
     * @return int The CRC32 checksum as an integer.
     */
    public function crc32(): int
    {
        return crc32($this->getRawData());
    }

    /**
     * Encode the string as a hexadecimal representation.
     *
     * @return StringObject A new StringObject containing the hex-encoded string.
     */
    public function toHex(): StringObject
    {
        return new self(bin2hex($this->getRawData()));
    }

    /**
     * Decode the string from a hexadecimal representation.
     *
     * @return StringObject A new StringObject containing the decoded string.
     */
    public function fromHex(): StringObject
    {
        $decoded = hex2bin($this->getRawData());
        return new self($decoded !== false ? $decoded : '');
    }

    /**
     * Decode the string from Base64 format.
     *
     * @return StringObject A new StringObject containing the decoded string.
     */
    public function decodeFromBase64(): StringObject
    {
        $decoded = base64_decode($this->getRawData(), true);
        return new self($decoded !== false ? $decoded : '');
    }

    /**
     * URL-encode the string.
     *
     * @return StringObject A new StringObject containing the URL-encoded string.
     */
    public function urlEncode(): StringObject
    {
        return new self(urlencode($this->getRawData()));
    }

    /**
     * URL-decode the string.
     *
     * @return StringObject A new StringObject containing the URL-decoded string.
     */
    public function urlDecode(): StringObject
    {
        return new self(urldecode($this->getRawData()));
    }

    /**
     * Raw URL-encode the string (RFC 3986 compliant).
     *
     * @return StringObject A new StringObject containing the raw URL-encoded string.
     */
    public function rawUrlEncode(): StringObject
    {
        return new self(rawurlencode($this->getRawData()));
    }

    /**
     * Convert special characters to HTML entities.
     *
     * @param int $flags Bitmask of flags (default ENT_QUOTES | ENT_SUBSTITUTE).
     *
     * @return StringObject A new StringObject with HTML entities applied.
     */
    public function toHtmlEntities(int $flags = ENT_QUOTES | ENT_SUBSTITUTE): StringObject
    {
        return new self(htmlspecialchars($this->getRawData(), $flags, $this->encoding));
    }

    /**
     * Convert HTML entities back to their corresponding characters.
     *
     * @param int $flags Bitmask of flags (default ENT_QUOTES | ENT_SUBSTITUTE).
     *
     * @return StringObject A new StringObject with HTML entities decoded.
     */
    public function fromHtmlEntities(int $flags = ENT_QUOTES | ENT_SUBSTITUTE): StringObject
    {
        return new self(htmlspecialchars_decode($this->getRawData(), $flags));
    }

    /**
     * Convert the string to its binary representation (space-separated bytes).
     *
     * @param string $separator Separator between binary byte groups. Default is a space.
     *
     * @return StringObject A new StringObject containing the binary representation.
     */
    public function toBinary(string $separator = ' '): StringObject
    {
        $bytes = unpack('C*', $this->getRawData());
        $binary = array_map(fn(int $b) => str_pad(decbin($b), 8, '0', STR_PAD_LEFT), $bytes);
        return new self(implode($separator, $binary));
    }

    /**
     * Decode a space-separated binary string back to text.
     *
     * @param string $separator Separator between binary byte groups. Default is a space.
     *
     * @return StringObject A new StringObject containing the decoded text.
     */
    public function fromBinary(string $separator = ' '): StringObject
    {
        $parts = explode($separator, $this->getRawData());
        $text = implode('', array_map(fn(string $b) => chr((int) bindec($b)), $parts));
        return new self($text);
    }

    /**
     * Encode the string as a JSON-safe value (with proper escaping).
     *
     * @param int $flags JSON encoding flags. Default is JSON_UNESCAPED_UNICODE.
     *
     * @return StringObject A new StringObject containing the JSON-encoded value.
     */
    public function toJsonString(int $flags = JSON_UNESCAPED_UNICODE): StringObject
    {
        $encoded = json_encode($this->getRawData(), $flags);
        return new self($encoded !== false ? $encoded : '""');
    }

    /**
     * Compress the string using gzip.
     *
     * @param int $level Compression level (0-9). Default is -1 (default level).
     *
     * @return StringObject A new StringObject containing the compressed data.
     */
    public function gzCompress(int $level = -1): StringObject
    {
        $compressed = gzcompress($this->getRawData(), $level);
        return new self($compressed !== false ? $compressed : '');
    }

    /**
     * Decompress a gzip-compressed string.
     *
     * @return StringObject A new StringObject containing the decompressed data.
     */
    public function gzUncompress(): StringObject
    {
        $decompressed = @gzuncompress($this->getRawData());
        return new self($decompressed !== false ? $decompressed : '');
    }

    /**
     * Check if the string is a valid URL.
     *
     * @return bool True if the string is a valid URL.
     */
    public function isUrl(): bool
    {
        return filter_var($this->getRawData(), FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if the string is a valid IP address (IPv4 or IPv6).
     *
     * @param int $flags Optional FILTER_FLAG_IPV4 or FILTER_FLAG_IPV6 to restrict validation.
     *
     * @return bool True if the string is a valid IP address.
     */
    public function isIpAddress(int $flags = 0): bool
    {
        return filter_var($this->getRawData(), FILTER_VALIDATE_IP, $flags) !== false;
    }

    /**
     * Check if the string is a valid IPv4 address.
     *
     * @return bool True if the string is a valid IPv4 address.
     */
    public function isIpv4(): bool
    {
        return $this->isIpAddress(FILTER_FLAG_IPV4);
    }

    /**
     * Check if the string is a valid IPv6 address.
     *
     * @return bool True if the string is a valid IPv6 address.
     */
    public function isIpv6(): bool
    {
        return $this->isIpAddress(FILTER_FLAG_IPV6);
    }

    /**
     * Check if the string is a valid UUID (v1-v5).
     *
     * @return bool True if the string is a valid UUID.
     */
    public function isUuid(): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $this->getRawData()
        );
    }

    /**
     * Check if the string is valid JSON.
     *
     * @return bool True if the string is valid JSON.
     */
    public function isJson(): bool
    {
        if ($this->getRawData() === '') {
            return false;
        }

        json_decode($this->getRawData());
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Check if the string contains only numeric characters (including decimals and negatives).
     *
     * @return bool True if the string is numeric.
     */
    public function isNumeric(): bool
    {
        return is_numeric($this->getRawData());
    }

    /**
     * Check if the string contains only alphabetic characters.
     *
     * @return bool True if the string contains only letters.
     */
    public function isAlpha(): bool
    {
        return (bool) preg_match('/^\pL+$/u', $this->getRawData());
    }

    /**
     * Check if the string contains only whitespace characters (or is empty).
     *
     * @return bool True if the string is blank.
     */
    public function isBlank(): bool
    {
        return trim($this->getRawData()) === '';
    }

    /**
     * Check if the string is a valid MAC address.
     *
     * @return bool True if the string is a valid MAC address.
     */
    public function isMacAddress(): bool
    {
        return filter_var($this->getRawData(), FILTER_VALIDATE_MAC) !== false;
    }

    /**
     * Check if the string is a valid domain name.
     *
     * @return bool True if the string is a valid domain name.
     */
    public function isDomain(): bool
    {
        return filter_var($this->getRawData(), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    /**
     * Check if the string is a valid hexadecimal color code (e.g., #FF0000 or #F00).
     *
     * @return bool True if the string is a valid hex color code.
     */
    public function isHexColor(): bool
    {
        return (bool) preg_match('/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6}|[0-9A-Fa-f]{8})$/', $this->getRawData());
    }

    /**
     * Check if the string contains only printable characters.
     *
     * @return bool True if all characters are printable.
     */
    public function isPrintable(): bool
    {
        return (bool) preg_match('/^[[:print:]]*$/', $this->getRawData());
    }

    /**
     * Check if the string is a valid Base64-encoded value.
     *
     * @return bool True if the string is valid Base64.
     */
    public function isBase64(): bool
    {
        if ($this->getRawData() === '') {
            return false;
        }

        return (bool) preg_match('/^[A-Za-z0-9+\/]*={0,2}$/', $this->getRawData())
            && base64_decode($this->getRawData(), true) !== false;
    }

    /**
     * Check if the string contains multibyte (non-ASCII) characters.
     *
     * @return bool True if the string contains multibyte characters.
     */
    public function isMultibyte(): bool
    {
        return mb_strlen($this->getRawData(), $this->encoding) !== strlen($this->getRawData());
    }

    /**
     * Check if the string is a valid semantic version string (e.g., "1.2.3" or "1.0.0-alpha+build.1").
     *
     * @return bool True if the string is a valid semver.
     */
    public function isSemver(): bool
    {
        return (bool) preg_match(
            '/^(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)(?:-([\da-zA-Z\-]+(?:\.[\da-zA-Z\-]+)*))?(?:\+([\da-zA-Z\-]+(?:\.[\da-zA-Z\-]+)*))?$/',
            $this->getRawData()
        );
    }

    /**
     * Extract the substring between the first occurrence of two delimiters.
     *
     * @param string $start The opening delimiter.
     * @param string $end The closing delimiter.
     *
     * @return StringObject|null A new StringObject with the content between delimiters, or null if not found.
     */
    public function between(string $start, string $end): ?StringObject
    {
        $value = $this->getRawData();
        $startPos = mb_strpos($value, $start);

        if ($startPos === false) {
            return null;
        }

        $startPos += mb_strlen($start);
        $endPos = mb_strpos($value, $end, $startPos);

        if ($endPos === false) {
            return null;
        }

        return new self(mb_substr($value, $startPos, $endPos - $startPos));
    }

    /**
     * Extract all substrings between pairs of delimiters.
     *
     * @param string $start The opening delimiter.
     * @param string $end The closing delimiter.
     *
     * @return array An array of strings found between the delimiter pairs.
     */
    public function betweenAll(string $start, string $end): array
    {
        $value = $this->getRawData();
        $results = [];
        $offset = 0;

        while (($startPos = mb_strpos($value, $start, $offset)) !== false) {
            $startPos += mb_strlen($start);
            $endPos = mb_strpos($value, $end, $startPos);

            if ($endPos === false) {
                break;
            }

            $results[] = mb_substr($value, $startPos, $endPos - $startPos);
            $offset = $endPos + mb_strlen($end);
        }

        return $results;
    }

    /**
     * Get the portion of the string before the first occurrence of the given delimiter.
     *
     * @param string $delimiter The delimiter to search for.
     *
     * @return StringObject A new StringObject with the portion before the delimiter.
     */
    public function beforeFirst(string $delimiter): StringObject
    {
        $value = $this->getRawData();
        $pos = mb_strpos($value, $delimiter);

        return new self($pos === false ? $value : mb_substr($value, 0, $pos));
    }

    /**
     * Get the portion of the string after the first occurrence of the given delimiter.
     *
     * @param string $delimiter The delimiter to search for.
     *
     * @return StringObject A new StringObject with the portion after the delimiter.
     */
    public function afterFirst(string $delimiter): StringObject
    {
        $value = $this->getRawData();
        $pos = mb_strpos($value, $delimiter);

        if ($pos === false) {
            return new self($value);
        }

        return new self(mb_substr($value, $pos + mb_strlen($delimiter)));
    }

    /**
     * Get the portion of the string before the last occurrence of the given delimiter.
     *
     * @param string $delimiter The delimiter to search for.
     *
     * @return StringObject A new StringObject with the portion before the last delimiter.
     */
    public function beforeLast(string $delimiter): StringObject
    {
        $value = $this->getRawData();
        $pos = mb_strrpos($value, $delimiter);

        return new self($pos === false ? $value : mb_substr($value, 0, $pos));
    }

    /**
     * Get the portion of the string after the last occurrence of the given delimiter.
     *
     * @param string $delimiter The delimiter to search for.
     *
     * @return StringObject A new StringObject with the portion after the last delimiter.
     */
    public function afterLast(string $delimiter): StringObject
    {
        $value = $this->getRawData();
        $pos = mb_strrpos($value, $delimiter);

        if ($pos === false) {
            return new self($value);
        }

        return new self(mb_substr($value, $pos + mb_strlen($delimiter)));
    }

    /**
     * Extract an excerpt from the string centered around a given phrase.
     *
     * Useful for showing search result snippets with context.
     *
     * @param string $phrase The phrase to center the excerpt around.
     * @param int $radius Number of characters of context to include on each side.
     * @param string $omission The string to use when text is truncated. Default is '...'.
     *
     * @return StringObject|null A new StringObject with the excerpt, or null if phrase not found.
     */
    public function excerpt(string $phrase, int $radius = 50, string $omission = '...'): ?StringObject
    {
        $value = $this->getRawData();
        $pos = mb_stripos($value, $phrase);

        if ($pos === false) {
            return null;
        }

        $start = max(0, $pos - $radius);
        $end = min(mb_strlen($value), $pos + mb_strlen($phrase) + $radius);

        $prefix = $start > 0 ? $omission : '';
        $suffix = $end < mb_strlen($value) ? $omission : '';

        return new self($prefix . mb_substr($value, $start, $end - $start) . $suffix);
    }

    /**
     * Extract all URLs from the string.
     *
     * @return array An array of URLs found in the string.
     */
    public function extractUrls(): array
    {
        $value = $this->getRawData();
        preg_match_all('/https?:\/\/[^\s<>\"\'\)]+/ui', $value, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Extract all email addresses from the string.
     *
     * @return array An array of email addresses found in the string.
     */
    public function extractEmails(): array
    {
        $value = $this->getRawData();
        preg_match_all('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/u', $value, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Extract all numeric values from the string.
     *
     * @return array An array of numeric strings found in the string.
     */
    public function extractNumbers(): array
    {
        $value = $this->getRawData();
        preg_match_all('/-?\d+(?:\.\d+)?/', $value, $matches);
        return $matches[0] ?? [];
    }

    /**
     * Extract all IPv4 addresses from the string.
     *
     * @return array An array of IPv4 addresses found in the string.
     */
    public function extractIpAddresses(): array
    {
        $value = $this->getRawData();
        preg_match_all('/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $value, $matches);
        return array_filter($matches[0] ?? [], fn($ip) => filter_var($ip, FILTER_VALIDATE_IP) !== false);
    }

    /**
     * Convert the string to PascalCase (StudlyCase).
     *
     * @return StringObject A new StringObject in PascalCase.
     */
    public function toPascalCase(): StringObject
    {
        $value = $this->getRawData();
        $parts = preg_split('/[^a-zA-Z0-9]+/', strtolower($value), -1, PREG_SPLIT_NO_EMPTY);
        return new self(implode('', array_map('ucfirst', $parts)));
    }

    /**
     * Convert the string to dot.notation.
     *
     * @return StringObject A new StringObject in dot notation.
     */
    public function toDotNotation(): StringObject
    {
        $value = $this->getRawData();
        $dot = strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '.', $value));
        return new self(trim($dot, '.'));
    }

    /**
     * Convert the string to CONSTANT_CASE (screaming snake case).
     *
     * @return StringObject A new StringObject in CONSTANT_CASE.
     */
    public function toConstantCase(): StringObject
    {
        $value = $this->getRawData();
        // Insert underscore before uppercase letters (for camelCase/PascalCase input)
        $separated = preg_replace('/([a-z])([A-Z])/', '$1_$2', $value);
        $constant = strtoupper(preg_replace('/[^\p{L}\p{N}]+/u', '_', $separated));
        return new self(trim($constant, '_'));
    }

    /**
     * Swap the case of each character (uppercase becomes lowercase and vice versa).
     *
     * @return StringObject A new StringObject with swapped case.
     */
    public function swapCase(): StringObject
    {
        $chars = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);
        $out = [];

        foreach ($chars as $ch) {
            $lower = mb_strtolower($ch);
            $upper = mb_strtoupper($ch);
            $out[] = ($ch === $lower) ? $upper : $lower;
        }

        return new self(implode('', $out));
    }

    /**
     * Surround the string with the given value on both sides.
     *
     * @param string $wrapper The string to wrap around.
     *
     * @return StringObject A new StringObject surrounded by the wrapper.
     */
    public function surround(string $wrapper): StringObject
    {
        return new self($wrapper . $this->getRawData() . $wrapper);
    }

    /**
     * Mask a portion of the string with a repeated character.
     *
     * Useful for redacting sensitive information (e.g., credit card numbers, emails).
     *
     * @param string $char The masking character. Default is '*'.
     * @param int $start Starting position of the mask (0-based).
     * @param int|null $length Number of characters to mask. Null masks to the end.
     *
     * @return StringObject A new StringObject with the masked portion.
     */
    public function mask(string $char = '*', int $start = 0, ?int $length = null): StringObject
    {
        $value = $this->getRawData();
        $strLen = mb_strlen($value);

        if ($start >= $strLen) {
            return new self($value);
        }

        $length = $length ?? ($strLen - $start);
        $length = min($length, $strLen - $start);

        $before = mb_substr($value, 0, $start);
        $masked = str_repeat($char, $length);
        $after = mb_substr($value, $start + $length);

        return new self($before . $masked . $after);
    }

    /**
     * Convert the string to a "headline" format by splitting camelCase, snake_case, etc.
     *
     * Example: "getUserName_fast" → "Get User Name Fast"
     *
     * @return StringObject A new StringObject in headline format.
     */
    public function toHeadline(): StringObject
    {
        $value = $this->getRawData();
        // Split on underscores, hyphens, dots, and camelCase boundaries
        $separated = preg_replace('/([a-z])([A-Z])/', '$1 $2', $value);
        $words = preg_split('/[\s_\-\.]+/', $separated, -1, PREG_SPLIT_NO_EMPTY);
        return new self(implode(' ', array_map('ucfirst', array_map('strtolower', $words))));
    }

    /**
     * Remove all non-alphanumeric characters from the string.
     *
     * @return StringObject A new StringObject containing only letters and digits.
     */
    public function alphanumericOnly(): StringObject
    {
        return new self(preg_replace('/[^\p{L}\p{N}]/u', '', $this->getRawData()));
    }

    /**
     * Remove all non-numeric characters from the string, preserving sign and decimal point.
     *
     * @return StringObject A new StringObject containing only the numeric portion.
     */
    public function numericOnly(): StringObject
    {
        return new self(preg_replace('/[^0-9.\-]/', '', $this->getRawData()));
    }

    /**
     * Transliterate the string to ASCII by removing diacritical marks.
     *
     * @return StringObject A new StringObject with ASCII transliteration applied.
     */
    public function toAscii(): StringObject
    {
        $value = $this->getRawData();
        $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        return new self($ascii !== false ? $ascii : $value);
    }

    /**
     * Strip all HTML and PHP tags from the string.
     *
     * @param array|string|null $allowedTags Tags to allow (e.g., '<p><br>'). Default is null (strip all).
     *
     * @return StringObject A new StringObject with tags removed.
     */
    public function stripTags(array|string|null $allowedTags = null): StringObject
    {
        return new self(strip_tags($this->getRawData(), $allowedTags));
    }

    /**
     * Escape the string for safe use in a shell command argument.
     *
     * @return StringObject A new StringObject with shell-safe escaping applied.
     */
    public function escapeShell(): StringObject
    {
        return new self(escapeshellarg($this->getRawData()));
    }

    /**
     * Remove all control characters (ASCII 0-31 and 127) except newlines and tabs.
     *
     * @param bool $preserveNewlines Whether to keep newline and tab characters.
     *
     * @return StringObject A new StringObject with control characters removed.
     */
    public function removeControlChars(bool $preserveNewlines = true): StringObject
    {
        $pattern = $preserveNewlines
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'
            : '/[\x00-\x1F\x7F]/u';
        return new self(preg_replace($pattern, '', $this->getRawData()));
    }

    /**
     * Sanitize the string for safe use in a filename by removing invalid characters.
     *
     * @param string $replacement Replacement character for invalid characters. Default is '_'.
     *
     * @return StringObject A new StringObject safe for use as a filename.
     */
    public function sanitizeFilename(string $replacement = '_'): StringObject
    {
        $value = $this->getRawData();
        // Remove null bytes, slashes, backslashes, and other unsafe chars
        $safe = preg_replace('/[\/\\\\<>:"|?*\x00-\x1F]/', $replacement, $value);
        // Remove leading/trailing dots and spaces
        $safe = trim($safe, ". \t");
        // Collapse repeated replacements
        $safe = preg_replace('/' . preg_quote($replacement, '/') . '{2,}/', $replacement, $safe);
        return new self($safe ?: 'unnamed');
    }

    /**
     * Calculate the Shannon entropy of the string.
     *
     * Higher entropy indicates more randomness/information content.
     *
     * @return float The Shannon entropy value in bits.
     */
    public function calcEntropy(): float
    {
        $value = $this->getRawData();

        if ($value === '') {
            return 0.0;
        }

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        $len = count($chars);
        $freq = array_count_values($chars);
        $entropy = 0.0;

        foreach ($freq as $count) {
            $p = $count / $len;
            $entropy -= $p * log($p, 2);
        }

        return $entropy;
    }

    /**
     * Estimate the reading time for the string content.
     *
     * @param int $wordsPerMinute Average reading speed. Default is 200 words per minute.
     *
     * @return array An associative array with 'minutes' and 'seconds' keys.
     */
    public function readingTime(int $wordsPerMinute = 200): array
    {
        $words = preg_split('/\s+/', trim($this->getRawData()), -1, PREG_SPLIT_NO_EMPTY);
        $totalSeconds = (int) ceil((count($words) / $wordsPerMinute) * 60);

        return [
            'minutes' => intdiv($totalSeconds, 60),
            'seconds' => $totalSeconds % 60,
        ];
    }

    /**
     * Count the number of lines in the string.
     *
     * @return int The number of lines.
     */
    public function countLines(): int
    {
        if ($this->getRawData() === '') {
            return 0;
        }

        return substr_count($this->getRawData(), "\n") + 1;
    }

    /**
     * Get the byte length of the string.
     *
     * @return int The number of bytes.
     */
    public function byteLength(): int
    {
        return strlen($this->getRawData());
    }

    /**
     * Count the number of occurrences of each vowel in the string.
     *
     * @return array An associative array with vowels as keys and their counts as values.
     */
    public function vowelCount(): array
    {
        $value = mb_strtolower($this->getRawData());
        $vowels = ['a' => 0, 'e' => 0, 'i' => 0, 'o' => 0, 'u' => 0];

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $ch) {
            if (isset($vowels[$ch])) {
                $vowels[$ch]++;
            }
        }

        return $vowels;
    }

    /**
     * Count the number of consonants in the string.
     *
     * @return int The number of consonant characters.
     */
    public function consonantCount(): int
    {
        $value = mb_strtolower($this->getRawData());
        return preg_match_all('/[bcdfghjklmnpqrstvwxyz]/u', $value);
    }

    /**
     * Calculate the ratio of unique characters to total characters.
     *
     * @return float A value between 0.0 and 1.0 indicating character diversity.
     */
    public function charDiversity(): float
    {
        $chars = preg_split('//u', $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY);

        if (empty($chars)) {
            return 0.0;
        }

        return count(array_unique($chars)) / count($chars);
    }

    /**
     * Compute the Jaccard similarity coefficient with another string using word-level comparison.
     *
     * @param string $other The other string to compare with.
     *
     * @return float A value between 0.0 and 1.0 indicating word-level similarity.
     */
    public function jaccardSimilarity(string $other): float
    {
        $aWords = preg_split('/\W+/u', mb_strtolower($this->getRawData()), -1, PREG_SPLIT_NO_EMPTY);
        $bWords = preg_split('/\W+/u', mb_strtolower($other), -1, PREG_SPLIT_NO_EMPTY);

        $setA = array_unique($aWords);
        $setB = array_unique($bWords);

        $intersection = count(array_intersect($setA, $setB));
        $union = count(array_unique(array_merge($setA, $setB)));

        return $union === 0 ? 1.0 : $intersection / $union;
    }

    /**
     * Calculate the similarity percentage between this string and another.
     *
     * @param string $other The string to compare with.
     *
     * @return float The similarity as a percentage (0.0 to 100.0).
     */
    public function similarityPercent(string $other): float
    {
        $percent = 0.0;
        similar_text($this->getRawData(), $other, $percent);
        return $percent;
    }

    // =========================================================================
    // Fluent Helpers & Utilities
    // =========================================================================

    /**
     * Apply a callback to the string value and return a new StringObject with the result.
     *
     * Useful for applying custom transformations in a fluent chain.
     *
     * @param callable $callback A function that receives the raw string and returns a new string.
     *
     * @return StringObject A new StringObject with the callback result.
     */
    public function pipe(callable $callback): StringObject
    {
        return new self((string) $callback($this->getRawData()));
    }

    /**
     * Apply a callback to the StringObject for side effects, then return the same object.
     *
     * Useful for debugging or logging within a fluent chain.
     *
     * @param callable $callback A function that receives the StringObject instance.
     *
     * @return static The same StringObject instance (unmodified).
     */
    public function tap(callable $callback): static
    {
        $callback($this);
        return $this;
    }

    /**
     * Conditionally apply a callback to the string.
     *
     * @param bool $condition If true, the callback is applied; otherwise the string is returned unchanged.
     * @param callable $callback A function that receives the raw string and returns a new string.
     *
     * @return StringObject The resulting StringObject.
     */
    public function when(bool $condition, callable $callback): StringObject
    {
        if ($condition) {
            return new self((string) $callback($this->getRawData()));
        }

        return new self($this->getRawData());
    }

    /**
     * Return the string if it is not empty, otherwise return the given default value.
     *
     * @param string $default The fallback value.
     *
     * @return StringObject The current string if non-empty, otherwise a new StringObject with the default.
     */
    public function ifEmpty(string $default): StringObject
    {
        return new self(trim($this->getRawData()) === '' ? $default : $this->getRawData());
    }

    /**
     * Limit the byte size of the string, truncating at a valid UTF-8 boundary.
     *
     * Useful for database column constraints or protocol limits.
     *
     * @param int $maxBytes Maximum number of bytes allowed.
     * @param string $ellipsis The string to append if truncation occurs. Default is ''.
     *
     * @return StringObject A new StringObject within the byte limit.
     */
    public function limitBytes(int $maxBytes, string $ellipsis = ''): StringObject
    {
        $value = $this->getRawData();

        if (strlen($value) <= $maxBytes) {
            return new self($value);
        }

        $ellipsisLen = strlen($ellipsis);
        $target = $maxBytes - $ellipsisLen;

        // Walk backwards to find a valid UTF-8 boundary
        $truncated = mb_strcut($value, 0, $target, $this->encoding);

        return new self($truncated . $ellipsis);
    }

    /**
     * Convert the string to chunks of a specified size.
     *
     * @param int $size The size of each chunk.
     * @param string $separator The separator between chunks. Default is a space.
     *
     * @return StringObject A new StringObject with the chunked string.
     */
    public function chunk(int $size, string $separator = ' '): StringObject
    {
        $value = $this->getRawData();
        $chunks = mb_str_split($value, $size);
        return new self(implode($separator, $chunks));
    }

    /**
     * Check if the string contains any of the given substrings.
     *
     * @param array $needles An array of substrings to search for.
     *
     * @return bool True if the string contains at least one of the needles.
     */
    public function containsAny(array $needles): bool
    {
        $value = $this->getRawData();

        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($value, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the string contains all of the given substrings.
     *
     * @param array $needles An array of substrings to search for.
     *
     * @return bool True if the string contains all of the needles.
     */
    public function containsAll(array $needles): bool
    {
        $value = $this->getRawData();

        foreach ($needles as $needle) {
            if (mb_strpos($value, $needle) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Squish the string by collapsing all whitespace (including newlines) into single spaces and trimming.
     *
     * @return StringObject A new StringObject with squished whitespace.
     */
    public function squish(): StringObject
    {
        return new self(preg_replace('/[\s\x{00A0}]+/u', ' ', trim($this->getRawData())));
    }

    /**
     * Convert the string into a human-friendly file size representation.
     *
     * Treats the string value as a number of bytes.
     *
     * @param int $precision Number of decimal places. Default is 2.
     *
     * @return StringObject A new StringObject with the formatted file size (e.g., "1.5 MB").
     */
    public function toHumanFileSize(int $precision = 2): StringObject
    {
        $bytes = (float) $this->getRawData();

        if ($bytes < 0) {
            return new self('0 B');
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return new self(round($bytes, $precision) . ' ' . $units[$index]);
    }

    /**
     * Repeat the string until it reaches the desired length, then trim to exact length.
     *
     * @param int $length The target length of the resulting string.
     *
     * @return StringObject A new StringObject repeated to the target length.
     */
    public function repeatToLength(int $length): StringObject
    {
        $value = $this->getRawData();

        if ($value === '' || $length <= 0) {
            return new self('');
        }

        $repeated = str_repeat($value, (int) ceil($length / mb_strlen($value)));
        return new self(mb_substr($repeated, 0, $length));
    }

    /**
     * Count the number of paragraphs in the string.
     *
     * Paragraphs are defined as blocks of text separated by one or more blank lines.
     *
     * @return int The number of paragraphs.
     */
    public function countParagraphs(): int
    {
        $value = trim($this->getRawData());

        if ($value === '') {
            return 0;
        }

        $paragraphs = preg_split('/\n\s*\n/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        return count($paragraphs);
    }

    /**
     * Check if the string starts with any of the given prefixes.
     *
     * @param array $prefixes An array of prefix strings to check.
     *
     * @return bool True if the string starts with any of the given prefixes.
     */
    public function startsWithAny(array $prefixes): bool
    {
        $value = $this->getRawData();

        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the string ends with any of the given suffixes.
     *
     * @param array $suffixes An array of suffix strings to check.
     *
     * @return bool True if the string ends with any of the given suffixes.
     */
    public function endsWithAny(array $suffixes): bool
    {
        $value = $this->getRawData();

        foreach ($suffixes as $suffix) {
            if ($suffix !== '' && str_ends_with($value, $suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace the first occurrence of a substring.
     *
     * @param string $search The substring to search for.
     * @param string $replace The replacement string.
     *
     * @return StringObject A new StringObject with the first occurrence replaced.
     */
    public function replaceFirst(string $search, string $replace): StringObject
    {
        $value = $this->getRawData();
        $pos = mb_strpos($value, $search);

        if ($pos === false) {
            return new self($value);
        }

        return new self(
            mb_substr($value, 0, $pos) . $replace . mb_substr($value, $pos + mb_strlen($search))
        );
    }

    /**
     * Test if the string matches a wildcard pattern (using * and ?).
     *
     * @param string $pattern The wildcard pattern (e.g., "foo*bar?").
     *
     * @return bool True if the string matches the pattern.
     */
    public function matchesWildcard(string $pattern): bool
    {
        return fnmatch($pattern, $this->getRawData());
    }

    /**
     * Convert the string to a PHP array by parsing it as a CSV row.
     *
     * @param string $separator The field delimiter. Default is ','.
     * @param string $enclosure The field enclosure character. Default is '"'.
     * @param string $escape The escape character. Default is '\\'.
     *
     * @return array An array of fields parsed from the CSV row.
     */
    public function parseCsvRow(string $separator = ',', string $enclosure = '"', string $escape = '\\'): array
    {
        $result = str_getcsv($this->getRawData(), $separator, $enclosure, $escape);
        return $result !== false ? $result : [];
    }

    /**
     * Convert the string to title case while respecting common English articles and prepositions.
     *
     * Words like "a", "the", "of", "in", etc. remain lowercase unless they are the first word.
     *
     * @return StringObject A new StringObject with smart title casing.
     */
    public function toSmartTitleCase(): StringObject
    {
        $minorWords = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'nor', 'of', 'on', 'or', 'so', 'the', 'to', 'up', 'yet'];

        $words = preg_split('/\s+/', mb_strtolower(trim($this->getRawData())));
        $result = [];

        foreach ($words as $i => $word) {
            if ($i === 0 || !in_array($word, $minorWords, true)) {
                $result[] = mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
            } else {
                $result[] = $word;
            }
        }

        return new self(implode(' ', $result));
    }

    /** 
     * Calculate the Hamming distance in bits between this string and another.
     *
     * The Hamming distance is the number of positions at which the corresponding bits are different.
     * This method compares the raw byte representations of the strings.
     *
     * @param string $b The other string to compare with.
     *
     * @return int The Hamming distance in bits.
     */
    public function hammingBitDistance(string $b): int
    {
        $a = $this->getRawData();
        $len = min(strlen($a), strlen($b));
        $dist = 0;
        for ($i = 0; $i < $len; $i++) {
            $x = ord($a[$i]) ^ ord($b[$i]);
            $x = $x - (($x >> 1) & 0x55);
            $x = ($x & 0x33) + (($x >> 2) & 0x33);
            $dist += (($x + ($x >> 4)) & 0x0F);
        }
        return $dist;
    }

    /**
     * Get Unicode code point of a UTF-8 character.
     * 
     * @param string $char
     * @return int
     */
    private static function charCode(string $char): int
    {
        return mb_ord($char, 'UTF-8');
    }

    /** 
     * Check if the string contains any Korean characters (Hangul Syllables).
     *
     * @return bool True if the string contains at least one Korean character.
     */
    public function containsJapaneseSymbols(): bool
    {
        $text = self::getRawData();
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($text, $i, 1, 'UTF-8');
            $cp = self::charCode($c);

            if (
                ($cp >= 0x3021 && $cp <= 0x3029) || // kana-like symbols
                ($cp >= 0x3031 && $cp <= 0x3035) || // kana-like symbols
                ($cp >= 0x3041 && $cp <= 0x3096) || // hiragana
                ($cp >= 0x30A1 && $cp <= 0x30FA) || // katakana
                ($cp >= 0xFF66 && $cp <= 0xFF9D) || // half-width katakana
                ($cp >= 0x4E00 && $cp <= 0x9FAF) || // CJK unified ideographs
                ($cp >= 0x3400 && $cp <= 0x4DBF) || // CJK Extension A
                ($cp >= 0xF900 && $cp <= 0xFAFF)    // CJK Compatibility Ideographs
            ) {
                return true;
            }
        }

        return false;
    }

    /** 
     * Check if the string contains any Korean characters (Hangul Syllables).
     *
     * @return bool True if the string contains at least one Korean character.
     */
    public function containsKoreanSymbols(): bool
    {
        $text = self::getRawData();
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($text, $i, 1, 'UTF-8');
            $cp = self::charCode($c);

            if ($cp >= 0xAC00 && $cp <= 0xD7AF) { // Hangul Syllables
                return true;
            }
        }

        return false;
    }

    /** 
     * Check if the string contains any Chinese characters (CJK Unified Ideographs).
     *
     * @return bool True if the string contains at least one Chinese character.
     */
    public function containsChineseSymbols(): bool
    {
        $text = self::getRawData();
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($text, $i, 1, 'UTF-8');
            $cp = self::charCode($c);

            if (($cp >= 0x4E00 && $cp <= 0x9FAF) || ($cp >= 0x3400 && $cp <= 0x4DBF) || ($cp >= 0xF900 && $cp <= 0xFAFF)) {
                return true;
            }
        }

        return false;
    }

    /** 
     * Check if the string contains any standard Latin letters (A-Z, a-z).
     *
     * @return bool True if the string contains at least one standard Latin letter.
     */
    public function containsRussianSymbols(): bool
    {
        $text = self::getRawData();
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($text, $i, 1, 'UTF-8');
            $cp = self::charCode($c);

            if (($cp >= 0x0400 && $cp <= 0x04FF) || ($cp >= 0x0500 && $cp <= 0x052F) || ($cp >= 0x2DE0 && $cp <= 0x2DFF) || ($cp >= 0xA640 && $cp <= 0xA69F) || ($cp >= 0x1C80 && $cp <= 0x1C88) || ($cp >= 0xFE2E && $cp <= 0xFE2F) || $cp === 0x1D2B || $cp === 0x1D78) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the string contains any standard Latin letters (A-Z, a-z).
     *
     * @return bool True if the string contains at least one standard Latin letter.
     */
    public function containsStandardLatinSymbols(): bool
    {
        $text = self::getRawData();
        $len = mb_strlen($text, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($text, $i, 1, 'UTF-8');
            $cp = self::charCode($c);

            if (($cp >= 0x0041 && $cp <= 0x005A) || /** A-Z */ ($cp >= 0x0061 && $cp <= 0x007A) /** a-z */) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the character at the given (UTF-8) index.
     *
     * Negative indexes count from the end of the string.
     *
     * @param int $index Zero-based character index.
     *
     * @return string Single character, or empty string when out of range.
     */
    public function characterAt(int $index): string
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        if ($index < 0) {
            $index += $len;
        }
        if ($index < 0 || $index >= $len) {
            return '';
        }

        return mb_substr($value, $index, 1, 'UTF-8');
    }

    /**
     * Unicode code point at the given character index.
     *
     * @param int $index Zero-based character index.
     *
     * @return int|null Code point, or null when out of range.
     */
    public function characterCodeAt(int $index): ?int
    {
        $char = $this->characterAt($index);
        if ($char === '') {
            return null;
        }
        $unpacked = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));

        return $unpacked[1] ?? null;
    }

    /**
     * Unicode code points that make up the string.
     *
     * @return int[]
     */
    public function codePoints(): array
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        $out = [];
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            $unpacked = unpack('N', mb_convert_encoding($char, 'UCS-4BE', 'UTF-8'));
            if (isset($unpacked[1])) {
                $out[] = $unpacked[1];
            }
        }

        return $out;
    }

    /**
     * Raw byte values that make up the string.
     *
     * @return int[]
     */
    public function bytes(): array
    {
        $value = (string) $this->getRawData();
        $out = [];
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $out[] = ord($value[$i]);
        }

        return $out;
    }

    /**
     * Split the string into grapheme clusters (user-perceived characters).
     *
     * Falls back to mb_substr when the intl extension is unavailable.
     *
     * @return string[]
     */
    public function graphemes(): array
    {
        $value = (string) $this->getRawData();
        if (function_exists('grapheme_strlen')) {
            $len = (int) (grapheme_strlen($value) ?: 0);
            $out = [];
            for ($i = 0; $i < $len; $i++) {
                $out[] = (string) grapheme_substr($value, $i, 1);
            }

            return $out;
        }

        $len = mb_strlen($value, 'UTF-8');
        $out = [];
        for ($i = 0; $i < $len; $i++) {
            $out[] = mb_substr($value, $i, 1, 'UTF-8');
        }

        return $out;
    }

    /**
     * Count of grapheme clusters. Falls back to mb_strlen when intl is unavailable.
     *
     * @return int
     */
    public function graphemeLength(): int
    {
        $value = (string) $this->getRawData();
        if (function_exists('grapheme_strlen')) {
            return (int) grapheme_strlen($value);
        }

        return mb_strlen($value, 'UTF-8');
    }

    /**
     * Invoke a callback once per character. The callback return value is ignored.
     *
     * @param callable(string, int): void $fn
     *
     * @return static Current instance for chaining.
     */
    public function eachCharacter(callable $fn): static
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $fn(mb_substr($value, $i, 1, 'UTF-8'), $i);
        }

        return $this;
    }

    /**
     * Invoke a callback once per line. The callback return value is ignored.
     *
     * @param callable(string, int): void $fn
     *
     * @return static Current instance for chaining.
     */
    public function eachLine(callable $fn): static
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];
        foreach ($lines as $idx => $line) {
            $fn($line, $idx);
        }

        return $this;
    }

    /**
     * Convert to Train-Case (hyphenated, each word capitalized).
     *
     * @return self
     */
    public function toTrainCase(): self
    {
        $value = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', (string) $this->getRawData());
        $value = (string) preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', ' ', $value);
        $words = preg_split('/\s+/u', trim($value)) ?: [];
        $words = array_map(static fn(string $w): string => mb_convert_case($w, MB_CASE_TITLE, 'UTF-8'), $words);

        return new self(implode('-', array_filter($words, static fn($w) => $w !== '')));
    }

    /**
     * Convert to dot.case (lowercase tokens joined by dots).
     *
     * @return self
     */
    public function toDotCase(): self
    {
        $expanded = (string) preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', '_', (string) $this->getRawData());
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $expanded) ?: [];
        $tokens = array_values(array_filter($tokens, static fn($t) => $t !== ''));

        return new self(mb_strtolower(implode('.', $tokens), 'UTF-8'));
    }

    /**
     * Convert to path/case (lowercase tokens joined by forward slashes).
     *
     * @return self
     */
    public function toPathCase(): self
    {
        $expanded = (string) preg_replace('/(?<=\p{Ll})(?=\p{Lu})/u', '_', (string) $this->getRawData());
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $expanded) ?: [];
        $tokens = array_values(array_filter($tokens, static fn($t) => $t !== ''));

        return new self(mb_strtolower(implode('/', $tokens), 'UTF-8'));
    }

    /**
     * Convert to Sentence case — only the first character is uppercase.
     *
     * @return self
     */
    public function toSentenceCase(): self
    {
        $value = mb_strtolower((string) $this->getRawData(), 'UTF-8');
        if ($value === '') {
            return new self('');
        }
        $first = mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8');

        return new self($first . mb_substr($value, 1, null, 'UTF-8'));
    }

    /**
     * Capitalize the first letter of every word (Unicode-aware).
     *
     * @return self
     */
    public function capitalizeEachWord(): self
    {
        return new self(mb_convert_case((string) $this->getRawData(), MB_CASE_TITLE, 'UTF-8'));
    }

    /**
     * Left-pad the string to at least the given character length.
     *
     * @param int $length Final target length.
     * @param string $pad Pad character/sequence.
     *
     * @return self
     */
    public function padLeft(int $length, string $pad = ' '): self
    {
        $value = (string) $this->getRawData();
        $cur = mb_strlen($value, 'UTF-8');
        if ($cur >= $length || $pad === '') {
            return new self($value);
        }
        $needed = $length - $cur;
        $padLen = max(1, mb_strlen($pad, 'UTF-8'));
        $repeat = str_repeat($pad, (int) ceil($needed / $padLen));

        return new self(mb_substr($repeat, 0, $needed, 'UTF-8') . $value);
    }

    /**
     * Right-pad the string to at least the given character length.
     *
     * @param int $length Final target length.
     * @param string $pad Pad character/sequence.
     *
     * @return self
     */
    public function padRight(int $length, string $pad = ' '): self
    {
        $value = (string) $this->getRawData();
        $cur = mb_strlen($value, 'UTF-8');
        if ($cur >= $length || $pad === '') {
            return new self($value);
        }
        $needed = $length - $cur;
        $padLen = max(1, mb_strlen($pad, 'UTF-8'));
        $repeat = str_repeat($pad, (int) ceil($needed / $padLen));

        return new self($value . mb_substr($repeat, 0, $needed, 'UTF-8'));
    }

    /**
     * Wrap the string with the given prefix and suffix.
     *
     * @param string $before Leading marker.
     * @param string $after  Trailing marker. Defaults to $before for symmetric wrapping.
     *
     * @return self
     */
    public function wrapWith(string $before, string $after = ''): self
    {
        if ($after === '') {
            $after = $before;
        }

        return new self($before . (string) $this->getRawData() . $after);
    }

    /**
     * Surround the string with a single quote character.
     *
     * @param string $quote Quote character.
     *
     * @return self
     */
    public function quote(string $quote = '"'): self
    {
        return new self($quote . (string) $this->getRawData() . $quote);
    }

    /**
     * Strip matching surrounding quote characters, if present.
     *
     * @param string $quotes Candidate quote characters (each must match on both ends).
     *
     * @return self
     */
    public function unquote(string $quotes = "\"'"): self
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        if ($len < 2) {
            return new self($value);
        }
        $first = mb_substr($value, 0, 1, 'UTF-8');
        $last = mb_substr($value, -1, 1, 'UTF-8');
        if ($first === $last && mb_strpos($quotes, $first, 0, 'UTF-8') !== false) {
            return new self(mb_substr($value, 1, $len - 2, 'UTF-8'));
        }

        return new self($value);
    }

    /**
     * Surround with the given bracket pair.
     *
     * @param string $open  Opening bracket.
     * @param string $close Closing bracket.
     *
     * @return self
     */
    public function bracket(string $open = '[', string $close = ']'): self
    {
        return new self($open . (string) $this->getRawData() . $close);
    }

    /**
     * Trim ASCII punctuation and whitespace from both ends.
     *
     * @return self
     */
    public function trimPunctuation(): self
    {
        return new self(trim((string) $this->getRawData(), " \t\n\r\0\x0B!\"#$%&'()*+,-./:;<=>?@[\\]^_`{|}~"));
    }

    /**
     * Trim single, double, and back-tick quote characters from both ends.
     *
     * @return self
     */
    public function trimQuotes(): self
    {
        return new self(trim((string) $this->getRawData(), "\"'`"));
    }

    /**
     * Collapse runs of the same character into a single occurrence.
     *
     * @param string $char Character to squeeze (default: space).
     *
     * @return self
     */
    public function squeeze(string $char = ' '): self
    {
        if ($char === '') {
            return new self((string) $this->getRawData());
        }
        $pattern = '/' . preg_quote($char, '/') . '+/u';

        return new self((string) preg_replace($pattern, $char, (string) $this->getRawData()));
    }

    /**
     * Remove every whitespace character.
     *
     * @return self
     */
    public function deflate(): self
    {
        return new self((string) preg_replace('/\s+/u', '', (string) $this->getRawData()));
    }

    /**
     * Split the string into two parts at the given character index.
     *
     * @param int $position Zero-based character index.
     *
     * @return array{0: string, 1: string}
     */
    public function splitAt(int $position): array
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        $position = max(0, min($position, $len));

        return [
            mb_substr($value, 0, $position, 'UTF-8'),
            mb_substr($value, $position, null, 'UTF-8'),
        ];
    }

    /**
     * Break the string into fixed-size chunks (character-based).
     *
     * @param int $size Chunk size in characters. Values < 1 are treated as 1.
     *
     * @return string[]
     */
    public function splitEvery(int $size): array
    {
        $size = max(1, $size);
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        $out = [];
        for ($i = 0; $i < $len; $i += $size) {
            $out[] = mb_substr($value, $i, $size, 'UTF-8');
        }

        return $out;
    }

    /**
     * Split the string in two at the first occurrence of the separator.
     *
     * When the separator is missing, returns [original, ''].
     *
     * @param string $separator Separator to look for.
     *
     * @return array{0: string, 1: string}
     */
    public function splitOnce(string $separator): array
    {
        $value = (string) $this->getRawData();
        if ($separator === '') {
            return [$value, ''];
        }
        $pos = mb_strpos($value, $separator, 0, 'UTF-8');
        if ($pos === false) {
            return [$value, ''];
        }

        return [
            mb_substr($value, 0, $pos, 'UTF-8'),
            mb_substr($value, $pos + mb_strlen($separator, 'UTF-8'), null, 'UTF-8'),
        ];
    }

    /**
     * Split by separator, keeping only up to $limit splits counted from the right.
     *
     * @param string $separator Separator string.
     * @param int $limit Maximum splits to produce from the right; -1 for unlimited.
     *
     * @return string[]
     */
    public function rsplit(string $separator, int $limit = -1): array
    {
        $value = (string) $this->getRawData();
        if ($separator === '' || $limit === 0) {
            return [$value];
        }
        $parts = explode($separator, $value);
        if ($limit < 0 || count($parts) <= $limit) {
            return $parts;
        }
        $tail = array_slice($parts, -$limit, $limit);
        $head = implode($separator, array_slice($parts, 0, count($parts) - $limit));

        return array_merge([$head], $tail);
    }

    /**
     * Non-empty tokens separated by whitespace.
     *
     * @return string[]
     */
    public function words(): array
    {
        $tokens = preg_split('/\s+/u', trim((string) $this->getRawData())) ?: [];

        return array_values(array_filter($tokens, static fn($t) => $t !== ''));
    }

    /**
     * Lines of the string (line terminators are stripped).
     *
     * @return string[]
     */
    public function lines(): array
    {
        return preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];
    }

    /**
     * Apply the ROT13 cipher to ASCII letters.
     *
     * @return self
     */
    public function toRot13(): self
    {
        return new self(str_rot13((string) $this->getRawData()));
    }

    /**
     * Apply a repeating-key XOR cipher against the given key.
     *
     * The transformation is symmetric: applying it twice with the same key
     * restores the original bytes.
     *
     * @param string $key Non-empty key.
     *
     * @throws InvalidArgumentException When the key is empty.
     *
     * @return self
     */
    public function xorCipher(string $key): self
    {
        if ($key === '') {
            throw new InvalidArgumentException('XOR key must not be empty.');
        }
        $value = (string) $this->getRawData();
        $keyLen = strlen($key);
        $len = strlen($value);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= chr(ord($value[$i]) ^ ord($key[$i % $keyLen]));
        }

        return new self($out);
    }

    /**
     * Apply the Atbash cipher (A↔Z, B↔Y, ...) to ASCII letters.
     *
     * @return self
     */
    public function atbashCipher(): self
    {
        $value = (string) $this->getRawData();
        $len = strlen($value);
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $o = ord($value[$i]);
            if ($o >= 0x41 && $o <= 0x5A) {
                $out .= chr(0x5A - ($o - 0x41));
            } elseif ($o >= 0x61 && $o <= 0x7A) {
                $out .= chr(0x7A - ($o - 0x61));
            } else {
                $out .= $value[$i];
            }
        }

        return new self($out);
    }

    /**
     * Encode ASCII letters, digits, and common punctuation to International Morse code.
     *
     * Unknown characters are dropped. Letters are separated by single spaces and
     * words by " / ".
     *
     * @return self
     */
    public function morseEncode(): self
    {
        $table = self::morseTable();
        $value = strtoupper((string) $this->getRawData());
        $words = preg_split('/\s+/u', trim($value)) ?: [];
        $encodedWords = [];
        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            $codes = [];
            $len = strlen($word);
            for ($i = 0; $i < $len; $i++) {
                $c = $word[$i];
                if (isset($table[$c])) {
                    $codes[] = $table[$c];
                }
            }
            if ($codes !== []) {
                $encodedWords[] = implode(' ', $codes);
            }
        }

        return new self(implode(' / ', $encodedWords));
    }

    /**
     * Decode International Morse code back to ASCII text.
     *
     * Words must be separated by " / " and letters by single spaces.
     *
     * @return self
     */
    public function morseDecode(): self
    {
        $reverse = array_flip(self::morseTable());
        $words = explode(' / ', trim((string) $this->getRawData()));
        $decoded = [];
        foreach ($words as $word) {
            $letters = preg_split('/\s+/', trim($word)) ?: [];
            $buf = '';
            foreach ($letters as $code) {
                if ($code !== '' && isset($reverse[$code])) {
                    $buf .= $reverse[$code];
                }
            }
            $decoded[] = $buf;
        }

        return new self(implode(' ', $decoded));
    }

    /**
     * Internal Morse code translation table keyed by uppercase letter/digit/punctuation.
     *
     * @return array<string,string>
     */
    private static function morseTable(): array
    {
        return [
            'A' => '.-',
            'B' => '-...',
            'C' => '-.-.',
            'D' => '-..',
            'E' => '.',
            'F' => '..-.',
            'G' => '--.',
            'H' => '....',
            'I' => '..',
            'J' => '.---',
            'K' => '-.-',
            'L' => '.-..',
            'M' => '--',
            'N' => '-.',
            'O' => '---',
            'P' => '.--.',
            'Q' => '--.-',
            'R' => '.-.',
            'S' => '...',
            'T' => '-',
            'U' => '..-',
            'V' => '...-',
            'W' => '.--',
            'X' => '-..-',
            'Y' => '-.--',
            'Z' => '--..',
            '0' => '-----',
            '1' => '.----',
            '2' => '..---',
            '3' => '...--',
            '4' => '....-',
            '5' => '.....',
            '6' => '-....',
            '7' => '--...',
            '8' => '---..',
            '9' => '----.',
            '.' => '.-.-.-',
            ',' => '--..--',
            '?' => '..--..',
            "'" => '.----.',
            '!' => '-.-.--',
            '/' => '-..-.',
            '&' => '.-...',
            ':' => '---...',
            ';' => '-.-.-.',
            '=' => '-...-',
            '+' => '.-.-.',
            '-' => '-....-',
            '_' => '..--.-',
            '"' => '.-..-.',
            '@' => '.--.-.',
        ];
    }

    /**
     * Translate each ASCII letter/digit to its NATO phonetic word.
     *
     * Unknown characters are preserved verbatim.
     *
     * @param string $separator Separator between words.
     *
     * @return self
     */
    public function nato(string $separator = ' '): self
    {
        static $nato = [
        'A' => 'Alpha',
        'B' => 'Bravo',
        'C' => 'Charlie',
        'D' => 'Delta',
        'E' => 'Echo',
        'F' => 'Foxtrot',
        'G' => 'Golf',
        'H' => 'Hotel',
        'I' => 'India',
        'J' => 'Juliett',
        'K' => 'Kilo',
        'L' => 'Lima',
        'M' => 'Mike',
        'N' => 'November',
        'O' => 'Oscar',
        'P' => 'Papa',
        'Q' => 'Quebec',
        'R' => 'Romeo',
        'S' => 'Sierra',
        'T' => 'Tango',
        'U' => 'Uniform',
        'V' => 'Victor',
        'W' => 'Whiskey',
        'X' => 'Xray',
        'Y' => 'Yankee',
        'Z' => 'Zulu',
        '0' => 'Zero',
        '1' => 'One',
        '2' => 'Two',
        '3' => 'Three',
        '4' => 'Four',
        '5' => 'Five',
        '6' => 'Six',
        '7' => 'Seven',
        '8' => 'Eight',
        '9' => 'Nine',
        ];
        $value = strtoupper((string) $this->getRawData());
        $out = [];
        $len = strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $c = $value[$i];
            $out[] = $nato[$c] ?? $c;
        }

        return new self(implode($separator, $out));
    }

    /**
     * Render each byte as an 8-digit binary string.
     *
     * @param string $separator Separator between byte groups.
     *
     * @return self
     */
    public function binaryRepresentation(string $separator = ' '): self
    {
        $value = (string) $this->getRawData();
        $len = strlen($value);
        $out = [];
        for ($i = 0; $i < $len; $i++) {
            $out[] = str_pad(decbin(ord($value[$i])), 8, '0', STR_PAD_LEFT);
        }

        return new self(implode($separator, $out));
    }

    /**
     * Render each byte as a 3-digit octal string.
     *
     * @param string $separator Separator between byte groups.
     *
     * @return self
     */
    public function octalRepresentation(string $separator = ' '): self
    {
        $value = (string) $this->getRawData();
        $len = strlen($value);
        $out = [];
        for ($i = 0; $i < $len; $i++) {
            $out[] = str_pad(decoct(ord($value[$i])), 3, '0', STR_PAD_LEFT);
        }

        return new self(implode($separator, $out));
    }

    /**
     * Escape HTML special characters using htmlspecialchars semantics.
     *
     * @return self
     */
    public function htmlEscape(): self
    {
        return new self(htmlspecialchars((string) $this->getRawData(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    /**
     * Undo htmlspecialchars-style escaping.
     *
     * @return self
     */
    public function htmlUnescape(): self
    {
        return new self(htmlspecialchars_decode((string) $this->getRawData(), ENT_QUOTES));
    }

    /**
     * Sum of the numeric value of every decimal digit in the string.
     *
     * @return int
     */
    public function sumDigits(): int
    {
        $value = (string) $this->getRawData();
        $len = strlen($value);
        $sum = 0;
        for ($i = 0; $i < $len; $i++) {
            $c = $value[$i];
            if ($c >= '0' && $c <= '9') {
                $sum += (int) $c;
            }
        }

        return $sum;
    }

    /**
     * Average Unicode code point. Returns 0.0 for empty strings.
     *
     * @return float
     */
    public function averageCharCode(): float
    {
        $cps = $this->codePoints();
        if ($cps === []) {
            return 0.0;
        }

        return array_sum($cps) / count($cps);
    }

    /**
     * Smallest Unicode code point in the string. Returns 0 when empty.
     *
     * @return int
     */
    public function minCharCode(): int
    {
        $cps = $this->codePoints();

        return $cps === [] ? 0 : (int) min($cps);
    }

    /**
     * Largest Unicode code point in the string. Returns 0 when empty.
     *
     * @return int
     */
    public function maxCharCode(): int
    {
        $cps = $this->codePoints();

        return $cps === [] ? 0 : (int) max($cps);
    }

    /**
     * Sum of byte values.
     *
     * @return int
     */
    public function asciiSum(): int
    {
        $value = (string) $this->getRawData();
        $len = strlen($value);
        $sum = 0;
        for ($i = 0; $i < $len; $i++) {
            $sum += ord($value[$i]);
        }

        return $sum;
    }

    /**
     * Sum of alphabetic positions for ASCII letters (A/a = 1, Z/z = 26).
     *
     * @return int
     */
    public function letterPositionSum(): int
    {
        $value = (string) $this->getRawData();
        $len = strlen($value);
        $sum = 0;
        for ($i = 0; $i < $len; $i++) {
            $o = ord($value[$i]);
            if ($o >= 0x41 && $o <= 0x5A) {
                $sum += $o - 0x40;
            } elseif ($o >= 0x61 && $o <= 0x7A) {
                $sum += $o - 0x60;
            }
        }

        return $sum;
    }

    /**
     * Byte offsets of every occurrence of the needle.
     *
     * @param string $needle Substring to search for.
     * @param bool $ignoreCase Perform a case-insensitive search.
     *
     * @return int[]
     */
    public function findAllPositions(string $needle, bool $ignoreCase = false): array
    {
        if ($needle === '') {
            return [];
        }
        $value = (string) $this->getRawData();
        $find = $ignoreCase ? 'mb_stripos' : 'mb_strpos';
        $positions = [];
        $offset = 0;
        while (($pos = $find($value, $needle, $offset, 'UTF-8')) !== false) {
            $positions[] = (int) $pos;
            $offset = (int) $pos + 1;
        }

        return $positions;
    }

    /**
     * Count occurrences of the needle.
     *
     * @param string $needle Substring to count.
     * @param bool $ignoreCase Perform a case-insensitive count.
     *
     * @return int
     */
    public function occurrences(string $needle, bool $ignoreCase = false): int
    {
        if ($needle === '') {
            return 0;
        }
        $haystack = $ignoreCase ? mb_strtolower((string) $this->getRawData(), 'UTF-8') : (string) $this->getRawData();
        $needle = $ignoreCase ? mb_strtolower($needle, 'UTF-8') : $needle;

        return mb_substr_count($haystack, $needle, 'UTF-8');
    }

    /**
     * Position of the Nth occurrence of the needle (1-indexed).
     *
     * @param string $needle Substring to find.
     * @param int $n Occurrence number, starting at 1.
     *
     * @return int|null Byte offset, or null when fewer occurrences exist.
     */
    public function nthOccurrence(string $needle, int $n): ?int
    {
        if ($needle === '' || $n < 1) {
            return null;
        }
        $positions = $this->findAllPositions($needle);

        return $positions[$n - 1] ?? null;
    }

    /**
     * True when the string starts with any of the given needles (case-insensitive).
     *
     * @param string[] $items Candidates.
     *
     * @return bool
     */
    public function startsWithAnyIgnoreCase(array $items): bool
    {
        $value = mb_strtolower((string) $this->getRawData(), 'UTF-8');
        foreach ($items as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }
            if (mb_strpos($value, mb_strtolower($item, 'UTF-8'), 0, 'UTF-8') === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the string ends with any of the given needles (case-insensitive).
     *
     * @param string[] $items Candidates.
     *
     * @return bool
     */
    public function endsWithAnyIgnoreCase(array $items): bool
    {
        if (!self::isMultibyteExtensionLoaded()) {
            $lower = strtolower((string) $this->getRawData());
            $len = strlen($lower);
            foreach ($items as $item) {
                if (!is_string($item) || $item === '') {
                    continue;
                }
                $needle = strtolower($item);
                $nl = strlen($needle);
                if ($nl <= $len && substr($lower, -$nl, null) === $needle) {
                    return true;
                }
            }

            return false;
        }

        $lower = mb_strtolower((string) $this->getRawData(), 'UTF-8');
        $len = mb_strlen($lower, 'UTF-8');
        foreach ($items as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }
            $needle = mb_strtolower($item, 'UTF-8');
            $nl = mb_strlen($needle, 'UTF-8');
            if ($nl <= $len && mb_substr($lower, -$nl, null, 'UTF-8') === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * True when the string contains all given needles (case-insensitive).
     *
     * @param string[] $items Required substrings.
     *
     * @return bool
     */
    public function containsAllIgnoreCase(array $items): bool
    {
        if ($items === []) {
            return true;
        }
        $lower = mb_strtolower((string) $this->getRawData(), 'UTF-8');
        foreach ($items as $item) {
            if (!is_string($item) || $item === '') {
                continue;
            }
            if (mb_strpos($lower, mb_strtolower($item, 'UTF-8'), 0, 'UTF-8') === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether the needle starts exactly at the given character position.
     *
     * @param int $position Zero-based character position.
     * @param string $needle Substring to check.
     *
     * @return bool
     */
    public function hasSubstringAt(int $position, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $slice = mb_substr((string) $this->getRawData(), $position, mb_strlen($needle, 'UTF-8'), 'UTF-8');

        return $slice === $needle;
    }

    /**
     * Map a callback over every character; the callback result replaces the original char.
     *
     * @param callable(string, int): string $fn
     *
     * @return self
     */
    public function mapCharacters(callable $fn): self
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= (string) $fn(mb_substr($value, $i, 1, 'UTF-8'), $i);
        }

        return new self($out);
    }

    /**
     * Keep only characters for which the callback returns a truthy value.
     *
     * @param callable(string, int): bool $fn
     *
     * @return self
     */
    public function filterCharacters(callable $fn): self
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($value, $i, 1, 'UTF-8');
            if ($fn($c, $i)) {
                $out .= $c;
            }
        }

        return new self($out);
    }

    /**
     * Fold every character into an accumulator.
     *
     * @param callable(mixed, string, int): mixed $fn
     * @param mixed $initial Starting value.
     *
     * @return mixed Final accumulator.
     */
    public function reduceCharacters(callable $fn, mixed $initial = null): mixed
    {
        $value = (string) $this->getRawData();
        $len = mb_strlen($value, 'UTF-8');
        $acc = $initial;
        for ($i = 0; $i < $len; $i++) {
            $acc = $fn($acc, mb_substr($value, $i, 1, 'UTF-8'), $i);
        }

        return $acc;
    }

    /**
     * Remove every character that appears in $chars.
     *
     * @param string $chars Characters to strip.
     *
     * @return self
     */
    public function removeCharacters(string $chars): self
    {
        if ($chars === '') {
            return new self((string) $this->getRawData());
        }
        $set = [];
        $len = mb_strlen($chars, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $set[mb_substr($chars, $i, 1, 'UTF-8')] = true;
        }

        return $this->filterCharacters(static fn(string $c): bool => !isset($set[$c]));
    }

    /**
     * Keep only characters that appear in $chars.
     *
     * @param string $chars Characters to keep.
     *
     * @return self
     */
    public function keepCharacters(string $chars): self
    {
        if ($chars === '') {
            return new self('');
        }
        $set = [];
        $len = mb_strlen($chars, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $set[mb_substr($chars, $i, 1, 'UTF-8')] = true;
        }

        return $this->filterCharacters(static fn(string $c): bool => isset($set[$c]));
    }

    /**
     * Keep only Unicode letters (L* category).
     *
     * @return self
     */
    public function keepAlpha(): self
    {
        return new self((string) preg_replace('/[^\p{L}]+/u', '', (string) $this->getRawData()));
    }

    /**
     * Keep only decimal digits.
     *
     * @return self
     */
    public function keepDigits(): self
    {
        return new self((string) preg_replace('/[^0-9]+/u', '', (string) $this->getRawData()));
    }

    /**
     * Remove every decimal digit.
     *
     * @return self
     */
    public function removeDigits(): self
    {
        return new self((string) preg_replace('/[0-9]+/u', '', (string) $this->getRawData()));
    }

    /**
     * Remove every Unicode letter.
     *
     * @return self
     */
    public function removeLetters(): self
    {
        return new self((string) preg_replace('/\p{L}+/u', '', (string) $this->getRawData()));
    }

    /**
     * Swap two individual characters wherever they appear.
     *
     * @param string $a First character (must be 1 UTF-8 character).
     * @param string $b Second character (must be 1 UTF-8 character).
     *
     * @return self
     */
    public function swapCharacters(string $a, string $b): self
    {
        if ($a === '' || $b === '' || $a === $b) {
            return new self((string) $this->getRawData());
        }
        $placeholder = "\x00\x01SWAP\x01\x00";
        $result = str_replace($a, $placeholder, (string) $this->getRawData());
        $result = str_replace($b, $a, $result);
        $result = str_replace($placeholder, $b, $result);

        return new self($result);
    }

    /**
     * True when the string consists entirely of ASCII vowels.
     *
     * @return bool
     */
    public function isOnlyVowels(): bool
    {
        $value = (string) $this->getRawData();

        return $value !== '' && preg_match('/^[aeiouAEIOU]+$/u', $value) === 1;
    }

    /**
     * True when the string consists entirely of ASCII consonants.
     *
     * @return bool
     */
    public function isOnlyConsonants(): bool
    {
        $value = (string) $this->getRawData();

        return $value !== '' && preg_match('/^[bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ]+$/u', $value) === 1;
    }

    /**
     * True when the string parses as a calendar date via strtotime().
     *
     * A date separator (-, /, ., :, space) or an alphabetic month token is required
     * so that bare integers are not classified as dates.
     *
     * @return bool
     */
    public function isDate(): bool
    {
        $value = trim((string) $this->getRawData());
        if ($value === '') {
            return false;
        }
        if (preg_match('/[-\/.:\s]/', $value) !== 1 && preg_match('/[A-Za-z]/', $value) !== 1) {
            return false;
        }

        return strtotime($value) !== false;
    }

    /**
     * True when the string matches a 24-hour time (HH:MM or HH:MM:SS).
     *
     * @return bool
     */
    public function isTime(): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', (string) $this->getRawData()) === 1;
    }

    /**
     * True when the string is a valid credit-card number (Luhn check, 12-19 digits).
     *
     * Non-digit characters are ignored before the check.
     *
     * @return bool
     */
    public function isCreditCard(): bool
    {
        $digits = (string) preg_replace('/\D+/', '', (string) $this->getRawData());
        $len = strlen($digits);
        if ($len < 12 || $len > 19) {
            return false;
        }
        $sum = 0;
        $alt = false;
        for ($i = $len - 1; $i >= 0; $i--) {
            $n = (int) $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }

        return ($sum % 10) === 0;
    }

    /**
     * True when the string is a kebab-case slug (lowercase letters, digits, hyphens).
     *
     * @return bool
     */
    public function isSlug(): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $this->getRawData()) === 1;
    }

    /**
     * Translate ASCII words to Pig Latin. Non-letter characters are preserved.
     *
     * @return self
     */
    public function pigLatin(): self
    {
        $result = preg_replace_callback(
            '/\b([A-Za-z]+)\b/',
            static function (array $m): string {
                $word = $m[1];
                $firstChar = $word[0];
                $isUpper = ctype_upper($firstChar);
                $lower = strtolower($word);
                if (strpbrk($lower[0], 'aeiou') !== false) {
                    $out = $lower . 'way';
                } else {
                    $head = '';
                    $i = 0;
                    $len = strlen($lower);
                    while ($i < $len && strpbrk($lower[$i], 'aeiou') === false) {
                        $head .= $lower[$i];
                        $i++;
                    }
                    $out = substr($lower, $i) . $head . 'ay';
                }

                return $isUpper ? ucfirst($out) : $out;
            },
            (string) $this->getRawData()
        );

        return new self((string) $result);
    }

    /**
     * Double every ASCII vowel in place (a→aa, e→ee, ...).
     *
     * @return self
     */
    public function doubleVowels(): self
    {
        return new self((string) preg_replace('/([aeiouAEIOU])/', '$1$1', (string) $this->getRawData()));
    }

    /**
     * Double every ASCII consonant in place.
     *
     * @return self
     */
    public function doubleConsonants(): self
    {
        return new self((string) preg_replace('/([bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ])/', '$1$1', (string) $this->getRawData()));
    }

    /**
     * Reverse the order of lines while keeping each line intact.
     *
     * @return self
     */
    public function reverseLines(): self
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];

        return new self(implode("\n", array_reverse($lines)));
    }

    /**
     * Reverse every word in place, keeping word order unchanged.
     *
     * @return self
     */
    public function reverseEachWord(): self
    {
        $result = preg_replace_callback(
            '/\S+/u',
            static function (array $m): string {
                $chars = preg_split('//u', $m[0], -1, PREG_SPLIT_NO_EMPTY) ?: [];

                return implode('', array_reverse($chars));
            },
            (string) $this->getRawData()
        );

        return new self((string) $result);
    }

    /**
     * Randomly reorder the whitespace-separated words.
     *
     * @return self
     */
    public function shuffleWords(): self
    {
        $words = $this->words();
        if ($words === []) {
            return new self((string) $this->getRawData());
        }
        shuffle($words);

        return new self(implode(' ', $words));
    }

    /**
     * Directory portion of a path-like string.
     *
     * @return self
     */
    public function dirname(): self
    {
        return new self(dirname((string) $this->getRawData()));
    }

    /**
     * Base-name portion of a path-like string.
     *
     * @param string $suffix Optional suffix to strip from the base name.
     *
     * @return self
     */
    public function basename(string $suffix = ''): self
    {
        return new self(basename((string) $this->getRawData(), $suffix));
    }

    /**
     * File extension (without the leading dot). Empty string when missing.
     *
     * @return self
     */
    public function extension(): self
    {
        $info = pathinfo((string) $this->getRawData());

        return new self($info['extension'] ?? '');
    }

    /**
     * Replace the current file extension with the given one.
     *
     * @param string $ext New extension, with or without a leading dot.
     *
     * @return self
     */
    public function changeExtension(string $ext): self
    {
        $value = (string) $this->getRawData();
        $ext = ltrim($ext, '.');
        $lastDot = strrpos($value, '.');
        $lastSlash = max((int) strrpos($value, '/'), (int) strrpos($value, '\\'));
        if ($lastDot !== false && $lastDot > $lastSlash) {
            $value = substr($value, 0, $lastDot);
        }

        return new self($ext === '' ? $value : $value . '.' . $ext);
    }

    /**
     * Ensure the string ends with a single forward slash.
     *
     * @return self
     */
    public function ensureTrailingSlash(): self
    {
        $value = (string) $this->getRawData();
        if ($value === '' || substr($value, -1) === '/') {
            return new self($value);
        }

        return new self($value . '/');
    }

    /**
     * Strip any trailing forward/back slashes.
     *
     * @return self
     */
    public function stripTrailingSlash(): self
    {
        return new self(rtrim((string) $this->getRawData(), "/\\"));
    }

    /**
     * Jaro similarity against another string (0.0 – 1.0).
     *
     * @param string $other Comparison string.
     *
     * @return float
     */
    public function jaroSimilarity(string $other): float
    {
        $s1 = (string) $this->getRawData();
        $s2 = $other;
        if ($s1 === '' && $s2 === '') {
            return 1.0;
        }
        if ($s1 === '' || $s2 === '') {
            return 0.0;
        }
        $a = preg_split('//u', $s1, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('//u', $s2, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $la = count($a);
        $lb = count($b);
        $window = (int) max(0, floor(max($la, $lb) / 2) - 1);
        $matchA = array_fill(0, $la, false);
        $matchB = array_fill(0, $lb, false);
        $matches = 0;
        for ($i = 0; $i < $la; $i++) {
            $start = max(0, $i - $window);
            $end = min($i + $window + 1, $lb);
            for ($j = $start; $j < $end; $j++) {
                if (!$matchB[$j] && $a[$i] === $b[$j]) {
                    $matchA[$i] = true;
                    $matchB[$j] = true;
                    $matches++;
                    break;
                }
            }
        }
        if ($matches === 0) {
            return 0.0;
        }
        $k = 0;
        $transpositions = 0;
        for ($i = 0; $i < $la; $i++) {
            if (!$matchA[$i]) {
                continue;
            }
            while (!$matchB[$k]) {
                $k++;
            }
            if ($a[$i] !== $b[$k]) {
                $transpositions++;
            }
            $k++;
        }
        $t = $transpositions / 2;

        return (($matches / $la) + ($matches / $lb) + (($matches - $t) / $matches)) / 3;
    }

    /**
     * Jaro-Winkler similarity: Jaro + prefix bonus (0.0 – 1.0).
     *
     * @param string $other Comparison string.
     * @param float $p Scaling factor, capped to 0.25. Default 0.1.
     * @param int $maxPrefix Maximum prefix length considered. Default 4.
     *
     * @return float
     */
    public function jaroWinklerSimilarity(string $other, float $p = 0.1, int $maxPrefix = 4): float
    {
        $jaro = $this->jaroSimilarity($other);
        $p = max(0.0, min($p, 0.25));
        $a = preg_split('//u', (string) $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('//u', $other, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $prefix = 0;
        $limit = min($maxPrefix, count($a), count($b));
        for ($i = 0; $i < $limit; $i++) {
            if ($a[$i] !== $b[$i]) {
                break;
            }
            $prefix++;
        }

        return $jaro + ($prefix * $p * (1 - $jaro));
    }

    /**
     * Cosine similarity of character bag-of-features (0.0 – 1.0).
     *
     * @param string $other Comparison string.
     *
     * @return float
     */
    public function cosineSimilarity(string $other): float
    {
        $a = count_chars((string) $this->getRawData(), 1);
        $b = count_chars($other, 1);
        if ($a === [] || $b === []) {
            return 0.0;
        }
        $dot = 0;
        $na = 0;
        $nb = 0;
        foreach ($a as $v) {
            $na += $v * $v;
        }
        foreach ($b as $v) {
            $nb += $v * $v;
        }
        foreach ($a as $k => $v) {
            if (isset($b[$k])) {
                $dot += $v * $b[$k];
            }
        }
        if ($na === 0 || $nb === 0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }

    /**
     * Longest common subsequence length with another string.
     *
     * @param string $other Comparison string.
     *
     * @return int
     */
    public function lcsLength(string $other): int
    {
        $a = preg_split('//u', (string) $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('//u', $other, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $la = count($a);
        $lb = count($b);
        if ($la === 0 || $lb === 0) {
            return 0;
        }
        $prev = array_fill(0, $lb + 1, 0);
        $curr = array_fill(0, $lb + 1, 0);
        for ($i = 1; $i <= $la; $i++) {
            for ($j = 1; $j <= $lb; $j++) {
                $curr[$j] = $a[$i - 1] === $b[$j - 1]
                    ? $prev[$j - 1] + 1
                    : max($prev[$j], $curr[$j - 1]);
            }
            $prev = $curr;
        }

        return (int) $prev[$lb];
    }

    /**
     * Overlap coefficient of the two character sets (0.0 – 1.0).
     *
     * @param string $other Comparison string.
     *
     * @return float
     */
    public function overlapCoefficient(string $other): float
    {
        $a = array_unique(preg_split('//u', (string) $this->getRawData(), -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $b = array_unique(preg_split('//u', $other, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $la = count($a);
        $lb = count($b);
        if ($la === 0 || $lb === 0) {
            return 0.0;
        }
        $intersect = count(array_intersect($a, $b));

        return $intersect / min($la, $lb);
    }

    /**
     * Replace the value when the trimmed string is blank.
     *
     * @param string $default Replacement for blank strings.
     *
     * @return self
     */
    public function ifBlank(string $default): self
    {
        return trim((string) $this->getRawData()) === ''
            ? new self($default)
            : new self((string) $this->getRawData());
    }

    /**
     * Run a callback only when the condition evaluates to false.
     *
     * @param bool $condition When true, the callback is skipped.
     * @param callable(self): ?self $fn Callback may return a new StringObject.
     *
     * @return self
     */
    public function unless(bool $condition, callable $fn): self
    {
        if ($condition) {
            return new self((string) $this->getRawData());
        }
        $out = $fn(new self((string) $this->getRawData()));
        if ($out instanceof self) {
            return $out;
        }

        return new self((string) $this->getRawData());
    }

    /**
     * Swap two lines identified by 0-based indices.
     *
     * @param int $a First line index.
     * @param int $b Second line index.
     *
     * @return self
     */
    public function swapLines(int $a, int $b): self
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];
        if (!isset($lines[$a], $lines[$b])) {
            return new self((string) $this->getRawData());
        }
        [$lines[$a], $lines[$b]] = [$lines[$b], $lines[$a]];

        return new self(implode("\n", $lines));
    }

    /**
     * Remove duplicate lines, keeping the first occurrence of each.
     *
     * @return self
     */
    public function uniqueLines(): self
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];

        return new self(implode("\n", array_values(array_unique($lines))));
    }

    /**
     * Sort lines alphabetically.
     *
     * @param bool $desc Sort descending when true.
     *
     * @return self
     */
    public function sortLines(bool $desc = false): self
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];
        if ($desc) {
            rsort($lines, SORT_STRING);
        } else {
            sort($lines, SORT_STRING);
        }

        return new self(implode("\n", $lines));
    }

    /**
     * Randomly reorder all lines.
     *
     * @return self
     */
    public function shuffleLines(): self
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];
        shuffle($lines);

        return new self(implode("\n", $lines));
    }

    /**
     * Prepend the given string to every line.
     *
     * @param string $prefix Prefix added to each line.
     *
     * @return self
     */
    public function prependLines(string $prefix): self
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];
        foreach ($lines as &$line) {
            $line = $prefix . $line;
        }
        unset($line);

        return new self(implode("\n", $lines));
    }

    /**
     * Append the given string to every line.
     *
     * @param string $suffix Suffix added to each line.
     *
     * @return self
     */
    public function appendLines(string $suffix): self
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $this->getRawData()) ?: [];
        foreach ($lines as &$line) {
            $line = $line . $suffix;
        }
        unset($line);

        return new self(implode("\n", $lines));
    }

    /**
     * True when the first character is an ASCII digit.
     *
     * @return bool
     */
    public function startsWithDigit(): bool
    {
        $v = (string) $this->getRawData();

        return $v !== '' && ctype_digit($v[0]);
    }

    /**
     * True when the last character is an ASCII digit.
     *
     * @return bool
     */
    public function endsWithDigit(): bool
    {
        $v = (string) $this->getRawData();

        return $v !== '' && ctype_digit($v[strlen($v) - 1]);
    }

    /**
     * Count occurrences of a single (multibyte) character.
     *
     * @param string $char Exactly one UTF-8 character.
     *
     * @return int
     */
    public function countCharacter(string $char): int
    {
        if (mb_strlen($char, 'UTF-8') !== 1) {
            return 0;
        }

        return mb_substr_count((string) $this->getRawData(), $char, 'UTF-8');
    }

    /**
     * The most common character in the string, or null when empty.
     *
     * @return string|null
     */
    public function mostCommonCharacter(): ?string
    {
        $value = (string) $this->getRawData();
        if ($value === '') {
            return null;
        }
        $counts = [];
        $len = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($value, $i, 1, 'UTF-8');
            $counts[$c] = ($counts[$c] ?? 0) + 1;
        }
        arsort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * The least common character in the string, or null when empty.
     *
     * @return string|null
     */
    public function leastCommonCharacter(): ?string
    {
        $value = (string) $this->getRawData();
        if ($value === '') {
            return null;
        }
        $counts = [];
        $len = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($value, $i, 1, 'UTF-8');
            $counts[$c] = ($counts[$c] ?? 0) + 1;
        }
        asort($counts);

        return (string) array_key_first($counts);
    }

    /**
     * Distinct characters in first-seen order.
     *
     * @return string[]
     */
    public function uniqueCharacters(): array
    {
        $value = (string) $this->getRawData();
        $seen = [];
        $out = [];
        $len = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($value, $i, 1, 'UTF-8');
            if (!isset($seen[$c])) {
                $seen[$c] = true;
                $out[] = $c;
            }
        }

        return $out;
    }

    /**
     * Characters that appear more than once, in first-seen order.
     *
     * @return string[]
     */
    public function duplicateCharacters(): array
    {
        $value = (string) $this->getRawData();
        $counts = [];
        $order = [];
        $len = mb_strlen($value, 'UTF-8');
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($value, $i, 1, 'UTF-8');
            if (!isset($counts[$c])) {
                $counts[$c] = 0;
                $order[] = $c;
            }
            $counts[$c]++;
        }

        return array_values(array_filter($order, static fn(string $c): bool => $counts[$c] > 1));
    }

    #endregion
}
