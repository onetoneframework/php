<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Socket;

use Clover\Enumeration\Bitwise\MaskingBit;
use function ord;
use function chr;
use function strlen;

class WebSocket
{

    /**
     * Send a socket frame
     * @param resource $socket
     * 
     * @return bool|string
     */
    public static function readFrame(mixed $socket): bool|string
    {
        $header = fread($socket, 2);
        if (!$header) {
            return false;
        }
        $data = unpack('Cfirst/Csecond', $header);
        $fin = ($data['first'] >> 7) & 0x1;
        $opcode = $data['first'] & 0x0F;
        $masked = ($data['second'] >> 7) & 0x1;
        $length = $data['second'] & 0x7F;

        if ($length === 126) {
            $ext = fread($socket, 2);
            $length = unpack('n', $ext)[1];
        } elseif ($length === 127) {
            $ext = fread($socket, 8);
            $length = unpack('J', $ext)[1];
        }

        $maskKey = '';
        if ($masked) {
            $maskKey = fread($socket, 4);
            $maskBytes = array_map('ord', str_split($maskKey));
        }

        $payload = '';
        $remaining = $length;
        while ($remaining > 0) {
            $chunk = fread($socket, $remaining);
            $payload .= $chunk;
            $remaining -= strlen($chunk);
        }

        if ($masked) {
            for ($i = 0; $i < $length; $i++) {
                $payload[$i] = chr(ord($payload[$i]) ^ $maskBytes[$i % 4]);
            }
        }

        return $payload;
    }

    /**
     * Send a socket data
     * @param resource $socket
     * @param string $payload
     * @param int $opcode
     * @return void
     */
    public static function sendToWebSocket(mixed $socket, string $payload, int $opcode = 1): void
    {
        $fin = 0x80;
        $maskBit = 0x80;
        $len = strlen($payload);
        $frame = chr($fin | $opcode);

        if ($len < 126) {
            $frame .= chr($maskBit | $len);
        } elseif ($len < 0x10000) {
            $frame .= chr($maskBit | 126) . pack('n', $len);
        } else {
            $frame .= chr($maskBit | 127) . pack('J', $len);
        }

        $mask = random_bytes(4);
        $frame .= $mask;
        for ($i = 0; $i < $len; $i++) {
            $frame .= $payload[$i] ^ $mask[$i % 4];
        }

        fwrite($socket, $frame);
    }

    /**
     * Receive a socket data
     * 
     * @param resource $sock
     * @param string $payload
     * @param int $opcode
     * 
     * @return ?array
     */
    public static function receiveWebSocketData(mixed $sock): ?array
    {
        $hdr = fread($sock, 2);
        if (!$hdr) {
            return null;
        }

        // Opcode (fragment)
        $b1 = ord($hdr[0]);
        // Length (7-bit maked)
        $b2 = ord($hdr[1]);
        $len = $b2 & MaskingBit::LOW_BYTE_7;

        if ($len === 126) {
            $ext = fread($sock, 2);
            $len = unpack('n', $ext)[1];
        } elseif ($len === 127) {
            $ext = fread($sock, 8);
            $len = unpack('J', $ext)[1];
        }

        $masked = ($b2 & MaskingBit::MSB_BYTE_7) >> 7;
        $maskKey = $masked ? fread($sock, 4) : '';
        $data = $len ? fread($sock, $len) : '';

        if ($masked) {
            $unmasked = '';
            for ($i = 0; $i < $len; $i++) {
                $unmasked .= $data[$i] ^ $maskKey[$i % 4];
            }
            $data = $unmasked;
        }

        return ['opcode' => $b1 & MaskingBit::FULL_NIBBLE, 'payload' => $data];
    }

}