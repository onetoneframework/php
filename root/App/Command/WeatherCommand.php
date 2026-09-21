<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\CLI\InputArgument;
use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputOption;
use Clover\Plugin\API\KoreaPublicData\NearMeasurmentStationList;
use Clover\Plugin\API\KoreaPublicData\MeasurmentStationList;
use Clover\Plugin\KakaoTalk;
use Clover\Plugin\KoreaPublicData;
use Exception;

class WeatherCommand implements CommandInterface
{
    public array $arguments = [];
    public array $options = [];

    public function getName(): string
    {
        return 'weather:status';
    }

    public function getDescription(): string
    {
        return 'Fetch nearby air-quality station data from Korean public APIs';
    }

    public function configure(): void
    {
        $this->arguments[] = new InputOption('command', 'command', 'get:route');
        $this->options[] = new InputArgument('id', 'id', '196015867');
    }

    public function run(Input $input): bool
    {
        $kakao = new KakaoTalk($_ENV['KAKAO_RESTFUL_API_KEY']);
        $tm = $kakao->transCoord(129.3113596, 35.5383773);

        $tm = $tm['documents'][0];
        $x = $tm['x'];
        $y = $tm['y'];

        $kpd = new KoreaPublicData($_ENV['KOREA_PUBLIC_DATA_API_KEY']);
        $vf = $kpd->getNearMeasurementStationList($x, $y);
        if (!$vf->hasData()) {
            throw new Exception("Failed to fetch nearby measurement stations");
        }

        /** @var NearMeasurmentStationList $item */
        $item = $vf->getData()[0];
        $measurementStationList = $kpd->getMeasurementStationList($item->address, $item->stationName);
        if (!$measurementStationList->hasData()) {
            throw new Exception("Failed to fetch measurement station list for station " . $item->stationName);
        }

        /** @var MeasurmentStationList $item */
        $item = $measurementStationList->getData()[0];

        $ultraShorttermNowcast = $kpd->getUltraShorttermNowcast($kpd->getBaseDate(), '0600', $item->x, $item->y);
        if (!$ultraShorttermNowcast->hasData()) {
            throw new Exception("Failed to fetch ultra short-term nowcast data for station " . $item->stationName);
        }
        $ultraShorttermNowcast->getData()->map(function ($data) {
            echo ($data->getSummaryText()) . PHP_EOL;
        });

        $ultraShorttermForecast = $kpd->getUltraShorttermForecast($kpd->getBaseDate(), '0630', $item->x, $item->y);
        if (!$ultraShorttermForecast->hasData()) {
            throw new Exception("Failed to fetch ultra short-term forecast data for station " . $item->stationName);
        }
        $ultraShorttermForecast->getData()->map(function ($data) {
            echo ($data->getSummaryText()) . PHP_EOL;
        });

        return true;
    }
}
