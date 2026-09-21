<?php

declare(strict_types=1);

namespace Clover\Tests\Math;
use InvalidArgumentException;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Math\Vocal;
use Clover\Classes\Math\VTL;
use PHPUnit\Framework\TestCase;

class VocalMathTest extends TestCase
{

	public function setUp(): void
	{
	}

	public function testFailure(): void
	{
		$f4 = Vocal::getEffectiveAcousticVocalTractLength(4200, 4);
		$this->assertEquals('14.583333333333334', $f4);

		$f4 = Vocal::getEffectiveAcousticVocalTractLength(3800, 4);
		$this->assertEquals('16.11842105263158', $f4);
	}

	public function testGetEffectiveAcousticVocalTractLength(): void
	{
		$result = Vocal::getEffectiveAcousticVocalTractLength(500.0, 1);
		$this->assertEquals('17.5', (string) $result);

		$result = Vocal::getEffectiveAcousticVocalTractLength(2500.0, 3, 35000.0);
		$this->assertEquals('17.5', (string) $result);
	}

	public function testGetEffectiveAcousticVocalTractLengthThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::getEffectiveAcousticVocalTractLength(0, 1);
	}

	public function testGetFormantDispersion(): void
	{
		$result = Vocal::getFormantDispersion(17.5);
		$this->assertEquals('1000', (string) $result);
	}

	public function testGetFormantDispersionThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::getFormantDispersion(0);
	}

	public function testGetVocalTractLengthFromF3(): void
	{
		$result = Vocal::getVocalTractLengthFromF3(2500.0);
		$this->assertEquals('17.5', (string) $result);

		$result = Vocal::getVocalTractLengthFromF3(2800.0, 35000.0);
		$this->assertEquals('15.625', (string) $result);
	}

	public function testGetVocalTractLengthFromF3ThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::getVocalTractLengthFromF3(-100.0);
	}

	public function testCalculatePhiValues(): void
	{
		$formants = [500.0, 1500.0, 2500.0, 3500.0];
		$result = Vocal::calculatePhiValues($formants);

		$this->assertEquals('500', (string) $result[0]);
		$this->assertEquals('500', (string) $result[1]);
		$this->assertEquals('500', (string) $result[2]);
		$this->assertEquals('500', (string) $result[3]);
	}

	public function testCalculatePhiValuesThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::calculatePhiValues([500.0, -100.0]);
	}

	public function testIsNeutralVowel(): void
	{
		$neutralFormants = [500.0, 1500.0, 2500.0, 3500.0];
		$this->assertTrue(Vocal::isNeutralVowel($neutralFormants));

		$nonNeutralFormants = [344.4, 894.3, 2728.6, 3533.4];
		$this->assertFalse(Vocal::isNeutralVowel($nonNeutralFormants));
	}

	public function testEstimateVocalTractLengthFlego(): void
	{
		$formants = [500.0, 1500.0, 2500.0, 3500.0];
		$result = Vocal::estimateVocalTractLengthFlego($formants);

		$this->assertEquals('500', (string) $result['phi_mean']);
		$this->assertEquals('0', (string) $result['phi_std_dev']);
		$this->assertEquals('17.5', (string) $result['acoustic_length']);
		$this->assertEquals('16.7', (string) $result['anatomical_length']);
		$this->assertTrue($result['is_neutral_vowel']);
		$this->assertEquals('high', $result['confidence']);
	}

	public function testEstimateVocalTractLengthFlegoThrowsOnInsufficientFormants(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::estimateVocalTractLengthFlego([500.0]);
	}

	public function testEstimateVocalTractLengthScordilis(): void
	{
		$result = Vocal::estimateVocalTractLengthScordilis(4500.0, 5);
		$this->assertEquals('900', (string) $result['average_spacing']);
		$this->assertEquals('19.44', (string) $result['acoustic_length']);
		$this->assertEquals('18.64', (string) $result['anatomical_length']);
	}

	public function testEstimateVocalTractLengthScordilisThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::estimateVocalTractLengthScordilis(0, 5);
	}

	public function testApplyEndCorrection(): void
	{
		$result = Vocal::applyEndCorrection(17.5);
		$this->assertEquals('16.7', (string) $result);

		$result = Vocal::applyEndCorrection(17.5, 0.5, 0.5);
		$this->assertEquals('16.5', (string) $result);
	}

	public function testGetTwangRatio(): void
	{
		$this->assertTrue(Vocal::getTwangRatio(3500.0, 2800.0));
		$this->assertFalse(Vocal::getTwangRatio(4000.0, 2500.0));
	}

	public function testGetTwangRatioThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::getTwangRatio(0, 2500.0);
	}

	public function testGetTongueAdvancement(): void
	{
		$this->assertEquals('front', Vocal::getTongueAdvancement(1600.0, true));
		$this->assertEquals('central', Vocal::getTongueAdvancement(1300.0, true));
		$this->assertEquals('back', Vocal::getTongueAdvancement(1000.0, true));

		$this->assertEquals('front', Vocal::getTongueAdvancement(1900.0, false));
		$this->assertEquals('central', Vocal::getTongueAdvancement(1500.0, false));
		$this->assertEquals('back', Vocal::getTongueAdvancement(1200.0, false));
	}

	public function testGetTongueAdvancementThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::getTongueAdvancement(0);
	}

	public function testGetJawOpenedStatus(): void
	{
		$this->assertEquals('opened', Vocal::getJawOpenedStatus(700.0, true));
		$this->assertEquals('mid', Vocal::getJawOpenedStatus(500.0, true));
		$this->assertEquals('closed', Vocal::getJawOpenedStatus(300.0, true));

		$this->assertEquals('opened', Vocal::getJawOpenedStatus(800.0, false));
		$this->assertEquals('mid', Vocal::getJawOpenedStatus(600.0, false));
		$this->assertEquals('closed', Vocal::getJawOpenedStatus(400.0, false));
	}

	public function testGetJawOpenedStatusThrowsOnInvalidInput(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::getJawOpenedStatus(-100.0);
	}

	public function testIsJawOpened(): void
	{
		$this->assertTrue(Vocal::isJawOpened(700.0, true));
		$this->assertFalse(Vocal::isJawOpened(500.0, true));

		$this->assertTrue(Vocal::isJawOpened(800.0, false));
		$this->assertFalse(Vocal::isJawOpened(700.0, false));
	}

	public function testIsFrontVowel(): void
	{
		$this->assertTrue(Vocal::isFrontVowel(1600.0, true));
		$this->assertFalse(Vocal::isFrontVowel(1000.0, true));
	}

	public function testIsBackVowel(): void
	{
		$this->assertTrue(Vocal::isBackVowel(1000.0, true));
		$this->assertFalse(Vocal::isBackVowel(1600.0, true));
	}

	public function testGetAnatomicalVocalTractLength(): void
	{
		$result = Vocal::getAnatomicalVocalTractLength(500.0, 1500.0, 2500.0, 3500.0);

		$this->assertEquals('1000', (string) $result['slope_delta_f']);
		$this->assertEquals('17.5', (string) $result['acoustic_length']);
		$this->assertEquals('16.7', (string) $result['anatomical_length']);
		$this->assertTrue($result['is_neutral_vowel']);
		$this->assertEquals('high', $result['confidence']);
	}

	public function testGetAnatomicalVocalTractLengthWithRealData(): void
	{
		$result = Vocal::getAnatomicalVocalTractLength(604.5, 1033.4, 2752.8, 3793.5);

		$this->assertEquals('1128.64', (string) $result['slope_delta_f']);
		$this->assertEquals('15.51', (string) $result['acoustic_length']);
		$this->assertEquals('14.71', (string) $result['anatomical_length']);
		$this->assertFalse($result['is_neutral_vowel']);
		$this->assertEquals('low', $result['confidence']);
	}

	public function testCalculateSlope(): void
	{
		$x = [1, 2, 3, 4];
		$y = [2, 4, 6, 8];
		$this->assertEquals('2', (string) Vocal::calculateSlope($x, $y));

		$x = [0.5, 1.5, 2.5, 3.5];
		$y = [500.0, 1500.0, 2500.0, 3500.0];
		$this->assertEquals('1000', (string) Vocal::calculateSlope($x, $y));
	}

	public function testCalculateSlopeThrowsOnMismatchedArrays(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::calculateSlope([1, 2], [1, 2, 3]);
	}

	public function testCalculateSlopeThrowsOnInsufficientPoints(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::calculateSlope([1], [1]);
	}

	public function testCalculateStandardDeviation(): void
	{
		$values = [2, 4, 4, 4, 5, 5, 7, 9];
		$result = Vocal::calculateStandardDeviation($values);
		$this->assertEquals('2.1380899352994', (string) $result);

		$this->assertEquals('0', (string) Vocal::calculateStandardDeviation([5]));
	}

	public function testCalculateMean(): void
	{
		$this->assertEquals('5', (string) Vocal::calculateMean([2, 4, 6, 8]));
		$this->assertEquals('500', (string) Vocal::calculateMean([500.0, 500.0, 500.0, 500.0]));
	}

	public function testCalculateMeanThrowsOnEmptyArray(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::calculateMean([]);
	}

	public function testEstimateVocalTractLengthFromMultipleSamples(): void
	{
		$samples = [
			[500.0, 1500.0, 2500.0, 3500.0],
			[510.0, 1530.0, 2550.0, 3570.0],
		];
		$result = Vocal::estimateVocalTractLengthFromMultipleSamples($samples);

		$this->assertEquals('high', $result['confidence']);
		$this->assertEquals(2, $result['total_samples']);
		$this->assertEquals(2, $result['neutral_samples_used']);
	}

	public function testEstimateVocalTractLengthFromMultipleSamplesWithNonNeutral(): void
	{
		$samples = [
			[344.4, 894.3, 2728.6, 3533.4],
			[338.2, 1663.0, 2785.6, 3711.9],
		];
		$result = Vocal::estimateVocalTractLengthFromMultipleSamples($samples);

		$this->assertEquals('low', $result['confidence']);
		$this->assertEquals(2, $result['total_samples']);
		$this->assertEquals(0, $result['neutral_samples_used']);
	}

	public function testEstimateVocalTractLengthFromMultipleSamplesThrowsOnEmptyArray(): void
	{
		$this->expectException(InvalidArgumentException::class);
		Vocal::estimateVocalTractLengthFromMultipleSamples([]);
	}

	public function testConstants(): void
	{
		$this->assertEquals(33400.0, Vocal::SPEED_OF_SOUND_ROOM_TEMP);
		$this->assertEquals(35000.0, Vocal::SPEED_OF_SOUND_BODY_TEMP);
		$this->assertEquals(0.3, Vocal::END_CORRECTION_GLOTTAL);
		$this->assertEquals(0.5, Vocal::END_CORRECTION_LIP);
		$this->assertEquals(0.8, Vocal::END_CORRECTION_TOTAL);
		$this->assertEquals(80.0, Vocal::NEUTRAL_VOWEL_PHI_THRESHOLD);
	}

	public function testVTL(): void
	{
		$vtl = new VTL();
		$this->assertEqualsWithDelta(17.957823030654396, $vtl->estimateVTL([344, 894, 2728, 3533], 'regression', true, 'closed-open', 35400, true, 'detailed', false)['vocalTract'], 1e-5);
		$this->assertEqualsWithDelta(15.489758923176296, $vtl->estimateVTL([526, 1094, 2887, 4250], 'regression', true, 'closed-open', 35400, true, 'detailed', false)['vocalTract'], 1e-5);
		$this->assertEqualsWithDelta(16.53138828971069, $vtl->estimateVTL([552, 1042, 2675, 3988], 'regression', true, 'closed-open', 35400, true, 'detailed', false)['vocalTract'], 1e-5);
		$this->assertEqualsWithDelta(15.58033281636417, $vtl->estimateVTL([740, 1159, 2902, 4141], 'regression', true, 'closed-open', 35400, true, 'detailed', false)['vocalTract'], 1e-5);
		$this->assertEqualsWithDelta(16.53138828971069, $vtl->estimateVTL([552, 1042, 2675, 3988], 'regression', true, 'closed-open', 35400, true, 'detailed', false)['vocalTract'], 1e-5);
	}

	public function testEstimateFusionFraction(): void
	{
		// https://gall.dcinside.com/mgallery/board/view/?id=tg74&no=109717&search_pos=672739&s_type=search_comment&s_keyword=%EC%A0%80%EC%9D%8C&page=1&fcno=667901&fpno=667884
		$this->assertEqualsWithDelta(0.5, Vocal::estimateFusionFraction(80, 160)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.25, Vocal::estimateFusionFraction(120, 160)['w'], 1e-5);

		// https://gall.dcinside.com/mgallery/board/view/?id=tg74&no=134581&search_pos=802739&s_type=search_comment&s_keyword=%EC%98%88%EC%86%A1&page=1
		$this->assertEqualsWithDelta(0.666666, Vocal::estimateFusionFraction(96, 288)['w'], 1e-5);

		// https://gall.dcinside.com/mgallery/board/view/?id=tg74&no=136669
		$this->assertEqualsWithDelta(0.368421, Vocal::estimateFusionFraction(120, 190)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.4, Vocal::estimateFusionFraction(120, 200)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.44444444, Vocal::estimateFusionFraction(150, 270)['w'], 1e-5);

		// https://www.reddit.com/r/asktransgender/comments/4wud1v/im_3_years_postop_yeson_voice_feminization/
		$this->assertEqualsWithDelta(0.3333333, Vocal::estimateFusionFraction(100, 150)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.371428, Vocal::estimateFusionFraction(110, 175)['w'], 1e-5);

		// https://www.susans.org/index.php?topic=192899.760
		$this->assertEqualsWithDelta(0.228571, Vocal::estimateFusionFraction(135, 175)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.340909, Vocal::estimateFusionFraction(145, 220)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.3181818, Vocal::estimateFusionFraction(150, 220)['w'], 1e-5);

		// https://www.youtube.com/watch?v=1Ks08BRZ4MM
		$this->assertEqualsWithDelta(0.48888888, Vocal::estimateFusionFraction(138, 270)['w'], 1e-5);

		// https://www.youtube.com/watch?v=3ELY0ft6Vo0
		$this->assertEqualsWithDelta(0.2957746, Vocal::estimateFusionFraction(150, 213)['w'], 1e-5);

		// https://www.youtube.com/watch?v=3bHtOwiuVj0&pp=0gcJCZEKAYcqIYzv
		$this->assertEqualsWithDelta(0.25572519, Vocal::estimateFusionFraction(195, 262)['w'], 1e-5);

		// https://www.youtube.com/watch?v=3fJeX5sUcUE
		$this->assertEqualsWithDelta(0.4433962, Vocal::estimateFusionFraction(118, 212)['w'], 1e-5);

		// https://www.youtube.com/watch?v=44aKAiJbk1A
		$this->assertEqualsWithDelta(0.27027027, Vocal::estimateFusionFraction(162, 222)['w'], 1e-5);

		// https://www.youtube.com/watch?v=6bpGi2SdBJs
		$this->assertEqualsWithDelta(0.29000000, Vocal::estimateFusionFraction(142, 200)['w'], 1e-5);

		// https://www.youtube.com/watch?v=9N-5Rttp6-g
		$this->assertEqualsWithDelta(0.34825870646, Vocal::estimateFusionFraction(131, 201)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.3532608695, Vocal::estimateFusionFraction(119, 184)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.29716981, Vocal::estimateFusionFraction(149, 212)['w'], 1e-5);
		$this->assertEqualsWithDelta(0.48888888888, Vocal::estimateFusionFraction(138, 270)['w'], 1e-5);

		// https://www.youtube.com/watch?v=BrrVwDAFSco
		$this->assertEqualsWithDelta(0.24, Vocal::estimateFusionFraction(190, 250)['w'], 1e-5);

		// https://www.youtube.com/watch?v=C0d754okOMU
		$this->assertEqualsWithDelta(0.42660550458, Vocal::estimateFusionFraction(125, 218)['w'], 1e-5);

		// https://www.youtube.com/watch?v=CvBU9r7k1Bw
		$this->assertEqualsWithDelta(0.339055793, Vocal::estimateFusionFraction(154, 233)['w'], 1e-5);

		// https://www.youtube.com/watch?v=DOeRpR7zgJs
		$this->assertEqualsWithDelta(0.33809523809, Vocal::estimateFusionFraction(139, 210)['w'], 1e-5);

		// https://www.youtube.com/watch?v=HEMGYZOri-g
		$this->assertEqualsWithDelta(0.3564814814, Vocal::estimateFusionFraction(139, 216)['w'], 1e-5);

		// https://www.youtube.com/watch?v=IiZ4qZ97stA&pp=0gcJCZEKAYcqIYzv
		$this->assertEqualsWithDelta(0.5282258064, Vocal::estimateFusionFraction(117, 248)['w'], 1e-5);

		// https://www.youtube.com/watch?v=JZQmHpzeMno
		$this->assertEqualsWithDelta(0.42436974789, Vocal::estimateFusionFraction(137, 238)['w'], 1e-5);

		// https://www.youtube.com/watch?v=ODRwJs8AvXA
		$this->assertEqualsWithDelta(0.5333333333, Vocal::estimateFusionFraction(126, 270)['w'], 1e-5);

		// https://www.youtube.com/watch?v=R9-lj7ZI8ME
		$this->assertEqualsWithDelta(0.21739130434, Vocal::estimateFusionFraction(144, 184)['w'], 1e-5);

		// https://www.youtube.com/watch?v=Rf5DEIiDHY0
		$this->assertEqualsWithDelta(0.301886792452, Vocal::estimateFusionFraction(148, 212)['w'], 1e-5);

		// https://www.youtube.com/watch?v=S4MCq3qQH3g
		$this->assertEqualsWithDelta(0.3350515463, Vocal::estimateFusionFraction(129, 194)['w'], 1e-5);

		// https://www.youtube.com/watch?v=TP3trT4Ogxc
		$this->assertEqualsWithDelta(0.49099099099, Vocal::estimateFusionFraction(113, 222)['w'], 1e-5);

		// https://www.youtube.com/watch?v=UM6KQaIYHiA
		$this->assertEqualsWithDelta(0.354961832061, Vocal::estimateFusionFraction(169, 262)['w'], 1e-5);

		// https://www.youtube.com/watch?v=XO7XRUqAm5c
		$this->assertEqualsWithDelta(0.261111111, Vocal::estimateFusionFraction(133, 180)['w'], 1e-5);

		// https://www.youtube.com/watch?v=XnvMBRfCAQc
		$this->assertEqualsWithDelta(0.2631578947, Vocal::estimateFusionFraction(112, 152)['w'], 1e-5);

		// https://www.youtube.com/watch?v=YMXlhx3OBlY
		$this->assertEqualsWithDelta(0.405982905, Vocal::estimateFusionFraction(139, 234)['w'], 1e-5);

		// https://www.youtube.com/watch?v=frdzTju9NAs
		$this->assertEqualsWithDelta(0.365217391304, Vocal::estimateFusionFraction(146, 230)['w'], 1e-5);

		// https://www.youtube.com/watch?v=jeAvoxczquU
		$this->assertEqualsWithDelta(0.5333333333, Vocal::estimateFusionFraction(126, 270)['w'], 1e-5);

		// https://www.youtube.com/watch?v=niWcrsUUUEo
		$this->assertEqualsWithDelta(0.3532608695, Vocal::estimateFusionFraction(119, 184)['w'], 1e-5);

		// https://www.youtube.com/watch?v=oOfM5Oy6pYM
		$this->assertEqualsWithDelta(0.338164251, Vocal::estimateFusionFraction(137, 207)['w'], 1e-5);

		// https://www.youtube.com/watch?v=rozlJe8OiKc
		$this->assertEqualsWithDelta(0.5211267605, Vocal::estimateFusionFraction(102, 213)['w'], 1e-5);

		// https://www.youtube.com/watch?v=tzoRbMJ6Mfw
		$this->assertEqualsWithDelta(0.32499999999, Vocal::estimateFusionFraction(135, 200)['w'], 1e-5);

		// https://www.youtube.com/watch?v=ypW079iw8ko
		$this->assertEqualsWithDelta(0.4243697478991, Vocal::estimateFusionFraction(137, 238)['w'], 1e-5);

		// https://www.youtube.com/watch?v=zh3DRA5zaro
		$this->assertEqualsWithDelta(0.35885167464, Vocal::estimateFusionFraction(134, 209)['w'], 1e-5);
	}
}
