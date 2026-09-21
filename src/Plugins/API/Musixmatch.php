<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * Musixmatch desktop API client
 * Uses the internal desktop app endpoint with cookie-based access
 */
class Musixmatch
{
    private const BASE_URL = "https://apic-desktop.musixmatch.com/ws/1.1/";
    private const APP_ID = "web-desktop-app-v1.0";
    private const COOKIE = "AWSELBCORS=0; AWSELB=0";

    private string $token;

    /**
     * Initialize with an optional pre-fetched user token
     *
     * @param string $token Musixmatch user token (empty = fetch via getToken())
     */
    public function __construct(string $token = "")
    {
        $this->token = $token;
    }

    /**
     * Make a GET request to a Musixmatch desktop API endpoint
     *
     * @param string $endpoint Endpoint name (e.g. "track.search")
     * @param array<string, mixed> $params Query parameters
     * @return string Raw JSON response
     * @throws Exception
     */
    public function request(string $endpoint, array $params = []): string
    {
        try {
            $params = array_merge([
                "user_language" => "en",
                "app_id" => self::APP_ID,
                "format" => "json",
                "t" => (int) (microtime(true) * 1000),
            ], $params);

            $url = self::BASE_URL . $endpoint . "?" . http_build_query($params);

            $requestURL = new URLObject($url);
            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setCustomMethod("GET")
                ->setHeaders(["cookie" => self::COOKIE]);

            $response = $cURL->execute();
            $cURL->close();

            return is_string($response) ? $response : '';
        } catch (Exception $e) {
            throw new Exception("Musixmatch request failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch a user token required for all other API calls
     *
     * @return string Raw JSON response containing user_token
     */
    public function getToken(): string
    {
        return $this->request("token.get");
    }

    /**
     * Search for tracks
     *
     * @param string $title Song title
     * @param string $artist Artist name
     * @param string $album Album name
     * @return string Raw JSON response
     */
    public function searchTracks(string $title, string $artist, string $album = ""): string
    {
        return $this->request("track.search", [
            "q_track" => $title,
            "q_artist" => $artist,
            "q_album" => $album,
            "f_has_lyrics" => 1,
            "subtitle_format" => "lrc",
            "usertoken" => $this->token,
        ]);
    }

    /**
     * Fetch plain lyrics for a track
     *
     * @param int $trackId Musixmatch commontrack_id
     * @return string Raw JSON response
     */
    public function getLyric(int $trackId): string
    {
        return $this->request("track.lyrics.get", [
            "commontrack_id" => $trackId,
            "usertoken" => $this->token,
        ]);
    }

    /**
     * Fetch synced subtitle (LRC format) for a track
     *
     * @param int $trackId Musixmatch commontrack_id
     * @return string Raw JSON response
     */
    public function getSubtitle(int $trackId): string
    {
        return $this->request("track.subtitle.get", [
            "commontrack_id" => $trackId,
            "subtitle_format" => "lrc",
            "usertoken" => $this->token,
        ]);
    }
}
