<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Clover\Classes\OperationSystem;
use function in_array;

class DronePathOptimizer
{
    public static function calculateDistance3D(array $point1, array $point2): string
    {
        $dx_sq = AdvancedMathBCMath::bcpow(AdvancedMathBCMath::bcsub($point1[0], $point2[0]), 2);
        $dy_sq = AdvancedMathBCMath::bcpow(AdvancedMathBCMath::bcsub($point1[1], $point2[1]), 2);
        $dz_sq = AdvancedMathBCMath::bcpow(AdvancedMathBCMath::bcsub($point1[2], $point2[2]), 2);
        return AdvancedMathBCMath::bcsqrt(AdvancedMathBCMath::bcadd(AdvancedMathBCMath::bcadd($dx_sq, $dy_sq), $dz_sq));
    }

    public static function findOptimalPathGreedy(array $startPoint, array $waypoints, array $endPoint, bool $allowRevisit = false): array
    {
        $unvisitedWaypoints = $waypoints;
        $visitedWaypointIndices = [];
        $currentLocation = $startPoint;
        $optimalPath = [];

        while (!empty($unvisitedWaypoints)) {
            $nearestWaypointIndex = -1;
            $minDistance = null;

            foreach ($unvisitedWaypoints as $index => $waypoint) {

                if (!$allowRevisit && in_array(array_search($waypoint, $waypoints, true), $visitedWaypointIndices, true)) {
                    continue;
                }

                $distance = self::calculateDistance3D($currentLocation, $waypoint);
                if ($minDistance === null || AdvancedMathBCMath::bccomp($distance, $minDistance, 20) < 0) {
                    $minDistance = $distance;
                    $nearestWaypointIndex = $index;
                }
            }

            if ($nearestWaypointIndex !== -1) {
                $originalIndex = array_search($unvisitedWaypoints[$nearestWaypointIndex], $waypoints, true);
                $optimalPath[] = $originalIndex;
                $visitedWaypointIndices[] = $originalIndex;
                $currentLocation = $unvisitedWaypoints[$nearestWaypointIndex];
                unset($unvisitedWaypoints[$nearestWaypointIndex]);
                $unvisitedWaypoints = array_values($unvisitedWaypoints);
            } else {
                break;
            }
        }

        return $optimalPath;
    }

    public static function calculateTotalDistance(array $startPoint, array $waypoints, array $endPoint, array $pathOrder): string
    {
        $totalDistance = '0';
        $currentLocation = $startPoint;

        foreach ($pathOrder as $index) {
            $totalDistance = AdvancedMathBCMath::bcadd($totalDistance, self::calculateDistance3D($currentLocation, $waypoints[$index]));
            $currentLocation = $waypoints[$index];
        }

        $totalDistance = AdvancedMathBCMath::bcadd($totalDistance, self::calculateDistance3D($currentLocation, $endPoint));

        return $totalDistance;
    }

    public static function visualizePath(array $startPoint, array $waypoints, array $endPoint, array $pathOrder, string $imagePath = 'drone_path.png', int $width = 600, int $height = 600)
    {
        ini_set('memory_limit', '512M');

        $image = imagecreatetruecolor($width, $height);
        $bgColor = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgColor);

        $black = imagecolorallocate($image, 0, 0, 0);
        $red = imagecolorallocate($image, 255, 0, 0);
        $blue = imagecolorallocate($image, 0, 0, 255);
        $green = imagecolorallocate($image, 0, 255, 0);
        $overlapColor = imagecolorallocate($image, 255, 165, 0);
        $textColor = imagecolorallocate($image, 255, 255, 255);

        $font = BASE_PATH . '/arial.ttf';
        if (!file_exists($font)) {
            $font = null;
            echo $font;
        }
        $fontSize = 10;

        $allPoints = array_merge([$startPoint], $waypoints, [$endPoint]);
        $minX = min(array_column($allPoints, 0));
        $maxX = max(array_column($allPoints, 0));
        $minY = min(array_column($allPoints, 1));
        $maxY = max(array_column($allPoints, 1));

        $rangeX = $maxX - $minX;
        $rangeY = $maxY - $minY;

        $scaleX = $rangeX == 0 ? 1 : ($width * 0.8) / $rangeX;
        $scaleY = $rangeY == 0 ? 1 : ($height * 0.8) / $rangeY;
        $scale = min($scaleX, $scaleY);

        $offsetX = ($width / 2) - ($scale * ($minX + $maxX) / 2);
        $offsetY = ($height / 2) - ($scale * ($minY + $maxY) / 2);

        $scalePoint = function ($point) use ($scale, $offsetX, $offsetY) {
            return [
                (int) ($point[0] * $scale + $offsetX),
                (int) ($point[1] * $scale + $offsetY),
            ];
        };

        list($startX, $startY) = $scalePoint($startPoint);
        imagefilledellipse($image, $startX, $startY, 30, 30, $red);
        if ($font) {
            $text = 'S';
            $textBox = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = $textBox[2] - $textBox[0];
            $textHeight = $textBox[1] - $textBox[7];
            $textX = $startX - ($textWidth / 2);
            $textY = $startY + ($textHeight / 2);
            imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $font, $text);
        }


        list($endX, $endY) = $scalePoint($endPoint);
        imagefilledellipse($image, $endX, $endY, 30, 30, $blue);
        if ($font) {
            $text = 'E';
            $textBox = imagettfbbox($fontSize, 0, $font, $text);
            $textWidth = $textBox[2] - $textBox[0];
            $textHeight = $textBox[1] - $textBox[7];
            $textX = $endX - ($textWidth / 2);
            $textY = $endY + ($textHeight / 2);
            imagettftext($image, $fontSize, 0, (int) $textX, (int) $textY, $textColor, $font, $text);
        }

        foreach ($waypoints as $index => $waypoint) {
            list($wx, $wy) = $scalePoint($waypoint);
            imagefilledellipse($image, $wx, $wy, 20, 20, $green);
            if ($font) {
                /*$text = (string)($index + 1);
                $textBox = imagettfbbox($fontSize, 0, $font, $text);
                $textWidth = $textBox[2] - $textBox[0];
                $textHeight = $textBox[1] - $textBox[7];
                $textX = $wx - ($textWidth / 2);
                $textY = $wy + ($textHeight / 2);
                imagettftext($image, $fontSize, 0, $textX, $textY, $textColor, $font, $text);*/
            }
        }


        $drawnLines = [];
        $current = $startPoint;
        $path = $pathOrder;

        foreach ($path as $index => $nextPointIdentifier) {
            $next = $waypoints[$nextPointIdentifier];
            list($x1, $y1) = $scalePoint($current);
            list($x2, $y2) = $scalePoint($next);

            $line = [$x1 . '-' . $y1, $x2 . '-' . $y2];
            sort($line);
            $lineKey = implode('-', $line);

            $lineColor = in_array($lineKey, $drawnLines) ? $overlapColor : $black;
            imageline($image, $x1, $y1, $x2, $y2, $lineColor);
            $drawnLines[] = $lineKey;

            $current = $next;
        }

        $arrowSize = 10;
        $arrowAngle = deg2rad(num: 20);
        $departureOffset = 50;

        $colors = [
            imagecolorallocate($image, 0, 0, 0),       // Black
            imagecolorallocate($image, 255, 0, 0),   // Red
            imagecolorallocate($image, 0, 0, 255),   // Blue
            imagecolorallocate($image, 255, 165, 0), // Orange
            imagecolorallocate($image, 0, 128, 0),   // Green
            imagecolorallocate($image, 128, 0, 128), // Purple
            imagecolorallocate($image, 0, 255, 255), // Cyan
        ];
        $colorIndex = 0;
        $arrowSize = 10;
        $arrowAngle = deg2rad(20);
        $arrowOffset = 20;
        $departureOffset = 15;

        $drawnLines = [];
        $current = $startPoint;
        $allPointsWithEnd = array_merge($waypoints, [$endPoint]);
        $pathWithEnd = $pathOrder;
        $pathWithEnd[] = 'end';

        foreach ($pathWithEnd as $index => $nextPointIdentifier) {
            $next = ($nextPointIdentifier === 'end') ? $endPoint : $waypoints[$nextPointIdentifier];
            list($x1, $y1) = $scalePoint($current);
            list($x2, $y2) = $scalePoint($next);

            $line = [$x1 . '-' . $y1, $x2 . '-' . $y2];
            sort($line);
            $lineKey = implode('-', $line);

            $lineColor = $colors[$colorIndex % count($colors)];
            if (in_array($lineKey, $drawnLines)) {
                $colorIndex++;
                $lineColor = $colors[$colorIndex % count($colors)];
            }

            if ($nextPointIdentifier === 'end') {
                $angle = atan2($y2 - $y1, $x2 - $x1);
                $offsetX = $departureOffset * cos($angle);
                $offsetY = $departureOffset * sin($angle);
                $startXOffset = $x2 - $offsetX;
                $startYOffset = $y2 - $offsetY;
                imageline($image, (int) $x1, (int) $y1, (int) $startXOffset, (int) $startYOffset, $lineColor);
            } else {
                imageline($image, (int) $x1, (int) $y1, (int) $x2, (int) $y2, $lineColor);
            }
            $drawnLines[] = $lineKey;


            if ($index < count($pathWithEnd) - 1) {
                $angle = atan2($y2 - $y1, $x2 - $x1);
                $arrowOffsetX = $arrowOffset * cos($angle);
                $arrowOffsetY = $arrowOffset * sin($angle);
                $arrowBaseX = $x2 - $arrowOffsetX;
                $arrowBaseY = $y2 - $arrowOffsetY;

                $arrowX1 = $arrowBaseX - ($arrowSize) * cos($angle - $arrowAngle);
                $arrowY1 = $arrowBaseY - ($arrowSize) * sin($angle - $arrowAngle);
                $arrowX2 = $arrowBaseX - ($arrowSize) * cos($angle + $arrowAngle);
                $arrowY2 = $arrowBaseY - ($arrowSize) * sin($angle + $arrowAngle);

                imagefilledpolygon($image, [
                    $arrowBaseX,
                    $arrowBaseY,
                    $arrowX1,
                    $arrowY1,
                    $arrowX2,
                    $arrowY2
                ], 3, $lineColor);
            }

            $current = $next;
        }

        list($x1, $y1) = $scalePoint($current);
        list($x2, $y2) = $scalePoint($endPoint);
        imageline($image, $x1, $y1, $x2, $y2, $black);

        imagepng($image, $imagePath);
        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($image);
        }
    }
}
