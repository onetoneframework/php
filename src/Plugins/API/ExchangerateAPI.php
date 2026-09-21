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
use function sprintf;

final class ExchangerateAPI
{
    public const DEFAULT_BASE_URL = 'https://open.er-api.com/v6/latest';

    public function getExchangeRates(?string $region = null)
    {
        $url = $region ? sprintf("%s/%s", self::DEFAULT_BASE_URL, $region) : self::DEFAULT_BASE_URL;

        $requestUrl = new URLObject($url);
        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod()
            ->setFollowRedirects(true);

        $decoded = $cURL->executeWithDecode();

        return $decoded;
    }
}
