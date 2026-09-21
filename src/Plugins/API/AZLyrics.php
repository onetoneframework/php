<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * AZLyrics.com unsynced lyric client
 * Slug rules: strips "a_" and "the_" prefixes, removes all remaining spaces
 */
class AZLyrics
{
    private const BASE_URL = "https://azlyrics.com/lyrics";
    private const TIMEOUT  = 5000;

    /**
     * Normalize text to match AZLyrics URL format
     * Strips brackets, special chars, strips "a_"/"the_" prefixes, removes spaces
     *
     * @param string $text Raw metadata string
     * @return string URL-safe slug
     */
    private function clean(string $text): string
    {
        $text = preg_replace('/\(.*\)|\{.*\}|\[.*\]|【.*】/', '', $text) ?? '';
        $text = mb_strtolower(trim(normalizer_normalize($text, \Normalizer::FORM_C) ?: $text));
        $text = preg_replace('/[^a-z0-9\- ]/', '', $text) ?? '';
        $text = str_replace('@', 'at', $text);
        $text = str_replace('&', 'and', $text);
        $text = str_replace(' ', '_', $text);           // temporary separator
        $text = preg_replace('/^a_/', '', $text) ?? $text;
        $text = preg_replace('/^the_/', '', $text) ?? $text;
        $text = str_replace('_', '', $text);            // AZLyrics: no separator

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
            throw new Exception("AZLyrics request failed: " . $e->getMessage());
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
