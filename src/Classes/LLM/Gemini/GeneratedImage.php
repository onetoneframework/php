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
 * Class GeneratedImage
 *
 * Represents a single image returned by the Imagen (image generation) API.
 *
 * The Imagen API responds with a "predictions" array, where each element
 * contains a base64-encoded PNG and its MIME type:
 *
 *   {
 *     "predictions": [
 *       {
 *         "bytesBase64Encoded": "<base64 string>",
 *         "mimeType": "image/png"
 *       }
 *     ]
 *   }
 *
 * This class wraps one such prediction entry and provides helpers for
 * saving the image to disk or obtaining the raw binary data.
 *
 * @package Clover\Classes\LLM\Gemini
 */
class GeneratedImage
{
    /**
     * Raw base64-encoded image data returned by the API.
     *
     * @var string
     */
    private string $base64Data;

    /**
     * MIME type of the generated image (e.g. "image/png").
     *
     * @var string
     */
    private string $mimeType;

    /**
     * GeneratedImage constructor.
     *
     * @param string $base64Data  Base64-encoded image bytes from the API.
     * @param string $mimeType    MIME type string (e.g. "image/png").
     */
    public function __construct(string $base64Data, string $mimeType)
    {
        $this->base64Data = $base64Data;
        $this->mimeType   = $mimeType;
    }

    /**
     * Build a GeneratedImage from a single prediction array element.
     *
     * Expected array shape:
     *   [
     *     'bytesBase64Encoded' => '<base64 string>',
     *     'mimeType'           => 'image/png',
     *   ]
     *
     * @param  array $prediction  One entry from the "predictions" array.
     * @return self
     *
     * @throws \InvalidArgumentException If required keys are absent.
     */
    public static function from(array $prediction): self
    {
        // Both keys must be present; raise a clear error when they are not.
        if (!isset($prediction['bytesBase64Encoded'], $prediction['mimeType'])) {
            throw new \InvalidArgumentException('Imagen prediction is missing "bytesBase64Encoded" or "mimeType".');
        }

        return new self(
            base64Data: $prediction['bytesBase64Encoded'],
            mimeType:   $prediction['mimeType'],
        );
    }

    /**
     * Return the raw base64 string exactly as received from the API.
     *
     * Useful if you need to embed the image in an HTML <img> tag or store
     * the encoded string in a database.
     *
     * @return string
     */
    public function getBase64(): string
    {
        return $this->base64Data;
    }

    /**
     * Decode the base64 data and return the raw binary bytes.
     *
     * Use this when you need to pass the image to an image library (e.g. GD,
     * Imagick) or write it directly to a response body.
     *
     * @return string  Binary image data.
     */
    public function getBytes(): string
    {
        return base64_decode($this->base64Data, strict: true);
    }

    /**
     * Return the MIME type of the image (e.g. "image/png").
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    /**
     * Derive a suitable file extension from the MIME type.
     *
     * The Imagen API currently only returns "image/png", but this helper
     * future-proofs the code for additional MIME types.
     *
     * @return string  Extension without a leading dot (e.g. "png", "jpeg").
     */
    public function getExtension(): string
    {
        // Map known image MIME types to their canonical file extension.
        $map = [
            'image/png'  => 'png',
            'image/jpeg' => 'jpg',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        return $map[$this->mimeType] ?? 'bin'; // Fall back to ".bin" for unknown types.
    }

    /**
     * Save the decoded image bytes to a file on disk.
     *
     * The file path should include a name but NOT an extension; this method
     * appends the correct extension automatically:
     *
     *   $image->saveToFile('/var/www/storage/generated');
     *   // Writes to: /var/www/storage/generated.png
     *
     * @param  string $pathWithoutExtension  Absolute or relative path minus extension.
     * @return string                         The full path where the file was written.
     *
     * @throws \RuntimeException If the file cannot be written.
     */
    public function saveToFile(string $pathWithoutExtension): string
    {
        $fullPath = $pathWithoutExtension . '.' . $this->getExtension();

        // file_put_contents returns false on failure.
        $written = file_put_contents($fullPath, $this->getBytes());

        if ($written === false) {
            throw new \RuntimeException("GeneratedImage: failed to write image file to \"{$fullPath}\".");
        }

        return $fullPath;
    }
}
