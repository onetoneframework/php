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
use GdImage;

use function in_array;
use function ord;
use function strlen;
use function substr;

/**
 * Class IntraFrameDecoder
 *
 * Decodes one H.264 keyframe from its decoder configuration record and sample bytes.
 *
 * Supports CABAC coded intra slices of 4:2:0 8-bit streams, which covers Baseline, Main and High
 * profile keyframes. Anything outside that is refused by name rather than approximated.
 */
final class IntraFrameDecoder
{
	private const HIGH_PROFILES = [100, 110, 122, 244, 44, 83, 86, 118, 128, 138, 139, 134, 135];
	private const NAL_TYPE_IDR_SLICE = 5;
	private const NAL_TYPE_NON_IDR_SLICE = 1;
	private const SLICE_TYPE_I = 2;
	private const SLICE_TYPE_I_ONLY = 7;
	private const CHROMA_FORMAT_420 = 1;
	private const ASPECT_RATIO_EXTENDED = 255;

	/** Luma coefficients (Kr, Kb) of each colour matrix this decoder converts. */
	private const MATRIX_COEFFICIENTS = [
		1 => [0.2126, 0.0722],
		4 => [0.30, 0.11],
		5 => [0.299, 0.114],
		6 => [0.299, 0.114],
		7 => [0.212, 0.087],
		9 => [0.2627, 0.0593],
		10 => [0.2627, 0.0593],
	];

	/** A stream that signals no matrix is read the way players read it: by picture height. */
	private const MATRIX_UNSPECIFIED = 2;
	private const HIGH_DEFINITION_HEIGHT = 720;
	private const MATRIX_STANDARD_DEFINITION = 6;
	private const MATRIX_HIGH_DEFINITION = 1;

	private const FIXED_POINT_BITS = 8;

	/** @var array<string, mixed> */
	private array $sequence;
	/** @var array<string, mixed> */
	private array $picture;

	private int $nalLengthSize;
	private ?int $containerMatrix;

	/**
	 * @param  string    $decoderConfiguration The avcC box payload.
	 * @param  int|null  $matrixCoefficients   The container's colour matrix, which outranks the stream's own.
	 * @throws Exception If the stream uses a feature this decoder does not implement.
	 */
	public function __construct(string $decoderConfiguration, ?int $matrixCoefficients = null)
	{
		$this->containerMatrix = $matrixCoefficients;
		if (strlen($decoderConfiguration) < 7) {
			throw new Exception('The AVC decoder configuration record is too short to read.');
		}

		$this->nalLengthSize = (ord($decoderConfiguration[4]) & 0x03) + 1;
		$offset = 5;

		$sequenceCount = ord($decoderConfiguration[$offset++]) & 0x1F;
		$sequenceSets = [];
		for ($index = 0; $index < $sequenceCount; $index++) {
			$length = (ord($decoderConfiguration[$offset]) << 8) | ord($decoderConfiguration[$offset + 1]);
			$offset += 2;
			$sequenceSets[] = substr($decoderConfiguration, $offset, $length);
			$offset += $length;
		}

		$pictureCount = $offset < strlen($decoderConfiguration) ? ord($decoderConfiguration[$offset++]) : 0;
		$pictureSets = [];
		for ($index = 0; $index < $pictureCount; $index++) {
			$length = (ord($decoderConfiguration[$offset]) << 8) | ord($decoderConfiguration[$offset + 1]);
			$offset += 2;
			$pictureSets[] = substr($decoderConfiguration, $offset, $length);
			$offset += $length;
		}

		if ($sequenceSets === [] || $pictureSets === []) {
			throw new Exception('The AVC decoder configuration record carries no parameter sets.');
		}

		$this->sequence = $this->parseSequenceParameterSet(BitReader::unescape(substr($sequenceSets[0], 1)));
		$this->picture = $this->parsePictureParameterSet(BitReader::unescape(substr($pictureSets[0], 1)));

		if ($this->picture['entropy_coding_mode'] !== 1) {
			throw new Exception('This stream is CAVLC coded; only CABAC coded frames can be decoded.');
		}
	}

	public function width(): int
	{
		return $this->sequence['width'];
	}

	public function height(): int
	{
		return $this->sequence['height'];
	}

	/**
	 * Decodes one keyframe sample into a GD image.
	 *
	 * @throws Exception If the sample holds no intra slice or uses an unsupported feature.
	 */
	public function decode(string $sample): GdImage
	{
		$slices = $this->collectSliceNalUnits($sample);
		if ($slices === []) {
			throw new Exception('The sample carries no coded slice; it is not a keyframe.');
		}

		$parser = new SliceParser(
			$this->sequence['mb_width'],
			$this->sequence['mb_height'],
			$this->picture['transform_8x8'] === 1
		);
		$reconstructor = new FrameReconstructor(
			$parser,
			$this->sequence['mb_width'],
			$this->sequence['mb_height'],
			$this->picture['chroma_quantiser_offset'],
			$this->picture['second_chroma_quantiser_offset']
		);

		$deblocking = null;

		foreach ($slices as $sliceIndex => $nal) {
			$header = $this->parseSliceHeader($nal);
			$cabac = new CabacDecoder(
				$header['rbsp'],
				$header['byte_offset'],
				$header['quantiser'],
				ContextInitialisation::forIntraSlices()
			);
			$parser->parseSlice($cabac, $header['first_macroblock'], $header['quantiser'], $sliceIndex);

			if ($deblocking === null) {
				$deblocking = $header;
			}
		}

		$total = $this->sequence['mb_width'] * $this->sequence['mb_height'];
		for ($mbAddress = 0; $mbAddress < $total; $mbAddress++) {
			if (!isset($parser->macroblocks[$mbAddress])) {
				throw new Exception('The sample left macroblock ' . $mbAddress . ' of the picture uncoded.');
			}
		}

		$reconstructor->setDeblockingParameters(
			$deblocking['deblocking_mode'],
			$deblocking['alpha_offset'],
			$deblocking['beta_offset']
		);
		$reconstructor->reconstruct();
		$reconstructor->deblock();

		return $this->toImage($reconstructor);
	}

	/** @return array<int, string> */
	private function collectSliceNalUnits(string $sample): array
	{
		$slices = [];
		$offset = 0;
		$length = strlen($sample);

		while ($offset + $this->nalLengthSize <= $length) {
			$size = 0;
			for ($index = 0; $index < $this->nalLengthSize; $index++) {
				$size = ($size << 8) | ord($sample[$offset + $index]);
			}
			$offset += $this->nalLengthSize;

			if ($size <= 0 || $offset + $size > $length) {
				break;
			}

			$type = ord($sample[$offset]) & 0x1F;
			if ($type === self::NAL_TYPE_IDR_SLICE || $type === self::NAL_TYPE_NON_IDR_SLICE) {
				$slices[] = substr($sample, $offset, $size);
			}
			$offset += $size;
		}

		return $slices;
	}

	/** @return array<string, mixed> */
	private function parseSequenceParameterSet(string $rbsp): array
	{
		$reader = new BitReader($rbsp);
		$profile = $reader->read(8);
		$reader->read(8);
		$level = $reader->read(8);
		$reader->readUnsignedExpGolomb();

		$chromaFormat = self::CHROMA_FORMAT_420;
		if (in_array($profile, self::HIGH_PROFILES, true)) {
			$chromaFormat = $reader->readUnsignedExpGolomb();
			if ($chromaFormat === 3) {
				$reader->read(1);
			}
			$lumaDepth = $reader->readUnsignedExpGolomb() + 8;
			$chromaDepth = $reader->readUnsignedExpGolomb() + 8;
			$reader->read(1);

			if ($lumaDepth !== 8 || $chromaDepth !== 8) {
				throw new Exception('Only 8-bit streams can be decoded; this one is ' . $lumaDepth . '-bit.');
			}
			if ($reader->read(1) === 1) {
				throw new Exception('Sequence scaling matrices are not supported.');
			}
		}

		if ($chromaFormat !== self::CHROMA_FORMAT_420) {
			throw new Exception('Only 4:2:0 chroma sampling is supported; this stream uses format ' . $chromaFormat . '.');
		}

		$log2MaxFrameNumber = $reader->readUnsignedExpGolomb() + 4;
		$pictureOrderType = $reader->readUnsignedExpGolomb();
		$log2MaxPictureOrder = 0;

		if ($pictureOrderType === 0) {
			$log2MaxPictureOrder = $reader->readUnsignedExpGolomb() + 4;
		} elseif ($pictureOrderType === 1) {
			$reader->read(1);
			$reader->readSignedExpGolomb();
			$reader->readSignedExpGolomb();
			$count = $reader->readUnsignedExpGolomb();
			for ($index = 0; $index < $count; $index++) {
				$reader->readSignedExpGolomb();
			}
		}

		$reader->readUnsignedExpGolomb();
		$reader->read(1);
		$mbWidth = $reader->readUnsignedExpGolomb() + 1;
		$mbHeight = $reader->readUnsignedExpGolomb() + 1;
		$frameMacroblocksOnly = $reader->read(1);

		if ($frameMacroblocksOnly === 0) {
			throw new Exception('Field and macroblock-adaptive frame/field coding are not supported.');
		}

		$reader->read(1);
		$cropLeft = $cropRight = $cropTop = $cropBottom = 0;

		if ($reader->read(1) === 1) {
			$cropLeft = $reader->readUnsignedExpGolomb();
			$cropRight = $reader->readUnsignedExpGolomb();
			$cropTop = $reader->readUnsignedExpGolomb();
			$cropBottom = $reader->readUnsignedExpGolomb();
		}

		$matrix = null;
		$fullRange = false;

		if ($reader->read(1) === 1) {
			if ($reader->read(1) === 1) {
				if ($reader->read(8) === self::ASPECT_RATIO_EXTENDED) {
					$reader->read(16);
					$reader->read(16);
				}
			}
			if ($reader->read(1) === 1) {
				$reader->read(1);
			}
			if ($reader->read(1) === 1) {
				$reader->read(3);
				$fullRange = $reader->read(1) === 1;

				if ($reader->read(1) === 1) {
					$reader->read(8);
					$reader->read(8);
					$matrix = $reader->read(8);
				}
			}
		}

		return [
			'profile' => $profile,
			'level' => $level,
			'mb_width' => $mbWidth,
			'mb_height' => $mbHeight,
			'log2_max_frame_number' => $log2MaxFrameNumber,
			'picture_order_type' => $pictureOrderType,
			'log2_max_picture_order' => $log2MaxPictureOrder,
			'crop_x' => 2 * $cropLeft,
			'crop_y' => 2 * $cropTop,
			'width' => $mbWidth * 16 - 2 * ($cropLeft + $cropRight),
			'height' => $mbHeight * 16 - 2 * ($cropTop + $cropBottom),
			'matrix_coefficients' => $matrix,
			'full_range' => $fullRange,
		];
	}

	/** @return array<string, mixed> */
	private function parsePictureParameterSet(string $rbsp): array
	{
		$reader = new BitReader($rbsp);
		$reader->readUnsignedExpGolomb();
		$reader->readUnsignedExpGolomb();
		$entropyCodingMode = $reader->read(1);
		$bottomFieldPictureOrder = $reader->read(1);
		$sliceGroups = $reader->readUnsignedExpGolomb() + 1;

		if ($sliceGroups > 1) {
			throw new Exception('Streams with more than one slice group are not supported.');
		}

		$reader->readUnsignedExpGolomb();
		$reader->readUnsignedExpGolomb();
		$reader->read(1);
		$reader->read(2);
		$initialQuantiser = $reader->readSignedExpGolomb() + 26;
		$reader->readSignedExpGolomb();
		$chromaQuantiserOffset = $reader->readSignedExpGolomb();
		$deblockingPresent = $reader->read(1);
		$reader->read(1);
		$reader->read(1);

		$transform8x8 = 0;
		$secondChromaQuantiserOffset = $chromaQuantiserOffset;

		if ($reader->hasMoreData()) {
			$transform8x8 = $reader->read(1);
			if ($reader->read(1) === 1) {
				throw new Exception('Picture scaling matrices are not supported.');
			}
			$secondChromaQuantiserOffset = $reader->readSignedExpGolomb();
		}

		return [
			'entropy_coding_mode' => $entropyCodingMode,
			'bottom_field_picture_order' => $bottomFieldPictureOrder,
			'initial_quantiser' => $initialQuantiser,
			'chroma_quantiser_offset' => $chromaQuantiserOffset,
			'second_chroma_quantiser_offset' => $secondChromaQuantiserOffset,
			'deblocking_present' => $deblockingPresent,
			'transform_8x8' => $transform8x8,
		];
	}

	/** @return array<string, mixed> */
	private function parseSliceHeader(string $nal): array
	{
		$referenceIndicator = (ord($nal[0]) >> 5) & 3;
		$isIdr = (ord($nal[0]) & 0x1F) === self::NAL_TYPE_IDR_SLICE;
		$rbsp = BitReader::unescape(substr($nal, 1));
		$reader = new BitReader($rbsp);

		$firstMacroblock = $reader->readUnsignedExpGolomb();
		$sliceType = $reader->readUnsignedExpGolomb();

		if ($sliceType !== self::SLICE_TYPE_I && $sliceType !== self::SLICE_TYPE_I_ONLY) {
			throw new Exception('Only intra slices can be decoded; this slice has type ' . $sliceType . '.');
		}

		$reader->readUnsignedExpGolomb();
		$reader->read($this->sequence['log2_max_frame_number']);

		if ($isIdr) {
			$reader->readUnsignedExpGolomb();
		}
		if ($this->sequence['picture_order_type'] === 0) {
			$reader->read($this->sequence['log2_max_picture_order']);
			if ($this->picture['bottom_field_picture_order'] === 1) {
				$reader->readSignedExpGolomb();
			}
		}
		if ($referenceIndicator !== 0) {
			if ($isIdr) {
				$reader->read(1);
				$reader->read(1);
			} elseif ($reader->read(1) === 1) {
				do {
					$operation = $reader->readUnsignedExpGolomb();

					if ($operation === 1 || $operation === 3) {
						$reader->readUnsignedExpGolomb();
					}
					if ($operation === 2) {
						$reader->readUnsignedExpGolomb();
					}
					if ($operation === 3 || $operation === 6) {
						$reader->readUnsignedExpGolomb();
					}
					if ($operation === 4) {
						$reader->readUnsignedExpGolomb();
					}
				} while ($operation !== 0 && $reader->hasMoreData());
			}
		}

		$quantiser = $this->picture['initial_quantiser'] + $reader->readSignedExpGolomb();
		$deblockingMode = 0;
		$alphaOffset = 0;
		$betaOffset = 0;

		if ($this->picture['deblocking_present'] === 1) {
			$deblockingMode = $reader->readUnsignedExpGolomb();
			if ($deblockingMode !== 1) {
				$alphaOffset = $reader->readSignedExpGolomb() * 2;
				$betaOffset = $reader->readSignedExpGolomb() * 2;
			}
		}

		$reader->alignToByte();

		return [
			'rbsp' => $rbsp,
			'byte_offset' => $reader->position() >> 3,
			'first_macroblock' => $firstMacroblock,
			'quantiser' => $quantiser,
			'deblocking_mode' => $deblockingMode,
			'alpha_offset' => $alphaOffset,
			'beta_offset' => $betaOffset,
		];
	}

	/**
	 * Fixed-point YCbCr to RGB coefficients for the matrix this picture was coded against.
	 *
	 * @return array{0:int,1:int,2:int,3:int,4:int,5:int}
	 */
	private function conversionCoefficients(): array
	{
		$matrix = $this->containerMatrix ?? $this->sequence['matrix_coefficients'];

		if ($matrix === null || $matrix === self::MATRIX_UNSPECIFIED) {
			$matrix = $this->sequence['height'] < self::HIGH_DEFINITION_HEIGHT
				? self::MATRIX_STANDARD_DEFINITION
				: self::MATRIX_HIGH_DEFINITION;
		}

		if (!isset(self::MATRIX_COEFFICIENTS[$matrix])) {
			throw new Exception('Colour matrix ' . $matrix . ' is not one this decoder converts.');
		}

		[$red, $blue] = self::MATRIX_COEFFICIENTS[$matrix];
		$green = 1.0 - $red - $blue;
		$fullRange = $this->sequence['full_range'];

		$lumaScale = $fullRange ? 1.0 : 255.0 / 219.0;
		$chromaScale = $fullRange ? 1.0 : 255.0 / 224.0;
		$unit = 1 << self::FIXED_POINT_BITS;

		return [
			(int) round($lumaScale * $unit),
			(int) round(2.0 * (1.0 - $red) * $chromaScale * $unit),
			(int) round(2.0 * (1.0 - $blue) * $chromaScale * $unit),
			(int) round(2.0 * $red * (1.0 - $red) / $green * $chromaScale * $unit),
			(int) round(2.0 * $blue * (1.0 - $blue) / $green * $chromaScale * $unit),
			$fullRange ? 0 : 16,
		];
	}

	private function toImage(FrameReconstructor $reconstructor): GdImage
	{
		$width = $this->sequence['width'];
		$height = $this->sequence['height'];
		$originX = $this->sequence['crop_x'];
		$originY = $this->sequence['crop_y'];

		[$lumaGain, $redGain, $blueGain, $redToGreen, $blueToGreen, $lumaFloor] = $this->conversionCoefficients();
		$rounding = 1 << (self::FIXED_POINT_BITS - 1);

		$image = imagecreatetruecolor($width, $height);
		if ($image === false) {
			throw new Exception('Failed to allocate the frame image.');
		}

		for ($row = 0; $row < $height; $row++) {
			$sourceRow = $originY + $row;
			for ($column = 0; $column < $width; $column++) {
				$sourceColumn = $originX + $column;
				$luma = ($reconstructor->luma[$sourceRow][$sourceColumn] - $lumaFloor) * $lumaGain;
				$blueDifference = $reconstructor->chroma[0][$sourceRow >> 1][$sourceColumn >> 1] - 128;
				$redDifference = $reconstructor->chroma[1][$sourceRow >> 1][$sourceColumn >> 1] - 128;

				$red = ($luma + $redGain * $redDifference + $rounding) >> self::FIXED_POINT_BITS;
				$green = ($luma - $redToGreen * $redDifference - $blueToGreen * $blueDifference + $rounding) >> self::FIXED_POINT_BITS;
				$blue = ($luma + $blueGain * $blueDifference + $rounding) >> self::FIXED_POINT_BITS;

				$red = $red < 0 ? 0 : ($red > 255 ? 255 : $red);
				$green = $green < 0 ? 0 : ($green > 255 ? 255 : $green);
				$blue = $blue < 0 ? 0 : ($blue > 255 ? 255 : $blue);

				imagesetpixel($image, $column, $row, ($red << 16) | ($green << 8) | $blue);
			}
		}

		return $image;
	}
}
