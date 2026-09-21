<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data\HTMLHandler as HTMLHandler;
use Override;

/**
 * Class HTMLObject
 *
 * Represents an HTML string and provides methods for HTML manipulation.
 */
#[\AllowDynamicProperties]
class HTMLObject extends StringObject
{
    /**
     * The raw HTML string data.
     *
     * @var mixed
     */
    protected $rawData;
    
    /**
     * HTMLObject constructor.
     *
     * @param mixed $data The HTML string data.
     */
    public function __construct($data)
    {
        parent::__construct($data);

        $this->rawData = $data;
    }

    /**
     * Gets the length of the HTML string.
     *
     * @return int The length of the HTML string.
     */
    public function length(): int
    {
        return parent::length();
    }

    /**
     * Converts special HTML characters to their corresponding entities.
     *
     * @return array|string The HTML string with special characters converted to entities.
     */
    public function unhtmlSpecialChars(): array|string
    {
        return HTMLHandler::unhtmlSpecialChars($this->rawData);
    }

    /**
     * Converts special characters to HTML entities.
     *
     * @return string The HTML string with special characters converted to HTML entities.
     */
    public function convertSpecialCharactersToHtmlEntities(): string
    {
        return HTMLHandler::convertSpecialCharactersToHtmlEntities($this->rawData);
    }

    /**
     * Automatically converts URLs in the HTML string to clickable links.
     *
     * @return array|string|null The HTML string with URLs converted to clickable links.
     */
    public function autolink(): array|string|null
    {
        return HTMLHandler::autolink($this->rawData);
    }

    /**
     * Converts HTML entities back to their corresponding characters.
     *
     * @param mixed $names Optional parameter for specific entity names.
     * @return array|string The HTML string with entities converted back to characters.
     */
    public function entityToTag($names): mixed
    {
        return HTMLHandler::entityToTag($this->rawData, $names);
    }

    /**
     * Trims whitespace and newlines from the HTML string.
     *
     * @return string The trimmed HTML string.
     */
    public function nTrim(): string
    {
        return HTMLHandler::nTrim($this->rawData);
    }

    /**
     * Converts <br> tags to newlines in the HTML string.
     *
     * @return string The HTML string with <br> tags converted to newlines.
     */
    public function brToNl(): string
    {
        return HTMLHandler::brToNl($this->rawData);
    }

    /**
     * Converts newlines to <br> tags in the HTML string.
     *
     * @return array|string|null The HTML string with newlines converted to <br> tags.
     */
    public function nlToBr(): array|string|null
    {
        return HTMLHandler::nlToBr($this->rawData);
    }

    /**
     * Strips HTML tags from the HTML string.
     *
     * @param array|string|null $allowedTags Optional parameter for specific tags to strip.
     * @return string The HTML string with specified tags stripped.
     */
    public function stripTags(array|string|null $allowedTags = null): self
    {
        return new self(HTMLHandler::stripTags($this->rawData, $allowedTags));
    }

    /**
     * Limits the number of newlines in the HTML string.
     *
     * @param string $tags Optional parameter for specific tags to consider.
     * @return array|string The HTML string with limited newlines.
     */
    public function nlslim(string $tags = ''): array|string
    {
        return HTMLHandler::nlslim($this->rawData, $tags);
    }

    /**
     * Trims newlines from the start and end of the HTML string.
     *
     * @return array|string|null The HTML string with newlines trimmed.
     */
    public function nlTrim(): array|string|null
    {
        return HTMLHandler::nlTrim($this->rawData);
    }

    /**
     * Replaces certain characters in the HTML string to make it safe for HTML source.
     *
     * @return mixed The HTML string with characters replaced for HTML source safety.
     */
    public function replaceToHtmlSource(): mixed
    {
        return HTMLHandler::replaceToHtmlSource($this->rawData);
    }

    /**
     * Escapes slashes in the HTML string.
     *
     * @return string The HTML string with slashes escaped.
     */
    public function escapeSlash(): string
    {
        return HTMLHandler::escapeSlash($this->rawData);
    }

}
