<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Chord;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Chord\Chord;
use Clover\Classes\Chord\Interval;
use Clover\Classes\Chord\Scale;
use Clover\Classes\Chord\Tension;
use Clover\Classes\Chord\Tone;
use Clover\Enumeration\MusicChordType;
use Clover\Enumeration\MusicScaleType;
use PHPUnit\Framework\TestCase;

class ChordTest extends TestCase
{

    public function setUp(): void
    {
    }

    public function testChord(): void
    {
        $chord = new Chord(0, "major", 0); // C major chord in c key

        $tones = $chord->getYamahaTones();
        $this->assertEquals('CEG', join($tones));
        
        $tones = $chord->getChordTones();
        $this->assertEquals('047', join($tones));

        $tension = new Tension(0, MusicChordType::MAJOR_7TH);
        $tones = new Tone($tension->getAvailableTensions());
        $tones = $tones->getYamahaTones();
        $this->assertEquals('DF#A', join($tones));
        
        $tension = new Tension(0, MusicChordType::MINOR_7TH);
        $tones = new Tone($tension->getAvailableTensions());
        $tones = $tones->getYamahaTones();
        $this->assertEquals('DFA', join($tones));

        $tension = new Tension(0, MusicChordType::DIMINISHED_7TH);
        $tones = new Tone($tension->getAvailableTensions());
        $tones = $tones->getYamahaTones();
        $this->assertEquals('C#DD#F#G#A', join($tones));
        
        $tension = new Tension(0, MusicChordType::MINOR_7TH_FLAT_5);
        $tones = new Tone($tension->getAvailableTensions());
        $tones = $tones->getYamahaTones();
        $this->assertEquals('C#DF', join($tones));

        $scale = new Scale(0);
        $tones = new Tone($scale->getScale());
        $tones = $tones->getYamahaTones();
        $this->assertEquals('CDEFGAB', join($tones));

        $scale = new Scale(0, MusicScaleType::PENTATONIC);
        $tones = new Tone($scale->getScale());
        $tones = $tones->getYamahaTones();
        $this->assertEquals('CDEGA', join($tones));

        $interval = new Interval(0, 10);
        $this->assertEquals('792.0', $interval->getHertz());

        $chord = new Chord(8, "french6", 0); // Ab
        $tones = $chord->getYamahaTones();
        $this->assertEquals('G#CDF#', join($tones));
        
        $chord = new Chord(8, "german6", 0); // Ab
        $tones = $chord->getYamahaTones();
        $this->assertEquals('G#CD#F#', join($tones));
        
        $chord = new Chord(8, "italian6", 0); // Ab
        $tones = $chord->getYamahaTones();
        $this->assertEquals('G#CF#', join($tones));
        
        $chord = new Chord(5, "tristan", 0); // F
        $tones = $chord->getYamahaTones();
        $this->assertEquals('FBD#G#', join($tones));
        
        $chord = new Chord(0, "mystic", 0); // C
        $tones = $chord->getYamahaTones();
        $this->assertEquals('CF#A#EAD', join($tones));
        
        $chord = new Chord(0, "synthetic", 0); // C
        $tones = $chord->getYamahaTones();
        $this->assertEquals('CFB', join($tones));

        $chord = new Chord(0, "dominant7", 0); // C
        $tones = $chord->getYamahaTones();
        $this->assertEquals('CEGA#', join($tones));
        
        $chord = new Chord(0, "minormajor7", 0); // C
        $tones = $chord->getYamahaTones();
        $this->assertEquals('CD#GB', join($tones));
        
        $chord = new Chord(0, "dominant7b5", 0); // C
        $tones = $chord->getYamahaTones();
        $this->assertEquals('CEF#A#', join($tones));

        $chord = new Chord(0, "dominant13", 0); // C
        $tones = $chord->getYamahaTones();
        $this->assertEquals('CEGA#DFA', join($tones));
    }
}
