<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * Bandcamp unsynced lyric client
 * Artist name is used as the subdomain with all hyphens stripped;
 * title uses standard hyphen-separated slugs
 */
class BandcampLyrics
{
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
     * Fetch the raw HTML page for a given artist and title
     * Artist is used as a Bandcamp subdomain (hyphens stripped)
     *
     * @param string $artist Artist name (used as subdomain, hyphens stripped)
     * @param string $title Song title
     * @return string Raw HTML response body
     * @throws Exception
     */
    public function request(string $artist, string $title): string
    {
        $url = $this->buildUrl($artist, $title);

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
            throw new Exception("Bandcamp request failed: " . $e->getMessage());
        }
    }

    /**
     * Build the canonical Bandcamp track URL without fetching it
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Full URL string
     */
    public function buildUrl(string $artist, string $title): string
    {
        $subdomain = str_replace('-', '', $this->clean($artist));  // Bandcamp: no hyphens in subdomain
        $slug      = $this->clean($title);

        return "https://{$subdomain}.bandcamp.com/track/{$slug}";
    }
}
