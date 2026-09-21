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

class FinancialModelingPrep
{
    private $API_KEY;

    public function __construct(string $apiKey)
    {
        $this->API_KEY = $apiKey;
    }

    /**
     * Easily find the ticker symbol of any stock with the FMP Stock Symbol Search API. Search by company name or symbol across multiple global markets.
     * 
     * @return mixed
     */
    public function searchStockSymbol(): mixed
    {
        $queries = [
            'apikey' => $this->API_KEY,
            'query' => 'AAPL'
        ];

        $requestUrl = new URLObject('https://financialmodelingprep.com/stable/search-symbol');
        $requestUrl->setQueryString($queries);

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

    /**
     * Search for ticker symbols, company names, and exchange details for equity securities and ETFs listed on various exchanges with the FMP Name Search API. This endpoint is useful for retrieving ticker symbols when you know the full or partial company or asset name but not the symbol identifier.
     * 
     * @return mixed
     */
    public function searchCompanyName(): mixed
    {
        $queries = [
            'apikey' => $this->API_KEY,
            'query' => 'AA'
        ];

        $requestUrl = new URLObject('https://financialmodelingprep.com/stable/search-name');
        $requestUrl->setQueryString($queries);

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

    /**
     * Easily retrieve the Central Index Key (CIK) for publicly traded companies with the FMP CIK API. Access unique identifiers needed for SEC filings and regulatory documents for a streamlined compliance and financial analysis process.
     * 
     * @param mixed $cik
     * 
     * @return mixed
     */
    public function getCentralIndexKey($cik): mixed
    {
        $queries = [
            'apikey' => $this->API_KEY,
            'cik' => $cik
        ];

        $requestUrl = new URLObject('https://financialmodelingprep.com/stable/search-cik');
        $requestUrl->setQueryString($queries);

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

    /**
     * Easily search and retrieve financial securities information by CUSIP number using the FMP CUSIP API. Find key details such as company name, stock symbol, and market capitalization associated with the CUSIP.
     * 
     * @param mixed $cusip
     * 
     * @return mixed
     */
    public function getCUSIPNumber($cusip): mixed
    {
        $queries = [
            'apikey' => $this->API_KEY,
            'cik' => $cusip
        ];

        $requestUrl = new URLObject('https://financialmodelingprep.com/stable/search-cusip');
        $requestUrl->setQueryString($queries);

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

    /**
     * Easily search and retrieve the International Securities Identification Number (ISIN) for financial securities using the FMP ISIN API. Find key details such as company name, stock symbol, and market capitalization associated with the ISIN.
     * 
     * @param string $isin
     * 
     * @return mixed
     */
    public function getInternationalSecuritiesIdentificationNumber(string $isin): mixed
    {
        $queries = [
            'apikey' => $this->API_KEY,
            'isin' => $isin
        ];

        $requestUrl = new URLObject('https://financialmodelingprep.com/stable/search-isin');
        $requestUrl->setQueryString($queries);

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

    /**
     * Discover stocks that align with your investment strategy using the FMP Stock Screener API. Filter stocks based on market cap, price, volume, beta, sector, country, and more to identify the best opportunities.
     * 
     * @param mixed $marketCapMoreThan
     * @param mixed $marketCapLowerThan
     * @param mixed $sector
     * @param mixed $industry
     * @param mixed $betaMoreThan
     * @param mixed $betaLowerThan
     * @param mixed $priceMoreThan
     * @param mixed $priceLowerThan
     * @param mixed $dividendMoreThan
     * @param mixed $dividendLowerThan
     * @param mixed $volumeMoreThan
     * @param mixed $volumeLowerThan
     * @param mixed $exchange
     * @param mixed $country
     * @param mixed $isEtf
     * @param mixed $isFund
     * @param mixed $isActivelyTrading
     * @param mixed $limit
     * @param mixed $includeAllShareClasses
     * 
     * @return mixed
     */
    public function callCompanyScreener(?int $marketCapMoreThan, ?int $marketCapLowerThan, ?string $sector, ?string $industry, ?int $betaMoreThan, ?int $betaLowerThan, ?int $priceMoreThan, ?int $priceLowerThan, ?int $dividendMoreThan, ?int $dividendLowerThan, ?int $volumeMoreThan, ?int $volumeLowerThan, ?string $exchange, ?string $country, ?bool $isEtf, ?bool $isFund, ?bool $isActivelyTrading, ?int $limit, ?bool $includeAllShareClasses): mixed
    {
        $queries = [
            'apikey' => $this->API_KEY,
            'marketCapMoreThan' => $marketCapMoreThan,
            'marketCapLowerThan' => $marketCapLowerThan,
            'sector' => $sector,
            'industry' => $industry,
            'betaMoreThan' => $betaMoreThan,
            'betaLowerThan' => $betaLowerThan,
            'priceMoreThan' => $priceMoreThan,
            'priceLowerThan' => $priceLowerThan,
            'dividendMoreThan' => $dividendMoreThan,
            'dividendLowerThan' => $dividendLowerThan,
            'volumeMoreThan' => $volumeMoreThan,
            'volumeLowerThan' => $volumeLowerThan,
            'exchange' => $exchange,
            'country' => $country,
            'isEtf' => $isEtf,
            'isFund' => $isFund,
            'isActivelyTrading' => $isActivelyTrading,
            'limit' => $limit,
            'includeAllShareClasses' => $includeAllShareClasses,
        ];

        $requestUrl = new URLObject('https://financialmodelingprep.com/stable/company-screener');
        $requestUrl->setQueryString($queries);

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