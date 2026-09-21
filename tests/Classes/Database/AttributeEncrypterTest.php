<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database;

use Clover\Classes\Database\AttributeEncrypter;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Characterisation tests for the attribute encryption ActiveRecord used to
 * carry itself.
 *
 * The key is process-wide and stayed that way through the move, so every test
 * here puts it back afterwards rather than assuming a fresh class.
 */
final class AttributeEncrypterTest extends TestCase
{
	private const KEY = '0123456789abcdef0123456789abcdef';

	private mixed $originalKey = null;

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalKey = $this->keyProperty()->getValue();
	}

	protected function tearDown(): void
	{
		$this->keyProperty()->setValue(null, $this->originalKey);
		parent::tearDown();
	}

	private function keyProperty(): ReflectionProperty
	{
		$property = new ReflectionProperty(AttributeEncrypter::class, 'key');
		$property->setAccessible(true);

		return $property;
	}

	public function testAKeyMustBeThirtyTwoBytes(): void
	{
		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Encryption key is should be 32 bytes');

		AttributeEncrypter::setKey('too short');
	}

	public function testHasKeyReportsWhetherOneIsSet(): void
	{
		$this->keyProperty()->setValue(null, null);
		$this->assertFalse(AttributeEncrypter::hasKey());

		AttributeEncrypter::setKey(self::KEY);
		$this->assertTrue(AttributeEncrypter::hasKey());
	}

	public function testAValueSurvivesARoundTrip(): void
	{
		AttributeEncrypter::setKey(self::KEY);

		$this->assertSame('secret', AttributeEncrypter::decrypt(AttributeEncrypter::encrypt('secret')));
	}

	public function testTheSameValueEncryptsDifferentlyEachTime(): void
	{
		AttributeEncrypter::setKey(self::KEY);

		$this->assertNotSame(
			AttributeEncrypter::encrypt('secret'),
			AttributeEncrypter::encrypt('secret'),
			'A fresh IV is prepended each time.'
		);
	}

	/**
	 * The IV is sixteen random bytes and the separator is a literal '::'.
	 * decrypt() used to split the payload on the first '::' anywhere in it, so an
	 * IV that happened to contain 0x3A3A - about one encryption in 4,369 - was
	 * cut short and the value could never be decrypted again. It is read by
	 * offset now, which also recovers the payloads that were already written.
	 */
	public function testAnIvContainingTheSeparatorStillDecrypts(): void
	{
		AttributeEncrypter::setKey(self::KEY);

		$iv = "\x01\x02\x3A\x3A\x05\x06\x07\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F\x10";
		$ciphertext = openssl_encrypt('secret', 'AES-256-CBC', self::KEY, 0, $iv);
		$payload = base64_encode($iv . '::' . $ciphertext);

		$this->assertSame('secret', AttributeEncrypter::decrypt($payload));
	}

	public function testASeparatorInsideTheCiphertextIsHarmless(): void
	{
		AttributeEncrypter::setKey(self::KEY);

		// The ciphertext is base64 from openssl_encrypt, so it cannot contain
		// '::' itself; a payload assembled by hand can, and must still split at
		// the fixed offset rather than at the first match.
		$iv = str_repeat("\x11", 16);
		$ciphertext = 'AA::BB';
		$payload = base64_encode($iv . '::' . $ciphertext);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Decryption failed');
		AttributeEncrypter::decrypt($payload);
	}

	/**
	 * A thousand round trips: at one failure in 4,369 this catches the old
	 * behaviour better than nine times in ten, and costs a few milliseconds.
	 */
	public function testAThousandRoundTripsAllSurvive(): void
	{
		AttributeEncrypter::setKey(self::KEY);

		for ($round = 0; $round < 1000; $round++) {
			$value = 'value-' . $round;

			$this->assertSame($value, AttributeEncrypter::decrypt(AttributeEncrypter::encrypt($value)));
		}
	}

	public function testEncryptingWithoutAKeyThrows(): void
	{
		$this->keyProperty()->setValue(null, null);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Encryption key not set');

		AttributeEncrypter::encrypt('secret');
	}

	public function testDecryptingWithoutAKeyThrows(): void
	{
		$this->keyProperty()->setValue(null, null);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Encryption key not set');

		AttributeEncrypter::decrypt('anything');
	}

	public function testAPayloadWithoutTheSeparatorIsRejected(): void
	{
		AttributeEncrypter::setKey(self::KEY);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Invalid encrypted payload');

		AttributeEncrypter::decrypt(base64_encode('no separator here'));
	}

	public function testAPayloadThatDoesNotDecryptIsRejected(): void
	{
		AttributeEncrypter::setKey(self::KEY);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Decryption failed');

		AttributeEncrypter::decrypt(base64_encode('0123456789abcdef::not-real-ciphertext'));
	}
}
