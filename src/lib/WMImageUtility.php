<?php

class WMImageUtility
{
    public static function wmDrawMarkerCross($gdImage, $colour, $point, $size = 5)
    {
        $relative_moves = array(
            array(-1,0),
            array(2,0),
            array(-1,0),
            array(0,-1),
            array(0,2)
        );

        wmDrawMarkerPolygon($gdImage, $colour, $point, $size, $relative_moves);
    }

    public static function wmDrawMarkerDiamond($gdImage, $colour, $point, $size = 10)
    {
        $relative_moves = array(
            array(-1,0),
            array(1,-1),
            array(1,1),
            array(-1,1),
        );

        wmDrawMarkerPolygon($gdImage, $colour, $point, $size, $relative_moves);
    }

    public static function wmDrawMarkerBox($gdImage, $colour, $point, $size = 10)
    {
        $relative_moves = array(
            array(-1,-1),
            array(2,0),
            array(0,2),
            array(-2,0),
        );

        wmDrawMarkerPolygon($gdImage, $colour, $point, $size, $relative_moves);
    }

    public static function wmDrawMarkerCircle($gdImage, $colour, $point, $size = 10)
    {
        imagearc($gdImage, $point->x, $point->y, $size, $size, 0, 360, $colour);
    }

    /**
     * @param $gdImage
     * @param $colour
     * @param $point
     * @param $size
     * @param $relative_moves
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
}
