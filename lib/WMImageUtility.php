<?php

class WMImageUtility
{
    public static function wmDrawMarkerCross($gdImage, $colour, $point, $size = 5)
    {
        $relative_moves = array(
            array(-1, 0),
            array(2, 0),
            array(-1, 0),
            array(0, -1),
            array(0, 2)
        );

        WMImageUtility::wmDrawMarkerPolygon($gdImage, $colour, $point, $size, $relative_moves);
    }

    /**
     * @param resource $gdImage
     * @param resource $colour
     * @param WMPoint $point
     * @param float $size
     * @param float[][] $relative_moves
     */
    public static function wmDrawMarkerPolygon($gdImage, $colour, $point, $size, $relative_moves)
    {
        $points = array();

        foreach ($relative_moves as $move) {
            $point->translate($move[0] * $size, $move[1] * $size);
            $points[] = $point->x;
            $points[] = $point->y;
        }

        imagepolygon($gdImage, $points, count($relative_moves), $colour);
    }

    public static function wmDrawMarkerDiamond($gdImage, $colour, $point, $size = 10)
    {
        $relative_moves = array(
            array(-1, 0),
            array(1, -1),
            array(1, 1),
            array(-1, 1),
        );

        WMImageUtility::wmDrawMarkerPolygon($gdImage, $colour, $point, $size, $relative_moves);
    }

    public static function wmDrawMarkerBox($gdImage, $colour, $point, $size = 10)
    {
        $relative_moves = array(
            array(-1, -1),
            array(2, 0),
            array(0, 2),
            array(-2, 0),
        );

        WMImageUtility::wmDrawMarkerPolygon($gdImage, $colour, $point, $size, $relative_moves);
    }

    public static function wmDrawMarkerCircle($gdImage, $colour, $point, $size = 10)
    {
        imagearc($gdImage, $point->x, $point->y, $size, $size, 0, 360, $colour);
    }
}
