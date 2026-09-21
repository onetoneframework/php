<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Crypt;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Crypt\BinaryQueueCodec;
use PHPUnit\Framework\TestCase;
use Clover\Classes\Crypt\AES256CBC;
use Clover\Classes\Crypt\AES128CBC;
use Clover\Classes\Crypt\DESEDE3;

class CryptoTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testCrypto(): void
	{
		$encrypt = AES256CBC::encrypt('aywio&@siosP', 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = AES256CBC::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, "aywio&@siosP");

		$encrypt = AES128CBC::encrypt('aywio&@siosP', 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = AES128CBC::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, "aywio&@siosP");

		$encrypt = DESEDE3::encrypt('aywio&@siosP', 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = DESEDE3::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, "aywio&@siosP");

		$encrypt = BinaryQueueCodec::encrypt('1234', 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = BinaryQueueCodec::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, "1234");

		$encrypt = BinaryQueueCodec::encrypt('4321', 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = BinaryQueueCodec::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, "4321");

		$encrypt = BinaryQueueCodec::encrypt('Hello World', 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = BinaryQueueCodec::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, "Hello World");

		$encrypt = BinaryQueueCodec::encrypt('私は明日友達と映画を見に行きます', 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = BinaryQueueCodec::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, "私は明日友達と映画を見に行きます");

		$word = "朝の光がホームに差し込むとき、私は静かに「おはよう」とつぶやきながら想着今天要做的事情，想着明天可能会发生的变化";

		$encrypt = BinaryQueueCodec::encrypt($word, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');
		$decrypt = BinaryQueueCodec::decrypt($encrypt, 'P4lB5jeIzH3ei1elH6rIPZqvDhEDRgYc');

		$this->assertEquals($decrypt, $word);

	}
}
