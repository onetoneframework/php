<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\ClientURL;

class Skeb
{
    /**
     * Executes a POST request to download a Skeb attachment.
     * 
     * @param string $articleId
     * @param string $attachmentId
     * @param string $key Bearer token
     * @return mixed
     */
    public function download(string $articleId, string $attachmentId, string $key): mixed
    {
        $requestUrl = "https://skeb.jp/api/requests/{$articleId}/download";
        $postData = json_encode([
            "attachment_id" => (int) $attachmentId,
            "type" => "original"
        ]);

        return $this->executeRequest($requestUrl, 'POST', $key, $postData);
    }

    /**
     * Fetches user/creator profile information.
     * 
     * @param string $username The user's screen name (alias) on Skeb
     * @return mixed
     */
    public function getUser(string $username): mixed
    {
        $requestUrl = "https://skeb.jp/api/users/{$username}";
        return $this->executeRequest($requestUrl, 'GET');
    }

    /**
     * Fetches a user's works/portfolio.
     * 
     * @param string $username The user's screen name (alias) on Skeb
     * @return mixed
     */
    public function getWorks(string $username): mixed
    {
        $requestUrl = "https://skeb.jp/api/users/{$username}/works";
        return $this->executeRequest($requestUrl, 'GET');
    }

    /**
     * Fetches details of a specific request.
     * 
     * @param string $articleId
     * @param string|null $key Bearer token (optional, for private details)
     * @return mixed
     */
    public function getRequest(string $articleId, ?string $key = null): mixed
    {
        $requestUrl = "https://skeb.jp/api/requests/{$articleId}";
        return $this->executeRequest($requestUrl, 'GET', $key);
    }

    /**
     * Helper method to execute Skeb API requests with required mocking headers.
     * 
     * @param string $url Target URL
     * @param string $method HTTP Method (GET/POST)
     * @param string|null $key Bearer token for Authorization
     * @param string|null $postData JSON body for POST requests
     * @return mixed
     */
    private function executeRequest(string $url, string $method = 'GET', ?string $key = null, ?string $postData = null): mixed
    {
        $cURL = new ClientURL($url);
        
        $cURL->option
            ->setHeader('sec-ch-ua', '"Chromium";v="134", "Not:A-Brand";v="24", "Google Chrome";v="134"')
            ->setHeader('sec-ch-ua-mobile', '?0')
            ->setHeader('Accept', 'application/json, text/plain, */*')
            ->setHeader('Accept-Encoding', 'gzip, deflate, br, zstd')
            ->setHeader('Accept-Language', 'ko,zh;q=0.9,en-US;q=0.8,en;q=0.7,zh-CN;q=0.6')
            ->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/134.0.0.0 Safari/537.36')
            ->setContentTypeApplicationJson()
            ->setHeader('Referer', 'https://skeb.jp')
            ->setHeader('Origin', 'https://skeb.jp')
            ->setURL($url)
            ->setReturnTransfer();

        if ($key !== null) {
            $cURL->option->setHeader('Authorization', "Bearer {$key}");
        }

        if ($method === 'POST') {
            $cURL->option->setPostMethod();
            if ($postData !== null) {
                $cURL->option->setPostField($postData);
            }
        } else {
            $cURL->option->setGetMethod();
        }

        $response = $cURL->execute();
        $statusCode = $cURL->information()->getStatusCode();
        $cURL->close();

        return $response;
    }
}