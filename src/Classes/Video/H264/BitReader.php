<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Video\H264;

use function ord;
use function strlen;

/**
 * Class BitReader
 *
 * Reads the fixed-width and Exp-Golomb coded fields of an H.264 raw byte sequence payload.
 */
final class BitReader
{
	private const EXP_GOLOMB_LEADING_ZERO_LIMIT = 31;

	private string $data;
	private int $position = 0;
	private int $length;

	public function __construct(string $data)
	{
		$this->data = $data;
		$this->length = strlen($data) * 8;
	}

	/**
	 * Reads an unsigned value of the given bit width.
	 *
	 * @phpstan-impure
	 */
	public function read(int $count): int
	{
		$value = 0;

		for ($index = 0; $index < $count; $index++) {
			$bit = $this->position + $index;
			$value = ($value << 1) | ((ord($this->data[$bit >> 3] ?? "\0") >> (7 - ($bit & 7))) & 1);
		}
		$this->position += $count;

		return $value;
	}

	/**
	 * Reads an unsigned Exp-Golomb coded value.
	 *
	 * @phpstan-impure
	 */
	public function readUnsignedExpGolomb(): int
	{
		$zeros = 0;

		while ($this->position < $this->length && $this->read(1) === 0) {
			$zeros++;
			if ($zeros > self::EXP_GOLOMB_LEADING_ZERO_LIMIT) {
				return 0;
			}
		}

		return $zeros === 0 ? 0 : (1 << $zeros) - 1 + $this->read($zeros);
	}

	/**
	 * Reads a signed Exp-Golomb coded value.
	 *
	 * @phpstan-impure
	 */
	public function readSignedExpGolomb(): int
	{
		$value = $this->readUnsignedExpGolomb();

		return ($value & 1) ? (($value + 1) >> 1) : -($value >> 1);
	}

	public function position(): int
	{
		return $this->position;
	}

	public function length(): int
	{
		return $this->length;
	}

	public function alignToByte(): void
	{
		$this->position = (($this->position + 7) >> 3) << 3;
	}

	/**
	 * Whether syntax elements remain, as more_rbsp_data() defines it.
	 *
	 * A payload ends with a one bit and then zero padding, so bits remaining is not the same as data
	 * remaining: reading the stop bit as a syntax element is how an optional field gets invented.
	 */
	public function hasMoreData(): bool
	{
		$stopBit = $this->stopBitPosition();

		return $stopBit !== null && $this->position < $stopBit;
	}

	private function stopBitPosition(): ?int
	{
		for ($byte = strlen($this->data) - 1; $byte >= 0; $byte--) {
			$value = ord($this->data[$byte]);

			if ($value === 0) {
				continue;
			}

			for ($bit = 0; $bit < 8; $bit++) {
				if (($value >> $bit) & 1) {
					return $byte * 8 + (7 - $bit);
				}
			}
		}

		return null;
	}

	/** Strips the emulation prevention bytes a NAL unit carries. */
	public static function unescape(string $nal): string
	{
		$out = '';
		$length = strlen($nal);
		$index = 0;

		while ($index < $length) {
			if ($index + 2 < $length && ord($nal[$index]) === 0 && ord($nal[$index + 1]) === 0 && ord($nal[$index + 2]) === 3) {
				$out .= "\x00\x00";
				$index += 3;
				continue;
			}
			$out .= $nal[$index++];
		}

		return $out;
	}
}
