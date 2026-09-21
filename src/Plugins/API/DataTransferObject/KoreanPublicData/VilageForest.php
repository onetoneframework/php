<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\KoreaPublicData;

use Clover\Plugin\API\PublicDataInterface;

class VilageForest implements PublicDataInterface
{
    private ?string $baseDate;
    private ?string $baseTime;
    private ?string $category;
    private ?int $nx;
    private ?int $ny;
    private ?string $forestDate;
    private ?string $forestTime;
    private ?string $forestValue;

    public function __construct($baseDate, $baseTime, $category, $nx, $ny, $fcstDate, $fcstTime, $fcstValue)
    {
        $this->baseDate = $baseDate;
        $this->baseTime = $baseTime;
        $this->category = $category;
        $this->nx = $nx;
        $this->ny = $ny;
        $this->forestDate = $fcstDate;
        $this->forestTime = $fcstTime;
        $this->forestValue = $fcstValue;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self($data->baseDate, $data->baseTime, $data->category, $data->nx, $data->ny, $data->fcstDate, $data->fcstTime, $data->fcstValue);
    }

    private function parsePTYValue() 
    {
        switch ($this->forestValue) {
            case 0:
                return '없음';
            case 1:
                return '비';
            case 2:
                return '비/눈';
            case 3:
                return '눈';
            case 5:
                return '빗방울';
            case 6:
                return '빗방울/눈날림';
            case 7:
                return '눈날림';
            default:
                return '알수없음';
        }
    }

    private function parseSkyValue()
    {
        switch ($this->forestValue) {
            case 1:
                return '맑음';
            case 3:
                return '구름많음';
            case 4:
                return '흐림';
            default:
                return '알수없음';
        }
    }

    public function getState()
    {
        switch ($this->category) {
            case 'SKY':
                return $this->parseSkyValue();
            case 'PTY':
                return $this->parsePTYValue();
            case 'T1H':
                return $this->forestValue;
                // Humidity
            case 'REH':
                return $this->forestValue;
            case 'WSD':
                return $this->forestValue;
            case 'VVV':
                return $this->forestValue;
            case 'VEC':
                return $this->forestValue;
            case 'UUU':
                return $this->forestValue;
            case 'RN1':
                return $this->forestValue;
            default:
                return $this->forestValue;
        }
    }

    public function getStateCategoryName()
    {
        switch ($this->category) {
            default:
                return null;
            case 'SKY':
                return '하늘상태';
            case 'PTY':
                return '강수형태';
            case 'T1H':
                return '기온';
            case 'REH':
                return '습도';
            case 'WSD':
                return '풍속';
            case 'VVV':
                return '남북바람성분';
            case 'VEC':
                return '풍향';
            case 'UUU':
                return '동서바람성분';
            case 'RN1':
                return '1시간 강수량';
            case 'LGT':
                return '낙뢰';
        }
    }

    public function getSummaryText(): string
    {
        return sprintf("%s %s 기준으로 '%s'의 값은 '%s'입니다.", $this->forestDate, $this->forestTime, $this->getStateCategoryName(), $this->getState());
    }
}
