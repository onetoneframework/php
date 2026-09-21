<?php

declare(strict_types=1);

namespace Clover\Tests\Token;

use Clover\Classes\Token\JWTToken;
use PHPUnit\Framework\TestCase;

class JWTTokenTest extends TestCase
{
    public function testGenerateAndDecodeValidToken(): void
    {
        $secret = 'top-secret';
        $payload = ['sub' => 'user-1', 'exp' => time() + 60];

        $token = JWTToken::generate($payload, $secret);
        $decoded = JWTToken::decode($token, $secret);

        $this->assertIsArray($decoded);
        $this->assertSame('HS256', $decoded['header']['alg']);
        $this->assertSame('user-1', $decoded['payload']['sub']);
    }

    public function testGenerateThrowsForEmptyPayload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Payload must not be empty');
        JWTToken::generate([], 'secret');
    }

    public function testIsValidReturnsFalseWhenTokenIsTampered(): void
    {
        $secret = 'top-secret';
        $token = JWTToken::generate(['sub' => 'abc', 'exp' => time() + 60], $secret);
        $tampered = substr($token, 0, -2) . 'xx';

        $this->assertFalse(JWTToken::isValid($tampered, $secret));
        $this->assertFalse(JWTToken::decode($tampered, $secret));
    }

    public function testIsValidReturnsFalseForExpiredToken(): void
    {
        $secret = 'top-secret';
        $expired = JWTToken::generate(['sub' => 'abc', 'exp' => time() - 1], $secret);

        $this->assertFalse(JWTToken::isValid($expired, $secret));
    }

    public function testNbfClaimRespectsLeeway(): void
    {
        $secret = 'top-secret';
        $futureNbf = time() + 3;
        $token = JWTToken::generate(['sub' => 'abc', 'nbf' => $futureNbf], $secret);

        $this->assertFalse(JWTToken::isValid($token, $secret));
        $this->assertTrue(JWTToken::isValid($token, $secret, 'HS256', 5));
    }

    public function testUnsupportedAlgorithmThrowsRuntimeException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not a supported algorithm');
        JWTToken::generate(['sub' => 'abc'], 'secret', 'RS256');
    }
}
