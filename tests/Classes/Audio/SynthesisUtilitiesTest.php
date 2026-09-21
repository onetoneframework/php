<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Audio;

use Clover\Classes\Audio\GlottalSource;
use Clover\Classes\Audio\SourceFilter;
use Clover\Classes\Audio\VowelFormant;
use PHPUnit\Framework\TestCase;

final class SynthesisUtilitiesTest extends TestCase
{
	public function testVowelFormantsAreCaseInsensitiveAndExtensible(): void
	{
		$formants = new VowelFormant();

		$this->assertSame(['a', 'e', 'i', 'o', 'u'], $formants->getAllVowels());
		$this->assertSame($formants->getFormants('a'), $formants->getFormants('A'));
		$this->assertSame([], $formants->getFormants('unknown'));

		$custom = [['frequency' => 400, 'bandwidth' => 75, 'amplitude' => 0.8]];
		$formants->addCustomFormant('custom', $custom);
		$this->assertSame($custom, $formants->getFormants('custom'));
	}

	public function testSourceFilterGeneratesHarmonicSpectrumAndNearestAmplitude(): void
	{
		$filter = new SourceFilter(100.0, 3);

		$this->assertSame([
			['frequency' => 100.0, 'amplitude' => 1.0],
			['frequency' => 200.0, 'amplitude' => 0.5],
			['frequency' => 300.0, 'amplitude' => 1.0 / 3.0],
		], $filter->getSpectrum());
		$this->assertSame(0.5, $filter->getAmplitudeAtFrequency(190.0));
	}

	public function testSourceFilterAppliesGaussianFormantResponse(): void
	{
		$filter = new SourceFilter(100.0, 3);
		$filtered = $filter->applyFormantFilter([
			['frequency' => 200.0, 'bandwidth' => 100.0],
		]);

		$this->assertCount(3, $filtered);
		$this->assertEqualsWithDelta(0.5, $filtered[1]['amplitude'], 0.000001);
		$this->assertLessThan($filter->getSpectrum()[0]['amplitude'], $filtered[0]['amplitude']);
		$this->assertLessThan($filter->getSpectrum()[2]['amplitude'], $filtered[2]['amplitude']);
	}

	public function testGlottalSourceGeneratesDeterministicWaveformAndSpectrum(): void
	{
		$source = new GlottalSource(100.0);
		$waveform = $source->generateLFWaveform(6, 1000);

		$this->assertCount(6, $waveform);
		$this->assertSame(0.0, $waveform[0]);
		foreach ($waveform as $sample) {
			$this->assertGreaterThanOrEqual(0.0, $sample);
			$this->assertLessThanOrEqual(1.0, $sample);
		}

		$this->assertSame([
			['frequency' => 100.0, 'amplitude' => 1.0],
			['frequency' => 200.0, 'amplitude' => 0.25],
			['frequency' => 300.0, 'amplitude' => 1.0 / 9.0],
		], $source->getSpectrum(3));
	}
}
