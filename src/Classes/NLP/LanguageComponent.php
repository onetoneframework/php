<?php

declare(strict_types=1);

namespace Clover\Classes\NLP\Components;

use function count;
use function in_array;
use function is_string;
use function mb_strtolower;
use function preg_replace;
use function preg_split;
use function trim;

/**
 * Base contract for language-specific NLP components.
 *
 * Each component encapsulates tokenization, sentence splitting, normalization,
 * stop-word removal, stemming, and script detection for one language.
 */
abstract class LanguageComponent
{
    /** Human-readable language name, e.g. "English". */
	abstract public function name(): string;

    /** ISO 639-1 code, e.g. "en", "ko", "ja". */
	abstract public function isoCode(): string;

    /**
     * Tokenize text into meaningful units.
     * Strategy differs per language:
     *   English  → split on whitespace / punctuation
     *   Korean   → space-delimited eojeol groups
     *   Japanese → character / script-boundary segments
     *
     * @return string[]
     */
	abstract public function tokenize(string $text): array;

	/**
	 * Split text into sentences.
	 * 
	 * @return string[]
	 */
	abstract public function splitSentences(string $text): array;

    /**
     * Language-specific normalization (lowercasing, unicode NFC, etc.).
     */
	abstract public function normalize(string $text): string;

	/**
	 * Remove stop words from a token list.
	 * 
	 * @param  string[] $tokens
	 * @return string[]
	 */
	abstract public function removeStopWords(array $tokens): array;

	/**
	 * Stem / lemmatize tokens.
	 * 
	 * @param  string[] $tokens
	 * @return string[]
	 */
	abstract public function stem(array $tokens): array;

    /**
     * Detect script / character classes present in the text.
     * Returns a map of script_name → count_of_chars.
     *
	 * @return array<string, int>
	 */
	abstract public function detectScript(string $text): array;

	/**
	 * @return string[]
	 */
	abstract public function stopWords(): array;

	/**
	 * Generate n-grams from a token list.
	 * 
	 * @param  string[] $tokens
	 * @return array<int, array<int, string>>
	 */
	public function ngrams(array $tokens, int $n = 2): array
	{
		$filteredTokens = $this->filterNonEmptyStrings($tokens);
		if ($n < 1 || count($filteredTokens) < $n) {
			return [];
		}

		$result = [];
		$limit = count($filteredTokens) - $n + 1;
		for ($index = 0; $index < $limit; $index++) {
			$result[] = array_slice($filteredTokens, $index, $n);
		}

		return $result;
	}

    /**
     * Basic POS tagging. Default implementation is a no-op (returns UNKNOWN).
     * Override in each language component with pattern-based or rule-based logic.
     *
	 * @param  string[] $tokens
	 * @return array<string, string>
	 */
	public function posTag(array $tokens): array
	{
		$tags = [];
		foreach ($this->filterNonEmptyStrings($tokens) as $token) {
			$tags[$token] = 'UNKNOWN';
		}

		return $tags;
	}

	/**
	 * @return string[]
	 */
	public function supportedFeatures(): array
	{
		return [
			'tokenize',
			'split_sentences',
			'normalize',
			'remove_stop_words',
			'stem',
			'detect_script',
			'ngrams',
			'pos_tag',
		];
	}

	public function supportsFeature(string $feature): bool
	{
		return in_array($feature, $this->supportedFeatures(), true);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function info(): array
	{
		return [
			'name' => $this->name(),
			'iso' => $this->isoCode(),
			'class' => static::class,
			'stop_words' => count($this->stopWords()),
			'features' => $this->supportedFeatures(),
		];
	}

	/**
	 * @return string[]
	 */
	protected function splitCharacters(string $text): array
	{
		return preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
	}

	protected function normalizeUnicode(string $text): string
	{
		if (class_exists('Normalizer')) {
			$normalized = \Normalizer::normalize($text, \Normalizer::NFC);
			if (is_string($normalized) === true) {
				return $normalized;
			}
		}

		return $text;
	}

	protected function collapseWhitespace(string $text): string
	{
		$trimmedText = trim($text);
		return preg_replace('/\s+/u', ' ', $trimmedText) ?? $trimmedText;
	}

	protected function lowercase(string $text): string
	{
		return mb_strtolower($text, 'UTF-8');
	}

	/**
	 * @param  array<int, mixed> $values
	 * @return string[]
	 */
	protected function filterNonEmptyStrings(array $values): array
	{
		$result = [];
		foreach ($values as $value) {
			if (is_string($value) === false) {
				continue;
			}

			$trimmedValue = trim($value);
			if ($trimmedValue === '') {
				continue;
			}

			$result[] = $trimmedValue;
		}

		return $result;
	}
}
