<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Video;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Video\Mp4Parser;
use Clover\Classes\File\Handler as FileHandler;
use PHPUnit\Framework\TestCase;

class Mp4ParserTest extends TestCase
{
	private const OUTPUT_FILE_RANDOM_BYTES = 8;
	private const BOX_HEADER_BYTES = 8;
	private const MACROBLOCK_SIZE = 16;

	/**
	 * A decoded frame carries no step at its macroblock boundaries that it does not also carry
	 * inside them. A frame decoded wrongly is flat blocks with a jump at every edge, which this
	 * ratio separates: a correct decode of the sample measures 1.08, the earlier broken one 4.16.
	 */
	private const MAXIMUM_BOUNDARY_RATIO = 2.0;

	/** @var array<int, string> */
	private array $temporaryOutputPaths = [];

	protected function tearDown(): void
	{
		foreach ($this->temporaryOutputPaths as $outputPath) {
			if (FileHandler::isExists($outputPath)) {
				$this->assertTrue(FileHandler::delete($outputPath));
			}
		}
	}

	private function sampleFile(): string
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'sample.mp4';
	}

	private function createTemporaryOutputPath(string $extension): string
	{
		$outputPath = sys_get_temp_dir()
			. DIRECTORY_SEPARATOR
			. 'onetone-mp4-frame-'
			. bin2hex(random_bytes(self::OUTPUT_FILE_RANDOM_BYTES))
			. '.'
			. $extension;
		$this->temporaryOutputPaths[] = $outputPath;

		return $outputPath;
	}

	public function testMp4Parse(): void
	{
		$tags = Mp4Parser::getMetaTags($this->sampleFile());
		$this->assertEquals("0:12", $tags['meta']['duration_human']);
		$outputPath = $this->createTemporaryOutputPath('jpg');
		$this->assertTrue(Mp4Parser::saveFrameAsImage($this->sampleFile(), 30, $outputPath));
		$this->assertFileExists($outputPath);
		$this->assertGreaterThan(0, filesize($outputPath));
	}

	public function testGetFrameDataReturnsVideoFrameMetadata(): void
	{
		$frame = Mp4Parser::getFrameData($this->sampleFile(), 3.0);
		$this->assertNotNull($frame);
		$this->assertSame('avc1', $frame['codec']);
		$this->assertNotEmpty($frame['data']);
		$this->assertGreaterThan(0, $frame['sample_number']);
	}

	public function testSaveFrameAsImageDecodesTheKeyframe(): void
	{
		$outputPath = $this->createTemporaryOutputPath('jpg');
		$this->assertTrue(Mp4Parser::saveFrameAsImage($this->sampleFile(), 3.0, $outputPath));
		$this->assertFileExists($outputPath);

		$image = imagecreatefromjpeg($outputPath);
		$this->assertNotFalse($image);
		$this->assertImageIsADecodedFrame($image);

		if (\Clover\Classes\OperationSystem::comparePHPVersion('8.5.0', '<')) {
			imagedestroy($image);
		}
	}

	public function testSaveFrameAsImageCanWritePngOutput(): void
	{
		$outputPath = $this->createTemporaryOutputPath('png');
		$this->assertTrue(Mp4Parser::saveFrameAsImage($this->sampleFile(), 3.0, $outputPath));
		$this->assertFileExists($outputPath);
		$this->assertGreaterThan(0, filesize($outputPath));

		$image = imagecreatefrompng($outputPath);
		$this->assertNotFalse($image);
		$this->assertImageIsADecodedFrame($image);

		if (\Clover\Classes\OperationSystem::comparePHPVersion('8.5.0', '<')) {
			imagedestroy($image);
		}
	}

	public function testSaveFrameAsImageRendersTheFrameAtTheRequestedTime(): void
	{
		$earlyPath = $this->createTemporaryOutputPath('png');
		$latePath = $this->createTemporaryOutputPath('png');

		$this->assertTrue(Mp4Parser::saveFrameAsImage($this->sampleFile(), 3.0, $earlyPath));
		$this->assertTrue(Mp4Parser::saveFrameAsImage($this->sampleFile(), 10.0, $latePath));

		$this->assertNotSame(md5_file($earlyPath), md5_file($latePath));
	}

	public function testSaveFrameAsImageRefusesAFrameItCannotDecode(): void
	{
		$corruptedPath = $this->createTemporaryOutputPath('mp4');
		$outputPath = $this->createTemporaryOutputPath('png');
		file_put_contents(
			$corruptedPath,
			$this->replaceMediaDataWithNoise((string) file_get_contents($this->sampleFile()))
		);

		$this->expectException(\Throwable::class);

		try {
			Mp4Parser::saveFrameAsImage($corruptedPath, 3.0, $outputPath);
		} finally {
			$this->assertFileDoesNotExist($outputPath);
		}
	}

	/** Overwrites the mdat payload, leaving a file whose sample table still parses. */
	private function replaceMediaDataWithNoise(string $contents): string
	{
		$offset = 0;

		while ($offset + self::BOX_HEADER_BYTES <= strlen($contents)) {
			$size = (int) unpack('N', substr($contents, $offset, 4))[1];
			$type = substr($contents, $offset + 4, 4);

			if ($size === 1) {
				$size = (int) unpack('J', substr($contents, $offset + 8, 8))[1];
			}
			if ($size < self::BOX_HEADER_BYTES) {
				break;
			}
			if ($type === 'mdat') {
				$payloadLength = $size - self::BOX_HEADER_BYTES;

				return substr_replace(
					$contents,
					random_bytes($payloadLength),
					$offset + self::BOX_HEADER_BYTES,
					$payloadLength
				);
			}

			$offset += $size;
		}

		$this->fail('The sample file has no mdat box to corrupt.');
	}

	private function assertImageIsADecodedFrame(\GdImage $image): void
	{
		$resolution = Mp4Parser::getResolution($this->sampleFile());
		$this->assertNotNull($resolution);
		$this->assertSame($resolution['width'], imagesx($image));
		$this->assertSame($resolution['height'], imagesy($image));

		$boundaryStep = 0.0;
		$boundaryCount = 0;
		$interiorStep = 0.0;
		$interiorCount = 0;

		for ($y = 0; $y < imagesy($image); $y++) {
			$previous = null;

			for ($x = 0; $x < imagesx($image); $x++) {
				$rgb = imagecolorat($image, $x, $y);
				$luma = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);

				if ($previous !== null) {
					$step = abs($luma - $previous);

					if ($x % self::MACROBLOCK_SIZE === 0) {
						$boundaryStep += $step;
						$boundaryCount++;
					} else {
						$interiorStep += $step;
						$interiorCount++;
					}
				}
				$previous = $luma;
			}
		}

		$this->assertGreaterThan(0, $boundaryCount);
		$this->assertGreaterThan(0.0, $interiorStep, 'The frame is a flat fill, so nothing was decoded.');

		$ratio = ($boundaryStep / $boundaryCount) / ($interiorStep / $interiorCount);
		$this->assertLessThan(self::MAXIMUM_BOUNDARY_RATIO, $ratio);
	}
}
