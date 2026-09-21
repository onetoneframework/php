<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Token;

use Clover\Classes\Data\JSONHandler;
use RuntimeException;
use InvalidArgumentException;
use Throwable;
use function sprintf;
use function in_array;
use function is_array;
use function count;
use function strlen;

/**
 * Class JWTToken
 *
 * Provides methods to generate, validate, and decode JSON Web Tokens (JWT).
 */
class JWTToken
{
    /** @var array<string, string> Mapping from JWT algorithm names to hash_hmac algorithm identifiers */
    private const ALGORITHM_MAP = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
    ];

    /**
     * Resolve JWT algorithm name to hash_hmac algorithm identifier
     *
     * @param string $algorithm JWT algorithm name (e.g. HS256)
     * @return string hash_hmac algorithm identifier (e.g. sha256)
     * @throws RuntimeException If the algorithm is not supported
     */
    private static function resolveAlgorithm(string $algorithm): string
    {
        if (!isset(self::ALGORITHM_MAP[$algorithm])) {
            throw new RuntimeException(sprintf("The '%s' is not a supported algorithm. Supported: %s", $algorithm, implode(', ', array_keys(self::ALGORITHM_MAP))));
        }

        $hmacAlgo = self::ALGORITHM_MAP[$algorithm];

        if (!in_array($hmacAlgo, hash_hmac_algos(), true)) {
            throw new RuntimeException(sprintf("The hash algorithm '%s' is not available in this environment", $hmacAlgo));
        }

        return $hmacAlgo;
    }

    /**
     * Encode data using Base64 URL-safe encoding (RFC 4648)
     *
     * @param string $data Raw data to encode
     * @return string Base64 URL-safe encoded string
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decode Base64 URL-safe encoded string (RFC 4648)
     *
     * @param string $data Base64 URL-safe encoded string
     * @return string Decoded raw data
     * @throws RuntimeException If decoding fails
     */
    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Failed to decode base64url data');
        }

        return $decoded;
    }

    /**
     * Compute the HMAC signature for the given content
     *
     * @param string $content The content to sign (header.payload)
     * @param string $secretKey The secret key
     * @param string $hmacAlgo The hash_hmac algorithm identifier
     * @return string Raw binary signature
     */
    private static function computeSignature(string $content, string $secretKey, string $hmacAlgo): string
    {
        return hash_hmac($hmacAlgo, $content, $secretKey, true);
    }

    /**
     * Split and structurally validate a JWT token string
     *
     * @param string $token The JWT token string
     * @return array{0: string, 1: string, 2: string} The three JWT segments
     * @throws InvalidArgumentException If the token structure is invalid
     */
    private static function splitToken(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new InvalidArgumentException(sprintf('Invalid JWT structure: expected 3 segments, got %d', count($segments)));
        }

        foreach ($segments as $i => $segment) {
            if ($segment === '') {
                throw new InvalidArgumentException(sprintf('Invalid JWT structure: segment %d is empty', $i));
            }
        }

        return $segments;
    }

    /**
     * Validate registered claims (exp, nbf, iat)
     *
     * @param array{exp: number, nbf: number, iat: number} $payload Decoded payload
     * @param int $leeway Allowed clock skew in seconds
     * @return bool True if all present claims are valid
     */
    private static function validateClaims(array $payload, int $leeway = 0): bool
    {
        $now = time();

        if (isset($payload['exp'])) {
            if (!is_numeric($payload['exp'])) {
                return false;
            }
            if (($now - $leeway) >= (int) $payload['exp']) {
                return false;
            }
        }

        if (isset($payload['nbf'])) {
            if (!is_numeric($payload['nbf'])) {
                return false;
            }
            if (($now + $leeway) < (int) $payload['nbf']) {
                return false;
            }
        }

        if (isset($payload['iat'])) {
            if (!is_numeric($payload['iat'])) {
                return false;
            }
            if (($now + $leeway) < (int) $payload['iat']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a JWT token
     *
     * @param string $token The JWT token to validate
     * @param string $secretKey The secret key used for validation
     * @param string $algorithm The JWT algorithm (default: HS256)
     * @param int $leeway Allowed clock skew in seconds for time-based claims (default: 0)
     * @return bool True if the token is valid, false otherwise
     * @throws RuntimeException If the specified algorithm is not supported
     */
    public static function isValid(string $token, string $secretKey, string $algorithm = 'HS256', int $leeway = 0): bool
    {
        $hmacAlgo = self::resolveAlgorithm($algorithm);

        try {
            $segments = self::splitToken($token);
        } catch (InvalidArgumentException) {
            return false;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;

        try {
            $headerJson = self::base64UrlDecode($encodedHeader);
            $header = JSONHandler::decode($headerJson, true, 512, 0, true);
        } catch (Throwable) {
            return false;
        }

        if (!is_array($header) || !isset($header['alg']) || $header['alg'] !== $algorithm) {
            return false;
        }

        $expectedSignature = self::computeSignature(sprintf('%s.%s', $encodedHeader, $encodedPayload), $secretKey, $hmacAlgo);

        try {
            $providedSignature = self::base64UrlDecode($encodedSignature);
        } catch (RuntimeException) {
            return false;
        }

        if (!hash_equals($expectedSignature, $providedSignature)) {
            return false;
        }

        try {
            $payloadJson = self::base64UrlDecode($encodedPayload);
            $payload = JSONHandler::decode($payloadJson, true, 512, 0, true);
        } catch (Throwable) {
            return false;
        }

        if (!is_array($payload)) {
            return false;
        }

        return self::validateClaims($payload, $leeway);
    }

    /**
     * Decode a JWT token
     *
     * @param string $token The JWT token to decode
     * @param string $secretKey The secret key used for validation
     * @param string $algorithm The JWT algorithm (default: HS256)
     * @param int $leeway Allowed clock skew in seconds for time-based claims (default: 0)
     * @return array{header: array, payload: array}|false Decoded header and payload, or false if invalid
     * @throws RuntimeException If the specified algorithm is not supported
     */
    public static function decode(string $token, string $secretKey, string $algorithm = 'HS256', int $leeway = 0): array|false
    {
        if (!self::isValid($token, $secretKey, $algorithm, $leeway)) {
            return false;
        }

        [$encodedHeader, $encodedPayload] = self::splitToken($token);

        $header = JSONHandler::decode(self::base64UrlDecode($encodedHeader), true, 512, 0, true);
        $payload = JSONHandler::decode(self::base64UrlDecode($encodedPayload), true, 512, 0, true);

        return [
            'header' => $header,
            'payload' => $payload,
        ];
    }

    /**
     * Generate a JWT token
     *
     * @param array $payload The payload data
     * @param string $secretKey The secret key used for signing
     * @param string $algorithm The JWT algorithm (default: HS256)
     * @return string The generated JWT token
     * @throws RuntimeException If the specified algorithm is not supported
     * @throws InvalidArgumentException If the payload is empty
     */
    public static function generate(array $payload, string $secretKey, string $algorithm = 'HS256'): string
    {
        if (empty($payload)) {
            throw new InvalidArgumentException('Payload must not be empty');
        }

        $hmacAlgo = self::resolveAlgorithm($algorithm);

        $header = [
            'typ' => 'JWT',
            'alg' => $algorithm,
        ];

        $encodedHeader = self::base64UrlEncode(JSONHandler::encode($header));
        $encodedPayload = self::base64UrlEncode(JSONHandler::encode($payload));

        $body = sprintf('%s.%s', $encodedHeader, $encodedPayload);

        $signature = self::computeSignature($body, $secretKey, $hmacAlgo);
        $encodedSignature = self::base64UrlEncode($signature);

        return sprintf('%s.%s', $body, $encodedSignature);
    }
}
