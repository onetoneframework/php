<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Data\{Matrix4Object, Vector3Object};

/**
 * Class StageObject
 *
 * Provides static methods for 3D transformations and matrix operations.
 */
class StageObject
{
    /**
     * Creates a Vector3Object from an array.
     *
     * @param array $a An array containing three elements representing x, y, and z components.
     * 
     * @return Vector3Object The created Vector3Object.
     */
    public static function vecor3FromArray(array $a): Vector3Object
    {
        return new Vector3Object($a[0], $a[1], $a[2]);
    }

    /**
     * Computes the cross product of two Vector3Objects.
     *
     * @param Vector3Object $a The first vector.
     * @param Vector3Object $b The second vector.
     * 
     * @return Vector3Object The resulting cross product vector.
     */
    public static function crossProduct(Vector3Object $a, Vector3Object $b): Vector3Object
    {
        return $a->crossProduct($b);
    }

    /**
     * Normalizes a Vector3Object.
     *
     * @param Vector3Object $a The vector to normalize.
     * 
     * @return Vector3Object The normalized vector.
     */
    public static function normalize(Vector3Object $a): Vector3Object
    {
        return $a->normalize();
    }

    /**
     * Converts degrees to radians.
     *
     * @param float $degrees The angle in degrees.
     * 
     * @return float The angle in radians.
     */
    public static function radians(float $degrees): float
    {
        return deg2rad($degrees);
    }

    /**
     * Creates a look-at matrix for a camera.
     *
     * @param Vector3Object $eye The position of the camera.
     * @param Vector3Object $center The point the camera is looking at.
     * @param Vector3Object $up The up direction for the camera.
     * 
     * @return Matrix4Object The resulting look-at matrix.
     */
    public static function lookAt(Vector3Object $eye, Vector3Object $center, Vector3Object $up): Matrix4Object
    {
        $cameraDirection = $eye->substract($center)->normalize();
        $cameraRight = $up->crossProduct($cameraDirection)->normalize();
        $cameraUp = $cameraDirection->crossProduct($cameraRight);

        $orientation = new Matrix4Object([
            [$cameraRight->x, $cameraUp->x, $cameraDirection->x, 0],
            [$cameraRight->y, $cameraUp->y, $cameraDirection->y, 0],
            [$cameraRight->z, $cameraUp->z, $cameraDirection->z, 0],
            [0, 0, 0, 1.0],
        ]);
        $translation = new Matrix4Object([
            [1, 0, 0, 0],
            [0, 1, 0, 0],
            [0, 0, 1, 0],
            [-$cameraRight->dotProduct($eye), -$cameraUp->dotProduct($eye), -$cameraDirection->dotProduct($eye), 1],
        ]);

        return $orientation->multiply($translation);
    }

    /**
     * Creates a perspective projection matrix.
     *
     * @param float $fovy The field of view in the y direction, in degrees.
     * @param float $aspect The aspect ratio of the viewport.
     * @param float $zNear The near clipping plane distance.
     * @param float $zFar The far clipping plane distance.
     * 
     * @return Matrix4Object The resulting perspective projection matrix.
     */
    public static function perspective(float $fovy, float $aspect, float $zNear, float $zFar): Matrix4Object
    {
        $fovy = deg2rad($fovy);
        $f = 1 / tan($fovy / 2);

        $result = new Matrix4Object([
            [$f / $aspect, 0, 0, 0,],
            [0, $f, 0, 0],
            [0, 0, ($zFar + $zNear) / ($zNear - $zFar), -1],
            [0, 0, (-2 * $zFar * $zNear) / ($zFar - $zNear), 0]
        ]);

        return $result;
    }

    /**
     * Translates a matrix by a given vector.
     *
     * @param Matrix4Object $m The matrix to translate.
     * @param Vector3Object $v The translation vector.
     * 
     * @return Matrix4Object The translated matrix.
     */
    public static function translate(Matrix4Object $m, Vector3Object $v): Matrix4Object
    {
        $translation = new Matrix4Object([
            [1.0, 0.0, 0.0, 0],
            [0.0, 1.0, 0.0, 0],
            [0.0, 0.0, 1.0, 0],
            [$v->x, $v->y, $v->z, 1.0]
        ]);

        return $translation->multiply($m);
    }

    /**
     * Scales a matrix by a given vector.
     *
     * @param Matrix4Object $m The matrix to scale.
     * @param Vector3Object $v The scaling vector.
     * 
     * @return Matrix4Object The scaled matrix.
     */
    public static function scale(Matrix4Object $m, Vector3Object $v): Matrix4Object
    {
        $d = self::value_ptr($m);

        return new Matrix4Object([
            [$d[0] * $v->x, $d[1] * $v->x, $d[2] * $v->x, $d[3] * $v->x],
            [$d[4] * $v->y, $d[5] * $v->y, $d[6] * $v->y, $d[7] * $v->y],
            [$d[8] * $v->z, $d[9] * $v->z, $d[10] * $v->z, $d[11] * $v->z],
            [$d[12], $d[13], $d[14], $d[15]]
        ]);
    }

    /**
     * Rotates a matrix around a given axis by a specified angle.
     *
     * @param Matrix4Object $m The matrix to rotate.
     * @param float $angle The angle in degrees to rotate the matrix.
     * @param Vector3Object $normal The axis to rotate around.
     * 
     * @return Matrix4Object The rotated matrix.
     */
    public static function rotate(Matrix4Object $m, float $angle, Vector3Object $normal): Matrix4Object
    {
        $angle = deg2rad($angle);
        if ($normal->x) {
            $rotation = new Matrix4Object([
                [1.0, 0.0, 0.0, 0.0],
                [0, cos($angle), -sin($angle), 0],
                [0, sin($angle), cos($angle), 0],
                [0.0, 0.0, 0.0, 1.0]
            ]);
            $rotation = $rotation->multiply($m);
        }

        if ($normal->y) {
            $rotation = new Matrix4Object([
                [cos($angle), 0, sin($angle), 0],
                [0.0, 1.0, 0.0, 0.0],
                [-sin($angle), 0, cos($angle), 0],
                [0.0, 0.0, 0.0, 1.0]
            ]);
            $rotation = $rotation->multiply($m);
        }

        if ($normal->z) {
            $rotation = new Matrix4Object([
                [cos($angle), -sin($angle), 0.0, 0],
                [sin($angle), cos($angle), 0.0, 0],
                [0.0, 0.0, 1.0, 0.0],
                [0.0, 0.0, 0.0, 1.0]
            ]);
            $rotation = $rotation->multiply($m);
        }

        return $rotation;
    }

    /**
     * Retrieves the value pointer of a Matrix4Object as a flat array.
     *
     * @param Matrix4Object $matrix The matrix to retrieve the values from.
     * 
     * @return array The flat array of matrix values.
     */
    public static function value_ptr(Matrix4Object $matrix): array
    {
        $array = $matrix->toArray();
        return array_merge($array[0], $array[1], $array[2], $array[3]);
    }
}