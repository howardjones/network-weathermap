<?php

namespace Weathermap\Core;

/**
 * Maths functions that aren't directly related to geometry primitives
 *
 * @package Weathermap\Core
 */
class MathUtility
{
    /**
     * clip a value to fit within a range
     *
     * @param float|int $value
     * @param float|int $min
     * @param float|int $max
     * @return float|int
     */
    public static function clip($value, $min, $max)
    {
        return max(min($value, $max), $min);
    }

    public static function getTriangleArea($point1, $point2, $point3)
    {
        $area = abs(
            $point1->x * ($point2->y - $point3->y)
                + $point2->x * ($point3->y - $point1->y)
            + $point3->x * ($point1->y - $point2->y)
        ) / 2.0;

        return $area;
    }


    /**
     * rotate a list of points around cx,cy by an angle in radians, IN PLACE
     *
     * TODO: This should be using WMPoints! (And should be a method of WMPoint) - there is now a version in Point, too
     *
     * @param array $points array of ordinates (x,y,x,y,x,y...)
     * @param float $centreX centre of rotation, X coordinate
     * @param float $centreY centre of rotation, Y coordinate
     * @param int $radiansAngle angle in radians
     */
    public static function rotateAboutPoint(&$points, $centreX, $centreY, $radiansAngle = 0)
    {
        $nPoints = count($points) / 2;

        for ($i = 0; $i < $nPoints; $i++) {
            $deltaX = $points[$i * 2] - $centreX;
            $deltaY = $points[$i * 2 + 1] - $centreY;

            $points[$i * 2] = ($deltaX * cos($radiansAngle) - $deltaY * sin($radiansAngle)) + $centreX;
            $points[$i * 2 + 1] = $deltaY * cos($radiansAngle) + $deltaX * sin($radiansAngle) + $centreY;
        }
    }
}
