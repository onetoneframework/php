<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

class Encode
{
	public function detect(string $string)
	{
		$encoding = mb_detect_encoding($string, mb_list_encodings(), true);

		return $encoding;
	}
}
