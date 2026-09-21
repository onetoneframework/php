<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Word;

/**
 * Class WordBranchSentenceBuilder
 * 
 * Builds sentences based on a branching word map.
 */
class WordBranchSentenceBuilder
{
    /** @var array $branchMap The branching word map */
    protected array $branchMap;
    /** @var array $sentence The current sentence */
    protected array $sentence = [];

    /**
     * Constructor
     *
     * @param array $branchMap The branching word map
     */
    public function __construct(array $branchMap)
    {
        $this->branchMap = $branchMap;
    }

    /**
     * Get the starting words
     *
     * @return array The starting words
     */
    public function start(): array
    {
        return $this->branchMap['__start__'] ?? [];
    }

    /**
     * Choose the next word and update the sentence
     *
     * @param string $word The chosen word
     * @return array The next possible words
     */
    public function choose(string $word): array
    {
        if ($word === '<[/OBJECT/]>' || !$this->isValidWord($word)) {
            return [];
        }

        $this->sentence[] = $word;

        // Sentence completed if ends with punctuation
        if (preg_match('/[.!?]$/', $word)) {
            return [];
        }

        return $this->branchMap[$word] ?? [];
    }

    /**
     * Get the constructed sentence as a string
     *
     * @return string The constructed sentence
     */
    public function getSentence(): string
    {
        return implode(' ', $this->sentence);
    }

    /**
     * Validate a word
     *
     * @param string $word The word to validate
     * @return bool True if the word is valid, false otherwise
     */
    protected function isValidWord(string $word): bool
    {
        return $word !== '<[/OBJECT/]>';
    }
}
