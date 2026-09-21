<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Video\H264;

use Exception;

use function ord;
use function strlen;

/**
 * Class CabacDecoder
 *
 * The H.264 context adaptive binary arithmetic decoding engine.
 */
final class CabacDecoder
{
	private const INITIAL_RANGE = 510;
	private const INITIAL_OFFSET_BITS = 9;
	private const RENORMALISATION_FLOOR = 256;
	private const TERMINATE_RANGE_STEP = 2;
	private const MINIMUM_PROBABILITY_STATE = 1;
	private const MAXIMUM_PROBABILITY_STATE = 126;
	private const PROBABILITY_STATE_PIVOT = 63;
	private const QUANTISER_LIMIT = 51;

	private const RANGE_TABLE_LPS = [
		[128,176,208,240],[128,167,197,227],[128,158,187,216],[123,150,178,205],
		[116,142,169,195],[111,135,160,185],[105,128,152,175],[100,122,144,166],
		[95,116,137,158],[90,110,130,150],[85,104,123,142],[81,99,117,135],
		[77,94,111,128],[73,89,105,122],[69,85,100,116],[66,80,95,110],
		[62,76,90,104],[59,72,86,99],[56,69,81,94],[53,65,77,89],
		[51,62,73,85],[48,59,69,80],[46,56,66,76],[43,53,63,72],
		[41,50,59,69],[39,48,56,65],[37,45,54,62],[35,43,51,59],
		[33,41,48,56],[32,39,46,53],[30,37,43,50],[29,35,41,48],
		[27,33,39,45],[26,31,37,43],[24,30,35,41],[23,28,33,39],
		[22,27,32,37],[21,26,30,35],[20,24,29,33],[19,23,27,31],
		[18,22,26,30],[17,21,25,28],[16,20,23,27],[15,19,22,25],
		[14,18,21,24],[14,17,20,23],[13,16,19,22],[12,15,18,21],
		[12,14,17,20],[11,14,16,19],[11,13,15,18],[10,12,15,17],
		[10,12,14,16],[9,11,13,15],[9,11,12,14],[8,10,12,14],
		[8,9,11,13],[7,9,11,12],[7,9,10,12],[7,8,10,11],
		[6,8,9,11],[6,7,9,10],[6,7,8,9],[2,2,2,2],
	];

	private const TRANSITION_LPS = [
		0,0,1,2,2,4,4,5,6,7,8,9,9,11,11,12,13,13,15,15,16,16,18,18,19,19,21,21,22,22,23,24,
		24,25,26,26,27,27,28,29,29,30,30,30,31,32,32,33,33,33,34,34,35,35,35,36,36,36,37,37,37,38,38,63,
	];
	private const TRANSITION_MPS = [
		1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,
		33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,62,63,
	];

	/** @var array<int, int> */
	private array $state = [];
	/** @var array<int, int> */
	private array $mostProbableSymbol = [];

	private string $data;
	private int $bitPosition;
	private int $range;
	private int $offset;

	/** @param array<int, array{0:int,1:int}> $contexts */
	public function __construct(string $data, int $byteOffset, int $sliceQuantiser, array $contexts)
	{
		$quantiser = max(0, min(self::QUANTISER_LIMIT, $sliceQuantiser));

		foreach ($contexts as $index => $pair) {
			$preState = max(
				self::MINIMUM_PROBABILITY_STATE,
				min(self::MAXIMUM_PROBABILITY_STATE, (($pair[0] * $quantiser) >> 4) + $pair[1])
			);

			if ($preState <= self::PROBABILITY_STATE_PIVOT) {
				$this->state[$index] = self::PROBABILITY_STATE_PIVOT - $preState;
				$this->mostProbableSymbol[$index] = 0;
			} else {
				$this->state[$index] = $preState - (self::PROBABILITY_STATE_PIVOT + 1);
				$this->mostProbableSymbol[$index] = 1;
			}
		}

		$this->data = $data;
		$this->bitPosition = $byteOffset * 8;
		$this->range = self::INITIAL_RANGE;
		$this->offset = $this->readBits(self::INITIAL_OFFSET_BITS);
	}

	private function readBits(int $count): int
	{
		$value = 0;

		for ($index = 0; $index < $count; $index++) {
			$bit = $this->bitPosition + $index;
			$value = ($value << 1) | ((ord($this->data[$bit >> 3] ?? "\0") >> (7 - ($bit & 7))) & 1);
		}
		$this->bitPosition += $count;

		return $value;
	}

	private function renormalise(): void
	{
		while ($this->range < self::RENORMALISATION_FLOOR) {
			$this->range <<= 1;
			$this->offset = ($this->offset << 1) | $this->readBits(1);
		}
	}

	/**
	 * Decodes one context coded bin.
	 *
	 * @phpstan-impure
	 */
	public function decodeDecision(int $contextIndex): int
	{
		if (!isset($this->state[$contextIndex])) {
			throw new Exception('CABAC context ' . $contextIndex . ' has no initialisation value.');
		}

		$probabilityState = $this->state[$contextIndex];
		$valueMps = $this->mostProbableSymbol[$contextIndex];
		$rangeLps = self::RANGE_TABLE_LPS[$probabilityState][($this->range >> 6) & 3];
		$this->range -= $rangeLps;

		if ($this->offset >= $this->range) {
			$bin = 1 - $valueMps;
			$this->offset -= $this->range;
			$this->range = $rangeLps;

			if ($probabilityState === 0) {
				$this->mostProbableSymbol[$contextIndex] = 1 - $valueMps;
			}
			$this->state[$contextIndex] = self::TRANSITION_LPS[$probabilityState];
		} else {
			$bin = $valueMps;
			$this->state[$contextIndex] = self::TRANSITION_MPS[$probabilityState];
		}

		$this->renormalise();

		return $bin;
	}

	/**
	 * Decodes one bin that uses no context.
	 *
	 * @phpstan-impure
	 */
	public function decodeBypass(): int
	{
		$this->offset = ($this->offset << 1) | $this->readBits(1);

		if ($this->offset >= $this->range) {
			$this->offset -= $this->range;

			return 1;
		}

		return 0;
	}

	/**
	 * Decodes the bin that ends a slice or selects I_PCM.
	 *
	 * @phpstan-impure
	 */
	public function decodeTerminate(): int
	{
		$this->range -= self::TERMINATE_RANGE_STEP;

		if ($this->offset >= $this->range) {
			return 1;
		}
		$this->renormalise();

		return 0;
	}

	public function bitPosition(): int
	{
		return $this->bitPosition;
	}

	public function bitLength(): int
	{
		return strlen($this->data) * 8;
	}
}
