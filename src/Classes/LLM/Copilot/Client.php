<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\Copilot;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\{ArrayObject, JSONHandler, StringObject};
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\LLM\Copilot\ResponseBody;
use Clover\Enumeration\Copilot\Model;
use Exception;

/**
 * Class Client
 *
 * A client for interacting with the GitHub Copilot LLM API.
 *
 * @package Clover\Classes\LLM\Copilot
 */
class Client
{
    private string $apiKey;
    private string $model;
    private array $messages = [];
    private string $systemPrompt = "";
    private float $temperature = 0.7;

    /**
     * Client constructor.
     *
     * @param string|StringObject $apiKey
     * @param Model|string|StringObject $model
     */
    public function __construct(string|StringObject $apiKey, string|Model $model = 'gpt-4o')
    {
        $this->apiKey = $apiKey;
        $this->model = ($model instanceof Model) ? $model : $model;
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
     * Set the temperature for the response generation.
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
     * Upload media file to be used in the conversation.
     *
     * @param string $filePath
     *
     * @return array
     *
     * @throws Exception
     */
    public function uploadMedia($filePath): array
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
     * Send a prompt to the Copilot API and get the response.
     *
     * @param string|StringObject $prompt
     * @param array $media
     *
     * @return ResponseBody
     *
     * @throws Exception
     */
    public function requestPrompt(string|StringObject $prompt, array $media = []): ResponseBody
    {
        $profileUrl = "https://api.githubcopilot.com/chat/completions";

        $content = [];

        if (!empty($media)) {
            foreach ($media as $mediaItem) {
                $content[] = $mediaItem;
            }
            $content[] = ['type' => 'text', 'text' => $prompt];
        } else {
            $content = $prompt;
        }

        $this->addMessage('user', $content);

        $messages = $this->messages;

        if (!empty($this->systemPrompt)) {
            array_unshift($messages, ['role' => 'system', 'content' => $this->systemPrompt]);
        }

        $data = new ArrayObject([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'stream' => false
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
                'Authorization: Bearer ' . $this->apiKey,
                'Editor-Version: vscode/1.95.0',
                'Editor-Plugin-Version: copilot-chat/0.22.0',
                'Openai-Organization: github-copilot',
                'Openai-Intent: conversation-panel',
                'VScode-SessionId: ' . bin2hex(random_bytes(16)),
                'VScode-MachineId: ' . bin2hex(random_bytes(16))
            ]);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }
}