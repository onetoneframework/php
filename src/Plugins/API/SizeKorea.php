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
use Clover\Classes\Data\URLObject;
use Clover\Plugin\API\PublicDataResponse;
use Clover\Plugin\API\SizeKorea\MeasurementItem;
use Clover\Plugin\API\SizeKorea\SearchItem;

/**
 * SizeKorea API Client
 *
 * Client class for communicating with the SizeKorea (Korean Human Body Size Database) API.
 * Provides functionality for searching body measurement items and retrieving measurement data.
 *
 * SizeKorea is a Korean human body size database operated by the Ministry of Trade, Industry and Energy,
 * providing standard body measurement data used in industrial design for clothing, footwear, safety equipment, etc.
 *
 * @package Clover\Plugin
 * @link https://sizekorea.kr
 */
class SizeKorea
{
    /**
     * Get search item list
     *
     * Searches for available measurement items in the SizeKorea database.
     * Uses different parameters depending on professional mode vs general mode.
     *
     * @param bool $isProfessional Whether to use professional mode (true: professional mode, false: general mode)
     * @param string $bodyPartUppCcd Body part upper code
     *                               - OA: All
     *                               - HD: Head/Neck
     *                               - TK: Torso
     *                               - AM: Hand/Arm
     *                               - LG: Foot/Leg
     * @param string|null $bodyPartCcd Body part detail code (optional)
     *                                Professional mode: Last segment of measurement item code (S-SIa-L-[ER02-ER03]-{DM})
     *                                General mode: Pose type code
     *
     * @return PublicDataResponse<SearchItem> List of searchable measurement items
     * @throws \Exception When API call fails
     *
     * @example
     * ```php
     * $sizeKorea = new SizeKorea();
     * $response = $sizeKorea->getSearchItem(false, 'HD'); // General mode, head/neck area
     * if ($response->isSuccess()) {
     *     $items = $response->getData();
     *     foreach ($items as $item) {
     *         echo $item->getName() . "\n";
     *     }
     * }
     * ```
     */
    public function getSearchItem(bool $isProfessional, string $bodyPartUppCcd, ?string $bodyPartCcd = null): PublicDataResponse
    {
        $queries = [
            $isProfessional ? 'bodyPartUppCcd' : 'measItemTypeCcd' => $bodyPartUppCcd,
            $isProfessional ? 'bodyPartCcd' : 'poseTypeCcd' => $bodyPartCcd
        ];

        $requestUrl = new URLObject('https://sizekorea.kr/human-meas-search/ergo-std/search-item');

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostField($queries)
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack($response->get('payload'));
        $data->setStatusCode($response->result);
        $data->setData(SearchItem::class);

        return $data;
    }

    /**
     * Get measurement item data
     *
     * Searches for body measurement data that matches specified conditions.
     * Can filter by gender, age group, measurement items, etc.
     *
     * @param string $searchMode Search mode
     *                           - NORMAL: Normal search
     *                           - HIGH: High precision search
     * @param string $mode Data mode
     *                     - NORMAL: Standard data
     *                     - HIGH: High precision data
     * @param string $gender Gender
     *                      - M: Male
     *                      - F: Female
     * @param string $agePeriod Age range (e.g., "20-29", "30-39")
     * @param string $measItemCds Measurement item codes (comma-separated string)
     * @param null|int $measDegree Measurement precision (1-5, higher = more precise)
     *
     * @return PublicDataResponse<MeasurementItem> List of measurement data
     * @throws \Exception When API call fails
     *
     * @example
     * ```php
     * $sizeKorea = new SizeKorea();
     * $response = $sizeKorea->getMeasurementItem(
     *     3,           // Measurement precision
     *     'NORMAL',    // Search mode
     *     'NORMAL',    // Data mode
     *     'F',         // Female
     *     '20-29',     // Age 20-29
     *     'HEAD01,HEAD02' // Head circumference, head height
     * );
     *
     * if ($response->isSuccess()) {
     *     $measurements = $response->getData();
     *     foreach ($measurements as $measurement) {
     *         echo "Item: " . $measurement->getItemName() . "\n";
     *         echo "Mean: " . $measurement->getMean() . " cm\n";
     *         echo "Std Dev: " . $measurement->getStdDev() . " cm\n";
     *     }
     * }
     * ```
     */
    public function getMeasurementItem(string $searchMode, string $mode, string $gender, string $agePeriod, string $measItemCds, ?int $measDegree = null): PublicDataResponse
    {
        $queries = [
            'measDegree' => $measDegree,
            'searchMode' => $searchMode,
            'mode' => $mode,
            'gender' => $gender,
            'agePeriod' => $agePeriod,
            'measItemCds' => $measItemCds,
        ];

        $requestUrl = new URLObject('https://sizekorea.kr/human-meas-search/human-data-search/meas-item');

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostField($queries)
            ->setPostMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack($response->get('payload'));
        $data->setStatusCode($response->result);
        $data->setData(MeasurementItem::class);

        return $data;
    }
}
