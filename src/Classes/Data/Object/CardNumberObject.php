<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data\StringObject as StringObject;

use function intval;
use function strlen;

/**
 * Class CardNumberObject
 *
 * Represents a credit/debit card number and provides methods to validate and identify the card type.
 */
#[\AllowDynamicProperties]
class CardNumberObject extends StringObject
{
    /**
     * The raw card number string.
     *
     * @var mixed
     */
    protected $rawData;

    /**
     * Constructor for CardNumberObject.
     *
     * @param string $data The card number as a string.
     * 
     * @return void
     */
    public function __construct(string $data)
    {
        $this->rawData = $data;

        parent::__construct($data);
    }

    /**
     * Returns the card number as a string.
     *
     * @return string The card number.
     */
    public function __toString(): string
    {
        return $this->rawData;
    }

    /**
     * Checks if the card number is an American Express card.
     *
     * @return bool True if the card number is an American Express card, false otherwise.
     */
    public function isAmex(): bool
    {
        return $this->matchesPattern("^3[4|7]\d{13}$");
    }

    /**
     * Checks if the card number is a Visa card.
     *
     * @return bool True if the card number is a Visa card, false otherwise.
     */
    public function isVisa(): bool
    {
        return $this->matchesPattern("^4\d{15}$");
    }

    /**
     * Checks if the card number is a Mastercard.
     *
     * @return bool True if the card number is a Mastercard, false otherwise.
     */
    public function isMastercard(): bool
    {
        return $this->matchesPattern("^5[1|5]\d{14}$");
    }

    /**
     * Checks if the card number is a Diners Club card.
     *
     * @return bool True if the card number is a Diners Club card, false otherwise.
     */
    public function isDinersClub(): bool
    {
        return $this->matchesPattern("^(30[0-5]\d{11})|(3[68]\d{12})$");
    }

    /**
     * Checks if the card number is a Discover card.
     *
     * @return bool True if the card number is a Discover card, false otherwise.
     */
    public function isDiscover(): bool
    {
        return $this->matchesPattern("^(6011\d{12})|(5\d{14})$");
    }

    /**
     * Checks if the card number is a JCB card.
     *
     * @return bool True if the card number is a JCB card, false otherwise.
     */
    public function isJCB(): bool
    {
        return $this->matchesPattern("^([2131|1800]\d{11})|(35\d{14})$");
    }

    /**
     * Checks if the card number is a Voyager card.
     *
     * @return bool True if the card number is a Voyager card, false otherwise.
     */
    public function isVoyager(): bool
    {
        return $this->matchesPattern("^8699[0-9]{11}$");
    }

    /**
     * Checks if the card number is an Electron card.
     *
     * @return bool True if the card number is an Electron card, false otherwise.
     */
    public function isElectron(): bool
    {
        return $this->matchesPattern("^(?:417500|4917\d{2}|4913\d{2})\d{10}$");
    }

    /**
     * Checks if the card number is a Solo card.
     *
     * @return bool True if the card number is a Solo card, false otherwise.
     */
    public function isSolo(): bool
    {
        return $this->matchesPattern("^(6334[5-9][0-9]|6767[0-9]{2})\d{10}(\d{2,3})?$");
    }

    /**
     * Checks if the card number is an Enroute card.
     *
     * @return bool True if the card number is an Enroute card, false otherwise.
     */
    public function isEnroute(): bool
    {
        return $this->matchesPattern("^2(?:014|149)\d{11}$");
    }

    /**
     * Checks if the card number is a Maestro card.
     *
     * @return bool True if the card number is a Maestro card, false otherwise.
     */
    public function isMaestro(): bool
    {
        return $this->matchesPattern("^(?:5020|6\\d{3})\d{12}$");
    }

    /**
     * Validates the card number using the Luhn algorithm.
     *
     * @return bool True if the card number is valid, false otherwise.
     */
    public function isValidate(): bool
    {
        $reverse = $this->reverse()->getRawData();
        $length = strlen($reverse);
        $numberes = [];

        for ($i = 0; $i < $length; $i++) {
            $numberes[$i] = intval($reverse[$i]);

            if ($i % 2 === 0) {
                continue;
            }

            $numberes[$i] <<= 1;
            if ($numberes[$i] > 10) {
                $numberes[$i] -= 9;
            }
        }

        $sum = 0;
        for ($i = 0; $i < $length; $i++) {
            $sum += intval($numberes[$i]);
        }

        return intval($sum) % 10 === 0;
    }
}
