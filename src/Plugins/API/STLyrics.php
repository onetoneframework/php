<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * STLyrics.com unsynced lyric client (soundtrack-focused)
 * Searches by album name and track title (artist is not used in the URL)
 */
class STLyrics
{
    private const BASE_URL = "https://www.stlyrics.com/lyrics";
    private const TIMEOUT = 5000;

    /**
     * Normalize text to match STLyrics URL format
     * Strips brackets, special chars, and removes all spaces
     *
     * @param string $text Raw metadata string
     * @return string URL-safe slug (no separators)
     */
    private function clean(string $text): string
    {
        $text = preg_replace('/\(.*\)|\{.*\}|\[.*\]|【.*】/', '', $text) ?? '';
        $text = mb_strtolower(trim(\Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text));
        $text = preg_replace('/[^a-z0-9\- ]/', '', $text) ?? '';
        $text = str_replace('@', 'at', $text);
        $text = str_replace('&', 'and', $text);
        $text = str_replace(' ', '', $text);    // STLyrics: no separator

        return $text;
    }

    /**
     * Fetch the raw HTML page for a given album and title
     *
     * @param string $album Album name (used as the first path segment)
     * @param string $title Song title
     * @return string Raw HTML response body
     * @throws Exception
     */
    public function request(string $album, string $title): string
    {
        $url = self::BASE_URL . "/" . $this->clean($album) . "/" . $this->clean($title) . ".htm";

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
            throw new Exception("STLyrics request failed: " . $e->getMessage());
        }
    }

    /**
     * Build the canonical lyric page URL without fetching it
     *
     * @param string $album Album name
     * @param string $title Song title
     * @return string Full URL string
     */
    public function buildUrl(string $album, string $title): string
    {
        return self::BASE_URL . "/" . $this->clean($album) . "/" . $this->clean($title) . ".htm";
    }
}
