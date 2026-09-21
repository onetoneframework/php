<?php

declare(strict_types=1);

namespace App\Modules\AudioAnalysis\DataTransferObject;

use App\Modules\AudioAnalysis\Exception\InvalidAudioAnalysisResponseException;
use Clover\Classes\Data\StringObject;

use function abs;
use function array_key_exists;
use function count;
use function is_array;
use function is_bool;
use function is_finite;
use function is_float;
use function is_int;
use function is_string;
use function trim;

/**
 * Validates and carries a versioned acoustic analysis response payload.
 *
 * @phpstan-type AnalysisSnapshot array{
 *     analysis_id: non-empty-string,
 *     recording: array<string, mixed>,
 *     measurements: array<mixed>
 * }
 * @phpstan-type AnalysisPayload array{
 *     analysis_id: non-empty-string,
 *     recording: array<string, mixed>,
 *     measurements: array<mixed>,
 *     notice: string
 * }
 * @phpstan-type ComparisonPayload array{
 *     comparison_id: non-empty-string,
 *     baseline: AnalysisSnapshot,
 *     current: AnalysisSnapshot,
 *     changes: array<mixed>,
 *     notice: string
 * }
 */
final class AudioAnalysisResponse
{
	private const MINIMUM_DURATION_SECONDS = 0.25;
	private const MAXIMUM_DURATION_SECONDS = 3600.0;
	private const MINIMUM_SAMPLE_RATE_HZ = 16_000;
	private const MAXIMUM_SAMPLE_RATE_HZ = 192_000;
	private const MINIMUM_CHANNEL_COUNT = 1;
	private const MAXIMUM_CHANNEL_COUNT = 2;
	private const MINIMUM_SAMPLE_WIDTH_BYTES = 1;
	private const MAXIMUM_SAMPLE_WIDTH_BYTES = 4;
	private const MAXIMUM_UPLOAD_BYTES = 1_073_741_824;
	private const MAXIMUM_WAVEFORM_CHART_POINTS = 1_200;
	private const MAXIMUM_ACOUSTIC_TRACK_POINTS = 1_200;
	private const MAXIMUM_SPECTRUM_CHART_POINTS = 320;
	private const MAXIMUM_SPECTRUM_FREQUENCY_HZ = 8_000.0;
	private const COVERAGE_DURATION_EPSILON = 0.001;
	private const FULL_COVERAGE_MINIMUM_RATIO = 0.999;
	private const FULL_RECORDING_STRATEGY = 'full_recording';
	private const WINDOWED_RECORDING_STRATEGY = 'evenly_spaced_windows';
	private const RELATIVE_CHANGE_EPSILON = 1.0E-12;
	private const WAVE_CONTENT_TYPE = 'audio/wav';

	private const MEASUREMENT_UNITS_BY_NAME = [
		'peak_amplitude' => 'amplitude',
		'dc_offset' => 'amplitude',
		'global_root_mean_square' => 'amplitude',
		'global_root_mean_square_dbfs' => 'dBFS',
		'crest_factor_db' => 'dB',
		'root_mean_square_dbfs_standard_deviation' => 'dB',
		'root_mean_square_dbfs_fifth_percentile' => 'dBFS',
		'root_mean_square_dbfs_ninety_fifth_percentile' => 'dBFS',
		'dynamic_range_db' => 'dB',
		'fundamental_frequency_median_hz' => 'Hz',
		'fundamental_frequency_standard_deviation_hz' => 'Hz',
		'fundamental_frequency_fifth_percentile_hz' => 'Hz',
		'fundamental_frequency_ninety_fifth_percentile_hz' => 'Hz',
		'fundamental_frequency_range_hz' => 'Hz',
		'voiced_fraction' => 'ratio',
		'unvoiced_fraction' => 'ratio',
		'intensity_mean_db' => 'dB',
		'intensity_standard_deviation_db' => 'dB',
		'intensity_fifth_percentile_db' => 'dB',
		'intensity_ninety_fifth_percentile_db' => 'dB',
		'harmonics_to_noise_ratio_mean_db' => 'dB',
		'harmonics_to_noise_ratio_standard_deviation_db' => 'dB',
		'harmonics_to_noise_ratio_fifth_percentile_db' => 'dB',
		'local_jitter_percent' => 'percent',
		'rap_jitter_percent' => 'percent',
		'ppq5_jitter_percent' => 'percent',
		'local_shimmer_percent' => 'percent',
		'apq3_shimmer_percent' => 'percent',
		'apq5_shimmer_percent' => 'percent',
		'first_formant_median_hz' => 'Hz',
		'second_formant_median_hz' => 'Hz',
		'third_formant_median_hz' => 'Hz',
		'fourth_formant_median_hz' => 'Hz',
		'first_formant_standard_deviation_hz' => 'Hz',
		'second_formant_standard_deviation_hz' => 'Hz',
		'third_formant_standard_deviation_hz' => 'Hz',
		'fourth_formant_standard_deviation_hz' => 'Hz',
		'formant_dispersion_hz' => 'Hz',
		'geometric_mean_formant_hz' => 'Hz',
		'vocal_tract_length_fitch_cm' => 'cm',
		'vocal_tract_length_odd_harmonic_cm' => 'cm',
		'spectral_centroid_mean_hz' => 'Hz',
		'spectral_bandwidth_mean_hz' => 'Hz',
		'spectral_rolloff_mean_hz' => 'Hz',
		'spectral_flatness_mean' => 'ratio',
		'spectral_flux_mean' => 'amplitude',
		'spectral_contrast_mean_db' => 'dB',
		'spectral_tilt_db_per_octave' => 'dB/octave',
		'onset_strength_mean' => 'amplitude',
		'zero_crossing_rate_mean' => 'ratio',
	];

	private const RELATIVE_CHANGE_NAMES = [
		'peak_amplitude' => true,
		'global_root_mean_square' => true,
		'fundamental_frequency_median_hz' => true,
		'fundamental_frequency_standard_deviation_hz' => true,
		'fundamental_frequency_fifth_percentile_hz' => true,
		'fundamental_frequency_ninety_fifth_percentile_hz' => true,
		'fundamental_frequency_range_hz' => true,
		'voiced_fraction' => true,
		'unvoiced_fraction' => true,
		'local_jitter_percent' => true,
		'rap_jitter_percent' => true,
		'ppq5_jitter_percent' => true,
		'local_shimmer_percent' => true,
		'apq3_shimmer_percent' => true,
		'apq5_shimmer_percent' => true,
		'first_formant_median_hz' => true,
		'second_formant_median_hz' => true,
		'third_formant_median_hz' => true,
		'fourth_formant_median_hz' => true,
		'first_formant_standard_deviation_hz' => true,
		'second_formant_standard_deviation_hz' => true,
		'third_formant_standard_deviation_hz' => true,
		'fourth_formant_standard_deviation_hz' => true,
		'formant_dispersion_hz' => true,
		'geometric_mean_formant_hz' => true,
		'vocal_tract_length_fitch_cm' => true,
		'vocal_tract_length_odd_harmonic_cm' => true,
		'spectral_centroid_mean_hz' => true,
		'spectral_bandwidth_mean_hz' => true,
		'spectral_rolloff_mean_hz' => true,
		'spectral_flatness_mean' => true,
		'spectral_flux_mean' => true,
		'onset_strength_mean' => true,
		'zero_crossing_rate_mean' => true,
	];

	/**
	 * @param array<string, mixed> $payload
	 */
	private function __construct(private array $payload)
	{
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function fromPayload(array $payload): self
	{
		if (!self::isAnalysisPayload($payload) && !self::isComparisonPayload($payload)) {
			throw new InvalidAudioAnalysisResponseException('The acoustic analysis response schema is invalid.');
		}

		return new self($payload);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return $this->payload;
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private static function isAnalysisPayload(array $payload): bool
	{
		return self::isAnalysisSnapshot($payload)
			&& array_key_exists('notice', $payload)
			&& is_string($payload['notice'])
			&& trim($payload['notice']) !== '';
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private static function isAnalysisSnapshot(array $payload): bool
	{
		return array_key_exists('analysis_id', $payload)
			&& is_string($payload['analysis_id'])
			&& (new StringObject($payload['analysis_id']))->isUuid()
			&& array_key_exists('recording', $payload)
			&& is_array($payload['recording'])
			&& self::isRecording($payload['recording'])
			&& array_key_exists('measurements', $payload)
			&& is_array($payload['measurements'])
			&& self::isMeasurementList($payload['measurements'])
			&& array_key_exists('charts', $payload)
			&& is_array($payload['charts'])
			&& self::isCharts($payload['charts'], (float) $payload['recording']['duration_seconds']);
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private static function isComparisonPayload(array $payload): bool
	{
		return array_key_exists('comparison_id', $payload)
			&& is_string($payload['comparison_id'])
			&& (new StringObject($payload['comparison_id']))->isUuid()
			&& array_key_exists('baseline', $payload)
			&& is_array($payload['baseline'])
			&& self::isAnalysisSnapshot($payload['baseline'])
			&& array_key_exists('current', $payload)
			&& is_array($payload['current'])
			&& self::isAnalysisSnapshot($payload['current'])
			&& array_key_exists('changes', $payload)
			&& is_array($payload['changes'])
			&& self::isChangeList($payload['changes'])
			&& array_key_exists('notice', $payload)
			&& is_string($payload['notice'])
			&& trim($payload['notice']) !== '';
	}

	/**
	 * @param array<string, mixed> $recording
	 */
	private static function isRecording(array $recording): bool
	{
		return array_key_exists('filename', $recording)
			&& is_string($recording['filename'])
			&& trim($recording['filename']) !== ''
			&& array_key_exists('content_type', $recording)
			&& $recording['content_type'] === self::WAVE_CONTENT_TYPE
			&& array_key_exists('size_bytes', $recording)
			&& is_int($recording['size_bytes'])
			&& $recording['size_bytes'] > 0
			&& $recording['size_bytes'] <= self::MAXIMUM_UPLOAD_BYTES
			&& array_key_exists('duration_seconds', $recording)
			&& self::isFiniteNumber($recording['duration_seconds'])
			&& $recording['duration_seconds'] >= self::MINIMUM_DURATION_SECONDS
			&& $recording['duration_seconds'] <= self::MAXIMUM_DURATION_SECONDS
			&& array_key_exists('sample_rate_hz', $recording)
			&& is_int($recording['sample_rate_hz'])
			&& $recording['sample_rate_hz'] >= self::MINIMUM_SAMPLE_RATE_HZ
			&& $recording['sample_rate_hz'] <= self::MAXIMUM_SAMPLE_RATE_HZ
			&& array_key_exists('channel_count', $recording)
			&& is_int($recording['channel_count'])
			&& $recording['channel_count'] >= self::MINIMUM_CHANNEL_COUNT
			&& $recording['channel_count'] <= self::MAXIMUM_CHANNEL_COUNT
			&& array_key_exists('frame_count', $recording)
			&& is_int($recording['frame_count'])
			&& $recording['frame_count'] > 0
			&& array_key_exists('sample_width_bytes', $recording)
			&& is_int($recording['sample_width_bytes'])
			&& $recording['sample_width_bytes'] >= self::MINIMUM_SAMPLE_WIDTH_BYTES
			&& $recording['sample_width_bytes'] <= self::MAXIMUM_SAMPLE_WIDTH_BYTES;
	}

	/**
	 * @param array<mixed> $measurements
	 */
	private static function isMeasurementList(array $measurements): bool
	{
		if (count($measurements) !== count(self::MEASUREMENT_UNITS_BY_NAME)) {
			return false;
		}

		$seenNames = [];
		foreach ($measurements as $measurement) {
			if (!is_array($measurement) || !self::isMeasurement($measurement)) {
				return false;
			}

			$name = $measurement['name'];
			if (array_key_exists($name, $seenNames)) {
				return false;
			}

			$seenNames[$name] = true;
		}

		return count($seenNames) === count(self::MEASUREMENT_UNITS_BY_NAME);
	}

	/**
	 * @param array<string, mixed> $measurement
	 */
	private static function isMeasurement(array $measurement): bool
	{
		if (
			!array_key_exists('name', $measurement)
			|| !is_string($measurement['name'])
			|| !array_key_exists($measurement['name'], self::MEASUREMENT_UNITS_BY_NAME)
		) {
			return false;
		}

		return array_key_exists('label', $measurement)
			&& is_string($measurement['label'])
			&& trim($measurement['label']) !== ''
			&& array_key_exists('value', $measurement)
			&& self::isFiniteNumber($measurement['value'])
			&& array_key_exists('unit', $measurement)
			&& $measurement['unit'] === self::MEASUREMENT_UNITS_BY_NAME[$measurement['name']]
			&& array_key_exists('available', $measurement)
			&& is_bool($measurement['available'])
			&& ($measurement['available'] || (float) $measurement['value'] === 0.0);
	}

	/**
	 * @param array<mixed> $changes
	 */
	private static function isChangeList(array $changes): bool
	{
		if (count($changes) !== count(self::MEASUREMENT_UNITS_BY_NAME)) {
			return false;
		}

		$seenNames = [];
		foreach ($changes as $change) {
			if (!is_array($change) || !self::isChange($change)) {
				return false;
			}

			$name = $change['name'];
			if (array_key_exists($name, $seenNames)) {
				return false;
			}

			$seenNames[$name] = true;
		}

		return count($seenNames) === count(self::MEASUREMENT_UNITS_BY_NAME);
	}

	/**
	 * @param array<string, mixed> $change
	 */
	private static function isChange(array $change): bool
	{
		if (
			!array_key_exists('name', $change)
			|| !is_string($change['name'])
			|| !array_key_exists($change['name'], self::MEASUREMENT_UNITS_BY_NAME)
		) {
			return false;
		}

		$numericFields = [
			'baseline_value',
			'current_value',
			'absolute_change',
			'relative_change_percent',
		];
		foreach ($numericFields as $fieldName) {
			if (!array_key_exists($fieldName, $change) || !self::isFiniteNumber($change[$fieldName])) {
				return false;
			}
		}

		$booleanFields = [
			'baseline_available',
			'current_available',
			'available',
			'relative_change_available',
		];
		foreach ($booleanFields as $fieldName) {
			if (!array_key_exists($fieldName, $change) || !is_bool($change[$fieldName])) {
				return false;
			}
		}

		$available = $change['baseline_available'] && $change['current_available'];
		$relativeChangeAllowed = $available
			&& array_key_exists($change['name'], self::RELATIVE_CHANGE_NAMES)
			&& abs((float) $change['baseline_value']) > self::RELATIVE_CHANGE_EPSILON;

		return array_key_exists('label', $change)
			&& is_string($change['label'])
			&& trim($change['label']) !== ''
			&& array_key_exists('unit', $change)
			&& $change['unit'] === self::MEASUREMENT_UNITS_BY_NAME[$change['name']]
			&& $change['available'] === $available
			&& ($available || (float) $change['absolute_change'] === 0.0)
			&& ($change['baseline_available'] || (float) $change['baseline_value'] === 0.0)
			&& ($change['current_available'] || (float) $change['current_value'] === 0.0)
			&& (!$change['relative_change_available'] || $relativeChangeAllowed)
			&& ($change['relative_change_available'] || (float) $change['relative_change_percent'] === 0.0);
	}

	/**
	 * @param array<string, mixed> $charts
	 */
	private static function isCharts(array $charts, float $recordingDurationSeconds): bool
	{
		if (
			!array_key_exists('waveform', $charts)
			|| !is_array($charts['waveform'])
			|| !self::isBoundedList($charts['waveform'], self::MAXIMUM_WAVEFORM_CHART_POINTS)
			|| !array_key_exists('tracks', $charts)
			|| !is_array($charts['tracks'])
			|| !self::isBoundedList($charts['tracks'], self::MAXIMUM_ACOUSTIC_TRACK_POINTS)
			|| !array_key_exists('spectrum', $charts)
			|| !is_array($charts['spectrum'])
			|| !self::isBoundedList($charts['spectrum'], self::MAXIMUM_SPECTRUM_CHART_POINTS)
			|| !array_key_exists('coverage', $charts)
			|| !is_array($charts['coverage'])
			|| !self::isCoverage($charts['coverage'], $recordingDurationSeconds)
		) {
			return false;
		}

		foreach ($charts['waveform'] as $point) {
			if (!is_array($point) || !self::isWaveformPoint($point, $recordingDurationSeconds)) {
				return false;
			}
		}

		foreach ($charts['tracks'] as $point) {
			if (!is_array($point) || !self::isTrackPoint($point, $recordingDurationSeconds)) {
				return false;
			}
		}

		foreach ($charts['spectrum'] as $point) {
			if (!is_array($point) || !self::isSpectrumPoint($point)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param array<mixed> $values
	 */
	private static function isBoundedList(array $values, int $maximumCount): bool
	{
		return array_is_list($values) && count($values) > 0 && count($values) <= $maximumCount;
	}

	/**
	 * @param array<string, mixed> $point
	 */
	private static function isWaveformPoint(array $point, float $recordingDurationSeconds): bool
	{
		return array_key_exists('time_seconds', $point)
			&& self::isFiniteNumber($point['time_seconds'])
			&& (float) $point['time_seconds'] >= 0.0
			&& (float) $point['time_seconds'] <= $recordingDurationSeconds
			&& array_key_exists('minimum_amplitude', $point)
			&& self::isFiniteNumber($point['minimum_amplitude'])
			&& array_key_exists('maximum_amplitude', $point)
			&& self::isFiniteNumber($point['maximum_amplitude'])
			&& (float) $point['minimum_amplitude'] <= (float) $point['maximum_amplitude'];
	}

	/**
	 * @param array<string, mixed> $point
	 */
	private static function isTrackPoint(array $point, float $recordingDurationSeconds): bool
	{
		if (
			!array_key_exists('time_seconds', $point)
			|| !self::isFiniteNumber($point['time_seconds'])
			|| (float) $point['time_seconds'] < 0.0
			|| (float) $point['time_seconds'] > $recordingDurationSeconds
			|| !array_key_exists('segment_index', $point)
			|| !is_int($point['segment_index'])
			|| $point['segment_index'] < 0
		) {
			return false;
		}

		$valueAvailabilityPairs = [
			'fundamental_frequency_hz' => 'fundamental_frequency_available',
			'first_formant_hz' => 'first_formant_available',
			'second_formant_hz' => 'second_formant_available',
			'third_formant_hz' => 'third_formant_available',
			'fourth_formant_hz' => 'fourth_formant_available',
			'spectral_tilt_db_per_octave' => 'spectral_tilt_available',
		];
		foreach ($valueAvailabilityPairs as $valueName => $availabilityName) {
			if (
				!array_key_exists($valueName, $point)
				|| !self::isFiniteNumber($point[$valueName])
				|| !array_key_exists($availabilityName, $point)
				|| !is_bool($point[$availabilityName])
				|| (!$point[$availabilityName] && (float) $point[$valueName] !== 0.0)
			) {
				return false;
			}
		}

		return (float) $point['fundamental_frequency_hz'] >= 0.0
			&& (float) $point['first_formant_hz'] >= 0.0
			&& (float) $point['second_formant_hz'] >= 0.0
			&& (float) $point['third_formant_hz'] >= 0.0
			&& (float) $point['fourth_formant_hz'] >= 0.0;
	}

	/**
	 * @param array<string, mixed> $point
	 */
	private static function isSpectrumPoint(array $point): bool
	{
		return array_key_exists('frequency_hz', $point)
			&& self::isFiniteNumber($point['frequency_hz'])
			&& (float) $point['frequency_hz'] >= 0.0
			&& (float) $point['frequency_hz'] <= self::MAXIMUM_SPECTRUM_FREQUENCY_HZ
			&& array_key_exists('level_db', $point)
			&& self::isFiniteNumber($point['level_db'])
			&& array_key_exists('tilt_db', $point)
			&& self::isFiniteNumber($point['tilt_db']);
	}

	/**
	 * @param array<string, mixed> $coverage
	 */
	private static function isCoverage(array $coverage, float $recordingDurationSeconds): bool
	{
		if (
			!array_key_exists('total_duration_seconds', $coverage)
			|| !self::isFiniteNumber($coverage['total_duration_seconds'])
			|| abs((float) $coverage['total_duration_seconds'] - $recordingDurationSeconds) > self::COVERAGE_DURATION_EPSILON
			|| !array_key_exists('analyzed_duration_seconds', $coverage)
			|| !self::isFiniteNumber($coverage['analyzed_duration_seconds'])
			|| (float) $coverage['analyzed_duration_seconds'] <= 0.0
			|| (float) $coverage['analyzed_duration_seconds'] > $recordingDurationSeconds
			|| !array_key_exists('coverage_ratio', $coverage)
			|| !self::isFiniteNumber($coverage['coverage_ratio'])
			|| (float) $coverage['coverage_ratio'] <= 0.0
			|| (float) $coverage['coverage_ratio'] > 1.0
			|| !array_key_exists('strategy', $coverage)
			|| !is_string($coverage['strategy'])
			|| !in_array($coverage['strategy'], [self::FULL_RECORDING_STRATEGY, self::WINDOWED_RECORDING_STRATEGY], true)
		) {
			return false;
		}

		$expectedRatio = (float) $coverage['analyzed_duration_seconds'] / $recordingDurationSeconds;
		if (abs($expectedRatio - (float) $coverage['coverage_ratio']) > self::COVERAGE_DURATION_EPSILON) {
			return false;
		}

		return $coverage['strategy'] !== self::FULL_RECORDING_STRATEGY
			|| (float) $coverage['coverage_ratio'] >= self::FULL_COVERAGE_MINIMUM_RATIO;
	}

	private static function isFiniteNumber(mixed $value): bool
	{
		return (is_int($value) || is_float($value)) && is_finite((float) $value);
	}
}
