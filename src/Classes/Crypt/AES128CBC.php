<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Crypt;

/**
 * AES-128-CBC cipher implementation
 * 
 * 128-bit AES encryption in Cipher Block Chaining mode.
 * Key: 16 bytes, IV: 16 bytes, Block size: 16 bytes
 */
class AES128CBC extends OpenSSLCipher
{
	protected const METHOD = 'AES-128-CBC';
	protected const KEY_LENGTH = 16;

	/**
	 * Encrypt data using AES-128-CBC
	 * 
	 * @param string $string Plaintext to encrypt
	 * @param string $key 16-byte encryption key
	 * @param string $iv 16-byte IV (auto-generated if empty)
	 * @param int $options OpenSSL options
	 * 
	 * @return string Base64-encoded ciphertext with IV prepended
	 */
	public static function encrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
	{
		parent::setAlgorithm(self::METHOD);

		if ($iv === '') {
			$iv = OpenSSL::generateSecureRandomBytes(OpenSSL::getCipherInitializationVectorLength(self::METHOD));
		}

		return parent::encrypt($string, $key, $iv, $options);
	}

	/**
	 * Decrypt data using AES-128-CBC
	 * 
	 * @param string $string Base64-encoded ciphertext (with or without IV)
	 * @param string $key 16-byte decryption key
	 * @param string $iv IV if provided separately (empty to extract from data)
	 * @param int $options OpenSSL options
	 * 
	 * @return string Decrypted plaintext
	 */
	public static function decrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
	{
		parent::setAlgorithm(self::METHOD);

		$ivLength = OpenSSL::getCipherInitializationVectorLength(self::METHOD);

		if ($iv === '') {
			$extracted = parent::extractIvAndCiphertext($string, $ivLength);
			$iv = $extracted['iv'];
			$ciphertext = $extracted['ciphertext'];
		} else {
			$ciphertext = base64_decode($string, true);
		}

		return parent::decrypt($ciphertext, $key, $iv, $options);
	}
}