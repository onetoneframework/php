<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * ChartLyrics public API client
 * Uses the SearchLyricDirect SOAP/REST endpoint which returns an XML response
 */
class ChartLyrics
{
    private const API_URL = "http://api.chartlyrics.com/apiv1.asmx/SearchLyricDirect";
    private const TIMEOUT = 5000;

    /**
     * Normalize text to a hyphen-separated slug
     *
     * @param string $text Raw metadata string
     * @return string Hyphen-separated slug
     */
    private function clean(string $text): string
    {
        $text = preg_replace('/\(.*\)|\{.*\}|\[.*\]|【.*】/', '', $text) ?? '';
        $text = mb_strtolower(trim(normalizer_normalize($text, \Normalizer::FORM_C) ?: $text));
        $text = preg_replace('/[^a-z0-9\- ]/', '', $text) ?? '';
        $text = str_replace('@', 'at', $text);
        $text = str_replace('&', 'and', $text);
        $text = str_replace(' ', '-', $text);

        return $text;
    }

    /**
     * Call the SearchLyricDirect endpoint and return the raw XML response
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Raw XML response body containing a <Lyric> element
     * @throws Exception
     */
    public function request(string $artist, string $title): string
    {
        $query = http_build_query([
            "artist" => $this->clean($artist),
            "song" => $this->clean($title),
        ]);
        $url = self::API_URL . "?" . $query;

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
            throw new Exception("ChartLyrics request failed: " . $e->getMessage());
        }
    }

    /**
     * Build the canonical API URL without fetching it
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Full URL string
     */
    public function buildUrl(string $artist, string $title): string
    {
        return self::API_URL . "?artist=" . $this->clean($artist) . "&song=" . $this->clean($title);
    }
}
