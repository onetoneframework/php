<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Perplexity;

use Clover\Classes\Data\StringObject;
use Exception;

class ResponseBody
{
    private array $choices = [];
    private ?Error $error = null;

    public function __construct(array $attributes)
    {
        if (isset($attributes['error'])) {
            $this->error = Error::from($attributes['error']);
        }

        if (isset($attributes['choices'])) {
            foreach ($attributes['choices'] as $choice) {
                $this->choices[] = Choice::from($choice);
            }
        }
    }

    public function hasError(): bool
    {
        return $this->error !== null;
    }

    public function getError(): ?Error
    {
        return $this->error;
    }

    public function getText(): StringObject
    {
        if (empty($this->choices)) {
            throw new Exception('Choices is empty');
        }

        return $this->choices[0]->getMessage()->getContent();
    }
}