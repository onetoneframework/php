<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Queue;

use function count;

/**
 * Class FileMessageQueue
 * 
 * A simple file-based message queue implementation.
 */
class FileMessageQueue
{
    private string $queueDir;

    /**
     * Constructor
     * 
     * @param string $queueDir Directory to store queue files
     */
    public function __construct(string $queueDir = __DIR__ . '/queue')
    {
        $this->queueDir = rtrim($queueDir, '/');

        if (!is_dir($this->queueDir)) {
            mkdir($this->queueDir, 0777, true);
        }
    }

    /**
     * Push a message onto the queue
     * 
     * @param string $queue Queue name
     * @param string $message Message to enqueue
     * 
     * @return bool Success status
     */
    public function push(string $queue, string $message): bool
    {
        $filename = $this->queueDir . "/$queue-" . uniqid('', true) . ".msg";
        return file_put_contents($filename, $message) !== false;
    }

    /**
     * Pop a message from the queue
     * 
     * @param string $queue Queue name
     * 
     * @return string|null The dequeued message or null if the queue is empty
     */
    public function pop(string $queue): ?string
    {
        $files = glob($this->queueDir . "/$queue-*.msg");

        if (empty($files)) {
            return null;
        }

        sort($files);

        $file = $files[0];
        $message = file_get_contents($file);
        unlink($file);

        return $message;
    }

    /**
     * Get the size of the queue
     * 
     * @param string $queue Queue name
     * 
     * @return int Number of messages in the queue
     */
    public function size(string $queue): int
    {
        return count(glob($this->queueDir . "/$queue-*.msg"));
    }

    /**
     * Clear all messages from the queue
     * 
     * @param string $queue Queue name
     * 
     * @return void
     */
    public function clear(string $queue): void
    {
        $files = glob($this->queueDir . "/$queue-*.msg");
        foreach ($files as $file) {
            unlink($file);
        }
    }
}