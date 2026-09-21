<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * OldieLyrics.com unsynced lyric client
 * Constructs a direct URL from artist/title and fetches the page HTML
 */
class OldieLyrics
{
    private const BASE_URL = "http://oldielyrics.com/lyrics";
    private const TIMEOUT = 5000;

    /**
     * Normalize text to match OldieLyrics URL format
     * Strips brackets, special chars, and replaces spaces with underscores
     *
     * @param string $text Raw metadata string
     * @return string URL-safe slug
     */
    private function clean(string $text): string
    {
        $text = preg_replace('/\(.*\)|\{.*\}|\[.*\]|【.*】/', '', $text) ?? '';
        $text = mb_strtolower(trim(\Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text));
        $text = preg_replace('/[^a-z0-9\- ]/', '', $text) ?? '';
        $text = str_replace('@', 'at', $text);
        $text = str_replace('&', 'and', $text);
        $text = str_replace(' ', '_', $text);   // OldieLyrics: underscore separator

        return $text;
    }

    /**
     * Fetch the raw HTML page for a given artist and title
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Raw HTML response body
     * @throws Exception
     */
    public function request(string $artist, string $title): string
    {
        $url = self::BASE_URL . "/" . $this->clean($artist) . "/" . $this->clean($title) . ".html";

        try {
            $requestURL = new URLObject($url);
            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setCustomMethod("GET")
                ->setTimeout(self::TIMEOUT);

            $response = $cURL->execute();
            $cURL->close();

            return is_string($response) ? $response : '';
        } catch (Exception $e) {
            throw new Exception("OldieLyrics request failed: " . $e->getMessage());
        }
    }

    /**
     * Build the canonical lyric page URL without fetching it
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Full URL string
     */
    public function buildUrl(string $artist, string $title): string
    {
        return self::BASE_URL . "/" . $this->clean($artist) . "/" . $this->clean($title) . ".html";
    }
}
