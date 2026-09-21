<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\I18N\I18n;
use function is_string;
use function in_array;

/**
 * Framework translation gateway.
 *
 * Provides a lightweight static bridge so framework components can
 * consistently resolve localized messages without DI coupling.
 */
class Translator
{
    private static ?I18n $translator = null;
    /** @var string[]|null */
    private static ?array $availableLanguagesCache = null;

    /**
     * Resolve and cache translator instance.
     */
    public static function getInstance(): I18n
    {
        if (self::$translator === null) {
            $defaultLanguage = self::getDefaultLanguage();
            $fallbackLanguage = self::getFallbackLanguage();
            self::$translator = new I18n($defaultLanguage, __DIR__ . '/../../Classes/I18N/languages', $fallbackLanguage);
        }

        return self::$translator;
    }

    /**
     * Translate a key and optionally fall back to a provided default text.
     */
    public static function trans(string $key, array $replacements = [], ?string $default = null): string
    {
        $translated = self::getInstance()->translate($key, $replacements);
        if ($translated === $key && $default !== null) {
            return self::applyReplacements($default, $replacements);
        }

        return $translated;
    }

    /**
     * Set current language explicitly.
     */
    public static function setLanguage(string $language): void
    {
        self::getInstance()->setLanguage($language);
    }

    /**
     * Detect language from Accept-Language header and set it.
     */
    public static function detectAndSetFromAcceptLanguage(?string $acceptLanguage): string
    {
        $instance = self::getInstance();
        if ($acceptLanguage === null || trim($acceptLanguage) === '') {
            return $instance->getLanguage();
        }

        $supported = $instance->getAvailableLanguages();
        $detected = I18n::detectFromHeader($acceptLanguage, $supported);
        $instance->setLanguage($detected);

        return $instance->getLanguage();
    }

    private static function getDefaultLanguage(): string
    {
        $locale = self::getEnvValue(['APP_LOCALE', 'APP_LANG', 'APP_LANGUAGE']);
        if (is_string($locale) && trim($locale) !== '') {
            return self::normalizeLocale((string) $locale);
        }

        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
        if (is_string($acceptLanguage) && trim($acceptLanguage) !== '') {
            $supported = self::getAvailableLanguages();
            $detected = I18n::detectFromHeader($acceptLanguage, $supported);

            return self::normalizeLocale($detected);
        }

        return 'en';
    }

    private static function getFallbackLanguage(): string
    {
        $fallback = self::getEnvValue(['APP_FALLBACK_LOCALE', 'APP_FALLBACK_LANGUAGE']);
        if (!is_string($fallback) || trim($fallback) === '') {
            return 'en';
        }

        return self::normalizeLocale($fallback);
    }

    /**
     * Apply placeholder values in a default text.
     */
    private static function applyReplacements(string $text, array $replacements): string
    {
        if ($replacements === []) {
            return $text;
        }

        foreach ($replacements as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
            $text = str_replace(':' . $key, (string) $value, $text);
        }

        return $text;
    }

    /**
     * Return first non-empty environment value from given keys.
     */
    private static function getEnvValue(array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $_ENV[$key] ?? getenv($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * Normalize locale strings like ko-KR, ko_KR.UTF-8 -> ko.
     */
    private static function normalizeLocale(string $locale): string
    {
        $normalized = strtolower(trim($locale));
        $normalized = str_replace('-', '_', $normalized);
        $normalized = explode('.', $normalized)[0];
        $normalized = explode('@', $normalized)[0];

        if ($normalized === '') {
            return 'en';
        }

        $supported = self::getAvailableLanguages();
        if (in_array($normalized, $supported, true)) {
            return $normalized;
        }

        $base = explode('_', $normalized)[0];
        if (in_array($base, $supported, true)) {
            return $base;
        }

        return $normalized;
    }

    /**
     * Get available language file names in i18n directory.
     */
    private static function getAvailableLanguages(): array
    {
        if (self::$availableLanguagesCache !== null) {
            return self::$availableLanguagesCache;
        }

        $languages = [];
        $files = glob(__DIR__ . '/../../Classes/I18N/languages/*.php');
        if ($files === false) {
            return $languages;
        }

        foreach ($files as $file) {
            $languages[] = pathinfo($file, PATHINFO_FILENAME);
        }

        self::$availableLanguagesCache = $languages;

        return self::$availableLanguagesCache;
    }
}
