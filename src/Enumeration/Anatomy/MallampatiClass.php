<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Anatomy;

/**
 * The Mallampati classification is a system used in anesthesiology to predict the ease of endotracheal intubation based on the visibility of structures in the oral cavity. 
 * It is typically assessed by having the patient open their mouth and protrude their tongue, allowing the clinician to evaluate the visibility of the soft palate, uvula, fauces, and pillars.
 */
enum MallampatiClass: int
{
    case CLASS_I = 1;
    case CLASS_II = 2;
    case CLASS_III = 3;
    case CLASS_IV = 4;

    /**
     * This method provides a descriptive label for each Mallampati class, which can be used in clinical documentation, educational materials, or any context where a clear explanation of the classification is needed.
     */
    public function label(): string
    {
        return match ($this) {
            self::CLASS_I => 'Class I — Soft palate, uvula, fauces, and pillars fully visible',
            self::CLASS_II => 'Class II — Soft palate, uvula, and fauces visible; pillars obscured',
            self::CLASS_III => 'Class III — Soft palate and base of uvula visible only',
            self::CLASS_IV => 'Class IV — Hard palate only visible; soft palate not visible',
        };
    }

    /**
     * This method assesses the difficulty of airway management based on the Mallampati classification. 
     * It returns true for Class III and IV, which are associated with a higher risk of difficult intubation, and false for Class I and II, which are generally considered easier to manage.
     */
    public function isDifficultAirway(): bool
    {
        return $this->value >= self::CLASS_III->value;
    }

    /**
     * This method provides a general assessment of the intubation risk based on the Mallampati classification. 
     * It categorizes the risk as 'Low' for Class I and II, 'Moderate' for Class III, and 'High' for Class IV.
     */
    public function intubationRisk(): string
    {
        return match ($this) {
            self::CLASS_I, self::CLASS_II => 'Low',
            self::CLASS_III => 'Moderate',
            self::CLASS_IV => 'High',
        };
    }
}
