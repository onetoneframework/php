<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Crypt;

use Clover\Classes\Crypt\OpenSSL;
use PHPUnit\Framework\TestCase;

final class OpenSSLUtilityTest extends TestCase
{
	protected function setUp(): void
	{
		if (!OpenSSL::isExtensionLoaded()) {
			$this->markTestSkipped('OpenSSL extension is not available.');
		}
	}

	public function testCipherAndDigestCapabilityChecksAreCaseInsensitive(): void
	{
		$this->assertTrue(OpenSSL::isCipherSupported('AES-256-CBC'));
		$this->assertTrue(OpenSSL::isCipherSupported('aes-256-cbc'));
		$this->assertTrue(OpenSSL::isDigestSupported('SHA256'));
		$this->assertFalse(OpenSSL::isCipherSupported('not-a-real-cipher'));
		$this->assertFalse(OpenSSL::isDigestSupported('not-a-real-digest'));
	}

	public function testAeadDetectionRecognizesSupportedNamingPatterns(): void
	{
		$this->assertTrue(OpenSSL::isAeadCipher('AES-256-GCM'));
		$this->assertTrue(OpenSSL::isAeadCipher('aes-128-ccm'));
		$this->assertTrue(OpenSSL::isAeadCipher('chacha20-poly1305'));
		$this->assertFalse(OpenSSL::isAeadCipher('aes-256-cbc'));
	}

	public function testDigestMatchesNativeSha256Result(): void
	{
		$this->assertSame(hash('sha256', 'clover'), OpenSSL::digest('clover', 'sha256'));
	}

	public function testCipherMetadataMatchesAes256CbcContract(): void
	{
		$this->assertSame(32, OpenSSL::getCipherKeyLength('aes-256-cbc'));
		$this->assertSame(16, OpenSSL::getCipherInitializationVectorLength('aes-256-cbc'));
	}

	public function testSymmetricEncryptDecryptRoundTripWithFixedInputs(): void
	{
		$key = str_repeat('k', 32);
		$iv = str_repeat("\0", 16);
		$plaintext = 'deterministic plaintext';
		$tag = '';

		$ciphertext = OpenSSL::encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv, $tag);

		$this->assertIsString($ciphertext);
		$this->assertNotSame($plaintext, $ciphertext);
		$this->assertSame($plaintext, OpenSSL::decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv));
	}
}
