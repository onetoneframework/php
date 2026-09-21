<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTTP;

use Clover\Classes\Event\EventLoop;
use Clover\Classes\Event\Promise;
use Exception;

/**
 * Class AsyncHttpClient
 *
 * @package Clover\Classes\HTTP
 */
class AsyncHttpClient
{
    /**
     * @var EventLoop The event loop instance used for managing asynchronous operations.
     */
    private EventLoop $loop;

    /**
     * AsyncHttpClient constructor.
     *
     * @param EventLoop $loop
     */
    public function __construct(EventLoop $loop)
    {
        $this->loop = $loop;
    }

    /**
     * Perform an asynchronous HTTP GET request.
     *
     * @param string $url
     * 
     * @return Promise
     */
    public function get(string $url): Promise
    {
        return new Promise(function ($resolve, $reject) use ($url) {
            /**
             * @var array{host: string, port: int, path: string, scheme: string, user: string, pass: string, path: string, query: string, fragment: string} $urlParts
             */
            $urlParts = parse_url($url);
            if ($urlParts === false || !isset($urlParts['host'])) {
                $reject(new Exception("Invalid URL: $url"));
                return;
            }
            $host = $urlParts['host'];
            $port = $urlParts['port'] ?? 80;
            $path = $urlParts['path'] ?? '/';

            $socket = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 30, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT);
            if (!$socket) {
                $reject(new Exception("Connection failed: $errstr"));
                return;
            }

            $blocked = stream_set_blocking($socket, false);
            if ($blocked === false) {
                fclose($socket);
                $reject(new Exception("Failed to set non-blocking mode"));
                return;
            }
            $request = "GET {$path} HTTP/1.0\r\nHost: {$host}\r\nConnection: close\r\n\r\n";
            $response = '';
            $requestSent = false;

            $this->loop->addWriteStream($socket, function ($stream) use (&$request, &$requestSent, $socket) {
                if (!$requestSent) {
                    fwrite($stream, $request);
                    $requestSent = true;
                    $this->loop->removeWriteStream($socket);
                }
            });

            $this->loop->addReadStream($socket, function ($stream) use (&$response, $resolve, $socket) {
                $chunk = fread($stream, 8192);
                if ($chunk === false || $chunk === '') {
                    $this->loop->removeReadStream($socket);
                    fclose($stream);
                    $resolve($response);
                } else {
                    $response .= $chunk;
                }
            });
        });
    }
}
