<?php
/***
 * Class WMLinkGeometry - everything needed to draw a link
 *
 * Actually collect all the link-drawing code into an object!
 *
 */
class WMLinkGeometry
{
    protected $linkWidths;
    protected $fillColours;
    protected $outlineColour;
    protected $directions;
    protected $splitPosition;
    protected $owner;
    protected $arrowStyle;
    protected $name;

    protected $controlPoints; // the points defined by the user for this link
    protected $curvePoints; // the calculated spine for the whole link, used for distance calculations

    protected $splitCurves; // The spines for each direction of the link
    protected $drawnCurves; // The actual list of WMPoints that will be drawn
    protected $midDistance; // The distance along to link where the split for arrowheads will be
    protected $arrowWidths; // the size
    protected $arrowPoints; // the points where an arrowhead should be started
    protected $arrowIndexes; // the index in the spines where the arrowhead takes over
    protected $midPoint; // the point where both halves meet

    /***
     * Get things started for link geometry
     *
     * @param WeatherMapLink $link
     * @param WMPoint[] $controlPoints
     * @param int[] $widths
     * @param int $directions
     * @param int $splitPosition
     * @throws Exception
     */
    function Init(&$link, $controlPoints, $widths, $directions = 2, $splitPosition=50, $arrowStyle="classic")
    {
        $this->owner = $link;
        $this->name = $link->name;
        $this->drawnCurves = array();

        if ($directions == 1) {
            $this->directions = array(OUT);
            $this->splitPosition = 100;
        } else {
            $this->directions = array(IN, OUT);
            $this->splitPosition = $splitPosition;
        }

        $this->controlPoints = $controlPoints;

        foreach ($this->directions as $direction) {
            $this->linkWidths[$direction] = $widths[$direction];
            $this->splitCurves[$direction] = new WMSpine();
            $this->drawnCurves[$direction] = array();
        }

        $this->processControlPoints();

        if (count($this->controlPoints) <= 1) {
            throw new Exception("OneDimensionalLink");
        }

        $this->arrowStyle = $arrowStyle;

        $this->curvePoints = new WMSpine();

        $this->calculateSpine();
    }

    /***
     * processControlPoints - remove duplicate points, and co-linear points from control point list
     */
    function processControlPoints()
    {
        $previousPoint = new WMPoint(-101.111, -2345234.333);

        foreach ($this->controlPoints as $key=>$cp)
        {
            if ( $cp->closeEnough($previousPoint)) {
                wm_debug("Dumping useless duplicate point on curve");
                unset($this->controlPoints[$key]);
            }
            $previousPoint = $cp;
        }
    }

    function getWidths()
    {
        return $this->linkWidths;
    }

    function setFillColours($colours)
    {
        foreach ($this->directions as $direction) {
            $this->fillColours[$direction] = $colours[$direction];
        }
    }

    function setOutlineColour($colour)
    {
        $this->outlineColour = $colour;
    }

    function calculateArrowSize($linkWidth, $arrowStyle)
    {
        // This is the default 'classic' size
        $arrowLengthFactor = 4;
        $arrowWidthFactor = 2;

        if ($arrowStyle == 'compact') {
            $arrowLengthFactor = 1;
            $arrowWidthFactor = 1;
        }

        if (preg_match('/(\d+) (\d+)/', $arrowStyle, $matches)) {
            $arrowLengthFactor = $matches[1];
            $arrowWidthFactor = $matches[2];
        }

        $arrowLength = $linkWidth * $arrowLengthFactor;
        $arrowWidth = $linkWidth * $arrowWidthFactor;

        return (array(
            $arrowLength,
            $arrowWidth
        ));
    }


    /**
     * Given a start point on the spine, calculate the points for an arrowhead.
     *
     * @param WMPoint $startPoint - a point back from the end of the spine
     * @param WMPoint $endPoint - the actual end of the spine (the point of the arrow)
     * @param int $direction - whether we're going backwards or forwards along the line
     * @param int $linkWidth - the width of the link
     * @param int $arrowWidth - the width of the arrowhead widest point
     *
     * @return WMPoint[]
     */
    function generateArrowhead($startPoint, $endPoint, $direction, $linkWidth, $arrowWidth)
    {
        $points = array();

        // Calculate a tangent
        $arrowDirection = $startPoint->vectorToPoint($endPoint);
        $arrowDirection->normalise();
        // and from that, a normal
        $arrowNormal = $arrowDirection->getNormal();

        $points[]= $startPoint->copy()->addVector($arrowNormal, $direction * $linkWidth);
        $points[]= $startPoint->copy()->addVector($arrowNormal, $direction * $arrowWidth);
        $points[]= $endPoint;
        $points[]= $startPoint->copy()->addVector($arrowNormal, $direction * -$arrowWidth);
        $points[]= $startPoint->copy()->addVector($arrowNormal, $direction * -$linkWidth);

        return $points;
    }

    function totalDistance()
    {
        return $this->curvePoints->totalDistance();
    }

    function findTangentAtIndex($index, $direction = OUT)
    {
        $step = -1 + $direction * 2;

        return $this->curvePoints->findTangentAtIndex($index, $step);
    }

    function findPointAndAngleAtPercentageDistance($targetPercentage)
    {
        return $this->curvePoints->findPointAndAngleAtPercentageDistance($targetPercentage);
    }

    function findPointAndAngleAtDistance($targetDistance)
    {
        return $this->curvePoints->findPointAndAngleAtDistance($targetDistance);
    }

    function preDraw()
    {
        $halfwayDistance = $this->curvePoints->totalDistance() * ($this->splitPosition / 100);

        // For a one-directional link, there's no split point, and one arrow
        // just copy the whole spine
        if (count($this->directions) == 1) {
            $this->splitCurves[OUT] = $this->curvePoints;
        } else {
            // for a 'normal' link, we want to split the spine into two
            // then reverse one of them, so that we can just draw two
            // arrows with exactly the same method
            list($this->splitCurves[OUT], $this->splitCurves[IN]) = $this->curvePoints->splitAtDistance($halfwayDistance);
        }

        $this->midPoint = $this->splitCurves[OUT]->lastPoint();

        foreach ($this->directions as $direction) {
            $nPoints = count($this->splitCurves[$direction]) - 1;
            $totalDistance = $this->splitCurves[$direction]->totalDistance();

            list($arrowSize, $this->arrowWidths[$direction]) = $this->calculateArrowSize($this->linkWidths[$direction], $this->arrowStyle);

            $arrowDistance = $totalDistance - $arrowSize;
            list($this->arrowPoints[$direction], $this->arrowIndexes[$direction]) = $this->splitCurves[$direction]->findPointAtDistance($arrowDistance);
        }
    }

    function draw($gdImage)
    {
        if (is_null($this->curvePoints)) {
            throw new Exception("DrawingEmptySpline");
        }

        if ( ($this->arrowWidths[IN] + $this->arrowWidths[OUT] * 1.2) > $this->curvePoints->totalDistance()) {
            wm_warn("Skipping too-short link [WMWARN50]");

            return;
        }

        $this->preDraw();
        $this->generateOutlines();

        $colour = imagecolorallocate($gdImage, 255, 0, 0);

        $linkName = $this->name;

        foreach ($this->directions as $direction)
        {
            $curve = $this->drawnCurves[$direction];
            $polyline = array();

            foreach ($curve as $point)
            {
                $polyline[] = round($point->x);
                $polyline[] = round($point->y);
            }

            if (! $this->fillColours[$direction]->isNone() ) {
                imagefilledpolygon($gdImage, $polyline, count($polyline ) / 2, $this->fillColours[$direction]->gdAllocate($gdImage) );
            } else {
                wm_debug("Not drawing $linkName ($direction) fill because there is no fill colour\n" );
            }

            if (! $this->outlineColour->isNone() ) {
                imagepolygon($gdImage, $polyline, count($polyline ) / 2, $this->outlineColour->gdAllocate($gdImage) );
            } else {
                wm_debug("Not drawing $linkName ($direction) outline because there is no outline colour\n" );
            }

        }

        # $this->curvePoints->drawSpine($gdImage, $colour);
    }
}

class WMCurvedLinkGeometry extends WMLinkGeometry
{
    function calculateSpine($pointsPerSpan=32)
    {
        // duplicate the first and last points, so that all points are drawn
        // (C-R normally would draw from x[1] to x[n-1]
        array_unshift($this->controlPoints, $this->controlPoints[0]);
        array_push($this->controlPoints, $this->controlPoints[count($this->controlPoints)-1]);

        // Loop through (nearly) all the points. C-R consumes 3 points after the one we specify, so
        // don't go all the way to the end of the list. Note that for a straight line, that means we
        // do this once only. (two original points, plus our two duplicates).
        $nPoints = count($this->controlPoints);

        // Add the very first point manually. The CRSpan function counts from 1 to avoid
        // duplication of points later in the curve
        $this->curvePoints->addPoint($this->controlPoints[0]);

        for ($i = 0; $i < ($nPoints - 3); $i ++) {
            $this->calculateCRSpan($i, $pointsPerSpan);
        }
    }

    function calculateCRSpan($startIndex, $pointsPerSpan=32)
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

            for($i=0; $i <= $this->arrowIndexes[$direction]; $i++) {
                $here = $this->splitCurves[$direction]->points[$i][0];
                $tangent = $here->vectorToPoint($this->splitCurves[$direction]->points[$i+1][0]);
                $tangent->normalise();
                $normal = $tangent->getNormal();

                $there[] = $here->copy()->addVector($normal, $width);
                array_unshift($back, $here->copy()->addVector($normal, -$width));
            }

            $arrow = $this->generateArrowhead($this->arrowPoints[$direction], $this->midPoint, 1, $this->linkWidths[$direction], $this->arrowWidths[$direction]);
            $outline = array_merge($there, $arrow, $back);

            $this->drawnCurves[$direction] = $outline;
        }
    }
}

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

    function olddraw($gdImage)
    {
        if (is_null($this->curvePoints)) {
            throw new Exception("DrawingEmptySpline");
        }
        $colour = imagecolorallocate($gdImage, 255, 0, 0);
        $colour2 = imagecolorallocate($gdImage, 0, 255, 0);
        $colour3 = imagecolorallocate($gdImage, 0, 0, 255);

        $this->curvePoints->drawSpine($gdImage, $colour);

        $this->preDraw();

        $splitDistance = $this->curvePoints->totalDistance() * ($this->splitPosition/100);
        list($halfwayPoint, $halfwayIndex) = $this->curvePoints->findPointAtDistance($splitDistance);
        wmDrawMarkerDiamond($gdImage, $colour3, $halfwayPoint->x, $halfwayPoint->y);

        $this->splitCurves[OUT]->drawSpine($gdImage, $colour2);
        $this->splitCurves[IN]->drawSpine($gdImage, $colour3);

        wmDrawMarkerDiamond($gdImage, $colour3, $this->arrowPoints[IN]->x, $this->arrowPoints[IN]->y, 5);
        wmDrawMarkerDiamond($gdImage, $colour3, $this->arrowPoints[OUT]->x, $this->arrowPoints[OUT]->y, 5);
    }
}

class WMLinkGeometryFactory
{
    function create($style)
    {
        if ($style=='angled') {
            return new WMAngledLinkGeometry();
        }
        if ($style=='curved') {
            return new WMCurvedLinkGeometry();
        }

        throw new Exception("UnexpectedViaStyle");
    }

}