<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\LLM\Gemini;

use Clover\Classes\Data\StringObject;

/**
 * Class ResponseBody
 *
 * @package Clover\Classes\LLM\Gemini
 */
class ResponseBody
{

    /**
     * Candidate responses returned by the model.
     *
     * @var array<Candidates>
     */
    private array $candidates = [];
    /**
     * Error information if the request failed.
     *
     * @var Error|null
     */
    private ?Error $error = null;

    /**
     * ResponseBody constructor.
     *
     * @param array $attributes
     */
    public function __construct($attributes)
    {
        if (isset($attributes['error'])) {
            $this->error = Error::from($attributes['error']);
        }

        foreach ($attributes['candidates'] as $candidate) {
            $this->candidates[] = Candidates::from($candidate);
        }
    }

    /**
     * Check if the response has an error.
     *
     * @return bool
     */
    public function hasError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Get the error details if present.
     *
     * @return Error|null
     */
    public function getError(): Error|null
    {
        return $this->error;
    }

    /**
     * Get the candidate responses.
     *
     * @return array<Candidates|Part>
     * @throws \Exception
     */
    public function getParts(): array
    {
        if (empty($this->candidates)) {
            throw new \Exception('Candidates is empty');
        }

        if (count($this->candidates) > 1) {
            throw new \Exception('Candidate is more than 1');
        }

        return $this->candidates[0]->getContent()->getParts();
    }

    /**
     * Get the text of the first part of the first candidate response.
     *
     * @return StringObject
     * @throws \Exception
     */
    public function getText(): StringObject
    {
        $parts = $this->getParts();

        return $parts[0]->getText();
    }

}