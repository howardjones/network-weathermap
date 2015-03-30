<?php

// TODO - extract spine-related stuff into WMSpine
class WMSpine
{
    var $points;
    var $totalDistance;

    function simplify()
    {

    }

    function findIndexNearDistance($target_distance)
    {

    }

    function findPointAtDistance($target_distance)
    {

    }

    function findPointAndAngleAtDistance($target_distance)
    {

    }

    function dump($spine)
    {
        print "===============\n";
        $nPoints = count($spine);
        for ($i = 0; $i < $nPoints; $i ++) {
            printf("  %3d: %d,%d (%d)\n", $i, $spine[$i][X], $spine[$i][Y], $spine[$i][DISTANCE] );
        }
        print "===============\n";
    }

    function drawSpine($gdImage, $spine, $colour)
    {
        $nPoints = count($spine ) - 1;

        for ($i = 0; $i < $nPoints; $i ++) {
            imageline($gdImage, $spine[$i][X], $spine[$i][Y], $spine[$i + 1][X], $spine[$i + 1][Y], $colour );
        }
    }

    function drawChain($gdImage, $spine, $colour, $size = 10)
    {
        $nPoints = count($spine);

        for ($i = 0; $i < $nPoints; $i ++) {
            imagearc($gdImage, $spine[$i][X], $spine[$i][Y], $size, $size, 0, 360, $colour);
        }
    }
}