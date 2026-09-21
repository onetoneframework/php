<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputOption;
use Clover\Classes\System\Output;
use Clover\Enumeration\Currency;
use Clover\Implement\CommandInterface;
use Clover\Plugin\API\Frankfurter\ExchangeRateQuote;
use Clover\Plugin\API\Frankfurter\FrankfurterFetchResult;
use Clover\Plugin\Frankfurter;
use Throwable;
use function preg_match;
use function sprintf;
use function strtoupper;
use function trim;
use function is_string;

/**
 * CLI: fetch ECB-based foreign-exchange rates (Frankfurter, no API key).
 */
final class ExchangeRateCommand implements CommandInterface
{
    public array $arguments = [];

    public array $options = [];

    /**
     * Get the name of the command.
     */
    public function getName(): string
    {
        return 'exchange:rates';
    }

    /**
     * Get a short description of the command.
     */
    public function getDescription(): string
    {
        return 'Fetch or convert exchange rates via Frankfurter (free JSON API).';
    }

    /**
     * Configure the command's options.
     */
    public function configure(): void
    {
        $this->options[] = new InputOption('from', 'Base currency ISO code', 'USD');
        $this->options[] = new InputOption('to', 'Comma-separated quote currencies (omit for all supported)', null);
        $this->options[] = new InputOption('date', 'Historical rate date YYYY-MM-DD (optional)', null);
        $this->options[] = new InputOption('api', 'Frankfurter API root URL', Frankfurter::DEFAULT_BASE_URL);
        $this->options[] = new InputOption('url', 'Full GET URL (Frankfurter-compatible JSON; overrides from/to/date/api)', null);
        $this->options[] = new InputOption('convert', 'Convert an amount, e.g. 1THB->USD or "12.5 EUR -> USD" (not with --url)', null);
    }

    /**
     * Run the command with the given input and print results to output.
     * 
     * @return bool success
     */
    public function run(Input $input): bool
    {
        $convert = $input->getOption('convert');
        if (is_string($convert) && trim($convert) !== '') {
            return $this->runConvert($input, trim($convert));
        }

        try {
            $result = $this->resolveFetch($input);
        } catch (Throwable $e) {
            Output::printLine('Error: ' . $e->getMessage());

            return false;
        }

        $ratesResponse = $result->getRates();
        $http = $result->getHttpStatus();
        Output::printLine(sprintf('HTTP %d | base=%s | date=%s | amount=%s', $http, $result->getBaseCurrency(), $result->getRateDate(), $this->formatAmount($result->getAmount())));

        if ($http < 200 || $http >= 300) {
            Output::printLine('Non-success HTTP status; body may be incomplete.');
        }

        $rows = $ratesResponse->getData();
        if ($rows === []) {
            Output::printLine('No rate rows returned (check --from / --to / --url).');

            return $http >= 200 && $http < 300;
        }

        $lines = [];
        foreach ($rows as $row) {
            if (!$row instanceof ExchangeRateQuote) {
                continue;
            }
            $lines[$row->getCurrencyCode()] = sprintf('  %s  %s', $row->getCurrencyCode(), $this->formatRate($row->getRate()));
        }
        ksort($lines, SORT_STRING);
        foreach ($lines as $line) {
            Output::printLine($line);
        }

        return true;
    }

    /**
     * Parse a conversion expression like "1.5 USD->EUR" or "10 EUR -> USD".
     *
     * @return array{0: float, 1: string, 2: string}|null  amount, from ISO, to ISO (uppercase)
     */
    private function parseConvertExpression(string $expression): ?array
    {
        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*([A-Za-z]{3})\s*(?:->|=>|→)\s*([A-Za-z]{3})\s*$/u', $expression, $m) !== 1) {
            return null;
        }

        return [(float) $m[1], strtoupper($m[2]), strtoupper($m[3])];
    }

    /**
     * Run a conversion based on a parsed expression and print the result.
     *
     * @param Input $input
     * @param string $expression the raw conversion expression (e.g. "1.5 USD->EUR")
     * @return bool success
     */
    private function runConvert(Input $input, string $expression): bool
    {
        $url = $input->getOption('url');
        if (is_string($url) && $url !== '') {
            Output::printLine('--convert cannot be used together with --url.');

            return false;
        }

        $parsed = $this->parseConvertExpression($expression);
        if ($parsed === null) {
            Output::printLine('Invalid --convert. Examples: 1THB->USD   10.5 EUR -> USD');

            return false;
        }

        [$amount, $from, $to] = $parsed;
        if ($from === $to) {
            Output::printLine(sprintf('%s %s = %s %s', $this->formatRate($amount), $from, $this->formatRate($amount), $to));

            return true;
        }

        try {
            $client = $this->makeFrankfurterClient($input);
            $date = $input->getOption('date');
            $result = is_string($date) && $date !== '' ? $client->getForDate($date, $from, $to) : $client->getLatest($from, $to);
        } catch (Throwable $e) {
            Output::printLine('Error: ' . $e->getMessage());

            return false;
        }

        $http = $result->getHttpStatus();
        $rate = $this->findRate($result, $to);
        if ($rate === null) {
            Output::printLine(sprintf('No rate for %s (HTTP %d, base=%s, date=%s).', $to, $http, $result->getBaseCurrency(), $result->getRateDate()));

            return false;
        }

        $converted = $amount * $rate;
        Output::printLine(sprintf('%s %s = %s %s  (1 %s = %s %s, as of %s, HTTP %d)', $this->formatRate($amount), $from, $this->formatRate($converted), $to, $from, $this->formatRate($rate), $to, $result->getRateDate(), $http));

        return $http >= 200 && $http < 300;
    }

    /**
     * Find the exchange rate for a specific quote currency in the fetch result.
     *
     * @param FrankfurterFetchResult $result
     * @param string $currencyCode ISO currency code to find (case-insensitive)
     * @return float|null the exchange rate, or null if not found
     */
    private function findRate(FrankfurterFetchResult $result, string $currencyCode): ?float
    {
        $want = strtoupper($currencyCode);
        foreach ($result->getRates()->getData() as $row) {
            if ($row instanceof ExchangeRateQuote && strtoupper($row->getCurrencyCode()) === $want) {
                return $row->getRate();
            }
        }

        return null;
    }

    /**
     * Create a Frankfurter API client based on input options.
     * @param Input $input
     * @return Frankfurter
     * @throws Throwable on error
     */
    private function makeFrankfurterClient(Input $input): Frankfurter
    {
        $api = $input->getOption('api');
        $apiRoot = is_string($api) && $api !== '' ? $api : Frankfurter::DEFAULT_BASE_URL;

        return new Frankfurter($apiRoot);
    }

    /**
     * Resolve the fetch operation based on input options and return the result.
     *
     * @return FrankfurterFetchResult
     * @throws Throwable on error
     */
    private function resolveFetch(Input $input): FrankfurterFetchResult
    {
        $url = $input->getOption('url');
        if (is_string($url) && $url !== '') {
            return (new Frankfurter())->fetchFromUrl($url);
        }

        $from = (string) ($input->getOption('from') ?? Currency::USD->value);
        $to = $input->getOption('to');
        $toCsv = is_string($to) && $to !== '' ? $to : null;
        $date = $input->getOption('date');

        $client = $this->makeFrankfurterClient($input);
        if (is_string($date) && $date !== '') {
            return $client->getForDate($date, $from, $toCsv);
        }

        return $client->getLatest($from, $toCsv);
    }

    private function formatNumber(float $value): string
    {
        $trimmed = rtrim(rtrim(sprintf('%.8F', $value), '0'), '.');

        $parts = explode('.', $trimmed);
        $intPart = number_format((int) $parts[0]);
        $decPart = $parts[1] ?? null;

        return $decPart ? "{$intPart}.{$decPart}" : $intPart;
    }

    private function formatRate(float $rate): string
    {
        return $this->formatNumber($rate);
    }

    private function formatAmount(float $amount): string
    {
        return $this->formatNumber($amount);
    }
}
