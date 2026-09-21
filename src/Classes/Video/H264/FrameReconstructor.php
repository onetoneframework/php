<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Video\H264;

use function abs;
use function array_fill;
use function array_search;
use function array_slice;
use function array_sum;
use function intdiv;
use function max;
use function min;

/**
 * Class FrameReconstructor
 *
 * Turns the parsed macroblocks of an intra picture into luma and chroma sample planes.
 */
final class FrameReconstructor
{
	private const ZIGZAG_4X4 = [0, 1, 4, 8, 5, 2, 3, 6, 9, 12, 13, 10, 7, 11, 14, 15];
	private const ZIGZAG_8X8 = [
		0,1,8,16,9,2,3,10,17,24,32,25,18,11,4,5,12,19,26,33,40,48,41,34,27,20,13,6,7,14,21,28,
		35,42,49,56,57,50,43,36,29,22,15,23,30,37,44,51,58,59,52,45,38,31,39,46,53,60,61,54,47,55,62,63,
	];

	private const NORM_ADJUST_4X4 = [
		[10, 16, 13], [11, 18, 14], [13, 20, 16], [14, 23, 18], [16, 25, 20], [18, 29, 23],
	];
	private const POSITION_CLASS_4X4 = [0,2,0,2, 2,1,2,1, 0,2,0,2, 2,1,2,1];

	private const NORM_ADJUST_8X8 = [
		[20,18,32,19,25,24], [22,19,35,21,28,26], [26,23,42,24,33,31],
		[28,25,45,26,35,33], [32,28,51,30,40,38], [36,32,58,34,46,43],
	];
	private const POSITION_CLASS_8X8 = [0,3,4,3,3,1,5,1,4,5,2,5,3,1,5,1];

	private const CHROMA_QUANTISER_TABLE = [
		0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,
		29,30,31,32,32,33,34,34,35,35,36,36,37,37,37,38,38,38,39,39,39,39,
	];
	private const CHROMA_QUANTISER_TABLE_FLOOR = 30;

	private const BLOCK_PIXEL_OFFSET = [
		[0,0],[4,0],[0,4],[4,4],[8,0],[12,0],[8,4],[12,4],
		[0,8],[4,8],[0,12],[4,12],[8,8],[12,8],[8,12],[12,12],
	];

	private const ALPHA_TABLE = [
		0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
		4,4,5,6,7,8,9,10,12,13,15,17,20,22,25,28,
		32,36,40,45,50,56,63,71,80,90,101,113,127,144,162,182,
		203,226,255,255,
	];
	private const BETA_TABLE = [
		0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,
		2,2,2,3,3,3,3,4,4,4,6,6,7,7,8,8,
		9,9,10,10,11,11,12,12,13,13,14,14,15,15,16,16,
		17,17,18,18,
	];
	private const CLIPPING_TABLE = [
		[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],
		[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],[0,0,0],
		[0,0,0],[0,0,1],[0,0,1],[0,0,1],[0,0,1],[0,1,1],[0,1,1],[1,1,1],
		[1,1,1],[1,1,1],[1,1,1],[1,1,2],[1,1,2],[1,1,2],[1,1,2],[1,2,3],
		[1,2,3],[2,2,3],[2,2,4],[2,3,4],[2,3,4],[3,3,5],[3,4,6],[3,4,6],
		[4,5,7],[4,5,8],[4,6,9],[5,7,10],[6,8,11],[6,8,13],[7,10,14],[8,11,16],
		[9,12,18],[10,13,20],[11,15,23],[13,17,25],
	];

	private const STRENGTH_MACROBLOCK_EDGE = 4;
	private const STRENGTH_INTERNAL_EDGE = 3;
	private const DEBLOCKING_DISABLED = 1;
	private const DEBLOCKING_WITHIN_SLICE_ONLY = 2;
	private const QUANTISER_LIMIT = 51;

	private SliceParser $parser;
	private int $mbWidth;
	private int $mbHeight;
	private int $chromaQuantiserOffset;
	private int $secondChromaQuantiserOffset;

	private int $lumaWidth;
	private int $lumaHeight;
	private int $chromaWidth;
	private int $chromaHeight;

	/** @var array<int, array<int, int>> */
	public array $luma = [];
	/** @var array<int, array<int, array<int, int>>> */
	public array $chroma = [];

	private int $currentMbAddress = 0;
	private int $currentBlockOrder = 0;
	private int $alphaOffset = 0;
	private int $betaOffset = 0;
	private int $deblockingMode = 0;

	public function __construct(
		SliceParser $parser,
		int $mbWidth,
		int $mbHeight,
		int $chromaQuantiserOffset,
		int $secondChromaQuantiserOffset
	) {
		$this->parser = $parser;
		$this->mbWidth = $mbWidth;
		$this->mbHeight = $mbHeight;
		$this->chromaQuantiserOffset = $chromaQuantiserOffset;
		$this->secondChromaQuantiserOffset = $secondChromaQuantiserOffset;

		$this->lumaWidth = $mbWidth * 16;
		$this->lumaHeight = $mbHeight * 16;
		$this->chromaWidth = $mbWidth * 8;
		$this->chromaHeight = $mbHeight * 8;

		$this->luma = array_fill(0, $this->lumaHeight, array_fill(0, $this->lumaWidth, 0));
		$this->chroma = [
			array_fill(0, $this->chromaHeight, array_fill(0, $this->chromaWidth, 0)),
			array_fill(0, $this->chromaHeight, array_fill(0, $this->chromaWidth, 0)),
		];
	}

	public function setDeblockingParameters(int $mode, int $alphaOffset, int $betaOffset): void
	{
		$this->deblockingMode = $mode;
		$this->alphaOffset = $alphaOffset;
		$this->betaOffset = $betaOffset;
	}

	public function reconstruct(): void
	{
		$total = $this->mbWidth * $this->mbHeight;

		for ($mbAddress = 0; $mbAddress < $total; $mbAddress++) {
			$this->currentMbAddress = $mbAddress;
			$this->reconstructMacroblock($mbAddress);
		}
	}

	private function chromaQuantiser(int $lumaQuantiser, int $plane): int
	{
		$offset = $plane === 0 ? $this->chromaQuantiserOffset : $this->secondChromaQuantiserOffset;
		$index = max(-12, min(self::QUANTISER_LIMIT, $lumaQuantiser + $offset));

		return $index < self::CHROMA_QUANTISER_TABLE_FLOOR ? $index : self::CHROMA_QUANTISER_TABLE[$index];
	}

	private function reconstructMacroblock(int $mbAddress): void
	{
		$macroblock = $this->parser->macroblocks[$mbAddress];
		$mbX = ($mbAddress % $this->mbWidth) * 16;
		$mbY = intdiv($mbAddress, $this->mbWidth) * 16;
		$quantiser = $macroblock['quantiser'];
		$isIntra16x16 = $macroblock['mb_type'] >= SliceParser::MB_TYPE_I_16X16_FIRST
			&& $macroblock['mb_type'] <= SliceParser::MB_TYPE_I_16X16_LAST;

		if ($isIntra16x16) {
			$this->currentBlockOrder = 0;
			$this->predictLuma16x16($mbX, $mbY, $macroblock['i16_mode']);
			$dc = $this->inverseHadamard4x4($macroblock['luma_dc'], $quantiser);

			for ($block = 0; $block < 16; $block++) {
				$this->currentBlockOrder = $block;
				$coefficients = array_fill(0, 16, 0);
				$coefficients[0] = $dc[$block];
				$levels = $macroblock['luma_levels'][$block] ?? array_fill(0, 15, 0);

				for ($index = 0; $index < 15; $index++) {
					$coefficients[self::ZIGZAG_4X4[$index + 1]] = $this->dequantiseCoefficient($levels[$index], $quantiser, $index + 1);
				}

				$this->addResidual4x4(
					$this->luma,
					$mbX + self::BLOCK_PIXEL_OFFSET[$block][0],
					$mbY + self::BLOCK_PIXEL_OFFSET[$block][1],
					$coefficients,
					$this->lumaWidth,
					$this->lumaHeight
				);
			}
		} elseif ($macroblock['transform_8x8']) {
			for ($block8x8 = 0; $block8x8 < 4; $block8x8++) {
				$this->currentBlockOrder = $block8x8;
				$blockX = $mbX + ($block8x8 % 2) * 8;
				$blockY = $mbY + intdiv($block8x8, 2) * 8;
				$this->predictLuma8x8($blockX, $blockY, $macroblock['luma_pred_modes'][$block8x8 * 4]);

				if ((($macroblock['cbp_luma'] >> $block8x8) & 1) === 0) {
					continue;
				}

				$levels = $macroblock['luma_levels'][$block8x8] ?? array_fill(0, 64, 0);
				$coefficients = array_fill(0, 64, 0);
				for ($index = 0; $index < 64; $index++) {
					$raster = self::ZIGZAG_8X8[$index];
					$coefficients[$raster] = $this->dequantiseCoefficient8x8($levels[$index], $quantiser, $raster);
				}

				$this->addResidual8x8($blockX, $blockY, $coefficients);
			}
		} else {
			foreach (SliceParser::LUMA_BLOCK_GRID as $block => [$gridX, $gridY]) {
				$this->currentBlockOrder = $block;
				$blockX = $mbX + $gridX * 4;
				$blockY = $mbY + $gridY * 4;
				$this->predictLuma4x4($blockX, $blockY, $macroblock['luma_pred_modes'][$block]);

				if ((($macroblock['cbp_luma'] >> intdiv($block, 4)) & 1) === 0) {
					continue;
				}

				$levels = $macroblock['luma_levels'][$block] ?? array_fill(0, 16, 0);
				$coefficients = array_fill(0, 16, 0);
				for ($index = 0; $index < 16; $index++) {
					$coefficients[self::ZIGZAG_4X4[$index]] = $this->dequantiseCoefficient($levels[$index], $quantiser, $index);
				}

				$this->addResidual4x4($this->luma, $blockX, $blockY, $coefficients, $this->lumaWidth, $this->lumaHeight);
			}
		}

		$this->reconstructChroma($mbAddress, $macroblock, $quantiser);
	}

	private function reconstructChroma(int $mbAddress, array $macroblock, int $lumaQuantiser): void
	{
		$mbX = ($mbAddress % $this->mbWidth) * 8;
		$mbY = intdiv($mbAddress, $this->mbWidth) * 8;

		for ($plane = 0; $plane < 2; $plane++) {
			$this->predictChroma($plane, $mbX, $mbY, $macroblock['chroma_pred_mode']);

			if ($macroblock['cbp_chroma'] === 0) {
				continue;
			}

			$quantiser = $this->chromaQuantiser($lumaQuantiser, $plane);
			$dc = $this->inverseHadamard2x2($macroblock['chroma_dc'][$plane], $quantiser);

			for ($block = 0; $block < 4; $block++) {
				$coefficients = array_fill(0, 16, 0);
				$coefficients[0] = $dc[$block];

				if ($macroblock['cbp_chroma'] === 2) {
					$levels = $macroblock['chroma_ac'][$plane][$block] ?? array_fill(0, 15, 0);
					for ($index = 0; $index < 15; $index++) {
						$coefficients[self::ZIGZAG_4X4[$index + 1]] = $this->dequantiseCoefficient($levels[$index], $quantiser, $index + 1);
					}
				}

				$this->addResidual4x4(
					$this->chroma[$plane],
					$mbX + ($block % 2) * 4,
					$mbY + intdiv($block, 2) * 4,
					$coefficients,
					$this->chromaWidth,
					$this->chromaHeight
				);
			}
		}
	}

	private function dequantiseCoefficient(int $level, int $quantiser, int $scanIndex): int
	{
		if ($level === 0) {
			return 0;
		}

		$raster = self::ZIGZAG_4X4[$scanIndex];
		$factor = self::NORM_ADJUST_4X4[$quantiser % 6][self::POSITION_CLASS_4X4[$raster]];

		return ($level * $factor) << intdiv($quantiser, 6);
	}

	private function dequantiseCoefficient8x8(int $level, int $quantiser, int $raster): int
	{
		if ($level === 0) {
			return 0;
		}

		$class = self::POSITION_CLASS_8X8[(intdiv($raster, 8) % 4) * 4 + ($raster % 8) % 4];
		$scaled = $level * self::NORM_ADJUST_8X8[$quantiser % 6][$class] * 16;
		$period = intdiv($quantiser, 6);

		if ($period >= 6) {
			return $scaled << ($period - 6);
		}

		return ($scaled + (1 << (5 - $period))) >> (6 - $period);
	}

	/** @return array<int, int> */
	private function inverseHadamard4x4(array $dc, int $quantiser): array
	{
		$input = [[0,0,0,0],[0,0,0,0],[0,0,0,0],[0,0,0,0]];
		foreach (self::ZIGZAG_4X4 as $scanIndex => $raster) {
			$input[intdiv($raster, 4)][$raster % 4] = $dc[$scanIndex] ?? 0;
		}

		for ($row = 0; $row < 4; $row++) {
			$a = $input[$row][0] + $input[$row][2];
			$b = $input[$row][0] - $input[$row][2];
			$c = $input[$row][1] - $input[$row][3];
			$d = $input[$row][1] + $input[$row][3];
			$input[$row] = [$a + $d, $b + $c, $b - $c, $a - $d];
		}

		$transformed = [];
		for ($column = 0; $column < 4; $column++) {
			$a = $input[0][$column] + $input[2][$column];
			$b = $input[0][$column] - $input[2][$column];
			$c = $input[1][$column] - $input[3][$column];
			$d = $input[1][$column] + $input[3][$column];
			$transformed[0][$column] = $a + $d;
			$transformed[1][$column] = $b + $c;
			$transformed[2][$column] = $b - $c;
			$transformed[3][$column] = $a - $d;
		}

		$factor = self::NORM_ADJUST_4X4[$quantiser % 6][0];
		$period = intdiv($quantiser, 6);
		$scaled = [];

		for ($row = 0; $row < 4; $row++) {
			for ($column = 0; $column < 4; $column++) {
				$value = $transformed[$row][$column] * $factor * 16;
				$scaled[$row * 4 + $column] = $period >= 6
					? $value << ($period - 6)
					: ($value + (1 << (5 - $period))) >> (6 - $period);
			}
		}

		$byBlock = [];
		foreach (SliceParser::LUMA_BLOCK_GRID as $block => [$x, $y]) {
			$byBlock[$block] = $scaled[$y * 4 + $x];
		}

		return $byBlock;
	}

	/** @return array<int, int> */
	private function inverseHadamard2x2(array $dc, int $quantiser): array
	{
		$a = $dc[0] + $dc[1];
		$b = $dc[0] - $dc[1];
		$c = $dc[2] + $dc[3];
		$d = $dc[2] - $dc[3];

		$factor = self::NORM_ADJUST_4X4[$quantiser % 6][0];
		$period = intdiv($quantiser, 6);
		$result = [];

		foreach ([$a + $c, $b + $d, $a - $c, $b - $d] as $index => $value) {
			$result[$index] = (($value * $factor * 16) << $period) >> 5;
		}

		return $result;
	}

	private function inverseTransform4x4(array $coefficients): array
	{
		$rows = [];
		for ($row = 0; $row < 4; $row++) {
			$base = $row * 4;
			$c0 = $coefficients[$base];
			$c1 = $coefficients[$base + 1];
			$c2 = $coefficients[$base + 2];
			$c3 = $coefficients[$base + 3];

			$e0 = $c0 + $c2;
			$e1 = $c0 - $c2;
			$e2 = ($c1 >> 1) - $c3;
			$e3 = $c1 + ($c3 >> 1);

			$rows[$row] = [$e0 + $e3, $e1 + $e2, $e1 - $e2, $e0 - $e3];
		}

		$output = [];
		for ($column = 0; $column < 4; $column++) {
			$g0 = $rows[0][$column] + $rows[2][$column];
			$g1 = $rows[0][$column] - $rows[2][$column];
			$g2 = ($rows[1][$column] >> 1) - $rows[3][$column];
			$g3 = $rows[1][$column] + ($rows[3][$column] >> 1);

			$output[0][$column] = ($g0 + $g3 + 32) >> 6;
			$output[1][$column] = ($g1 + $g2 + 32) >> 6;
			$output[2][$column] = ($g1 - $g2 + 32) >> 6;
			$output[3][$column] = ($g0 - $g3 + 32) >> 6;
		}

		return $output;
	}

	private function inverseTransform8x8(array $coefficients): array
	{
		$rows = [];
		for ($row = 0; $row < 8; $row++) {
			$rows[$row] = $this->transform8x8Line(array_slice($coefficients, $row * 8, 8));
		}

		$columns = [];
		for ($column = 0; $column < 8; $column++) {
			$line = [];
			for ($row = 0; $row < 8; $row++) {
				$line[$row] = $rows[$row][$column];
			}
			$columns[$column] = $this->transform8x8Line($line);
		}

		$output = [];
		for ($row = 0; $row < 8; $row++) {
			for ($column = 0; $column < 8; $column++) {
				$output[$row][$column] = ($columns[$column][$row] + 32) >> 6;
			}
		}

		return $output;
	}

	private function transform8x8Line(array $c): array
	{
		$a0 = $c[0] + $c[4];
		$a2 = $c[0] - $c[4];
		$a4 = ($c[2] >> 1) - $c[6];
		$a6 = ($c[6] >> 1) + $c[2];

		$b0 = $a0 + $a6;
		$b2 = $a2 + $a4;
		$b4 = $a2 - $a4;
		$b6 = $a0 - $a6;

		$a1 = -$c[3] + $c[5] - $c[7] - ($c[7] >> 1);
		$a3 = $c[1] + $c[7] - $c[3] - ($c[3] >> 1);
		$a5 = -$c[1] + $c[7] + $c[5] + ($c[5] >> 1);
		$a7 = $c[3] + $c[5] + $c[1] + ($c[1] >> 1);

		$b1 = $a1 + ($a7 >> 2);
		$b3 = $a3 + ($a5 >> 2);
		$b5 = ($a3 >> 2) - $a5;
		$b7 = $a7 - ($a1 >> 2);

		return [
			$b0 + $b7, $b2 + $b5, $b4 + $b3, $b6 + $b1,
			$b6 - $b1, $b4 - $b3, $b2 - $b5, $b0 - $b7,
		];
	}

	private function addResidual4x4(array &$plane, int $x, int $y, array $coefficients, int $width, int $height): void
	{
		$residual = $this->inverseTransform4x4($coefficients);

		for ($row = 0; $row < 4; $row++) {
			if ($y + $row >= $height) {
				break;
			}
			for ($column = 0; $column < 4; $column++) {
				if ($x + $column >= $width) {
					break;
				}
				$value = $plane[$y + $row][$x + $column] + $residual[$row][$column];
				$plane[$y + $row][$x + $column] = $value < 0 ? 0 : ($value > 255 ? 255 : $value);
			}
		}
	}

	private function addResidual8x8(int $x, int $y, array $coefficients): void
	{
		$residual = $this->inverseTransform8x8($coefficients);

		for ($row = 0; $row < 8; $row++) {
			for ($column = 0; $column < 8; $column++) {
				$value = $this->luma[$y + $row][$x + $column] + $residual[$row][$column];
				$this->luma[$y + $row][$x + $column] = $value < 0 ? 0 : ($value > 255 ? 255 : $value);
			}
		}
	}

	/** Whether the luma sample at this position has already been reconstructed in this slice. */
	private function lumaAvailable(int $x, int $y, int $blockSize): bool
	{
		if ($x < 0 || $y < 0 || $x >= $this->lumaWidth || $y >= $this->lumaHeight) {
			return false;
		}

		$mbAddress = intdiv($y, 16) * $this->mbWidth + intdiv($x, 16);
		if ($mbAddress > $this->currentMbAddress) {
			return false;
		}
		if ($mbAddress < $this->currentMbAddress) {
			return $this->parser->macroblocks[$mbAddress]['slice']
				=== $this->parser->macroblocks[$this->currentMbAddress]['slice'];
		}

		$gridX = intdiv($x % 16, $blockSize);
		$gridY = intdiv($y % 16, $blockSize);

		if ($blockSize === 8) {
			return ($gridY * 2 + $gridX) < $this->currentBlockOrder;
		}

		$order = array_search([$gridX, $gridY], SliceParser::LUMA_BLOCK_GRID, true);

		return $order !== false && $order < $this->currentBlockOrder;
	}

	private function chromaAvailable(int $x, int $y): bool
	{
		if ($x < 0 || $y < 0 || $x >= $this->chromaWidth || $y >= $this->chromaHeight) {
			return false;
		}

		$mbAddress = intdiv($y, 8) * $this->mbWidth + intdiv($x, 8);
		if ($mbAddress >= $this->currentMbAddress) {
			return false;
		}

		return $this->parser->macroblocks[$mbAddress]['slice']
			=== $this->parser->macroblocks[$this->currentMbAddress]['slice'];
	}

	private function predictLuma4x4(int $x, int $y, int $mode): void
	{
		$leftAvailable = $this->lumaAvailable($x - 1, $y, 4);
		$topAvailable = $this->lumaAvailable($x, $y - 1, 4);
		$topLeftAvailable = $this->lumaAvailable($x - 1, $y - 1, 4);
		$topRightAvailable = $this->lumaAvailable($x + 4, $y - 1, 4);

		$left = [];
		for ($index = 0; $index < 4; $index++) {
			$left[$index] = $leftAvailable ? $this->luma[$y + $index][$x - 1] : 0;
		}

		$top = [];
		for ($index = 0; $index < 4; $index++) {
			$top[$index] = $topAvailable ? $this->luma[$y - 1][$x + $index] : 0;
		}
		for ($index = 4; $index < 8; $index++) {
			$top[$index] = $topRightAvailable
				? $this->luma[$y - 1][$x + $index]
				: ($topAvailable ? $top[3] : 0);
		}

		$prediction = $this->predict4x4($mode, $left, $top, $topLeftAvailable ? $this->luma[$y - 1][$x - 1] : 0, $leftAvailable, $topAvailable);

		for ($row = 0; $row < 4; $row++) {
			for ($column = 0; $column < 4; $column++) {
				$this->luma[$y + $row][$x + $column] = $prediction[$row][$column];
			}
		}
	}

	private function predict4x4(int $mode, array $left, array $top, int $topLeft, bool $leftAvailable, bool $topAvailable): array
	{
		$prediction = array_fill(0, 4, array_fill(0, 4, 128));
		$average2 = static fn (int $a, int $b): int => ($a + $b + 1) >> 1;
		$average3 = static fn (int $a, int $b, int $c): int => ($a + 2 * $b + $c + 2) >> 2;

		switch ($mode) {
			case 0:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						$prediction[$row][$column] = $top[$column];
					}
				}
				break;

			case 1:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						$prediction[$row][$column] = $left[$row];
					}
				}
				break;

			case 2:
				if ($leftAvailable && $topAvailable) {
					$value = (array_sum(array_slice($top, 0, 4)) + array_sum($left) + 4) >> 3;
				} elseif ($leftAvailable) {
					$value = (array_sum($left) + 2) >> 2;
				} elseif ($topAvailable) {
					$value = (array_sum(array_slice($top, 0, 4)) + 2) >> 2;
				} else {
					$value = 128;
				}
				$prediction = array_fill(0, 4, array_fill(0, 4, $value));
				break;

			case 3:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						$index = $column + $row;
						$prediction[$row][$column] = $index === 6
							? $average3($top[6], $top[7], $top[7])
							: $average3($top[$index], $top[$index + 1], $top[$index + 2]);
					}
				}
				break;

			case 4:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						if ($column > $row) {
							$index = $column - $row;
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $top[$index - 2] : $topLeft,
								$index >= 1 ? $top[$index - 1] : $topLeft,
								$top[$index]
							);
						} elseif ($column < $row) {
							$index = $row - $column;
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $left[$index - 2] : $topLeft,
								$index >= 1 ? $left[$index - 1] : $topLeft,
								$left[$index]
							);
						} else {
							$prediction[$row][$column] = $average3($top[0], $topLeft, $left[0]);
						}
					}
				}
				break;

			case 5:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						$zone = 2 * $column - $row;
						$index = $column - ($row >> 1);

						if ($zone >= 0 && ($zone % 2) === 0) {
							$prediction[$row][$column] = $average2($index >= 1 ? $top[$index - 1] : $topLeft, $top[$index]);
						} elseif ($zone >= 0) {
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $top[$index - 2] : $topLeft,
								$index >= 1 ? $top[$index - 1] : $topLeft,
								$top[$index]
							);
						} elseif ($zone === -1) {
							$prediction[$row][$column] = $average3($left[0], $topLeft, $top[0]);
						} else {
							$prediction[$row][$column] = $average3(
								$left[$row - 1],
								$left[$row - 2],
								$row >= 3 ? $left[$row - 3] : $topLeft
							);
						}
					}
				}
				break;

			case 6:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						$zone = 2 * $row - $column;
						$index = $row - ($column >> 1);

						if ($zone >= 0 && ($zone % 2) === 0) {
							$prediction[$row][$column] = $average2($index >= 1 ? $left[$index - 1] : $topLeft, $left[$index]);
						} elseif ($zone >= 0) {
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $left[$index - 2] : $topLeft,
								$index >= 1 ? $left[$index - 1] : $topLeft,
								$left[$index]
							);
						} elseif ($zone === -1) {
							$prediction[$row][$column] = $average3($left[0], $topLeft, $top[0]);
						} else {
							$prediction[$row][$column] = $average3(
								$top[$column - 1],
								$top[$column - 2],
								$column >= 3 ? $top[$column - 3] : $topLeft
							);
						}
					}
				}
				break;

			case 7:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						$index = $column + ($row >> 1);
						$prediction[$row][$column] = ($row % 2) === 0
							? $average2($top[$index], $top[$index + 1])
							: $average3($top[$index], $top[$index + 1], $top[$index + 2]);
					}
				}
				break;

			case 8:
				for ($row = 0; $row < 4; $row++) {
					for ($column = 0; $column < 4; $column++) {
						$zone = $column + 2 * $row;
						$index = $row + ($column >> 1);

						if ($zone === 0 || $zone === 2 || $zone === 4) {
							$prediction[$row][$column] = $average2($left[$index], $left[$index + 1]);
						} elseif ($zone === 1 || $zone === 3) {
							$prediction[$row][$column] = $average3($left[$index], $left[$index + 1], $left[$index + 2]);
						} elseif ($zone === 5) {
							$prediction[$row][$column] = $average3($left[2], $left[3], $left[3]);
						} else {
							$prediction[$row][$column] = $left[3];
						}
					}
				}
				break;
		}

		return $prediction;
	}

	private function predictLuma16x16(int $x, int $y, int $mode): void
	{
		$leftAvailable = $this->lumaAvailable($x - 1, $y, 16);
		$topAvailable = $this->lumaAvailable($x, $y - 1, 16);
		$topLeftAvailable = $this->lumaAvailable($x - 1, $y - 1, 16);
		$prediction = array_fill(0, 16, array_fill(0, 16, 128));

		switch ($mode) {
			case 0:
				if ($topAvailable) {
					for ($row = 0; $row < 16; $row++) {
						for ($column = 0; $column < 16; $column++) {
							$prediction[$row][$column] = $this->luma[$y - 1][$x + $column];
						}
					}
				}
				break;

			case 1:
				if ($leftAvailable) {
					for ($row = 0; $row < 16; $row++) {
						$value = $this->luma[$y + $row][$x - 1];
						for ($column = 0; $column < 16; $column++) {
							$prediction[$row][$column] = $value;
						}
					}
				}
				break;

			case 2:
				$sum = 0;
				$count = 0;
				if ($topAvailable) {
					for ($column = 0; $column < 16; $column++) {
						$sum += $this->luma[$y - 1][$x + $column];
					}
					$count += 16;
				}
				if ($leftAvailable) {
					for ($row = 0; $row < 16; $row++) {
						$sum += $this->luma[$y + $row][$x - 1];
					}
					$count += 16;
				}
				$value = $count === 32 ? ($sum + 16) >> 5 : ($count === 16 ? ($sum + 8) >> 4 : 128);
				$prediction = array_fill(0, 16, array_fill(0, 16, $value));
				break;

			case 3:
				if ($leftAvailable && $topAvailable && $topLeftAvailable) {
					$horizontal = 0;
					$vertical = 0;

					for ($index = 0; $index < 8; $index++) {
						$right = $this->luma[$y - 1][$x + 8 + $index];
						$mirrored = $index === 7 ? $this->luma[$y - 1][$x - 1] : $this->luma[$y - 1][$x + 6 - $index];
						$horizontal += ($index + 1) * ($right - $mirrored);

						$below = $this->luma[$y + 8 + $index][$x - 1];
						$above = $index === 7 ? $this->luma[$y - 1][$x - 1] : $this->luma[$y + 6 - $index][$x - 1];
						$vertical += ($index + 1) * ($below - $above);
					}

					$base = 16 * ($this->luma[$y + 15][$x - 1] + $this->luma[$y - 1][$x + 15]);
					$slopeX = (5 * $horizontal + 32) >> 6;
					$slopeY = (5 * $vertical + 32) >> 6;

					for ($row = 0; $row < 16; $row++) {
						for ($column = 0; $column < 16; $column++) {
							$value = ($base + $slopeX * ($column - 7) + $slopeY * ($row - 7) + 16) >> 5;
							$prediction[$row][$column] = $value < 0 ? 0 : ($value > 255 ? 255 : $value);
						}
					}
				}
				break;
		}

		for ($row = 0; $row < 16; $row++) {
			for ($column = 0; $column < 16; $column++) {
				$this->luma[$y + $row][$x + $column] = $prediction[$row][$column];
			}
		}
	}

	private function predictLuma8x8(int $x, int $y, int $mode): void
	{
		$leftAvailable = $this->lumaAvailable($x - 1, $y, 8);
		$topAvailable = $this->lumaAvailable($x, $y - 1, 8);
		$topLeftAvailable = $this->lumaAvailable($x - 1, $y - 1, 8);
		$topRightAvailable = $this->lumaAvailable($x + 8, $y - 1, 8);

		$rawTop = [];
		for ($index = 0; $index < 8; $index++) {
			$rawTop[$index] = $topAvailable ? $this->luma[$y - 1][$x + $index] : 0;
		}
		for ($index = 8; $index < 16; $index++) {
			$rawTop[$index] = $topRightAvailable
				? $this->luma[$y - 1][$x + $index]
				: ($topAvailable ? $rawTop[7] : 0);
		}

		$rawLeft = [];
		for ($index = 0; $index < 8; $index++) {
			$rawLeft[$index] = $leftAvailable ? $this->luma[$y + $index][$x - 1] : 0;
		}

		$rawTopLeft = $topLeftAvailable ? $this->luma[$y - 1][$x - 1] : 0;

		/* An 8x8 block predicts from low-pass filtered reference samples. */
		$top = array_fill(0, 16, 0);
		$left = array_fill(0, 8, 0);
		$topLeft = $rawTopLeft;

		if ($topAvailable) {
			$top[0] = $topLeftAvailable
				? ($rawTopLeft + 2 * $rawTop[0] + $rawTop[1] + 2) >> 2
				: (3 * $rawTop[0] + $rawTop[1] + 2) >> 2;
			for ($index = 1; $index < 15; $index++) {
				$top[$index] = ($rawTop[$index - 1] + 2 * $rawTop[$index] + $rawTop[$index + 1] + 2) >> 2;
			}
			$top[15] = ($rawTop[14] + 3 * $rawTop[15] + 2) >> 2;
		}

		if ($topLeftAvailable) {
			if ($topAvailable && $leftAvailable) {
				$topLeft = ($rawTop[0] + 2 * $rawTopLeft + $rawLeft[0] + 2) >> 2;
			} elseif ($topAvailable) {
				$topLeft = (3 * $rawTopLeft + $rawTop[0] + 2) >> 2;
			} elseif ($leftAvailable) {
				$topLeft = (3 * $rawTopLeft + $rawLeft[0] + 2) >> 2;
			}
		}

		if ($leftAvailable) {
			$left[0] = $topLeftAvailable
				? ($rawTopLeft + 2 * $rawLeft[0] + $rawLeft[1] + 2) >> 2
				: (3 * $rawLeft[0] + $rawLeft[1] + 2) >> 2;
			for ($index = 1; $index < 7; $index++) {
				$left[$index] = ($rawLeft[$index - 1] + 2 * $rawLeft[$index] + $rawLeft[$index + 1] + 2) >> 2;
			}
			$left[7] = ($rawLeft[6] + 3 * $rawLeft[7] + 2) >> 2;
		}

		$prediction = $this->predict8x8($mode, $left, $top, $topLeft, $leftAvailable, $topAvailable);

		for ($row = 0; $row < 8; $row++) {
			for ($column = 0; $column < 8; $column++) {
				$this->luma[$y + $row][$x + $column] = $prediction[$row][$column];
			}
		}
	}

	private function predict8x8(int $mode, array $left, array $top, int $topLeft, bool $leftAvailable, bool $topAvailable): array
	{
		$prediction = array_fill(0, 8, array_fill(0, 8, 128));
		$average2 = static fn (int $a, int $b): int => ($a + $b + 1) >> 1;
		$average3 = static fn (int $a, int $b, int $c): int => ($a + 2 * $b + $c + 2) >> 2;

		switch ($mode) {
			case 0:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						$prediction[$row][$column] = $top[$column];
					}
				}
				break;

			case 1:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						$prediction[$row][$column] = $left[$row];
					}
				}
				break;

			case 2:
				if ($leftAvailable && $topAvailable) {
					$value = (array_sum(array_slice($top, 0, 8)) + array_sum($left) + 8) >> 4;
				} elseif ($leftAvailable) {
					$value = (array_sum($left) + 4) >> 3;
				} elseif ($topAvailable) {
					$value = (array_sum(array_slice($top, 0, 8)) + 4) >> 3;
				} else {
					$value = 128;
				}
				$prediction = array_fill(0, 8, array_fill(0, 8, $value));
				break;

			case 3:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						$index = $column + $row;
						$prediction[$row][$column] = $index === 14
							? $average3($top[14], $top[15], $top[15])
							: $average3($top[$index], $top[$index + 1], $top[$index + 2]);
					}
				}
				break;

			case 4:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						if ($column > $row) {
							$index = $column - $row;
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $top[$index - 2] : $topLeft,
								$index >= 1 ? $top[$index - 1] : $topLeft,
								$top[$index]
							);
						} elseif ($column < $row) {
							$index = $row - $column;
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $left[$index - 2] : $topLeft,
								$index >= 1 ? $left[$index - 1] : $topLeft,
								$left[$index]
							);
						} else {
							$prediction[$row][$column] = $average3($top[0], $topLeft, $left[0]);
						}
					}
				}
				break;

			case 5:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						$zone = 2 * $column - $row;
						$index = $column - ($row >> 1);

						if ($zone >= 0 && ($zone % 2) === 0) {
							$prediction[$row][$column] = $average2($index >= 1 ? $top[$index - 1] : $topLeft, $top[$index]);
						} elseif ($zone >= 0) {
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $top[$index - 2] : $topLeft,
								$index >= 1 ? $top[$index - 1] : $topLeft,
								$top[$index]
							);
						} elseif ($zone === -1) {
							$prediction[$row][$column] = $average3($left[0], $topLeft, $top[0]);
						} else {
							$reverse = $row - 2 * $column - 1;
							$prediction[$row][$column] = $average3(
								$left[$reverse],
								$reverse >= 1 ? $left[$reverse - 1] : $topLeft,
								$reverse >= 2 ? $left[$reverse - 2] : $topLeft
							);
						}
					}
				}
				break;

			case 6:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						$zone = 2 * $row - $column;
						$index = $row - ($column >> 1);

						if ($zone >= 0 && ($zone % 2) === 0) {
							$prediction[$row][$column] = $average2($index >= 1 ? $left[$index - 1] : $topLeft, $left[$index]);
						} elseif ($zone >= 0) {
							$prediction[$row][$column] = $average3(
								$index >= 2 ? $left[$index - 2] : $topLeft,
								$index >= 1 ? $left[$index - 1] : $topLeft,
								$left[$index]
							);
						} elseif ($zone === -1) {
							$prediction[$row][$column] = $average3($left[0], $topLeft, $top[0]);
						} else {
							$reverse = $column - 2 * $row - 1;
							$prediction[$row][$column] = $average3(
								$top[$reverse],
								$reverse >= 1 ? $top[$reverse - 1] : $topLeft,
								$reverse >= 2 ? $top[$reverse - 2] : $topLeft
							);
						}
					}
				}
				break;

			case 7:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						$index = $column + ($row >> 1);
						$prediction[$row][$column] = ($row % 2) === 0
							? $average2($top[$index], $top[$index + 1])
							: $average3($top[$index], $top[$index + 1], $top[$index + 2]);
					}
				}
				break;

			case 8:
				for ($row = 0; $row < 8; $row++) {
					for ($column = 0; $column < 8; $column++) {
						$zone = $column + 2 * $row;
						$index = $row + ($column >> 1);

						if ($zone > 13) {
							$prediction[$row][$column] = $left[7];
						} elseif ($zone === 13) {
							$prediction[$row][$column] = $average3($left[6], $left[7], $left[7]);
						} elseif (($zone % 2) === 0) {
							$prediction[$row][$column] = $average2($left[$index], $left[$index + 1]);
						} else {
							$prediction[$row][$column] = $average3($left[$index], $left[$index + 1], $left[$index + 2]);
						}
					}
				}
				break;
		}

		return $prediction;
	}

	private function predictChroma(int $plane, int $x, int $y, int $mode): void
	{
		$samples = &$this->chroma[$plane];
		$leftAvailable = $this->chromaAvailable($x - 1, $y);
		$topAvailable = $this->chromaAvailable($x, $y - 1);
		$topLeftAvailable = $this->chromaAvailable($x - 1, $y - 1);
		$prediction = array_fill(0, 8, array_fill(0, 8, 128));

		switch ($mode) {
			case 0:
				for ($block = 0; $block < 4; $block++) {
					$blockX = ($block % 2) * 4;
					$blockY = intdiv($block, 2) * 4;
					$topSum = 0;
					$leftSum = 0;

					if ($topAvailable) {
						for ($index = 0; $index < 4; $index++) {
							$topSum += $samples[$y - 1][$x + $blockX + $index];
						}
					}
					if ($leftAvailable) {
						for ($index = 0; $index < 4; $index++) {
							$leftSum += $samples[$y + $blockY + $index][$x - 1];
						}
					}

					$onDiagonal = $block === 0 || $block === 3;
					$prefersTop = $block === 1;

					if ($onDiagonal && $topAvailable && $leftAvailable) {
						$value = ($topSum + $leftSum + 4) >> 3;
					} elseif ($prefersTop && $topAvailable) {
						$value = ($topSum + 2) >> 2;
					} elseif (!$onDiagonal && !$prefersTop && $leftAvailable) {
						$value = ($leftSum + 2) >> 2;
					} elseif ($topAvailable) {
						$value = ($topSum + 2) >> 2;
					} elseif ($leftAvailable) {
						$value = ($leftSum + 2) >> 2;
					} else {
						$value = 128;
					}

					for ($row = 0; $row < 4; $row++) {
						for ($column = 0; $column < 4; $column++) {
							$prediction[$blockY + $row][$blockX + $column] = $value;
						}
					}
				}
				break;

			case 1:
				if ($leftAvailable) {
					for ($row = 0; $row < 8; $row++) {
						$value = $samples[$y + $row][$x - 1];
						for ($column = 0; $column < 8; $column++) {
							$prediction[$row][$column] = $value;
						}
					}
				}
				break;

			case 2:
				if ($topAvailable) {
					for ($row = 0; $row < 8; $row++) {
						for ($column = 0; $column < 8; $column++) {
							$prediction[$row][$column] = $samples[$y - 1][$x + $column];
						}
					}
				}
				break;

			case 3:
				if ($leftAvailable && $topAvailable && $topLeftAvailable) {
					$horizontal = 0;
					$vertical = 0;

					for ($index = 0; $index < 4; $index++) {
						$right = $samples[$y - 1][$x + 4 + $index];
						$mirrored = $index === 3 ? $samples[$y - 1][$x - 1] : $samples[$y - 1][$x + 2 - $index];
						$horizontal += ($index + 1) * ($right - $mirrored);

						$below = $samples[$y + 4 + $index][$x - 1];
						$above = $index === 3 ? $samples[$y - 1][$x - 1] : $samples[$y + 2 - $index][$x - 1];
						$vertical += ($index + 1) * ($below - $above);
					}

					$base = 16 * ($samples[$y + 7][$x - 1] + $samples[$y - 1][$x + 7]);
					$slopeX = (34 * $horizontal + 32) >> 6;
					$slopeY = (34 * $vertical + 32) >> 6;

					for ($row = 0; $row < 8; $row++) {
						for ($column = 0; $column < 8; $column++) {
							$value = ($base + $slopeX * ($column - 3) + $slopeY * ($row - 3) + 16) >> 5;
							$prediction[$row][$column] = $value < 0 ? 0 : ($value > 255 ? 255 : $value);
						}
					}
				}
				break;
		}

		for ($row = 0; $row < 8; $row++) {
			for ($column = 0; $column < 8; $column++) {
				$samples[$y + $row][$x + $column] = $prediction[$row][$column];
			}
		}
	}

	/**
	 * Filters every block edge of the picture: macroblock edges at strength 4 and the edges inside a
	 * macroblock at strength 3, which is what an intra picture always calls for. An 8x8 transform
	 * leaves its interior 4x4 edges alone.
	 */
	public function deblock(): void
	{
		if ($this->deblockingMode === self::DEBLOCKING_DISABLED) {
			return;
		}

		$total = $this->mbWidth * $this->mbHeight;

		for ($mbAddress = 0; $mbAddress < $total; $mbAddress++) {
			$macroblock = $this->parser->macroblocks[$mbAddress];
			$mbX = ($mbAddress % $this->mbWidth) * 16;
			$mbY = intdiv($mbAddress, $this->mbWidth) * 16;
			$quantiser = $macroblock['quantiser'];
			$edges = $macroblock['transform_8x8'] ? [0, 8] : [0, 4, 8, 12];

			$left = ($mbAddress % $this->mbWidth) > 0 ? $mbAddress - 1 : null;
			$above = $mbAddress >= $this->mbWidth ? $mbAddress - $this->mbWidth : null;

			if ($this->deblockingMode === self::DEBLOCKING_WITHIN_SLICE_ONLY) {
				if ($left !== null && $this->parser->macroblocks[$left]['slice'] !== $macroblock['slice']) {
					$left = null;
				}
				if ($above !== null && $this->parser->macroblocks[$above]['slice'] !== $macroblock['slice']) {
					$above = null;
				}
			}

			foreach ($edges as $edge) {
				if ($edge === 0) {
					if ($left !== null) {
						$this->filterLumaEdge($mbX, $mbY, true, self::STRENGTH_MACROBLOCK_EDGE, $quantiser, $this->parser->macroblocks[$left]['quantiser']);
					}
					continue;
				}
				$this->filterLumaEdge($mbX + $edge, $mbY, true, self::STRENGTH_INTERNAL_EDGE, $quantiser, $quantiser);
			}

			foreach ($edges as $edge) {
				if ($edge === 0) {
					if ($above !== null) {
						$this->filterLumaEdge($mbX, $mbY, false, self::STRENGTH_MACROBLOCK_EDGE, $quantiser, $this->parser->macroblocks[$above]['quantiser']);
					}
					continue;
				}
				$this->filterLumaEdge($mbX, $mbY + $edge, false, self::STRENGTH_INTERNAL_EDGE, $quantiser, $quantiser);
			}

			$chromaX = ($mbAddress % $this->mbWidth) * 8;
			$chromaY = intdiv($mbAddress, $this->mbWidth) * 8;

			for ($plane = 0; $plane < 2; $plane++) {
				$current = $this->chromaQuantiser($quantiser, $plane);

				if ($left !== null) {
					$this->filterChromaEdge($plane, $chromaX, $chromaY, true, self::STRENGTH_MACROBLOCK_EDGE, $current, $this->chromaQuantiser($this->parser->macroblocks[$left]['quantiser'], $plane));
				}
				$this->filterChromaEdge($plane, $chromaX + 4, $chromaY, true, self::STRENGTH_INTERNAL_EDGE, $current, $current);

				if ($above !== null) {
					$this->filterChromaEdge($plane, $chromaX, $chromaY, false, self::STRENGTH_MACROBLOCK_EDGE, $current, $this->chromaQuantiser($this->parser->macroblocks[$above]['quantiser'], $plane));
				}
				$this->filterChromaEdge($plane, $chromaX, $chromaY + 4, false, self::STRENGTH_INTERNAL_EDGE, $current, $current);
			}
		}
	}

	private function filterLumaEdge(int $x, int $y, bool $vertical, int $strength, int $quantiserQ, int $quantiserP): void
	{
		[$alpha, $beta, $indexA] = $this->filterThresholds($quantiserP, $quantiserQ);
		if ($alpha === 0 || $beta === 0) {
			return;
		}

		for ($offset = 0; $offset < 16; $offset++) {
			$samples = [];
			for ($index = 0; $index < 8; $index++) {
				$sampleX = $vertical ? $x - 4 + $index : $x + $offset;
				$sampleY = $vertical ? $y + $offset : $y - 4 + $index;
				$samples[$index] = $this->luma[$sampleY][$sampleX];
			}

			$filtered = $this->filterLine($samples, $alpha, $beta, $strength, $indexA, true);
			if ($filtered === null) {
				continue;
			}

			foreach ($filtered as $index => $value) {
				$sampleX = $vertical ? $x - 4 + $index : $x + $offset;
				$sampleY = $vertical ? $y + $offset : $y - 4 + $index;
				$this->luma[$sampleY][$sampleX] = $value;
			}
		}
	}

	private function filterChromaEdge(int $plane, int $x, int $y, bool $vertical, int $strength, int $quantiserQ, int $quantiserP): void
	{
		[$alpha, $beta, $indexA] = $this->filterThresholds($quantiserP, $quantiserQ);
		if ($alpha === 0 || $beta === 0) {
			return;
		}

		for ($offset = 0; $offset < 8; $offset++) {
			$samples = [];
			for ($index = 0; $index < 8; $index++) {
				$sampleX = $vertical ? $x - 4 + $index : $x + $offset;
				$sampleY = $vertical ? $y + $offset : $y - 4 + $index;
				$samples[$index] = $this->chroma[$plane][$sampleY][$sampleX];
			}

			$filtered = $this->filterLine($samples, $alpha, $beta, $strength, $indexA, false);
			if ($filtered === null) {
				continue;
			}

			foreach ($filtered as $index => $value) {
				$sampleX = $vertical ? $x - 4 + $index : $x + $offset;
				$sampleY = $vertical ? $y + $offset : $y - 4 + $index;
				$this->chroma[$plane][$sampleY][$sampleX] = $value;
			}
		}
	}

	/** @return array{0:int,1:int,2:int} */
	private function filterThresholds(int $quantiserP, int $quantiserQ): array
	{
		$average = ($quantiserP + $quantiserQ + 1) >> 1;
		$indexA = max(0, min(self::QUANTISER_LIMIT, $average + $this->alphaOffset));
		$indexB = max(0, min(self::QUANTISER_LIMIT, $average + $this->betaOffset));

		return [self::ALPHA_TABLE[$indexA], self::BETA_TABLE[$indexB], $indexA];
	}

	/** Samples run p3 p2 p1 p0 q0 q1 q2 q3; returns replacements, or null when the edge is left alone. */
	private function filterLine(array $samples, int $alpha, int $beta, int $strength, int $indexA, bool $isLuma): ?array
	{
		[$p3, $p2, $p1, $p0, $q0, $q1, $q2, $q3] = $samples;

		if (abs($p0 - $q0) >= $alpha || abs($p1 - $p0) >= $beta || abs($q1 - $q0) >= $beta) {
			return null;
		}

		$clip = static fn (int $value): int => $value < 0 ? 0 : ($value > 255 ? 255 : $value);
		$result = $samples;

		if ($strength === self::STRENGTH_MACROBLOCK_EDGE) {
			$shortP = (2 * $p1 + $p0 + $q1 + 2) >> 2;
			$shortQ = (2 * $q1 + $q0 + $p1 + 2) >> 2;

			if (!$isLuma) {
				$result[3] = $clip($shortP);
				$result[4] = $clip($shortQ);

				return $result;
			}

			$narrow = abs($p0 - $q0) < (($alpha >> 2) + 2);

			if (abs($p2 - $p0) < $beta && $narrow) {
				$result[3] = $clip(($p2 + 2 * $p1 + 2 * $p0 + 2 * $q0 + $q1 + 4) >> 3);
				$result[2] = $clip(($p2 + $p1 + $p0 + $q0 + 2) >> 2);
				$result[1] = $clip((2 * $p3 + 3 * $p2 + $p1 + $p0 + $q0 + 4) >> 3);
			} else {
				$result[3] = $clip($shortP);
			}

			if (abs($q2 - $q0) < $beta && $narrow) {
				$result[4] = $clip(($q2 + 2 * $q1 + 2 * $q0 + 2 * $p0 + $p1 + 4) >> 3);
				$result[5] = $clip(($q2 + $q1 + $q0 + $p0 + 2) >> 2);
				$result[6] = $clip((2 * $q3 + 3 * $q2 + $q1 + $q0 + $p0 + 4) >> 3);
			} else {
				$result[4] = $clip($shortQ);
			}

			return $result;
		}

		$baseClip = self::CLIPPING_TABLE[$indexA][$strength - 1];
		$filterP1 = $isLuma && abs($p2 - $p0) < $beta;
		$filterQ1 = $isLuma && abs($q2 - $q0) < $beta;
		$limit = $isLuma ? $baseClip + ($filterP1 ? 1 : 0) + ($filterQ1 ? 1 : 0) : $baseClip + 1;

		$delta = ((($q0 - $p0) << 2) + ($p1 - $q1) + 4) >> 3;
		$delta = $delta < -$limit ? -$limit : ($delta > $limit ? $limit : $delta);

		$result[3] = $clip($p0 + $delta);
		$result[4] = $clip($q0 - $delta);

		if ($filterP1) {
			$adjust = ($p2 + (($p0 + $q0 + 1) >> 1) - ($p1 << 1)) >> 1;
			$result[2] = $p1 + ($adjust < -$baseClip ? -$baseClip : ($adjust > $baseClip ? $baseClip : $adjust));
		}
		if ($filterQ1) {
			$adjust = ($q2 + (($p0 + $q0 + 1) >> 1) - ($q1 << 1)) >> 1;
			$result[5] = $q1 + ($adjust < -$baseClip ? -$baseClip : ($adjust > $baseClip ? $baseClip : $adjust));
		}

		return $result;
	}
}
