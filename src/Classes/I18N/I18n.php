<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\I18N;

use Exception;
use function array_key_exists;
use function array_merge;
use function count;
use function file_exists;
use function is_array;
use function is_string;
use function str_replace;
use function strtolower;
use function in_array;

/**
 * Internationalization (I18N) Class
 */
class I18n
{
    /**
     * @var string Current language code (e.g. 'en', 'fr', 'zh_cn').
     */
    private string $lang;
    /**
     * @var string Fallback language code used when a key is missing in the current language.
     */
    private string $fallbackLang;
    /**
     * @var string Directory path where language files are stored.
     */
    private string $langDir;
    /**
     * @var array Loaded translations for the current language, structured as ['namespace.key' => 'translation'].
     */
    private array $translations = [];
    /**
     * @var array Loaded translations for the fallback language, used when keys are missing in the current language.
     */
    private array $fallbackTranslations = [];
    /**
     * @var array List of namespaces that have been loaded to prevent redundant loading.
     */
    private array $loadedNamespaces = [];
    /**
     * @var array Pluralization rules for different languages. Each entry maps a language code to a function that determines the plural form based on a count.
     */
    private array $pluralRules = [];
    /**
     * @var callable|null Custom handler for missing translation keys. If set, this function will be called with the missing key and current language when a key is not found.
     */
    private mixed $missingKeyHandler = null;
    /**
     * @var array List of missing translation keys that have been accessed but not found. This can be used for logging or exporting missing keys for translation.
     */
    private array $missingKeys = [];
    /**
     * @var bool Whether to collect missing keys for later retrieval. When enabled, missing keys will be stored in the $missingKeys array for analysis or export.
     */
    private bool $collectMissing = false;

    /**
     * @var array Mapping of common locale aliases to their canonical forms (e.g. 'jp' => 'ja').
     */
    private static array $localeAliases = [
        'jp' => 'ja',
        'cn' => 'zh_cn',
        'tw' => 'zh_tw',
        'ko' => 'ko',
        'kr' => 'ko',
        'us' => 'en',
        'gb' => 'en',
        'uk' => 'en',
        'br' => 'pt_br',
    ];

    /** @var array List of language codes that are written right-to-left (RTL). */
    private static array $rtlLocales = [
        'ar',
        'he',
        'fa',
        'ur',
        'ps',
        'sd',
        'yi',
        'ku',
    ];

    /** @var array<string, string[]> Cache of available languages for different language directories to optimize repeated lookups. */
    private static array $availableLanguagesCache = [];

    /**
     * Constructor
     * 
     * @param string $lang Initial language code (default 'en').
     * @param string $langDir Directory where language files are stored (default is 'languages' subdirectory).
     * @param string $fallbackLang Fallback language code to use when keys are missing (
     */
    public function __construct(string $lang = 'en', string $langDir = __DIR__ . '/languages', string $fallbackLang = 'en')
    {
        $this->langDir = rtrim($langDir, '/');
        $this->fallbackLang = $this->resolveAlias($fallbackLang);
        $this->initPluralRules();
        $this->setLanguage($lang);
    }

    /**
     * Set current language
     * 
     * @param string $lang Language code to set (e.g. 'en', 'fr', 'zh_cn'). This will load the corresponding translation file and reset any loaded namespaces.
     * 
     * @return void
     * @throws Exception
     */
    public function setLanguage(string $lang): void
    {
        $lang = $this->resolveAlias($lang);

        $this->lang = $lang;
        $this->translations = [];
        $this->loadedNamespaces = [];
        $this->loadTranslationFile($lang, $this->translations);

        if ($lang !== $this->fallbackLang) {
            $this->fallbackTranslations = [];
            $this->loadTranslationFile($this->fallbackLang, $this->fallbackTranslations);
        } else {
            $this->fallbackTranslations = $this->translations;
        }
    }

    /**
     * Get current language
     * 
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->lang;
    }

    /**
     * Get the fallback language.
     *
     * @return string The fallback language code.
     */
    public function getFallbackLanguage(): string
    {
        return $this->fallbackLang;
    }

    /**
     * Set the fallback language and load its translations.
     *
     * @param string $lang The fallback language code.
     * @return void
     */
    public function setFallbackLanguage(string $lang): void
    {
        $this->fallbackLang = $this->resolveAlias($lang);

        if ($this->fallbackLang === $this->lang) {
            $this->fallbackTranslations = $this->translations;
            return;
        }

        $this->fallbackTranslations = [];
        $this->loadTranslationFile($this->fallbackLang, $this->fallbackTranslations);
    }

    /**
     * Load a specific namespace (translation file group) for the current and fallback languages.
     *
     * @param string $namespace The namespace to load.
     * @return void
     */
    public function loadNamespace(string $namespace): void
    {
        if (isset($this->loadedNamespaces[$namespace])) {
            return;
        }

        $file = "{$this->langDir}/{$namespace}/{$this->lang}.php";
        if (file_exists($file)) {
            $data = require $file;
            if (is_array($data)) {
                $this->translations[$namespace] = array_merge(
                    $this->translations[$namespace] ?? [],
                    $data
                );
            }
        }

        if ($this->lang !== $this->fallbackLang) {
            $fallbackFile = "{$this->langDir}/{$namespace}/{$this->fallbackLang}.php";
            if (file_exists($fallbackFile)) {
                $data = require $fallbackFile;
                if (is_array($data)) {
                    $this->fallbackTranslations[$namespace] = array_merge(
                        $this->fallbackTranslations[$namespace] ?? [],
                        $data
                    );
                }
            }
        } else {
            $this->fallbackTranslations = $this->translations;
        }

        $this->loadedNamespaces[$namespace] = true;
    }

    /**
     * Translate a key with optional replacements
     * 
     * @param string $key
     * @param array $replacements
     * 
     * @return string
     */
    public function translate(string $key, array $replacements = []): string
    {
        $text = $this->resolve($key, $this->translations);

        if ($text === null) {
            $text = $this->resolve($key, $this->fallbackTranslations);
        }

        if ($text === null) {
            $this->handleMissingKey($key);
            return $this->applyReplacements($key, $replacements);
        }

        if (is_array($text)) {
            return $this->applyReplacements((string) ($text[0] ?? $key), $replacements);
        }

        return $this->applyReplacements($text, $replacements);
    }

    /**
     * Alias for the translate method.
     *
     * @param string $key The translation key.
     * @param array $replacements Optional variable replacements.
     * @return string The translated string.
     */
    public function t(string $key, array $replacements = []): string
    {
        return $this->translate($key, $replacements);
    }

    /**
     * Magic method to allow object to be used as a function for translation
     * 
     * @param string $key
     * @param array $replacements
     * 
     * @return string
     */
    public function __invoke(string $key, array $replacements = []): string
    {
        return $this->translate($key, $replacements);
    }

    /**
     * Retrieve a pluralized translation string.
     *
     * @param string $key The translation key base.
     * @param int $count The count to determine the plural form.
     * @param array $replacements Optional variable replacements.
     * @return string The pluralized and translated string.
     */
    public function plural(string $key, int $count, array $replacements = []): string
    {
        $replacements['count'] = (string) $count;
        $form = $this->getPluralForm($this->lang, $count);
        $candidates = ["{$key}.{$form}", "{$key}.other"];

        foreach ($candidates as $candidate) {
            $text = $this->resolve($candidate, $this->translations);
            if ($text !== null) {
                return $this->applyReplacements($text, $replacements);
            }
        }

        foreach ($candidates as $candidate) {
            $text = $this->resolve($candidate, $this->fallbackTranslations);
            if ($text !== null) {
                return $this->applyReplacements($text, $replacements);
            }
        }

        $this->handleMissingKey($key);

        return $this->applyReplacements($key, $replacements);
    }

    /**
     * Retrieve a translation string based on a count for complex choice formats.
     * Uses pipe (|) separation or explicit brackets like {0}, [1,19], etc.
     *
     * @param string $key The translation key.
     * @param int $count The count for determining the choice.
     * @param array $replacements Optional variable replacements.
     * @return string The chosen and translated string.
     */
    public function choice(string $key, int $count, array $replacements = []): string
    {
        $raw = $this->resolve($key, $this->translations)
            ?? $this->resolve($key, $this->fallbackTranslations);

        if ($raw === null || !is_string($raw)) {
            $this->handleMissingKey($key);
            return $this->applyReplacements($key, $replacements);
        }

        $replacements['count'] = (string) $count;
        $segments = explode('|', $raw);

        foreach ($segments as $segment) {
            $segment = trim($segment);

            if (preg_match('/^\{(\d+)\}\s*(.+)$/', $segment, $m)) {
                if ((int) $m[1] === $count) {
                    return $this->applyReplacements($m[2], $replacements);
                }
                continue;
            }

            if (preg_match('/^\[(\d+),(\d+|\*)\]\s*(.+)$/', $segment, $m)) {
                $from = (int) $m[1];
                $to = $m[2] === '*' ? PHP_INT_MAX : (int) $m[2];
                if ($count >= $from && $count <= $to) {
                    return $this->applyReplacements($m[3], $replacements);
                }
                continue;
            }
        }

        $form = $this->getPluralForm($this->lang, $count);
        $index = $form === 'one' ? 0 : (count($segments) > 1 ? 1 : 0);
        if ($index < count($segments)) {
            return $this->applyReplacements(trim($segments[$index]), $replacements);
        }

        return $this->applyReplacements(trim($segments[0]), $replacements);
    }

    /**
     * Determine if a translation key exists in either the current or fallback language.
     *
     * @param string $key The translation key.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $key): bool
    {
        return $this->resolve($key, $this->translations) !== null
            || $this->resolve($key, $this->fallbackTranslations) !== null;
    }

    /**
     * Get all currently loaded translations.
     *
     * @return array Array of loaded translations.
     */
    public function getAll(): array
    {
        return $this->translations;
    }

    /**
     * Get a list of available languages based on the translation directory.
     *
     * @return array List of available language codes.
     */
    public function getAvailableLanguages(): array
    {
        if (isset(self::$availableLanguagesCache[$this->langDir])) {
            return self::$availableLanguagesCache[$this->langDir];
        }

        $languages = [];
        $files = glob("{$this->langDir}/*.php");

        if ($files === false) {
            return $languages;
        }

        foreach ($files as $file) {
            $languages[] = pathinfo($file, PATHINFO_FILENAME);
        }

        sort($languages);

        self::$availableLanguagesCache[$this->langDir] = $languages;

        return self::$availableLanguagesCache[$this->langDir];
    }

    /**
     * Determine if a language is written right-to-left (RTL).
     *
     * @param string|null $lang The language code to check, or null for current language.
     * @return bool True if RTL, false if LTR.
     */
    public function isRtl(?string $lang = null): bool
    {
        $lang = $lang ?? $this->lang;
        $base = explode('_', strtolower($lang))[0];

        return in_array($base, self::$rtlLocales, true);
    }

    /**
     * Get the text direction for a given language.
     *
     * @param string|null $lang The language code, or null for current language.
     * @return string 'rtl' or 'ltr'.
     */
    public function getDirection(?string $lang = null): string
    {
        return $this->isRtl($lang) ? 'rtl' : 'ltr';
    }

    /**
     * Set a custom callback handler for missing translation keys.
     *
     * @param callable $handler Callable function to handle missing keys.
     * @return void
     */
    public function setMissingKeyHandler(callable $handler): void
    {
        $this->missingKeyHandler = $handler;
    }

    /**
     * Enable or disable the collection of missing translation keys.
     *
     * @param bool $enable True to enable collection.
     * @return void
     */
    public function enableMissingKeyCollection(bool $enable = true): void
    {
        $this->collectMissing = $enable;
    }

    /**
     * Get the array of missing keys that have been collected.
     *
     * @return array List of missing keys.
     */
    public function getMissingKeys(): array
    {
        return array_keys($this->missingKeys);
    }

    /**
     * Clear the list of collected missing keys.
     *
     * @return void
     */
    public function clearMissingKeys(): void
    {
        $this->missingKeys = [];
    }

    /**
     * Merge an explicit array of translations into the currently loaded ones.
     *
     * @param array $translations Associative array of translations to merge.
     * @return void
     */
    public function mergeTranslations(array $translations): void
    {
        $this->translations = $this->arrayMergeDeep($this->translations, $translations);

        if ($this->lang === $this->fallbackLang) {
            $this->fallbackTranslations = $this->translations;
        }
    }

    /**
     * Detect the best language match from an HTTP Accept-Language header.
     *
     * @param string $acceptLanguage The Accept-Language header value.
     * @param array $supported Array of supported language codes.
     * @return string The best matching supported language code, or the first supported as default.
     */
    public static function detectFromHeader(string $acceptLanguage, array $supported): string
    {
        $candidates = self::parseAcceptLanguage($acceptLanguage);

        foreach ($candidates as $candidate) {
            $resolved = self::matchLocale($candidate, $supported);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return $supported[0] ?? 'en';
    }

    /**
     * Parse the Accept-Language header and sort locales by their quality factor (q).
     *
     * @param string $header The header string.
     * @return array Array of locale strings sorted by descending quality.
     */
    private static function parseAcceptLanguage(string $header): array
    {
        $result = [];
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $quality = 1.0;
            if (preg_match('/;q=([\d.]+)/', $part, $m)) {
                $quality = (float) $m[1];
                $part = trim(explode(';', $part)[0]);
            }

            $result[] = ['locale' => strtolower(str_replace('-', '_', $part)), 'quality' => $quality];
        }

        usort($result, fn($a, $b) => $b['quality'] <=> $a['quality']);

        return array_column($result, 'locale');
    }

    /**
     * Match a requested locale against the list of supported locales, checking aliases and base languages.
     *
     * @param string $candidate The requested locale.
     * @param array $supported The supported locales.
     * @return string|null The best matching supported locale, or null if no match.
     */
    private static function matchLocale(string $candidate, array $supported): ?string
    {
        $supportedLower = array_map('strtolower', $supported);

        $alias = self::$localeAliases[$candidate] ?? null;
        if ($alias !== null) {
            $idx = array_search(strtolower($alias), $supportedLower, true);
            if ($idx !== false) {
                return $supported[$idx];
            }
        }

        $idx = array_search($candidate, $supportedLower, true);
        if ($idx !== false) {
            return $supported[$idx];
        }

        $base = explode('_', $candidate)[0];
        foreach ($supportedLower as $i => $s) {
            if (explode('_', $s)[0] === $base) {
                return $supported[$i];
            }
        }

        return null;
    }

    /**
     * Resolve a language code to its alias, if one exists (e.g. 'us' to 'en').
     *
     * @param string $lang The original language code.
     * @return string The alias, or the original code if no alias exists.
     */
    private function resolveAlias(string $lang): string
    {
        $lower = strtolower($lang);

        return self::$localeAliases[$lower] ?? $lang;
    }

    /**
     * Load a translation file and merge its data into the target array.
     *
     * @param string $lang The language code matching the file name.
     * @param array &$target The target array to populate.
     * @return void
     */
    private function loadTranslationFile(string $lang, array &$target): void
    {
        $file = "{$this->langDir}/{$lang}.php";

        if (!file_exists($file)) {
            return;
        }

        $data = require $file;
        if (is_array($data)) {
            $target = array_merge($target, $data);
        }
    }

    /**
     * Retrieve a nested value from a source array using dot notation.
     *
     * @param string $key The dot-separated key (e.g., 'messages.welcome').
     * @param array $source The array to search in.
     * @return mixed The value, or null if missing.
     */
    private function resolve(string $key, array $source): mixed
    {
        if (array_key_exists($key, $source)) {
            return $source[$key];
        }

        if (strpos($key, '.') === false) {
            return null;
        }

        $segments = explode('.', $key);
        $current = $source;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Replace placeholders in a text string.
     * Supports both '{key}' and ':key' formats.
     *
     * @param string $text The text containing placeholders.
     * @param array $replacements Associative array of replacements.
     * @return string The processed text.
     */
    private function applyReplacements(string $text, array $replacements): string
    {
        if (empty($replacements)) {
            return $text;
        }

        foreach ($replacements as $k => $v) {
            $text = str_replace('{' . $k . '}', (string) $v, $text);
            $text = str_replace(':' . $k, (string) $v, $text);
        }

        return $text;
    }

    /**
     * Register a missing key, triggering collection or customized handlers.
     *
     * @param string $key The missing translation key.
     * @return void
     */
    private function handleMissingKey(string $key): void
    {
        if ($this->collectMissing) {
            $this->missingKeys[$key] = true;
        }

        if ($this->missingKeyHandler !== null) {
            ($this->missingKeyHandler)($key, $this->lang);
        }
    }

    /**
     * Deep-merge two associative arrays overriding base entries with new ones.
     *
     * @param array $base The base array.
     * @param array $override The array with overrides.
     * @return array The merged array.
     */
    private function arrayMergeDeep(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->arrayMergeDeep($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Initialize language-specific plural rules.
     * Define how numerical counts map to forms like 'one', 'few', 'many', 'other'.
     *
     * @return void
     */
    private function initPluralRules(): void
    {
        $this->pluralRules = [
            'default' => fn(int $n): string => $n === 1 ? 'one' : 'other',
            'ja' => fn(int $n): string => 'other',
            'ko' => fn(int $n): string => 'other',
            'zh' => fn(int $n): string => 'other',
            'zh_cn' => fn(int $n): string => 'other',
            'zh_tw' => fn(int $n): string => 'other',
            'ru' => function (int $n): string {
                $mod10 = $n % 10;
                $mod100 = $n % 100;
                if ($mod10 === 1 && $mod100 !== 11) {
                    return 'one';
                }
                if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20)) {
                    return 'few';
                }
                return 'many';
            },
            'ar' => function (int $n): string {
                if ($n === 0)
                    return 'zero';
                if ($n === 1)
                    return 'one';
                if ($n === 2)
                    return 'two';
                $mod100 = $n % 100;
                if ($mod100 >= 3 && $mod100 <= 10)
                    return 'few';
                if ($mod100 >= 11 && $mod100 <= 99)
                    return 'many';
                return 'other';
            },
            'fr' => fn(int $n): string => $n === 0 || $n === 1 ? 'one' : 'other',
            'de' => fn(int $n): string => $n === 1 ? 'one' : 'other',
            'es' => fn(int $n): string => $n === 1 ? 'one' : 'other',
            'pt' => fn(int $n): string => $n === 0 || $n === 1 ? 'one' : 'other',
            'pl' => function (int $n): string {
                if ($n === 1)
                    return 'one';
                $mod10 = $n % 10;
                $mod100 = $n % 100;
                if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 10 || $mod100 >= 20))
                    return 'few';
                return 'many';
            },
        ];
    }

    /**
     * Get the descriptive plural form constraint for a language and count.
     *
     * @param string $lang The language code.
     * @param int $count The numerical count.
     * @return string The plural form (e.g. 'one', 'other').
     */
    private function getPluralForm(string $lang, int $count): string
    {
        $base = explode('_', strtolower($lang))[0];
        $rule = $this->pluralRules[$lang] ?? $this->pluralRules[$base] ?? $this->pluralRules['default'];

        return $rule(abs($count));
    }
}
