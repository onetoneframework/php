<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use function count;

class Credit
{
    /**
     * Calculate Default Rate (%)
     * 
     * @return float|int
     */
    public static function getDefaultRate($defaultedFirms, $normalFirms1yAgo): float|int
    {
        if ($normalFirms1yAgo == 0) {
            return 0;
        }

        return ($defaultedFirms / $normalFirms1yAgo) * 100;
    }

    /**
     * Calculate Delinquency Rate (%)
     * 
     * @return float|int
     */
    public static function getDelinquencyRate($delinquentFirms, $normalFirmsNow): float|int
    {
        $denominator = $normalFirmsNow + $delinquentFirms;
        if ($denominator == 0) {
            return 0;
        }

        return ($delinquentFirms / $denominator) * 100;
    }

    /**
     * Calculate Closure Rate (%)
     * 
     * @return float|int
     */
    public static function getClosureRate($closedFirms, $normalFirms1yAgo): float|int
    {
        if ($normalFirms1yAgo == 0) {
            return 0;
        }

        return ($closedFirms / $normalFirms1yAgo) * 100;
    }

    /**
     * Check if company is high risk based on thresholds
     * 
     * @return bool
     */
    public static function isHighRisk($defaultRate, $delinquencyRate, $closureRate): bool
    {
        $defaultThreshold = 5.0;
        $delinquencyThreshold = 15.0;
        $closureThreshold = 3.0;

        return (
            $defaultRate >= $defaultThreshold ||
            $delinquencyRate >= $delinquencyThreshold ||
            $closureRate >= $closureThreshold
        );
    }

    /**
     * Calculate risk score (0 ~ 100)
     * 
     * @return float|int
     */
    public static function getRiskScore($defaultRate, $delinquencyRate, $closureRate): float|int
    {
        $score = ($defaultRate * 0.5) + ($delinquencyRate * 0.3) + ($closureRate * 0.2);

        return min($score, 100);
    }

    /**
     * Calculate financial risk indicators
     * 
     * @param int|float $assets
     * @param int|float $liabilities
     * @param int|float $currentAssets
     * @param int|float $currentLiabilities
     * @param int|float $ebit
     * @param int|float $interestExpense
     * 
     * @return array{debtRatio: float|int, currentRatio: float|int, interestCoverage: float|int, warnings: string[]}
     */
    public static function getFinancialRisk(int|float $assets, int|float $liabilities, int|float $currentAssets, int|float $currentLiabilities, int|float $ebit, int|float $interestExpense): array
    {
        // Debt ratio = Liabilities / Assets * 100
        $debtRatio = ($assets == 0) ? 0 : ($liabilities / $assets) * 100;

        // Current ratio = Current Assets / Current Liabilities * 100
        $currentRatio = ($currentLiabilities == 0) ? 0 : ($currentAssets / $currentLiabilities) * 100;

        // Interest coverage ratio = EBIT / Interest Expense
        $interestCoverage = ($interestExpense == 0) ? INF : ($ebit / $interestExpense);

        // Risk flags based on thresholds
        $riskFlags = [];
        if ($debtRatio > 200) {
            $riskFlags[] = "Excessive debt ratio";
        }
        if ($currentRatio < 100) {
            $riskFlags[] = "Liquidity shortage";
        }
        if ($interestCoverage < 1) {
            $riskFlags[] = "Insufficient interest coverage";
        }

        return [
            "debtRatio" => $debtRatio,
            "currentRatio" => $currentRatio,
            "interestCoverage" => $interestCoverage,
            "warnings" => $riskFlags
        ];
    }

    /**
     * Debt Ratio = (Liabilities / Assets) * 100
     * 
     * @param mixed $assets
     * @param mixed $equity
     * 
     * @return float|int
     */
    public static function getDebtRatio($assets, $equity): float|int
    {
        $liabilities = $assets - $equity;
        if ($assets == 0) {
            return 0;
        }

        return ($liabilities / $assets) * 100;
    }

    /**
     * Equity Ratio = (Equity / Assets) * 100
     * 
     * @param mixed $assets
     * @param mixed $equity
     * 
     * @return float|int
     */
    public static function getEquityRatio($assets, $equity): float|int
    {
        if ($assets == 0) {
            return 0;
        }

        return ($equity / $assets) * 100;
    }

    /**
     * Operating Margin = (Operating Income / Sales) * 100
     * 
     * @param mixed $sales
     * @param mixed $operatingIncome
     * 
     * @return float|int
     */
    public static function getOperatingMargin(mixed $sales, mixed $operatingIncome): float|int
    {
        if ($sales == 0) {
            return 0;
        }

        return ($operatingIncome / $sales) * 100;
    }

    /**
     * Net Profit Margin = (Net Income / Sales) * 100
     * 
     * @param mixed $sales
     * @param mixed $netIncome
     * 
     * @return float|int
     */
    public static function getNetMargin($sales, $netIncome): float|int
    {
        if ($sales == 0) {
            return 0;
        }

        return ($netIncome / $sales) * 100;
    }

    /**
     * Sales Growth Rate = (Current Sales - Previous Sales) / Previous Sales * 100
     * 
     * @param mixed $currentSales
     * @param mixed $previousSales
     * 
     * @return float|int
     */
    public static function getSalesGrowth($currentSales, $previousSales): float|int
    {
        if ($previousSales == 0) {
            return 0;
        }

        return (($currentSales - $previousSales) / $previousSales) * 100;
    }

    /**
     * Analyze financial status from MSS data
     * 
     * @param array<array<string, mixed>> $data
     * 
     * @return array{
     *  penalties: string,
     *  score: int,
     *  comment: string, array{
     *  debtRatio: float|int, 
     *  equityRatio: float|int, 
     *  netMargin: float|int, 
     *  operatingMargin: float|int, 
     *  salesGrowth: float|int|null, 
     *  year: mixed
     * }}|object
     */
    public static function getStatusFromMssData(array $data): array|object
    {
        $count = count($data);
        $summaries = [];

        for ($i = 0; $i < $count; $i++) {
            $current = $data[$i];
            $previous = ($i > 0) ? $data[$i - 1] : null;

            $debtRatio = self::getDebtRatio($current['assets'], $current['equity']);
            $equityRatio = self::getEquityRatio($current['assets'], $current['equity']);
            $operatingMargin = self::getOperatingMargin($current['sales'], $current['operatingIncome']);
            $netMargin = self::getNetMargin($current['sales'], $current['netIncome']);
            $salesGrowth = $previous ? self::getSalesGrowth($current['sales'], $previous['sales']) : null;

            // Save summary for later evaluation
            $summaries[] = [
                "year" => $current['year'],
                "debtRatio" => $debtRatio,
                "equityRatio" => $equityRatio,
                "operatingMargin" => $operatingMargin,
                "netMargin" => $netMargin,
                "salesGrowth" => $salesGrowth
            ];
        }

        $latest = end($summaries);

        $score = 100;

        $summaries['penalties'] = [];

        // Penalties
        if ($latest['equityRatio'] < 0) {
            $summaries['penalties'][] = "Negative equity: financial stability is weak.";
        }
        if ($latest['operatingMargin'] < 0) {
            $summaries['penalties'][] = "Operating losses: profitability is poor.";
            $score -= 20;
        }
        if ($latest['netMargin'] < 0) {
            $summaries['penalties'][] = "Net losses: company is not generating profit.";
            $score -= 20;
        }
        if ($latest['salesGrowth'] !== null && $latest['salesGrowth'] < 0) {
            $summaries['penalties'][] = "Sales are declining compared to the previous year.";
            $score -= 10;
        }
        if ($latest['debtRatio'] > 200) {
            $summaries['penalties'][] = "Excessive debt ratio: financial risk is high.";
            $score -= 20;
        }

        // Ensure score is within 0~100
        $score = max(0, min(100, $score));

        $summaries['score'] = $score;

        if ($score >= 70) {
            $summaries['comment'] = "Low Risk (Stable)\n";
        } elseif ($score >= 40) {
            $summaries['comment'] = "Medium Risk (Caution required)";
        } else {
            $summaries['comment'] = "High Risk (Avoid this company)";
        }

        return $summaries;
    }
}