<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * eLyrics.net unsynced lyric client
 * Slug rules: artist strips "the-", uses first-letter or "0-9" directory;
 * title converts numeric digits to English words
 */
class ELyrics
{
    private const BASE_URL = "https://www.elyrics.net/read";
    private const TIMEOUT = 5000;

    /**
     * Convert an integer (0–999) to its English word equivalent
     * Used to normalize song titles containing digits to match eLyrics URLs
     *
     * @param int $number Integer to convert (0–999)
     * @return string English word representation
     */
    private function num2Word(int $number): string
    {
        $ones = [
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
        ];
        $tens = [
            2 => 'twenty',
            3 => 'thirty',
            4 => 'forty',
            5 => 'fifty',
            6 => 'sixty',
            7 => 'seventy',
            8 => 'eighty',
            9 => 'ninety',
        ];

        if ($number === 0)
            return 'zero';

        $words = '';

        if ($number >= 100) {
            $words .= $this->num2Word((int) ($number / 100)) . 'hundred';
            $number %= 100;
        }

        if ($number > 0) {
            if ($words !== '')
                $words .= '-and-';
            if ($number < 20) {
                $words .= $ones[$number];
            } else {
                $words .= $tens[(int) ($number / 10)];
                if ($number % 10 > 0) {
                    $words .= '-' . $ones[$number % 10];
                }
            }
        }

        return $words;
    }

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
     *
     * @param string $artist Artist name
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
            throw new Exception("eLyrics request failed: " . $e->getMessage());
        }
    }

    /**
     * Build the canonical eLyrics page URL without fetching it
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Full URL string
     */
    public function buildUrl(string $artist, string $title): string
    {
        $artistSlug = str_replace('the-', '', $this->clean($artist));
        $artistLetter = is_numeric($artistSlug[0] ?? '') ? '0-9' : ($artistSlug[0] ?? 'a');

        // Convert digit sequences in the title to English words
        $titleSlug = preg_replace_callback('/\d+/', function (array $m): string {
            return $this->num2Word((int) $m[0]);
        }, $this->clean($title)) ?? $this->clean($title);

        return self::BASE_URL . "/{$artistLetter}/{$artistSlug}-lyrics/{$titleSlug}-lyrics.html";
    }
}
