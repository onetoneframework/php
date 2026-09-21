<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Audio;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Audio\Mp3Parser;
use PHPUnit\Framework\TestCase;

class Mp3ParserTest extends TestCase
{

    public function setUp(): void
    {
    }

    public function testMp3Parse(): void
    {
        $tags = Mp3Parser::getMetaTags(__DIR__."/crankin-shortver.mp3");

		$this->assertEquals("Lavf61.7.100", $tags['meta']['TSSE']['value']);
		$this->assertEquals("辰巳 深空（CV.夏和小）", $tags['meta']['TPE1']['value']);
		$this->assertEquals("クランクイン！", $tags['meta']['TIT2']['value']);
		$this->assertEquals("ANZIE", $tags['meta']['TCOM']['value']);
		$this->assertEquals("177", $tags['meta']['TBPM']['value']);
		$this->assertEquals("やりなおしクランクイン", $tags['meta']['TALB']['value']);
		$this->assertEquals("1:49", $tags['meta']['duration_human']);
		$this->assertEquals("48000", $tags['meta']['sample_rate']);
		$this->assertEquals("192000", $tags['meta']['bitrate']);
		$this->assertEquals("Stereo", $tags['meta']['mode']);
		$this->assertEquals("Jpop", $tags['meta']['TCON']['genre']);
		$this->assertEquals("Groovy", $tags['meta']['TMOO']['value']);

        $tags = Mp3Parser::getMetaTags(__DIR__."/lmd.mp3");

		$this->assertEquals("鈴森芽衣（CV.夏和小）", $tags['meta']['TPE1']['value']);
		$this->assertEquals("L.M.D！", $tags['meta']['TIT2']['value']);
		$this->assertEquals("鈴木ぷよ", $tags['meta']['TCOM']['value']);
		$this->assertEquals("こあくまちゃんの誘惑っ！", $tags['meta']['TALB']['value']);
		$this->assertEquals("1:42", $tags['meta']['duration_human']);
		$this->assertEquals("48000", $tags['meta']['sample_rate']);
		$this->assertEquals("192000", $tags['meta']['bitrate']);
		$this->assertEquals("Joint stereo", $tags['meta']['mode']);
    }
}
