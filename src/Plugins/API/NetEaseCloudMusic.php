<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;

/**
 * NetEase Cloud Music API client
 * Uses the Linux API forward endpoint with AES-ECB encryption for authentication
 *
 * @see https://github.com/Binaryify/NeteaseCloudMusicApi
 */
class NetEaseCloudMusic
{
    private const LINUX_API_KEY     = "rFgB&h#%2?^eDg:Q";
    private const ANONYMOUS_TOKEN   = "bf8bfeabb1aa84f9c8c3906c04a04fb864322804c83f5d607e91a04eae463c9436bd1a17ec353cf780b396507a3f7464e8a60f4bbc019437993166e004087dd32d1490298caf655c2353e58daa0bc13cc7d5c198250968580b12c1b8817e3f5c807e650dd04abd3fb8130b7ae43fcc5b";
    private const LINUX_API_FORWARD = "https://music.163.com/api/linux/forward";
    private const USER_AGENT        = "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36";

    /**
     * Encrypt request payload using AES-ECB (Linux API format)
     *
     * @param array<string, mixed> $params Original request parameters
     * @param string $method HTTP method of the target endpoint
     * @param string $targetUrl Original target URL before forwarding
     * @return array<string, string> Encrypted payload ready for POST
     */
    private function encryptLinuxApi(array $params, string $method, string $targetUrl): array
    {
        $payload = json_encode([
            "method" => $method,
            "url"    => preg_replace('/\w*api/', "api", $targetUrl),
            "params" => $params,
        ], JSON_UNESCAPED_UNICODE);

        $encrypted = openssl_encrypt(
            $payload,
            "AES-128-ECB",
            self::LINUX_API_KEY,
            OPENSSL_RAW_DATA
        );

        return ["eparams" => strtoupper(bin2hex($encrypted))];
    }

    /**
     * Make a signed request to the NetEase Linux API forward endpoint
     *
     * @param string $method HTTP method for the target endpoint
     * @param string $targetUrl Actual NetEase API URL to forward to
     * @param array<string, mixed> $params Request parameters
     * @return string Raw JSON response
     * @throws Exception
     */
    public function request(string $method, string $targetUrl, array $params = []): string
    {
        try {
            $encryptedData = $this->encryptLinuxApi($params, $method, $targetUrl);

            $requestURL = new URLObject(self::LINUX_API_FORWARD);
            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setCustomMethod("POST")
                ->setHeaders([
                    "Content-Type" => "application/x-www-form-urlencoded",
                    "Referer"      => "https://music.163.com",
                    "User-Agent"   => self::USER_AGENT,
                    "Cookie"       => "MUSIC_A=" . self::ANONYMOUS_TOKEN,
                ])
                ->setPostField(http_build_query($encryptedData));

            $response = $cURL->execute();
            $cURL->close();

            return is_string($response) ? $response : '';
        } catch (Exception $e) {
            throw new Exception("NetEase request failed: " . $e->getMessage());
        }
    }

    /**
     * Search songs by title and artist
     *
     * @param string $title Song title
     * @param string $artist Artist name
     * @param int $limit Max number of results
     * @param int $offset Pagination offset
     * @return string Raw JSON response
     */
    public function searchSongs(string $title, string $artist, int $limit = 10, int $offset = 0): string
    {
        return $this->request("POST", "https://music.163.com/weapi/search/get", [
            "s"      => "{$title} {$artist}",
            "type"   => 1,
            "limit"  => $limit,
            "offset" => $offset,
        ]);
    }

    /**
     * Get lyric data for a song
     *
     * @param int $songId NetEase song ID
     * @return string Raw JSON response containing lrc/tlyric/klyric fields
     */
    public function getLyric(int $songId): string
    {
        return $this->request("POST", "https://music.163.com/weapi/song/lyric?lv=-1&kv=-1&tv=-1", [
            "id" => $songId,
        ]);
    }
}
