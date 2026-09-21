<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * DarkLyrics.com unsynced lyric client (heavy metal / rock focus)
 * URL is built from artist + album; the specific track is identified inside the page
 * Slug rules: all spaces and separators removed entirely
 */
class DarkLyrics
{
    private const BASE_URL = "http://www.darklyrics.com/lyrics";
    private const TIMEOUT = 5000;

    /**
     * Normalize text to match DarkLyrics URL format
     * Strips brackets, special chars, removes all spaces/separators
     *
     * @param string $text Raw metadata string
     * @return string Separator-free slug
     */
    private function clean(string $text): string
    {
        $text = preg_replace('/\(.*\)|\{.*\}|\[.*\]|【.*】/', '', $text) ?? '';
        $text = mb_strtolower(trim(normalizer_normalize($text, \Normalizer::FORM_C) ?: $text));
        $text = preg_replace('/[^a-z0-9\- ]/', '', $text) ?? '';
        $text = str_replace('@', 'at', $text);
        $text = str_replace('&', 'and', $text);
        $text = str_replace(' ', '', $text);    // DarkLyrics: no separator

        return $text;
    }

    /**
     * Fetch the raw HTML album page containing all track lyrics
     * The caller is responsible for extracting the specific track section by title
     *
     * @param string $artist Artist name
     * @param string $album Album name
     * @return string Raw HTML response body
     * @throws Exception
     */
    public function request(string $artist, string $album): string
    {
        $url = $this->buildUrl($artist, $album);

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
            throw new Exception("DarkLyrics request failed: " . $e->getMessage());
        }
    }

    /**
     * Build the canonical album lyric page URL without fetching it
     *
     * @param string $artist Artist name
     * @param string $album Album name
     * @return string Full URL string
     */
    public function buildUrl(string $artist, string $album): string
    {
        return self::BASE_URL . "/" . $this->clean($artist) . "/" . $this->clean($album) . ".html";
    }
}
