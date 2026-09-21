<?php

declare(strict_types=1);

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use Exception;
use function is_string;
use function strlen;
use function chr;

/**
 * MiniLyrics API client
 * Uses a custom binary protocol (CompressedXML) with MD5-based request signing
 *
 * @see https://github.com/olee/minilyrics-proxy
 */
class MiniLyrics
{
    private const SEARCH_URL = "http://search.crintsoft.com/searchlyrics.htm";
    private const USER_AGENT = "MiniLyrics 7.6.41 for Foobar2000";
    private const SIGNING_KEY = "Mlv1clt4.0";
    private const CLIENT_ID = "MiniLyrics 7.6.41 for Foobar2000";

    /**
     * Build the CompressedXML (CXML) binary search payload
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Raw binary payload
     */
    private function buildSearchPayload(string $artist, string $title): string
    {
        $fields = [
            "filetype" => "lyrics",
            "artist" => $artist,
            "title" => $title,
            "client" => self::CLIENT_ID,
            "ProtoVer" => "0.9",
            "OnlyMatched" => "1",
            "ClientCharEncoding" => "utf-8",
        ];

        // Build string table
        $stringTable = "";
        $stringCount = 0;
        foreach ($fields as $k => $v) {
            $stringTable .= $k . "\x00" . $v . "\x00";
            $stringCount += 2;
        }

        // Body: ST header (2) + size(4) + count(4) + "searchV1\0"(9) + strings + tail guard(2)
        $rootName = "searchV1\x00";
        $bodySize = 2 + 4 + 4 + strlen($rootName) + strlen($stringTable) + 2;
        $body = "ST";
        $body .= pack("V", $bodySize - 2);
        $body .= pack("V", $stringCount + 1);
        $body .= $rootName;
        $body .= $stringTable;
        $body .= "CT";

        // Tail: count(4) + 0x02 + indices
        $tail = pack("V", $stringCount + 1 + 1 + 1);
        $tail .= chr(0x02);
        $tail .= chr(0x0a);
        $idx = 1;
        foreach ($fields as $k => $v) {
            $tail .= chr(0x0a + $idx++);
            $tail .= chr(0x0a + $idx++);
        }
        $tail .= chr(0x04);

        // MBXML1 file header: magic(6) + version(4) + totalSize(4)
        $innerSize = strlen($body) + strlen($tail);
        $header = "MBXML1";
        $header .= pack("V", 0x02);
        $header .= pack("V", 6 + 4 + 4 + $innerSize);

        return $header . $body . $tail;
    }

    /**
     * Encrypt the CXML payload for transmission
     *
     * @param string $data Raw CXML binary payload
     * @return string Encrypted binary payload
     */
    private function encryptPayload(string $data): string
    {
        $bytes = array_values(unpack("C*", $data));
        $byteSum = array_sum(array_map(function (int $b): int {
            return $b > 127 ? $b - 256 : $b;   // signed byte
        }, $bytes));
        $key = ((int) floor($byteSum / count($bytes))) & 0xFF;

        $signing = md5($data . self::SIGNING_KEY, true);
        $xored = implode("", array_map(fn($b) => chr($b ^ $key), $bytes));

        return chr(0x02) . chr($key) . chr(0x04) . "\x00\x00\x00" . $signing . $xored;
    }

    /**
     * Make a POST request to the MiniLyrics search endpoint
     *
     * @param string $artist Artist name
     * @param string $title Song title
     * @return string Raw encrypted binary response (decrypt separately)
     * @throws Exception
     */
    public function searchLyrics(string $artist, string $title): string
    {
        try {
            $payload = $this->buildSearchPayload($artist, $title);
            $encrypted = $this->encryptPayload($payload);

            $requestURL = new URLObject(self::SEARCH_URL);
            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setCustomMethod("POST")
                ->setHeaders(["User-Agent" => self::USER_AGENT])
                ->setPostField($encrypted);

            $response = $cURL->execute();
            $cURL->close();

            return is_string($response) ? $response : '';
        } catch (Exception $e) {
            throw new Exception("MiniLyrics search failed: " . $e->getMessage());
        }
    }

    /**
     * Download a lyric file from a MiniLyrics CDN link
     *
     * @param string $lyricUrl Full URL returned in the search response
     * @return string Raw lyric file body
     * @throws Exception
     */
    public function getLyric(string $lyricUrl): string
    {
        try {
            $requestURL = new URLObject($lyricUrl);
            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setCustomMethod("GET")
                ->setHeaders(["User-Agent" => self::USER_AGENT]);

            $response = $cURL->execute();
            $cURL->close();

            return is_string($response) ? $response : '';
        } catch (Exception $e) {
            throw new Exception("MiniLyrics download failed: " . $e->getMessage());
        }
    }
}
