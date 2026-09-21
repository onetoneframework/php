<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\Audio\Mp3Parser;
use Clover\Classes\ClientURL;
use function count;
use function strlen;

/**
 * AlsongLyric class handles fetching and parsing lyrics from Alsong service for MP3 files.
 * This class processes audio files to extract MD5 checksums and queries the Alsong web service
 * to retrieve synchronized lyrics, then parses and formats them for use.
 */
class AlsongLyric
{
    /**
     * Path to the MP3 file being processed.
     * @var string
     */
    private string $filepath;

    /**
     * Flag indicating if the file is valid for processing.
     * @var bool
     */
    private bool $validFile = true;

    /**
     * Flag indicating if the lyrics are in single-line format.
     * @var bool
     */
    private bool $singleLineLyric = false;

    /**
     * Array containing parsed lyric lines with timestamps.
     * @var array<string>
     */
    private array $lines = [];

    /**
     * Flag indicating if the lyric loading process is in progress.
     * @var bool
     */
    private bool $loading = true;

    /**
     * Constructor for AlsongLyric class.
     * Initializes the lyric processor with the path to an MP3 file.
     *
     * @param string $filepath Path to the MP3 file to process.
     */
    public function __construct(string $filepath)
    {
        $this->filepath = $filepath;
        $this->_init();
    }

    /**
     * Initializes the lyric loading process.
     * This private method reads the MP3 file, calculates its MD5 checksum,
     * queries the Alsong web service, and parses the returned lyrics.
     */
    private function _init(): void
    {
        $file = fopen($this->filepath, "rb");
        if (!$file) {
            $this->validFile = false;
            $this->loading = false;
            return;
        }

        $firstBytes = fread($file, 100);
        $startOffset = Mp3Parser::skipID3v2Tag($firstBytes);

        fseek($file, $startOffset);
        $targetData = fread($file, 163840);
        fclose($file);

        $template = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://www.w3.org/2003/05/soap-envelope" xmlns:SOAP-ENC="http://www.w3.org/2003/05/soap-encoding" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:ns2="ALSongWebServer/Service1Soap" xmlns:ns1="ALSongWebServer" xmlns:ns3="ALSongWebServer/Service1Soap12">
<SOAP-ENV:Body>
<ns1:GetLyric7>
<ns1:encData>7c2d15b8f51ac2f3b2a37d7a445c3158455defb8a58d621eb77a3ff8ae4921318e49cefe24e515f79892a4c29c9a3e204358698c1cfe79c151c04f9561e945096ccd1d1c0a8d8f265a2f3fa7995939b21d8f663b246bbc433c7589da7e68047524b80e16f9671b6ea0faaf9d6cde1b7dbcf1b89aa8a1d67a8bbc566664342e12</ns1:encData>
<ns1:stQuery><ns1:strChecksum>{md5}</ns1:strChecksum><ns1:strVersion></ns1:strVersion><ns1:strMACAddress></ns1:strMACAddress><ns1:strIPAddress>192.168.1.5</ns1:strIPAddress></ns1:stQuery></ns1:GetLyric7></SOAP-ENV:Body></SOAP-ENV:Envelope>
EOD;

        $md5 = md5($targetData);
        $soapXml = str_replace('{md5}', $md5, $template);

        $curl = new ClientURL('http://lyrics.alsong.co.kr/alsongwebservice/service1.asmx');
        $curl->option
            ->setPostMethod()
            ->setReturnTransfer()
            ->setPostField($soapXml)
            ->setContentTypeXMLSoap();

        $responseContent = $curl->execute();
        $curl->close();

        $lyricResult = [];
        preg_match('/<strLyric>(.*?)<\/strLyric>/s', $responseContent, $lyricResult);

        if (count($lyricResult) > 1) {
            $lyricContent = $lyricResult[1];
            $lyricContent = str_replace("&lt;br&gt;", "\n", $lyricContent);
            $lyricLines = explode("\n", $lyricContent);

            $lines = [];
            foreach ($lyricLines as $each) {
                $lineResult = [];
                preg_match('/\[(\d{2}):(\d{2})(?:\.(\d{2,3}))?](.*)/', $each, $lineResult);
                if (count($lineResult) > 0) {
                    $minutes = (int) $lineResult[1];
                    $seconds = (int) $lineResult[2];
                    $milliseconds = isset($lineResult[3]) ? (int) $lineResult[3] : 0;
                    $timestamp = $minutes * 60 + $seconds + ($milliseconds / 100);
                    $lines[] = [$timestamp, $lineResult[4]];
                }
            }

            $hasBanner = true;
            $maxBannerCount = 3;
            $singleLine = true;
            $filteredLines = [];

            foreach ($lines as $each) {
                $line = trim($each[1]);
                if (!strlen($line)) {
                    continue;
                }
                if ($each[0] != 0) {
                    $hasBanner = false;
                }
                if ($each[0] == 0) {
                    if (!$hasBanner) {
                        continue;
                    } else {
                        if ($maxBannerCount > 0) {
                            $maxBannerCount--;
                        } else {
                            continue;
                        }
                    }
                }
                $filteredLines[] = [$each[0], html_entity_decode($line)];
            }

            $groupLine = [];
            foreach ($filteredLines as $each) {
                if (empty($groupLine) || $groupLine[count($groupLine) - 1][0] != $each[0]) {
                    $groupLine[] = [$each[0], []];
                }
                $groupLine[count($groupLine) - 1][1][] = $each[1];
                if (count($groupLine[count($groupLine) - 1][1]) > 1) {
                    $singleLine = false;
                }
            }

            usort($groupLine, function ($a, $b) {
                return $a[0] <=> $b[0];
            });

            $this->lines = $groupLine;
            $this->singleLineLyric = $singleLine;
        }

        $this->loading = false;
    }

    /**
     * Checks if the lyric loading process is currently in progress.
     *
     * @return bool True if loading is in progress, false otherwise.
     */
    public function isLoading(): bool
    {
        return $this->loading;
    }

    /**
     * Checks if the lyrics have been successfully loaded.
     *
     * @return bool True if lyrics are loaded and available, false otherwise.
     */
    public function isLoaded(): bool
    {
        return !$this->loading && !empty($this->lines);
    }

    /**
     * Checks if the loaded lyrics are in single-line format.
     *
     * @return bool True if lyrics are single-line, false if multi-line.
     */
    public function isSingleLineLyric(): bool
    {
        return $this->singleLineLyric;
    }

    /**
     * Checks if the provided file is valid for lyric processing.
     *
     * @return bool True if the file is valid, false otherwise.
     */
    public function isValidFile(): bool
    {
        return $this->validFile;
    }

    /**
     * Retrieves the parsed lyric lines with timestamps.
     *
     * @return array Array of lyric lines, each containing timestamp and text.
     */
    public function getLyric(): array
    {
        return $this->lines;
    }

    /**
     * Retrieves the file path of the MP3 file being processed.
     *
     * @return mixed The file path as a string.
     */
    public function getFilePath(): mixed
    {
        return $this->filepath;
    }
}