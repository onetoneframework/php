<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Chord;

use Clover\Classes\Chord\Tempo;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TempoTest extends TestCase
{
	public function testBeatBarAndNoteValueDurationsUseCurrentBpm(): void
	{
		$tempo = new Tempo(120);

		$this->assertSame(120, $tempo->getBPM());
		$this->assertSame(0.5, $tempo->getBeatDuration());
		$this->assertSame(2.0, $tempo->getBarDuration(4));
		$this->assertSame(30.0, $tempo->getTempoInNoteValue('quarter'));

		$tempo->setBPM(60);
		$this->assertSame(60, $tempo->getBPM());
		$this->assertSame(1.0, $tempo->getBeatDuration());
	}

	#[DataProvider('tempoCategoryProvider')]
	public function testTempoCategoriesAtBoundaries(int $bpm, string $category): void
	{
		$this->assertSame($category, (new Tempo($bpm))->getTempoCategory());
	}

	public static function tempoCategoryProvider(): array
	{
		return [
			'below 60' => [59, 'Largo'],
			'60' => [60, 'Adagio'],
			'76' => [76, 'Andante'],
			'108' => [108, 'Moderato'],
			'120' => [120, 'Allegro'],
			'168' => [168, 'Presto'],
			'200' => [200, 'Prestissimo'],
		];
	}

	public function testInvalidNoteValueIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid note value: dotted-quarter');

		(new Tempo())->getTempoInNoteValue('dotted-quarter');
	}
}
