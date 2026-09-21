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
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\URLObject;
use Clover\Plugin\API\PublicDataResponse;
use Clover\Plugin\API\SuperRich\BranchInfo;
use Clover\Plugin\API\SuperRich\ExchangeRateCollection;
use function sprintf;

final class SuperRich
{
    public const DEFAULT_BASE_URL = 'https://www.superrichthailand.com/web/api/v1/rates';
    public const AUTHORIZE_KEY = "c3VwZXJyaWNoVGg6aFRoY2lycmVwdXM=";

    /**
     * Fetch all exchange rates and return a typed PublicDataResponse
     * mapped to ExchangeRateCollection entries.
     */
    public function getExchangeRates(): PublicDataResponse
    {
        $decoded = $this->request();

        $data = new PublicDataResponse();
        $data->setStack($decoded->get('data')->get('exchangeRate') ?? []);
        $data->setStatusCode($decoded->get('code') ?? 0);
        $data->setStatusMessage($decoded->get('descriptionEn') ?? '');
        $data->setData(ExchangeRateCollection::class);

        return $data;
    }

    /**
     * Fetch all branch information and return a typed PublicDataResponse
     * mapped to BranchInfo entries.
     */
    public function getBranches(): PublicDataResponse
    {
        /**
         * @var ArrayObject $decoded
         */
        $decoded = $this->request();

        $data = new PublicDataResponse();
        $data->setStack($decoded->get('data')->get('branch') ?? []);
        $data->setStatusCode($decoded->get('code') ?? 0);
        $data->setStatusMessage($decoded->get('descriptionEn') ?? '');
        $data->setData(BranchInfo::class);

        return $data;
    }

    /**
     * Return the list of popular currency codes (e.g. ["USD", "JPY", ...]).
     *
     * @return string[]
     */
    public function getPopularRateCodes(): array
    {
        $decoded = $this->request();

        return array_map(static fn($v) => strtoupper((string) $v), (array) ($decoded->data->popularRate ?? []));
    }

    /**
     * Execute the HTTP request and return the decoded response body.
     */
    private function request(): mixed
    {
        $requestUrl = new URLObject(self::DEFAULT_BASE_URL);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setHeader("authorization", sprintf("Basic %s", self::AUTHORIZE_KEY))
            ->setGetMethod()
            ->setFollowRedirects(true);

        return $cURL->executeWithDecode();
    }
}
