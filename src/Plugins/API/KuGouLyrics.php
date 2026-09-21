<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * KuGou (酷狗音乐) synced lyric client
 * Two-step flow: search by keyword+duration to get candidates,
 * then download the KRC lyric binary by ID + accesskey
 */
class KuGouLyrics
{
    private const SEARCH_URL = "http://lyrics.kugou.com/search";
    private const DOWNLOAD_URL = "http://lyrics.kugou.com/download";

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
                ->setCustomMethod("GET");

            $response = $cURL->execute();
            $cURL->close();

            return is_string($response) ? $response : '';
        } catch (Exception $e) {
            throw new Exception("KuGou request failed: " . $e->getMessage());
        }
    }

    /**
     * Search for lyric candidates by artist, title, and track duration
     * Returns a JSON response containing a "candidates" array with id and accesskey fields
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @param int $durationMs Track duration in milliseconds
     * @return string Raw JSON response
     * @throws Exception
     */
    public function searchLyrics(string $artist, string $title, int $durationMs): string
    {
        $query = http_build_query([
            "ver" => "1",
            "man" => "yes",
            "client" => "pc",
            "keyword" => "{$artist}-{$title}",
            "duration" => $durationMs,
            "hash" => "",
        ]);

        return $this->get(self::SEARCH_URL . "?" . $query);
    }

    /**
     * Download a KRC lyric file by candidate ID and access key
     * Returns a JSON response with a base64-encoded "content" field (KRC binary)
     *
     * @param string $id Candidate lyric ID from search results
     * @param string $accessKey Access key from search results
     * @return string Raw JSON response
     * @throws Exception
     */
    public function downloadLyric(string $id, string $accessKey): string
    {
        $query = http_build_query([
            "ver" => "1",
            "client" => "pc",
            "id" => $id,
            "accesskey" => $accessKey,
            "fmt" => "krc",
            "charset" => "utf8",
        ]);

        return $this->get(self::DOWNLOAD_URL . "?" . $query);
    }
}
