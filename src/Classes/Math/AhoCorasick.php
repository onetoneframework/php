<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use function strlen;

/**
 * Class AhoCorasick
 *
 * An implementation of the Aho-Corasick algorithm for multi-pattern string matching.
 */
class AhoCorasick
{
    private $states = [];
    private $patterns = [];

    /**
     * AhoCorasick constructor.
     *
     * @param array $patterns An array of patterns to be matched against the input text.
     */
    public function __construct(array $patterns)
    {
        $this->patterns = $patterns;
        $this->buildAutomaton();
    }

    /**
     * Build the Aho-Corasick automaton based on the provided patterns.
     *
     * This method initializes the states of the automaton and constructs the goto and failure functions
     * necessary for efficient multi-pattern matching. It first builds the trie structure for the patterns
     * and then computes the failure links to ensure that the automaton can handle mismatches effectively.
     */
    private function buildAutomaton()
    {
        $this->states[] = [
            'goto' => [],
            'failure' => 0,
            'output' => []
        ];

        $this->buildGotoFunction();
        $this->buildFailureFunction();
    }

    /**
     * Build the goto function for the Aho-Corasick automaton.
     *
     * This function constructs the trie structure for the given patterns and initializes the states of the automaton.
     * Each state represents a node in the trie, and the 'goto' array defines transitions based on input characters.
     * The 'output' array stores the indices of patterns that end at that state.
     */
    private function buildGotoFunction()
    {
        $newStateId = 1;

        foreach ($this->patterns as $patternIndex => $pattern) {
            $currentStateId = 0;

            for ($i = 0; $i < strlen($pattern); $i++) {
                $char = $pattern[$i];

                if (!isset($this->states[$currentStateId]['goto'][$char])) {
                    $this->states[$currentStateId]['goto'][$char] = $newStateId;
                    $this->states[$newStateId] = [
                        'goto' => [],
                        'failure' => 0,
                        'output' => []
                    ];
                    $currentStateId = $newStateId;
                    $newStateId++;
                } else {
                    $currentStateId = $this->states[$currentStateId]['goto'][$char];
                }
            }

            $this->states[$currentStateId]['output'][] = $patternIndex;
        }
    }

    /**
     * Build the failure function for the Aho-Corasick automaton.
     *
     * This function uses a breadth-first search approach to compute the failure links for each state in the automaton.
     * It ensures that when a mismatch occurs, the automaton can transition to the appropriate state based on the longest
     * proper suffix of the current input that is also a prefix of some pattern.
     */
    private function buildFailureFunction()
    {
        $queue = new \SplQueue();

        foreach ($this->states[0]['goto'] as $char => $stateId) {
            $queue->enqueue($stateId);
        }

        while (!$queue->isEmpty()) {
            $currentStateId = $queue->dequeue();

            foreach ($this->states[$currentStateId]['goto'] as $char => $nextStateId) {
                $queue->enqueue($nextStateId);

                $failureStateId = $this->states[$currentStateId]['failure'];

                while ($failureStateId !== 0 && !isset($this->states[$failureStateId]['goto'][$char])) {
                    $failureStateId = $this->states[$failureStateId]['failure'];
                }

                if (isset($this->states[$failureStateId]['goto'][$char])) {
                    $this->states[$nextStateId]['failure'] = $this->states[$failureStateId]['goto'][$char];
                } else {
                    $this->states[$nextStateId]['failure'] = 0;
                }

                $this->states[$nextStateId]['output'] = array_merge(
                    $this->states[$nextStateId]['output'],
                    $this->states[$this->states[$nextStateId]['failure']]['output']
                );
            }
        }
    }

    /**
     * Search for patterns in the given text using the Aho-Corasick automaton.
     *
     * @param string $text The text to search within.
     * @return array An array of matches, each containing the pattern index, start position, and pattern string.
     */
    public function search(string $text)
    {
        $results = [];
        $currentStateId = 0;

        for ($i = 0; $i < strlen($text); $i++) {
            $char = $text[$i];

            while ($currentStateId !== 0 && !isset($this->states[$currentStateId]['goto'][$char])) {
                $currentStateId = $this->states[$currentStateId]['failure'];
            }

            if (isset($this->states[$currentStateId]['goto'][$char])) {
                $currentStateId = $this->states[$currentStateId]['goto'][$char];
            } else {
                $currentStateId = 0;
            }

            if (!empty($this->states[$currentStateId]['output'])) {
                foreach ($this->states[$currentStateId]['output'] as $patternIndex) {
                    $patternLength = strlen($this->patterns[$patternIndex]);
                    $startPosition = ($i - $patternLength + 1) + 1;
                    $results[] = [
                        'index' => $patternIndex,
                        'start' => $startPosition,
                        'pattern' => $this->patterns[$patternIndex]
                    ];
                }
            }
        }

        return $results;
    }
}
