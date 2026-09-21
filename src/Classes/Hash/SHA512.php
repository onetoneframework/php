<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Hash;

class SHA512
{
	public function encrypt(#[\SensitiveParameter] $string, $useBase64 = true)
	{
		$hashed = hash('sha512', $string, true);

		return $useBase64 ? base64_encode($hashed) : $hashed;
	}
}
