<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Security\Auth;

/**
 * TOTP (Time-based One-Time Password) generator - 2FA support
 */
class TOTPGenerator
{
    private $secretLength = 32;
    private $timeStep = 30; 
    private $digits = 6;
    private $algorithm = 'sha1';

    /**
     * Generate TOTP secret
     */
    public function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        for ($i = 0; $i < $this->secretLength; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }

    /**
     * Generate TOTP code
     */
    public function generateCode(string $secret, ?int $timestamp = null): string
    {
		$timestamp = $timestamp ?? time();
		$timeCounter = intdiv($timestamp, $this->timeStep);
        
        $secretBytes = $this->base32Decode($secret);
        $timeBytes = pack('N*', 0, $timeCounter);
        
        $hash = hash_hmac($this->algorithm, $timeBytes, $secretBytes, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xF;
        
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % pow(10, $this->digits);
        
		return str_pad((string) $code, $this->digits, '0', STR_PAD_LEFT);
    }

    /**
     * Verify TOTP code
     */
    public function verify(string $code, string $secret, int $window = 1): bool
    {
        $timestamp = time();
        
        
        for ($i = -$window; $i <= $window; $i++) {
            $testTime = $timestamp + ($i * $this->timeStep);
            $testCode = $this->generateCode($secret, $testTime);
            
            if (hash_equals($code, $testCode)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Generate QR code URL
     */
    public function getQRCodeUrl(string $email, string $secret, string $issuer = 'CloverFramework'): string
    {
        $label = urlencode($email);
        $issuer = urlencode($issuer);
        $secret = urlencode($secret);
        
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm={$this->algorithm}&digits={$this->digits}&period={$this->timeStep}";
    }

    /**
     * Base32 decode
     */
    private function base32Decode(string $data): string
    {
        $map = [
            'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7,
            'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15,
            'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23,
            'Y' => 24, 'Z' => 25, '2' => 26, '3' => 27, '4' => 28, '5' => 29, '6' => 30, '7' => 31
        ];
        
        $data = strtoupper($data);
        $data = str_replace('=', '', $data);
        
        $result = '';
        $bits = 0;
        $value = 0;
        
        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            if (!isset($map[$char])) {
                continue;
            }
            
            $value = ($value << 5) | $map[$char];
            $bits += 5;
            
            if ($bits >= 8) {
                $result .= chr(($value >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }
        
        return $result;
    }

    /**
     * Set secret length
     */
    public function setSecretLength(int $length): self
    {
        $this->secretLength = $length;
        return $this;
    }

    /**
     * Set time step
     */
    public function setTimeStep(int $step): self
    {
        $this->timeStep = $step;
        return $this;
    }

    /**
     * Set digits
     */
    public function setDigits(int $digits): self
    {
        $this->digits = $digits;
        return $this;
    }

    /**
     * Set algorithm
     */
    public function setAlgorithm(string $algorithm): self
    {
        $this->algorithm = $algorithm;
        return $this;
    }
}
