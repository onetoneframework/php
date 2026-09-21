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
use Clover\Classes\LLM\Gemini\Blob;

/**
 * Class Part
 *
 * @package Clover\Classes\LLM\Gemini
 */
class Part
{
    private string $text;
    private $inlineData;

    /**
     * Part constructor.
     *
     * @param string|null $text
     * @param Blob|null   $inlineData
     */
    public function __construct(?string $text = null, ?Blob $inlineData = null)
    {
        $this->text = $text;
        $this->inlineData = $inlineData;
    }

    /**
     * Create a Part instance from an array of attributes.
     *
     * @param array $attributes
     * @return Part
     */
    public static function from(array $attributes): self
    {
        return new self(
            text: $attributes['text'] ?? null,
            inlineData: isset($attributes['inlineData']) ? Blob::from($attributes['inlineData']) : null
        );
    }
    
    /**
     * Get the text content of the part.
     *
     * @return StringObject
     */
    public function getText(): StringObject
    {
        return new StringObject($this->text);
    }

    /**
     * Convert the Part instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->text !== null) {
            $data['text'] = $this->text;
        }

        if ($this->inlineData !== null) {
            $data['inlineData'] = $this->inlineData;
        }

        return $data;
    }
}