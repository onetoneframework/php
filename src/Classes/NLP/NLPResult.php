<?php

declare(strict_types=1);

namespace Clover\Classes\NLP\Result;

use JsonSerializable;
use OutOfBoundsException;
use function array_key_exists;
use function array_slice;
use function array_unique;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function json_encode;
use function round;

/**
 * Immutable NLP result snapshot.
 *
 * The result is safe to serialize, safe to pass across layers, and carries
 * both pipeline output and structured metadata about how that output was built.
 */
final class NLPResult implements JsonSerializable
{
	/**
	 * @param string[]            $tokens
	 * @param string[]            $sentences
	 * @param array<string, mixed> $state
	 * @param string[]            $pipeline
	 * @param array<string, mixed> $metadata
	 */
	public function __construct(
		public readonly string $raw,
		public readonly string $processed,
		public readonly array $tokens,
		public readonly array $sentences,
		public readonly array $state,
		public readonly array $pipeline,
		public readonly string $language,
		public readonly array $metadata = [],
	) {
	}

	/**
	 * @return string[]
	 */
	public function tokens(): array
	{
		return $this->tokens;
	}

	/**
	 * @return string[]
	 */
	public function sentences(): array
	{
		return $this->sentences;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function metadata(): array
	{
		return $this->metadata;
	}

	/**
	 * Word-frequency map (only if ->frequency() was called).
	 * 
	 * @return array<string, int>
	 */
	public function frequency(): array
	{
		$frequency = $this->state['frequency'] ?? [];
		return is_array($frequency) ? $frequency : [];
	}

	/**
	 * POS tags map (only if ->posTag() was called).
	 * 
	 * @return array<string, string>
	 */
	public function posTags(): array
	{
		$tags = $this->state['pos_tags'] ?? [];
		return is_array($tags) ? $tags : [];
	}

	/**
	 * N-grams (only if ->ngrams() was called).
	 * 
	 * @return array<int, array<int, string>>
	 */
	public function ngrams(): array
	{
		$ngrams = $this->state['ngrams'] ?? [];
		return is_array($ngrams) ? $ngrams : [];
	}

	/**
	 * Script detection map (only if ->detectScript() was called).
	 * 
	 * @return array<string, int>
	 */
	public function scripts(): array
	{
		$scripts = $this->state['scripts'] ?? [];
		return is_array($scripts) ? $scripts : [];
	}

    /** Any extra state stored by language-specific steps. */
	public function get(string $key, mixed $default = null): mixed
	{
		return $this->state[$key] ?? $default;
	}

	public function require(string $key): mixed
	{
		if ($this->hasState($key) === false) {
			throw new OutOfBoundsException('Missing NLP result state key: ' . $key);
		}

		return $this->state[$key];
	}

	public function metadataValue(string $key, mixed $default = null): mixed
	{
		return $this->metadata[$key] ?? $default;
	}

	public function hasState(string $key): bool
	{
		return array_key_exists($key, $this->state);
	}

	public function hasMetadata(string $key): bool
	{
		return array_key_exists($key, $this->metadata);
	}

	/**
	 * @return string[]
	 */
	public function pipelineSteps(): array
	{
		return $this->pipeline;
	}

	public function hasPipelineStep(string $step): bool
	{
		return in_array($step, $this->pipeline, true);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function stepDetails(): array
	{
		$stepDetails = $this->metadata['step_details'] ?? [];
		return is_array($stepDetails) ? $stepDetails : [];
	}

	public function languageCode(): string
	{
		$languageCode = $this->metadata['language_code'] ?? '';
		return is_string($languageCode) ? $languageCode : '';
	}

	public function componentClass(): ?string
	{
		$componentClass = $this->metadata['component_class'] ?? null;
		return is_string($componentClass) ? $componentClass : null;
	}

	public function tokenCount(): int
	{
		return count($this->tokens);
	}

	public function sentenceCount(): int
	{
		return count($this->sentences);
	}

	public function pipelineCount(): int
	{
		return count($this->pipeline);
	}

	public function avgTokensPerSentence(): float
	{
		if ($this->sentenceCount() === 0) {
			return 0.0;
		}

		return round($this->tokenCount() / $this->sentenceCount(), 2);
	}

    /** Type-token ratio (lexical diversity), 0.0–1.0. */
	public function typeTokenRatio(): float
	{
		if ($this->tokenCount() === 0) {
			return 0.0;
		}

		return round(count(array_unique($this->tokens)) / $this->tokenCount(), 4);
	}

	/**
	 * Top-N most frequent tokens (requires ->frequency() in pipeline).
	 * 
	 * @return array<string, int>
	 */
	public function topTokens(int $limit = 10): array
	{
		if ($limit < 1) {
			return [];
		}

		return array_slice($this->frequency(), 0, $limit, true);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function summary(): array
	{
		return [
			'language' => $this->language,
			'language_code' => $this->languageCode(),
			'token_count' => $this->tokenCount(),
			'sentence_count' => $this->sentenceCount(),
			'pipeline_count' => $this->pipelineCount(),
			'has_frequency' => $this->hasState('frequency'),
			'has_pos_tags' => $this->hasState('pos_tags'),
			'has_scripts' => $this->hasState('scripts'),
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'language' => $this->language,
			'language_code' => $this->languageCode(),
			'pipeline' => $this->pipeline,
			'pipeline_count' => $this->pipelineCount(),
			'raw' => $this->raw,
			'processed' => $this->processed,
			'token_count' => $this->tokenCount(),
			'sentence_count' => $this->sentenceCount(),
			'type_token_ratio' => $this->typeTokenRatio(),
			'tokens' => $this->tokens,
			'sentences' => $this->sentences,
			'state' => $this->state,
			'metadata' => $this->metadata,
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

	public function toJson(int $flags = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT): string
	{
		return json_encode($this->jsonSerialize(), $flags) ?: '{}';
	}

	public function __toString(): string
	{
		return $this->toJson();
	}
}
