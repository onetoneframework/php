<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Exception;
use Clover\Classes\Data\ArrayObject;
use function count;
use function array_slice;
use function is_array;

/** 
 * CSVHandler provides methods to encode and decode CSV data.
 */
class CSVHandler
{
    /** 
     * Decode a CSV string into an ArrayObject.
     * @param string|StringObject $string
     * @param string $separator
     * @param string $enclosure
     * @param string $escape
     * @param bool $useFirstHeader
     * @return ArrayObject
     */
    public static function decode(string|StringObject $string, string $separator = ",", string $enclosure = "\"", string $escape = "\\", bool $useFirstHeader = false): ArrayObject
    {
        if ($string instanceof StringObject) {
            $string = $string->__toString();
        }

        $lines = explode("\n", trim($string));
        $decoded = [];

        foreach ($lines as $line) {
            $decoded[] = str_getcsv($line, $separator, $enclosure, $escape);
        }

        if (!$useFirstHeader) {
            return new ArrayObject($decoded);
        }

        $headers = [];
        $csvRows = [];
        foreach ($decoded as $row) {
            $cells = is_array($row) ? $row : explode($separator, $row[0]);
            if (empty($headers)) {
                $headers = $cells;
                continue;
            }

            $csvCells = [];
            if (count($headers) !== count($cells)) {
                throw new Exception('Count of csv cell is not equals');
            }
            foreach ($cells as $index => $cell) {
                $csvCells[$headers[$index]] = $cells[$index];
            }
            $csvRows[] = $csvCells;
        }

        return new ArrayObject($csvRows);
    }

    /** 
     * Encode an array or ArrayObject into a CSV string.
     * @param array|ArrayObject $data
     * @return string|bool
     */
    public static function encode(array|ArrayObject $data): string|bool
    {
        $fp = fopen('php://temp', 'r+');
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        return stream_get_contents($fp);
    }

    /** 
     * Check if a string is a valid CSV format.
     * @param string|StringObject $data
     * @return bool
     */
    public static function isCsv(string|StringObject $data)
    {
        if ($data instanceof StringObject) {
            $data = $data->__toString();
        }

        $lines = explode("\n", trim($data));

        if (count($lines) < 2) {
            return false;
        }

        $firstRow = str_getcsv($lines[0]);
        $numCols = count($firstRow);

        if ($numCols < 2) {
            return false;
        }

        $checkLines = array_slice($lines, 1, 5);
        foreach ($checkLines as $line) {
            $cols = str_getcsv($line);
            if (count($cols) !== $numCols) {
                return false;
            }
        }

        return true;
    }
}
