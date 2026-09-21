<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\LLM\Gemini;

use Clover\Classes\LLM\Gemini\Content;
use Clover\Enumeration\Gemini\{FinishReason, Role};

/**
 * Class Candidates
 *
 * Represents a candidate response from the Gemini LLM API.
 *
 * @package Clover\Classes\LLM\Gemini
 */
class Candidates
{

    private Content $content;
    private string $finishReason;
    private ?float $avgLogprobs;

    public function __construct(Content $content, string $finishReason, ?float $avgLogprobs)
    {
        $this->content = $content;
        $this->finishReason = $finishReason;
        $this->avgLogprobs = $avgLogprobs;
    }

    /**
     * Create a Candidates instance from an array of attributes.
     *
     * @param array $attributes
     * @return Candidates
     */
    public static function from(array $attributes): self
    {
        $content = match (true) {
            isset($attributes['content']) => Content::from($attributes['content']),
            default => new Content(parts: [], role: Role::MODEL),
        };

        $finishReason = match (true) {
            isset($attributes['finishReason']) => FinishReason::from($attributes['finishReason']),
            default => null,
        };

        return new self($content, $finishReason, $attributes['avgLogprobs'] ?? null);
    }

    /**
     * Get the content of the candidate.
     *
     * @return Content
     */
    public function getContent()
    {
        return $this->content;
    }
}