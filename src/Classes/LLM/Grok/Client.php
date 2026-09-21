<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\LLM\Grok;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\{ArrayObject, JSONHandler, StringObject};
use Exception;

/**
 * Class Client
 *
 * A client for interacting with the Grok LLM API.
 *
 * @package Clover\Classes\LLM\Grok
 */
class Client
{
    private string $apiKey;
    private string $model;

    /**
     * Client constructor.
     *
     * @param string|StringObject $apiKey
     * @param string|StringObject $model
     */
    public function __construct(string|StringObject $apiKey, string|StringObject $model = 'grok-beta')
    {
        $this->apiKey = $apiKey instanceof StringObject ? $apiKey->__toString() : $apiKey;
        $this->model = $model instanceof StringObject ? $model->__toString() : $model;
    }

    /**
     * Request a prompt completion from the Grok API.
     *
     * @param string|StringObject $prompt
     * @param string             $systemPrompt
     *
     * @return ResponseBody
     *
     * @throws Exception
     */
    public function requestPrompt(string|StringObject $prompt, string $systemPrompt = ""): ResponseBody
    {
        $url = "https://api.x.ai/v1/chat/completions";

        $messages = [];

        if (!empty($systemPrompt)) {
            $messages[] = [
                'role' => 'system',
                'content' => $systemPrompt
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $prompt instanceof StringObject ? $prompt->__toString() : $prompt
        ];

        $data = new ArrayObject([
            'model' => $this->model,
            'messages' => $messages
        ]);

        $cURL = new ClientURL($url);
        $cURL->option
            ->setURL($url)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setFollowRedirects()
            ->setReturnTransfer()
            ->setContentTypeJson()
            ->setPostMethod()
            ->setPostField(JSONHandler::encode($data))
            ->setHeader("Authorization", "Bearer " . $this->apiKey);

        $response = $cURL->executeWithDecode();
        $cURL->close();

        return new ResponseBody($response);
    }
}