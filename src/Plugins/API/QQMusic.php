<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * QQ Music API client for searching songs and fetching lyrics
 *
 * @see https://github.com/jsososo/QQMusicApi
 */
class QQMusic
{
    private const SEARCH_URL = "https://c.y.qq.com/lyric/fcgi-bin/fcg_search_pc_lrc.fcg";
    private const LYRIC_V3_URL = "https://u.y.qq.com/cgi-bin/musicu.fcg";
    private const LYRIC_V2_URL = "https://c.y.qq.com/qqmusic/fcgi-bin/lyric_download.fcg";
    private const DEFAULT_HEADERS = ["Referer" => "https://y.qq.com"];

    /**
     * Make an HTTP request to QQ Music API
     *
     * @param string $method HTTP method
     * @param string $url Full request URL
     * @param array<string, mixed> $data POST body data
     * @param array<string, string> $headers Additional headers
     * @return string Raw response body
     * @throws Exception
     */
    public function request(string $method, string $url, array $data = [], array $headers = []): string
    {
        try {
            $requestURL = new URLObject($url);
            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setCustomMethod($method)
                ->setHeaders(array_merge(self::DEFAULT_HEADERS, $headers));

            if (!empty($data)) {
                $cURL->option->setPostField(json_encode($data, JSON_UNESCAPED_UNICODE));
            }

            $response = $cURL->execute();
            $cURL->close();

            return is_string($response) ? $response : '';
        } catch (Exception $e) {
            throw new Exception("QQMusic request failed: " . $e->getMessage());
        }
    }

    /**
     * Search songs by title and artist
     *
     * @param string $title Song title
     * @param string $artist Artist name
     * @param int $rangeMin Minimum result index
     * @param int $rangeMax Maximum result index
     * @return string Raw XML response
     */
    public function searchSongs(string $title, string $artist, int $rangeMin = 1, int $rangeMax = 20): string
    {
        $query = http_build_query([
            "SONGNAME" => $title,
            "SINGERNAME" => $artist,
            "TYPE" => 2,
            "RANGE_MIN" => $rangeMin,
            "RANGE_MAX" => $rangeMax,
        ]);

        return $this->request("GET", self::SEARCH_URL . "?" . $query);
    }

    /**
     * Fetch synced lyric data via V3 API (QRC format)
     *
     * @param int $songID Numeric song ID
     * @param string $songName Song name
     * @param string $singerName Singer name
     * @param string $albumName Album name
     * @param int $duration Track duration in seconds
     * @return string Raw JSON response
     */
    public function getLyricV3(int $songID, string $songName, string $singerName, string $albumName, int $duration): string
    {
        $postData = [
            "comm" => [
                "_channelid" => "0",
                "_os_version" => "6.2.9200-2",
                "authst" => "",
                "ct" => "19",
                "cv" => "1873",
                "patch" => "118",
                "psrf_access_token_expiresAt" => 0,
                "psrf_qqaccess_token" => "",
                "psrf_qqopenid" => "",
                "psrf_qqunionid" => "",
                "tmeAppID" => "qqmusic",
                "tmeLoginType" => 2,
                "uin" => "0",
                "wid" => "0",
            ],
            "music.musichallSong.PlayLyricInfo.GetPlayLyricInfo" => [
                "method" => "GetPlayLyricInfo",
                "module" => "music.musichallSong.PlayLyricInfo",
                "param" => [
                    "albumName" => base64_encode($albumName),
                    "crypt" => 1,
                    "ct" => 19,
                    "cv" => 1873,
                    "interval" => $duration,
                    "lrc_t" => 0,
                    "qrc" => 1,
                    "qrc_t" => 0,
                    "roma" => 1,
                    "roma_t" => 0,
                    "singerName" => base64_encode($singerName),
                    "songID" => $songID,
                    "songName" => base64_encode($songName),
                    "trans" => 1,
                    "trans_t" => 0,
                    "type" => -1,
                ],
            ],
        ];

        $query = http_build_query(["pcachetime" => (int) (microtime(true) * 1000)]);

        return $this->request("POST", self::LYRIC_V3_URL . "?" . $query, $postData, [
            "Host" => "u.y.qq.com",
        ]);
    }

    /**
     * Fetch lyric data via V2 API (QRC format, fallback)
     *
     * @param string $songId Song ID string
     * @return string Raw XML response
     */
    public function getLyricV2(string $songId): string
    {
        $query = http_build_query([
            "version" => "15",
            "miniversion" => "82",
            "lrctype" => "4",
            "musicid" => $songId,
        ]);

        return $this->request("GET", self::LYRIC_V2_URL . "?" . $query);
    }
}
