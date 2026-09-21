<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\Claude;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\{ArrayObject, JSONHandler, StringObject};
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\LLM\Claude\ResponseBody;
use Clover\Enumeration\Claude\Model;
use Exception;
use function sprintf;

/**
 * Class Client
 *
 * A client for interacting with the Claude LLM API.
 *
 * @package Clover\Classes\LLM\Claude
 */
class Client
{
    private string $apiKey;
    private string $model;
    private array $messages = [];
    private string $systemPrompt = "";
    private ?string $sessionKey = null;

    /**
     * Client constructor.
     *
     * @param string|StringObject $apiKey
     * @param Model|string|StringObject $model
     */
    public function __construct(string|StringObject $apiKey, Model|string|StringObject $model = Model::HAIKU_3_20240307)
    {
        $this->apiKey = $apiKey;
        $this->model = ($model instanceof Model) ? $model : $model;
    }

    /**
     * Add a message to the conversation.
     *
     * @param string|StringObject $role
     * @param string|array $content
     *
     * @return void
     */
    public function addMessage(string|StringObject $role, string|array $content): void
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
    }

    /**
     * Set the system prompt for the conversation.
     *
     * @param string $systemPrompt
     *
     * @return void
     */
    public function setSystemPrompt(string $systemPrompt): void
    {
        $this->systemPrompt = $systemPrompt;
    }

    /**
     * Upload media file to be used in the conversation.
     *
     * @param string $filePath
     *
     * @return array
     * @throws Exception
     */
    public function uploadMedia(string $filePath): array
    {
        if (!FileHandler::isExists($filePath)) {
            throw new Exception('File is not exists');
        }

        $mimeType = FileHandler::getMIMEContentType($filePath);
        $fileContent = file_get_contents($filePath);
        $base64Data = base64_encode($fileContent);

        return [
            'type' => $this->getContentType($mimeType),
            'source' => [
                'type' => 'base64',
                'media_type' => $mimeType,
                'data' => $base64Data
            ]
        ];
    }

    /**
     * Determine content type based on MIME type.
     *
     * @param string $mimeType
     *
     * @return string
     * @throws Exception
     */
    private function getContentType(string $mimeType): string
    {
        if (strpos($mimeType, 'image/') === 0) {
            return 'image';
        } elseif ($mimeType === 'application/pdf') {
            return 'document';
        }
        throw new Exception('Unsupported media type');
    }

    /**
     * Build cookie string from an array of cookies.
     *
     * @param array $cookies
     *
     * @return string
     */
    private function buildCookieString(array $cookies): string
    {
        $cookiePairs = [];
        foreach ($cookies as $name => $value) {
            $cookiePairs[] = "{$name}={$value}";
        }
        return implode('; ', $cookiePairs);
    }

    /**
     * Get session key using provided cookies.
     *
     * @param array $cookies
     *
     * @return string
     * @throws Exception
     */
    private function getSessionKey(array $cookies): string
    {
        if ($this->sessionKey !== null) {
            return $this->sessionKey;
        }

        $sessionUrl = "https://claude.ai/api/organizations";
        $cookieString = $this->buildCookieString($cookies);

        $cURL = new ClientURL($sessionUrl);
        $cURL->option
            ->setURL($sessionUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setHeaders([
                'Referer: https://claude.ai/',
                'Origin: https://claude.ai',
                'Accept: */*',
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        if (empty($response) || !isset($response[0]['uuid'])) {
            throw new Exception('Failed to get session key');
        }

        $this->sessionKey = $response[0]['uuid'];
        return $this->sessionKey;
    }

    /**
     * Get usage statistics for the organization.
     *
     * @param string $organizationId
     *
     * @return mixed
     */
    public function getUsage(string $organizationId): mixed
    {
        $profileUrl = sprintf("https://api.anthropic.com/v1/organizations/%s/usage", $organizationId);

        $cURL = new ClientURL($profileUrl);
        $cURL->option
            ->setURL($profileUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setHeaders([
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return $response;
    }

    /**
     * Get list of conversations for the user.
     *
     * @param array $cookies
     *
     * @return array
     * @throws Exception
     */
    public function getConversations(array $cookies): array
    {
        $organizationId = $this->getSessionKey($cookies);
        $conversationsUrl = "https://claude.ai/api/organizations/{$organizationId}/chat_conversations";
        $cookieString = $this->buildCookieString($cookies);

        $cURL = new ClientURL($conversationsUrl);
        $cURL->option
            ->setURL($conversationsUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setHeaders([
                'Referer: https://claude.ai/',
                'Origin: https://claude.ai',
                'Accept: */*',
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return $response;
    }

    /**
     * Send a prompt to an existing conversation.
     *
     * @param string|StringObject $prompt
     * @param string             $conversationId
     * @param array              $cookies
     * @param array              $media
     *
     * @return ResponseBody
     * @throws Exception
     */
    public function requestPromptToConversation(string|StringObject $prompt, string $conversationId, array $cookies, array $media = []): ResponseBody
    {
        $organizationId = $this->getSessionKey($cookies);
        $chatUrl = "https://claude.ai/api/organizations/{$organizationId}/chat_conversations/{$conversationId}/completion";
        $cookieString = $this->buildCookieString($cookies);

        $content = [];
        foreach ($media as $mediaItem) {
            $content[] = $mediaItem;
        }
        $content[] = ['type' => 'text', 'text' => $prompt];

        $this->addMessage('user', $content);

        $data = new ArrayObject([
            'prompt' => $prompt,
            'attachments' => $media
        ]);

        $cURL = new ClientURL($chatUrl);
        $cURL->option
            ->setURL($chatUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setContentTypeJson()
            ->setPostMethod()
            ->setPostField(JSONHandler::encode($data))
            ->setHeaders([
                'Referer: https://claude.ai/',
                'Origin: https://claude.ai',
                'Accept: */*',
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }

    /**
     * Send a prompt to the Claude API.
     *
     * @param string|StringObject $prompt
     * @param array $media
     *
     * @return ResponseBody
     * @throws Exception
     */
    public function requestPrompt(string|StringObject $prompt, array $media = []): ResponseBody
    {
        $profileUrl = "https://api.anthropic.com/v1/messages";

        $content = [];

        foreach ($media as $mediaItem) {
            $content[] = $mediaItem;
        }

        $content[] = ['type' => 'text', 'text' => $prompt];

        $this->addMessage('user', $content);

        $data = new ArrayObject([
            'model' => $this->model,
            'max_tokens' => 4096,
            'messages' => $this->messages
        ]);

        if (!empty($this->systemPrompt)) {
            $data['system'] = $this->systemPrompt;
        }

        $cURL = new ClientURL($profileUrl);
        $cURL->option
            ->setURL($profileUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setContentTypeJson()
            ->setPostMethod()
            ->setPostField(JSONHandler::encode($data))
            ->setHeaders([
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01'
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }
}