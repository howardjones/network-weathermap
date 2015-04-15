<?php

class WMAngledLinkGeometry extends WMLinkGeometry
{

    function calculateSpine($pointsPerSpan=5)
    {
        $nPoints = count($this->controlPoints);

        for ($i = 0; $i < ($nPoints - 1); $i ++) {
            // still subdivide the straight line, because other stuff makes assumptions about
            // how often there is a point - at least find_distance_coords_angle breaks
            $dx = ($this->controlPoints[$i + 1]->x - $this->controlPoints[$i]->x) / $pointsPerSpan;
            $dy = ($this->controlPoints[$i + 1]->y - $this->controlPoints[$i]->y) / $pointsPerSpan;

            for ($j = 0; $j < $pointsPerSpan; $j ++) {
                $x = $this->controlPoints[$i]->x + $j * $dx;
                $y = $this->controlPoints[$i]->y + $j * $dy;

                $newPoint = new WMPoint($x, $y);
                $this->curvePoints->addPoint($newPoint);
            }
        }

        $this->curvePoints->addPoint($this->controlPoints[$nPoints-1]);
    }

    function generateOutlines()
    {
        foreach ($this->directions as $direction) {
            $there = array();
            $back = array();
            $width = $this->linkWidths[$direction];

            // temporary - just a line between A and B
            $outline = array();
            $outline[] = $this->splitCurves[$direction]->points[0][0];
            $outline[] = $this->splitCurves[$direction]->points[count($this->splitCurves[$direction])-1][0];
            $outline[] = $this->splitCurves[$direction]->points[0][0];

            $this->drawnCurves[$direction] = $outline;
        }
    }

}
