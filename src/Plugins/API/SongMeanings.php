<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * SongMeanings.com unsynced lyric client
 * First searches by artist+title to resolve a song ID,
 * then fetches the lyric page directly using that ID
 */
class SongMeanings
{
    private const SEARCH_URL = "https://songmeanings.com/query/";
    private const LYRIC_URL = "https://songmeanings.com/songs/view/";
    private const TIMEOUT = 5000;

    /**
     * Normalize text to match SongMeanings URL/query format
     * Strips brackets, special chars, and replaces spaces with hyphens
     *
     * @param string $text Raw metadata string
     * @return string Cleaned slug
     */
    private function clean(string $text): string
    {
        $text = preg_replace('/\(.*\)|\{.*\}|\[.*\]|【.*】/', '', $text) ?? '';
        $text = mb_strtolower(trim(\Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text));
        $text = preg_replace('/[^a-z0-9\- ]/', '', $text) ?? '';
        $text = str_replace('@', 'at', $text);
        $text = str_replace('&', 'and', $text);
        $text = str_replace(' ', '-', $text);

        return $text;
    }

    /**
     * Make an HTTP GET request
     *
     * @param string $url Full request URL
     * @return string Raw response body
     * @throws Exception
     */
    public function request(string $url): string
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
            throw new Exception("SongMeanings request failed: " . $e->getMessage());
        }
    }

    /**
     * Search for songs and return the results page HTML
     * The HTML contains links of the form /songs/view/{id}/ to extract the song ID
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Raw HTML response body
     */
    public function searchSongs(string $artist, string $title): string
    {
        $query = http_build_query([
            "query" => $this->clean($artist) . "+" . $this->clean($title),
            "type" => "songtitles",
        ]);

        return $this->request(self::SEARCH_URL . "?" . $query);
    }

    /**
     * Fetch the lyric page HTML for a resolved song ID
     *
     * @param string $songId Numeric song ID extracted from search results
     * @return string Raw HTML response body
     */
    public function getLyricPage(string $songId): string
    {
        return $this->request(self::LYRIC_URL . $songId);
    }

    /**
     * Build the canonical lyric page URL without fetching it
     *
     * @param string $songId Numeric song ID
     * @return string Full URL string
     */
    public function buildUrl(string $songId): string
    {
        return self::LYRIC_URL . $songId;
    }
}
