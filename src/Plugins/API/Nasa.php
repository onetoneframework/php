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
use Datetime;

class Nasa
{

    private string $API_KEY;

    public function __construct(string $key)
    {
        $this->API_KEY = $key;
    }

    /**
     * Get Astronomy Picture Of The Day
     * @param string|null $date The date of the APOD image to retrieve
     * @param string|null $startDate The start of a date range, when requesting date for a range of dates. Cannot be used with date.
     * @param string|null $endDate The end of the date range, when used with start_date.
     * @param int|null $count If this is specified then count randomly chosen images will be returned. Cannot be used with date or start_date and end_date.
     * @param bool $isThumbnails Return the URL of video thumbnail. If an APOD is not a video, this parameter is ignored.
     * 
     * @return mixed
     */
    public function getAstronomyPictureOfTheDay(?string $date = null, ?string $startDate = null, ?string $endDate = null, ?int $count = null, bool $isThumbnails = false): mixed
    {
        if ($date === null && $startDate === null && $endDate === null) {
            $date = date_format(new Datetime(), "Y-m-d");
        }

        $queries = [
            'api_key' => $this->API_KEY,
            'date' => $date,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'startDate' => $startDate,
            'count' => $count,
            'thumbs' => $isThumbnails,
        ];

        $requestUrl = new URLObject("https://api.nasa.gov/planetary/apod");
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();
        $response = json_decode($response, true);

        return $response;
    }

}