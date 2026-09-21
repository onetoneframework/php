<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

final class AudioAnalysisPayloadFixture
{
	public const ANALYSIS_IDENTIFIER = '00000000-0000-4000-8000-000000000001';
	public const BASELINE_IDENTIFIER = '00000000-0000-4000-8000-000000000002';
	public const CURRENT_IDENTIFIER = '00000000-0000-4000-8000-000000000003';
	public const COMPARISON_IDENTIFIER = '00000000-0000-4000-8000-000000000004';

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
	 * @return array<string, mixed>
	 */
	public static function analysis(string $analysisIdentifier = self::ANALYSIS_IDENTIFIER): array
	{
		return [
			'analysis_id' => $analysisIdentifier,
			'recording' => [
				'filename' => 'sample.wav',
				'content_type' => 'audio/wav',
				'size_bytes' => 32_044,
				'duration_seconds' => 1.0,
				'sample_rate_hz' => 16_000,
				'channel_count' => 1,
				'frame_count' => 16_000,
				'sample_width_bytes' => 2,
			],
			'measurements' => self::measurements(),
			'charts' => self::charts(),
			'notice' => 'Measurements support tracking and are not a diagnosis.',
		];
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function snapshot(string $analysisIdentifier): array
	{
		$payload = self::analysis($analysisIdentifier);
		unset($payload['notice']);
		return $payload;
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function comparison(): array
	{
		return [
			'comparison_id' => self::COMPARISON_IDENTIFIER,
			'baseline' => self::snapshot(self::BASELINE_IDENTIFIER),
			'current' => self::snapshot(self::CURRENT_IDENTIFIER),
			'changes' => self::changes(),
			'notice' => 'Measurements support tracking and are not a diagnosis.',
		];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function measurements(): array
	{
		$measurements = [];
		foreach (self::MEASUREMENT_UNITS_BY_NAME as $name => $unit) {
			$measurements[] = [
				'name' => $name,
				'label' => $name,
				'value' => 1.0,
				'unit' => $unit,
				'available' => true,
			];
		}

		return $measurements;
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function charts(): array
	{
		return [
			'waveform' => [
				['time_seconds' => 0.25, 'minimum_amplitude' => -0.5, 'maximum_amplitude' => 0.5],
				['time_seconds' => 0.75, 'minimum_amplitude' => -0.4, 'maximum_amplitude' => 0.4],
			],
			'tracks' => [
				[
					'time_seconds' => 0.25,
					'segment_index' => 0,
					'fundamental_frequency_hz' => 180.0,
					'fundamental_frequency_available' => true,
					'first_formant_hz' => 700.0,
					'first_formant_available' => true,
					'second_formant_hz' => 1_200.0,
					'second_formant_available' => true,
					'third_formant_hz' => 2_500.0,
					'third_formant_available' => true,
					'fourth_formant_hz' => 3_800.0,
					'fourth_formant_available' => true,
					'spectral_tilt_db_per_octave' => -6.0,
					'spectral_tilt_available' => true,
				],
			],
			'spectrum' => [
				['frequency_hz' => 100.0, 'level_db' => -2.0, 'tilt_db' => -3.0],
				['frequency_hz' => 1_000.0, 'level_db' => -12.0, 'tilt_db' => -9.0],
			],
			'coverage' => [
				'total_duration_seconds' => 1.0,
				'analyzed_duration_seconds' => 1.0,
				'coverage_ratio' => 1.0,
				'strategy' => 'full_recording',
			],
		];
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private static function changes(): array
	{
		$changes = [];
		foreach (self::MEASUREMENT_UNITS_BY_NAME as $name => $unit) {
			$changes[] = [
				'name' => $name,
				'label' => $name,
				'unit' => $unit,
				'baseline_value' => 1.0,
				'current_value' => 1.0,
				'absolute_change' => 0.0,
				'relative_change_percent' => 0.0,
				'baseline_available' => true,
				'current_available' => true,
				'available' => true,
				'relative_change_available' => isset(self::RELATIVE_CHANGE_NAMES[$name]),
			];
		}

		return $changes;
	}
}
