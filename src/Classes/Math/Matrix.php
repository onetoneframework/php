<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use InvalidArgumentException;
use Exception;
use function is_array;
use function count;
use function intval;

/**
 * Class Matrix
 *
 * A class representing a mathematical matrix with basic operations.
 */
class Matrix
{
    /**
     * @var array The matrix data as a 2-dimensional array.
     */
    public array $data;

    /**
     * @var int The number of rows in the matrix.
     */
    public int $rows;

    /**
     * @var int The number of columns in the matrix.
     */
    public int $cols;

    /**
     * Constructor for the Matrix class.
     *
     * @param array $data The matrix data as a 2-dimensional array.
     * 
     * @throws InvalidArgumentException If the input data is not a valid 2-dimensional array
     * 
     * or if the number of columns is inconsistent across rows.
     */
    public function __construct(array $data)
    {
        if (empty($data) || !is_array($data[0])) {
            throw new InvalidArgumentException('Matrix data must be a 2-dimensional array.');
        }

        $this->data = $data;
        $this->rows = count($data);
        $this->cols = $this->rows > 0 ? count($data[0]) : 0;

        // Ensure that the number of columns is consistent across all rows.
        foreach ($this->data as $row) {
            if (!is_array($row) || count($row) !== $this->cols) {
                throw new InvalidArgumentException('All rows in the matrix must have the same number of columns.');
            }
        }
    }

    /**
     * Adds another matrix to this matrix.
     *
     * @param Matrix $other The matrix to add.
     * 
     * @return ?Matrix A new Matrix object representing the sum, or null if the matrices have different dimensions.
     */
    public function add(Matrix $other): ?Matrix
    {
        if ($this->rows !== $other->rows || $this->cols !== $other->cols) {
            return null;
        }

        $result = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $row = [];
            for ($j = 0; $j < $this->cols; $j++) {
                $row[] = $this->data[$i][$j] + $other->data[$i][$j];
            }
            $result[] = $row;
        }

        return new Matrix($result);
    }

    /**
     * Create a zero matrix.
     *
     * @param int $rows
     * @param int $cols
     *
     * @return Matrix
     */
    public static function zeros(int $rows, int $cols): Matrix
    {
        if ($rows <= 0 || $cols <= 0) {
            throw new InvalidArgumentException('Rows and cols must be positive integers.');
        }

        $data = array_fill(0, $rows, array_fill(0, $cols, 0.0));
        return new Matrix($data);
    }

    /**
     * Subtracts another matrix from this matrix.
     *
     * @param Matrix $other The matrix to subtract.
     * 
     * @return ?Matrix A new Matrix object representing the difference, or null if the matrices have different dimensions.
     */
    public function subtract(Matrix $other): ?Matrix
    {
        if ($this->rows !== $other->rows || $this->cols !== $other->cols) {
            return null;
        }

        $result = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $row = [];
            for ($j = 0; $j < $this->cols; $j++) {
                $row[] = $this->data[$i][$j] - $other->data[$i][$j];
            }
            $result[] = $row;
        }

        return new Matrix($result);
    }

    /**
     * Multiplies the matrix by a scalar value.
     *
     * @param float|int $scalar The scalar value to multiply by.
     * 
     * @return Matrix A new Matrix object representing the scalar product.
     */
    public function multiplyScalar(float|int $scalar): Matrix
    {
        $result = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $row = [];
            for ($j = 0; $j < $this->cols; $j++) {
                $row[] = $this->data[$i][$j] * $scalar;
            }
            $result[] = $row;
        }

        return new Matrix($result);
    }

    /**
     * Multiplies this matrix by another matrix.
     *
     * @param Matrix $other The matrix to multiply by.
     * 
     * @return ?Matrix A new Matrix object representing the product, or null if the matrices cannot be multiplied.
     */
    public function multiply(Matrix $other): ?Matrix
    {
        if ($this->cols !== $other->rows) {
            return null;
        }

        $result = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $row = [];
            for ($j = 0; $j < $other->cols; $j++) {
                $sum = 0;
                for ($k = 0; $k < $this->cols; $k++) {
                    $sum += $this->data[$i][$k] * $other->data[$k][$j];
                }
                $row[] = $sum;
            }
            $result[] = $row;
        }

        return new Matrix($result);
    }

        /**
     * Element-wise multiply (Hadamard product).
     *
     * @param Matrix $other
     * @return Matrix
     */
    public function elementWiseMultiply(Matrix $other): Matrix
    {
        $result = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $row = [];
            for ($j = 0; $j < $this->cols; $j++) {
                $row[] = $this->data[$i][$j] * $other->data[$i][$j];
            }
            $result[] = $row;
        }

        return new Matrix($result);
    }

    /**
     * Transposes the matrix.
     *
     * @return Matrix A new Matrix object representing the transpose of this matrix.
     */
    public function transpose(): Matrix
    {
        $result = [];
        for ($j = 0; $j < $this->cols; $j++) {
            $row = [];
            for ($i = 0; $i < $this->rows; $i++) {
                $row[] = $this->data[$i][$j];
            }
            $result[] = $row;
        }

        return new Matrix($result);
    }

    /**
     * Returns a specific row of the matrix.
     *
     * @param int $rowIndex The index of the row (0-based).
     * 
     * @return ?array The row as an array, or null if the index is out of bounds.
     */
    public function getRow(int $rowIndex): ?array
    {
        if ($rowIndex >= 0 && $rowIndex < $this->rows) {
            return $this->data[$rowIndex];
        }

        return null;
    }

    /**
     * Returns a specific column of the matrix.
     *
     * @param int $colIndex The index of the column (0-based).
     * 
     * @return ?array The column as an array, or null if the index is out of bounds.
     */
    public function getCol(int $colIndex): ?array
    {
        if ($colIndex >= 0 && $colIndex < $this->cols) {
            $col = [];
            for ($i = 0; $i < $this->rows; $i++) {
                $col[] = $this->data[$i][$colIndex];
            }

            return $col;
        }

        return null;
    }

    /**
     * Extracts a submatrix from the current matrix.
     *
     * @param int $startRow The starting row index.
     * @param int $endRow The ending row index.
     * @param int $startCol The starting column index.
     * @param int $endCol The ending column index.
     * 
     * @return ?Matrix A new Matrix object representing the submatrix, or null if the indices are invalid.
     */
    public function submatrix(int $startRow, int $endRow, int $startCol, int $endCol): ?Matrix
    {
        if ($startRow < 0 || $endRow >= $this->rows || $startCol < 0 || $endCol >= $this->cols || $startRow > $endRow || $startCol > $endCol) {
            return null;
        }

        $result = [];
        for ($i = $startRow; $i <= $endRow; $i++) {
            $row = [];
            for ($j = $startCol; $j <= $endCol; $j++) {
                $row[] = $this->data[$i][$j];
            }
            $result[] = $row;
        }

        return new Matrix($result);
    }

    /**
     * Checks if the current matrix is equal to another matrix.
     *
     * @param Matrix $other The matrix to compare with.
     * 
     * @return bool True if the matrices are equal, false otherwise.
     */
    public function equals(Matrix $other): bool
    {
        if ($this->rows !== $other->rows || $this->cols !== $other->cols) {
            return false;
        }

        for ($i = 0; $i < $this->rows; $i++) {
            for ($j = 0; $j < $this->cols; $j++) {
                if ($this->data[$i][$j] !== $other->data[$i][$j]) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Solves the matrix by cramers rule solution
     * @throws Exception
     * 
     * @return float
     */
    public function solveByCramersRule(): float
    {
        if ($this->cols != $this->rows) {
            throw new Exception('This matrix is not squarable');
        }

        $clone = array_map(function ($row) {
            return [...$row, ...array_chunk($row, $this->rows - 1)[0]];
        }, $this->data);

        $sum = fn(bool $reverse = false) => array_reduce(range(0, $this->cols - 1), function ($total, $i) use ($clone, $reverse) {
            $product = array_reduce(range(0, $this->rows - 1), function ($acc, $z) use ($clone, $i, $reverse) {
                $rowIndex = $reverse ? $this->rows - $z - 1 : $z;
                return $acc * intval($clone[$rowIndex][$i + $z]);
            }, 1);
            return $total + ($reverse ? -$product : $product);
        }, 0);

        return $sum(false) + $sum(true);
    }

    /**
     * Returns a string representation of the matrix.
     *
     * @return string The matrix as a string.
     */
    public function __toString(): string
    {
        $output = '';

        foreach ($this->data as $row) {
            $output .= '[' . implode(', ', $row) . ']' . PHP_EOL;
        }
        return $output;
    }
    
    /**
     * Apply a callback to every element and return a new Matrix.
     *
     * @param callable $fn function(float $value, int $i, int $j): float
     * @return Matrix
     */
    public function map(callable $fn): Matrix
    {
        $result = [];
        for ($i = 0; $i < $this->rows; $i++) {
            $row = [];
            for ($j = 0; $j < $this->cols; $j++) {
                $row[] = (float)$fn($this->data[$i][$j], $i, $j);
            }
            $result[] = $row;
        }
        return new Matrix($result);
    }

    /**
     * Flatten matrix to a single-dimensional array.
     *
     * @return array<int, float>
     */
    public function flatten(): array
    {
        return array_merge(...$this->data);
    }

    /**
     * Return a native 2D array copy.
     *
     * @return array<int, array<int, float>>
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
