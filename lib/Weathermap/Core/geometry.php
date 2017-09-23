<?php

namespace Weathermap\Core;

function getTriangleArea($point1, $point2, $point3)
{
    $area = abs($point1->x * ($point2->y - $point3->y)
            + $point2->x * ($point3->y - $point1->y)
            + $point3->x * ($point1->y - $point2->y)) / 2.0;

    return $area;
}


/**
 * rotate a list of points around cx,cy by an angle in radians, IN PLACE
 *
 * TODO: This should be using WMPoints! (And should be a method of WMPoint)
 *
 * @param array $points array of ordinates (x,y,x,y,x,y...)
 * @param float $centre_x centre of rotation, X coordinate
 * @param float $centre_y centre of rotation, Y coordinate
 * @param int $angle angle in radians
 */
function rotateAboutPoint(&$points, $centre_x, $centre_y, $angle = 0)
{
    $nPoints = count($points) / 2;

    for ($i = 0; $i < $nPoints; $i++) {
        $delta_x = $points[$i * 2] - $centre_x;
        $delta_y = $points[$i * 2 + 1] - $centre_y;
        $rotated_x = $delta_x * cos($angle) - $delta_y * sin($angle);
        $rotated_y = $delta_y * cos($angle) + $delta_x * sin($angle);

        $points[$i * 2] = $rotated_x + $centre_x;
        $points[$i * 2 + 1] = $rotated_y + $centre_y;
    }
}
