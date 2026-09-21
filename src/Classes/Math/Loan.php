<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Exception;

class Loan
{
    /**
     * Get the interest breakdown for a loan based on the principal, annual interest rate, number of months, and loan type.
     *
     * @param int $principal The total amount of the loan.
     * @param int $annualRate The annual interest rate (in percentage).
     * @param int $months The total number of months for the loan repayment.
     * @param string $type The type of loan repayment schedule ('annuity', 'principal', or 'bullet').
     * @return array An array containing the payment schedule, total payment, and total interest for the loan.
     * @throws Exception If an invalid loan type is provided.
     */
    public static function getInterestr(int $principal, int $annualRate, int $months, string $type = 'annuity'): array
    {
        $monthlyRate = $annualRate / 12 / 100;
        $schedule = [];
        $totalPayment = 0;
        $totalInterest = 0;

        if ($type === 'annuity') { // annuity (equal payments of principal + interest)
            $monthlyPayment = $principal * $monthlyRate * pow(1 + $monthlyRate, $months) / (pow(1 + $monthlyRate, $months) - 1);
            $remaining = $principal;
            for ($i = 1; $i <= $months; $i++) {
                $interest = $remaining * $monthlyRate;
                $principalPayment = $monthlyPayment - $interest;
                $remaining -= $principalPayment;
                $schedule[] = [
                    'month' => $i,
                    'payment' => round($monthlyPayment),
                    'principal' => round($principalPayment),
                    'interest' => round($interest),
                    'balance' => round($remaining > 0 ? $remaining : 0)
                ];
                $totalPayment += $monthlyPayment;
                $totalInterest += $interest;
            }
        } elseif ($type === 'principal') { // principal-equal (equal principal payments)
            $principalPayment = $principal / $months;
            $remaining = $principal;
            for ($i = 1; $i <= $months; $i++) {
                $interest = $remaining * $monthlyRate;
                $monthlyPayment = $principalPayment + $interest;
                $remaining -= $principalPayment;
                $schedule[] = [
                    'month' => $i,
                    'payment' => round($monthlyPayment),
                    'principal' => round($principalPayment),
                    'interest' => round($interest),
                    'balance' => round($remaining > 0 ? $remaining : 0)
                ];
                $totalPayment += $monthlyPayment;
                $totalInterest += $interest;
            }
        } elseif ($type === 'bullet') { // bullet (lump-sum principal at maturity)
            for ($i = 1; $i <= $months; $i++) {
                $interest = $principal * $monthlyRate;
                $principalPayment = ($i == $months) ? $principal : 0;
                $monthlyPayment = $interest + $principalPayment;
                $schedule[] = [
                    'month' => $i,
                    'payment' => round($monthlyPayment),
                    'principal' => round($principalPayment),
                    'interest' => round($interest),
                    'balance' => round(($i == $months) ? 0 : $principal)
                ];
                $totalPayment += $monthlyPayment;
                $totalInterest += $interest;
            }
        } else {
            throw new Exception("Invalid loan type. Use 'annuity', 'principal', or 'bullet'.");
        }

        return [
            'schedule' => $schedule,
            'totalPayment' => round($totalPayment),
            'totalInterest' => round($totalInterest)
        ];
    }

    /**
     * Get the monthly breakdown of principal and interest payments for a loan.
     *
     * @param int $principal The total amount of the loan.
     * @param int $annualRate The annual interest rate (in percentage).
     * @param int $months The total number of months for the loan repayment.
     * @param string $type The type of loan repayment schedule ('annuity', 'principal', or 'bullet').
     * @return array An array containing the monthly breakdown of principal and interest payments, where each element is an associative array with keys 'month', 'principal', and 'interest'.
     * @throws Exception If an invalid loan type is provided.
     */
    public static function getMonthlyBreakdown(int $principal, int $annualRate, int $months, string $type = 'annuity'): array
    {
        $monthlyRate = $annualRate / 12 / 100;
        $breakdown = [];
        $remaining = $principal;

        if ($type === 'annuity') { // Equal principal and interest (equal installment repayment)
            $monthlyPayment = $principal * $monthlyRate * pow(1 + $monthlyRate, $months) / (pow(1 + $monthlyRate, $months) - 1);
            for ($i = 1; $i <= $months; $i++) {
                $interest = $remaining * $monthlyRate;
                $principalPayment = $monthlyPayment - $interest;
                $remaining -= $principalPayment;
                $breakdown[] = [
                    'month' => $i,
                    'principal' => round($principalPayment),
                    'interest' => round($interest)
                ];
            }
        } elseif ($type === 'principal') { // Equal principal (equal principal repayment)
            $principalPayment = $principal / $months;
            for ($i = 1; $i <= $months; $i++) {
                $interest = $remaining * $monthlyRate;
                $remaining -= $principalPayment;
                $breakdown[] = [
                    'month' => $i,
                    'principal' => round($principalPayment),
                    'interest' => round($interest)
                ];
            }
        } elseif ($type === 'bullet') { // Lump-sum at maturity (bullet repayment)
            for ($i = 1; $i <= $months; $i++) {
                $interest = $principal * $monthlyRate;
                $principalPayment = ($i === $months) ? $principal : 0;
                $breakdown[] = [
                    'month' => $i,
                    'principal' => round($principalPayment),
                    'interest' => round($interest)
                ];
            }
        } else {
            throw new Exception("Invalid loan type. Use 'annuity', 'principal', or 'bullet'.");
        }

        return $breakdown;
    }

}
