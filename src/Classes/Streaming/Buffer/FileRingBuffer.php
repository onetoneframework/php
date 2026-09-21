<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Streaming\Buffer;
use function strlen;

/**
 * Class FileRingBuffer
 *
 * @package Clover\Classes\Streaming\Buffer
 */
class FileRingBuffer
{
    // Ring buffer implemented using files for streaming data
    private string $bufferFile;

    // Meta file to store write position and other info
    private string $metaFile;

    // Size of the ring buffer in bytes
    private int $bufferSize = 10485760;

    /**
     * FileRingBuffer constructor.
     *
     * @param string $bufferFile
     * @param string $metaFile
     */
    public function __construct(string $bufferFile, string $metaFile)
    {
        $this->bufferFile = $bufferFile;
        $this->metaFile = $metaFile;
    }

    /**
     * Initialize the buffer and meta files
     */
    public function init(): void
    {
        file_put_contents($this->bufferFile, str_repeat("\0", $this->bufferSize));
        file_put_contents($this->metaFile, json_encode([
            'write_pos' => 0,
            'total_written' => 0,
            'start_time' => time(),
            'ready' => false
        ]));
    }

    /**
     * Mark the buffer as ready for reading
     */
    public function setReady(): void
    {
        $meta = $this->getMeta();
        $meta['ready'] = true;
        file_put_contents($this->metaFile, json_encode($meta));
    }

    /**
     * Write data to the ring buffer
     *
     * @param string $data
     */
    public function write(string $data): void
    {
        $meta = $this->getMeta();
        $len = strlen($data);
        $pos = $meta['write_pos'];

        $fp = fopen($this->bufferFile, 'r+b');
        if (!$fp) {
            return;
        }

        if ($pos + $len <= $this->bufferSize) {
            fseek($fp, $pos);
            fwrite($fp, $data);
        } else {
            $part1 = $this->bufferSize - $pos;
            fseek($fp, $pos);
            fwrite($fp, substr($data, 0, $part1));
            fseek($fp, 0);
            fwrite($fp, substr($data, $part1));
        }

        fclose($fp);

        $meta['write_pos'] = ($pos + $len) % $this->bufferSize;
        $meta['total_written'] += $len;
        file_put_contents($this->metaFile, json_encode($meta));
    }

    /**
     * Read data from the ring buffer
     *
     * @param int $pos
     * @param int $len
     *
     * @return bool|string|null
     */
    public function read(int $pos, int $len): bool|string|null
    {
        $fp = fopen($this->bufferFile, 'rb');
        if (!$fp) {
            return null;
        }

        $pos = $pos % $this->bufferSize;
        fseek($fp, $pos);

        if ($pos + $len <= $this->bufferSize) {
            $data = fread($fp, $len);
        } else {
            $part1 = $this->bufferSize - $pos;
            $data = fread($fp, $part1);
            fseek($fp, 0);
            $data .= fread($fp, $len - $part1);
        }

        fclose($fp);
        return $data;
    }

    /**
     * Get metadata about the buffer
     *
     * @return mixed
     */
    public function getMeta(): mixed
    {
        if (!file_exists($this->metaFile)) {
            return ['write_pos' => 0, 'total_written' => 0, 'start_time' => time(), 'ready' => false];
        }

        return json_decode(file_get_contents($this->metaFile), true);
    }

    /**
     * Check if the buffer is ready for reading
     *
     * @return mixed
     */
    public function isReady(): mixed
    {
        if (!file_exists($this->metaFile)) {
            return false;
        }

        $meta = $this->getMeta();
        return $meta['ready'] ?? false;
    }

    /**
     * Get the total size of the buffer
     *
     * @return int
     */
    public function getSize(): int
    {
        return $this->bufferSize;
    }

    /**
     * Get the current write position
     *
     * @return int
     */
    public function getSafeReadPosition(int $killobytes = 256): int
    {
        $meta = $this->getMeta();
        $writePos = $meta['write_pos'];
        $totalWritten = $meta['total_written'];

        if ($totalWritten < $this->bufferSize) {
            return 0;
        }

        $offset = $killobytes * 1024;
        $safePos = ($writePos - $offset + $this->bufferSize) % $this->bufferSize;

        return $safePos;
    }
}