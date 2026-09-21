<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\Gemini;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\{ArrayObject, JSONHandler, StringObject};
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\LLM\Gemini\ResponseBody;
use Clover\Classes\LLM\Gemini\GeneratedImage;
use Clover\Enumeration\Gemini\Model;
use Exception;
use function sprintf;

/**
 * Class Client
 *
 * Thin wrapper around the Google Gemini REST API.
 *
 * @package Clover\Classes\LLM\Gemini
 */
class Client
{
    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    /**
     * Imagen 3 model identifier used for image generation requests.
     *
     * This is the model the user referred to as "nanobanana".
     * Google's Imagen 3 is accessed via the "predict" action rather than
     * "generateContent", so it has its own dedicated method below.
     *
     * @see generateImage()
     */
    private static string $IMAGEN_MODEL = 'imagen-3.0-generate-001';

    /**
     * Base URL shared by all Gemini v1beta endpoints.
     */
    private const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta';

    // -------------------------------------------------------------------------
    // Properties
    // -------------------------------------------------------------------------

    private string $apiKey;
    private string $model;

    /**
     * Remote file references queued by uploadMedia().
     * Each element: ['fileUri' => string, 'mimeType' => string]
     *
     * @var array<int, array{fileUri: string, mimeType: string}>
     */
    private array $fileData = [];

    /**
     * Inline binary attachments queued by attachFileInline().
     * Each element is a "part" array ready for embedding in a request:
     *   ['inlineData' => ['mimeType' => string, 'data' => string]]
     *
     * @var array<int, array>
     */
    private array $inlineAttachments = [];

    /**
     * Conversation history used by requestPromptToChat().
     *
     * @var array<int, array>
     */
    private array $contents = [];

    /**
     * Client constructor.
     *
     * @param string|StringObject       $apiKey  Your Google API key.
     * @param Model|string|StringObject $model   Gemini model to use for text/multimodal
     *                                           requests (default: gemini-2.0-flash).
     *                                           Image generation always uses IMAGEN_MODEL
     *                                           regardless of this value.
     */
    public function __construct(string|StringObject $apiKey, Model|string|StringObject $model = 'gemini-2.0-flash')
    {
        $this->apiKey = (string) $apiKey;
        $this->model = ($model instanceof Model) ? $model : (string) $model;
    }

    public function setImagenModel(Model|string|StringObject $model)
    {
        self::$IMAGEN_MODEL = $model;
    }

    /**
     * Append a single turn to the internal conversation history.
     *
     * Used by requestPromptToChat() to build a multi-turn session.
     *
     * @param string       $role   "user" or "model"
     * @param string|array $parts  Either a plain string or an array of part objects.
     */
    public function addMessage(string $role, string|array $parts): void
    {
        $this->contents[] = [
            'role' => $role,
            // Normalise a plain string into a single-element parts array.
            'parts' => is_string($parts) ? [['text' => $parts]] : $parts,
        ];
    }

    /**
     * Attach a local file inline by base64-encoding it into the request body.
     *
     * Unlike uploadMedia() (which uploads to the Files API first), this method
     * does not make any network call.  It reads the file from disk, detects its
     * MIME type, base64-encodes the bytes, and queues the result.  The attachment
     * is then included automatically in the next call to requestPrompt() or
     * requestPromptToChat().
     *
     * When to use this vs uploadMedia():
     *   - attachFileInline() → best for images and documents under ~20 MB.
     *     The encoded data travels inside the request body, so very large files
     *     will hit the API's request size limit.
     *   - uploadMedia()      → best for video or audio files that are too big
     *     to embed inline.  The file is uploaded once and referenced by URI.
     *
     * Example usage:
     *   $client->attachFileInline('/path/to/diagram.png');
     *   $response = $client->requestPrompt('Describe this diagram.');
     *
     * @param  string $filePath  Absolute or relative path to the file.
     * @return void
     *
     * @throws Exception If the file does not exist or its MIME type cannot be
     *                   determined.
     */
    public function attachFileInline(string $filePath): void
    {
        // Guard: make sure the file actually exists before reading it.
        if (!file_exists($filePath)) {
            throw new Exception("attachFileInline: file not found — \"{$filePath}\".");
        }

        // Detect the MIME type using PHP's built-in fileinfo extension.
        // This is more reliable than relying on the file extension alone.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        if ($mimeType === false) {
            throw new Exception(
                "attachFileInline: could not determine MIME type for \"{$filePath}\"."
            );
        }

        // Read the raw file bytes and encode them as base64.
        $base64Data = base64_encode(file_get_contents($filePath));

        // Queue the attachment as an "inlineData" part.
        // This format matches the Gemini API's "Part" schema for inline blobs.
        $this->inlineAttachments[] = [
            'inlineData' => [
                'mimeType' => $mimeType,
                'data' => $base64Data,
            ],
        ];
    }

    /**
     * Remove all queued inline attachments.
     *
     * Call this between requests if you want to start a fresh prompt without
     * the previously attached files.
     *
     * @return void
     */
    public function clearInlineAttachments(): void
    {
        $this->inlineAttachments = [];
    }

    /**
     * Generate images using Google's Imagen 3 model ("nanobanana").
     *
     * Imagen 3 is accessed through the "predict" action on the model endpoint,
     * which is separate from the "generateContent" action used for text.
     *
     * API endpoint:
     *   POST https://generativelanguage.googleapis.com/v1beta/
     *        models/imagen-3.0-generate-001:predict?key=<API_KEY>
     *
     * Request body:
     *   {
     *     "instances":  [{ "prompt": "<your text prompt>" }],
     *     "parameters": { "sampleCount": <number of images> }
     *   }
     *
     * Response body:
     *   {
     *     "predictions": [
     *       { "bytesBase64Encoded": "<base64>", "mimeType": "image/png" },
     *       ...
     *     ]
     *   }
     *
     * Example usage:
     *   $images = $client->generateImage('A cat riding a surfboard at sunset');
     *   foreach ($images as $img) {
     *       $img->saveToFile('/var/www/storage/' . uniqid('img_'));
     *   }
     *
     * @param  string $prompt       Natural-language description of the desired image.
     * @param  int    $sampleCount  How many image variants to generate (1–4).
     *                              More samples mean more API credit usage.
     * @return GeneratedImage[]     One GeneratedImage instance per generated sample.
     *
     * @throws Exception If the API returns an error or an unexpected response shape.
     */
    public function generateImage(string $prompt, int $sampleCount = 1): array
    {
        // Clamp sampleCount to the range the Imagen API supports.
        $sampleCount = max(1, min(4, $sampleCount));

        // Build the Imagen predict endpoint URL.
        $url = sprintf('%s/models/%s:predict?key=%s', self::BASE_URL, self::$IMAGEN_MODEL, $this->apiKey, );

        // Assemble the request payload following the Imagen prediction schema.
        $payload = new ArrayObject([
            // "instances" is always an array; each entry holds one prompt.
            'instances' => [
                ['prompt' => (string) $prompt],
            ],
            // "parameters" controls generation settings.
            'parameters' => [
                'sampleCount' => $sampleCount, // Number of images to generate.
            ],
        ]);

        // Send the POST request via the project's cURL wrapper.
        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setContentTypeJson()
            ->setPostMethod()
            ->setPostField(JSONHandler::encode($payload));

        $response = $cURL->executeWithDecode();
        $cURL->close();

        // Surface any top-level API error immediately.
        if (isset($response['error'])) {
            $err = $response['error'];
            throw new Exception(sprintf('Imagen API error %s: %s', $err['code'] ?? 'unknown', $err['message'] ?? 'no message'));
        }

        // The "predictions" key must be present and non-empty for a valid response.
        if (empty($response['predictions'])) {
            throw new Exception('generateImage: API response contained no predictions. The prompt may have been blocked by the safety filter.');
        }

        // Convert each raw prediction array into a typed GeneratedImage object.
        return array_map(
            static fn(array $prediction): GeneratedImage => GeneratedImage::from($prediction),
            $response['predictions'],
        );
    }

    /**
     * Upload a media file to the Gemini Files API (remote storage).
     *
     * The file is uploaded once; subsequent requests reference it by URI.
     * Prefer this for large files (video, audio) that would be too big to
     * send inline.  For smaller files consider attachFileInline() instead.
     *
     * @param string $filePath  Local path to the file to upload.
     * @param string $fileName  Display name to give the file in the API.
     * @return void
     *
     * @throws Exception If the file does not exist.
     */
    public function uploadMedia(string $filePath, string $fileName): void
    {
        $profileUrl = sprintf('https://generativelanguage.googleapis.com/upload/v1beta/files?key=%s', $this->apiKey);

        if (!FileHandler::isExists($filePath)) {
            throw new Exception('File is not exists');
        }

        $mimeType = FileHandler::getMIMEContentType($filePath);

        $cURL = new ClientURL($profileUrl);
        $cURL->option
            ->setURL($profileUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setPostMethod()
            ->setPostFileField('file', $filePath, $mimeType, $fileName);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        // Store the remote file reference so it can be appended to future requests.
        $this->fileData[] = [
            'fileUri' => self::BASE_URL . '/' . $response['file']['name']->__toString(),
            'mimeType' => $mimeType,
        ];
    }

    /**
     * Send a one-shot prompt to the configured Gemini text/multimodal model.
     *
     * Any files queued via uploadMedia() or attachFileInline() are automatically
     * included as additional parts alongside the text prompt.
     *
     * The method merges attachments in this order:
     *   1. Inline attachments (attachFileInline)  — sent as inlineData parts
     *   2. Remote file references (uploadMedia)   — sent as fileData parts
     *   3. The text prompt                        — always last
     *
     * @param  string|StringObject $prompt        The user's text prompt.
     * @param  string              $systemPrompt  Optional system instruction
     *                                            (currently appended to the prompt
     *                                            for API compatibility).
     * @return ResponseBody
     *
     * @throws Exception On cURL or API errors.
     */
    public function requestPrompt(string|StringObject $prompt, string $systemPrompt = ''): ResponseBody
    {
        $profileUrl = sprintf('%s/models/%s:generateContent?key=%s', self::BASE_URL, $this->model, $this->apiKey, );

        // Build the parts array for this request.
        // Start with any inline attachments queued by attachFileInline().
        $parts = $this->inlineAttachments;

        // Append remote file references queued by uploadMedia().
        if (!empty($this->fileData)) {
            foreach ($this->fileData as $file) {
                $parts[] = ['fileData' => $file];
            }
        }

        // Always place the text prompt last so context comes before the question.
        $parts[] = ['text' => (string) $prompt];

        $data = new ArrayObject([
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => $parts,
                ],
            ],
        ]);

        $cURL = new ClientURL($profileUrl);
        $cURL->option
            ->setURL($profileUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setContentTypeJson()
            ->setPostMethod()
            ->setPostField(JSONHandler::encode($data));

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }

    /**
     * Retrieve a paginated list of cached content sessions from the API.
     *
     * @param  int   $pageSize  Maximum number of entries to return (default 100).
     * @return array            Raw decoded API response array.
     *
     * @throws Exception On cURL errors.
     */
    public function getChats(int $pageSize = 100): array
    {
        $url = sprintf('%s/cachedContents?pageSize=%d&key=%s', self::BASE_URL, $pageSize, $this->apiKey, );

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer();

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return $response;
    }

    /**
     * Send a multi-turn prompt to a specific cached content session (chat).
     *
     * Inline attachments queued by attachFileInline() are included in the
     * user turn, just like in requestPrompt().
     *
     * @param  string|StringObject $prompt    The user's text message.
     * @param  string              $chatName  The cached content resource name
     *                                        (e.g. "cachedContents/abc123").
     * @param  array               $media     Extra media parts to prepend.
     * @return ResponseBody
     *
     * @throws Exception On cURL or API errors.
     */
    public function requestPromptToChat(string|StringObject $prompt, string $chatName, array $media = []): ResponseBody
    {
        $profileUrl = sprintf('%s/%s:generateContent?key=%s', self::BASE_URL, $chatName, $this->apiKey, );

        // Collect all part types into a single ordered array.
        $parts = [];

        // 1. Caller-supplied media parts (e.g. pre-built fileData arrays).
        foreach ($media as $mediaItem) {
            $parts[] = $mediaItem;
        }

        // 2. Inline attachments queued by attachFileInline().
        foreach ($this->inlineAttachments as $inline) {
            $parts[] = $inline;
        }

        // 3. Remote file references queued by uploadMedia().
        foreach ($this->fileData as $file) {
            $parts[] = ['fileData' => $file];
        }

        // 4. The text prompt goes last.
        $parts[] = ['text' => (string) $prompt];

        // Append this turn to the conversation history.
        $this->addMessage('user', $parts);

        $data = new ArrayObject([
            'contents' => $this->contents,
        ]);

        $cURL = new ClientURL($profileUrl);
        $cURL->option
            ->setURL($profileUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setContentTypeJson()
            ->setPostMethod()
            ->setPostField(JSONHandler::encode($data));

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }
}
