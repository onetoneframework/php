<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use function sprintf;

class FFmpeg
{
	public function downloadRemoteVideo(string $url, string $filePath): bool|string
	{
		$cmd = sprintf("ffmpeg -i %s -c copy %s", $url, $filePath);
		return exec($cmd, $output, $returnVariable);
	}

	/**
	 * Convert video format from one codec to another
	 *
	 * @param string $inputPath Path to the input video file
	 * @param string $outputPath Path to the output video file
	 * @param string $outputFormat Output format (e.g., 'mp4', 'avi', 'mkv')
	 * @return bool|string
	 */
	public function convertVideoFormat(string $inputPath, string $outputPath, string $outputFormat): bool|string
	{
		$cmd = sprintf("ffmpeg -i %s -c:v libx264 -c:a aac %s.%s", $inputPath, $outputPath, $outputFormat);
		return exec($cmd, $output, $returnVariable);
	}

	/**
	 * Extract audio from video file
	 *
	 * @param string $inputPath Path to the input video file
	 * @param string $outputPath Path to the output audio file
	 * @param string $audioFormat Output audio format (e.g., 'mp3', 'wav', 'aac')
	 * @return bool|string
	 */
	public function extractAudio(string $inputPath, string $outputPath, string $audioFormat = 'mp3'): bool|string
	{
		$cmd = sprintf("ffmpeg -i %s -vn -c:a libmp3lame %s.%s", $inputPath, $outputPath, $audioFormat);
		return exec($cmd, $output, $returnVariable);
	}

	/**
	 * Resize video to specified dimensions
	 *
	 * @param string $inputPath Path to the input video file
	 * @param string $outputPath Path to the output video file
	 * @param int $width New width in pixels
	 * @param int $height New height in pixels
	 * @return bool|string
	 */
	public function resizeVideo(string $inputPath, string $outputPath, int $width, int $height): bool|string
	{
		$cmd = sprintf("ffmpeg -i %s -vf scale=%d:%d %s", $inputPath, $width, $height, $outputPath);
		return exec($cmd, $output, $returnVariable);
	}

	/**
	 * Trim video to specified start and end times
	 *
	 * @param string $inputPath Path to the input video file
	 * @param string $outputPath Path to the output video file
	 * @param string $startTime Start time in HH:MM:SS format
	 * @param string $endTime End time in HH:MM:SS format
	 * @return bool|string
	 */
	public function trimVideo(string $inputPath, string $outputPath, string $startTime, string $endTime): bool|string
	{
		$cmd = sprintf("ffmpeg -i %s -ss %s -to %s -c copy %s", $inputPath, $startTime, $endTime, $outputPath);
		return exec($cmd, $output, $returnVariable);
	}

	/**
	 * Generate thumbnail image from video at specified time
	 *
	 * @param string $inputPath Path to the input video file
	 * @param string $outputPath Path to the output thumbnail image
	 * @param string $time Time to capture thumbnail in HH:MM:SS format (default: 00:00:01)
	 * @return bool|string
	 */
	public function generateThumbnail(string $inputPath, string $outputPath, string $time = '00:00:01'): bool|string
	{
		$cmd = sprintf("ffmpeg -i %s -ss %s -vframes 1 %s", $inputPath, $time, $outputPath);
		return exec($cmd, $output, $returnVariable);
	}

	/**
	 * Get video information using ffprobe
	 *
	 * @param string $inputPath Path to the video file
	 * @return array Array containing video information
	 */
	public function getVideoInfo(string $inputPath): mixed
	{
		$cmd = sprintf("ffprobe -v quiet -print_format json -show_format -show_streams %s", $inputPath);
		exec($cmd, $output, $returnVariable);
		$json = implode("\n", $output);
		return json_decode($json, true);
	}

	/**
	 * Concatenate multiple video files
	 *
	 * @param array $inputPaths Array of paths to input video files
	 * @param string $outputPath Path to the output concatenated video file
	 * @return bool|string
	 */
	public function concatenateVideos(array $inputPaths, string $outputPath): bool|string
	{
		$fileList = tempnam(sys_get_temp_dir(), 'ffmpeg_concat');
		$content = '';
		foreach ($inputPaths as $path) {
			$content .= "file '" . $path . "'\n";
		}
		file_put_contents($fileList, $content);
		$cmd = sprintf("ffmpeg -f concat -safe 0 -i %s -c copy %s", $fileList, $outputPath);
		$result = exec($cmd, $output, $returnVariable);
		unlink($fileList);

		return $result;
	}

	/**
	 * Add watermark to video
	 *
	 * @param string $inputPath Path to the input video file
	 * @param string $outputPath Path to the output video file
	 * @param string $watermarkPath Path to the watermark image file
	 * @param int $x X position of watermark
	 * @param int $y Y position of watermark
	 * @return bool|string
	 */
	public function addWatermark(string $inputPath, string $outputPath, string $watermarkPath, int $x = 10, int $y = 10): bool|string
	{
		$cmd = sprintf("ffmpeg -i %s -i %s -filter_complex \"[0:v][1:v] overlay=%d:%d\" -c:a copy %s", $inputPath, $watermarkPath, $x, $y, $outputPath);
		return exec($cmd, $output, $returnVariable);
	}
}
