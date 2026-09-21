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

use function count;
use function intdiv;
use function min;

/**
 * Class SliceParser
 *
 * Reads the macroblock syntax of an H.264 intra slice from a CABAC decoder into per-macroblock
 * prediction modes and residual levels.
 */
final class SliceParser
{
	public const MB_TYPE_I_NXN = 0;
	public const MB_TYPE_I_PCM = 25;
	public const MB_TYPE_I_16X16_FIRST = 1;
	public const MB_TYPE_I_16X16_LAST = 24;

	/** Position of each 4x4 luma block in the macroblock's 4x4 grid, in decoding order. */
	public const LUMA_BLOCK_GRID = [
		[0,0],[1,0],[0,1],[1,1],[2,0],[3,0],[2,1],[3,1],
		[0,2],[1,2],[0,3],[1,3],[2,2],[3,2],[2,3],[3,3],
	];

	private const QUANTISER_RANGE = 52;
	private const MAXIMUM_QUANTISER_DELTA_BINS = 104;
	private const EXP_GOLOMB_SUFFIX_LIMIT = 30;
	private const LEVEL_PREFIX_LIMIT = 15;

	private const SIGNIFICANT_CONTEXT_8X8 = [
		0,1,2,3,4,5,5,4,4,3,3,4,4,4,5,5,4,4,4,4,3,3,6,7,7,7,8,9,10,9,8,7,
		7,6,11,12,13,11,6,7,8,9,14,10,9,8,6,11,12,13,11,6,9,14,10,9,11,12,13,11,14,10,12,
	];
	private const LAST_CONTEXT_8X8 = [
		0,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,2,
		3,3,3,3,3,3,3,3,4,4,4,4,4,4,4,4,5,5,5,5,6,6,6,6,7,7,7,7,8,8,8,
	];

	/** Significant, last significant and absolute level context bases, by block category. */
	private const CATEGORY_BASES = [
		0 => [105, 166, 227],
		1 => [120, 181, 237],
		2 => [134, 195, 247],
		3 => [149, 210, 257],
		4 => [152, 213, 266],
		5 => [402, 417, 426],
	];
	private const CODED_BLOCK_FLAG_BASES = [0 => 85, 1 => 89, 2 => 93, 3 => 97, 4 => 101, 5 => 1012];

	private const CATEGORY_LUMA_DC = 0;
	private const CATEGORY_LUMA_AC = 1;
	private const CATEGORY_LUMA_4X4 = 2;
	private const CATEGORY_CHROMA_DC = 3;
	private const CATEGORY_CHROMA_AC = 4;
	private const CATEGORY_LUMA_8X8 = 5;

	private int $mbWidth;
	private int $mbHeight;
	private bool $transform8x8Allowed;

	/** @var array<int, array<string, mixed>> */
	public array $macroblocks = [];

	/** @var array<int, int> */
	private array $lumaBlockIndexByGrid = [];

	private CabacDecoder $cabac;
	private int $quantiser = 0;
	private int $sliceIndex = 0;
	private bool $blockHadCoefficients = false;

	public function __construct(int $mbWidth, int $mbHeight, bool $transform8x8Allowed)
	{
		$this->mbWidth = $mbWidth;
		$this->mbHeight = $mbHeight;
		$this->transform8x8Allowed = $transform8x8Allowed;

		foreach (self::LUMA_BLOCK_GRID as $index => [$x, $y]) {
			$this->lumaBlockIndexByGrid[$y * 4 + $x] = $index;
		}
	}

	/**
	 * Reads one slice, returning the address of the macroblock its end_of_slice_flag closed.
	 *
	 * @throws Exception If the slice runs past the picture or uses an unsupported macroblock.
	 */
	public function parseSlice(CabacDecoder $cabac, int $firstMbAddress, int $sliceQuantiser, int $sliceIndex): int
	{
		$this->cabac = $cabac;
		$this->quantiser = $sliceQuantiser;
		$this->sliceIndex = $sliceIndex;

		$total = $this->mbWidth * $this->mbHeight;

		for ($mbAddress = $firstMbAddress; $mbAddress < $total; $mbAddress++) {
			$this->parseMacroblock($mbAddress);

			if ($this->cabac->decodeTerminate() === 1) {
				return $mbAddress;
			}
		}

		throw new Exception('The slice did not end before the last macroblock of the picture.');
	}

	/** Address of the neighbouring macroblock, or null when it is outside the picture or slice. */
	private function neighbour(int $mbAddress, int $deltaX, int $deltaY): ?int
	{
		$x = ($mbAddress % $this->mbWidth) + $deltaX;
		$y = intdiv($mbAddress, $this->mbWidth) + $deltaY;

		if ($x < 0 || $y < 0 || $x >= $this->mbWidth || $y >= $this->mbHeight) {
			return null;
		}

		$target = $y * $this->mbWidth + $x;
		if (!isset($this->macroblocks[$target]) || $this->macroblocks[$target]['slice'] !== $this->sliceIndex) {
			return null;
		}

		return $target;
	}

	private function parseMacroblock(int $mbAddress): void
	{
		$left = $this->neighbour($mbAddress, -1, 0);
		$above = $this->neighbour($mbAddress, 0, -1);
		$mbType = $this->decodeMacroblockType($left, $above);

		$this->macroblocks[$mbAddress] = [
			'slice' => $this->sliceIndex,
			'mb_type' => $mbType,
			'transform_8x8' => false,
			'cbp_luma' => 0,
			'cbp_chroma' => 0,
			'chroma_pred_mode' => 0,
			'i16_mode' => 0,
			'luma_pred_modes' => array_fill(0, 16, 2),
			'quantiser' => $this->quantiser,
			'quantiser_delta' => 0,
			'cbf_luma_dc' => false,
			'cbf_luma' => array_fill(0, 16, false),
			'cbf_chroma_dc' => [false, false],
			'cbf_chroma_ac' => [array_fill(0, 4, false), array_fill(0, 4, false)],
			'luma_dc' => array_fill(0, 16, 0),
			'luma_levels' => [],
			'chroma_dc' => [array_fill(0, 4, 0), array_fill(0, 4, 0)],
			'chroma_ac' => [[], []],
		];

		if ($mbType === self::MB_TYPE_I_PCM) {
			throw new Exception('I_PCM macroblocks are not supported by this decoder.');
		}

		$isIntra16x16 = $mbType >= self::MB_TYPE_I_16X16_FIRST && $mbType <= self::MB_TYPE_I_16X16_LAST;

		if ($isIntra16x16) {
			$this->macroblocks[$mbAddress]['i16_mode'] = ($mbType - 1) % 4;
			$this->macroblocks[$mbAddress]['cbp_chroma'] = intdiv($mbType - 1, 4) % 3;
			$this->macroblocks[$mbAddress]['cbp_luma'] = $mbType > 12 ? 15 : 0;
			$this->macroblocks[$mbAddress]['chroma_pred_mode'] = $this->decodeChromaPredictionMode($left, $above);
		} else {
			if ($this->transform8x8Allowed) {
				$this->macroblocks[$mbAddress]['transform_8x8'] = $this->decodeTransformSizeFlag($left, $above) === 1;
			}
			$this->decodeLumaPredictionModes($mbAddress);
			$this->macroblocks[$mbAddress]['chroma_pred_mode'] = $this->decodeChromaPredictionMode($left, $above);
			$this->decodeCodedBlockPattern($mbAddress);
		}

		$macroblock = $this->macroblocks[$mbAddress];
		$hasResidual = $isIntra16x16 || $macroblock['cbp_luma'] > 0 || $macroblock['cbp_chroma'] > 0;

		if ($hasResidual) {
			$delta = $this->decodeQuantiserDelta($mbAddress);
			$this->quantiser = (($this->quantiser + $delta + self::QUANTISER_RANGE) % self::QUANTISER_RANGE + self::QUANTISER_RANGE) % self::QUANTISER_RANGE;
			$this->macroblocks[$mbAddress]['quantiser_delta'] = $delta;
		}
		$this->macroblocks[$mbAddress]['quantiser'] = $this->quantiser;

		if ($hasResidual) {
			$this->parseResidual($mbAddress, $isIntra16x16);
		}
	}

	private function decodeMacroblockType(?int $left, ?int $above): int
	{
		$conditionLeft = ($left !== null && $this->macroblocks[$left]['mb_type'] !== self::MB_TYPE_I_NXN) ? 1 : 0;
		$conditionAbove = ($above !== null && $this->macroblocks[$above]['mb_type'] !== self::MB_TYPE_I_NXN) ? 1 : 0;

		if ($this->cabac->decodeDecision(3 + $conditionLeft + $conditionAbove) === 0) {
			return self::MB_TYPE_I_NXN;
		}
		if ($this->cabac->decodeTerminate() === 1) {
			return self::MB_TYPE_I_PCM;
		}

		$mbType = 1;
		$mbType += 12 * $this->cabac->decodeDecision(6);
		if ($this->cabac->decodeDecision(7) !== 0) {
			$mbType += 4 + 4 * $this->cabac->decodeDecision(8);
		}
		$mbType += 2 * $this->cabac->decodeDecision(9);
		$mbType += $this->cabac->decodeDecision(10);

		return $mbType;
	}

	private function decodeTransformSizeFlag(?int $left, ?int $above): int
	{
		$conditionLeft = ($left !== null && $this->macroblocks[$left]['transform_8x8']) ? 1 : 0;
		$conditionAbove = ($above !== null && $this->macroblocks[$above]['transform_8x8']) ? 1 : 0;

		return $this->cabac->decodeDecision(399 + $conditionLeft + $conditionAbove);
	}

	private function decodeChromaPredictionMode(?int $left, ?int $above): int
	{
		$conditionLeft = ($left !== null && $this->macroblocks[$left]['chroma_pred_mode'] !== 0) ? 1 : 0;
		$conditionAbove = ($above !== null && $this->macroblocks[$above]['chroma_pred_mode'] !== 0) ? 1 : 0;

		if ($this->cabac->decodeDecision(64 + $conditionLeft + $conditionAbove) === 0) {
			return 0;
		}
		if ($this->cabac->decodeDecision(67) === 0) {
			return 1;
		}
		if ($this->cabac->decodeDecision(67) === 0) {
			return 2;
		}

		return 3;
	}

	/** Intra mode of the 4x4 block at these macroblock-relative grid coordinates, or null when absent. */
	private function modeAt(int $mbAddress, int $blockX, int $blockY): ?int
	{
		$deltaX = 0;
		$deltaY = 0;

		if ($blockX < 0) {
			$deltaX = -1;
			$blockX += 4;
		}
		if ($blockY < 0) {
			$deltaY = -1;
			$blockY += 4;
		}

		$target = ($deltaX === 0 && $deltaY === 0) ? $mbAddress : $this->neighbour($mbAddress, $deltaX, $deltaY);
		if ($target === null) {
			return null;
		}
		if ($this->macroblocks[$target]['mb_type'] !== self::MB_TYPE_I_NXN) {
			return 2;
		}

		return $this->macroblocks[$target]['luma_pred_modes'][$this->lumaBlockIndexByGrid[$blockY * 4 + $blockX]];
	}

	private function decodeLumaPredictionModes(int $mbAddress): void
	{
		$transform8x8 = $this->macroblocks[$mbAddress]['transform_8x8'];
		$groups = $transform8x8 ? 4 : 16;

		for ($group = 0; $group < $groups; $group++) {
			if ($transform8x8) {
				$blockX = ($group % 2) * 2;
				$blockY = intdiv($group, 2) * 2;
			} else {
				[$blockX, $blockY] = self::LUMA_BLOCK_GRID[$group];
			}

			$leftMode = $this->modeAt($mbAddress, $blockX - 1, $blockY);
			$aboveMode = $this->modeAt($mbAddress, $blockX, $blockY - 1);
			$predicted = ($leftMode === null || $aboveMode === null) ? 2 : min($leftMode, $aboveMode);
			$mode = $predicted;

			if ($this->cabac->decodeDecision(68) === 0) {
				$remainder = $this->cabac->decodeDecision(69);
				$remainder |= $this->cabac->decodeDecision(69) << 1;
				$remainder |= $this->cabac->decodeDecision(69) << 2;
				$mode = $remainder < $predicted ? $remainder : $remainder + 1;
			}

			$span = $transform8x8 ? 2 : 1;
			for ($innerY = 0; $innerY < $span; $innerY++) {
				for ($innerX = 0; $innerX < $span; $innerX++) {
					$index = $this->lumaBlockIndexByGrid[($blockY + $innerY) * 4 + ($blockX + $innerX)];
					$this->macroblocks[$mbAddress]['luma_pred_modes'][$index] = $mode;
				}
			}
		}
	}

	private function decodeCodedBlockPattern(int $mbAddress): void
	{
		$cbpLuma = 0;

		for ($block8x8 = 0; $block8x8 < 4; $block8x8++) {
			$conditionLeft = $this->lumaPatternCondition($mbAddress, $block8x8, $cbpLuma, -1, 0);
			$conditionAbove = $this->lumaPatternCondition($mbAddress, $block8x8, $cbpLuma, 0, -1);
			$cbpLuma |= $this->cabac->decodeDecision(73 + $conditionLeft + 2 * $conditionAbove) << $block8x8;
		}
		$this->macroblocks[$mbAddress]['cbp_luma'] = $cbpLuma;

		$conditionLeft = $this->chromaPatternCondition($mbAddress, -1, 0, 0);
		$conditionAbove = $this->chromaPatternCondition($mbAddress, 0, -1, 0);

		if ($this->cabac->decodeDecision(77 + $conditionLeft + 2 * $conditionAbove) === 0) {
			$this->macroblocks[$mbAddress]['cbp_chroma'] = 0;

			return;
		}

		$conditionLeft = $this->chromaPatternCondition($mbAddress, -1, 0, 1);
		$conditionAbove = $this->chromaPatternCondition($mbAddress, 0, -1, 1);
		$this->macroblocks[$mbAddress]['cbp_chroma'] = 1 + $this->cabac->decodeDecision(81 + $conditionLeft + 2 * $conditionAbove);
	}

	/** 1 when the neighbouring 8x8 luma block carries no coefficients. */
	private function lumaPatternCondition(int $mbAddress, int $block8x8, int $patternSoFar, int $deltaX, int $deltaY): int
	{
		$blockX = ($block8x8 % 2) * 2 + ($deltaX < 0 ? -1 : 0);
		$blockY = intdiv($block8x8, 2) * 2 + ($deltaY < 0 ? -1 : 0);
		$targetDeltaX = 0;
		$targetDeltaY = 0;

		if ($blockX < 0) {
			$targetDeltaX = -1;
			$blockX += 4;
		}
		if ($blockY < 0) {
			$targetDeltaY = -1;
			$blockY += 4;
		}

		$neighbourBlock = intdiv($blockY, 2) * 2 + intdiv($blockX, 2);

		if ($targetDeltaX === 0 && $targetDeltaY === 0) {
			return (($patternSoFar >> $neighbourBlock) & 1) !== 0 ? 0 : 1;
		}

		$target = $this->neighbour($mbAddress, $targetDeltaX, $targetDeltaY);
		if ($target === null || $this->macroblocks[$target]['mb_type'] === self::MB_TYPE_I_PCM) {
			return 0;
		}

		return (($this->macroblocks[$target]['cbp_luma'] >> $neighbourBlock) & 1) !== 0 ? 0 : 1;
	}

	private function chromaPatternCondition(int $mbAddress, int $deltaX, int $deltaY, int $binIndex): int
	{
		$target = $this->neighbour($mbAddress, $deltaX, $deltaY);
		if ($target === null) {
			return 0;
		}
		if ($this->macroblocks[$target]['mb_type'] === self::MB_TYPE_I_PCM) {
			return 1;
		}

		$pattern = $this->macroblocks[$target]['cbp_chroma'];

		return $binIndex === 0 ? ($pattern !== 0 ? 1 : 0) : ($pattern === 2 ? 1 : 0);
	}

	private function decodeQuantiserDelta(int $mbAddress): int
	{
		$previous = $mbAddress > 0 ? ($this->macroblocks[$mbAddress - 1]['quantiser_delta'] ?? 0) : 0;

		if ($this->cabac->decodeDecision(60 + ($previous !== 0 ? 1 : 0)) === 0) {
			return 0;
		}

		$count = 1;
		if ($this->cabac->decodeDecision(62) !== 0) {
			$count++;
			while ($this->cabac->decodeDecision(63) !== 0) {
				$count++;
				if ($count > self::MAXIMUM_QUANTISER_DELTA_BINS) {
					throw new Exception('mb_qp_delta ran past the range the standard allows.');
				}
			}
		}

		return ($count & 1) ? (($count + 1) >> 1) : -(($count + 1) >> 1);
	}

	private function parseResidual(int $mbAddress, bool $isIntra16x16): void
	{
		if ($isIntra16x16) {
			$this->macroblocks[$mbAddress]['luma_dc'] = $this->residualBlock($mbAddress, self::CATEGORY_LUMA_DC, 16, 0);
			$this->macroblocks[$mbAddress]['cbf_luma_dc'] = $this->blockHadCoefficients;
		}

		$transform8x8 = $this->macroblocks[$mbAddress]['transform_8x8'];
		$cbpLuma = $this->macroblocks[$mbAddress]['cbp_luma'];

		for ($block8x8 = 0; $block8x8 < 4; $block8x8++) {
			if ((($cbpLuma >> $block8x8) & 1) === 0) {
				continue;
			}

			if ($transform8x8) {
				$this->macroblocks[$mbAddress]['luma_levels'][$block8x8] = $this->residualBlock($mbAddress, self::CATEGORY_LUMA_8X8, 64, $block8x8);
				for ($inner = 0; $inner < 4; $inner++) {
					$this->macroblocks[$mbAddress]['cbf_luma'][$block8x8 * 4 + $inner] = $this->blockHadCoefficients;
				}
				continue;
			}

			for ($inner = 0; $inner < 4; $inner++) {
				$blockIndex = $block8x8 * 4 + $inner;
				$category = $isIntra16x16 ? self::CATEGORY_LUMA_AC : self::CATEGORY_LUMA_4X4;
				$maximum = $isIntra16x16 ? 15 : 16;
				$this->macroblocks[$mbAddress]['luma_levels'][$blockIndex] = $this->residualBlock($mbAddress, $category, $maximum, $blockIndex);
				$this->macroblocks[$mbAddress]['cbf_luma'][$blockIndex] = $this->blockHadCoefficients;
			}
		}

		$cbpChroma = $this->macroblocks[$mbAddress]['cbp_chroma'];

		if ($cbpChroma > 0) {
			for ($plane = 0; $plane < 2; $plane++) {
				$this->macroblocks[$mbAddress]['chroma_dc'][$plane] = $this->residualBlock($mbAddress, self::CATEGORY_CHROMA_DC, 4, $plane);
				$this->macroblocks[$mbAddress]['cbf_chroma_dc'][$plane] = $this->blockHadCoefficients;
			}
		}

		if ($cbpChroma === 2) {
			for ($plane = 0; $plane < 2; $plane++) {
				for ($block = 0; $block < 4; $block++) {
					$this->macroblocks[$mbAddress]['chroma_ac'][$plane][$block] = $this->residualBlock($mbAddress, self::CATEGORY_CHROMA_AC, 15, $plane * 4 + $block);
					$this->macroblocks[$mbAddress]['cbf_chroma_ac'][$plane][$block] = $this->blockHadCoefficients;
				}
			}
		}
	}

	/** @return array<int, int> */
	private function residualBlock(int $mbAddress, int $category, int $maximumCoefficients, int $blockIndex): array
	{
		$coefficients = array_fill(0, $maximumCoefficients, 0);

		/* An 8x8 luma block carries no coded_block_flag outside 4:4:4; its pattern bit already said so. */
		if ($maximumCoefficients !== 64) {
			$contextIndex = self::CODED_BLOCK_FLAG_BASES[$category]
				+ $this->codedBlockFlagContext($mbAddress, $category, $blockIndex);

			if ($this->cabac->decodeDecision($contextIndex) === 0) {
				$this->blockHadCoefficients = false;

				return $coefficients;
			}
		}
		$this->blockHadCoefficients = true;

		[$significantBase, $lastBase, $levelBase] = self::CATEGORY_BASES[$category];
		$positions = [];
		$sawLast = false;

		for ($index = 0; $index < $maximumCoefficients - 1; $index++) {
			$increment = $category === self::CATEGORY_LUMA_8X8 ? self::SIGNIFICANT_CONTEXT_8X8[$index] : $index;

			if ($this->cabac->decodeDecision($significantBase + $increment) === 0) {
				continue;
			}

			$positions[] = $index;
			$lastIncrement = $category === self::CATEGORY_LUMA_8X8 ? self::LAST_CONTEXT_8X8[$index] : $index;

			if ($this->cabac->decodeDecision($lastBase + $lastIncrement) !== 0) {
				$sawLast = true;
				break;
			}
		}

		if (!$sawLast) {
			$positions[] = $maximumCoefficients - 1;
		}

		$equalToOne = 0;
		$greaterThanOne = 0;

		for ($index = count($positions) - 1; $index >= 0; $index--) {
			$increment = $greaterThanOne !== 0 ? 0 : min(4, 1 + $equalToOne);
			$level = 1;

			if ($this->cabac->decodeDecision($levelBase + $increment) !== 0) {
				$suffixIncrement = 5 + min(4 - ($category === self::CATEGORY_CHROMA_DC ? 1 : 0), $greaterThanOne);
				$level = 2;

				while ($level < self::LEVEL_PREFIX_LIMIT && $this->cabac->decodeDecision($levelBase + $suffixIncrement) !== 0) {
					$level++;
				}
				if ($level === self::LEVEL_PREFIX_LIMIT) {
					$level += $this->decodeExpGolombSuffix();
				}
				$greaterThanOne++;
			} else {
				$equalToOne++;
			}

			$coefficients[$positions[$index]] = $this->cabac->decodeBypass() === 0 ? $level : -$level;
		}

		return $coefficients;
	}

	private function decodeExpGolombSuffix(): int
	{
		$value = 0;
		$order = 0;

		while ($this->cabac->decodeBypass() === 1) {
			$value += 1 << $order;
			$order++;
			if ($order > self::EXP_GOLOMB_SUFFIX_LIMIT) {
				throw new Exception('coeff_abs_level_minus1 ran past the range the standard allows.');
			}
		}
		while ($order-- > 0) {
			$value += $this->cabac->decodeBypass() << $order;
		}

		return $value;
	}

	private function codedBlockFlagContext(int $mbAddress, int $category, int $blockIndex): int
	{
		return $this->codedBlockFlagCondition($mbAddress, $category, $blockIndex, -1, 0)
			+ 2 * $this->codedBlockFlagCondition($mbAddress, $category, $blockIndex, 0, -1);
	}

	private function codedBlockFlagCondition(int $mbAddress, int $category, int $blockIndex, int $deltaX, int $deltaY): int
	{
		if ($category === self::CATEGORY_LUMA_DC || $category === self::CATEGORY_CHROMA_DC) {
			$target = $this->neighbour($mbAddress, $deltaX, $deltaY);
			if ($target === null) {
				return 1;
			}

			$neighbour = $this->macroblocks[$target];
			if ($neighbour['mb_type'] === self::MB_TYPE_I_PCM) {
				return 1;
			}
			if ($category === self::CATEGORY_LUMA_DC) {
				$isIntra16x16 = $neighbour['mb_type'] >= self::MB_TYPE_I_16X16_FIRST
					&& $neighbour['mb_type'] <= self::MB_TYPE_I_16X16_LAST;

				return ($isIntra16x16 && $neighbour['cbf_luma_dc']) ? 1 : 0;
			}
			if ($neighbour['cbp_chroma'] === 0) {
				return 0;
			}

			return $neighbour['cbf_chroma_dc'][$blockIndex] ? 1 : 0;
		}

		if ($category === self::CATEGORY_CHROMA_AC) {
			$plane = intdiv($blockIndex, 4);
			$block = $blockIndex % 4;
			$blockX = ($block % 2) + $deltaX;
			$blockY = intdiv($block, 2) + $deltaY;
			$targetDeltaX = 0;
			$targetDeltaY = 0;

			if ($blockX < 0) {
				$targetDeltaX = -1;
				$blockX += 2;
			}
			if ($blockY < 0) {
				$targetDeltaY = -1;
				$blockY += 2;
			}

			if ($targetDeltaX === 0 && $targetDeltaY === 0) {
				return $this->macroblocks[$mbAddress]['cbf_chroma_ac'][$plane][$blockY * 2 + $blockX] ? 1 : 0;
			}

			$target = $this->neighbour($mbAddress, $targetDeltaX, $targetDeltaY);
			if ($target === null) {
				return 1;
			}

			$neighbour = $this->macroblocks[$target];
			if ($neighbour['mb_type'] === self::MB_TYPE_I_PCM) {
				return 1;
			}
			if ($neighbour['cbp_chroma'] !== 2) {
				return 0;
			}

			return $neighbour['cbf_chroma_ac'][$plane][$blockY * 2 + $blockX] ? 1 : 0;
		}

		if ($category === self::CATEGORY_LUMA_8X8) {
			$baseX = ($blockIndex % 2) * 2;
			$baseY = intdiv($blockIndex, 2) * 2;
		} else {
			[$baseX, $baseY] = self::LUMA_BLOCK_GRID[$blockIndex];
		}

		$blockX = $baseX + $deltaX;
		$blockY = $baseY + $deltaY;
		$targetDeltaX = 0;
		$targetDeltaY = 0;

		if ($blockX < 0) {
			$targetDeltaX = -1;
			$blockX += 4;
		}
		if ($blockY < 0) {
			$targetDeltaY = -1;
			$blockY += 4;
		}

		if ($targetDeltaX === 0 && $targetDeltaY === 0) {
			return $this->macroblocks[$mbAddress]['cbf_luma'][$this->lumaBlockIndexByGrid[$blockY * 4 + $blockX]] ? 1 : 0;
		}

		$target = $this->neighbour($mbAddress, $targetDeltaX, $targetDeltaY);
		if ($target === null) {
			return 1;
		}

		$neighbour = $this->macroblocks[$target];
		if ($neighbour['mb_type'] === self::MB_TYPE_I_PCM) {
			return 1;
		}

		$neighbourBlock = intdiv($blockY, 2) * 2 + intdiv($blockX, 2);
		if ((($neighbour['cbp_luma'] >> $neighbourBlock) & 1) === 0) {
			return 0;
		}

		return $neighbour['cbf_luma'][$this->lumaBlockIndexByGrid[$blockY * 4 + $blockX]] ? 1 : 0;
	}
}
