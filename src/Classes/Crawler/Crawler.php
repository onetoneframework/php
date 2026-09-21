<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes;

use Clover\Classes\Cralwer\CrawlerDocument;
use Clover\Classes\Data\URLObject;
use RuntimeException;
use function is_string;
use function sprintf;

/**
 * HTML/XML web crawler with BeautifulSoup-style element selection.
 */
final class Crawler
{
    /**
     * Constructor
     * @param int $timeoutSeconds Maximum time to wait for a response (in seconds).
     * @param bool $verifySSL Whether to verify SSL certificates (default: false for broader compatibility).
     * @param string $defaultUserAgent Default User-Agent string to use for requests (default: a common browser UA for better compatibility).
     */
    public function __construct(
        private readonly int $timeoutSeconds = 15,
        private readonly bool $verifySSL = false,
        private readonly string $defaultUserAgent = 'Mozilla/5.0 (compatible; CloverCrawler/1.0)',
    ) {
    }

    /**
     * Fetch a URL via GET and return a parsed {@see CrawlerDocument}.
     * 
     * @param string $url The URL to fetch.
     * @param array<string> $headers Additional HTTP headers to include in the request.
     * @return CrawlerDocument The parsed document ready for element selection.
     */
    public function get(string $url, array $headers = []): CrawlerDocument
    {
        $html = $this->fetch($url, 'GET', [], $headers);
        return CrawlerDocument::fromHtml($html, $url);
    }

    /**
     * Fetch a URL via POST and return a parsed {@see CrawlerDocument}.
     *
     * @param array<string, string> $postFields Form fields to POST.
     */
    public function post(string $url, array $postFields = [], array $headers = []): CrawlerDocument
    {
        $html = $this->fetch($url, 'POST', $postFields, $headers);
        return CrawlerDocument::fromHtml($html, $url);
    }

    /**
     * Parse raw HTML/XML without making an HTTP request.
     * 
     * @param string $html The HTML or XML content to parse.
     * @param string $baseUrl Optional base URL for resolving relative links.
     * @return CrawlerDocument The parsed document ready for element selection.
     */
    public function parse(string $html, string $baseUrl = ''): CrawlerDocument
    {
        return CrawlerDocument::fromHtml($html, $baseUrl);
    }

    /**
     * Internal method to perform the HTTP request and return the raw response.
     * 
     * @param array<string, string> $postFields Form fields to POST (if method is POST).
     * @param array<string>         $headers    Additional HTTP headers to include in the request.
     * 
     * @return string The raw response body from the HTTP request.
     * @throws RuntimeException If the HTTP request fails or returns an empty response.
     */
    private function fetch(string $url, string $method, array $postFields, array $headers): string
    {
        $urlObject = new URLObject($url);
        $cURL = new ClientURL($urlObject);

        $cURL->option
            ->setURL($urlObject)
            ->setSSLVerifyPeer($this->verifySSL)
            ->setSSLVerifyHost($this->verifySSL)
            ->setReturnTransfer()
            ->setFollowRedirects(true)
            ->setTimeout($this->timeoutSeconds)
            ->setUserAgent($this->defaultUserAgent);

        if ($headers !== []) {
            $cURL->option->setHeaders($headers);
        }

        if ($method === 'POST') {
            $cURL->option->setPostMethod()->setPostFields($postFields);
        } else {
            $cURL->option->setGetMethod();
        }

        $result = $cURL->execute();

        if (!is_string($result) || $result === '') {
            throw new RuntimeException(sprintf('Crawler: empty response from "%s" (HTTP %d)', $url, $cURL->getLastHttpCode()));
        }

        return $result;
    }
}
