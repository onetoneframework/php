<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * Lololyrics.com unsynced lyric client
 * Two-step flow: fetch the artist page to resolve a numeric lyric ID,
 * then fetch the lyric page by that ID
 * Uses raw (non-cleaned) metadata; preserves a wider character set in slugs
 */
class Lololyrics
{
    private const BASE_URL = "https://www.lololyrics.com";
    private const TIMEOUT = 5000;

    /**
     * Normalize text preserving most printable ASCII (Lololyrics uses raw metadata)
     *
     * @param string $text Raw metadata string
     * @return string Cleaned string (no URL encoding applied here)
     */
    private function clean(string $text): string
    {
        $text = preg_replace('/\(.*\)|\{.*\}|\[.*\]|【.*】/', '', $text) ?? '';
        $text = mb_strtolower(trim(normalizer_normalize($text, \Normalizer::FORM_C) ?: $text));
        // Lololyrics preserves a broader character set than other providers
        $text = preg_replace('/[^a-z0-9\- !#$%\'()*+,.\/:\-?@\[\]^_|~.]/', '', $text) ?? '';
        $text = str_replace('@', 'at', $text);

        return $text;
    }

    /**
     * Make an HTTP GET request
     *
     * @param string $url Full request URL
     * @return string Raw response body
     * @throws Exception
     */
    private function get(string $url): string
    {
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
            throw new Exception("Lololyrics request failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch the artist page HTML used to resolve a numeric lyric ID
     * Parse the response to find a link matching /lyrics/{id}.html for the desired title
     *
     * @param string $rawArtist Raw artist name (not cleaned by the caller)
     * @return string Raw HTML response body
     * @throws Exception
     */
    public function getArtistPage(string $rawArtist): string
    {
        $artist = $this->clean($rawArtist);
        return $this->get(self::BASE_URL . "/artist/" . $artist);
    }

    /**
     * Fetch the lyric page HTML by a resolved numeric lyric ID
     *
     * @param string $lyricId Numeric lyric ID extracted from the artist page
     * @return string Raw HTML response body
     * @throws Exception
     */
    public function getLyricPage(string $lyricId): string
    {
        return $this->get(self::BASE_URL . "/lyrics/" . $lyricId . ".html");
    }

    /**
     * Build the artist page URL without fetching it
     *
     * @param string $rawArtist Raw artist name
     * @return string Full URL string
     */
    public function buildArtistUrl(string $rawArtist): string
    {
        return self::BASE_URL . "/artist/" . $this->clean($rawArtist);
    }

    /**
     * Build the lyric page URL without fetching it
     *
     * @param string $lyricId Numeric lyric ID
     * @return string Full URL string
     */
    public function buildLyricUrl(string $lyricId): string
    {
        return self::BASE_URL . "/lyrics/" . $lyricId . ".html";
    }
}
