<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

use Clover\Classes\Event\EventLoop;
use RuntimeException;
use Closure;
use function is_resource;
use function strlen;

/**
 * Blocking I/O Operations
 */
final class Blocking
{
    /**
     * Set stream to non-blocking mode
     * 
     * @param resource $stream
     * 
     * @return void
     */
    private static function nb(mixed $stream): void
    {
        if (!is_resource($stream)) {
            throw new \InvalidArgumentException('stream must be resource');
        }
        $blocked = stream_set_blocking($stream, false);
        if ($blocked === false) {
            throw new RuntimeException('failed to set non-blocking mode');
        }
    }

    /**
     * Create a TCP server
     * 
     * @param string $address
     * 
     * @return Closure
     */
    public static function createServer(string $address): Closure
    {
        $server = @stream_socket_server($address, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
        if ($server === false) {
            throw new RuntimeException("server create failed: $errstr ($errno)");
        }
        self::nb($server);

        return function () use ($server): Promise {
            return new Promise(function ($resolve) use ($server) {
                $tryAccept = function () use ($server, $resolve) {
                    $client = @stream_socket_accept($server, 0);
                    if (is_resource($client)) {
                        Blocking::nb($client);
                        EventLoop::removeReadStream($server);
                        $resolve($client);
                    }
                };

                $tryAccept();
                EventLoop::addReadStream($server, function () use ($tryAccept) {
                    $tryAccept();
                });
            });
        };
    }

    /**
     * Connect to a TCP server
     * 
     * @param string $host
     * @param int $port
     * @param int $timeoutMs
     * 
     * @return Promise
     */
    public static function connectTcp(string $host, int $port, int $timeoutMs = 5000): Promise
    {
        return new Promise(function ($resolve, $reject) use ($host, $port, $timeoutMs) {
            $addr = "tcp://{$host}:{$port}";
            $flags = STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT;
            $sock = @stream_socket_client($addr, $errno, $errstr, 0.0, $flags);
            if ($sock === false) {
                $reject(new RuntimeException("connect failed: $errstr ($errno)"));
                return;
            }
            self::nb($sock);

            $timer = EventLoop::delay($timeoutMs, function () use ($sock, $reject) {
                EventLoop::removeWriteStream($sock);
                @fclose($sock);
                $reject(new RuntimeException('connect timeout'));
            });

            EventLoop::addWriteStream($sock, function () use ($sock, $resolve, $timer) {
                EventLoop::cancel($timer);
                EventLoop::removeWriteStream($sock);
                $resolve($sock);
            });
        });
    }

    /**
     * Read once from stream
     * 
     * @param resource $stream
     * @param int $length
     * 
     * @return Promise
     */
    public static function readOnce(mixed $stream, int $length = 8192): Promise
    {
        return new Promise(function ($resolve) use ($stream, $length) {
            $tryRead = function () use ($stream, $length, $resolve) {
                $data = @fread($stream, $length);
                if ($data !== false && $data !== '') {
                    EventLoop::removeReadStream($stream);
                    $resolve($data);
                    return;
                }
                $meta = @stream_get_meta_data($stream);
                if ($meta && ($meta['eof'] ?? false)) {
                    EventLoop::removeReadStream($stream);
                    $resolve(false);
                }
            };
            $tryRead();
            EventLoop::addReadStream($stream, function () use ($tryRead) {
                $tryRead();
            });
        });
    }

    /**
     * Write all data to stream
     * 
     * @param resource $stream
     * @param string $data
     * 
     * @return Promise
     */
    public static function writeAll(mixed $stream, string $data): Promise
    {
        return new Promise(function ($resolve, $reject) use ($stream, $data) {
            $len = strlen($data);
            $written = 0;

            $tryWrite = function () use ($stream, $data, $len, &$written, $resolve, $reject) {
                if ($written >= $len) {
                    EventLoop::removeWriteStream($stream);
                    $resolve($written);
                    return;
                }
                $chunk = substr($data, $written);
                $n = @fwrite($stream, $chunk);
                if ($n === false) {
                    EventLoop::removeWriteStream($stream);
                    $reject(new RuntimeException('write failed'));
                    return;
                }
                if ($n > 0) {
                    $written += $n;
                }
            };

            $tryWrite();
            if ($written < $len) {
                EventLoop::addWriteStream($stream, function () use ($tryWrite) {
                    $tryWrite();
                });
            } else {
                $resolve($written);
            }
        });
    }
}
