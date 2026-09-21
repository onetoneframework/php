<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\SSE;
use function is_resource;

/**
 * SSE (Server-Sent Events) connection and session manager.
 *
 * Responsibilities:
 *  - Accept and register SSE client connections
 *  - Maintain sessionId ↔ socket mapping
 *  - Send typed SSE events down the wire
 *  - Clean up closed sessions
 */
class SseServer
{
    /** @var array<string, resource> sessionId => socket */
    private array $sessions = [];

    /** @var string Base URL used when advertising the message endpoint to clients */
    private string $baseUrl;

    /**
     * Constructor.
     * 
     * @param string $baseUrl Base URL for constructing the message endpoint URL.
     *                        Should include protocol and port, e.g. "http://"
     */
    public function __construct(string $baseUrl = 'http://127.0.0.1:8002')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    // -----------------------------------------------------------------------
    // Connection lifecycle
    // -----------------------------------------------------------------------

    /**
     * Perform the SSE HTTP handshake, register the client, and send the
     * initial `endpoint` event that tells the MCP client where to POST.
     *
     * @param  resource $client  Raw TCP socket accepted from stream_socket_server
     * @return string            The new sessionId assigned to this connection
     */
    public function connect(mixed $client): string
    {
        $sessionId = uniqid('sess_', true);
        $this->sessions[$sessionId] = $client;

        fwrite($client, $this->buildSseHeaders());

        $endpointUrl = "{$this->baseUrl}/message?sessionId={$sessionId}";
        $this->emit($client, 'endpoint', $endpointUrl);

        fprintf(STDERR, "🔗 SSE session opened: %s\n", $sessionId);

        return $sessionId;
    }

    /**
     * Remove a session (called when the underlying socket is closed).
     * 
     * @param string $sessionId The session ID to disconnect
     */
    public function disconnect(string $sessionId): void
    {
        if (isset($this->sessions[$sessionId])) {
            fprintf(STDERR, "🔌 SSE session closed: %s\n", $sessionId);
            unset($this->sessions[$sessionId]);
        }
    }

    /**
     * Look up the socket for a given sessionId, or null if not found.
     *
     * @param string $sessionId The session ID to look up
     * @return resource|null
     */
    public function getSocket(string $sessionId): mixed
    {
        return $this->sessions[$sessionId] ?? null;
    }

    /**
     * Resolve the sessionId that owns a given socket resource, or null.
     *
     * @param resource $socket
     */
    public function findSessionId(mixed $socket): ?string
    {
        foreach ($this->sessions as $id => $sess) {
            if ($sess === $socket) {
                return $id;
            }
        }
        return null;
    }

    /**
     * Check if a sessionId is currently active.
     * 
     * @param string $sessionId The session ID to check
     */
    public function hasSession(string $sessionId): bool
    {
        return isset($this->sessions[$sessionId]);
    }

    /** 
     * Get a list of all active session IDs.
     * 
     * @return string[] All currently active session IDs 
     **/
    public function getSessionIds(): array
    {
        return array_keys($this->sessions);
    }

    /**
     * Write a single SSE event frame to a socket.
     *
     * Format:
     *   event: <event>\n
     *   data: <line1>\n
     *   data: <line2>\n   ← one `data:` line per newline in $data
     *   \n                 ← blank line terminates the event
     *
     * @param resource $socket
     */
    public function emit(mixed $socket, string $event, string $data): void
    {
        if (!is_resource($socket)) {
            return;
        }

        $frame = "event: {$event}\n";
        foreach (explode("\n", $data) as $line) {
            $frame .= "data: {$line}\n";
        }
        $frame .= "\n";

        @fwrite($socket, $frame);
        @fflush($socket);
    }

    /**
     * Convenience: send an event by sessionId instead of raw socket.
     * Returns false when the session is unknown or the socket is gone.
     * 
     * @param string $sessionId The session ID to send the event to
     * @param string $event     The event name/type
     * @param string $data      The event data payload (can contain newlines)
     */
    public function emitTo(string $sessionId, string $event, string $data): bool
    {
        $socket = $this->getSocket($sessionId);
        if ($socket === null) {
            return false;
        }
        $this->emit($socket, $event, $data);
        return true;
    }

    /**
     * Build the raw HTTP response headers for the SSE handshake.
     * @return string The full HTTP response headers as a string
     */
    private function buildSseHeaders(): string
    {
        return implode("\r\n", [
            'HTTP/1.1 200 OK',
            'Content-Type: text/event-stream',
            'Cache-Control: no-cache',
            'Access-Control-Allow-Origin: *',
            'Connection: keep-alive',
            '',
            '',   // blank line ends headers
        ]);
    }
}
