<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use function sprintf;

/**
 * Class Point
 *
 * A class representing a point in 3D space with rotation methods.
 */
class Point
{
    public float|int $x;
    public float|int $y;
    public float|int $z;

    /**
     * Point constructor.
     *
     * @param float|int $x The x-coordinate of the point.
     * @param float|int $y The y-coordinate of the point.
     * @param float|int $z The z-coordinate of the point.
     */
    public function __construct(float|int $x, float|int $y, float|int $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * Rotate the point around the Y-axis by a given angle.
     * @param Point $p The point to rotate.
     * @param float|int $angle The angle in radians to rotate the point.
     * @return Point A new Point that is the result of the rotation.
     */
    public function rotateY(Point $p, float|int $angle): Point
    {
        $s = sin($angle);
        $c = cos($angle);

        $x = $p->x * $c + $p->z * $s;
        $z = -$p->x * $s + $p->z * $c;

        return new Point($x, $p->y, $z);
    }

    /**
     * Rotate the point around the X-axis by a given angle.
     * Rotation matrix: [1, 0, 0; 0, cos(θ), -sin(θ); 0, sin(θ), cos(θ)]
     *
     * @param Point $p     The point to rotate.
     * @param float|int $angle The angle in radians to rotate the point.
     * 
     * @return Point A new Point that is the result of the rotation.
     */
    public function rotateX(Point $p, float|int $angle): Point
    {
        $s = sin($angle);
        $c = cos($angle);

        $y = $p->y * $c - $p->z * $s;
        $z = $p->y * $s + $p->z * $c;

        return new Point($p->x, $y, $z);
    }

    /**
     * Rotate the point around the Z-axis by a given angle.
     * Rotation matrix: [cos(θ), -sin(θ), 0; sin(θ), cos(θ), 0; 0, 0, 1]
     *
     * @param Point $p     The point to rotate.
     * @param float|int $angle The angle in radians to rotate the point.
     * 
     * @return Point A new Point that is the result of the rotation.
     */
    public function rotateZ(Point $p, float|int $angle): Point
    {
        $s = sin($angle);
        $c = cos($angle);

        $x = $p->x * $c - $p->y * $s;
        $y = $p->x * $s + $p->y * $c;

        return new Point($x, $y, $p->z);
    }

    /**
     * Rotate the point around an arbitrary axis (Rodrigues' rotation formula).
     * Formula: v_rot = v*cos(θ) + (k×v)*sin(θ) + k*(k·v)*(1-cos(θ))
     *
     * @param Point $p      The point to rotate.
     * @param array $axis   The axis [x, y, z] (should be normalized).
     * @param float|int $angle  The angle in radians to rotate.
     * 
     * @return Point A new Point that is the result of the rotation.
     */
    public function rotateArbitraryAxis(Point $p, array $axis, float|int $angle): Point
    {
        $ux = $axis[0];
        $uy = $axis[1];
        $uz = $axis[2];
        $cos = cos($angle);
        $sin = sin($angle);
        $ocos = 1 - $cos;

        $dotProduct = $p->x * $ux + $p->y * $uy + $p->z * $uz;

        $x = $p->x * $cos
            + ($uy * $p->z - $uz * $p->y) * $sin
            + $ux * $dotProduct * $ocos;
        $y = $p->y * $cos
            + ($uz * $p->x - $ux * $p->z) * $sin
            + $uy * $dotProduct * $ocos;
        $z = $p->z * $cos
            + ($ux * $p->y - $uy * $p->x) * $sin
            + $uz * $dotProduct * $ocos;

        return new Point($x, $y, $z);
    }

    /**
     * Translate the point by a given offset.
     *
     * @param Point $p       The point to translate.
     * @param float $offsetX The offset in the x direction.
     * @param float $offsetY The offset in the y direction.
     * @param float $offsetZ The offset in the z direction.
     * 
     * @return Point A new Point that is the result of the translation.
     */
    public function translate(Point $p, float $offsetX, float $offsetY, float $offsetZ): Point
    {
        return new Point(
            $p->x + $offsetX,
            $p->y + $offsetY,
            $p->z + $offsetZ
        );
    }

    /**
     * Scale the point by a given factor.
     *
     * @param Point $p      The point to scale.
     * @param float $scaleX The scale factor in the x direction.
     * @param float $scaleY The scale factor in the y direction.
     * @param float $scaleZ The scale factor in the z direction.
     * 
     * @return Point A new Point that is the result of the scaling.
     */
    public function scale(Point $p, float $scaleX, float $scaleY, float $scaleZ): Point
    {
        return new Point(
            $p->x * $scaleX,
            $p->y * $scaleY,
            $p->z * $scaleZ
        );
    }

    /**
     * Calculate the distance from this point to another point.
     * Formula: √((x₂-x₁)² + (y₂-y₁)² + (z₂-z₁)²)
     *
     * @param Point $other The other point.
     * 
     * @return float The Euclidean distance between the two points.
     */
    public function distance(Point $other): float
    {
        $dx = $this->x - $other->x;
        $dy = $this->y - $other->y;
        $dz = $this->z - $other->z;

        return sqrt($dx * $dx + $dy * $dy + $dz * $dz);
    }

    /**
     * Calculate the squared distance from this point to another point.
     * Useful for ranking/comparison without the sqrt computation.
     * Formula: (x₂-x₁)² + (y₂-y₁)² + (z₂-z₁)²
     *
     * @param Point $other The other point.
     * 
     * @return float The squared Euclidean distance.
     */
    public function distanceSquared(Point $other): float
    {
        $dx = $this->x - $other->x;
        $dy = $this->y - $other->y;
        $dz = $this->z - $other->z;

        return $dx * $dx + $dy * $dy + $dz * $dz;
    }

    /**
     * Get the magnitude (length) of the point vector from origin.
     * Formula: √(x² + y² + z²)
     *
     * @return float The magnitude of the point vector.
     */
    public function magnitude(): float
    {
        return sqrt($this->x * $this->x + $this->y * $this->y + $this->z * $this->z);
    }

    /**
     * Get the normalized point (unit vector).
     * Formula: (x/||v||, y/||v||, z/||v||)
     *
     * @return Point A new Point representing the unit vector.
     */
    public function normalize(): Point
    {
        $mag = $this->magnitude();

        if ($mag === 0) {
            return new Point(0, 0, 0);
        }

        return new Point($this->x / $mag, $this->y / $mag, $this->z / $mag);
    }

    /**
     * Calculate the dot product with another point vector.
     * Formula: x₁·x₂ + y₁·y₂ + z₁·z₂
     *
     * @param Point $other The other point.
     * 
     * @return float The dot product.
     */
    public function dotProduct(Point $other): float
    {
        return $this->x * $other->x + $this->y * $other->y + $this->z * $other->z;
    }

    /**
     * Calculate the cross product with another point vector.
     * Formula: (y₁·z₂ - z₁·y₂, z₁·x₂ - x₁·z₂, x₁·y₂ - y₁·x₂)
     *
     * @param Point $other The other point.
     * 
     * @return Point A new Point representing the cross product vector.
     */
    public function crossProduct(Point $other): Point
    {
        $x = $this->y * $other->z - $this->z * $other->y;
        $y = $this->z * $other->x - $this->x * $other->z;
        $z = $this->x * $other->y - $this->y * $other->x;

        return new Point($x, $y, $z);
    }

    /**
     * Get the x-coordinate.
     *
     * @return float
     */
    public function getX(): float
    {
        return $this->x;
    }

    /**
     * Get the y-coordinate.
     *
     * @return float
     */
    public function getY(): float
    {
        return $this->y;
    }

    /**
     * Get the z-coordinate.
     *
     * @return float
     */
    public function getZ(): float
    {
        return $this->z;
    }

    /**
     * Convert the point to an array representation.
     *
     * @return array [x, y, z]
     */
    public function toArray(): array
    {
        return [$this->x, $this->y, $this->z];
    }

    /**
     * Get string representation of the point.
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('Point(%.2f, %.2f, %.2f)', $this->x, $this->y, $this->z);
    }
}
