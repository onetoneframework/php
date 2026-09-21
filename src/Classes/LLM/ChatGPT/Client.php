<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\ChatGPT;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Data\StringObject;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Enumeration\OpenAI\Model;
use Exception;
use function sprintf;

/**
 * Class Client
 *
 * A client for interacting with the ChatGPT API.
 *
 * @package Clover\Classes\LLM\ChatGPT
 */
class Client
{
    private string $apiKey;
    private string $model;
    private array $messages = [];
    private string $systemPrompt = "";
    private float $temperature = 0.7;
    private ?string $accessToken = null;

    /**
     * Client constructor.
     *
     * @param string|StringObject $apiKey
     * @param string|Model       $model
     */
    public function __construct(string|StringObject $apiKey, string|Model $model = 'gpt-4o')
    {
        $this->apiKey = (string) $apiKey;
        $this->model = ($model instanceof Model) ? $model : (string) $model;
    }

    /**
     * Add a message to the conversation.
     *
     * @param string|StringObject $role
     * @param string|array       $content
     * 
     * @return void
     */
    public function addMessage(string|StringObject $role, string|array $content): void
    {
        $this->messages[] = ['role' => (string) $role, 'content' => $content];
    }

    /**
     * Set the system prompt for the conversation.
     *
     * @param string|StringObject $systemPrompt
     * 
     * @return void
     */
    public function setSystemPrompt(string|StringObject $systemPrompt): void
    {
        $this->systemPrompt = (string) $systemPrompt;
    }

    /**
     * Set the temperature for the model's responses.
     *
     * @param float $temperature
     * 
     * @return void
     */
    public function setTemperature(float $temperature): void
    {
        $this->temperature = $temperature;
    }

    /**
     * Upload media (image) to be used in the conversation.
     *
     * @param string $filePath
     * @param string $fileName
     *
     * @return array
     *
     * @throws Exception
     */
    public function uploadMedia(string $filePath, string $fileName): array
    {
        if (!FileHandler::isExists($filePath)) {
            throw new Exception('File is not exists');
        }

        $mimeType = FileHandler::getMIMEContentType($filePath);
        $fileContent = file_get_contents($filePath);
        $base64Data = base64_encode($fileContent);

        if (strpos($mimeType, 'image/') !== 0) {
            throw new Exception('Only image files are supported');
        }

        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => "data:{$mimeType};base64,{$base64Data}"
            ]
        ];
    }

    /**
     * Get usage statistics from OpenAI API.
     *
     * @param string $startDate
     * @param string $endDate
     *
     * @return mixed
     */
    public function getUsage(string $startDate, string $endDate): mixed
    {
        $profileUrl = sprintf("https://api.openai.com/v1/usage?start_date=%s&end_date=%s", $startDate, $endDate);

        $cURL = new ClientURL($profileUrl);
        $cURL->option
            ->setURL($profileUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setHeaders([
                'Authorization: Bearer ' . $this->apiKey
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return $response;
    }

    /**
     * Build a cookie string from an associative array.
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
     * Retrieve the access token using provided cookies.
     *
     * @param array $cookies
     *
     * @return string
     *
     * @throws Exception
     */
    private function getAccessToken(array $cookies): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $sessionUrl = "https://chatgpt.com/api/auth/session";
        $cookieString = $this->buildCookieString($cookies);

        $cURL = new ClientURL($sessionUrl);
        $cURL->option
            ->setURL($sessionUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setHeaders([
                'Referer: https://chatgpt.com/',
                'Origin: https://chatgpt.com',
                'Accept: */*',
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        if (!isset($response['accessToken'])) {
            throw new Exception('accessToken not found in session response');
        }

        $this->accessToken = $response['accessToken'];
        return $this->accessToken;
    }

    /**
     * Get Codex usage statistics.
     *
     * @param array $cookies
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getCodexUsage(array $cookies): mixed
    {
        $usageUrl = "https://chatgpt.com/backend-api/wham/usage";
        $cookieString = $this->buildCookieString($cookies);
        $accessToken = $this->getAccessToken($cookies);

        $cURL = new ClientURL($usageUrl);
        $cURL->option
            ->setURL($usageUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setHeaders([
                'Referer: https://chatgpt.com/',
                'Origin: https://chatgpt.com',
                'Accept: */*',
                'Authorization: Bearer ' . $accessToken,
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return $response;
    }

    /**
     * Send a Codex prompt request.
     *
     * @param string|StringObject $prompt
     * @param array $cookies
     * @param array $media
     *
     * @return ResponseBody
     *
     * @throws Exception
     */
    public function requestCodexPrompt(string|StringObject $prompt, array $cookies, array $media = []): ResponseBody
    {
        $chatUrl = "https://chatgpt.com/backend-api/conversation";
        $cookieString = $this->buildCookieString($cookies);
        $accessToken = $this->getAccessToken($cookies);

        $content = [];
        if (!empty($media)) {
            foreach ($media as $mediaItem) {
                $content[] = $mediaItem;
            }
            $content[] = ['type' => 'text', 'text' => (string) $prompt];
        } else {
            $content = (string) $prompt;
        }

        $this->addMessage('user', $content);

        $messages = $this->messages;
        if (!empty($this->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $this->systemPrompt]);
        }

        $data = new ArrayObject([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature
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
                'Referer: https://chatgpt.com/',
                'Origin: https://chatgpt.com',
                'Accept: */*',
                'Authorization: Bearer ' . $accessToken,
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }

    /**
     * Send a prompt request to OpenAI API.
     *
     * @param string|StringObject $prompt
     * @param array $media
     *
     * @return ResponseBody
     * @throws Exception
     */
    public function requestPrompt(string|StringObject $prompt, array $media = []): ResponseBody
    {
        $profileUrl = "https://api.openai.com/v1/chat/completions";

        $content = [];

        if (!empty($media)) {
            foreach ($media as $mediaItem) {
                $content[] = $mediaItem;
            }
            $content[] = ['type' => 'text', 'text' => (string) $prompt];
        } else {
            $content = (string) $prompt;
        }

        $this->addMessage('user', $content);

        $messages = $this->messages;

        if (!empty($this->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $this->systemPrompt]);
        }

        $data = new ArrayObject([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature
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
            ->setPostField(JSONHandler::encode($data))
            ->setHeaders([
                'Authorization: Bearer ' . $this->apiKey
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }

    /**
     * Get conversations from ChatGPT.
     *
     * @param array $cookies
     * @param int $offset
     * @param int $limit
     *
     * @return array
     *
     * @throws Exception
     */
    public function getConversations(array $cookies, int $offset = 0, int $limit = 28): array
    {
        $conversationsUrl = "https://chatgpt.com/backend-api/conversations?offset={$offset}&limit={$limit}";
        $cookieString = $this->buildCookieString($cookies);
        $accessToken = $this->getAccessToken($cookies);

        $cURL = new ClientURL($conversationsUrl);
        $cURL->option
            ->setURL($conversationsUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setHeaders([
                'Referer: https://chatgpt.com/',
                'Origin: https://chatgpt.com',
                'Accept: */*',
                'Authorization: Bearer ' . $accessToken,
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return $response;
    }

    /**
     * Send a Codex prompt request to a specific conversation.
     *
     * @param string|StringObject $prompt
     * @param string             $conversationId
     * @param array              $cookies
     * @param array              $media
     *
     * @return ResponseBody
     *
     * @throws Exception
     */
    public function requestCodexPromptToConversation(string|StringObject $prompt, string $conversationId, array $cookies, array $media = []): ResponseBody
    {
        $chatUrl = "https://chatgpt.com/backend-api/conversation";
        $cookieString = $this->buildCookieString($cookies);
        $accessToken = $this->getAccessToken($cookies);

        $content = [];
        if (!empty($media)) {
            foreach ($media as $mediaItem) {
                $content[] = $mediaItem;
            }
            $content[] = ['type' => 'text', 'text' => (string) $prompt];
        } else {
            $content = (string) $prompt;
        }

        $this->addMessage('user', $content);

        $messages = $this->messages;
        if (!empty($this->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $this->systemPrompt]);
        }

        $data = new ArrayObject([
            'conversation_id' => $conversationId,
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature
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
                'Referer: https://chatgpt.com/',
                'Origin: https://chatgpt.com',
                'Accept: */*',
                'Authorization: Bearer ' . $accessToken,
                'Cookie: ' . $cookieString
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }
}
