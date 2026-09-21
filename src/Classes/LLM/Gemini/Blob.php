<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\Gemini;

/**
 * Class Blob
 *
 * Represents a Blob object in the Gemini LLM API.
 *
 * A Blob carries binary data (typically an image or audio clip) that is sent
 * inline — base64-encoded — directly inside a request Part, as opposed to
 * being referenced by a remote file URI.
 *
 * Gemini API shape (inside a "parts" element):
 *
 *   {
 *     "inlineData": {
 *       "mimeType": "image/png",
 *       "data": "<base64 string>"
 *     }
 *   }
 *
 * @package Clover\Classes\LLM\Gemini
 */
class Blob
{
    /**
     * MIME type of the binary data (e.g. "image/png", "audio/mp3").
     *
     * @var string
     */
    private string $mimeType;

    /**
     * Base64-encoded binary content.
     *
     * @var string
     */
    private string $data;

    /**
     * Blob constructor.
     *
     * @param string $mimeType  MIME type string (e.g. "image/png").
     * @param string $data      Base64-encoded binary payload.
     */
    public function __construct(string $mimeType, string $data)
    {
        $this->mimeType = $mimeType;
        $this->data = $data;
    }

    /**
     * Create a Blob from a plain PHP array (e.g. parsed from an API response).
     *
     * Expected array shape:
     *   ['mimeType' => 'image/png', 'data' => '<base64 string>']
     *
     * @param  array $attributes
     * @return self
     */
    public static function from(array $attributes): self
    {
        return new self(
            mimeType: $attributes['mimeType'],
            data: $attributes['data'],
        );
    }

    /**
     * Create a Blob directly from a file path.
     *
     * Reads the file, detects its MIME type, and base64-encodes the content
     * so it can be sent inline with a Gemini API request.
     *
     * @param  string $filePath  Absolute or relative path to the file.
     * @return self
     *
     * @throws \InvalidArgumentException If the file does not exist.
     * @throws \RuntimeException         If the MIME type cannot be detected.
     */
    public static function fromFile(string $filePath): self
    {
        // Validate that the file actually exists before trying to read it.
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException(
                "Blob::fromFile — file not found: \"{$filePath}\"."
            );
        }

        // Use the fileinfo extension (built into PHP) to detect the MIME type.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        if ($mimeType === false) {
            throw new \RuntimeException(
                "Blob::fromFile — could not determine MIME type for \"{$filePath}\"."
            );
        }

        // Read the raw bytes and base64-encode them for inline transmission.
        $data = base64_encode(file_get_contents($filePath));

        return new self(mimeType: $mimeType, data: $data);
    }

    /**
     * Return the MIME type string.
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Return the base64-encoded data string.
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * Serialize the Blob to an array suitable for JSON-encoding into a request.
     *
     * The Gemini API expects:
     *   { "mimeType": "image/png", "data": "<base64>" }
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'mimeType' => $this->mimeType,
            'data' => $this->data,
        ];
    }
}
