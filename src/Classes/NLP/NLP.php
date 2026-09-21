<?php

declare(strict_types=1);

namespace Clover\Classes\NLP;

use Clover\Classes\NLP\Components\LanguageComponent;
use Clover\Classes\NLP\Result\NLPResult;
use LogicException;
use function array_count_values;
use function array_keys;
use function array_map;
use function arsort;
use function count;
use function implode;
use function is_array;
use function is_string;
use function mb_strlen;

/**
 * NLP pipeline coordinator.
 *
 * The pipeline keeps the original text immutable, mutates only the working set,
 * and captures structured step metadata so downstream services can inspect how
 * a result was produced.
 */
class NLP
{
	private string $rawText;

	private string $workingText;

	private ?LanguageComponent $component = null;

	/** @var string[] */
	private array $tokens = [];

	/** @var string[] */
	private array $sentences = [];

	/** @var string[] */
	private array $pipeline = [];

	/** @var array<string, mixed> */
	private array $state = [];

	/** @var array<int, array<string, mixed>> */
	private array $stepDetails = [];

	private bool $hasTokenizationRun = false;

	private bool $hasSentenceSplitRun = false;

	private function __construct(string $text)
	{
		$this->rawText = $text;
		$this->workingText = $text;
	}

	public static function of(string $text): static
	{
		return new static($text);
	}

    /**
     * Attach a language component to this pipeline.
     * Can be called multiple times to swap components mid-chain
     * (e.g. for mixed-language processing).
     */
	public function use(LanguageComponent $component): static
	{
		$this->component = $component;
		$this->recordStep('use:' . $component->name(), [
			'component' => $component->info(),
		]);

		return $this;
	}

    /** Split text into tokens (words / morphemes / characters per language). */
	public function tokenize(): static
	{
		$this->assertComponent(__METHOD__);

		$this->tokens = $this->component->tokenize($this->workingText);
		$this->hasTokenizationRun = true;
		$this->recordStep('tokenize', [
			'token_count' => count($this->tokens),
		]);

		return $this;
	}

    /** Split text into sentences. */
	public function sentences(): static
	{
		$this->assertComponent(__METHOD__);

		$this->sentences = $this->component->splitSentences($this->workingText);
		$this->hasSentenceSplitRun = true;
		$this->recordStep('sentences', [
			'sentence_count' => count($this->sentences),
		]);

		return $this;
	}

    /** Remove language-specific stop words from the token list. */
	public function removeStopWords(): static
	{
		$this->assertComponent(__METHOD__);
		$this->assertTokenized(__METHOD__);

		$this->tokens = $this->component->removeStopWords($this->tokens);
		$this->recordStep('removeStopWords', [
			'token_count' => count($this->tokens),
		]);

		return $this;
	}

    /** Normalize tokens (lowercasing, unicode normalization, etc.). */
	public function normalize(): static
	{
		$this->assertComponent(__METHOD__);

		$this->workingText = $this->component->normalize($this->workingText);

		if ($this->hasTokenizationRun === true) {
			$this->tokens = array_map(
				fn(string $token): string => $this->component->normalize($token),
				$this->tokens
			);
		}

		if ($this->hasSentenceSplitRun === true) {
			$this->sentences = array_map(
				fn(string $sentence): string => $this->component->normalize($sentence),
				$this->sentences
			);
		}

		$this->recordStep('normalize', [
			'processed_length' => mb_strlen($this->workingText, 'UTF-8'),
		]);

		return $this;
	}

    /**
     * Apply language-specific stemming / lemmatization.
     * (English: Porter-lite   Korean: suffix strip   Japanese: base-form)
     */
	public function stem(): static
	{
		$this->assertComponent(__METHOD__);
		$this->assertTokenized(__METHOD__);

		$this->tokens = $this->component->stem($this->tokens);
		$this->recordStep('stem', [
			'token_count' => count($this->tokens),
		]);

		return $this;
	}

    /**
     * Generate n-grams from the current token list.
     *
     * @param int $n  gram size (default 2)
     */
	public function ngrams(int $n = 2): static
	{
		$this->assertComponent(__METHOD__);
		$this->assertTokenized(__METHOD__);

		$ngrams = $this->component->ngrams($this->tokens, $n);
		$this->storeState('ngrams', $ngrams);
		$this->recordStep('ngrams(' . $n . ')', [
			'gram_size' => $n,
			'count' => count($ngrams),
		]);

		return $this;
	}

    /**
     * Tag tokens with basic part-of-speech labels.
     * Returns language-specific tag set.
     */
	public function posTag(): static
	{
		$this->assertComponent(__METHOD__);
		$this->assertTokenized(__METHOD__);

		$tagged = $this->component->posTag($this->tokens);
		$this->storeState('pos_tags', $tagged);
		$this->recordStep('posTag', [
			'count' => count($tagged),
		]);

		return $this;
	}

    /**
     * Detect character/script classes in the text.
     * (e.g. Latin, Hangul, Hiragana, Katakana, Kanji, …)
     */
	public function detectScript(): static
	{
		$this->assertComponent(__METHOD__);

		$scripts = $this->component->detectScript($this->workingText);
		$this->storeState('scripts', $scripts);
		$this->recordStep('detectScript', [
			'count' => count($scripts),
		]);

		return $this;
	}

    /**
     * Compute word-frequency map from current tokens.
     */
	public function frequency(): static
	{
		$this->assertTokenized(__METHOD__);

		$frequency = array_count_values($this->tokens);
		arsort($frequency);
		$this->storeState('frequency', $frequency);
		$this->recordStep('frequency', [
			'unique_tokens' => count($frequency),
		]);

		return $this;
	}

	public function analyze(): NLPResult
	{
		return new NLPResult(
			raw: $this->rawText,
			processed: $this->workingText,
			tokens: $this->tokens,
			sentences: $this->sentences,
			state: $this->state,
			pipeline: $this->pipeline,
			language: $this->component?->name() ?? 'unknown',
			metadata: $this->buildMetadata(),
		);
	}

	public function reset(bool $preserveComponent = true): static
	{
		$this->workingText = $this->rawText;
		$this->clearRuntimeState($preserveComponent);

		return $this;
	}

	public function replaceText(string $text, bool $preserveComponent = true): static
	{
		$this->rawText = $text;
		$this->workingText = $text;
		$this->clearRuntimeState($preserveComponent);

		return $this;
	}

	/** @return string[] */
	public function getTokens(): array
	{
		return $this->tokens;
	}

	/** @return string[] */
	public function getSentences(): array
	{
		return $this->sentences;
	}

	public function getRaw(): string
	{
		return $this->rawText;
	}

	public function getProcessed(): string
	{
		return $this->workingText;
	}

	/** @return string[] */
	public function getPipeline(): array
	{
		return $this->pipeline;
	}

	/** @return array<string, mixed> */
	public function getState(): array
	{
		return $this->state;
	}

	public function getLanguage(): string
	{
		return $this->component?->name() ?? 'unknown';
	}

	public function getLanguageCode(): string
	{
		return $this->component?->isoCode() ?? '';
	}

	public function hasTokenizationRun(): bool
	{
		return $this->hasTokenizationRun;
	}

	public function hasSentenceSplitRun(): bool
	{
		return $this->hasSentenceSplitRun;
	}

	public function hasStateKey(string $key): bool
	{
		return array_key_exists($key, $this->state);
	}

	/** @return array<int, array<string, mixed>> */
	public function getStepDetails(): array
	{
		return $this->stepDetails;
	}

	private function assertComponent(string $method): void
	{
		if ($this->component === null) {
			throw new LogicException(
				$method . ' requires a language component. Call ->use(new XxxComponent()) first.'
			);
		}
	}

	private function assertTokenized(string $method): void
	{
		if ($this->hasTokenizationRun === false) {
			throw new LogicException(
				$method . ' requires tokenization. Call ->tokenize() before this step.'
			);
		}
	}

	/**
	 * Store state in a single location so result snapshots stay consistent.
	 *
	 * @param array<string, mixed>|array<int, mixed>|string[] $value
	 */
	private function storeState(string $key, mixed $value): void
	{
		$this->state[$key] = $value;
	}

	/**
	 * Record a deterministic step trace for result metadata and debugging.
	 *
	 * @param array<string, mixed> $context
	 */
	private function recordStep(string $step, array $context = []): void
	{
		$this->pipeline[] = $step;
		$this->stepDetails[] = [
			'index' => count($this->stepDetails) + 1,
			'step' => $step,
			'component' => $this->component?->name(),
			'language_code' => $this->component?->isoCode(),
			'context' => $context,
		];
	}

	private function clearRuntimeState(bool $preserveComponent): void
	{
		if ($preserveComponent === false) {
			$this->component = null;
		}

		$this->tokens = [];
		$this->sentences = [];
		$this->pipeline = [];
		$this->state = [];
		$this->stepDetails = [];
		$this->hasTokenizationRun = false;
		$this->hasSentenceSplitRun = false;
	}

	/**
	 * Build structured metadata that can be used by higher application layers.
	 *
	 * @return array<string, mixed>
	 */
	private function buildMetadata(): array
	{
		$componentInfo = $this->component?->info();

		return [
			'component' => is_array($componentInfo) ? $componentInfo : null,
			'language_code' => $this->component?->isoCode(),
			'component_class' => $this->component !== null ? $this->component::class : null,
			'raw_length' => mb_strlen($this->rawText, 'UTF-8'),
			'processed_length' => mb_strlen($this->workingText, 'UTF-8'),
			'token_count' => count($this->tokens),
			'sentence_count' => count($this->sentences),
			'step_count' => count($this->pipeline),
			'pipeline_signature' => implode(' > ', $this->pipeline),
			'has_tokenization_run' => $this->hasTokenizationRun,
			'has_sentence_split_run' => $this->hasSentenceSplitRun,
			'state_keys' => array_keys($this->state),
			'step_details' => $this->stepDetails,
		];
	}
}
