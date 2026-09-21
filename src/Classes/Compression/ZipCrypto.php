<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Compression;

use function strlen;
use function ord;
use function chr;

/**
 * Class ZipCrypto
 * 
 * Implements the traditional PKWARE ZipCrypto encryption method.
 *
 * @package Clover\Classes\Compression
 */
class ZipCrypto
{
    // Internal state for ZipCrypto
    private array $keys;

    // Store the password for reference
    private string $_password;

    /**
     * Initializes the ZipCrypto class with a password.
     * 
     * @param string $password Password to initialize the encryption keys with
     */
    public function __construct(string $password)
    {
        $this->_password = $password;
        $this->keys = [0x12345678, 0x23456789, 0x34567890];
        $length = strlen($password);

        for ($i = 0; $i < $length; $i++) {
            $this->updateKeys(ord($password[$i]));
        }
    }

    /**
     * Get the password
     * 
     * @return string Current password
     */
    public function getPassword(): string
    {
        return $this->_password;
    }

    /**
     * Set the password
     * 
     * @param string $password Password to set
     * 
     * @return void
     */
    public function setPassword(string $password): void
    {
        $this->_password = $password;
    }

    /**
     * Update the internal keys based on a character
     * 
     * @param int $char Character to update keys with
     * 
     * @return void
     */
    private function updateKeys(int $char): void
    {
        $this->keys[0] = $this->crc32b($this->keys[0], $char);
        $this->keys[1] = (($this->keys[1] + ($this->keys[0] & 0xFF)) * 134775813 + 1) & 0xFFFFFFFF;
        $this->keys[2] = $this->crc32b($this->keys[2], $this->keys[1] >> 24);
    }

    /**
     * Decrypt a byte
     * 
     * @return int Decrypted byte
     */
    private function decryptByte(): int
    {
        $temp = ($this->keys[2] & 0xFFFF) | 2;
        return (($temp * ($temp ^ 1)) >> 8) & 0xFF;
    }

    /**
     * Decrypt data
     * 
     * @param string $data Data to decrypt (passed by reference)
     * 
     * @return void
     */
    public function decrypt(string &$data): void
    {
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $c = ord($data[$i]) ^ $this->decryptByte();
            $this->updateKeys($c);
            $data[$i] = chr($c);
        }
    }

    /**
     * CRC32 calculation
     * 
     * @param int $oldCrc Previous CRC value
     * @param int $char Character to update CRC with
     * 
     * @return int Updated CRC value
     */
    private function crc32b(int $oldCrc, int $char): int
    {
        $newCrc = crc32(chr($char)) ^ 0xFFFFFFFF;
        return (($oldCrc >> 8) & 0xFFFFFF) ^ $newCrc;
    }
}
