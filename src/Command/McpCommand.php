<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Command;

use Clover\Classes\CLI\Input;
use Clover\Classes\SSE\SseServer;
use Clover\Implement\CommandInterface;
use Clover\Annotation\McpTool;
use ReflectionClass;

/**
 * MCP Server Command using SSE (Server-Sent Events) over HTTP.
 *
 * This class owns:
 *  - TCP server loop (accept / read / route)
 *  - HTTP request parsing and routing
 *  - MCP JSON-RPC protocol handling
 *  - MCP tool registration and dispatch
 *
 * SSE session management and event emission are delegated to SseServer.
 */
class McpCommand implements CommandInterface
{
    private const HOST = '127.0.0.1';
    private const PORT = 8002;

    /** @var array<string, array{method:string, name:string, description:string}> */
    private array $tools = [];

    /** @var array<int, string> socket-id => partial HTTP data */
    private array $buffers = [];

    private SseServer $sse;

    public function __construct()
    {
        $baseUrl = 'http://' . self::HOST . ':' . self::PORT;
        $this->sse = new SseServer($baseUrl);
    }

    // -----------------------------------------------------------------------
    // CommandInterface
    // -----------------------------------------------------------------------

    public function getName(): string
    {
        return 'boost:mcp';
    }

    public function getDescription(): string
    {
        return sprintf(
            'Start MCP Server using SSE on tcp://%s:%d',
            self::HOST,
            self::PORT
        );
    }

    public function configure(): void
    {
    }

    // -----------------------------------------------------------------------
    // MCP Tool definitions (via Attributes)
    // -----------------------------------------------------------------------

    #[McpTool('get_php_version', 'Get current PHP version')]
    public function toolGetPhpVersion(): string
    {
        return 'PHP Version: ' . PHP_VERSION;
    }

    #[McpTool('framework_status', 'Check framework status over SSE')]
    public function toolFrameworkStatus(): string
    {
        return 'Onetone Framework is running correctly via SSE.';
    }

    // -----------------------------------------------------------------------
    // Server loop
    // -----------------------------------------------------------------------

    public function run(Input $input): bool
    {
        $this->scanTools();

        $serverUri = sprintf('tcp://%s:%d', self::HOST, self::PORT);
        $server = stream_socket_server($serverUri, $errno, $errstr);

        if (!$server) {
            fprintf(STDERR, "❌ Server start failed: %s (%d)\n", $errstr, $errno);
            return false;
        }

        stream_set_blocking($server, false);
        fprintf(STDERR, "🚀 PHP MCP Server (SSE) starting on http://%s:%d\n", self::HOST, self::PORT);

        $clients = [];

        while (true) {
            $read = array_merge([$server], $clients);
            $write = null;
            $except = null;

            if (stream_select($read, $write, $except, 1) === false) {
                break;
            }

            // Accept new connections
            if (in_array($server, $read, true)) {
                $client = stream_socket_accept($server);
                if ($client) {
                    stream_set_blocking($client, false);
                    $clients[] = $client;
                    $this->buffers[(int) $client] = '';
                }
                unset($read[array_search($server, $read, true)]);
            }

            // Handle readable clients
            foreach ($read as $client) {
                if (!is_resource($client) || feof($client)) {
                    $this->closeClient($client, $clients);
                    continue;
                }

                $data = fread($client, 8192);
                if ($data === false || $data === '') {
                    $this->closeClient($client, $clients);
                    continue;
                }

                $this->buffers[(int) $client] .= $data;
                $this->processBuffer($client, $clients);
            }
        }

        return true;
    }

    private function closeClient(mixed $client, array &$clients): void
    {
        $key = array_search($client, $clients, true);
        if ($key !== false) {
            unset($clients[$key]);
        }

        // Notify SseServer so it can drop the session
        $sessionId = $this->sse->findSessionId($client);
        if ($sessionId !== null) {
            $this->sse->disconnect($sessionId);
        }

        if (is_resource($client)) {
            unset($this->buffers[(int) $client]);
            @fclose($client);
        }
    }

    private function processBuffer(mixed $client, array &$clients): void
    {
        $buffer = &$this->buffers[(int) $client];

        // Wait until we have complete HTTP headers
        $headerEnd = strpos($buffer, "\r\n\r\n");
        if ($headerEnd === false) {
            return;
        }

        $headersRaw = substr($buffer, 0, $headerEnd);
        $lines = explode("\r\n", $headersRaw);
        $parts = explode(' ', array_shift($lines));

        if (count($parts) < 2) {
            $this->closeClient($client, $clients);
            return;
        }

        [$method, $uri] = $parts;

        $parsedUri = parse_url($uri);
        $path = $parsedUri['path'] ?? '/';
        parse_str($parsedUri['query'] ?? '', $query);

        $contentLength = 0;
        foreach ($lines as $line) {
            if (stripos($line, 'Content-Length:') === 0) {
                $contentLength = (int) trim(substr($line, 15));
            }
        }

        $bodyStart = $headerEnd + 4;

        match ($method) {
            'OPTIONS' => $this->handleCorsPreflight($client, $clients),

            'GET' => $path === '/sse'
            ? $this->handleSseConnection($client, $buffer)
            : $this->sendNotFound($client, $clients),

            'POST' => $this->handlePostRequest(
                $client,
                $clients,
                $buffer,
                $bodyStart,
                $contentLength,
                $query['sessionId'] ?? ''
            ),

            default => $this->sendNotFound($client, $clients),
        };
    }

    // -----------------------------------------------------------------------
    // Route handlers
    // -----------------------------------------------------------------------

    private function handleCorsPreflight(mixed $client, array &$clients): void
    {
        fwrite($client, implode("\r\n", [
            'HTTP/1.1 204 No Content',
            'Access-Control-Allow-Origin: *',
            'Access-Control-Allow-Methods: GET, POST, OPTIONS',
            'Access-Control-Allow-Headers: Content-Type, Accept',
            'Connection: close',
            '',
            '',
        ]));
        $this->closeClient($client, $clients);
    }

    /**
     * Delegate SSE handshake to SseServer; keep the socket open (no close).
     */
    private function handleSseConnection(mixed $client, string &$buffer): void
    {
        $this->sse->connect($client);
        $buffer = ''; // SSE socket stays open; discard any trailing bytes
    }

    private function handlePostRequest(mixed $client, array &$clients, string &$buffer, int $bodyStart, int $contentLength, string $sessionId): void
    {
        // Wait until the full body has arrived
        if (strlen($buffer) < $bodyStart + $contentLength) {
            return;
        }

        $body = substr($buffer, $bodyStart, $contentLength);
        $buffer = substr($buffer, $bodyStart + $contentLength); // pipeline-safe

        // Acknowledge immediately per MCP spec
        fwrite($client, implode("\r\n", [
            'HTTP/1.1 202 Accepted',
            'Access-Control-Allow-Origin: *',
            'Content-Length: 0',
            'Connection: close',
            '',
            '',
        ]));
        $this->closeClient($client, $clients);

        if (!$this->sse->hasSession($sessionId)) {
            fprintf(STDERR, "⚠️  Unknown sessionId: %s\n", $sessionId);
            return;
        }

        $request = json_decode($body, true);
        if (!is_array($request)) {
            return;
        }

        $mcpResponse = $this->handleMcpRequest($request);
        if ($mcpResponse !== null) {
            $this->sse->emitTo($sessionId, 'message', json_encode($mcpResponse));
        }
    }

    private function sendNotFound(mixed $client, array &$clients): void
    {
        fwrite($client, "HTTP/1.1 404 Not Found\r\nContent-Length: 0\r\nConnection: close\r\n\r\n");
        $this->closeClient($client, $clients);
    }

    private function handleMcpRequest(array $request): ?array
    {
        $id = $request['id'] ?? null;
        $method = $request['method'] ?? null;
        $params = $request['params'] ?? [];

        return match ($method) {
            'initialize' => [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => [
                    'protocolVersion' => '2024-11-05',
                    'capabilities' => [
                        'tools' => ['listChanged' => false],
                    ],
                    'serverInfo' => [
                        'name' => 'Onetone-PHP-MCP-SSE',
                        'version' => '1.0.0',
                    ],
                ],
            ],

            'notifications/initialized' => null,

            'tools/list' => [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => ['tools' => $this->getRegisteredTools()],
            ],

            'tools/call' => [
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $this->callTool(
                    $params['name'] ?? '',
                    $params['arguments'] ?? []
                ),
            ],

            default => $id !== null ? [
                'jsonrpc' => '2.0',
                'id' => $id,
                'error' => [
                    'code' => -32601,
                    'message' => "Method '{$method}' not found",
                ],
            ] : null,
        };
    }

    private function scanTools(): void
    {
        $refClass = new ReflectionClass($this);
        foreach ($refClass->getMethods() as $method) {
            $attributes = $method->getAttributes(McpTool::class);
            if (empty($attributes)) {
                continue;
            }
            $attr = $attributes[0]->newInstance();
            $this->tools[$attr->name] = [
                'method' => $method->getName(),
                'name' => $attr->name,
                'description' => $attr->description,
            ];
        }
    }

    private function getRegisteredTools(): array
    {
        return array_values(array_map(
            fn(array $meta) => [
                'name' => $meta['name'],
                'description' => $meta['description'],
                'inputSchema' => ['type' => 'object', 'properties' => (object) []],
            ],
            $this->tools
        ));
    }

    private function callTool(string $name, array $args): array
    {
        if (!isset($this->tools[$name])) {
            return $this->toolError($name, "Tool '{$name}' not found.");
        }

        try {
            $result = $this->{$this->tools[$name]['method']}();
            return [
                'content' => [['type' => 'text', 'text' => (string) $result]],
            ];
        } catch (\Throwable $e) {
            return $this->toolError($name, $e->getMessage());
        }
    }

    private function toolError(string $name, string $message): array
    {
        return [
            'isError' => true,
            'content' => [['type' => 'text', 'text' => $message]],
        ];
    }
}
