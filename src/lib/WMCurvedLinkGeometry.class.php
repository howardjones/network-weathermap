<?php
class WMCurvedLinkGeometry extends WMLinkGeometry
{
    function calculateSpine($pointsPerSpan = 32)
    {
        // duplicate the first and last points, so that all points are drawn
        // (C-R normally would draw from x[1] to x[n-1]
        array_unshift($this->controlPoints, $this->controlPoints[0]);
        array_push($this->controlPoints, $this->controlPoints[count($this->controlPoints)-1]);

        $nPoints = count($this->controlPoints);

        // Add the very first point manually. The CRSpan function counts from 1 to avoid
        // duplication of points later in the curve
        $this->curvePoints->addPoint($this->controlPoints[0]);

        // Loop through (nearly) all the points. C-R consumes 3 points after the one we specify, so
        // don't go all the way to the end of the list. Note that for a straight line, that means we
        // do this once only. (two original points, plus our two duplicates).
        for ($i = 0; $i < ($nPoints - 3); $i ++) {
            $this->calculateCRSpan($i, $pointsPerSpan);
        }
    }

    function calculateCRSpan($startIndex, $pointsPerSpan = 32)
    {
        $cr_x = new CatmullRom1D($this->controlPoints[$startIndex]->x, $this->controlPoints[$startIndex+1]->x, $this->controlPoints[$startIndex+2]->x, $this->controlPoints[$startIndex+3]->x);
        $cr_y = new CatmullRom1D($this->controlPoints[$startIndex]->y, $this->controlPoints[$startIndex+1]->y, $this->controlPoints[$startIndex+2]->y, $this->controlPoints[$startIndex+3]->y);

        for ($i = 1; $i <= $pointsPerSpan; $i++) {
            $t = $i / $pointsPerSpan;

            $x = $cr_x->calculate($t);
            $y = $cr_y->calculate($t);

            $this->curvePoints->addPoint(new WMPoint($x, $y));
        }
    }

    function generateOutlines()
    {
        foreach ($this->directions as $direction) {
            $there = array();
            $back = array();
            $width = $this->linkWidths[$direction];

            for ($i=0; $i <= $this->arrowIndexes[$direction]; $i++) {
                $here = $this->splitCurves[$direction]->getPoint($i);
                $tangent = $here->vectorToPoint($this->splitCurves[$direction]->getPoint($i+1));
                $normal = $tangent->getNormal();

                $there[] = $here->copy()->addVector($normal, $width);
                array_unshift($back, $here->copy()->addVector($normal, -$width));
            }

            $arrow = $this->generateArrowhead($this->arrowPoints[$direction], $this->midPoint, $this->linkWidths[$direction], $this->arrowWidths[$direction]);
            $outline = array_merge($there, $arrow, $back);

            $this->drawnCurves[$direction] = $outline;
        }
    }
}
