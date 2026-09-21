<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\ClientURL;
use Clover\Plugin\API\JapanMeteorologicalAgency\AreaConstraint;
use Clover\Plugin\API\JapanMeteorologicalAgency\ForestDataCollection;
use Clover\Plugin\API\PublicDataResponse;

/**
 * JapanMeteorologicalAgency class handles data retrieval from Japan's Meteorological Agency.
 * This class provides methods to fetch weather forecast data, area constraints,
 * and forest fire danger information from JMA's public APIs.
 */
class JapanMeteorologicalAgency
{

    /**
     * Retrieves area constraints data from JMA.
     * Gets geographical area definitions and constraints for weather forecasting.
     *
     * @return PublicDataResponse The response containing area constraint data.
     */
    public function getAreaConstraints(): PublicDataResponse
    {
        $requestUrl = "https://www.jma.go.jp/bosai/common/const/area.json";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response['centers']);
        $data->setData(AreaConstraint::class);

        return $data;
    }

    /**
     * Retrieves forest fire danger data for a specific area.
     * Gets weather forecast data including forest fire danger information.
     *
     * @param string $area The area code for the forecast data.
     * @return PublicDataResponse The response containing forest data.
     */
    public function getForestData(string $area): PublicDataResponse
    {
        $requestUrl = "https://www.jma.go.jp/bosai/forecast/data/forecast/{$area}.json";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response[0]->timeSeries);
        $data->setData(ForestDataCollection::class);

        return $data;
    }
}