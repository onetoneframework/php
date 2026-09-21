<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\LLM\OpenCode;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\{ArrayObject, JSONHandler, StringObject};
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\LLM\OpenCode\ResponseBody;
use Clover\Enumeration\OpenCode\Model;
use Exception;

class Client
{
    private string $apiKey;
    private string $model;
    private array $messages = [];
    private string $systemPrompt = "";
    private float $temperature = 0.7;

    public function __construct(string|StringObject $apiKey, string|Model $model = 'opencode-3.5')
    {
        $this->apiKey = $apiKey;
        $this->model = ($model instanceof Model) ? $model : $model;
    }

    public function addMessage(string|StringObject $role, string|array $content): void
    {
        $this->messages[] = ['role' => $role, 'content' => $content];
    }

    public function setSystemPrompt(string $systemPrompt): void
    {
        $this->systemPrompt = $systemPrompt;
    }

    public function setTemperature(float $temperature): void
    {
        $this->temperature = $temperature;
    }

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

    public function requestPrompt(string|StringObject $prompt, array $media = []): ResponseBody
    {
        $profileUrl = "https://api.opencode.app/v1/chat/completions";

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
}