<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Streaming;

use Clover\Classes\Streaming\Buffer\FileRingBuffer;
use Exception;
use function strlen;

/**
 * Class FileBaseBroadcaster
 *
 * A simple file-based broadcaster that reads from a music file and writes to a ring buffer for streaming.
 */
class FileBaseBroadcaster
{
    // Ring buffer for streaming data
    private FileRingBuffer $buffer;

    // Path to the music file to be streamed
    private string $musicFile;

    /** FileBaseBroadcaster constructor.
     *
     * @param string $musicFile
     * @param string $bufferFile
     * @param string $metaFile
     */
    public function __construct(string $musicFile, string $bufferFile, string $metaFile)
    {
        $this->musicFile = $musicFile;
        $this->buffer = new FileRingBuffer($bufferFile, $metaFile);
    }

    /**
     * Start broadcasting the music file by filling the buffer and continuously writing data to it.
     *
     * @throws Exception
     */
    public function start(): never
    {
        if (!file_exists($this->musicFile)) {
            throw new Exception("Cannot found file: {$this->musicFile}\n");
        }

        $this->buffer->init();
        $this->fillInitialBuffer();
        $this->buffer->setReady();

        while (true) {
            $fp = fopen($this->musicFile, 'rb');

            while (!feof($fp)) {
                $data = fread($fp, 8192);
                if ($data) {
                    $this->buffer->write($data);
                    usleep(50000);
                }
            }

            fclose($fp);
        }
    }

    /**
     * Fill the initial buffer with data from the music file
     *
     * @param int $bufferSize
     */
    private function fillInitialBuffer(int $bufferSize = 2097152): void
    {
        $fp = fopen($this->musicFile, 'rb');
        $filled = 0;

        while ($filled < $bufferSize && !feof($fp)) {
            $data = fread($fp, 8192);

            if ($data) {
                $this->buffer->write($data);
                $filled += strlen($data);
            }
        }

        fclose($fp);
    }
}