<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\LLM\Gemini;

use Clover\Enumeration\Gemini\Role;
use Clover\Classes\LLM\Gemini\Blob;

/**
 * Class Content
 *
 * Represents the content of a message in the Gemini LLM API.
 *
 * @package Clover\Classes\LLM\Gemini
 */
class Content
{

    /**
     * Parts that make up the content payload.
     *
     * @var array<Part>
     */
    private array $parts;
    private $role;

    /**
     * Content constructor.
     *
     * @param array<Part> $parts
     * @param string|Role        $role
     */
    public function __construct($parts, $role)
    {
        $this->parts = $parts;
        $this->role = $role;
    }

    /**
     * Parse various input types into a Content instance.
     *
     * @param string|array|Content $part
     * @param Role                 $role
     * @return Content
     */
    public static function parse(string|array|Content $part, Role $role = Role::USER): self
    {
        return match (true) {
            $part instanceof self => $part,
            $part instanceof Blob => new Content([new Part(inlineData: $part)], $role),
            \is_array($part) => new Content(
                parts: array_map(
                    callback: static fn($subPart) => match (true) {
                            \is_string($subPart) => new Part(text: $subPart),
                            $subPart instanceof Blob => new Part(inlineData: $subPart),
                        },
                    array: $part,
                ),
                role: $role,
            ),
            \is_string($part) => new Content(parts: [new Part(text: $part)], role: $role),
        };
    }

    /**
     * Create a Content instance from an array of attributes.
     *
     * @param array $attributes
     * @return Content
     */
    public static function from(array $attributes): self
    {
        $parts = array_map(
            static fn(array $candidate): Part => Part::from($candidate),
            $attributes['parts'],
        );

        return new self(
            parts: $parts,
            role: Role::from($attributes['role'])
        );
    }

    /**
     * Get the parts of the content.
     *
     * @return array<Part>
     */
    public function getParts(): array
    {
        return $this->parts;
    }

    /**
     * Convert the Content instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'parts' => array_map(
                static fn(Part $part): array => $part->toArray(),
                $this->parts,
            ),
            'role' => $this->role->value,
        ];
    }
}