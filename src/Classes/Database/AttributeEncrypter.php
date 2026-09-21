<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

use Exception;
use function mb_strlen;

/**
 * AES-256-CBC encryption for the attributes a model lists as encrypted.
 *
 * Lifted out of ActiveRecord unchanged, bodies and messages included. The key
 * is process-wide, as it always was — setEncryptionKey() on one model sets it
 * for every model — so it stays static here rather than becoming per-instance,
 * which would have been a behaviour change rather than a move.
 *
 * @package Clover\Classes\Database
 */
class AttributeEncrypter
{
	/** Length of the initialisation vector AES-256-CBC uses. */
	private const IV_LENGTH = 16;

	/** Literal placed between the IV and the ciphertext. */
	private const SEPARATOR = '::';

	/** Length of {@see SEPARATOR}, kept beside it so the two cannot drift. */
	private const SEPARATOR_LENGTH = 2;

	/**
	 * Encryption key used for AES-256-CBC. Set via setKey().
	 *
	 * @var string|null
	 */
	private static ?string $key = null;

	/**
	 * Set the global encryption key used for encrypted attribute handling.
	 *
	 * @param string $key  Encryption key (should be 32 bytes for AES-256)
	 *
	 * @return void
	 *
	 * @throws Exception When the key is not 32 bytes.
	 */
	public static function setKey(string $key): void
	{
		if (mb_strlen($key, '8bit') !== 32) {
			throw new Exception('Encryption key is should be 32 bytes');
		}

		self::$key = $key;
	}

	/**
	 * Whether a key has been set.
	 *
	 * @return bool
	 */
	public static function hasKey(): bool
	{
		return (bool) self::$key;
	}

	/**
	 * Encrypt a plain-text value using AES-256-CBC.
	 *
	 * @param string $value  Plain text
	 *
	 * @return string  Base64-encoded ciphertext with IV prepended
	 * @throws Exception When encryption key not set
	 */
	public static function encrypt(string $value): string
	{
		if (!self::$key) {
			throw new Exception("Encryption key not set. Call setEncryptionKey() first.");
		}

		$iv = openssl_random_pseudo_bytes(self::IV_LENGTH);
		$encrypted = openssl_encrypt($value, 'AES-256-CBC', self::$key, 0, $iv);

		return base64_encode($iv . self::SEPARATOR . $encrypted);
	}

	/**
	 * Decrypt a previously encrypted value.
	 *
	 * @param string $payload  Base64-encoded ciphertext with IV
	 *
	 * @return string  Decrypted plain text
	 * @throws Exception When encryption key not set or decryption fails
	 */
	public static function decrypt(string $payload): string
	{
		if (!self::$key) {
			throw new Exception("Encryption key not set. Call setEncryptionKey() first.");
		}

		$decoded = base64_decode($payload);

		// The IV is a fixed sixteen bytes, so it is read by length rather than by
		// splitting on the separator. explode('::', $decoded, 2) cut at the first
		// '::' in the whole payload, and the IV is random bytes: whenever two
		// adjacent ones happened to be 0x3A3A - about one encryption in 4,369 -
		// the split landed inside the IV, openssl_decrypt() was handed a truncated
		// one, and the value could never be decrypted again. Reading by offset
		// recovers those payloads too: the bytes on disk are unchanged, only the
		// way they are taken apart is.
		if (strlen($decoded) < self::IV_LENGTH + self::SEPARATOR_LENGTH
			|| substr($decoded, self::IV_LENGTH, self::SEPARATOR_LENGTH) !== self::SEPARATOR) {
			throw new Exception("Invalid encrypted payload");
		}

		$iv = substr($decoded, 0, self::IV_LENGTH);
		$encrypted = substr($decoded, self::IV_LENGTH + self::SEPARATOR_LENGTH);
		$decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', self::$key, 0, $iv);

		if ($decrypted === false) {
			throw new Exception("Decryption failed");
		}

		return $decrypted;
	}
}
