<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Streaming;

use Clover\Classes\Header;
use Clover\Classes\Streaming\Buffer\FileRingBuffer;
use Exception;
use function strlen;

/**
 * Class FileBaseListener
 *
 * A simple file-based listener that reads from a ring buffer and streams the data to clients.
 */
class FileBaseListener
{
    // Ring buffer for streaming data
    private FileRingBuffer $buffer;

    // Bitrate for streaming (in kbps)
    private int $bitrate = 128;

    // Size of each chunk to read and stream (in bytes)
    private int $chunkSize = 8192;

    /** FileBaseListener constructor.
     *
     * @param string $bufferFile
     * @param string $metaFile
     */
    public function __construct(string $bufferFile, string $metaFile)
    {
        $this->buffer = new FileRingBuffer($bufferFile, $metaFile);
    }

    /** 
     * Set the bitrate for streaming
     *
     * @param int $bitrate
     */
    public function setBitrate(int $bitrate): void
    {
        $this->bitrate = $bitrate;
    }

    /** 
     * Set the chunk size for streaming
     *
     * @param int $chunkSize
     */
    public function setChunkSize(int $chunkSize): void
    {
        $this->chunkSize = $chunkSize;
    }

    /**
     * Start streaming data from the buffer to clients
     *
     * @param string $icyName
     *
     * @throws Exception
     */
    public function stream($icyName = 'stream'): void
    {
        if (!$this->buffer->isReady()) {
            throw new Exception('Buffer is not ready');
        }

        Header::responseAudioMpegContentTypeHeader();
        Header::responseNoCacheNoStoreCacheControlHeader();
        Header::responseNoCachePragmaHeader();
        Header::responseExpiresHeader(0);
        Header::responseAcceptRangesHeader('none');
        Header::responseIcyNameHeader($icyName);
        Header::responseIcyBrHeader($this->bitrate);

        ini_set('output_buffering', 'off');
        ini_set('zlib.output_compression', false);
        ini_set('implicit_flush', 1);

        while (ob_get_level()) {
            ob_end_clean();
        }

        $readPos = $this->buffer->getSafeReadPosition();

        $bytesPerSecond = ($this->bitrate * 1024) / 8;
        $bytesStreamed = 0;
        $packetsSent = 0;
        $streamStartTime = microtime(true);

        while (true) {
            $meta = $this->buffer->getMeta();
            $writePos = $meta['write_pos'];

            if ($writePos > $readPos) {
                $available = $writePos - $readPos;
            } elseif ($writePos < $readPos) {
                $available = ($this->buffer->getSize() - $readPos) + $writePos;
            } else {
                $available = 0;
            }

            if ($available < $this->chunkSize) {
                usleep(50000);

                if (connection_status() != CONNECTION_NORMAL) {
                    break;
                }

                continue;
            }

            $data = $this->buffer->read($readPos, $this->chunkSize);

            if (!$data || strlen($data) <= 0) {
                continue;
            }

            $expectedTime = $streamStartTime + ($bytesStreamed / $bytesPerSecond);
            $currentTime = microtime(true);

            if ($currentTime < $expectedTime) {
                $waitTime = ($expectedTime - $currentTime) * 5000;
                if ($waitTime > 0) {
                    usleep((int) $waitTime);
                }
            }

            echo $data;
            @flush();

            $readPos = ($readPos + strlen($data)) % $this->buffer->getSize();
            $bytesStreamed += strlen($data);
            $packetsSent++;

            if ($packetsSent % 50 != 0) {
                continue;
            }

            if (connection_status() != CONNECTION_NORMAL) {
                break;
            }
        }
    }
}