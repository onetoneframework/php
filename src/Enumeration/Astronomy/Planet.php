<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Astronomy;

enum Planet: string
{
    case MERCURY = 'mercury';
    case VENUS = 'venus';
    case EARTH = 'earth';
    case MARS = 'mars';
    case JUPITER = 'jupiter';
    case SATURN = 'saturn';
    case URANUS = 'uranus';
    case NEPTUNE = 'neptune';
    case PLUTO = 'pluto';

    private const G = 6.67430e-11;

    /**
     * @var array<array{mass: float, radius: float, semi_major_axis: float}>
     */
    private static array $data = [
        'mercury' => [
            'mass' => 3.3011e23,
            'radius' => 2.4397e6,
            'semi_major_axis' => 5.7909e10,
        ],
        'venus' => [
            'mass' => 4.8675e24,
            'radius' => 6.0518e6,
            'semi_major_axis' => 1.0821e11,
        ],
        'earth' => [
            'mass' => 5.97237e24,
            'radius' => 6.371e6,
            'semi_major_axis' => 1.495978707e11,
        ],
        'mars' => [
            'mass' => 6.4171e23,
            'radius' => 3.3895e6,
            'semi_major_axis' => 2.2794382e11,
        ],
        'jupiter' => [
            'mass' => 1.8982e27,
            'radius' => 6.9911e7,
            'semi_major_axis' => 7.7857e11,
        ],
        'saturn' => [
            'mass' => 5.6834e26,
            'radius' => 5.8232e7,
            'semi_major_axis' => 1.43353e12,
        ],
        'uranus' => [
            'mass' => 8.6810e25,
            'radius' => 2.5362e7,
            'semi_major_axis' => 2.87246e12,
        ],
        'neptune' => [
            'mass' => 1.02413e26,
            'radius' => 2.4622e7,
            'semi_major_axis' => 4.49506e12,
        ],
        'pluto' => [
            'mass' => 1.303e22,
            'radius' => 1.1883e6,
            'semi_major_axis' => 5.90638e12,
        ],
    ];

    /**
     * @return array{mass: float, radius: float, semi_major_axis: float}
     */
    private function values(): array
    {
        return self::$data[$this->value];
    }

    public function mass(): float
    {
        return $this->values()['mass'];
    }

    public function radius(): float
    {
        return $this->values()['radius'];
    }

    public function semiMajorAxis(): float
    {
        return $this->values()['semi_major_axis'];
    }

    public function volume(): float
    {
        return (4.0 / 3.0) * M_PI * ($this->radius() ** 3);
    }

    public function density(): float
    {
        $vol = $this->volume();
        return $vol > 0.0 ? $this->mass() / $vol : 0.0;
    }

    public function surfaceGravity(): float
    {
        return self::G * $this->mass() / ($this->radius() ** 2);
    }

    public function escapeVelocity(): float
    {
        return sqrt(2.0 * self::G * $this->mass() / $this->radius());
    }

    public function orbitalVelocity(): float
    {
        $a = $this->semiMajorAxis();
        if ($a <= 0.0) {
            return 0.0;
        }
        $sunMass = 1.98847e30;
        return sqrt(self::G * $sunMass / $a);
    }

    public function displayName(): string
    {
        return match ($this) {
            self::MERCURY => 'Mercury',
            self::VENUS => 'Venus',
            self::EARTH => 'Earth',
            self::MARS => 'Mars',
            self::JUPITER => 'Jupiter',
            self::SATURN => 'Saturn',
            self::URANUS => 'Uranus',
            self::NEPTUNE => 'Neptune',
            self::PLUTO => 'Pluto',
        };
    }

    public function radiusKm(): float
    {
        return $this->radius() / 1000.0;
    }

    public function massE24(): float
    {
        return $this->mass() / 1e24;
    }

    public function semiMajorAxisAu(): float
    {
        return $this->semiMajorAxis() / 1.495978707e11;
    }

    public static function fromName(string $name): ?self
    {
        $key = strtolower($name);
        foreach (self::cases() as $case) {
            if ($case->value === $key || strtolower($case->displayName()) === $key) {
                return $case;
            }
        }
        return null;
    }
}
