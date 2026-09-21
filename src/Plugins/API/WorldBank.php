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
use Clover\Enumeration\ISO2Code;
use Clover\Enumeration\ISO639Code;
use Clover\Plugin\API\PublicDataResponse;

class WorldBank
{
    /**
     * Summary of getSpecifyCountry
     * @param mixed $language language code (ISO639Code)
     * @param mixed $locale locale code (ISO2Code)
     * 
     * @return PublicDataResponse
     */
    public function getSpecifyCountry(ISO639Code $language = 'en', ISO2Code $locale = 'us'): mixed
    {
        $requestUrl = "https://api.worldbank.org/v2/{$language->value}/country/{$locale->value}?format=json";

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }
}